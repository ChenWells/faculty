<?php
/**
 * 職員文章類型類
 * 
 * 處理職員資料的註冊、管理和顯示
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

if (!class_exists('HKFaculty\StaffPostType')) {
    class StaffPostType {
        private $post_type = 'staff';
        private $meta_prefix = 'hkf_staff_';

        public function __construct() {
            // 註冊文章類型
            $this->register_post_type();
            
            // 添加後台欄位
            add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
            
            // 儲存後台欄位資料
            add_action('save_post_' . $this->post_type, [$this, 'save_meta_box_data']);
            
            // 註冊 shortcode
            add_shortcode('staff_list', [$this, 'staff_list_shortcode']);
            add_shortcode('staff_info', [$this, 'staff_info_shortcode']);
            
            // 自定義後台列表欄位
            add_filter('manage_' . $this->post_type . '_posts_columns', [$this, 'set_custom_columns']);
            add_action('manage_' . $this->post_type . '_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
            add_filter('manage_edit-' . $this->post_type . '_sortable_columns', [$this, 'set_sortable_columns']);
            add_action('pre_get_posts', [$this, 'custom_orderby']);
            
            // 添加複製功能
            add_filter('post_row_actions', [$this, 'add_duplicate_action'], 10, 2);
            add_action('admin_action_duplicate_staff', [$this, 'duplicate_staff']);
        }

        public function register_post_type() {
            $labels = [
                'name'               => '職員',
                'singular_name'      => '職員',
                'menu_name'          => '職員管理',
                'name_admin_bar'     => '職員',
                'add_new'            => '新增職員',
                'add_new_item'       => '新增職員',
                'edit_item'          => '編輯職員資料',
                'new_item'           => '新職員',
                'view_item'          => '查看職員資料',
                'search_items'       => '搜尋職員',
                'not_found'          => '找不到職員資料',
                'not_found_in_trash' => '回收桶中沒有職員資料',
                'all_items'          => '全部職員',
            ];

            $args = [
                'labels'              => $labels,
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'            => true,
                'show_in_menu'       => false,
                'query_var'          => true,
                'rewrite'            => ['slug' => 'staff'],
                'capability_type'     => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => 6,
                'menu_icon'          => 'dashicons-businessperson',
                'supports'           => ['title', 'editor', 'thumbnail'],
                'show_in_rest'       => true,
            ];

            register_post_type($this->post_type, $args);
        }

        public function add_meta_boxes() {
            add_meta_box(
                'staff_details',
                '職員詳細資料',
                [$this, 'render_meta_box'],
                $this->post_type,
                'normal',
                'high'
            );
        }

        public function render_meta_box($post) {
            // 添加 nonce 欄位進行安全檢查
            wp_nonce_field('staff_meta_box', 'staff_meta_box_nonce');

            // 獲取已保存的值
            $position = get_post_meta($post->ID, $this->meta_prefix . 'position', true);
            $responsibilities = get_post_meta($post->ID, $this->meta_prefix . 'responsibilities', true);
            $email = get_post_meta($post->ID, $this->meta_prefix . 'email', true);
            $phone = get_post_meta($post->ID, $this->meta_prefix . 'phone', true);

            // 輸出表單欄位
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>position">職稱</label></th>
                    <td>
                        <input type="text" id="<?php echo $this->meta_prefix; ?>position" 
                               name="<?php echo $this->meta_prefix; ?>position" 
                               value="<?php echo esc_attr($position); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>responsibilities">工作職掌</label></th>
                    <td>
                        <textarea id="<?php echo $this->meta_prefix; ?>responsibilities" 
                                name="<?php echo $this->meta_prefix; ?>responsibilities" 
                                class="large-text" rows="5" required><?php echo esc_textarea($responsibilities); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>email">電子郵件</label></th>
                    <td>
                        <input type="email" id="<?php echo $this->meta_prefix; ?>email" 
                               name="<?php echo $this->meta_prefix; ?>email" 
                               value="<?php echo esc_attr($email); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>phone">電話/分機</label></th>
                    <td>
                        <input type="text" id="<?php echo $this->meta_prefix; ?>phone" 
                               name="<?php echo $this->meta_prefix; ?>phone" 
                               value="<?php echo esc_attr($phone); ?>" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <?php
        }

        public function save_meta_box_data($post_id) {
            // 檢查 nonce
            if (!isset($_POST['staff_meta_box_nonce']) || 
                !wp_verify_nonce($_POST['staff_meta_box_nonce'], 'staff_meta_box')) {
                return;
            }

            // 如果是自動保存，不處理
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // 檢查權限
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            // 保存欄位
            $text_fields = [
                'position',
                'email',
                'phone'
            ];
            
            $textarea_fields = [
                'responsibilities'
            ];

            // 處理單行文本字段
            foreach ($text_fields as $field) {
                $key = $this->meta_prefix . $field;
                if (isset($_POST[$key])) {
                    $value = sanitize_text_field($_POST[$key]);
                    update_post_meta($post_id, $key, $value);
                }
            }
            
            // 處理多行文本字段
            foreach ($textarea_fields as $field) {
                $key = $this->meta_prefix . $field;
                if (isset($_POST[$key])) {
                    $value = sanitize_textarea_field($_POST[$key]);
                    update_post_meta($post_id, $key, $value);
                }
            }
        }

        /**
         * 顯示職員列表的 shortcode
         * 用法: [staff_list limit="10" orderby="title" order="ASC" columns="1" show_type="false"]
         * columns 參數控制每列顯示的職員數量
         * show_type 參數控制是否顯示職員類型標籤
         */
        public function staff_list_shortcode($atts) {
            // 設置默認參數
            $atts = shortcode_atts([
                'limit' => 10,
                'orderby' => 'title',
                'order' => 'ASC',
                'columns' => 1, // 默認每列顯示1個職員
                'show_type' => 'false' // 默認不顯示職員標籤
            ], $atts, 'staff_list');
            
            // 查詢職員
            $args = [
                'post_type' => $this->post_type,
                'posts_per_page' => intval($atts['limit']),
                'orderby' => $atts['orderby'],
                'order' => $atts['order']
            ];
            
            $staff = new \WP_Query($args);
            
            // 每列顯示的職員數量
            $columns = intval($atts['columns']);
            if ($columns < 1) {
                $columns = 1;
            } elseif ($columns > 6) {
                $columns = 6; // 限制最大列數為6
            }
            
            // 是否顯示職員類型標籤
            $show_type = filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN);
            
            // 開始輸出緩衝
            ob_start();
            
            // 添加模態窗口
            echo '<div id="staff-modal" class="staff-modal">';
            echo '<div class="staff-modal-wrapper">';
            echo '<div class="staff-modal-header">';
            echo '<h3 class="staff-modal-title">工作職掌</h3>';
            echo '<span class="staff-modal-close">&times;</span>';
            echo '</div>'; // .staff-modal-header
            echo '<div class="staff-modal-body"></div>'; // 將在JavaScript中填充內容
            echo '</div>'; // .staff-modal-wrapper
            echo '</div>'; // .staff-modal
            
            if ($staff->have_posts()) {
                echo '<div class="staff-list staff-columns-' . esc_attr($columns) . '">';
                
                while ($staff->have_posts()) {
                    $staff->the_post();
                    
                    // 獲取職員資料
                    $id = get_the_ID();
                    $title = get_the_title();
                    $permalink = get_permalink();
                    $thumbnail = get_the_post_thumbnail($id, 'medium');
                    
                    // 獲取自定義欄位資料
                    $position = get_post_meta($id, $this->meta_prefix . 'position', true);
                    $responsibilities = get_post_meta($id, $this->meta_prefix . 'responsibilities', true);
                    $email = get_post_meta($id, $this->meta_prefix . 'email', true);
                    $phone = get_post_meta($id, $this->meta_prefix . 'phone', true);
                    $staff_type = get_post_meta($id, $this->meta_prefix . 'type', true);
                    
                    echo '<div class="staff-item">';
                    echo '<div class="staff-card-inner">';
                    
                    // 顯示縮略圖
                    echo '<div class="staff-thumbnail">';
                    if ($thumbnail) {
                        echo $thumbnail;
                    } else {
                        echo '<img src="' . HKF_PLUGIN_URL . 'assets/images/default-staff.png" alt="職員預設圖片">';
                    }
                    echo '</div>'; // .staff-thumbnail
                    
                    echo '<div class="staff-content">';
                    echo '<h3 class="staff-name"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';
                    
                    // 顯示職員類型標籤
                    if ($show_type && !empty($staff_type)) {
                        echo '<div class="staff-type">';
                        echo '<span class="staff-type-label">' . esc_html($staff_type) . '</span>';
                        echo '</div>'; // .staff-type
                    }
                    
                    echo '<div class="staff-details-list">';
                    
                    // 顯示職位
                    if (!empty($position)) {
                        echo '<div class="staff-position"><b>職稱：</b>' . esc_html($position) . '</div>';
                    }
                    
                    // 顯示工作職掌
                    if (!empty($responsibilities)) {
                        echo '<div class="staff-responsibilities">';
                        echo '<button class="staff-resp-btn" data-staff-id="' . esc_attr($id) . '">查看工作職掌</button>';
                        echo '<div class="staff-modal-content" id="staff-modal-' . esc_attr($id) . '" style="display:none;">';
                        echo '<div class="staff-resp-content">' . nl2br(esc_html($responsibilities)) . '</div>';
                        echo '</div>'; // .staff-modal-content
                        echo '</div>'; // .staff-responsibilities
                    }
                    
                    // 顯示聯絡資訊
                    echo '<div class="staff-contact-info">';
                    
                    if (!empty($email)) {
                        echo '<div class="staff-email"><b>電子郵件：</b> <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>';
                    }
                    
                    if (!empty($phone)) {
                        echo '<div class="staff-phone"><b>電話/分機：</b>' . esc_html($phone) . '</div>';
                    }
                    
                    echo '</div>'; // .staff-contact-info
                    
                    echo '</div>'; // .staff-details-list
                    echo '</div>'; // .staff-content
                    echo '</div>'; // .staff-card-inner
                    echo '</div>'; // .staff-item
                }
                
                echo '</div>'; // .staff-list
                
                // 重置查詢
                wp_reset_postdata();
            } else {
                echo '<div class="staff-empty"><p class="staff-empty-text">目前沒有職員資料。</p></div>';
            }
            
            // 獲取輸出緩衝內容
            return ob_get_clean();
        }
        
        /**
         * 顯示單個職員資訊的 shortcode
         * 用法: [staff_info id="123" show_photo="true" show_type="false"]
         */
        public function staff_info_shortcode($atts) {
            // 設置默認參數
            $atts = shortcode_atts([
                'id' => 0,
                'show_photo' => 'true',
                'show_type' => 'false' // 默認不顯示職員類型
            ], $atts, 'staff_info');
            
            $staff_id = intval($atts['id']);
            $show_photo = filter_var($atts['show_photo'], FILTER_VALIDATE_BOOLEAN);
            $show_type = filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN);
            
            // 如果沒有指定 ID，返回空
            if ($staff_id <= 0) {
                return '<p class="staff-error">請指定職員 ID。</p>';
            }
            
            // 檢查是否為職員文章類型
            if (get_post_type($staff_id) !== $this->post_type) {
                return '<p class="staff-error">指定的 ID 不是有效的職員資料。</p>';
            }
            
            // 獲取職員資料
            $position = get_post_meta($staff_id, $this->meta_prefix . 'position', true);
            $responsibilities = get_post_meta($staff_id, $this->meta_prefix . 'responsibilities', true);
            $email = get_post_meta($staff_id, $this->meta_prefix . 'email', true);
            $phone = get_post_meta($staff_id, $this->meta_prefix . 'phone', true);
            $staff_type = get_post_meta($staff_id, $this->meta_prefix . 'type', true);
            
            // 開始輸出緩衝
            ob_start();
            
            // 添加模態窗口
            echo '<div id="staff-modal" class="staff-modal">';
            echo '<div class="staff-modal-wrapper">';
            echo '<div class="staff-modal-header">';
            echo '<h3 class="staff-modal-title">工作職掌</h3>';
            echo '<span class="staff-modal-close">&times;</span>';
            echo '</div>'; // .staff-modal-header
            echo '<div class="staff-modal-body"></div>'; // 將在JavaScript中填充內容
            echo '</div>'; // .staff-modal-wrapper
            echo '</div>'; // .staff-modal
            
            echo '<div class="staff-info">';
            
            // 顯示職員標題
            echo '<h2 class="staff-title">' . get_the_title($staff_id) . '</h2>';
            
            // 顯示職員類型標籤
            if ($show_type && !empty($staff_type)) {
                echo '<div class="staff-type">';
                echo '<span class="staff-type-label">' . esc_html($staff_type) . '</span>';
                echo '</div>'; // .staff-type
            }
            
            echo '<div class="staff-info-container">';
            
            // 顯示職員照片
            if ($show_photo) {
                echo '<div class="staff-photo">';
                if (has_post_thumbnail($staff_id)) {
                    echo get_the_post_thumbnail($staff_id, 'large');
                } else {
                    echo '<img src="' . HKF_PLUGIN_URL . 'assets/images/default-staff.png" alt="職員預設圖片">';
                }
                echo '</div>'; // .staff-photo
            }
            
            echo '<div class="staff-details">';
            
            // 顯示職員資料
            if (!empty($position)) {
                echo '<div class="staff-position"><b>職稱：</b> ' . esc_html($position) . '</div>';
            }
            
            // 顯示工作職掌
            if (!empty($responsibilities)) {
                echo '<div class="staff-responsibilities">';
                echo '<button class="staff-resp-btn" data-staff-id="' . esc_attr($staff_id) . '">查看工作職掌</button>';
                echo '<div class="staff-modal-content" id="staff-modal-' . esc_attr($staff_id) . '" style="display:none;">';
                echo '<div class="staff-resp-content">' . nl2br(esc_html($responsibilities)) . '</div>';
                echo '</div>'; // .staff-modal-content
                echo '</div>'; // .staff-responsibilities
            }
            
            // 顯示聯絡資訊
            echo '<div class="staff-contact">';
            echo '<h3>聯絡資訊</h3>';
            
            if (!empty($email)) {
                echo '<div class="staff-email"><b>電子郵件：</b> <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>';
            }
            
            if (!empty($phone)) {
                echo '<div class="staff-phone"><b>電話/分機：</b> ' . esc_html($phone) . '</div>';
            }
            
            echo '</div>'; // .staff-contact
            
            echo '</div>'; // .staff-details
            echo '</div>'; // .staff-info-container
            
            // 顯示內容
            $content = get_post_field('post_content', $staff_id);
            if (!empty($content)) {
                echo '<div class="staff-content-area">';
                echo '<h3 class="staff-content-title">簡介</h3>';
                echo '<div class="staff-content-body">' . apply_filters('the_content', $content) . '</div>';
                echo '</div>'; // .staff-content-area
            }
            
            echo '</div>'; // .staff-info
            
            // 獲取輸出緩衝內容
            return ob_get_clean();
        }

        /**
         * 設置自定義列表欄位
         */
        public function set_custom_columns($columns) {
            $new_columns = [];
            
            // 保留複選框
            if (isset($columns['cb'])) {
                $new_columns['cb'] = $columns['cb'];
            }
            
            // 添加自定義欄位
            $new_columns['title'] = '姓名';
            $new_columns['thumbnail'] = '照片';
            $new_columns['position'] = '職稱';
            $new_columns['responsibilities'] = '工作職掌';
            $new_columns['email'] = '電子郵件';
            $new_columns['phone'] = '電話/分機';
            $new_columns['date'] = $columns['date'];
            
            return $new_columns;
        }
        
        /**
         * 填充自定義列表欄位內容
         */
        public function custom_column_content($column, $post_id) {
            switch ($column) {
                case 'thumbnail':
                    if (has_post_thumbnail($post_id)) {
                        echo get_the_post_thumbnail($post_id, [50, 50]);
                    } else {
                        echo '無照片';
                    }
                    break;
                    
                case 'position':
                    echo get_post_meta($post_id, $this->meta_prefix . 'position', true);
                    break;
                    
                case 'responsibilities':
                    $responsibilities = get_post_meta($post_id, $this->meta_prefix . 'responsibilities', true);
                    if (!empty($responsibilities)) {
                        // 顯示帶有彈出提示的截斷文本
                        echo '<span class="responsibilities-tooltip" data-tooltip="' . esc_attr($responsibilities) . '">';
                        echo esc_html(mb_substr($responsibilities, 0, 50)) . (mb_strlen($responsibilities) > 50 ? '...' : '');
                        echo '</span>';
                    } else {
                        echo '—';
                    }
                    break;
                    
                case 'email':
                    $email = get_post_meta($post_id, $this->meta_prefix . 'email', true);
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                    break;
                    
                case 'phone':
                    echo get_post_meta($post_id, $this->meta_prefix . 'phone', true);
                    break;
            }
        }
        
        /**
         * 設置可排序的欄位
         */
        public function set_sortable_columns($columns) {
            $columns['position'] = 'position';
            $columns['responsibilities'] = 'responsibilities';
            return $columns;
        }
        
        /**
         * 自定義排序邏輯
         */
        public function custom_orderby($query) {
            if (!is_admin() || !$query->is_main_query()) {
                return;
            }
            
            if ($query->get('post_type') === $this->post_type) {
                $orderby = $query->get('orderby');
                
                if ($orderby === 'position') {
                    $query->set('meta_key', $this->meta_prefix . 'position');
                    $query->set('orderby', 'meta_value');
                } elseif ($orderby === 'responsibilities') {
                    $query->set('meta_key', $this->meta_prefix . 'responsibilities');
                    $query->set('orderby', 'meta_value');
                }
            }
        }
        
        /**
         * 添加複製功能到行動列表
         */
        public function add_duplicate_action($actions, $post) {
            if ($post->post_type === $this->post_type) {
                $actions['duplicate'] = '<a href="' . wp_nonce_url(admin_url('admin.php?action=duplicate_staff&post=' . $post->ID), 'duplicate_staff_' . $post->ID) . '">複製</a>';
            }
            return $actions;
        }
        
        /**
         * 複製職員資料
         */
        public function duplicate_staff() {
            // 檢查權限和nonce
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
            
            if (!$post_id || !current_user_can('edit_posts') || !wp_verify_nonce($_GET['_wpnonce'], 'duplicate_staff_' . $post_id)) {
                wp_die('您沒有權限複製此職員資料。');
            }
            
            // 獲取原始職員資料
            $post = get_post($post_id);
            
            if (!$post || $post->post_type !== $this->post_type) {
                wp_die('無效的職員ID。');
            }
            
            // 創建新職員
            $new_post_args = [
                'post_title' => $post->post_title . ' (複製)',
                'post_content' => $post->post_content,
                'post_status' => 'draft',
                'post_type' => $this->post_type,
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
            ];
            
            $new_post_id = wp_insert_post($new_post_args);
            
            if (is_wp_error($new_post_id)) {
                wp_die('複製職員時發生錯誤：' . $new_post_id->get_error_message());
            }
            
            // 複製元資料
            $meta_keys = [
                $this->meta_prefix . 'position',
                $this->meta_prefix . 'responsibilities',
                $this->meta_prefix . 'email',
                $this->meta_prefix . 'phone',
            ];
            
            foreach ($meta_keys as $key) {
                $value = get_post_meta($post_id, $key, true);
                if ($value) {
                    update_post_meta($new_post_id, $key, $value);
                }
            }
            
            // 複製特色圖片
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                set_post_thumbnail($new_post_id, $thumbnail_id);
            }
            
            // 重定向到編輯頁面
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit;
        }
    }
} 