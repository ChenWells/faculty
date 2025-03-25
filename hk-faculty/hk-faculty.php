<?php
/**
 * 教職員管理外掛
 *
 * @package HK_Faculty
 * @author 陳富國 (Fu-Kuo Chen)
 * @copyright 2023-2024 陳富國 (Fu-Kuo Chen)
 * 
 * 本檔案為專有軟體的一部分，未經授權不得複製、修改或分發
 * This file is part of proprietary software and unauthorized copying, 
 * modification or distribution is prohibited
 *
 * @wordpress-plugin
 * Plugin Name: 教職員管理
 * Plugin URI: https://fgchen.com
 * Description: 管理教師和職員資料的 WordPress 外掛，提供完整的教職員資料管理功能，包括後台管理介面和前台顯示功能。
 * Version: 1.0.0
 * Author: 陳富國 (Fu-Kuo Chen)
 * Author URI: https://github.com/fkchen
 * Text Domain: hk-faculty
 * Domain Path: /languages
 * Network: true
 */

// 如果直接訪問此文件，則退出
if (!defined('ABSPATH')) {
    exit;
}

// 定義常量
define('HKF_VERSION', '1.0.0');
define('HKF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HKF_PLUGIN_URL', plugin_dir_url(__FILE__));

// 確保 WordPress 插件功能可用（僅在需要時加載）
if (!function_exists('is_plugin_active_for_network')) {
    include_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

// 包含必要的文件
require_once HKF_PLUGIN_DIR . 'includes/class-activator.php';
require_once HKF_PLUGIN_DIR . 'includes/class-teacher-post-type.php';
require_once HKF_PLUGIN_DIR . 'includes/class-staff-post-type.php';
require_once HKF_PLUGIN_DIR . 'includes/class-admin.php';
require_once HKF_PLUGIN_DIR . 'includes/class-updater.php';

// 註冊啟動鉤子
register_activation_hook(__FILE__, ['\HKFaculty\Activator', 'activate']);

// 註冊停用鉤子 - 確保自定義文章類型的重寫規則被刷新
register_deactivation_hook(__FILE__, function() {
    // 刷新重寫規則，確保自定義文章類型的規則被移除
    flush_rewrite_rules();
});

// 註冊新網站建立時的處理程序（僅在多網站環境中）
if (is_multisite()) {
    add_action('wp_initialize_site', function($new_site) {
        \HKFaculty\Activator::new_site($new_site->blog_id);
    });
}

/**
 * 初始化外掛
 */
function hkf_init() {
    // 初始化教師文章類型
    $teacher_post_type = new \HKFaculty\TeacherPostType();
    
    // 初始化職員文章類型
    $staff_post_type = new \HKFaculty\StaffPostType();
    
    // 初始化管理界面
    $admin = new \HKFaculty\Admin();
    
    // 初始化更新器
    $updater = new \HKFaculty\Updater(__FILE__);
    
    // 加載插件文本域
    load_plugin_textdomain('hk-faculty', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * 載入前端樣式
 */
function hkf_enqueue_styles() {
    wp_enqueue_style(
        'hk-faculty-style',
        HKF_PLUGIN_URL . 'assets/css/style.css',
        [],
        HKF_VERSION
    );
    
    // 添加前端JS
    wp_enqueue_script(
        'hk-faculty-script',
        HKF_PLUGIN_URL . 'assets/js/script.js',
        ['jquery'],
        HKF_VERSION,
        true
    );
}

/**
 * 檢查插件是否在當前站點啟用
 */
function hkf_is_active_for_current_site() {
    // 插件基本名稱
    $plugin_basename = plugin_basename(__FILE__);
    
    // 如果是網絡啟用的插件，我們需要檢查當前站點是否啟用
    if (is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network($plugin_basename)) {
        // 獲取當前站點上啟用的插件
        $active_plugins = get_option('active_plugins', []);
        return in_array($plugin_basename, $active_plugins);
    }
    
    // 如果不是網絡啟用的，或者是單站點環境
    return true;
}

/**
 * 修改子網站中插件的顯示文本與功能
 */
add_filter('plugin_action_links', function($actions, $plugin_file, $plugin_data, $context) {
    // 只在子網站處理
    if (is_multisite() && !is_network_admin()) {
        // 檢查是否為我們的插件
        if ($plugin_file == plugin_basename(__FILE__)) {
            // 移除網絡啟用的文本
            if (isset($actions['network_active'])) {
                unset($actions['network_active']);
                
                // 檢查插件是否已在當前站點啟用
                $active_plugins = get_option('active_plugins', []);
                if (in_array($plugin_file, $active_plugins)) {
                    // 已啟用，顯示「停用」按鈕
                    $deactivate_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'deactivate',
                                'plugin' => $plugin_file,
                            ),
                            self_admin_url('plugins.php')
                        ),
                        'deactivate-plugin_' . $plugin_file
                    );
                    $actions['deactivate'] = '<a href="' . esc_url($deactivate_url) . '">' . __('停用', 'hk-faculty') . '</a>';
                } else {
                    // 未啟用，顯示「啟用」按鈕
                    $activate_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'activate',
                                'plugin' => $plugin_file,
                            ),
                            self_admin_url('plugins.php')
                        ),
                        'activate-plugin_' . $plugin_file
                    );
                    $actions['activate'] = '<a href="' . esc_url($activate_url) . '">' . __('啟用', 'hk-faculty') . '</a>';
                    
                    // 移除刪除按鈕，因為子網站不應該有權限刪除插件
                    if (isset($actions['delete'])) {
                        unset($actions['delete']);
                    }
                }
            }
        }
    }
    return $actions;
}, 10, 4);

