# NET8 SDK 実装サンプル集

**バージョン**: v1.1.0
**最終更新**: 2025-11-23

このドキュメントでは、様々な言語・フレームワークでのNET8 SDK実装例を提供します。

---

## 📋 目次

1. [JavaScript/Node.js](#javascriptnodejs)
2. [TypeScript](#typescript)
3. [React](#react)
4. [Next.js](#nextjs)
5. [PHP](#php)
6. [Python](#python)
7. [Ruby](#ruby)
8. [Java](#java)

---

## JavaScript/Node.js

### 基本実装

```javascript
// net8-client.js
const axios = require('axios');

class NET8Client {
  constructor(apiKey, baseUrl = 'https://mgg-webservice-production.up.railway.app') {
    this.apiKey = apiKey;
    this.baseUrl = baseUrl;
    this.client = axios.create({
      baseURL: baseUrl,
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      },
      timeout: 30000
    });
  }

  async startGame(userId, modelId) {
    try {
      const { data } = await this.client.post('/api/v1/game_start.php', {
        userId,
        modelId
      });
      return data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  async endGame(sessionId, result, pointsWon) {
    try {
      const { data } = await this.client.post('/api/v1/game_end.php', {
        sessionId,
        result,
        pointsWon
      });
      return data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  async addPoints(userId, amount, reason = null) {
    try {
      const { data } = await this.client.post('/api/v1/add_points.php', {
        userId,
        amount,
        reason
      });
      return data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  async getPlayHistory(userId, limit = 10, offset = 0) {
    try {
      const { data } = await this.client.get('/api/v1/play_history.php', {
        params: { userId, limit, offset }
      });
      return data;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  handleError(error) {
    if (error.response) {
      return new Error(
        error.response.data.message ||
        `HTTP ${error.response.status}: ${error.response.statusText}`
      );
    }
    return error;
  }
}

module.exports = NET8Client;
```

### 使用例

```javascript
// app.js
require('dotenv').config();
const NET8Client = require('./net8-client');

const client = new NET8Client(process.env.NET8_API_KEY);

async function playGame() {
  try {
    // ゲーム開始
    console.log('ゲーム開始...');
    const gameStart = await client.startGame('user_123', 'HOKUTO4GO');
    console.log('セッション作成:', gameStart.sessionId);
    console.log('消費ポイント:', gameStart.pointsConsumed);
    console.log('残高:', gameStart.points.balance);

    // ゲームプレイ（実際のゲームロジック）
    console.log('\nゲームプレイ中...');
    await new Promise(resolve => setTimeout(resolve, 5000));

    // ゲーム終了
    console.log('\nゲーム終了...');
    const gameEnd = await client.endGame(gameStart.sessionId, 'win', 500);
    console.log('結果:', gameEnd.result);
    console.log('獲得ポイント:', gameEnd.pointsWon);
    console.log('純利益:', gameEnd.netProfit);
    console.log('新しい残高:', gameEnd.newBalance);

  } catch (error) {
    console.error('エラー:', error.message);
  }
}

playGame();
```

---

## TypeScript

### 型定義

```typescript
// types/net8.ts
export interface GameStartRequest {
  userId: string;
  modelId: string;
}

export interface GameStartResponse {
  success: boolean;
  environment: 'test' | 'production';
  sessionId: string;
  machineNo: number;
  model: {
    id: string;
    name: string;
    category: 'pachinko' | 'slot';
  };
  signaling: {
    signalingId: string;
    host: string;
    port: number;
    secure: boolean;
    path: string;
    iceServers: Array<{ urls: string }>;
    mock?: boolean;
  };
  camera: {
    cameraNo: number;
    streamUrl: string;
    mock?: boolean;
  };
  playUrl: string;
  mock?: boolean;
  points: {
    consumed: number;
    balance: string;
    balanceBefore: number;
  };
  pointsConsumed: number;
}

export interface GameEndRequest {
  sessionId: string;
  result: 'win' | 'lose' | 'draw';
  pointsWon: number;
}

export interface GameEndResponse {
  success: boolean;
  sessionId: string;
  result: 'win' | 'lose' | 'draw';
  pointsConsumed: string;
  pointsWon: number;
  netProfit: number;
  playDuration: number;
  endedAt: string;
  newBalance: number;
  transaction: {
    id: string;
    amount: number;
    balanceBefore: string;
    balanceAfter: number;
  };
}

export interface NET8Error {
  error: string;
  message: string;
  details?: any;
}
```

### クライアント実装

```typescript
// services/net8.service.ts
import axios, { AxiosInstance, AxiosError } from 'axios';
import type {
  GameStartRequest,
  GameStartResponse,
  GameEndRequest,
  GameEndResponse,
  NET8Error
} from '../types/net8';

export class NET8Service {
  private client: AxiosInstance;

  constructor(apiKey: string, baseUrl: string) {
    this.client = axios.create({
      baseURL: baseUrl,
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      },
      timeout: 30000
    });

    // リクエストロギング
    this.client.interceptors.request.use(config => {
      console.log(`[NET8] ${config.method?.toUpperCase()} ${config.url}`);
      return config;
    });

    // エラーハンドリング
    this.client.interceptors.response.use(
      response => response,
      (error: AxiosError<NET8Error>) => {
        const errorMessage = error.response?.data?.message || error.message;
        console.error('[NET8] Error:', errorMessage);
        return Promise.reject(error);
      }
    );
  }

  async startGame(request: GameStartRequest): Promise<GameStartResponse> {
    const { data } = await this.client.post<GameStartResponse>(
      '/api/v1/game_start.php',
      request
    );
    return data;
  }

  async endGame(request: GameEndRequest): Promise<GameEndResponse> {
    const { data } = await this.client.post<GameEndResponse>(
      '/api/v1/game_end.php',
      request
    );
    return data;
  }

  async addPoints(
    userId: string,
    amount: number,
    reason?: string
  ): Promise<any> {
    const { data } = await this.client.post('/api/v1/add_points.php', {
      userId,
      amount,
      reason
    });
    return data;
  }
}
```

### 使用例（TypeScript）

```typescript
// app.ts
import { NET8Service } from './services/net8.service';

const net8 = new NET8Service(
  process.env.NET8_API_KEY!,
  process.env.NET8_API_BASE!
);

async function main() {
  try {
    // ゲーム開始
    const gameStart = await net8.startGame({
      userId: 'user_123',
      modelId: 'HOKUTO4GO'
    });

    console.log('Session ID:', gameStart.sessionId);
    console.log('Points consumed:', gameStart.pointsConsumed);

    // ゲーム終了
    const gameEnd = await net8.endGame({
      sessionId: gameStart.sessionId,
      result: 'win',
      pointsWon: 500
    });

    console.log('Net profit:', gameEnd.netProfit);
    console.log('New balance:', gameEnd.newBalance);

  } catch (error: any) {
    console.error('Error:', error.response?.data || error.message);
  }
}

main();
```

---

## React

### カスタムフック

```typescript
// hooks/useNET8Game.ts
import { useState, useCallback } from 'react';
import { NET8Service } from '../services/net8.service';
import type { GameStartResponse, GameEndResponse } from '../types/net8';

const net8 = new NET8Service(
  process.env.REACT_APP_NET8_API_KEY!,
  process.env.REACT_APP_NET8_API_BASE!
);

export function useNET8Game() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [session, setSession] = useState<GameStartResponse | null>(null);

  const startGame = useCallback(async (userId: string, modelId: string) => {
    setLoading(true);
    setError(null);

    try {
      const result = await net8.startGame({ userId, modelId });
      setSession(result);
      return result;
    } catch (err: any) {
      const message = err.response?.data?.message || 'ゲーム開始に失敗しました';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const endGame = useCallback(async (
    result: 'win' | 'lose' | 'draw',
    pointsWon: number
  ) => {
    if (!session) {
      throw new Error('アクティブなセッションがありません');
    }

    setLoading(true);
    setError(null);

    try {
      const gameResult = await net8.endGame({
        sessionId: session.sessionId,
        result,
        pointsWon
      });
      setSession(null);
      return gameResult;
    } catch (err: any) {
      const message = err.response?.data?.message || 'ゲーム終了に失敗しました';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [session]);

  return {
    loading,
    error,
    session,
    startGame,
    endGame
  };
}
```

### コンポーネント

```typescript
// components/GameScreen.tsx
import React, { useState } from 'react';
import { useNET8Game } from '../hooks/useNET8Game';

interface GameScreenProps {
  userId: string;
  modelId: string;
}

export const GameScreen: React.FC<GameScreenProps> = ({ userId, modelId }) => {
  const { loading, error, session, startGame, endGame } = useNET8Game();
  const [gameResult, setGameResult] = useState<any>(null);

  const handleStartGame = async () => {
    try {
      await startGame(userId, modelId);
    } catch (error) {
      console.error('ゲーム開始エラー:', error);
    }
  };

  const handleEndGame = async (result: 'win' | 'lose', points: number) => {
    try {
      const endResult = await endGame(result, points);
      setGameResult(endResult);
    } catch (error) {
      console.error('ゲーム終了エラー:', error);
    }
  };

  if (loading) {
    return <div className="loading">読み込み中...</div>;
  }

  if (error) {
    return <div className="error">{error}</div>;
  }

  if (!session) {
    return (
      <div className="game-start">
        <h2>{modelId}で遊ぶ</h2>
        <button onClick={handleStartGame}>ゲーム開始</button>
      </div>
    );
  }

  if (gameResult) {
    return (
      <div className="game-result">
        <h2>ゲーム結果</h2>
        <p>結果: {gameResult.result === 'win' ? '勝利' : '敗北'}</p>
        <p>獲得ポイント: {gameResult.pointsWon}</p>
        <p>純利益: {gameResult.netProfit}</p>
        <p>新しい残高: {gameResult.newBalance}</p>
        <button onClick={() => setGameResult(null)}>もう一度遊ぶ</button>
      </div>
    );
  }

  return (
    <div className="game-playing">
      <h2>プレイ中...</h2>
      <p>セッションID: {session.sessionId}</p>
      <p>消費ポイント: {session.pointsConsumed}</p>
      <p>残高: {session.points.balance}</p>

      <div className="game-controls">
        <button onClick={() => handleEndGame('win', 500)}>
          勝利で終了 (+500ポイント)
        </button>
        <button onClick={() => handleEndGame('lose', 0)}>
          敗北で終了
        </button>
      </div>
    </div>
  );
};
```

---

## Next.js

### API Routes実装

```typescript
// pages/api/game/start.ts
import type { NextApiRequest, NextApiResponse } from 'next';
import { NET8Service } from '../../../lib/net8.service';

const net8 = new NET8Service(
  process.env.NET8_API_KEY!,
  process.env.NET8_API_BASE!
);

export default async function handler(
  req: NextApiRequest,
  res: NextApiResponse
) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const { userId, modelId } = req.body;

    if (!userId || !modelId) {
      return res.status(400).json({
        error: 'Missing required parameters'
      });
    }

    const result = await net8.startGame({ userId, modelId });
    return res.status(200).json(result);

  } catch (error: any) {
    console.error('Game start error:', error);
    return res.status(error.response?.status || 500).json({
      error: error.response?.data || error.message
    });
  }
}
```

```typescript
// pages/api/game/end.ts
import type { NextApiRequest, NextApiResponse } from 'next';
import { NET8Service } from '../../../lib/net8.service';

const net8 = new NET8Service(
  process.env.NET8_API_KEY!,
  process.env.NET8_API_BASE!
);

export default async function handler(
  req: NextApiRequest,
  res: NextApiResponse
) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const { sessionId, result, pointsWon } = req.body;

    if (!sessionId || !result || pointsWon === undefined) {
      return res.status(400).json({
        error: 'Missing required parameters'
      });
    }

    const gameResult = await net8.endGame({ sessionId, result, pointsWon });
    return res.status(200).json(gameResult);

  } catch (error: any) {
    console.error('Game end error:', error);
    return res.status(error.response?.status || 500).json({
      error: error.response?.data || error.message
    });
  }
}
```

### クライアント側実装

```typescript
// lib/api-client.ts
export async function startGame(userId: string, modelId: string) {
  const response = await fetch('/api/game/start', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ userId, modelId })
  });

  if (!response.ok) {
    throw new Error('Game start failed');
  }

  return await response.json();
}

export async function endGame(
  sessionId: string,
  result: 'win' | 'lose' | 'draw',
  pointsWon: number
) {
  const response = await fetch('/api/game/end', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ sessionId, result, pointsWon })
  });

  if (!response.ok) {
    throw new Error('Game end failed');
  }

  return await response.json();
}
```

---

## PHP

```php
<?php
// NET8Client.php

class NET8Client {
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $baseUrl = 'https://mgg-webservice-production.up.railway.app') {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function startGame($userId, $modelId) {
        return $this->request('POST', '/api/v1/game_start.php', [
            'userId' => $userId,
            'modelId' => $modelId
        ]);
    }

    public function endGame($sessionId, $result, $pointsWon) {
        return $this->request('POST', '/api/v1/game_end.php', [
            'sessionId' => $sessionId,
            'result' => $result,
            'pointsWon' => $pointsWon
        ]);
    }

    public function addPoints($userId, $amount, $reason = null) {
        $data = [
            'userId' => $userId,
            'amount' => $amount
        ];
        if ($reason) {
            $data['reason'] = $reason;
        }
        return $this->request('POST', '/api/v1/add_points.php', $data);
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode - $response");
        }

        return json_decode($response, true);
    }
}

// 使用例
$client = new NET8Client(getenv('NET8_API_KEY'));

try {
    // ゲーム開始
    $gameStart = $client->startGame('user_123', 'HOKUTO4GO');
    echo "セッション作成: " . $gameStart['sessionId'] . "\n";

    // ゲーム終了
    $gameEnd = $client->endGame($gameStart['sessionId'], 'win', 500);
    echo "獲得ポイント: " . $gameEnd['pointsWon'] . "\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>
```

---

## Python

```python
# net8_client.py
import requests
from typing import Dict, Optional

class NET8Client:
    def __init__(self, api_key: str, base_url: str = 'https://mgg-webservice-production.up.railway.app'):
        self.api_key = api_key
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json'
        }

    def start_game(self, user_id: str, model_id: str) -> Dict:
        """ゲームを開始する"""
        response = requests.post(
            f'{self.base_url}/api/v1/game_start.php',
            json={'userId': user_id, 'modelId': model_id},
            headers=self.headers,
            timeout=30
        )
        response.raise_for_status()
        return response.json()

    def end_game(self, session_id: str, result: str, points_won: int) -> Dict:
        """ゲームを終了する"""
        response = requests.post(
            f'{self.base_url}/api/v1/game_end.php',
            json={
                'sessionId': session_id,
                'result': result,
                'pointsWon': points_won
            },
            headers=self.headers,
            timeout=30
        )
        response.raise_for_status()
        return response.json()

    def add_points(self, user_id: str, amount: int, reason: Optional[str] = None) -> Dict:
        """ポイントを追加する"""
        data = {'userId': user_id, 'amount': amount}
        if reason:
            data['reason'] = reason

        response = requests.post(
            f'{self.base_url}/api/v1/add_points.php',
            json=data,
            headers=self.headers,
            timeout=30
        )
        response.raise_for_status()
        return response.json()

# 使用例
if __name__ == '__main__':
    import os

    client = NET8Client(os.getenv('NET8_API_KEY'))

    try:
        # ゲーム開始
        game_start = client.start_game('user_123', 'HOKUTO4GO')
        print(f"セッション作成: {game_start['sessionId']}")
        print(f"消費ポイント: {game_start['pointsConsumed']}")

        # ゲーム終了
        game_end = client.end_game(game_start['sessionId'], 'win', 500)
        print(f"獲得ポイント: {game_end['pointsWon']}")
        print(f"純利益: {game_end['netProfit']}")

    except requests.exceptions.HTTPError as e:
        print(f"HTTPエラー: {e.response.status_code} - {e.response.text}")
    except Exception as e:
        print(f"エラー: {str(e)}")
```

---

## Ruby

```ruby
# net8_client.rb
require 'net/http'
require 'json'

class NET8Client
  def initialize(api_key, base_url = 'https://mgg-webservice-production.up.railway.app')
    @api_key = api_key
    @base_url = URI(base_url)
  end

  def start_game(user_id, model_id)
    request('POST', '/api/v1/game_start.php', {
      userId: user_id,
      modelId: model_id
    })
  end

  def end_game(session_id, result, points_won)
    request('POST', '/api/v1/game_end.php', {
      sessionId: session_id,
      result: result,
      pointsWon: points_won
    })
  end

  def add_points(user_id, amount, reason = nil)
    data = {
      userId: user_id,
      amount: amount
    }
    data[:reason] = reason if reason
    request('POST', '/api/v1/add_points.php', data)
  end

  private

  def request(method, path, body = nil)
    uri = URI.join(@base_url, path)

    http = Net::HTTP.new(uri.host, uri.port)
    http.use_ssl = true
    http.read_timeout = 30

    request = case method
              when 'POST' then Net::HTTP::Post.new(uri)
              when 'GET' then Net::HTTP::Get.new(uri)
              end

    request['Authorization'] = "Bearer #{@api_key}"
    request['Content-Type'] = 'application/json'
    request.body = body.to_json if body

    response = http.request(request)

    unless response.is_a?(Net::HTTPSuccess)
      raise "HTTP Error: #{response.code} - #{response.body}"
    end

    JSON.parse(response.body)
  end
end

# 使用例
client = NET8Client.new(ENV['NET8_API_KEY'])

begin
  # ゲーム開始
  game_start = client.start_game('user_123', 'HOKUTO4GO')
  puts "セッション作成: #{game_start['sessionId']}"

  # ゲーム終了
  game_end = client.end_game(game_start['sessionId'], 'win', 500)
  puts "獲得ポイント: #{game_end['pointsWon']}"

rescue => e
  puts "エラー: #{e.message}"
end
```

---

## Java

```java
// NET8Client.java
import com.google.gson.Gson;
import com.google.gson.JsonObject;
import okhttp3.*;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

public class NET8Client {
    private final String apiKey;
    private final String baseUrl;
    private final OkHttpClient httpClient;
    private final Gson gson;

    public NET8Client(String apiKey) {
        this(apiKey, "https://mgg-webservice-production.up.railway.app");
    }

    public NET8Client(String apiKey, String baseUrl) {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl;
        this.gson = new Gson();
        this.httpClient = new OkHttpClient.Builder()
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .build();
    }

    public JsonObject startGame(String userId, String modelId) throws IOException {
        JsonObject body = new JsonObject();
        body.addProperty("userId", userId);
        body.addProperty("modelId", modelId);

        return post("/api/v1/game_start.php", body);
    }

    public JsonObject endGame(String sessionId, String result, int pointsWon) throws IOException {
        JsonObject body = new JsonObject();
        body.addProperty("sessionId", sessionId);
        body.addProperty("result", result);
        body.addProperty("pointsWon", pointsWon);

        return post("/api/v1/game_end.php", body);
    }

    private JsonObject post(String endpoint, JsonObject body) throws IOException {
        RequestBody requestBody = RequestBody.create(
                gson.toJson(body),
                MediaType.parse("application/json")
        );

        Request request = new Request.Builder()
                .url(baseUrl + endpoint)
                .header("Authorization", "Bearer " + apiKey)
                .post(requestBody)
                .build();

        try (Response response = httpClient.newCall(request).execute()) {
            if (!response.isSuccessful()) {
                throw new IOException("HTTP " + response.code() + ": " + response.body().string());
            }
            return gson.fromJson(response.body().string(), JsonObject.class);
        }
    }
}

// 使用例
public class Main {
    public static void main(String[] args) {
        NET8Client client = new NET8Client(System.getenv("NET8_API_KEY"));

        try {
            // ゲーム開始
            JsonObject gameStart = client.startGame("user_123", "HOKUTO4GO");
            System.out.println("セッション作成: " + gameStart.get("sessionId").getAsString());

            // ゲーム終了
            JsonObject gameEnd = client.endGame(
                    gameStart.get("sessionId").getAsString(),
                    "win",
                    500
            );
            System.out.println("獲得ポイント: " + gameEnd.get("pointsWon").getAsInt());

        } catch (IOException e) {
            System.err.println("エラー: " + e.getMessage());
        }
    }
}
```

---

## まとめ

このドキュメントでは、主要なプログラミング言語でのNET8 SDK実装例を提供しました。

### 次のステップ

1. **本番環境への移行**: [実装マニュアル](NET8_SDK_IMPLEMENTATION_GUIDE.md)を参照
2. **API詳細**: [APIリファレンス](NET8_SDK_API_REFERENCE.md)を確認
3. **トラブルシューティング**: [トラブルシューティングガイド](NET8_SDK_TROUBLESHOOTING.md)を参照

---

**© 2025 NET8. All rights reserved.**
