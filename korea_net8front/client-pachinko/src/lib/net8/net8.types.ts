/**
 * NET8 SDK 型定義
 * @version 1.1.0
 */

// ゲーム開始リクエスト
export interface GameStartRequest {
  userId: string;
  modelId: string;
}

// ゲーム開始レスポンス
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
  signaling?: {
    signalingId: string;
    host: string;
    port: number;
    secure: boolean;
  };
  camera?: {
    cameraNo: number;
    streamUrl: string;
  };
  playUrl: string;
  points: {
    consumed: number;
    balance: string;
    balanceBefore: number;
  };
  pointsConsumed: number;
}

// ゲーム終了リクエスト
export interface GameEndRequest {
  sessionId: string;
  result: 'win' | 'lose' | 'draw';
  pointsWon: number;
}

// ゲーム終了レスポンス
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

// ポイント追加リクエスト
export interface AddPointsRequest {
  userId: string;
  amount: number;
  reason?: string;
}

// ポイント追加レスポンス
export interface AddPointsResponse {
  success: boolean;
  userId: string;
  amountAdded: number;
  balanceBefore: number;
  balanceAfter: number;
  transaction: {
    id: string;
    timestamp: string;
  };
}

// プレイ履歴レスポンス
export interface PlayHistoryResponse {
  success: boolean;
  history: PlayHistoryItem[];
  pagination: {
    currentPage: number;
    totalPages: number;
    totalRecords: number;
    recordsPerPage: number;
  };
}

export interface PlayHistoryItem {
  sessionId: string;
  modelId: string;
  modelName: string;
  result: 'win' | 'lose' | 'draw';
  pointsConsumed: number;
  pointsWon: number;
  netProfit: number;
  startTime: string;
  endTime: string;
  duration: number;
}

// 推奨機種レスポンス
export interface RecommendedModelsResponse {
  success: boolean;
  models: GameModel[];
}

export interface GameModel {
  id: string;
  name: string;
  category: 'pachinko' | 'slot';
  thumbnail?: string;
  description?: string;
  minPoints: number;
  popular: boolean;
}

// NET8エラーレスポンス
export interface Net8ErrorResponse {
  error: Net8ErrorCode;
  message: string;
  details?: Record<string, unknown>;
}

export type Net8ErrorCode =
  | 'INVALID_API_KEY'
  | 'INSUFFICIENT_BALANCE'
  | 'MODEL_NOT_FOUND'
  | 'SESSION_NOT_FOUND'
  | 'SESSION_ALREADY_ENDED'
  | 'TRANSACTION_FAILED'
  | 'RATE_LIMIT_EXCEEDED'
  | 'INTERNAL_ERROR';

// WebRTC設定
export interface WebRTCConfig {
  peerId: string;
  signalingServer: string;
  stunServers: string[];
  turnServers?: {
    urls: string;
    username?: string;
    credential?: string;
  }[];
}

// ゲームセッション状態
export interface GameSession {
  sessionId: string;
  userId: string;
  modelId: string;
  modelName: string;
  status: 'active' | 'ended' | 'error';
  pointsConsumed: number;
  startTime: Date;
  webrtc?: WebRTCConfig;
}
