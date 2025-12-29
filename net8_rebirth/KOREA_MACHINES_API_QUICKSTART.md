# 🎰 機械リストAPI クイックスタート（韓国チーム向け）

**作成日**: 2025-12-29
**API**: `/api/v1/list_machines.php`
**状態**: ✅ デプロイ完了（Railway Production）

---

## 🚀 すぐに試す

### 1. 全台リスト取得

```bash
curl "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?lang=ko" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**結果**: 韓国語で全68台の情報が返る

---

### 2. 利用可能な台のみ取得（最も使う）

```bash
curl "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?availableOnly=true&lang=ko" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**用途**: ユーザーに選択可能な台を表示

---

### 3. 特定機種の台を取得

```bash
curl "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?modelId=HOKUTO4GO&lang=ko" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**用途**: 「北斗の拳」を選んだユーザーに、その機種の台番号を表示

---

## 📦 レスポンス例

```json
{
  "success": true,
  "total": 68,
  "available": 45,
  "playing": 3,
  "machines": [
    {
      "machineNo": 1,
      "modelId": "HOKUTO4GO",
      "modelName": "북두의 권 초호기",
      "category": "slot",
      "status": "available",
      "isAvailable": true,
      "images": {
        "thumbnail": "https://mgg-webservice-production.up.railway.app/data/img/model/hokuto4go.jpg",
        "detail": "https://mgg-webservice-production.up.railway.app/data/img/model/hokuto4go.jpg",
        "reel": null
      },
      "camera": {
        "cameraNo": 1,
        "peerId": "camera_10000021_1765859502",
        "mac": "00:11:22:33:44:55"
      },
      "currentUser": null
    }
  ]
}
```

---

## 🎨 React/Next.js 実装例

### 簡単な実装（コピペOK）

```typescript
// hooks/useMachines.ts
import { useState, useEffect } from 'react';

export function useMachines(modelId?: string) {
  const [machines, setMachines] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchMachines() {
      const params = new URLSearchParams({
        availableOnly: 'true',
        lang: 'ko'
      });
      if (modelId) params.append('modelId', modelId);

      const response = await fetch(
        `https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?${params}`,
        {
          headers: {
            'Authorization': `Bearer ${process.env.NEXT_PUBLIC_NET8_API_KEY}`
          }
        }
      );

      const data = await response.json();
      setMachines(data.machines || []);
      setLoading(false);
    }

    fetchMachines();
  }, [modelId]);

  return { machines, loading };
}
```

### 使い方

```typescript
// コンポーネント内
function MachineList() {
  const { machines, loading } = useMachines('HOKUTO4GO');

  if (loading) return <div>로딩 중...</div>;

  return (
    <div>
      <h2>이용 가능한 기계: {machines.length}대</h2>
      {machines.map(m => (
        <div key={m.machineNo} className="flex items-center gap-4">
          {/* 機種画像を表示 */}
          {m.images?.thumbnail && (
            <img
              src={m.images.thumbnail}
              alt={m.modelName}
              className="w-20 h-20 object-cover rounded"
            />
          )}
          <div>
            <h3>{m.machineNo}번 台 - {m.modelName}</h3>
            <p className="text-sm text-gray-500">{m.category}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
```

---

## 🔑 重要なフィールド

| フィールド | 説明 | 使用例 |
|----------|------|--------|
| `machineNo` | 台番号 | ゲーム開始APIに渡す |
| `modelName` | 機種名（韓国語） | UI表示 |
| `status` | 状態 | `available` なら選択可能 |
| `isAvailable` | 利用可能か | フィルタリング |
| `images.thumbnail` | サムネイル画像URL | 機種画像を表示 |
| `images.detail` | 詳細画像URL | 詳細画面で表示 |
| `camera.peerId` | カメラID | WebRTC接続用 |

---

## 💡 よくある使い方

### 1. ゲーム開始前に台を選ぶ

```typescript
// 1. 機種選択（例: 北斗の拳）
const modelId = 'HOKUTO4GO';

// 2. その機種の利用可能な台を取得
const { machines } = useMachines(modelId);

// 3. ユーザーに台番号を選ばせる
// 例: 「1番台」「3番台」「7番台」

// 4. 選択した台でゲーム開始
await startGame(userId, modelId, selectedMachineNo);
```

### 2. リアルタイム空き台表示

```typescript
// 10秒ごとに更新
useEffect(() => {
  const interval = setInterval(() => {
    refetchMachines();
  }, 10000);

  return () => clearInterval(interval);
}, []);
```

---

## 📝 クエリパラメータ一覧

| パラメータ | 説明 | 例 |
|-----------|------|-----|
| `modelId` | 特定機種でフィルタ | `HOKUTO4GO` |
| `availableOnly` | 利用可能な台のみ | `true` |
| `status` | ステータスでフィルタ | `available`, `playing` |
| `lang` | 言語 | `ko`, `ja`, `en` |
| `limit` | 取得件数 | `20` |
| `offset` | オフセット | `0` |

---

## 🔗 他のAPIとの組み合わせ

```typescript
// 1. 機種リスト取得
const models = await fetch('/api/v1/models.php');

// 2. ユーザーが機種を選択
const selectedModel = 'HOKUTO4GO';

// 3. その機種の利用可能な台を取得（★このAPI）
const machines = await fetch(`/api/v1/list_machines.php?modelId=${selectedModel}&availableOnly=true`);

// 4. ユーザーが台を選択
const selectedMachineNo = 1;

// 5. ゲーム開始
await fetch('/api/v1/game_start.php', {
  method: 'POST',
  body: JSON.stringify({
    userId: userId,
    modelId: selectedModel,
    machineNo: selectedMachineNo
  })
});
```

---

## ⚠️ 注意事項

1. **APIキー必須**: `Authorization` ヘッダーが必要
2. **CORS対応済み**: 韓国側フロントエンドから直接呼び出し可能
3. **多言語対応**: `lang=ko` で韓国語の機種名が返る
4. **リアルタイム**: 最新の稼働状況が返る

---

## 📚 詳細ドキュメント

完全な仕様は以下を参照：
- **NET8_MACHINES_API_SPEC.md**（詳細仕様）
- **NET8_KOREA_INTEGRATION_COMPLETE_SPEC.md**（統合仕様）

---

## 🎯 まとめ

### このAPIで何ができる？

✅ 利用可能な台のリアルタイム確認
✅ 特定機種の台番号リスト
✅ 韓国語での機種名表示
✅ 台の稼働状況監視

### いつ使う？

- ゲーム開始前に台を選ぶとき
- ダッシュボードで空き台を表示するとき
- 管理画面で全台の状況を確認するとき

---

**作成日**: 2025-12-29
**デプロイ**: ✅ Railway Production
**状態**: すぐに使用可能
