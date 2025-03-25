<?php
/**
 * 教師文章類型類
 * 
 * 處理教師資料的註冊、管理和顯示
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

if (!class_exists('HKFaculty\TeacherPostType')) {
    class TeacherPostType {
        private $post_type = 'teacher';
        private $meta_prefix = 'hkf_teacher_';

        public function __construct() {
            // 註冊文章類型
            // 移除 init 鉤子，因為已經在主插件文件中的 hkt_init 函數中調用了
            $this->register_post_type();
            
            // 添加後台欄位
            add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
            
            // 儲存後台欄位資料
            add_action('save_post_' . $this->post_type, [$this, 'save_meta_box_data']);
            
            // 註冊 shortcode
            add_shortcode('teacher_list', [$this, 'teacher_list_shortcode']);
            add_shortcode('teacher_info', [$this, 'teacher_info_shortcode']);
            
            // 自定義後台列表欄位
            add_filter('manage_' . $this->post_type . '_posts_columns', [$this, 'set_custom_columns']);
            add_action('manage_' . $this->post_type . '_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
            add_filter('manage_edit-' . $this->post_type . '_sortable_columns', [$this, 'set_sortable_columns']);
            add_action('pre_get_posts', [$this, 'custom_orderby']);
            
            // 添加複製功能
            add_filter('post_row_actions', [$this, 'add_duplicate_action'], 10, 2);
            add_action('admin_action_duplicate_teacher', [$this, 'duplicate_teacher']);
        }

        public function register_post_type() {
            $labels = [
                'name'               => '教師',
                'singular_name'      => '教師',
                'menu_name'          => '教師管理',
                'name_admin_bar'     => '教師',
                'add_new'            => '新增教師',
                'add_new_item'       => '新增教師',
                'edit_item'          => '編輯教師資料',
                'new_item'           => '新教師',
                'view_item'          => '查看教師資料',
                'search_items'       => '搜尋教師',
                'not_found'          => '找不到教師資料',
                'not_found_in_trash' => '回收桶中沒有教師資料',
                'all_items'          => '全部教師',
            ];

            $args = [
                'labels'              => $labels,
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'            => true,
                'show_in_menu'       => false,
                'query_var'          => true,
                'rewrite'            => ['slug' => 'teacher'],
                'capability_type'     => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => 5,
                'supports'           => ['title', 'editor', 'thumbnail'],
                'show_in_rest'       => true,
            ];

            register_post_type($this->post_type, $args);
        }

        public function add_meta_boxes() {
            add_meta_box(
                'teacher_details',
                '教師詳細資料',
                [$this, 'render_meta_box'],
                $this->post_type,
                'normal',
                'high'
            );
        }

        public function render_meta_box($post) {
            // 添加 nonce 欄位進行安全檢查
            wp_nonce_field('teacher_meta_box', 'teacher_meta_box_nonce');

            // 獲取已保存的值
            $education = get_post_meta($post->ID, $this->meta_prefix . 'education', true);
            $position = get_post_meta($post->ID, $this->meta_prefix . 'position', true);
            $experience = get_post_meta($post->ID, $this->meta_prefix . 'experience', true);
            $expertise = get_post_meta($post->ID, $this->meta_prefix . 'expertise', true);
            $email = get_post_meta($post->ID, $this->meta_prefix . 'email', true);
            $extension = get_post_meta($post->ID, $this->meta_prefix . 'extension', true);
            $office = get_post_meta($post->ID, $this->meta_prefix . 'office', true);
            $employment_type = get_post_meta($post->ID, $this->meta_prefix . 'employment_type', true);
            if (empty($employment_type)) {
                $employment_type = 'full_time'; // 默認為專任
            }

            // 輸出表單欄位
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>employment_type">教師類型</label></th>
                    <td>
                        <select id="<?php echo $this->meta_prefix; ?>employment_type" 
                                name="<?php echo $this->meta_prefix; ?>employment_type" required>
                            <option value="full_time" <?php selected($employment_type, 'full_time'); ?>>專任教師</option>
                            <option value="part_time" <?php selected($employment_type, 'part_time'); ?>>兼任教師</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>education">學歷</label></th>
                    <td>
                        <input type="text" id="<?php echo $this->meta_prefix; ?>education" 
                               name="<?php echo $this->meta_prefix; ?>education" 
                               value="<?php echo esc_attr($education); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>position">現職</label></th>
                    <td>
                        <input type="text" id="<?php echo $this->meta_prefix; ?>position" 
                               name="<?php echo $this->meta_prefix; ?>position" 
                               value="<?php echo esc_attr($position); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>experience">其他(工作、著作等)</label></th>
                    <td>
                        <textarea id="<?php echo $this->meta_prefix; ?>experience" 
                                name="<?php echo $this->meta_prefix; ?>experience" 
                                class="large-text" rows="5" required><?php echo esc_textarea($experience); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>expertise">專長</label></th>
                    <td>
                        <textarea id="<?php echo $this->meta_prefix; ?>expertise" 
                                name="<?php echo $this->meta_prefix; ?>expertise" 
                                class="large-text" rows="5" required><?php echo esc_textarea($expertise); ?></textarea>
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
                    <th><label for="<?php echo $this->meta_prefix; ?>extension">分機</label></th>
                    <td>
                        <input type="text" id="<?php echo $this->meta_prefix; ?>extension" 
                               name="<?php echo $this->meta_prefix; ?>extension" 
                               value="<?php echo esc_attr($extension); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="<?php echo $this->meta_prefix; ?>office">辦公室</label></th>
                    <td>
                        <input type="text" id="<?php echo $this->meta_prefix; ?>office" 
                               name="<?php echo $this->meta_prefix; ?>office" 
                               value="<?php echo esc_attr($office); ?>" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <?php
        }

        public function save_meta_box_data($post_id) {
            // 檢查 nonce
            if (!isset($_POST['teacher_meta_box_nonce']) || 
                !wp_verify_nonce($_POST['teacher_meta_box_nonce'], 'teacher_meta_box')) {
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
                'education',
                'position',
                'email',
                'extension',
                'office',
                'employment_type'
            ];
            
            $textarea_fields = [
                'experience',
                'expertise'
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
         * 顯示教師列表的 shortcode
         * 用法: [teacher_list limit="10" orderby="title" order="ASC" type="all" show_type="true" columns="1"]
         * type 參數可選值: all (所有教師), full_time (專任教師), part_time (兼任教師)
         * show_type 參數控制是否顯示教師類型標籤
         * columns 參數控制每列顯示的教師數量
         */
        public function teacher_list_shortcode($atts) {
            // 設置默認參數
            $atts = shortcode_atts([
                'limit' => 10,
                'orderby' => 'title',
                'order' => 'ASC',
                'type' => 'all', // 默認顯示所有教師
                'show_type' => 'true', // 默認顯示教師類型
                'columns' => 1 // 默認每列顯示1個教師
            ], $atts, 'teacher_list');
            
            // 查詢教師
            $args = [
                'post_type' => $this->post_type,
                'posts_per_page' => intval($atts['limit']),
                'orderby' => $atts['orderby'],
                'order' => $atts['order']
            ];
            
            // 根據教師類型篩選
            if ($atts['type'] !== 'all') {
                $args['meta_query'] = [
                    [
                        'key' => $this->meta_prefix . 'employment_type',
                        'value' => $atts['type'],
                        'compare' => '='
                    ]
                ];
            }
            
            $teachers = new \WP_Query($args);
            
            // 是否顯示教師類型
            $show_type = filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN);
            
            // 每列顯示的教師數量
            $columns = intval($atts['columns']);
            if ($columns < 1) {
                $columns = 1;
            } elseif ($columns > 6) {
                $columns = 6; // 限制最大列數為6
            }
            
            // 開始輸出緩衝
            ob_start();
            
            if ($teachers->have_posts()) {
                echo '<div class="teacher-list teacher-columns-' . esc_attr($columns) . '">';
                
                while ($teachers->have_posts()) {
                    $teachers->the_post();
                    
                    // 獲取教師資料
                    $id = get_the_ID();
                    $title = get_the_title();
                    $permalink = get_permalink();
                    $thumbnail = get_the_post_thumbnail($id, 'medium');
                    
                    // 獲取自定義欄位資料
                    $employment_type = get_post_meta($id, $this->meta_prefix . 'employment_type', true);
                    $position = get_post_meta($id, $this->meta_prefix . 'position', true);
                    $education = get_post_meta($id, $this->meta_prefix . 'education', true);
                    $expertise = get_post_meta($id, $this->meta_prefix . 'expertise', true);
                    $email = get_post_meta($id, $this->meta_prefix . 'email', true);
                    $extension = get_post_meta($id, $this->meta_prefix . 'extension', true);
                    $office = get_post_meta($id, $this->meta_prefix . 'office', true);
                    $experience = get_post_meta($id, $this->meta_prefix . 'experience', true);
                    
                    // 教師類型顯示文字
                    $type_text = '';
                    if ($employment_type === 'full_time') {
                        $type_text = '專任';
                    } elseif ($employment_type === 'part_time') {
                        $type_text = '兼任';
                    }
                    
                    echo '<div class="teacher-item">';
                    echo '<div class="teacher-card-inner">';
                    
                    // 顯示縮略圖
                    echo '<div class="teacher-thumbnail">';
                    if ($thumbnail) {
                        echo $thumbnail;
                    } else {
                        echo '<img src="' . HKF_PLUGIN_URL . 'assets/images/default-teacher.png" alt="教師預設圖片">';
                    }
                    echo '</div>'; // .teacher-thumbnail
                    
                    echo '<div class="teacher-content">';
                    echo '<h3 class="teacher-name"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';
                    
                    // 顯示教師類型標籤
                    if ($show_type && !empty($employment_type)) {
                        echo '<div class="teacher-type teacher-type-' . esc_attr($employment_type) . '">';
                        echo '<span class="teacher-type-label">' . esc_html($type_text) . '</span>';
                        echo '</div>'; // .teacher-type
                    }
                    
                    echo '<div class="teacher-details-list">';
                    
                    // 顯示教師資料
                    if (!empty($position)) {
                        echo '<div class="teacher-position"><b>現職：</b>' . esc_html($position) . '</div>';
                    }
                    
                    if (!empty($education)) {
                        echo '<div class="teacher-education"><b>學歷：</b>' . esc_html($education) . '</div>';
                    }
                    
                    if (!empty($expertise)) {
                        echo '<div class="teacher-expertise"><b>專長：</b>' . esc_html($expertise) . '</div>';
                    }
                    
                    if (!empty($experience)) {
                        echo '<div class="teacher-experience"><b>其他：</b>' . nl2br(esc_html($experience)) . '</div>';
                    }
                    
                    // 顯示聯絡資訊
                    echo '<div class="teacher-contact-info">';
                    
                    if (!empty($email)) {
                        echo '<div class="teacher-email"><b>電子郵件：</b> <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>';
                    }
                    
                    if (!empty($extension)) {
                        echo '<div class="teacher-extension"><b>分機：</b>' . esc_html($extension) . '</div>';
                    }
                    
                    if (!empty($office)) {
                        echo '<div class="teacher-office"><b>辦公室：</b>' . esc_html($office) . '</div>';
                    }
                    
                    echo '</div>'; // .teacher-contact-info
                    
                    echo '</div>'; // .teacher-details-list
                    echo '</div>'; // .teacher-content
                    echo '</div>'; // .teacher-card-inner
                    echo '</div>'; // .teacher-item
                }
                
                echo '</div>'; // .teacher-list
                
                // 重置查詢
                wp_reset_postdata();
            } else {
                echo '<div class="teacher-empty"><p class="teacher-empty-text">目前沒有教師資料。</p></div>';
            }
            
            // 獲取輸出緩衝內容
            return ob_get_clean();
        }
        
        /**
         * 顯示單個教師資訊的 shortcode
         * 用法: [teacher_info id="123" show_photo="true" show_type="true"]
         */
        public function teacher_info_shortcode($atts) {
            // 設置默認參數
            $atts = shortcode_atts([
                'id' => 0,
                'show_photo' => 'true',
                'show_type' => 'true' // 默認顯示教師類型
            ], $atts, 'teacher_info');
            
            $teacher_id = intval($atts['id']);
            $show_photo = filter_var($atts['show_photo'], FILTER_VALIDATE_BOOLEAN);
            $show_type = filter_var($atts['show_type'], FILTER_VALIDATE_BOOLEAN);
            
            // 如果沒有指定 ID，返回空
            if ($teacher_id <= 0) {
                return '<p class="teacher-error">請指定教師 ID。</p>';
            }
            
            // 檢查是否為教師文章類型
            if (get_post_type($teacher_id) !== $this->post_type) {
                return '<p class="teacher-error">指定的 ID 不是有效的教師資料。</p>';
            }
            
            // 獲取教師資料
            $education = get_post_meta($teacher_id, $this->meta_prefix . 'education', true);
            $position = get_post_meta($teacher_id, $this->meta_prefix . 'position', true);
            $experience = get_post_meta($teacher_id, $this->meta_prefix . 'experience', true);
            $expertise = get_post_meta($teacher_id, $this->meta_prefix . 'expertise', true);
            $email = get_post_meta($teacher_id, $this->meta_prefix . 'email', true);
            $extension = get_post_meta($teacher_id, $this->meta_prefix . 'extension', true);
            $office = get_post_meta($teacher_id, $this->meta_prefix . 'office', true);
            $employment_type = get_post_meta($teacher_id, $this->meta_prefix . 'employment_type', true);
            
            // 教師類型顯示文字
            $type_text = '';
            if ($employment_type === 'full_time') {
                $type_text = '專任';
            } elseif ($employment_type === 'part_time') {
                $type_text = '兼任';
            }
            
            // 開始輸出緩衝
            ob_start();
            
            echo '<div class="teacher-info">';
            
            // 顯示教師標題
            echo '<h2 class="teacher-title">' . get_the_title($teacher_id) . '</h2>';
            
            // 顯示教師類型標籤
            if ($show_type && !empty($employment_type)) {
                echo '<div class="teacher-type teacher-type-' . esc_attr($employment_type) . '">';
                echo '<span class="teacher-type-label">' . esc_html($type_text) . '</span>';
                echo '</div>'; // .teacher-type
            }
            
            echo '<div class="teacher-info-container">';
            
            // 顯示教師照片
            if ($show_photo) {
                echo '<div class="teacher-photo">';
                if (has_post_thumbnail($teacher_id)) {
                    echo get_the_post_thumbnail($teacher_id, 'large');
                } else {
                    echo '<img src="' . HKF_PLUGIN_URL . 'assets/images/default-teacher.png" alt="教師預設圖片">';
                }
                echo '</div>'; // .teacher-photo
            }
            
            echo '<div class="teacher-details">';
            
            // 顯示教師資料
            if (!empty($position)) {
                echo '<div class="teacher-position"><b>現職：</b>' . esc_html($position) . '</div>';
            }
            
            if (!empty($education)) {
                echo '<div class="teacher-education"><b>學歷：</b> ' . esc_html($education) . '</div>';
            }
            
            if (!empty($experience)) {
                echo '<div class="teacher-experience"><b>經歷：</b> ' . nl2br(esc_html($experience)) . '</div>';
            }
            
            if (!empty($expertise)) {
                echo '<div class="teacher-expertise"><b>專長：</b> ' . esc_html($expertise) . '</div>';
            }
            
            // 顯示聯絡資訊
            echo '<div class="teacher-contact">';
            echo '<h3>聯絡資訊</h3>';
            
            if (!empty($email)) {
                echo '<div class="teacher-email"><b>電子郵件：</b> <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>';
            }
            
            if (!empty($extension)) {
                echo '<div class="teacher-extension"><b>分機：</b> ' . esc_html($extension) . '</div>';
            }
            
            if (!empty($office)) {
                echo '<div class="teacher-office"><b>辦公室：</b> ' . esc_html($office) . '</div>';
            }
            
            echo '</div>'; // .teacher-contact
            
            echo '</div>'; // .teacher-details
            echo '</div>'; // .teacher-info-container
            
            // 顯示內容
            echo '<div class="teacher-content-area">';
            echo '<h3 class="teacher-content-title">簡介</h3>';
            echo '<div class="teacher-content-body">' . apply_filters('the_content', get_post_field('post_content', $teacher_id)) . '</div>';
            echo '</div>'; // .teacher-content-area
            
            echo '</div>'; // .teacher-info
            
            // 獲取輸出緩衝內容
            return ob_get_clean();
        }
        
        /**
         * 設置自定義後台列表欄位
         */
        public function set_custom_columns($columns) {
            $new_columns = [
                'cb' => $columns['cb'], // 保留複選框
                'title' => '教師姓名',
                'thumbnail' => '照片',
                'employment_type' => '教師類型',
                'position' => '現職',
                'education' => '學歷',
                'expertise' => '專長',
                'email' => '電子郵件',
                'extension' => '分機',
                'office' => '辦公室',
                'date' => $columns['date'] // 保留日期欄位
            ];
            
            return $new_columns;
        }
        
        /**
         * 填充自定義欄位內容
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
                    
                case 'employment_type':
                    $employment_type = get_post_meta($post_id, $this->meta_prefix . 'employment_type', true);
                    if ($employment_type === 'full_time') {
                        echo '<span style="display: inline-block; padding: 3px 8px; background-color: #e6f3fa; color: #0073aa; border: 1px solid #b5d8e6; border-radius: 3px; font-weight: 500; text-align: center; min-width: 80px;">專任教師</span>';
                    } elseif ($employment_type === 'part_time') {
                        echo '<span style="display: inline-block; padding: 3px 8px; background-color: #fae6e6; color: #d63638; border: 1px solid #e6b5b5; border-radius: 3px; font-weight: 500; text-align: center; min-width: 80px;">兼任教師</span>';
                    } else {
                        echo '—';
                    }
                    break;
                    
                case 'position':
                    $position = get_post_meta($post_id, $this->meta_prefix . 'position', true);
                    echo !empty($position) ? esc_html($position) : '—';
                    break;
                    
                case 'education':
                    $education = get_post_meta($post_id, $this->meta_prefix . 'education', true);
                    echo !empty($education) ? esc_html($education) : '—';
                    break;
                    
                case 'expertise':
                    $expertise = get_post_meta($post_id, $this->meta_prefix . 'expertise', true);
                    if (!empty($expertise)) {
                        // 顯示帶有彈出提示的截斷文本
                        echo '<span class="expertise-tooltip" data-tooltip="' . esc_attr($expertise) . '">';
                        echo esc_html(mb_substr($expertise, 0, 50)) . (mb_strlen($expertise) > 50 ? '...' : '');
                        echo '</span>';
                    } else {
                        echo '—';
                    }
                    break;
                    
                case 'email':
                    $email = get_post_meta($post_id, $this->meta_prefix . 'email', true);
                    if (!empty($email)) {
                        echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                    } else {
                        echo '—';
                    }
                    break;
                    
                case 'extension':
                    $extension = get_post_meta($post_id, $this->meta_prefix . 'extension', true);
                    echo !empty($extension) ? esc_html($extension) : '—';
                    break;
                    
                case 'office':
                    $office = get_post_meta($post_id, $this->meta_prefix . 'office', true);
                    echo !empty($office) ? esc_html($office) : '—';
                    break;
            }
        }
        
        /**
         * 設置可排序的欄位
         */
        public function set_sortable_columns($columns) {
            $columns['employment_type'] = 'employment_type';
            $columns['position'] = 'position';
            $columns['education'] = 'education';
            $columns['extension'] = 'extension';
            $columns['office'] = 'office';
            
            return $columns;
        }
        
        /**
         * 自定義排序邏輯
         */
        public function custom_orderby($query) {
            if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== $this->post_type) {
                return;
            }
            
            $orderby = $query->get('orderby');
            
            switch ($orderby) {
                case 'employment_type':
                    $query->set('meta_key', $this->meta_prefix . 'employment_type');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'position':
                    $query->set('meta_key', $this->meta_prefix . 'position');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'education':
                    $query->set('meta_key', $this->meta_prefix . 'education');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'extension':
                    $query->set('meta_key', $this->meta_prefix . 'extension');
                    $query->set('orderby', 'meta_value');
                    break;
                    
                case 'office':
                    $query->set('meta_key', $this->meta_prefix . 'office');
                    $query->set('orderby', 'meta_value');
                    break;
            }
        }
        
        /**
         * 添加複製操作到行動列表
         */
        public function add_duplicate_action($actions, $post) {
            // 只為教師文章類型添加複製操作
            if ($post->post_type === $this->post_type) {
                // 添加複製連結
                $duplicate_url = wp_nonce_url(
                    admin_url('admin.php?action=duplicate_teacher&post=' . $post->ID),
                    'duplicate_teacher_' . $post->ID
                );
                
                $actions['duplicate'] = '<a href="' . esc_url($duplicate_url) . '" title="複製此教師資料" rel="permalink">複製</a>';
            }
            
            return $actions;
        }
        
        /**
         * 複製教師資料
         */
        public function duplicate_teacher() {
            // 檢查權限
            if (!current_user_can('edit_posts')) {
                wp_die('您沒有權限複製教師資料。');
            }
            
            // 檢查 nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'duplicate_teacher_' . $_GET['post'])) {
                wp_die('安全檢查失敗。');
            }
            
            // 獲取原始教師 ID
            $original_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
            
            if ($original_id <= 0) {
                wp_die('無效的教師 ID。');
            }
            
            // 獲取原始教師資料
            $original_post = get_post($original_id);
            
            if (!$original_post || $original_post->post_type !== $this->post_type) {
                wp_die('找不到指定的教師資料。');
            }
            
            // 創建新教師資料
            $new_post_args = [
                'post_title'     => $original_post->post_title . ' (複製)',
                'post_content'   => $original_post->post_content,
                'post_excerpt'   => $original_post->post_excerpt,
                'post_status'    => 'draft', // 設為草稿，以便編輯後再發布
                'post_type'      => $this->post_type,
                'comment_status' => $original_post->comment_status,
                'ping_status'    => $original_post->ping_status,
                'post_author'    => get_current_user_id(),
            ];
            
            // 插入新教師資料
            $new_post_id = wp_insert_post($new_post_args);
            
            if (is_wp_error($new_post_id)) {
                wp_die('複製教師資料時發生錯誤：' . $new_post_id->get_error_message());
            }
            
            // 複製元資料
            $meta_fields = [
                'education',
                'position',
                'experience',
                'expertise',
                'email',
                'extension',
                'office',
                'employment_type'
            ];
            
            foreach ($meta_fields as $field) {
                $meta_key = $this->meta_prefix . $field;
                $meta_value = get_post_meta($original_id, $meta_key, true);
                update_post_meta($new_post_id, $meta_key, $meta_value);
            }
            
            // 複製特色圖片
            $thumbnail_id = get_post_thumbnail_id($original_id);
            if ($thumbnail_id) {
                set_post_thumbnail($new_post_id, $thumbnail_id);
            }
            
            // 複製分類法術語
            $taxonomies = get_object_taxonomies($this->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_object_terms($original_id, $taxonomy, ['fields' => 'slugs']);
                wp_set_object_terms($new_post_id, $terms, $taxonomy);
            }
            
            // 添加成功訊息
            $duplicate_notice = [
                'type'    => 'success',
                'message' => '教師資料已成功複製。您可以編輯新的教師資料。'
            ];
            set_transient('teacher_duplicate_notice', $duplicate_notice, 60);
            
            // 重定向到編輯頁面
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit;
        }
    }
} 