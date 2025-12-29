# NET8 機械リストAPI仕様書

**バージョン**: 1.0.0
**作成日**: 2025-12-29
**対象**: 韓国側フロントエンド開発チーム

---

## 📋 概要

このAPIは、NET8システムに接続されている**物理的な機械（パチンコ・スロット台）**のリストと稼働状態を取得します。

### 機種（Model）と機械（Machine）の違い

| 項目 | 機種（Model） | 機械（Machine） |
|------|-------------|---------------|
| 説明 | ゲームタイトル・型番 | 実際の物理的な台 |
| 例 | 「北斗の拳 初号機」 | 「1番台」「2番台」など |
| API | `/api/v1/models.php` | `/api/v1/list_machines.php` ★ |
| 数 | 約10-50機種 | 68台（実際の台数） |

**例**: 「北斗の拳 初号機」という機種が10台あれば、機械は10台存在します。

---

## 🚀 エンドポイント

```
GET /api/v1/list_machines.php
```

**ベースURL**:
```
https://mgg-webservice-production.up.railway.app
```

---

## 📝 リクエスト

### ヘッダー

```http
GET /api/v1/list_machines.php
Authorization: Bearer YOUR_API_KEY
```

### クエリパラメータ

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|-----|----------|------|
| `modelId` | string | ❌ | - | 特定機種の台のみ取得 |
| `status` | string | ❌ | - | ステータスでフィルタ<br>`available`, `playing`, `maintenance`, `inactive` |
| `availableOnly` | boolean | ❌ | `false` | `true`: 利用可能な台のみ |
| `limit` | integer | ❌ | `100` | 取得件数（最大1000） |
| `offset` | integer | ❌ | `0` | オフセット（ページネーション） |
| `lang` | string | ❌ | `ja` | 言語設定<br>`ja`, `ko`, `en`, `zh` |

---

## 📤 レスポンス

### 成功レスポンス (200 OK)

```json
{
  "success": true,
  "total": 68,
  "available": 45,
  "playing": 3,
  "count": 68,
  "limit": 100,
  "offset": 0,
  "hasMore": false,
  "language": "ja",
  "machines": [
    {
      "machineNo": 1,
      "modelNo": 10,
      "modelId": "HOKUTO4GO",
      "modelName": "北斗の拳 初号機",
      "maker": "サミー",
      "category": "slot",
      "status": "available",
      "isAvailable": true,
      "camera": {
        "cameraNo": 1,
        "peerId": "camera_10000021_1765859502",
        "mac": "00:11:22:33:44:55",
        "status": 1
      },
      "currentUser": null,
      "stats": {
        "totalGames": 1234,
        "lastPlayedAt": "2025-12-28 15:30:00"
      }
    },
    {
      "machineNo": 2,
      "modelNo": 15,
      "modelId": "ZENIGATA01",
      "modelName": "주역은 제니가타",
      "maker": "平和",
      "category": "pachinko",
      "status": "playing",
      "isAvailable": false,
      "camera": {
        "cameraNo": 2,
        "peerId": "camera_10000022_1765859503",
        "mac": "00:11:22:33:44:66",
        "status": 1
      },
      "currentUser": {
        "memberNo": 12345,
        "assignedAt": "2025-12-29 14:00:00"
      },
      "stats": {
        "totalGames": 567,
        "lastPlayedAt": "2025-12-29 14:00:00"
      }
    }
  ]
}
```

### レスポンスフィールド説明

#### ルートレベル

| フィールド | 型 | 説明 |
|----------|---|------|
| `success` | boolean | 成功フラグ |
| `total` | integer | 総台数 |
| `available` | integer | 利用可能な台数 |
| `playing` | integer | プレイ中の台数 |
| `count` | integer | 返却された台数 |
| `limit` | integer | リクエストされた取得件数 |
| `offset` | integer | オフセット |
| `hasMore` | boolean | 次のページがあるか |
| `language` | string | 言語設定 |
| `machines` | array | 機械リスト |

#### machines オブジェクト

| フィールド | 型 | 説明 |
|----------|---|------|
| `machineNo` | integer | 台番号（一意） |
| `modelNo` | integer | 機種番号（内部ID） |
| `modelId` | string | 機種ID（例: `HOKUTO4GO`） |
| `modelName` | string | 機種名（多言語対応） |
| `maker` | string | メーカー名 |
| `category` | string | カテゴリ<br>`slot`, `pachinko`, `unknown` |
| `status` | string | 台のステータス<br>`available`, `playing`, `maintenance`, `inactive` |
| `isAvailable` | boolean | 利用可能かどうか |
| `camera` | object | カメラ情報 |
| `currentUser` | object/null | 現在のユーザー（プレイ中の場合） |
| `stats` | object | 統計情報 |

