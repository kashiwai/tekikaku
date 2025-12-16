/**
 * NET8 SDK サービス
 * サーバーサイド専用
 */

import { NET8_CONFIG, NET8_ENDPOINTS } from './net8.config';
import type {
  GameStartRequest,
  GameStartResponse,
  GameEndRequest,
  GameEndResponse,
  AddPointsRequest,
  AddPointsResponse,
  PlayHistoryResponse,
  RecommendedModelsResponse,
  Net8ErrorResponse,
  Net8ErrorCode,
} from './net8.types';

class Net8ServiceError extends Error {
  constructor(
    public code: Net8ErrorCode,
    message: string,
    public details?: Record<string, unknown>
  ) {
    super(message);
    this.name = 'Net8ServiceError';
  }
}

class Net8Service {
  private baseUrl: string;
  private apiKey: string;
  private timeout: number;

  constructor() {
    this.baseUrl = NET8_CONFIG.apiBaseUrl;
    this.apiKey = NET8_CONFIG.apiKey;
    this.timeout = NET8_CONFIG.timeout;
  }

  /**
   * HTTPリクエスト共通処理
   */
  private async request<T>(
    endpoint: string,
    options: {
      method: 'GET' | 'POST';
      body?: Record<string, unknown>;
      params?: Record<string, string>;
    }
  ): Promise<T> {
    let url = `${this.baseUrl}${endpoint}`;

    // クエリパラメータを追加
    if (options.params) {
      const searchParams = new URLSearchParams(options.params);
      url += `?${searchParams.toString()}`;
    }

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.timeout);

    try {
      const response = await fetch(url, {
        method: options.method,
        headers: {
          Authorization: `Bearer ${this.apiKey}`,
          'Content-Type': 'application/json',
        },
        body: options.body ? JSON.stringify(options.body) : undefined,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      const data = await response.json();

      if (!response.ok) {
        const errorData = data as Net8ErrorResponse;
        throw new Net8ServiceError(
          errorData.error || 'INTERNAL_ERROR',
          errorData.message || 'Unknown error',
          errorData.details
        );
      }

      return data as T;
    } catch (error) {
      clearTimeout(timeoutId);

      if (error instanceof Net8ServiceError) {
        throw error;
      }

      if (error instanceof Error) {
        if (error.name === 'AbortError') {
          throw new Net8ServiceError('INTERNAL_ERROR', 'Request timeout');
        }
        throw new Net8ServiceError('INTERNAL_ERROR', error.message);
      }

      throw new Net8ServiceError('INTERNAL_ERROR', 'Unknown error occurred');
    }
  }

  /**
   * ゲーム開始
   * セッションを作成し、ポイントを消費する
   */
  async startGame(request: GameStartRequest): Promise<GameStartResponse> {
    console.log('[NET8] Starting game:', request);

    const response = await this.request<GameStartResponse>(
      NET8_ENDPOINTS.GAME_START,
      {
        method: 'POST',
        body: request as unknown as Record<string, unknown>,
      }
    );

    console.log('[NET8] Game started:', response.sessionId);
    return response;
  }

  /**
   * ゲーム終了
   * セッションを終了し、ポイントを払い出す
   */
  async endGame(request: GameEndRequest): Promise<GameEndResponse> {
    console.log('[NET8] Ending game:', request.sessionId);

    const response = await this.request<GameEndResponse>(
      NET8_ENDPOINTS.GAME_END,
      {
        method: 'POST',
        body: request as unknown as Record<string, unknown>,
      }
    );

    console.log('[NET8] Game ended:', {
      sessionId: response.sessionId,
      result: response.result,
      newBalance: response.newBalance,
    });

    return response;
  }

  /**
   * ポイント追加
   */
  async addPoints(request: AddPointsRequest): Promise<AddPointsResponse> {
    console.log('[NET8] Adding points:', request);

    const response = await this.request<AddPointsResponse>(
      NET8_ENDPOINTS.ADD_POINTS,
      {
        method: 'POST',
        body: request as unknown as Record<string, unknown>,
      }
    );

    console.log('[NET8] Points added:', {
      userId: response.userId,
      newBalance: response.balanceAfter,
    });

    return response;
  }

  /**
   * プレイ履歴取得
   */
  async getPlayHistory(
    userId: string,
    page: number = 1,
    limit: number = 10
  ): Promise<PlayHistoryResponse> {
    console.log('[NET8] Getting play history:', { userId, page, limit });

    const response = await this.request<PlayHistoryResponse>(
      NET8_ENDPOINTS.PLAY_HISTORY,
      {
        method: 'GET',
        params: {
          userId,
          page: page.toString(),
          limit: limit.toString(),
        },
      }
    );

    return response;
  }

  /**
   * 推奨機種取得
   */
  async getRecommendedModels(): Promise<RecommendedModelsResponse> {
    console.log('[NET8] Getting recommended models');

    const response = await this.request<RecommendedModelsResponse>(
      NET8_ENDPOINTS.RECOMMENDED_MODELS,
      {
        method: 'GET',
      }
    );

    return response;
  }

  /**
   * 接続テスト
   */
  async testConnection(): Promise<{
    success: boolean;
    message: string;
    environment?: string;
  }> {
    try {
      // 簡易テスト: 推奨機種APIを呼び出す
      const response = await this.getRecommendedModels();
      return {
        success: response.success,
        message: 'Connection successful',
        environment: NET8_CONFIG.apiKey.startsWith('pk_demo_')
          ? 'demo'
          : NET8_CONFIG.apiKey.startsWith('pk_test_')
            ? 'test'
            : 'production',
      };
    } catch (error) {
      if (error instanceof Net8ServiceError) {
        return {
          success: false,
          message: `Connection failed: ${error.message} (${error.code})`,
        };
      }
      return {
        success: false,
        message: 'Connection failed: Unknown error',
      };
    }
  }
}

// シングルトンインスタンス
export const net8Service = new Net8Service();

// エラークラスのエクスポート
export { Net8ServiceError };
