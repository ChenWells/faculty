<?php
/**
 * 管理員類別
 * 
 * 處理後台管理介面的功能
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

if (!class_exists('HKFaculty\Admin')) {
    class Admin {
        public function __construct() {
            // 添加後台樣式
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
            
            // 添加後台選單
            add_action('admin_menu', [$this, 'add_admin_menu']);
            
            // 添加後台通知
            add_action('admin_notices', [$this, 'admin_notices']);
            
            // 添加後台過濾器
            add_action('restrict_manage_posts', [$this, 'add_admin_filters']);
            add_filter('parse_query', [$this, 'filter_query']);
            
            // 添加網路管理選項（僅在多網站環境中）
            if (is_multisite()) {
                add_action('network_admin_menu', [$this, 'add_network_admin_menu']);
            }
            
            // 註冊設定
            add_action('admin_init', [$this, 'register_settings']);
        }
        
        /**
         * 載入後台樣式
         */
        public function enqueue_admin_styles($hook) {
            // 只在教師和職員編輯頁面載入
            if (in_array($hook, ['post.php', 'post-new.php'])) {
                $screen = get_current_screen();
                if (in_array($screen->post_type, ['teacher', 'staff'])) {
                    wp_enqueue_style('hkf-admin-style', HKF_PLUGIN_URL . 'assets/css/admin.css', [], HKF_VERSION);
                }
            }
        }
        
        /**
         * 添加後台選單
         */
        public function add_admin_menu() {
            // 在多站點環境中，確保即使在子網站上也能顯示菜單
            $plugin_file = plugin_basename(HKF_PLUGIN_DIR . 'hk-faculty.php');
            $network_activated = is_multisite() && is_plugin_active_for_network($plugin_file);
            
            // 添加主選單
            add_menu_page(
                '教職員管理', // 頁面標題
                '教職員管理', // 選單標題
                'manage_options', // 權限
                'hk-faculty', // 選單 slug
                [$this, 'render_dashboard_page'], // 回調函數
                'dashicons-groups', // 圖示
                5 // 位置
            );
            
            // 添加子選單
            add_submenu_page(
                'hk-faculty', // 父選單 slug
                '教職員管理儀表板', // 頁面標題
                '儀表板', // 選單標題
                'manage_options', // 權限
                'hk-faculty', // 選單 slug
                [$this, 'render_dashboard_page'] // 回調函數
            );
            
            // 添加全部教師子選單
            add_submenu_page(
                'hk-faculty', // 父選單 slug
                '全部教師', // 頁面標題
                '全部教師', // 選單標題
                'manage_options', // 權限
                'edit.php?post_type=teacher', // 選單 slug
                null // 回調函數
            );
            
            // 添加新增教師子選單
            add_submenu_page(
                'hk-faculty', // 父選單 slug
                '新增教師', // 頁面標題
                '新增教師', // 選單標題
                'manage_options', // 權限
                'post-new.php?post_type=teacher', // 選單 slug
                null // 回調函數
            );
            
            // 添加全部職員子選單
            add_submenu_page(
                'hk-faculty', // 父選單 slug
                '全部職員', // 頁面標題
                '全部職員', // 選單標題
                'manage_options', // 權限
                'edit.php?post_type=staff', // 選單 slug
                null // 回調函數
            );
            
            // 添加新增職員子選單
            add_submenu_page(
                'hk-faculty', // 父選單 slug
                '新增職員', // 頁面標題
                '新增職員', // 選單標題
                'manage_options', // 權限
                'post-new.php?post_type=staff', // 選單 slug
                null // 回調函數
            );
            
            // 添加設定子選單
            add_submenu_page(
                'hk-faculty', // 父選單 slug
                '教職員管理設定', // 頁面標題
                '設定', // 選單標題
                'manage_options', // 權限
                'hk-faculty-settings', // 選單 slug
                [$this, 'render_settings_page'] // 回調函數
            );
        }
        
        /**
         * 渲染儀表板頁面
         */
        public function render_dashboard_page() {
            // 獲取教師和職員數量
            $teacher_count = wp_count_posts('teacher')->publish;
            $staff_count = wp_count_posts('staff')->publish;
            
            ?>
            <div class="wrap">
                <h1>教職員管理儀表板</h1>
                
                <div class="hkf-dashboard-wrapper">
                    <div class="hkf-dashboard-card">
                        <h2>教師統計</h2>
                        <p>目前共有 <strong><?php echo $teacher_count; ?></strong> 位教師。</p>
                        <a href="<?php echo admin_url('edit.php?post_type=teacher'); ?>" class="button button-primary">管理教師</a>
                    </div>
                    
                    <div class="hkf-dashboard-card">
                        <h2>職員統計</h2>
                        <p>目前共有 <strong><?php echo $staff_count; ?></strong> 位職員。</p>
                        <a href="<?php echo admin_url('edit.php?post_type=staff'); ?>" class="button button-primary">管理職員</a>
                    </div>
                    
                    <div class="hkf-dashboard-card">
                        <h2>快速連結</h2>
                        <ul>
                            <li><a href="<?php echo admin_url('post-new.php?post_type=teacher'); ?>">新增教師</a></li>
                            <li><a href="<?php echo admin_url('post-new.php?post_type=staff'); ?>">新增職員</a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=hk-faculty-settings'); ?>">設定</a></li>
                        </ul>
                    </div>
                    
                    <div class="hkf-dashboard-card">
                        <h2>使用說明</h2>
                        <p>您可以使用以下 shortcode 在前台顯示教職員資料：</p>
                        <ul>
                            <li><code>[teacher_list]</code> - 顯示教師列表</li>
                            <li><code>[teacher_info id="123"]</code> - 顯示特定教師資料</li>
                            <li><code>[staff_list]</code> - 顯示職員列表</li>
                            <li><code>[staff_info id="123"]</code> - 顯示特定職員資料</li>
                        </ul>
                        <p>更多詳細說明請參考 <a href="<?php echo admin_url('admin.php?page=hk-faculty-settings'); ?>">設定頁面</a>。</p>
                    </div>
                </div>
            </div>
            <?php
        }
        
        /**
         * 渲染設定頁面
         */
        public function render_settings_page() {
            ?>
            <div class="wrap">
                <h1>教職員管理設定</h1>
                
                <div class="hkf-settings-wrapper">
                    <div class="hkf-settings-section">
                        <h2>一般設定</h2>
                        <div class="hkf-settings-card">
                            <form method="post" action="options.php">
                                <?php 
                                settings_fields('hkf_settings');
                                do_settings_sections('hkf_settings');
                                submit_button('儲存設定');
                                ?>
                            </form>
                        </div>
                    </div>
                    
                    <div class="hkf-settings-section">
                        <h2>Shortcode 使用說明</h2>
                        
                        <div class="hkf-settings-card">
                            <h3>教師列表 Shortcode</h3>
                            <p>使用 <code>[teacher_list]</code> 顯示教師列表。</p>
                            <p>可用參數：</p>
                            <ul>
                                <li><code>limit</code> - 顯示數量，預設 10</li>
                                <li><code>orderby</code> - 排序依據，預設 title</li>
                                <li><code>order</code> - 排序方式，預設 ASC</li>
                                <li><code>type</code> - 教師類型篩選，可用值：full_time（專任）、part_time（兼任）</li>
                                <li><code>show_type</code> - 是否顯示教師類型標籤，預設 true</li>
                                <li><code>columns</code> - 每列顯示的教師數量，預設 1，可用值：1-6</li>
                            </ul>
                            <p>範例：<code>[teacher_list limit="5" type="full_time" columns="3"]</code></p>
                        </div>
                        
                        <div class="hkf-settings-card">
                            <h3>教師資訊 Shortcode</h3>
                            <p>使用 <code>[teacher_info]</code> 顯示特定教師資訊。</p>
                            <p>可用參數：</p>
                            <ul>
                                <li><code>id</code> - 教師 ID，必填</li>
                                <li><code>show_photo</code> - 是否顯示照片，預設 true</li>
                                <li><code>show_type</code> - 是否顯示教師類型標籤，預設 true</li>
                            </ul>
                            <p>範例：<code>[teacher_info id="123" show_photo="true"]</code></p>
                        </div>
                        
                        <div class="hkf-settings-card">
                            <h3>職員列表 Shortcode</h3>
                            <p>使用 <code>[staff_list]</code> 顯示職員列表。</p>
                            <p>可用參數：</p>
                            <ul>
                                <li><code>limit</code> - 顯示數量，預設 10</li>
                                <li><code>orderby</code> - 排序依據，預設 title</li>
                                <li><code>order</code> - 排序方式，預設 ASC</li>
                                <li><code>columns</code> - 每列顯示的職員數量，預設 1，可用值：1-6</li>
                                <li><code>show_type</code> - 是否顯示職員類型標籤，預設 false</li>
                            </ul>
                            <p>範例：<code>[staff_list limit="5" columns="3" show_type="false"]</code></p>
                        </div>
                        
                        <div class="hkf-settings-card">
                            <h3>職員資訊 Shortcode</h3>
                            <p>使用 <code>[staff_info]</code> 顯示特定職員資訊。</p>
                            <p>可用參數：</p>
                            <ul>
                                <li><code>id</code> - 職員 ID，必填</li>
                                <li><code>show_photo</code> - 是否顯示照片，預設 true</li>
                                <li><code>show_type</code> - 是否顯示職員類型標籤，預設 false</li>
                            </ul>
                            <p>範例：<code>[staff_info id="123" show_photo="true" show_type="false"]</code></p>
                        </div>
                    </div>
                    
                    <div class="hkf-settings-section">
                        <h2>關於</h2>
                        <div class="hkf-settings-card">
                            <p>教職員管理外掛版本：<?php echo HKF_VERSION; ?></p>
                            <p>作者：陳富國 (Fu-Kuo Chen)</p>
                            <p>版權所有 © 2023-2024</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        
        /**
         * 顯示後台通知
         */
        public function admin_notices() {
            // 檢查是否有通知需要顯示
            if (isset($_GET['hkf_notice']) && $_GET['hkf_notice'] === 'duplicate_success') {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('複製成功！', 'hk-faculty'); ?></p>
                </div>
                <?php
            }
        }
        
        /**
         * 添加後台過濾器
         */
        public function add_admin_filters($post_type) {
            // 只在教師列表頁面添加過濾器
            if ($post_type === 'teacher') {
                $current_type = isset($_GET['teacher_type']) ? $_GET['teacher_type'] : '';
                ?>
                <select name="teacher_type">
                    <option value=""><?php _e('所有教師類型', 'hk-faculty'); ?></option>
                    <option value="full_time" <?php selected($current_type, 'full_time'); ?>><?php _e('專任教師', 'hk-faculty'); ?></option>
                    <option value="part_time" <?php selected($current_type, 'part_time'); ?>><?php _e('兼任教師', 'hk-faculty'); ?></option>
                </select>
                <?php
            }
        }
        
        /**
         * 處理過濾查詢
         */
        public function filter_query($query) {
            global $pagenow;
            
            // 確保我們在後台教師列表頁面
            if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'teacher' && isset($_GET['teacher_type']) && $_GET['teacher_type'] !== '') {
                // 添加元查詢條件
                $query->query_vars['meta_key'] = 'hkf_teacher_type';
                $query->query_vars['meta_value'] = $_GET['teacher_type'];
            }
            
            return $query;
        }
        
        /**
         * 添加網路管理選項
         */
        public function add_network_admin_menu() {
            add_submenu_page(
                'settings.php',
                '教職員管理網路設定',
                '教職員管理',
                'manage_network_options',
                'hk-faculty-network',
                [$this, 'render_network_settings_page']
            );
            
            // 在網絡管理選項頁面添加保存設置的處理
            add_action('network_admin_edit_hkf_network_settings', [$this, 'save_network_settings']);
        }
        
        /**
         * 渲染網路設定頁面
         */
        public function render_network_settings_page() {
            // 檢查權限
            if (!current_user_can('manage_network_options')) {
                wp_die(__('您沒有足夠的權限訪問此頁面。'));
            }

            // 獲取當前設定
            $network_options = get_site_option('hkf_network_options', [
                'enable_for_new_sites' => true,
                'sync_settings' => false,
                'default_columns' => 3
            ]);

            // 渲染設定頁面
            ?>
            <div class="wrap">
                <h1>教職員管理網路設定</h1>
                <?php 
                if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
                    echo '<div class="updated notice is-dismissible"><p>設定已保存。</p></div>';
                }
                ?>
                
                <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=hkf_network_settings')); ?>">
                    <?php wp_nonce_field('hkf-network-settings'); ?>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">新網站設定</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="hkf_enable_for_new_sites" 
                                           value="1" <?php checked($network_options['enable_for_new_sites']); ?>>
                                    自動在新網站上啟用外掛
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">設定同步</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="hkf_sync_settings" 
                                           value="1" <?php checked($network_options['sync_settings']); ?>>
                                    在所有網站間同步設定
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">預設欄數</th>
                            <td>
                                <input type="number" name="hkf_default_columns" 
                                       value="<?php echo esc_attr($network_options['default_columns']); ?>" 
                                       min="1" max="4" class="small-text">
                                <p class="description">新網站的預設顯示欄數</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('保存設定'); ?>
                </form>
            </div>
            <?php
        }

        /**
         * 保存網絡設置
         */
        public function save_network_settings() {
            if (!current_user_can('manage_network_options')) {
                wp_die(__('您沒有足夠的權限保存這些設置。'));
            }
            
            check_admin_referer('hkf-network-settings');
            
            $network_options = [
                'enable_for_new_sites' => isset($_POST['hkf_enable_for_new_sites']),
                'sync_settings' => isset($_POST['hkf_sync_settings']),
                'default_columns' => intval($_POST['hkf_default_columns'])
            ];
            
            update_site_option('hkf_network_options', $network_options);
            
            // 重定向回設置頁面
            wp_redirect(add_query_arg([
                'page' => 'hk-faculty-network',
                'updated' => 'true'
            ], network_admin_url('settings.php')));
            exit;
        }

        /**
         * 註冊外掛設定
         */
        public function register_settings() {
            // 註冊外掛設定
            register_setting('hkf_settings', 'hkf_options');
            
            // 添加設定區塊
            add_settings_section(
                'hkf_general_section',
                '一般設定',
                [$this, 'render_general_section'],
                'hkf_settings'
            );
            
            // 添加外觀設定區塊
            add_settings_section(
                'hkf_appearance_section',
                '外觀設定',
                [$this, 'render_appearance_section'],
                'hkf_settings'
            );
            
            // 註冊設定欄位
            add_settings_field(
                'hkf_teacher_columns',
                '教師顯示欄數',
                [$this, 'render_columns_field'],
                'hkf_settings',
                'hkf_general_section',
                ['type' => 'teacher']
            );
            
            add_settings_field(
                'hkf_staff_columns',
                '職員顯示欄數',
                [$this, 'render_columns_field'],
                'hkf_settings',
                'hkf_general_section',
                ['type' => 'staff']
            );
            
            add_settings_field(
                'hkf_show_type_label',
                '顯示類型標籤',
                [$this, 'render_checkbox_field'],
                'hkf_settings',
                'hkf_general_section',
                ['field' => 'show_type_label', 'label' => '在列表中顯示教師/職員類型標籤']
            );
            
            add_settings_field(
                'hkf_enable_photo_hover',
                '啟用照片懸停效果',
                [$this, 'render_checkbox_field'],
                'hkf_settings',
                'hkf_general_section',
                ['field' => 'enable_photo_hover', 'label' => '啟用照片上的懸停放大效果']
            );
            
            // 添加主題選擇欄位
            add_settings_field(
                'hkf_theme_mode',
                '顯示主題模式',
                [$this, 'render_theme_field'],
                'hkf_settings',
                'hkf_appearance_section',
                []
            );
            
            // 在多站點環境中設置同步
            if (is_multisite()) {
                add_action('updated_option', [$this, 'sync_network_settings'], 10, 3);
            }
        }
        
        /**
         * 同步網路設定到所有子網站
         */
        public function sync_network_settings($option, $old_value, $value) {
            // 僅同步我們的插件設置
            if ($option !== 'hkf_options') {
                return;
            }
            
            // 檢查是否啟用了設置同步
            $network_options = get_site_option('hkf_network_options', []);
            if (empty($network_options['sync_settings'])) {
                return;
            }
            
            // 只在主網站上執行同步
            if (!is_main_site()) {
                return;
            }
            
            // 獲取所有子網站
            $sites = get_sites(['fields' => 'ids']);
            
            // 遍歷所有子網站，同步設置
            foreach ($sites as $site_id) {
                // 跳過主網站
                if (get_main_site_id() === $site_id) {
                    continue;
                }
                
                switch_to_blog($site_id);
                update_option('hkf_options', $value);
                restore_current_blog();
            }
        }
        
        /**
         * 渲染設定區塊說明
         */
        public function render_general_section() {
            echo '<p>調整教職員管理外掛的一般設定。</p>';
        }
        
        /**
         * 渲染外觀設定區塊說明
         */
        public function render_appearance_section() {
            echo '<p>調整教職員管理外掛的外觀顯示設定。</p>';
        }
        
        /**
         * 渲染欄數欄位
         */
        public function render_columns_field($args) {
            $options = get_option('hkf_options');
            $field = $args['type'] . '_columns';
            $value = isset($options[$field]) ? $options[$field] : 3;
            
            echo '<input type="number" name="hkf_options[' . $field . ']" value="' . esc_attr($value) . '" min="1" max="4" class="small-text" />';
            echo '<p class="description">在前台顯示時的每行欄數 (1-4)</p>';
        }
        
        /**
         * 渲染核取方塊欄位
         */
        public function render_checkbox_field($args) {
            $options = get_option('hkf_options');
            $field = $args['field'];
            $checked = isset($options[$field]) && $options[$field] ? 'checked' : '';
            
            echo '<label>';
            echo '<input type="checkbox" name="hkf_options[' . $field . ']" value="1" ' . $checked . ' />';
            echo $args['label'];
            echo '</label>';
        }
        
        /**
         * 渲染主題選擇欄位
         */
        public function render_theme_field() {
            $options = get_option('hkf_options');
            $theme = isset($options['theme_mode']) ? $options['theme_mode'] : 'light';
            
            echo '<div class="theme-selector">';
            
            // 淺色主題選項
            echo '<label class="theme-option">';
            echo '<input type="radio" name="hkf_options[theme_mode]" value="light" ' . checked('light', $theme, false) . ' />';
            echo '<span class="theme-preview light-theme-preview"></span>';
            echo '淺色模式';
            echo '</label>';
            
            // 深色主題選項
            echo '<label class="theme-option">';
            echo '<input type="radio" name="hkf_options[theme_mode]" value="dark" ' . checked('dark', $theme, false) . ' />';
            echo '<span class="theme-preview dark-theme-preview"></span>';
            echo '深色模式';
            echo '</label>';
            
            echo '</div>';
            echo '<p class="description">選擇教職員資料在前台顯示的主題模式。</p>';
        }
    }
} 