#### camera オブジェクト

| フィールド | 型 | 説明 |
|----------|---|------|
| `cameraNo` | integer | カメラ番号 |
| `peerId` | string | WebRTC Peer ID |
| `mac` | string | カメラMACアドレス |
| `status` | integer | カメラステータス（0: 無効, 1: 有効） |

#### currentUser オブジェクト（プレイ中の場合のみ）

| フィールド | 型 | 説明 |
|----------|---|------|
| `memberNo` | integer | プレイ中のユーザーID |
| `assignedAt` | string | 割り当て開始時刻 |

#### stats オブジェクト

| フィールド | 型 | 説明 |
|----------|---|------|
| `totalGames` | integer | 総プレイ回数 |
| `lastPlayedAt` | string | 最終プレイ時刻 |

---

## 🔍 使用例

### 例1: 全台リスト取得

```bash
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**用途**: システム全体の台の状況を確認

---

### 例2: 利用可能な台のみ取得

```bash
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?availableOnly=true" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**用途**: ユーザーに選択可能な台を表示

**レスポンス例**:
```json
{
  "success": true,
  "total": 45,
  "available": 45,
  "playing": 0,
  "machines": [
    { "machineNo": 1, "status": "available", "isAvailable": true, ... },
    { "machineNo": 3, "status": "available", "isAvailable": true, ... }
  ]
}
```

---

### 例3: 特定機種の台を取得

```bash
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?modelId=HOKUTO4GO" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**用途**: 「北斗の拳」を選んだユーザーに、利用可能な台番号を表示

**レスポンス例**:
```json
{
  "success": true,
  "total": 10,
  "available": 7,
  "playing": 3,
  "machines": [
    { "machineNo": 1, "modelId": "HOKUTO4GO", "status": "available", ... },
    { "machineNo": 2, "modelId": "HOKUTO4GO", "status": "playing", ... },
    { "machineNo": 5, "modelId": "HOKUTO4GO", "status": "available", ... }
  ]
}
```

---

### 例4: 韓国語で台名を取得

```bash
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?lang=ko&availableOnly=true" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**レスポンス例**:
```json
{
  "success": true,
  "language": "ko",
  "machines": [
    {
      "machineNo": 1,
      "modelName": "북두의 권 초호기",
      "status": "available",
      ...
    }
  ]
}
```

---

### 例5: ページネーション

```bash
# 1ページ目（1-20台）
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?limit=20&offset=0" \
  -H "Authorization: Bearer YOUR_API_KEY"

# 2ページ目（21-40台）
curl -X GET "https://mgg-webservice-production.up.railway.app/api/v1/list_machines.php?limit=20&offset=20" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

## 🎨 フロントエンド実装例

### React/Next.js での実装

```typescript
// hooks/useMachines.ts
import { useState, useEffect } from 'react';

interface Machine {
  machineNo: number;
  modelId: string;
  modelName: string;
  category: 'slot' | 'pachinko';
  status: 'available' | 'playing' | 'maintenance' | 'inactive';
  isAvailable: boolean;
  camera: {
    cameraNo: number;
    peerId: string;
  };
}

interface MachinesResponse {
  success: boolean;
  total: number;
  available: number;
  playing: number;
  machines: Machine[];
}

export function useMachines(modelId?: string, availableOnly: boolean = false) {
  const [machines, setMachines] = useState<Machine[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchMachines() {
      try {
        setLoading(true);

        const params = new URLSearchParams();
        if (modelId) params.append('modelId', modelId);
        if (availableOnly) params.append('availableOnly', 'true');
        params.append('lang', 'ko'); // 韓国語

        const response = await fetch(
          `/api/machines?${params.toString()}`,
          {
            headers: {
              'Authorization': `Bearer ${process.env.NEXT_PUBLIC_NET8_API_KEY}`
            }
          }
        );

        if (!response.ok) {
          throw new Error('Failed to fetch machines');
        }

        const data: MachinesResponse = await response.json();
        setMachines(data.machines);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Unknown error');
      } finally {
        setLoading(false);
      }
    }

    fetchMachines();
  }, [modelId, availableOnly]);

  return { machines, loading, error };
}
```

### コンポーネント例

```typescript
// components/MachineSelector.tsx
import { useMachines } from '@/hooks/useMachines';

