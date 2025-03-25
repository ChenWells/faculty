<?php
/**
 * 啟動器類別
 * 
 * 處理外掛啟動時的操作
 * 
 * @package HK_Faculty
 * @author 陳富國 (Fu-Kuo Chen)
 * @copyright 2023-2024 陳富國 (Fu-Kuo Chen)
 * 
 * 本檔案為專有軟體的一部分，未經授權不得複製、修改或分發
 * This file is part of proprietary software and unauthorized copying, 
 * modification or distribution is prohibited
 */

namespace HKFaculty;

if (!class_exists('HKFaculty\Activator')) {
    class Activator {
        /**
         * 外掛啟動時執行的操作
         */
        public static function activate($network_wide) {
            if (is_multisite() && $network_wide) {
                // 在所有網站上執行啟動操作
                $site_ids = get_sites(['fields' => 'ids']);
                foreach ($site_ids as $site_id) {
                    switch_to_blog($site_id);
                    self::single_site_activate();
                    restore_current_blog();
                }
            } else {
                // 在單一網站上執行啟動操作
                self::single_site_activate();
            }
        }
        
        /**
         * 單一網站的啟動操作
         */
        private static function single_site_activate() {
            // 註冊自定義文章類型
            self::register_post_types();
            
            // 刷新重寫規則
            flush_rewrite_rules();
            
            // 建立或更新網站選項
            self::setup_options();
            
            // 記錄啟動日誌
            self::log_activation();
        }
        
        /**
         * 註冊自定義文章類型
         */
        private static function register_post_types() {
            global $wp_rewrite;
            
            // 註冊教師文章類型
            register_post_type('teacher', [
                'labels' => [
                    'name' => '教師',
                    'singular_name' => '教師'
                ],
                'public' => true,
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail'],
                'menu_icon' => 'dashicons-welcome-learn-more',
                'show_in_menu' => false  // 確保它不會單獨顯示在選單中
            ]);
            
            // 註冊職員文章類型
            register_post_type('staff', [
                'labels' => [
                    'name' => '職員',
                    'singular_name' => '職員'
                ],
                'public' => true,
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail'],
                'menu_icon' => 'dashicons-businessperson',
                'show_in_menu' => false  // 確保它不會單獨顯示在選單中
            ]);
            
            // 確保重寫規則正確設置
            if ($wp_rewrite) {
                $wp_rewrite->flush_rules(false);
            }
        }
        
        /**
         * 設置網站選項
         */
        private static function setup_options() {
            $default_options = [
                'version' => HKF_VERSION,
                'teacher_columns' => 3,
                'staff_columns' => 3,
                'show_type_label' => true,
                'enable_photo_hover' => true
            ];

            // 檢查是否已存在選項
            $existing_options = get_option('hkf_options');
            if ($existing_options === false) {
                // 如果不存在，則添加新選項
                add_option('hkf_options', $default_options);
            } else {
                // 如果存在，則更新選項
                update_option('hkf_options', array_merge($existing_options, [
                    'version' => HKF_VERSION
                ]));
            }
        }
        
        /**
         * 記錄啟動日誌
         */
        private static function log_activation() {
            $log_message = sprintf(
                '[%s] 教職員管理外掛已在網站 %d 上啟動，版本：%s',
                current_time('mysql'),
                get_current_blog_id(),
                HKF_VERSION
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log($log_message);
            }
        }
        
        /**
         * 處理新網站建立
         */
        public static function new_site($site_id) {
            if (is_plugin_active_for_network('hk-faculty/hk-faculty.php')) {
                switch_to_blog($site_id);
                self::single_site_activate();
                restore_current_blog();
            }
        }
    }
} 