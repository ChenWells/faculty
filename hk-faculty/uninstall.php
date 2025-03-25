<?php
/**
 * 教職員管理外掛卸載腳本
 * 
 * 當外掛被刪除時執行，清理所有外掛產生的數據
 * 
 * @package HK_Faculty
 * @author 陳富國 (Fu-Kuo Chen)
 * @copyright 2023-2024 陳富國 (Fu-Kuo Chen)
 * 
 * 本檔案為專有軟體的一部分，未經授權不得複製、修改或分發
 * This file is part of proprietary software and unauthorized copying, 
 * modification or distribution is prohibited
 */

// 如果未由 WordPress 調用，則退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 設置是否要完全刪除數據
$remove_all_data = get_option('hkf_remove_data_on_uninstall', false);

// 如果需要刪除所有數據
if ($remove_all_data) {
    // 在多網站環境中處理每個網站
    if (is_multisite()) {
        global $wpdb;
        
        // 獲取所有網站
        $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($site_ids as $site_id) {
            switch_to_blog($site_id);
            uninstall_for_site();
            restore_current_blog();
        }
    } else {
        // 單網站環境
        uninstall_for_site();
    }
}

/**
 * 為單一網站執行卸載操作
 */
function uninstall_for_site() {
    // 刪除自定義文章類型數據
    delete_custom_post_types();
    
    // 刪除選項
    delete_options();
    
    // 刪除角色功能
    delete_capabilities();
    
    // 刪除已安排的事件
    delete_scheduled_events();
    
    // 刪除臨時文件
    delete_temp_files();
}

/**
 * 刪除自定義文章類型數據
 */
function delete_custom_post_types() {
    // 刪除教師文章類型
    $teacher_posts = get_posts([
        'post_type' => 'teacher',
        'numberposts' => -1,
        'post_status' => 'any'
    ]);
    
    foreach ($teacher_posts as $post) {
        // 刪除文章中繼資料
        delete_post_meta_by_key('hkf_teacher_type');
        delete_post_meta_by_key('hkf_teacher_education');
        delete_post_meta_by_key('hkf_teacher_position');
        delete_post_meta_by_key('hkf_teacher_experience');
        delete_post_meta_by_key('hkf_teacher_expertise');
        delete_post_meta_by_key('hkf_teacher_email');
        delete_post_meta_by_key('hkf_teacher_phone');
        delete_post_meta_by_key('hkf_teacher_office');
        
        // 刪除文章
        wp_delete_post($post->ID, true);
    }
    
    // 刪除職員文章類型
    $staff_posts = get_posts([
        'post_type' => 'staff',
        'numberposts' => -1,
        'post_status' => 'any'
    ]);
    
    foreach ($staff_posts as $post) {
        // 刪除文章中繼資料
        delete_post_meta_by_key('hkf_staff_title');
        delete_post_meta_by_key('hkf_staff_responsibilities');
        delete_post_meta_by_key('hkf_staff_email');
        delete_post_meta_by_key('hkf_staff_phone');
        
        // 刪除文章
        wp_delete_post($post->ID, true);
    }
    
    // 清理自定義分類法
    $taxonomies = ['teacher_category', 'staff_category'];
    foreach ($taxonomies as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ]);
            
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }
}

/**
 * 刪除選項
 */
function delete_options() {
    // 刪除外掛選項
    delete_option('hkf_options');
    delete_option('hkf_version');
    delete_option('hkf_license_key');
    delete_option('hkf_license_status');
    delete_option('hkf_remove_data_on_uninstall');
    
    // 刪除多網站選項
    if (is_multisite()) {
        delete_site_option('hkf_network_options');
    }
    
    // 清理瞬態
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%hkf_transient_%'");
}

/**
 * 刪除角色功能
 */
function delete_capabilities() {
    // 獲取角色對象
    $admin_role = get_role('administrator');
    
    // 刪除外掛添加的功能
    if ($admin_role) {
        $admin_role->remove_cap('manage_hkf_teachers');
        $admin_role->remove_cap('manage_hkf_staff');
    }
}

/**
 * 刪除已安排的事件
 */
function delete_scheduled_events() {
    wp_clear_scheduled_hook('hkf_daily_maintenance');
    wp_clear_scheduled_hook('hkf_weekly_report');
}

/**
 * 刪除臨時文件
 */
function delete_temp_files() {
    // 刪除外掛可能創建的臨時文件
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/hkf-temp';
    
    if (file_exists($temp_dir) && is_dir($temp_dir)) {
        // 使用 SPL 遞迴刪除目錄
        $it = new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($temp_dir);
    }
} 