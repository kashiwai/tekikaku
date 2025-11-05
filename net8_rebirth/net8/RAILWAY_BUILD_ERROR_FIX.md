# Railway ビルドエラー修正ガイド

## 🔴 エラー内容

```
Build › Build image (00:11)
Error creating build plan with Railpack
```

このエラーは、Railwayが正しいビルド設定を検出できない場合に発生します。

---

## ✅ 解決方法

### 原因

Railway は以下の順序でビルド方法を検出します：
1. `railway.json` / `railway.toml`
2. Dockerfile（ルートディレクトリ）
3. Nixpacks（自動検出）

プロジェクトのDockerfileが `Dockerfile.web` と `Dockerfile.signaling` という名前のため、Railwayが自動検出できません。

---

## 🔧 修正手順

### 方法1: Settings でDockerfileパスを明示的に指定（推奨）

#### Signaling サーバーの場合

1. **Railway Dashboard で該当サービスをクリック**

2. **"Settings" タブをクリック**

3. **"Deploy" セクションまでスクロール**

4. **以下を設定**:
   ```
   Builder: Dockerfile
   Dockerfile Path: Dockerfile.signaling
   ```

   ⚠️ **重要**: `Dockerfile Path` の設定は以下のように入力：
   ```
   Dockerfile.signaling
   ```

   **先頭にスラッシュは不要です**

5. **"Deploy" をクリック**

---

#### Web サーバーの場合

1. **Railway Dashboard で該当サービスをクリック**

2. **"Settings" タブをクリック**

3. **"Deploy" セクションまでスクロール**

4. **以下を設定**:
   ```
   Builder: Dockerfile
   Dockerfile Path: Dockerfile.web
   ```

5. **"Deploy" をクリック**

---

### 方法2: railway.toml を作成（代替案）

各サービスディレクトリに `railway.toml` を作成する方法もあります。

ただし、この場合はGitHubリポジトリの更新が必要なので、**方法1を推奨**します。

---

## 📸 設定画面のスクリーンショット位置

### Settings → Deploy セクション

```
Settings
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

General
Source
Deploy ← ここをクリック
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Builder
  ○ Nixpacks
  ● Dockerfile ← これを選択

Dockerfile Path
  [Dockerfile.signaling] ← ここに入力

Start Command
  (空のまま)

Watch Paths
  (空のまま)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## ✅ 正しい設定値

### net8-signaling サービス

| 項目 | 値 |
|------|-----|
| Builder | Dockerfile |
| Dockerfile Path | `Dockerfile.signaling` |
| Start Command | (空) |

### net8-web サービス

| 項目 | 値 |
|------|-----|
| Builder | Dockerfile |
| Dockerfile Path | `Dockerfile.web` |
| Start Command | (空) |

---

## 🆘 よくある間違い

### ❌ 間違った設定

```
Dockerfile Path: /Dockerfile.signaling  ← 先頭のスラッシュは不要
Dockerfile Path: ./Dockerfile.signaling  ← ./ も不要
Dockerfile Path: net8_rebirth/net8/Dockerfile.signaling  ← フルパスは不要
```

### ✅ 正しい設定

```
Dockerfile Path: Dockerfile.signaling
```

---

## 🔍 設定確認チェックリスト

デプロイ前に以下を確認：

- [ ] Builder が "Dockerfile" に設定されている
- [ ] Dockerfile Path が正確に入力されている
  - [ ] Signaling: `Dockerfile.signaling`
  - [ ] Web: `Dockerfile.web`
- [ ] Root Directory は空のまま
- [ ] Start Command は空のまま

---

## 📋 再デプロイ手順

設定変更後：

1. **"Deployments" タブをクリック**

2. **"Deploy" ボタンをクリック**

3. **ビルドログを確認**
   - "View Logs" でリアルタイムログを表示
   - エラーがあれば詳細を確認

---

## ✅ 成功の確認

ビルドが成功すると、以下のようなログが表示されます：

```
Building Dockerfile
Step 1/10 : FROM node:14-alpine
Step 2/10 : RUN apk add --no-cache python3 make g++ sqlite-dev
...
Step 10/10 : CMD node bin/peerjs -p ${PORT} -k ${PEERJS_KEY}
Successfully built
Deployment successful
```

---

## 🆘 それでもエラーが出る場合

### エラーログの確認

1. **"Deployments" タブ → "View Logs"**

2. **エラーメッセージをコピー**

3. **以下の情報を共有**:
   - エラーメッセージ全文
   - どのサービスか（Signaling / Web）
   - Settings → Deploy の設定値

---

## 💡 追加のトラブルシューティング

### Q: "Dockerfile not found" エラー

**A**: Dockerfile Path の設定を確認
- スペルミスがないか
- 大文字小文字が正確か
- 余分な文字がないか

### Q: ビルドは成功するが起動しない

**A**: 環境変数を確認
- Variables タブで必要な環境変数が設定されているか
- 参照構文 `${{サービス名.変数名}}` が正しいか

---

## 🎯 次のステップ

1. **Settings → Deploy** を開く
2. **Builder を "Dockerfile" に設定**
3. **Dockerfile Path を設定**:
   - Signaling: `Dockerfile.signaling`
   - Web: `Dockerfile.web`
4. **"Deploy" をクリック**
5. **ビルドログを確認**

---

**エラーが続く場合**: ビルドログのスクリーンショットを共有してください