/**
 * 處理子網站中的插件啟用/停用請求
 */
add_action('admin_init', function() {
    // 只在子網站處理
    if (is_multisite() && !is_network_admin() && isset($_GET['action'])) {
        $plugin_file = plugin_basename(__FILE__);
        
        // 調試: 記錄請求信息
        if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['plugin']) && $_GET['plugin'] == $plugin_file) {
            error_log('HK-Faculty: 處理插件請求, action=' . $_GET['action'] . ', plugin=' . $_GET['plugin']);
        }
        
        // 檢查是否為我們的插件的啟用/停用請求
        if (isset($_GET['plugin']) && $_GET['plugin'] == $plugin_file) {
            // 處理啟用請求
            if ($_GET['action'] == 'activate' && check_admin_referer('activate-plugin_' . $plugin_file)) {
                // 調試: 啟用過程
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('HK-Faculty: 嘗試啟用插件, nonce 檢查通過');
                }
                
                // 更新當前站點的啟用插件列表
                $active_plugins = get_option('active_plugins', array());
                if (!in_array($plugin_file, $active_plugins)) {
                    $active_plugins[] = $plugin_file;
                    update_option('active_plugins', $active_plugins);
                    
                    // 清除快取
                    wp_cache_flush();
                    
                    // 確保變更被應用
                    $active_plugins = get_option('active_plugins', array());
                    
                    // 調試: 成功啟用
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('HK-Faculty: 插件已成功啟用，準備重定向');
                        error_log('HK-Faculty: 活動插件列表: ' . print_r($active_plugins, true));
                    }
                    
                    // 添加啟用標誌到數據庫
                    update_option('hkf_activation_timestamp', time());
                    
                    // 重定向
                    wp_redirect(admin_url('plugins.php?activate=true'));
                    exit;
                }
            }
            
            // 處理停用請求
            if ($_GET['action'] == 'deactivate' && check_admin_referer('deactivate-plugin_' . $plugin_file)) {
                // 調試: 停用過程
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('HK-Faculty: 嘗試停用插件, nonce 檢查通過');
                }
                
                // 更新當前站點的啟用插件列表
                $active_plugins = get_option('active_plugins', array());
                $key = array_search($plugin_file, $active_plugins);
                if ($key !== false) {
                    unset($active_plugins[$key]);
                    update_option('active_plugins', $active_plugins);
                    
                    // 清除快取並重定向
                    wp_cache_flush();
                    
                    // 調試: 成功停用
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('HK-Faculty: 插件已成功停用，準備重定向');
                    }
                    
                    wp_redirect(admin_url('plugins.php?deactivate=true'));
                    exit;
                }
            }
        }
    }
});

