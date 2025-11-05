# Railway環境用セットアップファイル

## 📋 このディレクトリについて

このディレクトリには、**Railway本番環境 + GCP Cloud SQL** 用に設定済みのセットアップファイルが含まれています。

## ✅ 既に設定されている内容

### 2_Site_AutoRun_railway.bat

```batch
set DOMAIN=mgg-webservice-production.up.railway.app
```

**手動での変更は不要です！** そのまま実行してください。

## 🚀 使い方

### 元のセットアップファイルと組み合わせる

1. **元のセットアップファイル一式を準備:**
   ```
   WorksetClientSetup_ja_local/
   ├── setupapp.exe (33MB)
   ├── GoogleChromeStandaloneEnterprise64.msi (59MB)
   ├── 1_Office_AutoRun.bat
   ├── 3_Last_AutoRun.bat
   ├── Net8AppInstall.bat
   ├── ChromeLocalInstall.bat
   ├── PowerControl.bat
   └── PowerShellスクリプト群/
   ```

2. **このファイルを追加:**
   ```
   2_Site_AutoRun_railway.bat （このファイル）
   ```

3. **Windows PC側で実行順序:**
   - ステップ1: `1_Office_AutoRun.bat` 実行 → 再起動
   - ステップ2: `2_Site_AutoRun_railway.bat` 実行 ← Railway用
   - ステップ3: `3_Last_AutoRun.bat` 実行

## 🔧 設定内容

- **Railway Webサーバー:** https://mgg-webservice-production.up.railway.app/
- **GCP Cloud SQL:** 136.116.70.86:3306
- **データベース:** net8_dev

## ⚠️ 注意事項

- ngrokは使用しません
- 元の `2_Site_AutoRun.bat` は使用しません（DOMAIN設定が違うため）
- このファイルをそのまま使用してください

---

**作成日:** 2025-11-05
**環境:** Railway本番環境 + GCP Cloud SQL
