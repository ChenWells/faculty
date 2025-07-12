# Google帳號整合架構設計

## 1. 整合Google帳號的優勢分析

### 1.1 技術優勢
- **統一認證**：使用Google OAuth 2.0，減少重複開發認證系統
- **API整合**：直接使用Google Sheets API和Google Drive API
- **安全性**：Google的安全標準和雙因素認證
- **可擴展性**：未來可輕鬆整合其他Google服務

### 1.2 業務優勢
- **用戶體驗**：用戶無需記住額外帳號密碼
- **管理便利**：房東可能已有Google帳號
- **協作功能**：天然支援Google Workspace協作
- **成本效益**：減少認證系統開發和維護成本

### 1.3 資料整合優勢
- **無縫同步**：Google帳號直接關聯Google Sheets
- **權限管理**：利用Google的權限系統
- **備份便利**：Google Drive自動備份
- **離線存取**：Google Workspace離線功能

## 2. 架構設計

### 2.1 認證流程
```
用戶訪問系統 → 重定向到Google OAuth → 授權後返回 → 建立會話 → 存取系統
```

### 2.2 權限範圍 (Scopes)
```javascript
const SCOPES = [
  'https://www.googleapis.com/auth/userinfo.email',     // 用戶郵箱
  'https://www.googleapis.com/auth/userinfo.profile',   // 用戶資料
  'https://www.googleapis.com/auth/spreadsheets',       // Google Sheets存取
  'https://www.googleapis.com/auth/drive.file',         // Google Drive檔案存取
  'https://www.googleapis.com/auth/drive.metadata.readonly' // Drive元資料讀取
];
```

### 2.3 系統架構圖
```
+----------------+         +----------------+         +-----------------+
|                |         |                |         |                 |
|  前端應用      |  -----> |  後端API       |  -----> |  PostgreSQL     |
|  React/Next.js |         |  Node.js/Express|        |  主資料庫       |
|                |         |                |         |                 |
+----------------+         +----------------+         +-----------------+
        |                          |                          |
        |                          |                          |
        v                          v                          v
+-----------------+      +------------------+      +------------------+
|                 |      |                  |      |                  |
|  Google OAuth   |      |  Google Sheets   |      |  Google Drive    |
|  認證服務       |      |  API服務         |      |  API服務         |
|                 |      |                  |      |                  |
+-----------------+      +------------------+      +------------------+
```

## 3. 技術實現

### 3.1 前端實現
```javascript
// Google OAuth登入組件
import { GoogleLogin } from '@react-oauth/google';

const GoogleAuthComponent = () => {
  const handleSuccess = (credentialResponse) => {
    // 發送credential到後端驗證
    authenticateWithBackend(credentialResponse.credential);
  };

  return (
    <GoogleLogin
      onSuccess={handleSuccess}
      onError={() => console.log('Login Failed')}
      scope={SCOPES.join(' ')}
    />
  );
};
```

### 3.2 後端實現
```javascript
// Google OAuth驗證中間件
const googleAuthMiddleware = async (req, res, next) => {
  try {
    const { idToken } = req.body;
    const ticket = await client.verifyIdToken({
      idToken,
      audience: process.env.GOOGLE_CLIENT_ID
    });
    
    const payload = ticket.getPayload();
    req.user = {
      googleId: payload.sub,
      email: payload.email,
      name: payload.name,
      picture: payload.picture
    };
    
    next();
  } catch (error) {
    res.status(401).json({ error: '認證失敗' });
  }
};
```

### 3.3 Google Sheets整合
```javascript
// Google Sheets服務
class GoogleSheetsService {
  constructor(authClient) {
    this.sheets = google.sheets({ version: 'v4', auth: authClient });
  }

  async createSpreadsheet(title) {
    const resource = {
      properties: { title },
      sheets: [
        { properties: { title: '房屋資料' } },
        { properties: { title: '租客資料' } },
        { properties: { title: '合約資料' } },
        { properties: { title: '電費記錄' } },
        { properties: { title: '收租記錄' } }
      ]
    };

    const response = await this.sheets.spreadsheets.create({ resource });
    return response.data;
  }

  async updateData(spreadsheetId, range, values) {
    const resource = { values };
    await this.sheets.spreadsheets.values.update({
      spreadsheetId,
      range,
      valueInputOption: 'RAW',
      resource
    });
  }
}
```

## 4. 資料同步策略