export function MachineSelector({ modelId }: { modelId: string }) {
  const { machines, loading, error } = useMachines(modelId, true);

  if (loading) return <div>로딩 중...</div>;
  if (error) return <div>오류: {error}</div>;

  return (
    <div className="grid grid-cols-4 gap-4">
      {machines.map((machine) => (
        <div
          key={machine.machineNo}
          className={`
            p-4 border rounded-lg cursor-pointer
            ${machine.isAvailable ? 'border-green-500 bg-green-50' : 'border-gray-300 bg-gray-100'}
          `}
        >
          <div className="text-2xl font-bold">{machine.machineNo}번 台</div>
          <div className="text-sm text-gray-600">{machine.modelName}</div>
          <div className={`
            mt-2 px-2 py-1 rounded text-xs font-bold
            ${machine.status === 'available' ? 'bg-green-500 text-white' : 'bg-gray-400 text-white'}
          `}>
            {machine.status === 'available' ? '이용 가능' : '사용 중'}
          </div>
        </div>
      ))}

      {machines.length === 0 && (
        <div className="col-span-4 text-center text-gray-500 py-8">
          이용 가능한 기계가 없습니다
        </div>
      )}
    </div>
  );
}
```

---

## 🔄 ステータス管理フロー

### ステータスの種類

| ステータス | 説明 | 条件 |
|-----------|------|------|
| `available` | 利用可能 | `status=1` かつ `assign_flg=0` |
| `playing` | プレイ中 | `status=1` かつ `assign_flg=1` |
| `maintenance` | メンテナンス中 | `status=2` |
| `inactive` | 非稼働 | `status=0` |

### ステータス遷移図

```
┌──────────────┐
│   inactive   │  台を停止
│  (status=0)  │
└──────────────┘
       │
       │ 台を有効化
       ▼
┌──────────────┐     ユーザーが    ┌──────────────┐
│  available   │ ──────────────▶  │   playing    │
│  (status=1)  │   ゲーム開始      │ (assign_flg=1)│
│(assign_flg=0)│ ◀────────────── │              │
└──────────────┘   ゲーム終了      └──────────────┘
       │
       │ メンテナンス開始
       ▼
┌──────────────┐
│ maintenance  │  修理・点検
│  (status=2)  │
└──────────────┘
```

---

## ⚠️ エラーレスポンス

### 401 Unauthorized

```json
{
  "error": "UNAUTHORIZED",
  "message": "Authorization header is required"
}
```

**原因**: APIキーが送信されていない

---

### 500 Database Error

```json
{
  "error": "DATABASE_ERROR",
  "message": "Database query failed"
}
```

**原因**: データベース接続エラー

---

## 💡 活用例

### 1. 台選択画面

```typescript
// ユーザーが「北斗の拳」を選択
const { machines } = useMachines('HOKUTO4GO', true);

// 利用可能な台番号を表示
// 例: 「1番台」「3番台」「7番台」が選択可能
```

### 2. リアルタイム空き台表示

```typescript
// 10秒ごとに自動更新
setInterval(async () => {
  const response = await fetch('/api/machines?availableOnly=true');
  const data = await response.json();
  updateAvailableMachines(data.machines);
}, 10000);
```

### 3. 管理画面

```typescript
// 全台の稼働状況を管理画面で表示
const { machines } = useMachines();

// ステータス別に集計
const stats = {
  available: machines.filter(m => m.status === 'available').length,
  playing: machines.filter(m => m.status === 'playing').length,
  maintenance: machines.filter(m => m.status === 'maintenance').length
};
```

---

## 🔗 関連API

| API | 用途 |
|-----|------|
| `/api/v1/models.php` | 機種マスタ一覧取得 |
| `/api/v1/list_machines.php` | **台リスト取得（本API）** |
| `/api/v1/game_start.php` | ゲーム開始（台を割り当て） |
| `/api/v1/game_end.php` | ゲーム終了（台を解放） |

---

## 📝 まとめ

### このAPIでできること

✅ 全台リストの取得
✅ 利用可能な台のみフィルタ
✅ 特定機種の台のみ取得
✅ 多言語対応（ja/ko/en/zh）
✅ リアルタイムステータス確認
✅ カメラ情報の取得

### 推奨される使い方

1. **機種選択後**: `?modelId=HOKUTO4GO&availableOnly=true` で利用可能な台を表示
2. **ダッシュボード**: 全台の稼働状況をリアルタイム表示
3. **台選択UI**: ユーザーに空いている台番号を選ばせる

---

**NET8 機械リストAPI仕様書 v1.0.0**
© 2025 NET8 Development Team
最終更新: 2025-12-29
