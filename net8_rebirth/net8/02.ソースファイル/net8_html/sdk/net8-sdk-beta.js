/**
 * NET8 Gaming SDK - Beta Version
 * Version: 1.0.1-beta
 * Updated: 2025-11-12 - Fixed infinite loop issues
 */

(function(window) {
    'use strict';

    // SDK設定
    const SDK_VERSION = '1.0.1-beta';
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
    }

    /**
     * Net8Game クラス
     */
    class Net8Game {
        constructor(config, sdk) {
            this.sdk = sdk;
            this.model = config.model;
            this.container = this._resolveContainer(config.container);
            this.sessionId = null;
            this.machineNo = null;
            this.playUrl = null;
            this.iframe = null;
            this.isStarting = false; // ゲーム開始中フラグ
            this.isStarted = false; // ゲーム開始済みフラグ

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
                const response = await fetch(`${this.sdk.apiUrl}/api/v1/game_start.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.sdk.token}`
                    },
                    body: JSON.stringify({
                        modelId: this.model
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to start game');
                }

                const data = await response.json();
                this.sessionId = data.sessionId;
                this.machineNo = data.machineNo;
                this.playUrl = `${this.sdk.apiUrl}${data.playUrl}`;

                // イベントリスナーを先に設定
                this._setupGameEventListener();

                // ゲーム画面を表示（iframeで既存システムを表示）
                this._displayGame();

                this.isStarted = true;
                this._emit('ready');
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
                // セキュリティチェック
                if (event.origin !== this.sdk.apiUrl) {
                    return;
                }

                const data = event.data;
                if (!data || !data.type) {
                    return;
                }

                // ゲームイベントを処理
                switch (data.type) {
                    case 'game:ready':
                        this._emit('ready', data.payload);
                        break;
                    case 'game:play':
                        this._emit('play', data.payload);
                        break;
                    case 'game:win':
                        this._emit('win', data.payload);
                        break;
                    case 'game:lose':
                        this._emit('lose', data.payload);
                        break;
                    case 'game:bonus':
                        this._emit('bonus', data.payload);
                        break;
                    case 'game:score':
                        this._emit('score', data.payload);
                        break;
                    case 'game:end':
                        this._emit('end', data.payload);
                        break;
                    case 'game:error':
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
         * ゲーム終了
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

        // バージョン情報
        version: SDK_VERSION
    };

    console.log(`[Net8 SDK v${SDK_VERSION}] Loaded`);

})(window);