### 4.1 即時同步
```javascript
// 資料變更時即時同步到Google Sheets
const syncToGoogleSheets = async (data, operation) => {
  try {
    const sheetsService = new GoogleSheetsService(authClient);
    
    switch (operation) {
      case 'CREATE':
        await sheetsService.appendData(SPREADSHEET_ID, 'A:Z', [data]);
        break;
      case 'UPDATE':
        await sheetsService.updateData(SPREADSHEET_ID, range, [data]);
        break;
      case 'DELETE':
        await sheetsService.deleteData(SPREADSHEET_ID, range);
        break;
    }
  } catch (error) {
    // 記錄錯誤，加入重試佇列
    await addToRetryQueue(data, operation);
  }
};
```

### 4.2 定期同步
```javascript
// 每日定期同步，確保資料一致性
const scheduledSync = async () => {
  const dbData = await getAllDataFromDatabase();
  const sheetsData = await getDataFromGoogleSheets();
  
  const differences = compareData(dbData, sheetsData);
  
  for (const diff of differences) {
    await syncToGoogleSheets(diff.data, diff.operation);
  }
};

// 使用cron job執行
cron.schedule('0 2 * * *', scheduledSync); // 每天凌晨2點執行
```

## 5. 權限管理

### 5.1 用戶權限
```javascript
// 用戶權限檢查
const checkUserPermission = async (userId, resourceId, action) => {
  const user = await getUserById(userId);
  const resource = await getResourceById(resourceId);
  
  // 檢查是否為資源擁有者
  if (resource.ownerId === userId) {
    return true;
  }
  
  // 檢查是否為協作者
  const collaborators = await getCollaborators(resourceId);
  const collaborator = collaborators.find(c => c.userId === userId);
  
  if (collaborator && collaborator.permissions.includes(action)) {
    return true;
  }
  
  return false;
};
```

### 5.2 Google Sheets權限
```javascript
// 設定Google Sheets權限
const setSpreadsheetPermissions = async (spreadsheetId, email, role) => {
  const drive = google.drive({ version: 'v3', auth: authClient });
  
  await drive.permissions.create({
    fileId: spreadsheetId,
    requestBody: {
      role: role, // 'reader', 'writer', 'owner'
      type: 'user',
      emailAddress: email
    }
  });
};
```

## 6. 安全性考量

### 6.1 資料加密
- 敏感資料在資料庫中加密存儲
- Google Sheets中的敏感欄位使用代碼化
- API通訊使用HTTPS

### 6.2 存取控制
- 基於Google帳號的認證
- 細粒度權限控制
- 操作日誌記錄

### 6.3 資料備份
- 本地資料庫定期備份
- Google Drive自動備份
- 多重備份策略

## 7. 用戶體驗設計

### 7.1 登入流程
1. 用戶點擊「使用Google帳號登入」
2. 重定向到Google OAuth頁面
3. 用戶授權應用程式存取權限
4. 返回系統，自動建立會話
5. 首次登入時建立用戶資料

### 7.2 權限管理界面
- 顯示當前Google帳號資訊
- 管理Google Sheets存取權限
- 設定協作者權限
- 查看操作歷史

### 7.3 資料同步狀態
- 顯示同步狀態指示器
- 同步錯誤通知
- 手動同步按鈕
- 同步歷史記錄

## 8. 開發時程

### 8.1 第一階段：基礎整合（1週）
- Google OAuth設定
- 用戶認證流程
- 基本權限管理

### 8.2 第二階段：Google Sheets整合（1週）
- Google Sheets API整合
- 資料同步機制
- 錯誤處理和重試

### 8.3 第三階段：Google Drive整合（1週）
- 文件存儲功能
- 檔案權限管理
- 備份功能

### 8.4 第四階段：優化和測試（1週）
- 效能優化
- 安全性測試
- 用戶體驗測試

## 9. 成本效益分析

### 9.1 開發成本節省
- 無需開發自建認證系統
- 減少安全相關開發工作
- 利用Google現有的安全機制

### 9.2 維護成本節省
- 減少認證系統維護
- 利用Google的服務可靠性
- 自動的安全更新

### 9.3 用戶接受度
- 用戶熟悉Google登入
- 減少註冊門檻
- 提升用戶信任度

## 10. 風險評估

### 10.1 技術風險
- **Google服務依賴**：Google服務中斷可能影響系統
- **API限制**：Google API配額限制
- **版本變更**：Google API版本更新

### 10.2 業務風險
- **資料主權**：資料存儲在Google服務器
- **隱私考量**：Google可能存取用戶資料
- **成本控制**：Google服務使用量增加

### 10.3 風險緩解
- 建立本地備份機制
- 監控API使用量
- 定期評估替代方案
- 明確的隱私政策 