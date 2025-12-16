/**
 * NET8 SDK エクスポート
 */

// サービス
export { net8Service, Net8ServiceError } from './net8.service';

// 設定
export { NET8_CONFIG, NET8_ENDPOINTS, validateNet8Config } from './net8.config';

// 型定義
export type {
  // リクエスト/レスポンス
  GameStartRequest,
  GameStartResponse,
  GameEndRequest,
  GameEndResponse,
  AddPointsRequest,
  AddPointsResponse,
  PlayHistoryResponse,
  PlayHistoryItem,
  RecommendedModelsResponse,
  GameModel,
  // エラー
  Net8ErrorResponse,
  Net8ErrorCode,
  // WebRTC
  WebRTCConfig,
  // セッション
  GameSession,
} from './net8.types';
