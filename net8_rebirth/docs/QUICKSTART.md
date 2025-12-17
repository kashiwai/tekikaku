# Net8 クイックスタートガイド

**5分で統合を始める**

---

## 1. APIキーを取得

テスト用キー: `pk_demo_12345`（本番前にお問い合わせください）

---

## 2. ゲーム開始APIを呼び出し

```bash
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_user_001",
    "modelId": "HOKUTO4GO",
    "initialPoints": 5000
  }'
```

レスポンス:
```json
{
  "success": true,
  "sessionId": "sess_abc123",
  "memberNo": 12345
}
```

---

## 3. HTMLにiframeを埋め込み

```html
<iframe
  src="https://mgg-webservice-production.up.railway.app/play_embed/?session_id=sess_abc123&member_no=12345"
  width="800"
  height="600"
  allow="autoplay; fullscreen"
></iframe>
```

---

## 4. ゲーム終了APIを呼び出し

```bash
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_end.php \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "sess_abc123",
    "result": "completed",
    "pointsWon": 2500
  }'
```

---

## 完了！

詳細は [SDK統合ガイド](./NET8_SDK_INTEGRATION_GUIDE.md) を参照してください。

---

## 最小限のJavaScriptコード

```javascript
// ゲーム開始
async function startNet8Game(userId, points) {
  const res = await fetch('/api/v1/game_start.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer pk_demo_12345',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      modelId: 'HOKUTO4GO',
      initialPoints: points
    })
  });

  const data = await res.json();

  // iframeを表示
  document.getElementById('game').innerHTML = `
    <iframe src="https://mgg-webservice-production.up.railway.app/play_embed/?session_id=${data.sessionId}&member_no=${data.memberNo}"
      width="800" height="600" allow="autoplay; fullscreen"></iframe>
  `;

  return data.sessionId;
}

// ゲーム終了
async function endNet8Game(sessionId, pointsWon) {
  const res = await fetch('/api/v1/game_end.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer pk_demo_12345',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      sessionId: sessionId,
      result: 'completed',
      pointsWon: pointsWon
    })
  });

  return res.json();
}
```

---

## チェックリスト

- [ ] APIキーを取得した
- [ ] game_start APIが200を返す
- [ ] iframeにゲーム画面が表示される
- [ ] game_end APIが200を返す
- [ ] 残高が正しく更新される

問題がある場合 → [トラブルシューティング](./NET8_SDK_INTEGRATION_GUIDE.md#7-トラブルシューティング)
