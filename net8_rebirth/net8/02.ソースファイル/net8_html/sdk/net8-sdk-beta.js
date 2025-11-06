/**
 * NET8 Gaming SDK - Beta Version
 * Version: 1.0.0-beta
 * Created: 2025-11-06
 *
 * 超シンプル版SDK - 明日までのデモ用
 */

(function(window) {
    'use strict';

    // SDK設定
    const SDK_VERSION = '1.0.0-beta';
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
        }

        /**
         * SDK初期化
         */
        async init(apiKey, options = {}) {
            if (!apiKey || !apiKey.startsWith('pk_')) {
                throw new Error('Invalid API key format. Must start with "pk_"');
            }

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

            // イベントリスナー
            this.listeners = {};
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

                // ゲーム画面を表示（iframeで既存システムを表示）
                this._displayGame();

                this._emit('ready');
                console.log('[Net8 Game] Game started successfully');

            } catch (error) {
                console.error('[Net8 Game] Failed to start:', error);
                this._emit('error', error);
                throw error;
            }
        }

        /**
         * ゲーム画面を表示
         */
        _displayGame() {
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
         * イベントリスナー登録
         */
        on(event, handler) {
            if (!this.listeners[event]) {
                this.listeners[event] = [];
            }
            this.listeners[event].push(handler);
        }

        /**
         * イベント発火
         */
        _emit(event, ...args) {
            if (this.listeners[event]) {
                this.listeners[event].forEach(handler => {
                    try {
                        handler(...args);
                    } catch (error) {
                        console.error(`[Net8 Game] Event handler error (${event}):`, error);
                    }
                });
            }
        }

        /**
         * ゲーム終了
         */
        destroy() {
            if (this.iframe) {
                this.iframe.remove();
                this.iframe = null;
            }
            this.listeners = {};
            console.log('[Net8 Game] Game destroyed');
        }
    }

    // グローバルに公開
    const net8 = new Net8SDK();
    window.Net8 = net8;

    // 便利なヘルパー
    window.Net8.createGame = function(config) {
        return net8.createGame(config);
    };

    console.log(`[Net8 SDK v${SDK_VERSION}] Loaded`);

})(window);
