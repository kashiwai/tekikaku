/**
 * NET8 SDK 設定
 */

export const NET8_CONFIG = {
  // API設定
  apiKey: process.env.NET8_API_KEY || '',
  apiBaseUrl: process.env.NET8_API_BASE_URL || 'https://mgg-webservice-production.up.railway.app',

  // タイムアウト（ミリ秒）
  timeout: 30000,

  // リトライ設定
  maxRetries: 3,
  retryDelay: 1000,

  // WebRTC設定
  peerjs: {
    host: process.env.PEERJS_HOST || 'mgg-signaling-production-c1bd.up.railway.app',
    port: parseInt(process.env.PEERJS_PORT || '443'),
    secure: process.env.PEERJS_SECURE !== 'false',
    path: '/peerjs',
  },

  // ICEサーバー
  iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
    { urls: 'stun:stun2.l.google.com:19302' },
  ],
} as const;

// APIエンドポイント
export const NET8_ENDPOINTS = {
  GAME_START: '/api/v1/game_start.php',
  GAME_END: '/api/v1/game_end.php',
  ADD_POINTS: '/api/v1/add_points.php',
  PLAY_HISTORY: '/api/v1/play_history.php',
  RECOMMENDED_MODELS: '/api/v1/recommended_models.php',
} as const;

// 環境チェック
export function validateNet8Config(): { valid: boolean; errors: string[] } {
  const errors: string[] = [];

  if (!NET8_CONFIG.apiKey) {
    errors.push('NET8_API_KEY is not set');
  }

  if (!NET8_CONFIG.apiBaseUrl) {
    errors.push('NET8_API_BASE_URL is not set');
  }

  return {
    valid: errors.length === 0,
    errors,
  };
}
