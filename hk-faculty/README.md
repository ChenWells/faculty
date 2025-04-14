# 弘光科技大學教職員管理系統

這是一個專門為弘光科技大學開發的 WordPress 外掛，用於管理學校的教師和職員資訊。

## 配色方案

本專案採用以下配色方案：

<div style="display: flex; gap: 10px; margin: 20px 0;">
  <div style="width: 50px; height: 50px; background: #363b4e; border-radius: 5px;"></div>
  <div style="width: 50px; height: 50px; background: #4f3b78; border-radius: 5px;"></div>
  <div style="width: 50px; height: 50px; background: #927fbf; border-radius: 5px;"></div>
  <div style="width: 50px; height: 50px; background: #c4bbf0; border-radius: 5px;"></div>
</div>

- 主色調：`#363b4e`（深藍灰）
- 次要色調：`#4f3b78`（深紫色）
- 強調色：`#927fbf`（中紫色）
- 輔助色：`#c4bbf0`（淺紫色）

同時提供深色及淺色兩種顯示模式，可在設定頁面中進行切換：

<div style="display: flex; gap: 20px; margin: 20px 0;">
  <div style="border: 1px solid #ddd; border-radius: 5px; padding: 10px; width: 45%;">
    <h4 style="margin-top: 0;">淺色模式</h4>
    <div style="height: 100px; background: linear-gradient(to right, #ffffff, #f8f8fa); border-radius: 5px;"></div>
  </div>
  <div style="border: 1px solid #ddd; border-radius: 5px; padding: 10px; width: 45%;">
    <h4 style="margin-top: 0;">深色模式</h4>
    <div style="height: 100px; background: linear-gradient(to right, #262938, #121318); border-radius: 5px;"></div>
  </div>
</div>

## 功能特點

### 教師管理
- 教師基本資料管理
- 教師職稱管理
- 教師專長領域管理
- 教師聯絡資訊管理
- 教師列表顯示（支援多欄位顯示）

### 職員管理
- 職員基本資料管理
- 職員職稱管理
- 職員工作職掌管理
- 職員聯絡資訊管理
- 職員列表顯示（支援多欄位顯示）

### 顯示功能
- 響應式設計，支援各種螢幕尺寸
- 美觀的卡片式佈局
- 自定義排序功能
- 自定義顯示欄位數
- 支援短代碼嵌入
- 支援深色/淺色主題切換

## 安裝方法

1. 下載最新版本的 ZIP 檔案
2. 在 WordPress 後台進入「外掛」→「安裝外掛」
3. 點擊「上傳外掛」按鈕
4. 選擇下載的 ZIP 檔案並安裝
5. 安裝完成後啟用外掛

## 使用方法

### 教師管理
1. 在 WordPress 後台選單中點擊「教師管理」
2. 點擊「新增教師」添加教師資料
3. 填寫教師相關資訊
4. 發布即可在網站上顯示

### 職員管理
1. 在 WordPress 後台選單中點擊「職員管理」
2. 點擊「新增職員」添加職員資料
3. 填寫職員相關資訊
4. 發布即可在網站上顯示

### 主題設置
1. 在 WordPress 後台選單中點擊「教職員管理」→「設定」
2. 在「外觀設定」區塊中選擇「淺色模式」或「深色模式」
3. 保存設定後，前台顯示將採用所選主題

### 短代碼使用
```html
[teacher_list] - 顯示教師列表
[staff_list] - 顯示職員列表
```

## 開發資訊

- 版本：1.0.0
- 作者：弘光科技大學
- 最後更新：2024-03-22

## 系統需求

- WordPress 5.0 或更高版本
- PHP 7.4 或更高版本
- MySQL 5.6 或更高版本

## 授權

本外掛採用 GPL v2 或更高版本授權。

## 更新日誌

### 1.0.0
- 初始版本發布
- 實現基本的教師和職員管理功能
- 支援響應式設計
- 支援自定義排序和顯示

## 貢獻指南

歡迎提交 Issue 和 Pull Request 來改進這個專案。

## 聯絡方式

如有任何問題或建議，請聯繫：
- 電子郵件：[您的郵箱]
- 網站：[您的網站]
