# WordPress 外掛開發教學

本教學以教職員管理系統為實例，說明如何開發一個完整的 WordPress 外掛。

## 目錄

1. [基礎設置](#基礎設置)
2. [自定義文章類型](#自定義文章類型)
3. [後台管理介面](#後台管理介面)
4. [前台顯示](#前台顯示)
5. [CSS 樣式設計](#css-樣式設計)
6. [Shortcode 樣式客製化](#shortcode-樣式客製化)
7. [最佳實踐](#最佳實踐)

## 基礎設置

### 外掛結構
```
hk-faculty/
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-activator.php
│   ├── class-admin.php
│   ├── class-teacher-post-type.php
│   └── class-staff-post-type.php
├── templates/
│   ├── teacher-list.php
│   └── staff-list.php
├── hk-faculty.php
├── uninstall.php
└── README.md
```

### 主要檔案功能
- `hk-faculty.php`: 外掛主文件
- `class-activator.php`: 處理外掛啟動時的操作
- `class-admin.php`: 管理後台介面
- `class-teacher-post-type.php`: 教師文章類型
- `class-staff-post-type.php`: 職員文章類型

## 自定義文章類型

### 註冊教師文章類型
```php
register_post_type('teacher', [
    'labels' => [
        'name' => '教師',
        'singular_name' => '教師',
        'menu_name' => '教師管理'
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_icon' => 'dashicons-welcome-learn-more'
]);
```

### 註冊職員文章類型
```php
register_post_type('staff', [
    'labels' => [
        'name' => '職員',
        'singular_name' => '職員',
        'menu_name' => '職員管理'
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_icon' => 'dashicons-businessperson'
]);
```

## 後台管理介面

### 整合選單結構
```php
add_action('admin_menu', function() {
    // 主選單：教職員管理
    add_menu_page(
        '教職員管理',
        '教職員管理',
        'manage_options',
        'hk-faculty',
        'render_dashboard',
        'dashicons-groups',
        5
    );
    
    // 子選單
    add_submenu_page(
        'hk-faculty',
        '全部教師',
        '全部教師',
        'manage_options',
        'edit.php?post_type=teacher'
    );
    
    add_submenu_page(
        'hk-faculty',
        '全部職員',
        '全部職員',
        'manage_options',
        'edit.php?post_type=staff'
    );
});
```

### 自定義欄位
```php
// 教師欄位
$teacher_fields = [
    'employment_type' => '教師類型',
    'education' => '學歷',
    'position' => '現任職位',
    'expertise' => '專長',
    'email' => '電子郵件',
    'extension' => '分機',
    'office' => '辦公室'
];

// 職員欄位
$staff_fields = [
    'position' => '職稱',
    'responsibilities' => '工作職掌',
    'email' => '電子郵件',
    'phone' => '電話/分機'
];
```

## 前台顯示

### Shortcode 使用
```php
// 顯示教師列表
[teacher_list limit="10" orderby="title" order="ASC" columns="3"]

// 顯示職員列表
[staff_list limit="10" orderby="title" order="ASC" columns="3"]
```

### 響應式設計
```css
/* 響應式布局 */
@media (max-width: 1200px) {
    .staff-columns-4 .staff-item,
    .staff-columns-5 .staff-item,
    .staff-columns-6 .staff-item {
        width: 33.333%;
    }
}

@media (max-width: 992px) {
    .staff-columns-3 .staff-item,
    .staff-columns-4 .staff-item,
    .staff-columns-5 .staff-item,
    .staff-columns-6 .staff-item {
        width: 50%;
    }
}

@media (max-width: 768px) {
    .staff-item {
        width: 100% !important;
    }
}
```

## CSS 樣式設計

### 卡片設計
```css
.staff-card-inner {
    display: flex;
    flex-direction: column;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
    height: 100%;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.staff-thumbnail {
    overflow: hidden;
}

.staff-thumbnail img {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.3s ease;
}

.staff-item:hover .staff-thumbnail img {
    transform: scale(1.05);
}
```

### 互動效果
```css
/* 工作職掌展開效果 */
.responsibilities-content {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    position: relative;
    cursor: pointer;
}

.responsibilities-content::after {
    content: '點我展開';
    display: block;
    color: #0073aa;
    font-size: 0.9em;
    margin-top: 5px;
}

.staff-responsibilities:hover .responsibilities-content {
    -webkit-line-clamp: unset;
    max-height: none;
}
```

## Shortcode 樣式客製化

### 基本結構
教職員管理系統的 Shortcode 顯示包含以下主要元素：

```css
/* 列表容器 */
.teacher-list,
.staff-list {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

/* 個別項目容器 */
.teacher-item,
.staff-item {
    padding: 15px;
    margin-bottom: 30px;
    box-sizing: border-box;
}

/* 卡片內容容器 */
.teacher-card-inner,
.staff-card-inner {
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
    height: 100%;
}
```

### 照片樣式
```css
/* 照片容器 */
.teacher-thumbnail,
.staff-thumbnail {
    position: relative;
    overflow: hidden;
    padding-top: 75%; /* 4:3 比例 */
}

/* 照片 */
.teacher-thumbnail img,
.staff-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

/* 滑鼠懸停效果 */
.teacher-item:hover .teacher-thumbnail img,
.staff-item:hover .staff-thumbnail img {
    transform: scale(1.1);
}
```

### 內容樣式
```css
/* 內容區塊 */
.teacher-content,
.staff-content {
    padding: 20px;
}

/* 姓名 */
.teacher-name,
.staff-name {
    font-size: 1.25em;
    font-weight: bold;
    margin: 0 0 10px;
}

/* 類型標籤 */
.teacher-type-label,
.staff-type-label {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.85em;
    margin-bottom: 15px;
}

/* 詳細資訊列表 */
.teacher-details-list,
.staff-details-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.teacher-details-list > div,
.staff-details-list > div {
    margin-bottom: 8px;
    line-height: 1.6;
}
```

### 展開效果
```css
/* 可展開內容 */
.expandable-content {
    position: relative;
    cursor: pointer;
}

/* 預設狀態 */
.expandable-content.collapsed {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* 展開提示 */
.expandable-content.collapsed::after {
    content: '點我展開';
    display: block;
    color: #0073aa;
    font-size: 0.9em;
    margin-top: 5px;
}

/* 展開狀態 */
.expandable-content.expanded {
    display: block;
}
```

### 響應式設計
```css
/* 多欄配置 */
.teacher-columns-2 .teacher-item,
.staff-columns-2 .staff-item {
    width: 50%;
}

.teacher-columns-3 .teacher-item,
.staff-columns-3 .staff-item {
    width: 33.333%;
}

.teacher-columns-4 .teacher-item,
.staff-columns-4 .staff-item {
    width: 25%;
}

/* 響應式調整 */
@media (max-width: 1200px) {
    .teacher-columns-4 .teacher-item,
    .staff-columns-4 .staff-item {
        width: 33.333%;
    }
}

@media (max-width: 992px) {
    .teacher-columns-3 .teacher-item,
    .staff-columns-3 .staff-item,
    .teacher-columns-4 .teacher-item,
    .staff-columns-4 .staff-item {
        width: 50%;
    }
}

@media (max-width: 768px) {
    .teacher-item,
    .staff-item {
        width: 100% !important;
    }
}
```

### 客製化主題
```css
/* 淺色主題 */
.theme-light {
    --card-bg: #ffffff;
    --card-border: #e0e0e0;
    --text-primary: #333333;
    --text-secondary: #666666;
    --accent-color: #0073aa;
}

/* 深色主題 */
.theme-dark {
    --card-bg: #2c2c2c;
    --card-border: #404040;
    --text-primary: #ffffff;
    --text-secondary: #cccccc;
    --accent-color: #4cc2ff;
}

/* 應用主題變數 */
.teacher-card-inner,
.staff-card-inner {
    background-color: var(--card-bg);
    border-color: var(--card-border);
}

.teacher-name,
.staff-name {
    color: var(--text-primary);
}

.teacher-details-list,
.staff-details-list {
    color: var(--text-secondary);
}
```

### 動畫效果
```css
/* 卡片懸浮效果 */
.teacher-card-inner,
.staff-card-inner {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.teacher-item:hover .teacher-card-inner,
.staff-item:hover .staff-card-inner {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* 內容淡入效果 */
.teacher-content,
.staff-content {
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInUp 0.5s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### 使用方式
1. 將需要的樣式複製到您的主題的 `style.css` 檔案中
2. 或創建一個新的 CSS 檔案並在 `functions.php` 中引入：
```php
function enqueue_custom_styles() {
    wp_enqueue_style(
        'custom-faculty-style',
        get_template_directory_uri() . '/css/faculty-custom.css',
        [],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');
```

### 客製化建議
1. **配色方案**
   - 使用主題變數方便全局修改
   - 確保與網站整體風格協調
   - 注意文字與背景的對比度

2. **間距調整**
   - 根據內容多寡調整內邊距
   - 確保卡片之間有適當間距
   - 在不同螢幕尺寸下保持良好的視覺效果

3. **響應式設計**
   - 針對不同裝置優化顯示方式
   - 調整字體大小和間距
   - 確保在行動裝置上的可用性

4. **效能考量**
   - 最小化 CSS 檔案
   - 使用適當的選擇器
   - 避免過度使用動畫效果

## 最佳實踐

1. **模組化設計**
   - 使用類別封裝相關功能
   - 分離前後台程式碼
   - 使用命名空間避免衝突

2. **WordPress 鉤子與初始化順序**
   - 在不同鉤子上註冊功能時注意執行順序：
     - `plugins_loaded`: 外掛載入最早的階段，此時部分 WordPress 核心功能尚未準備好
     - `init`: 適合註冊自定義文章類型、分類法和初始化多數外掛功能
     - `admin_init`: 後台管理功能初始化
     - `wp_enqueue_scripts`: 前台樣式和腳本載入
   - 避免在同一外掛中在不同鉤子上重複調用相同的初始化函數
   - 註冊自定義文章類型時務必在 `init` 鉤子上執行，因為此時 WordPress 重寫規則已初始化
   - 在主外掛文件中的初始化示例：
   ```php
   /**
    * 初始化外掛
    */
   function hkf_init() {
       // 初始化自定義文章類型
       $teacher_post_type = new \HKFaculty\TeacherPostType();
       $staff_post_type = new \HKFaculty\StaffPostType();
       
       // 加載文本域
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
   }
   
   // 正確使用適當的鉤子
   add_action('init', 'hkf_init');
   add_action('wp_enqueue_scripts', 'hkf_enqueue_styles');
   ```

3. **安全性考量**
   - 使用 nonce 驗證
   - 資料消毒和驗證
   - 適當的權限檢查

4. **效能優化**
   - 最小化 CSS/JS 檔案
   - 適當的快取機制
   - 優化資料庫查詢

5. **使用者體驗**
   - 清晰的操作介面
   - 適當的提示訊息
   - 響應式設計支援

6. **程式碼品質**
   - 遵循 WordPress 編碼標準
   - 詳細的註解說明
   - 一致的命名規範

## 結論

本教學展示了如何開發一個完整的 WordPress 外掛，從基礎設置到前台顯示，涵蓋了開發過程中的各個面向。通過教職員管理系統這個實例，我們可以學習到：

1. 如何組織外掛結構
2. 如何建立自定義文章類型
3. 如何設計後台管理介面
4. 如何實現前台顯示功能
5. 如何優化使用者體驗

這些知識和技巧可以應用到其他 WordPress 外掛的開發中。
