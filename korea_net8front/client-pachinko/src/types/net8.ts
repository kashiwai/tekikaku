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

export interface GamePlaybackMethods {
    isMock: boolean;
    streamUrl?: string;
    playUrl?: string;
    signaling?: any;
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

// 統合認証用の型定義
export interface NET8User {
    userId: string;
    balance: number;
    playHistory: GameHistoryEntry[];
    createdAt: string;
    lastLogin: string;
}

export interface GameHistoryEntry {
    id: string;
    sessionId: string;
    modelId: string;
    modelName: string;
    result: 'win' | 'lose' | 'draw';
    pointsConsumed: number;
    pointsWon: number;
    netProfit: number;
    playDuration: number;
    playedAt: string;
}

export interface UserRegistrationRequest {
    userId: string;
    email?: string;
    displayName?: string;
    initialPoints?: number;
}

export interface UserBalanceResponse {
    userId: string;
    balance: number;
    lastUpdated: string;
}