// types/point-transaction.types.ts - ポイント取引の型定義
export type TransactionType = 
  | 'charge'        // チャージ（課金）
  | 'deduction'     // 減産（消費）
  | 'bonus'         // ボーナス付与
  | 'refund'        // 返金
  | 'adjustment'    // 調整
  | 'game_win'      // ゲーム勝利
  | 'game_loss';    // ゲーム敗北

export type TransactionStatus = 
  | 'pending'       // 処理中
  | 'completed'     // 完了
  | 'failed'        // 失敗
  | 'cancelled';    // キャンセル

export type PaymentMethod = 
  | 'credit_card'   // クレジットカード
  | 'bank_transfer' // 銀行振込
  | 'digital_wallet'// デジタルウォレット
  | 'crypto'        // 暗号通貨
  | 'admin'         // 管理者操作
  | 'system';       // システム自動

export interface PointTransaction {
  id: string;
  userId: string;
  type: TransactionType;
  amount: number;                    // 取引金額（正数：増加、負数：減少）
  balanceBefore: number;            // 取引前残高
  balanceAfter: number;             // 取引後残高
  status: TransactionStatus;
  paymentMethod?: PaymentMethod;
  description: string;              // 取引説明
  metadata?: {
    gameId?: string;               // ゲーム関連の場合
    gameSessionId?: string;        // ゲームセッションID
    modelId?: string;             // パチンコ・スロット機種ID
    orderId?: string;             // 注文ID（課金の場合）
    adminId?: string;             // 管理者操作の場合
    referenceId?: string;         // 参照ID
  };
  createdAt: string;
  updatedAt: string;
  processedAt?: string;            // 処理完了時刻
}

// チャージ（課金）リクエスト
export interface ChargePointRequest {
  userId: string;
  amount: number;
  paymentMethod: PaymentMethod;
  orderId?: string;
  description?: string;
}

// ポイント消費リクエスト
export interface DeductPointRequest {
  userId: string;
  amount: number;
  description: string;
  gameId?: string;
  gameSessionId?: string;
  modelId?: string;
}

// ポイント取引レスポンス
export interface PointTransactionResponse {
  success: boolean;
  transaction?: PointTransaction;
  error?: string;
  message: string;
  newBalance: number;
}

// 取引履歴クエリ
export interface TransactionHistoryQuery {
  userId: string;
  type?: TransactionType;
  status?: TransactionStatus;
  startDate?: string;
  endDate?: string;
  limit?: number;
  offset?: number;
}

// 取引履歴レスポンス
export interface TransactionHistoryResponse {
  success: boolean;
  transactions: PointTransaction[];
  total: number;
  hasMore: boolean;
  error?: string;
}