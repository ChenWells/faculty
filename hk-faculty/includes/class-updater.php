<?php
/**
 * 更新器類別
 * 
 * 處理外掛更新的功能
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

if (!class_exists('HKFaculty\Updater')) {
    class Updater {
        private $file;
        private $plugin;
        private $basename;
        private $active;
        private $update_server;
        private $license_key;
        private $license_status;
        
        /**
         * 類別構造函數
         */
        public function __construct($file) {
            $this->file = $file;
            
            add_action('admin_init', [$this, 'set_plugin_properties']);
            
            // 添加更新檢查
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            
            // 添加插件信息
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            
            // 添加許可證設置
            add_action('admin_init', [$this, 'register_license_settings']);
            
            // 在設置頁面添加許可證管理
            add_action('hkf_after_settings', [$this, 'render_license_settings']);
            
            // 設置更新伺服器 URL
            $this->update_server = 'https://your-update-server.com/api/';
            
            // 獲取許可證密鑰
            $this->license_key = get_option('hkf_license_key', '');
            $this->license_status = get_option('hkf_license_status', 'inactive');
        }
        
        /**
         * 設置外掛屬性
         */
        public function set_plugin_properties() {
            $this->plugin   = get_plugin_data($this->file);
            $this->basename = plugin_basename($this->file);
            $this->active   = is_plugin_active($this->basename);
        }
        
        /**
         * 檢查更新
         */
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }
            
            // 發送更新檢查請求
            $response = $this->request_update_info();
            
            // 如果有新版本
            if ($response && isset($response->new_version) && version_compare($this->plugin['Version'], $response->new_version, '<')) {
                // 設置更新信息
                $transient->response[$this->basename] = $response;
            }
            
            return $transient;
        }
        
        /**
         * 請求更新信息
         */
        private function request_update_info() {
            $request_args = [
                'timeout' => 15,
                'body' => [
                    'action' => 'check_update',
                    'license_key' => $this->license_key,
                    'current_version' => $this->plugin['Version'],
                    'domain' => home_url(),
                    'plugin_name' => $this->plugin['Name']
                ]
            ];
            
            // 發送 HTTP 請求
            $response = wp_remote_post($this->update_server, $request_args);
            
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return false;
            }
            
            $data = json_decode(wp_remote_retrieve_body($response));
            
            if (!is_object($data)) {
                return false;
            }
            
            // 檢查許可證狀態
            if (isset($data->license_status)) {
                update_option('hkf_license_status', $data->license_status);
                $this->license_status = $data->license_status;
                
                if ($data->license_status !== 'valid') {
                    // 如果許可證無效，不提供更新
                    return false;
                }
            }
            
            // 格式化更新數據
            $data->slug = $this->basename;
            $data->plugin = $this->basename;
            $data->url = isset($data->url) ? $data->url : $this->plugin['PluginURI'];
            
            return $data;
        }
        
        /**
         * 獲取外掛信息
         */
        public function plugin_info($result, $action, $args) {
            // 只處理對我們外掛的請求
            if ('plugin_information' !== $action ||
                !isset($args->slug) ||
                $this->basename !== $args->slug
            ) {
                return $result;
            }
            
            // 獲取外掛信息
            $response = $this->request_update_info();
            
            if ($response) {
                return $response;
            }
            
            return $result;
        }
        
        /**
         * 註冊許可證設置
         */
        public function register_license_settings() {
            register_setting('hkf_license', 'hkf_license_key', [
                'sanitize_callback' => [$this, 'sanitize_license']
            ]);
        }
        
        /**
         * 許可證字段淨化
         */
        public function sanitize_license($value) {
            // 舊的許可證密鑰
            $old = get_option('hkf_license_key');
            
            // 如果許可證已變更，重置狀態
            if ($old && $old !== $value) {
                update_option('hkf_license_status', 'inactive');
            }
            
            return sanitize_text_field($value);
        }
        
        /**
         * 渲染許可證設置
         */
        public function render_license_settings() {
            $license_key = get_option('hkf_license_key', '');
            $status = get_option('hkf_license_status', 'inactive');
            
            ?>
            <div class="hkf-settings-section">
                <h2>許可證設置</h2>
                <div class="hkf-settings-card">
                    <form method="post" action="options.php">
                        <?php settings_fields('hkf_license'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">許可證密鑰</th>
                                <td>
                                    <input type="text" name="hkf_license_key" 
                                           value="<?php echo esc_attr($license_key); ?>" 
                                           class="regular-text">
                                    
                                    <?php if ($status === 'valid') : ?>
                                        <span class="hkf-license-status valid">有效</span>
                                    <?php elseif ($status === 'expired') : ?>
                                        <span class="hkf-license-status expired">已過期</span>
                                    <?php else : ?>
                                        <span class="hkf-license-status inactive">未啟用</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($license_key)) : ?>
                                        <?php if ($status === 'valid' || $status === 'expired') : ?>
                                            <input type="submit" name="hkf_license_deactivate" 
                                                   class="button" value="停用許可證">
                                        <?php else : ?>
                                            <input type="submit" name="hkf_license_activate" 
                                                   class="button" value="啟用許可證">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button('保存許可證'); ?>
                    </form>
                </div>
            </div>
            <?php
        }
        
        /**
         * 啟用許可證
         */
        public function activate_license() {
            // 檢查權限
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // 發送啟用請求
            $response = wp_remote_post($this->update_server, [
                'timeout' => 15,
                'body' => [
                    'action' => 'activate_license',
                    'license_key' => $this->license_key,
                    'domain' => home_url(),
                    'plugin_name' => $this->plugin['Name']
                ]
            ]);
            
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return false;
            }
            
            $data = json_decode(wp_remote_retrieve_body($response));
            
            if (isset($data->status)) {
                update_option('hkf_license_status', $data->status);
                return $data->status === 'valid';
            }
            
            return false;
        }
        
        /**
         * 停用許可證
         */
        public function deactivate_license() {
            // 檢查權限
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // 發送停用請求
            $response = wp_remote_post($this->update_server, [
                'timeout' => 15,
                'body' => [
                    'action' => 'deactivate_license',
                    'license_key' => $this->license_key,
                    'domain' => home_url(),
                    'plugin_name' => $this->plugin['Name']
                ]
            ]);
            
            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                return false;
            }
            
            $data = json_decode(wp_remote_retrieve_body($response));
            
            if (isset($data->status)) {
                update_option('hkf_license_status', 'inactive');
                return true;
            }
            
            return false;
        }
    }
} 