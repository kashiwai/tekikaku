/**
 * NET8 Gaming SDK - Beta Version
 * Version: 1.1.0-beta
 * Updated: 2025-11-18 - Added userId support, point management, game end events
 */

(function(window) {
    'use strict';

    // SDK設定
    const SDK_VERSION = '1.1.0-beta';
    const DEFAULT_API_URL = 'https://mgg-webservice-production.up.railway.app';

    /**
     * Net8 メインクラス
     */
    class Net8SDK {
        constructor() {
            this.apiKey = null;
            this.apiUrl = DEFAULT_API_URL;
            this.token = null;
            this.initialized = false;
            this.isInitializing = false; // 初期化中フラグ
        }

        /**
         * SDK初期化
         */
        async init(apiKey, options = {}) {
            // 既に初期化済みの場合はスキップ
            if (this.initialized) {
                console.log('[Net8 SDK] Already initialized');
                return;
            }

            // 初期化中の場合はスキップ
            if (this.isInitializing) {
                console.log('[Net8 SDK] Initialization in progress...');
                return;
            }

            if (!apiKey || !apiKey.startsWith('pk_')) {
                throw new Error('Invalid API key format. Must start with "pk_"');
            }

            this.isInitializing = true;
            this.apiKey = apiKey;
            this.apiUrl = options.apiUrl || DEFAULT_API_URL;

            console.log(`[Net8 SDK v${SDK_VERSION}] Initializing...`);

            try {
                // 認証トークン取得
                const response = await fetch(`${this.apiUrl}/api/v1/auth.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ apiKey: this.apiKey })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Authentication failed');
                }

                const data = await response.json();
                this.token = data.token;
                this.initialized = true;

                console.log('[Net8 SDK] Initialized successfully');
            } catch (error) {
                console.error('[Net8 SDK] Initialization failed:', error);
                throw error;
            } finally {
                this.isInitializing = false;
            }
        }

        /**
         * 機種一覧を取得
         */
        async getModels() {
            this._checkInitialized();

            const response = await fetch(`${this.apiUrl}/api/v1/models.php`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch models');
            }

            const data = await response.json();
            return data.models;
        }

        /**
         * ゲームインスタンスを作成
         */
        createGame(config) {
            this._checkInitialized();
            return new Net8Game(config, this);
        }

        /**
         * 初期化チェック
         */
        _checkInitialized() {
            if (!this.initialized) {
                throw new Error('SDK not initialized. Call Net8.init() first.');
            }
        }

        /**
         * タイムアウト付きfetch
         * @private
         */
        async _fetchWithTimeout(url, options = {}, timeout = 30000) {
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await fetch(url, {
                    ...options,
                    signal: controller.signal
                });
                clearTimeout(id);
                return response;
            } catch (error) {
                clearTimeout(id);
                if (error.name === 'AbortError') {
                    throw new Error('Request timeout. Please check your connection.');
                }
                throw error;
            }
        }

        /**
         * リトライ付きAPI呼び出し
         * @private
         */
        async _apiCallWithRetry(url, options = {}, maxRetries = 3, timeout = 30000) {
            let lastError;

            for (let i = 0; i < maxRetries; i++) {
                try {
                    const response = await this._fetchWithTimeout(url, options, timeout);

                    // ネットワークエラーではなくAPI エラーの場合はリトライしない
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    }

                    return response;
                } catch (error) {
                    lastError = error;
                    console.warn(`[Net8 SDK] API call attempt ${i + 1}/${maxRetries} failed:`, error.message);

                    // 最後のリトライでない場合は待機
                    if (i < maxRetries - 1) {
                        await this._sleep(Math.pow(2, i) * 1000); // Exponential backoff
                    }
                }
            }

            throw new Error(`API call failed after ${maxRetries} attempts: ${lastError.message}`);
        }

        /**
         * スリープユーティリティ
         * @private
         */
        _sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        /**
         * エラーメッセージを整形
         * @private
         */
        _formatErrorMessage(error) {
            if (error.message) {
                return error.message;
            }

            // 標準的なエラーコードをユーザーフレンドリーなメッセージに変換
            const errorMessages = {
                'UNAUTHORIZED': '認証に失敗しました。APIキーを確認してください。',
                'INVALID_API_KEY': '無効なAPIキーです。',
                'MODEL_NOT_FOUND': '指定された機種が見つかりません。',
                'NO_AVAILABLE_MACHINE': '利用可能な台がありません。しばらくしてから再度お試しください。',
                'INSUFFICIENT_BALANCE': 'ポイント残高が不足しています。',
                'SESSION_NOT_FOUND': 'ゲームセッションが見つかりません。',
                'SESSION_ALREADY_ENDED': 'このゲームは既に終了しています。',
                'NETWORK_ERROR': 'ネットワークエラーが発生しました。接続を確認してください。'
            };

            return errorMessages[error.error] || error.toString();
        }
    }

    /**
     * Net8Game クラス
     */
    class Net8Game {
        constructor(config, sdk) {
            this.sdk = sdk;
            this.model = config.model;
            this.userId = config.userId || null; // ユーザーID（パートナー側）
            this.container = this._resolveContainer(config.container);
            this.sessionId = null;
            this.machineNo = null;
            this.playUrl = null;
            this.iframe = null;
            this.isStarting = false; // ゲーム開始中フラグ
            this.isStarted = false; // ゲーム開始済みフラグ

            // ゲームデータ
            this.pointsConsumed = 0;
            this.pointsWon = 0;
            this.gameResult = null;

            // リアルタイムゲーム状態
            this.gameState = {
                credit: 0,
                playpoint: 0,
                drawpoint: 0,
                bb_count: 0,
                rb_count: 0,
                total_count: 0,
                isPlaying: false,
                lastUpdate: null
            };

            // イベントリスナー
            this.listeners = {};

            // メッセージハンドラーの参照を保持（削除用）
            this._messageHandler = null;
            this._isListenerAttached = false;
        }

        /**
         * コンテナ要素を解決
         */
        _resolveContainer(container) {
            if (typeof container === 'string') {
                const element = document.querySelector(container);
                if (!element) {
                    throw new Error(`Container not found: ${container}`);
                }
                return element;
            }
            return container;
        }

        /**
         * ゲーム開始
         */
        async start() {
            // 既に開始済みの場合はスキップ
            if (this.isStarted) {
                console.log('[Net8 Game] Already started');
                return;
            }

            // 開始処理中の場合はスキップ
            if (this.isStarting) {
                console.log('[Net8 Game] Start in progress...');
                return;
            }

            this.isStarting = true;
            console.log(`[Net8 Game] Starting game: ${this.model}`);

            try {
                // ゲームセッション開始API呼び出し
                const requestBody = {
                    modelId: this.model
                };

                // userIdがあれば追加
                if (this.userId) {
                    requestBody.userId = this.userId;
                }

                const response = await fetch(`${this.sdk.apiUrl}/api/v1/game_start.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.sdk.token}`
                    },
                    body: JSON.stringify(requestBody)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to start game');
                }

                const data = await response.json();
                this.sessionId = data.sessionId;
                this.machineNo = data.machineNo;
                this.playUrl = `${this.sdk.apiUrl}${data.playUrl}`;
                this.pointsConsumed = data.pointsConsumed || 0;

                // イベントリスナーを先に設定
                this._setupGameEventListener();

                // ゲーム画面を表示（iframeで既存システムを表示）
                this._displayGame();

                this.isStarted = true;
                this._emit('ready');
                this._emit('started', {
                    sessionId: this.sessionId,
                    machineNo: this.machineNo,
                    pointsConsumed: this.pointsConsumed
                });
                console.log('[Net8 Game] Game started successfully');

            } catch (error) {
                console.error('[Net8 Game] Failed to start:', error);
                this._emit('error', error);
                throw error;
            } finally {
                this.isStarting = false;
            }
        }

        /**
         * ゲーム画面を表示
         */
        _displayGame() {
            // 既存のiframeがあれば削除
            if (this.iframe) {
                this.iframe.remove();
            }

            // iframeを作成
            this.iframe = document.createElement('iframe');
            this.iframe.src = this.playUrl;
            this.iframe.style.width = '100%';
            this.iframe.style.height = '100%';
            this.iframe.style.border = 'none';

            // コンテナをクリアしてiframeを追加
            this.container.innerHTML = '';
            this.container.appendChild(this.iframe);
        }

        /**
         * ゲームイベントリスナー設定
         */
        _setupGameEventListener() {
            // 既にリスナーが登録されている場合はスキップ
            if (this._isListenerAttached) {
                console.log('[Net8 Game] Event listener already attached');
                return;
            }

            console.log('[Net8 Game] Setting up event listener');

            // メッセージハンドラーを作成
            this._messageHandler = (event) => {
                const data = event.data;
                if (!data || !data.type) {
                    return;
                }

                // ゲームイベントのみ処理（game:で始まる）
                if (!data.type.startsWith('game:')) {
                    return;
                }

                // セキュリティチェック（開発環境では緩和）
                // 同一オリジンまたはiframe内からのメッセージを許可
                const isSameOrigin = event.origin === window.location.origin;
                const isFromAPI = event.origin.includes('railway.app') || event.origin.includes('vercel.app');

                if (!isSameOrigin && !isFromAPI && this.sdk.apiUrl && !event.origin.includes(new URL(this.sdk.apiUrl).hostname)) {
                    console.warn('[Net8 Game] Message from untrusted origin:', event.origin);
                    return;
                }

                // ゲームイベントを処理
                switch (data.type) {
                    case 'game:ready':
                        // ゲーム準備完了
                        this.gameState.isPlaying = true;
                        this.gameState.lastUpdate = Date.now();
                        this._emit('ready', data.payload);
                        break;
                    case 'game:play':
                        // プレイ開始（クレジット消費）
                        if (data.payload.credit !== undefined) {
                            this.gameState.credit = data.payload.credit;
                        }
                        this.gameState.lastUpdate = Date.now();
                        this._emit('play', data.payload);
                        break;
                    case 'game:win':
                        // 勝利（クレジット増加）
                        if (data.payload.credit !== undefined) {
                            this.gameState.credit = data.payload.credit;
                        }
                        this.gameState.lastUpdate = Date.now();
                        this._emit('win', data.payload);
                        break;
                    case 'game:lose':
                        this.gameState.lastUpdate = Date.now();
                        this._emit('lose', data.payload);
                        break;
                    case 'game:bonus':
                        // ボーナス当選（BB/RB）
                        if (data.payload.type === 'BB') {
                            this.gameState.bb_count = data.payload.count || this.gameState.bb_count;
                        } else if (data.payload.type === 'RB') {
                            this.gameState.rb_count = data.payload.count || this.gameState.rb_count;
                        }
                        this.gameState.lastUpdate = Date.now();
                        this._emit('bonus', data.payload);
                        break;
                    case 'game:score':
                        // スコア更新（全ステータス）
                        if (data.payload.credit !== undefined) {
                            this.gameState.credit = data.payload.credit;
                        }
                        if (data.payload.playpoint !== undefined) {
                            this.gameState.playpoint = data.payload.playpoint;
                        }
                        if (data.payload.drawpoint !== undefined) {
                            this.gameState.drawpoint = data.payload.drawpoint;
                        }
                        if (data.payload.bb_count !== undefined) {
                            this.gameState.bb_count = data.payload.bb_count;
                        }
                        if (data.payload.rb_count !== undefined) {
                            this.gameState.rb_count = data.payload.rb_count;
                        }
                        this.gameState.total_count = this.gameState.bb_count + this.gameState.rb_count;
                        this.gameState.lastUpdate = Date.now();
                        this._emit('score', data.payload);
                        break;
                    case 'game:end':
                        // ゲーム終了処理
                        this.gameState.isPlaying = false;
                        this.gameState.lastUpdate = Date.now();
                        this._handleGameEnd(data.payload);
                        break;
                    case 'game:error':
                        this.gameState.isPlaying = false;
                        this.gameState.lastUpdate = Date.now();
                        this._emit('error', data.payload);
                        break;
                }
            };

            window.addEventListener('message', this._messageHandler);
            this._isListenerAttached = true;
        }

        /**
         * イベントリスナー登録
         */
        on(event, handler) {
            if (typeof handler !== 'function') {
                throw new Error('Event handler must be a function');
            }

            if (!this.listeners[event]) {
                this.listeners[event] = [];
            }

            // 同じハンドラーが既に登録されていないかチェック
            if (!this.listeners[event].includes(handler)) {
                this.listeners[event].push(handler);
            }
        }

        /**
         * イベント発火
         */
        _emit(event, ...args) {
            if (this.listeners[event]) {
                // リスナーのコピーを作成して反復（リスナー配列の変更を防ぐ）
                const listeners = [...this.listeners[event]];

                listeners.forEach(handler => {
                    try {
                        // setTimeoutで非同期実行してスタックオーバーフロー防止
                        setTimeout(() => {
                            handler(...args);
                        }, 0);
                    } catch (error) {
                        console.error(`[Net8 Game] Event handler error (${event}):`, error);
                    }
                });
            }
        }

        /**
         * イベントリスナー削除
         */
        off(event, handler) {
            if (this.listeners[event]) {
                if (handler) {
                    // 特定のハンドラーを削除
                    this.listeners[event] = this.listeners[event].filter(h => h !== handler);
                } else {
                    // イベントの全ハンドラーを削除
                    delete this.listeners[event];
                }
            }
        }

        /**
         * 現在のゲーム状態を取得
         * @returns {Object} 現在のゲーム状態
         */
        getGameState() {
            return {
                ...this.gameState,
                sessionId: this.sessionId,
                machineNo: this.machineNo,
                isStarted: this.isStarted,
                pointsConsumed: this.pointsConsumed
            };
        }

        /**
         * ゲーム終了処理
         */
        async _handleGameEnd(payload) {
            console.log('[Net8 Game] Game ended', payload);

            // ゲーム結果を保存
            this.gameResult = payload.result || 'completed';
            this.pointsWon = payload.pointsWon || 0;

            try {
                // ゲーム終了APIを呼び出し
                const response = await fetch(`${this.sdk.apiUrl}/api/v1/game_end.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.sdk.token}`
                    },
                    body: JSON.stringify({
                        sessionId: this.sessionId,
                        result: this.gameResult,
                        pointsWon: this.pointsWon,
                        resultData: payload
                    })
                });

                if (!response.ok) {
                    console.error('[Net8 Game] Failed to record game end');
                }

                const data = await response.json();

                // ゲーム終了イベントを発火
                this._emit('end', {
                    sessionId: this.sessionId,
                    result: this.gameResult,
                    pointsConsumed: this.pointsConsumed,
                    pointsWon: this.pointsWon,
                    netProfit: this.pointsWon - this.pointsConsumed,
                    newBalance: data.newBalance || null
                });

            } catch (error) {
                console.error('[Net8 Game] Error handling game end:', error);
                this._emit('error', error);
            }
        }

        /**
         * ゲーム中にポイントを追加
         * @param {number} amount - 追加するポイント数
         * @param {string} description - 追加理由の説明（オプション）
         * @returns {Promise<Object>} 取引情報
         */
        async addPoints(amount, description = 'Bonus points during gameplay') {
            if (!this.isStarted) {
                throw new Error('Game is not started');
            }

            if (!this.sessionId) {
                throw new Error('No active game session');
            }

            if (typeof amount !== 'number' || amount <= 0) {
                throw new Error('Amount must be a positive number');
            }

            try {
                const response = await fetch(`${this.sdk.apiUrl}/api/v1/add_points.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.sdk.token}`
                    },
                    body: JSON.stringify({
                        sessionId: this.sessionId,
                        amount: amount,
                        description: description
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to add points');
                }

                const data = await response.json();

                // ポイント追加イベントを発火
                this._emit('pointsAdded', {
                    amount: amount,
                    transaction: data.transaction,
                    description: description
                });

                console.log('[Net8 Game] Points added successfully:', data);
                return data;

            } catch (error) {
                console.error('[Net8 Game] Failed to add points:', error);
                this._emit('error', error);
                throw error;
            }
        }

        /**
         * ゲーム手動終了
         */
        async stop() {
            if (!this.isStarted) {
                console.log('[Net8 Game] Game not started');
                return;
            }

            // ゲーム終了処理を実行
            await this._handleGameEnd({
                result: 'cancelled',
                pointsWon: 0
            });

            // リソースをクリーンアップ
            this.destroy();
        }

        /**
         * ゲーム破棄
         */
        destroy() {
            console.log('[Net8 Game] Destroying game...');

            // イベントリスナーを削除
            if (this._messageHandler && this._isListenerAttached) {
                window.removeEventListener('message', this._messageHandler);
                this._messageHandler = null;
                this._isListenerAttached = false;
            }

            // iframeを削除
            if (this.iframe) {
                this.iframe.remove();
                this.iframe = null;
            }

            // リスナーをクリア
            this.listeners = {};

            // フラグをリセット
            this.isStarting = false;
            this.isStarted = false;

            console.log('[Net8 Game] Game destroyed');
        }
    }

    /**
     * GameTransitionManager クラス
     * ゲーム終了後の次ゲーム遷移を管理
     */
    class GameTransitionManager {
        constructor(sdk) {
            this.sdk = sdk;
            this.MIN_PLAY_POINTS = 100; // 最低プレイポイント
            this.businessHours = {
                open: '10:00',
                close: '24:00'
            };
        }

        /**
         * ゲーム終了処理のメインハンドラー
         */
        async handleGameEnd(endData, options = {}) {
            console.log('[Net8 Transition] Handling game end...', endData);

            try {
                // 1. 営業時間チェック
                if (!this.isBusinessHours()) {
                    return this.showClosedMessage(options.onClose);
                }

                // 2. 残高チェック
                if (endData.newBalance !== null && endData.newBalance < this.MIN_PLAY_POINTS) {
                    return this.showInsufficientBalance(endData, options.onCharge, options.onExit);
                }

                // 3. 推奨機種を取得
                const recommendations = await this.getRecommendations(endData.newBalance);

                // 4. 結果と推奨機種を表示
                return this.showResultWithRecommendations({
                    result: endData,
                    recommendations: recommendations,
                    onSelectModel: options.onSelectModel,
                    onViewAll: options.onViewAll,
                    onExit: options.onExit
                });

            } catch (error) {
                console.error('[Net8 Transition] Error handling game end:', error);
                // エラー時はシンプルな結果表示にフォールバック
                return this.showSimpleResult(endData, options.onViewAll);
            }
        }

        /**
         * 営業時間チェック
         */
        isBusinessHours() {
            const now = new Date();
            const currentTime = now.getHours() * 60 + now.getMinutes();

            const [openHour, openMin] = this.businessHours.open.split(':').map(Number);
            const [closeHour, closeMin] = this.businessHours.close.split(':').map(Number);

            const openTime = openHour * 60 + openMin;
            const closeTime = closeHour * 60 + closeMin;

            // 営業時間が日をまたぐ場合の処理
            if (closeTime < openTime) {
                return currentTime >= openTime || currentTime < closeTime;
            }

            return currentTime >= openTime && currentTime < closeTime;
        }

        /**
         * 推奨機種を取得
         */
        async getRecommendations(balance) {
            try {
                const response = await fetch(`${this.sdk.apiUrl}/api/v1/recommended_models.php?balance=${balance}&limit=3`, {
                    headers: {
                        'Authorization': `Bearer ${this.sdk.token || this.sdk.apiKey}`
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch recommendations');
                }

                const data = await response.json();
                return data.models || [];

            } catch (error) {
                console.error('[Net8 Transition] Failed to get recommendations:', error);
                return [];
            }
        }

        /**
         * 営業時間外メッセージ表示
         */
        showClosedMessage(onClose) {
            return {
                type: 'closed',
                title: '営業時間外',
                message: `現在は営業時間外です\n次回営業時間: ${this.businessHours.open}`,
                actions: [
                    {
                        label: '閉じる',
                        type: 'primary',
                        onClick: onClose || (() => window.location.href = '/')
                    }
                ]
            };
        }

        /**
         * ポイント不足メッセージ表示
         */
        showInsufficientBalance(endData, onCharge, onExit) {
            return {
                type: 'insufficient_balance',
                title: 'ポイント不足',
                message: `プレイに必要なポイントが不足しています`,
                balance: endData.newBalance,
                required: this.MIN_PLAY_POINTS,
                actions: [
                    {
                        label: 'チャージ',
                        type: 'primary',
                        onClick: onCharge || (() => window.location.href = '/charge')
                    },
                    {
                        label: '終了',
                        type: 'secondary',
                        onClick: onExit || (() => window.location.href = '/')
                    }
                ]
            };
        }

        /**
         * 結果と推奨機種を表示
         */
        showResultWithRecommendations({ result, recommendations, onSelectModel, onViewAll, onExit }) {
            return {
                type: 'result_with_recommendations',
                title: 'ゲーム終了',
                result: {
                    pointsWon: result.pointsWon,
                    pointsConsumed: result.pointsConsumed,
                    netProfit: result.netProfit,
                    balance: result.newBalance
                },
                recommendations: recommendations,
                actions: [
                    {
                        label: '推奨機種から選ぶ',
                        type: 'model-select',
                        models: recommendations,
                        onClick: onSelectModel || ((modelId) => console.log('Selected:', modelId))
                    },
                    {
                        label: '全機種を見る',
                        type: 'primary',
                        onClick: onViewAll || (() => window.location.href = '/')
                    },
                    {
                        label: '終了',
                        type: 'secondary',
                        onClick: onExit || (() => window.location.href = '/')
                    }
                ]
            };
        }

        /**
         * シンプルな結果表示（フォールバック）
         */
        showSimpleResult(endData, onContinue) {
            return {
                type: 'simple_result',
                title: 'ゲーム終了',
                result: {
                    pointsWon: endData.pointsWon,
                    pointsConsumed: endData.pointsConsumed,
                    netProfit: endData.netProfit,
                    balance: endData.newBalance
                },
                actions: [
                    {
                        label: '続けてプレイ',
                        type: 'primary',
                        onClick: onContinue || (() => window.location.href = '/')
                    }
                ]
            };
        }

        /**
         * 営業時間を設定
         */
        setBusinessHours(open, close) {
            this.businessHours = { open, close };
        }

        /**
         * 最低プレイポイントを設定
         */
        setMinPlayPoints(points) {
            this.MIN_PLAY_POINTS = points;
        }
    }

    // グローバルに公開
    const net8Instance = new Net8SDK();

    // Net8オブジェクトを作成
    window.Net8 = {
        // SDK初期化
        init: (apiKey, options) => net8Instance.init(apiKey, options),

        // 機種一覧取得
        getModels: () => net8Instance.getModels(),

        // ゲーム作成
        createGame: (config) => net8Instance.createGame(config),

        // 次ゲーム遷移マネージャー作成
        createTransitionManager: () => new GameTransitionManager(net8Instance),

        // バージョン情報
        version: SDK_VERSION
    };

    console.log(`[Net8 SDK v${SDK_VERSION}] Loaded`);

})(window);
