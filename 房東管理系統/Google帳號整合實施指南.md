# Google帳號整合實施指南

## 1. 前置準備

### 1.1 Google Cloud Console設定

#### 步驟1：建立Google Cloud專案
1. 前往 [Google Cloud Console](https://console.cloud.google.com/)
2. 建立新專案或選擇現有專案
3. 啟用必要的API：
   - Google Sheets API
   - Google Drive API
   - Google+ API (用於用戶資料)

#### 步驟2：建立OAuth 2.0憑證
1. 在Google Cloud Console中，前往「API和服務」→「憑證」
2. 點擊「建立憑證」→「OAuth 2.0用戶端ID」
3. 選擇應用程式類型：
   - **網頁應用程式**：用於後端API
   - **JavaScript**：用於前端應用
4. 設定授權的重新導向URI：
   - 開發環境：`http://localhost:3000/auth/google/callback`
   - 生產環境：`https://yourdomain.com/auth/google/callback`

#### 步驟3：取得API金鑰
1. 建立服務帳號金鑰（用於後端API存取）
2. 下載JSON金鑰檔案
3. 安全存儲金鑰檔案

### 1.2 環境變數設定

```bash
# .env 檔案
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:3000/auth/google/callback
GOOGLE_SERVICE_ACCOUNT_KEY_PATH=./service-account-key.json
JWT_SECRET=your_jwt_secret
```

## 2. 後端實施

### 2.1 安裝必要套件

```bash
npm install google-auth-library googleapis jsonwebtoken express-session
```

### 2.2 Google OAuth服務

```javascript
// services/googleAuthService.js
const { OAuth2Client } = require('google-auth-library');
const jwt = require('jsonwebtoken');

class GoogleAuthService {
  constructor() {
    this.client = new OAuth2Client(
      process.env.GOOGLE_CLIENT_ID,
      process.env.GOOGLE_CLIENT_SECRET,
      process.env.GOOGLE_REDIRECT_URI
    );
  }

  // 生成授權URL
  generateAuthUrl() {
    const scopes = [
      'https://www.googleapis.com/auth/userinfo.email',
      'https://www.googleapis.com/auth/userinfo.profile',
      'https://www.googleapis.com/auth/spreadsheets',
      'https://www.googleapis.com/auth/drive.file'
    ];

    return this.client.generateAuthUrl({
      access_type: 'offline',
      scope: scopes,
      prompt: 'consent'
    });
  }

  // 驗證ID Token
  async verifyIdToken(idToken) {
    try {
      const ticket = await this.client.verifyIdToken({
        idToken,
        audience: process.env.GOOGLE_CLIENT_ID
      });
      return ticket.getPayload();
    } catch (error) {
      throw new Error('Invalid ID token');
    }
  }

  // 取得存取Token
  async getTokens(code) {
    try {
      const { tokens } = await this.client.getToken(code);
      return tokens;
    } catch (error) {
      throw new Error('Failed to get tokens');
    }
  }

  // 建立JWT Token
  createJWT(user) {
    return jwt.sign(
      {
        id: user.id,
        email: user.email,
        name: user.name,
        googleId: user.googleId
      },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );
  }
}

module.exports = GoogleAuthService;
```

### 2.3 認證路由

```javascript
// routes/auth.js
const express = require('express');
const GoogleAuthService = require('../services/googleAuthService');
const User = require('../models/User');

const router = express.Router();
const googleAuth = new GoogleAuthService();

// 開始Google OAuth流程
router.get('/google', (req, res) => {
  const authUrl = googleAuth.generateAuthUrl();
  res.redirect(authUrl);
});

// Google OAuth回調
router.get('/google/callback', async (req, res) => {
  try {
    const { code } = req.query;
    
    // 取得存取Token
    const tokens = await googleAuth.getTokens(code);
    
    // 使用ID Token取得用戶資料
    const payload = await googleAuth.verifyIdToken(tokens.id_token);
    
    // 查找或建立用戶
    let user = await User.findOne({ googleId: payload.sub });
    
    if (!user) {
      user = await User.create({
        googleId: payload.sub,
        email: payload.email,
        name: payload.name,
        picture: payload.picture,
        accessToken: tokens.access_token,
        refreshToken: tokens.refresh_token
      });
    } else {
      // 更新存取Token
      user.accessToken = tokens.access_token;
      if (tokens.refresh_token) {
        user.refreshToken = tokens.refresh_token;
      }
      await user.save();
    }
    
    // 建立JWT Token
    const jwtToken = googleAuth.createJWT(user);
    
    // 重定向到前端並傳遞Token
    res.redirect(`/auth-success?token=${jwtToken}`);
    
  } catch (error) {
    console.error('Google OAuth error:', error);
    res.redirect('/auth-error');
  }
});

module.exports = router;
```

### 2.4 認證中間件

```javascript
// middleware/auth.js
const jwt = require('jsonwebtoken');
const User = require('../models/User');

const authMiddleware = async (req, res, next) => {
  try {
    const token = req.headers.authorization?.replace('Bearer ', '');
    
    if (!token) {
      return res.status(401).json({ error: 'No token provided' });
    }
    
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const user = await User.findById(decoded.id);
    
    if (!user) {
      return res.status(401).json({ error: 'User not found' });
    }
    
    req.user = user;
    next();
  } catch (error) {
    res.status(401).json({ error: 'Invalid token' });
  }
};

module.exports = authMiddleware;
```

## 3. 前端實施

### 3.1 安裝必要套件

```bash
npm install @react-oauth/google axios
```

### 3.2 Google OAuth Provider設定

```javascript
// pages/_app.js
import { GoogleOAuthProvider } from '@react-oauth/google';

function MyApp({ Component, pageProps }) {
  return (
    <GoogleOAuthProvider clientId={process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID}>
      <Component {...pageProps} />
    </GoogleOAuthProvider>
  );
}

export default MyApp;
```

### 3.3 登入組件

```javascript
// components/GoogleLogin.js
import { GoogleLogin } from '@react-oauth/google';
import { useRouter } from 'next/router';

const GoogleLoginComponent = () => {
  const router = useRouter();

  const handleSuccess = async (credentialResponse) => {
    try {
      // 發送credential到後端驗證
      const response = await fetch('/api/auth/google/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          credential: credentialResponse.credential,
        }),
      });

      const data = await response.json();

      if (data.token) {
        // 儲存Token
        localStorage.setItem('token', data.token);
        
        // 重定向到主頁面
        router.push('/dashboard');
      }
    } catch (error) {
      console.error('Login error:', error);
    }
  };

  return (
    <div className="login-container">
      <h2>使用Google帳號登入</h2>
      <GoogleLogin
        onSuccess={handleSuccess}
        onError={() => console.log('Login Failed')}
        scope="https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/drive.file"
      />
    </div>
  );
};

export default GoogleLoginComponent;
```

### 3.4 API路由處理

```javascript
// pages/api/auth/google/verify.js
import { OAuth2Client } from 'google-auth-library';
import jwt from 'jsonwebtoken';

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const { credential } = req.body;
    
    const client = new OAuth2Client(process.env.GOOGLE_CLIENT_ID);
    const ticket = await client.verifyIdToken({
      idToken: credential,
      audience: process.env.GOOGLE_CLIENT_ID
    });
    
    const payload = ticket.getPayload();
    
    // 查找或建立用戶
    let user = await User.findOne({ googleId: payload.sub });
    
    if (!user) {
      user = await User.create({
        googleId: payload.sub,
        email: payload.email,
        name: payload.name,
        picture: payload.picture
      });
    }
    
    // 建立JWT Token
    const token = jwt.sign(
      {
        id: user.id,
        email: user.email,
        name: user.name,
        googleId: user.googleId
      },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );
    
    res.json({ token, user });
    
  } catch (error) {
    console.error('Verification error:', error);
    res.status(400).json({ error: 'Verification failed' });
  }
}
```

## 4. Google Sheets整合

### 4.1 Google Sheets服務

```javascript
// services/googleSheetsService.js
const { google } = require('googleapis');
const fs = require('fs');

class GoogleSheetsService {
  constructor() {
    const keyFile = JSON.parse(
      fs.readFileSync(process.env.GOOGLE_SERVICE_ACCOUNT_KEY_PATH)
    );
    
    this.auth = new google.auth.GoogleAuth({
      credentials: keyFile,
      scopes: ['https://www.googleapis.com/auth/spreadsheets']
    });
    
    this.sheets = google.sheets({ version: 'v4', auth: this.auth });
  }

  // 建立新的試算表
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

  // 更新資料
  async updateData(spreadsheetId, range, values) {
    const resource = { values };
    await this.sheets.spreadsheets.values.update({
      spreadsheetId,
      range,
      valueInputOption: 'RAW',
      resource
    });
  }

  // 讀取資料
  async readData(spreadsheetId, range) {
    const response = await this.sheets.spreadsheets.values.get({
      spreadsheetId,
      range
    });
    return response.data.values;
  }

  // 新增資料
  async appendData(spreadsheetId, range, values) {
    const resource = { values };
    await this.sheets.spreadsheets.values.append({
      spreadsheetId,
      range,
      valueInputOption: 'RAW',
      resource
    });
  }
}

module.exports = GoogleSheetsService;
```

### 4.2 資料同步控制器

```javascript
// controllers/syncController.js
const GoogleSheetsService = require('../services/googleSheetsService');
const Property = require('../models/Property');
const Tenant = require('../models/Tenant');
const Contract = require('../models/Contract');

class SyncController {
  constructor() {
    this.sheetsService = new GoogleSheetsService();
  }

  // 同步房屋資料
  async syncProperties(spreadsheetId) {
    try {
      const properties = await Property.find();
      const data = properties.map(prop => [
        prop.id,
        prop.address,
        prop.type,
        prop.status,
        prop.createdAt
      ]);

      await this.sheetsService.updateData(
        spreadsheetId,
        '房屋資料!A:E',
        [['ID', '地址', '類型', '狀態', '建立日期'], ...data]
      );

      return { success: true, message: '房屋資料同步成功' };
    } catch (error) {
      console.error('Sync properties error:', error);
      throw error;
    }
  }

  // 同步租客資料
  async syncTenants(spreadsheetId) {
    try {
      const tenants = await Tenant.find();
      const data = tenants.map(tenant => [
        tenant.id,
        tenant.name,
        tenant.email,
        tenant.phone,
        tenant.createdAt
      ]);

      await this.sheetsService.updateData(
        spreadsheetId,
        '租客資料!A:E',
        [['ID', '姓名', '郵箱', '電話', '建立日期'], ...data]
      );

      return { success: true, message: '租客資料同步成功' };
    } catch (error) {
      console.error('Sync tenants error:', error);
      throw error;
    }
  }

  // 同步電費記錄
  async syncElectricityRecords(spreadsheetId) {
    try {
      const records = await ElectricityRecord.find()
        .populate('contract')
        .populate('property');

      const data = records.map(record => [
        record.id,
        record.property.address,
        record.privateUsage,
        record.publicUsage,
        record.totalAmount,
        record.recordDate
      ]);

      await this.sheetsService.updateData(
        spreadsheetId,
        '電費記錄!A:F',
        [['ID', '房屋地址', '私電度數', '公電度數', '總金額', '記錄日期'], ...data]
      );

      return { success: true, message: '電費記錄同步成功' };
    } catch (error) {
      console.error('Sync electricity records error:', error);
      throw error;
    }
  }
}

module.exports = SyncController;
```

## 5. 權限管理

### 5.1 設定Google Sheets權限

```javascript
// services/permissionService.js
const { google } = require('googleapis');

class PermissionService {
  constructor() {
    this.drive = google.drive({ version: 'v3', auth: this.auth });
  }

  // 為用戶設定試算表權限
  async setSpreadsheetPermission(spreadsheetId, email, role = 'writer') {
    try {
      await this.drive.permissions.create({
        fileId: spreadsheetId,
        requestBody: {
          role: role,
          type: 'user',
          emailAddress: email
        }
      });

      return { success: true, message: '權限設定成功' };
    } catch (error) {
      console.error('Set permission error:', error);
      throw error;
    }
  }

  // 移除用戶權限
  async removeSpreadsheetPermission(spreadsheetId, email) {
    try {
      const permissions = await this.drive.permissions.list({
        fileId: spreadsheetId
      });

      const permission = permissions.data.permissions.find(
        p => p.emailAddress === email
      );

      if (permission) {
        await this.drive.permissions.delete({
          fileId: spreadsheetId,
          permissionId: permission.id
        });
      }

      return { success: true, message: '權限移除成功' };
    } catch (error) {
      console.error('Remove permission error:', error);
      throw error;
    }
  }
}

module.exports = PermissionService;
```

## 6. 測試和部署

### 6.1 本地測試

```bash
# 啟動開發伺服器
npm run dev

# 測試Google OAuth流程
# 1. 訪問 http://localhost:3000/login
# 2. 點擊Google登入按鈕
# 3. 完成OAuth授權
# 4. 驗證Token和用戶資料
```

### 6.2 生產環境部署

```bash
# 設定生產環境變數
GOOGLE_CLIENT_ID=your_production_client_id
GOOGLE_CLIENT_SECRET=your_production_client_secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
JWT_SECRET=your_production_jwt_secret

# 部署到Vercel
vercel --prod

# 部署到Railway
railway up
```

### 6.3 監控和維護

```javascript
// 監控Google API使用量
const monitorAPIUsage = async () => {
  try {
    const quota = await google.quota.get();
    console.log('API Quota:', quota);
    
    if (quota.usage > quota.limit * 0.8) {
      // 發送警告通知
      await sendAlert('Google API使用量接近限制');
    }
  } catch (error) {
    console.error('Monitor API usage error:', error);
  }
};

// 定期執行監控
setInterval(monitorAPIUsage, 3600000); // 每小時檢查一次
```

## 7. 常見問題和解決方案

### 7.1 OAuth錯誤
- **問題**：授權失敗
- **解決**：檢查Client ID和Secret是否正確

### 7.2 API配額限制
- **問題**：Google API配額超限
- **解決**：實施請求限流和快取機制

### 7.3 權限問題
- **問題**：無法存取Google Sheets
- **解決**：檢查服務帳號權限和檔案權限設定

### 7.4 同步失敗
- **問題**：資料同步失敗
- **解決**：實施重試機制和錯誤日誌記錄 