// 確保在子網站啟用插件後初始化所需功能
add_action('plugins_loaded', function() {
    // 使用 hkf_is_active_for_current_site 函數檢查插件是否應該在當前站點啟用
    if (hkf_is_active_for_current_site()) {
        // 插件在當前站點已啟用，確保初始化所有功能
        add_action('init', 'hkf_init');
        add_action('wp_enqueue_scripts', 'hkf_enqueue_styles');
    } else {
        // 檢查是否是剛剛啟用的
        $plugin_file = plugin_basename(__FILE__);
        $active_plugins = get_option('active_plugins', array());
        
        // 調試: 檢查活動插件列表
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HK-Faculty plugins_loaded: 活動插件列表: ' . print_r($active_plugins, true));
        }
        
        // 如果插件在活動列表中但 hkf_is_active_for_current_site 返回 false，
        // 可能是因為某些緩存問題，強制初始化
        if (in_array($plugin_file, $active_plugins)) {
            // 調試: 強制初始化
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('HK-Faculty: 插件在活動列表中但 hkf_is_active_for_current_site 返回 false，強制初始化');
            }
            
            add_action('init', 'hkf_init');
            add_action('wp_enqueue_scripts', 'hkf_enqueue_styles');
        }
    }
});

/**
 * 修改子網站中插件的顯示狀態
 */
add_filter('all_plugins', function($plugins) {
    // 只在子網站的插件頁面執行
    if (!is_network_admin() && is_multisite()) {
        $plugin_basename = plugin_basename(__FILE__);
        if (isset($plugins[$plugin_basename])) {
            // 檢查插件是否在當前站點啟用
            $active_plugins = get_option('active_plugins', []);
            if (in_array($plugin_basename, $active_plugins)) {
                // 如果在當前站點啟用，更新顯示狀態
                $plugins[$plugin_basename]['Network'] = false;
            }
        }
    }
    return $plugins;
});

// 修改插件的網絡狀態顯示
add_filter('network_admin_plugin_action_links', function($actions, $plugin_file) {
    // 檢查是否為我們的插件
    if ($plugin_file == plugin_basename(__FILE__)) {
        // 在網絡管理頁面的插件列表中
        if (is_network_admin()) {
            // 檢查插件是否已啟用
            if (is_plugin_active_for_network($plugin_file)) {
                // 插件已啟用，應該只顯示停用選項
                // 確保刪除選項被移除
                if (isset($actions['delete'])) {
                    unset($actions['delete']);
                }
            } else {
                // 插件未啟用，應該顯示啟用和刪除選項
                // 確保刪除選項存在
                if (!isset($actions['delete'])) {
                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'delete-selected',
                                'checked[]' => $plugin_file,
                                'plugin_status' => 'all',
                            ),
                            network_admin_url('plugins.php')
                        ),
                        'bulk-plugins'
                    );
                    $actions['delete'] = '<a href="' . esc_url($delete_url) . '" class="delete" aria-label="' . esc_attr__('刪除', 'hk-faculty') . '">' . __('刪除', 'hk-faculty') . '</a>';
                }
            }
        } else if (isset($actions['network_active'])) {
            // 在子網站的插件頁面，提供標準的啟用/停用選項
            unset($actions['network_active']);
        }
    }
    return $actions;
}, 10, 2); 