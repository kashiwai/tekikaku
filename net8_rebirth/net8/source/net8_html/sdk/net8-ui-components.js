/**
 * NET8 SDK UI Components
 * Version: 1.0.0
 *
 * 機種選択、次ゲーム遷移などのUIコンポーネント
 */

(function(window) {
    'use strict';

    /**
     * Net8 UI コンポーネント
     */
    class Net8UI {
        constructor(options = {}) {
            this.sdk = options.sdk || null;
            this.container = options.container || document.body;
            this.theme = options.theme || 'default';
            this.language = options.language || 'ja';
        }

        /**
         * 機種選択UIを表示
         * @param {Array} models - 機種リスト
         * @param {Function} onSelect - 選択時のコールバック
         * @returns {HTMLElement} UIエレメント
         */
        showMachineSelector(models, onSelect) {
            const overlay = this._createOverlay();
            const modal = this._createModal();

            // ヘッダー
            const header = document.createElement('div');
            header.className = 'net8-modal-header';
            header.innerHTML = `
                <h2>${this._t('selectMachine')}</h2>
                <button class="net8-close-btn">&times;</button>
            `;

            // 機種リスト
            const content = document.createElement('div');
            content.className = 'net8-modal-content';

            if (!models || models.length === 0) {
                content.innerHTML = `
                    <div class="net8-empty-state">
                        <p>${this._t('noMachinesAvailable')}</p>
                    </div>
                `;
            } else {
                const grid = document.createElement('div');
                grid.className = 'net8-machine-grid';

                models.forEach(model => {
                    const card = this._createMachineCard(model);
                    card.addEventListener('click', () => {
                        this._removeOverlay(overlay);
                        if (typeof onSelect === 'function') {
                            onSelect(model);
                        }
                    });
                    grid.appendChild(card);
                });

                content.appendChild(grid);
            }

            // 閉じるボタン
            const closeBtn = header.querySelector('.net8-close-btn');
            closeBtn.addEventListener('click', () => {
                this._removeOverlay(overlay);
            });

            modal.appendChild(header);
            modal.appendChild(content);
            overlay.appendChild(modal);
            this.container.appendChild(overlay);

            return overlay;
        }

        /**
         * 機種カードを作成
         * @private
         */
        _createMachineCard(model) {
            const card = document.createElement('div');
            card.className = 'net8-machine-card';

            const categoryText = model.category === 'pachinko' ? 'パチンコ' : 'スロット';
            const imageUrl = model.imageUrl || this._getDefaultImage(model.category);

            card.innerHTML = `
                <div class="net8-machine-image">
                    <img src="${imageUrl}" alt="${model.name}" />
                    <span class="net8-category-badge">${categoryText}</span>
                </div>
                <div class="net8-machine-info">
                    <h3>${model.name}</h3>
                    <p class="net8-machine-id">ID: ${model.id}</p>
                    ${model.description ? `<p class="net8-machine-desc">${model.description}</p>` : ''}
                    <button class="net8-select-btn">${this._t('selectThisMachine')}</button>
                </div>
            `;

            return card;
        }

        /**
         * 次ゲーム遷移UIを表示
         * @param {Object} gameResult - ゲーム結果
         * @param {Function} onNext - 次ゲームのコールバック
         * @param {Function} onExit - 終了のコールバック
         * @returns {HTMLElement} UIエレメント
         */
        showGameTransition(gameResult, onNext, onExit) {
            const overlay = this._createOverlay();
            const modal = this._createModal('net8-transition-modal');

            // 結果表示
            const isWin = gameResult.netProfit > 0;
            const resultClass = isWin ? 'win' : 'lose';

            modal.innerHTML = `
                <div class="net8-modal-header">
                    <h2 class="net8-result-${resultClass}">
                        ${isWin ? this._t('youWin') : this._t('gameOver')}
                    </h2>
                </div>
                <div class="net8-modal-content">
                    <div class="net8-result-summary">
                        <div class="net8-stat">
                            <span class="net8-stat-label">${this._t('pointsConsumed')}</span>
                            <span class="net8-stat-value">-${gameResult.pointsConsumed}</span>
                        </div>
                        <div class="net8-stat">
                            <span class="net8-stat-label">${this._t('pointsWon')}</span>
                            <span class="net8-stat-value ${isWin ? 'positive' : ''}">
                                +${gameResult.pointsWon}
                            </span>
                        </div>
                        <div class="net8-stat net8-stat-total">
                            <span class="net8-stat-label">${this._t('netProfit')}</span>
                            <span class="net8-stat-value ${gameResult.netProfit >= 0 ? 'positive' : 'negative'}">
                                ${gameResult.netProfit >= 0 ? '+' : ''}${gameResult.netProfit}
                            </span>
                        </div>
                        ${gameResult.newBalance !== undefined ? `
                            <div class="net8-stat">
                                <span class="net8-stat-label">${this._t('currentBalance')}</span>
                                <span class="net8-stat-value">${gameResult.newBalance}</span>
                            </div>
                        ` : ''}
                    </div>
                    <div class="net8-action-buttons">
                        <button class="net8-btn net8-btn-primary" id="net8-next-game">
                            ${this._t('playAgain')}
                        </button>
                        <button class="net8-btn net8-btn-secondary" id="net8-exit-game">
                            ${this._t('exit')}
                        </button>
                    </div>
                </div>
            `;

            // イベントリスナー
            modal.querySelector('#net8-next-game').addEventListener('click', () => {
                this._removeOverlay(overlay);
                if (typeof onNext === 'function') {
                    onNext();
                }
            });

            modal.querySelector('#net8-exit-game').addEventListener('click', () => {
                this._removeOverlay(overlay);
                if (typeof onExit === 'function') {
                    onExit();
                }
            });

            overlay.appendChild(modal);
            this.container.appendChild(overlay);

            return overlay;
        }

        /**
         * ローディング表示
         * @param {string} message - メッセージ
         * @returns {HTMLElement} UIエレメント
         */
        showLoading(message = null) {
            const overlay = this._createOverlay('net8-loading-overlay');

            const loader = document.createElement('div');
            loader.className = 'net8-loader';
            loader.innerHTML = `
                <div class="net8-spinner"></div>
                ${message ? `<p class="net8-loading-message">${message}</p>` : ''}
            `;

            overlay.appendChild(loader);
            this.container.appendChild(overlay);

            return overlay;
        }

        /**
         * エラーメッセージ表示
         * @param {string} message - エラーメッセージ
         * @param {Function} onClose - 閉じる時のコールバック
         * @returns {HTMLElement} UIエレメント
         */
        showError(message, onClose) {
            const overlay = this._createOverlay();
            const modal = this._createModal('net8-error-modal');

            modal.innerHTML = `
                <div class="net8-modal-header net8-error-header">
                    <h2>⚠️ ${this._t('error')}</h2>
                </div>
                <div class="net8-modal-content">
                    <p class="net8-error-message">${message}</p>
                    <button class="net8-btn net8-btn-primary" id="net8-close-error">
                        ${this._t('close')}
                    </button>
                </div>
            `;

            modal.querySelector('#net8-close-error').addEventListener('click', () => {
                this._removeOverlay(overlay);
                if (typeof onClose === 'function') {
                    onClose();
                }
            });

            overlay.appendChild(modal);
            this.container.appendChild(overlay);

            return overlay;
        }

        /**
         * オーバーレイを作成
         * @private
         */
        _createOverlay(className = 'net8-overlay') {
            const overlay = document.createElement('div');
            overlay.className = className;
            return overlay;
        }

        /**
         * モーダルを作成
         * @private
         */
        _createModal(className = 'net8-modal') {
            const modal = document.createElement('div');
            modal.className = className;
            return modal;
        }

        /**
         * オーバーレイを削除
         * @private
         */
        _removeOverlay(overlay) {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }

        /**
         * デフォルト画像を取得
         * @private
         */
        _getDefaultImage(category) {
            return category === 'pachinko'
                ? '/sdk/assets/default-pachinko.png'
                : '/sdk/assets/default-slot.png';
        }

        /**
         * 翻訳
         * @private
         */
        _t(key) {
            const translations = {
                ja: {
                    selectMachine: '機種を選択',
                    noMachinesAvailable: '利用可能な機種がありません',
                    selectThisMachine: 'この機種で遊ぶ',
                    youWin: '勝利！',
                    gameOver: 'ゲーム終了',
                    pointsConsumed: '消費ポイント',
                    pointsWon: '獲得ポイント',
                    netProfit: '純利益',
                    currentBalance: '現在の残高',
                    playAgain: 'もう一度遊ぶ',
                    exit: '終了',
                    error: 'エラー',
                    close: '閉じる'
                },
                en: {
                    selectMachine: 'Select Machine',
                    noMachinesAvailable: 'No machines available',
                    selectThisMachine: 'Play This Machine',
                    youWin: 'You Win!',
                    gameOver: 'Game Over',
                    pointsConsumed: 'Points Consumed',
                    pointsWon: 'Points Won',
                    netProfit: 'Net Profit',
                    currentBalance: 'Current Balance',
                    playAgain: 'Play Again',
                    exit: 'Exit',
                    error: 'Error',
                    close: 'Close'
                }
            };

            return translations[this.language][key] || key;
        }

        /**
         * デフォルトCSSを注入
         */
        injectDefaultStyles() {
            if (document.getElementById('net8-ui-styles')) {
                return; // Already injected
            }

            const style = document.createElement('style');
            style.id = 'net8-ui-styles';
            style.textContent = `
                .net8-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                }

                .net8-modal {
                    background: white;
                    border-radius: 12px;
                    max-width: 800px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                }

                .net8-modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .net8-modal-header h2 {
                    margin: 0;
                    font-size: 24px;
                    color: #333;
                }

                .net8-close-btn {
                    background: none;
                    border: none;
                    font-size: 32px;
                    cursor: pointer;
                    color: #666;
                }

                .net8-modal-content {
                    padding: 20px;
                }

                .net8-machine-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                }

                .net8-machine-card {
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                    cursor: pointer;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .net8-machine-card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }

                .net8-machine-image {
                    position: relative;
                    height: 150px;
                    background: #f5f5f5;
                }

                .net8-machine-image img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .net8-category-badge {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: #ff6b6b;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-size: 12px;
                }

                .net8-machine-info {
                    padding: 15px;
                }

                .net8-machine-info h3 {
                    margin: 0 0 8px 0;
                    font-size: 18px;
                    color: #333;
                }

                .net8-machine-id {
                    color: #999;
                    font-size: 12px;
                    margin: 4px 0;
                }

                .net8-select-btn {
                    width: 100%;
                    padding: 10px;
                    background: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-top: 10px;
                }

                .net8-result-summary {
                    background: #f9f9f9;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                }

                .net8-stat {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }

                .net8-stat-total {
                    border-bottom: none;
                    font-size: 18px;
                    font-weight: bold;
                }

                .net8-stat-value.positive {
                    color: #4CAF50;
                }

                .net8-stat-value.negative {
                    color: #f44336;
                }

                .net8-action-buttons {
                    display: flex;
                    gap: 10px;
                }

                .net8-btn {
                    flex: 1;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 16px;
                }

                .net8-btn-primary {
                    background: #4CAF50;
                    color: white;
                }

                .net8-btn-secondary {
                    background: #757575;
                    color: white;
                }

                .net8-loader {
                    text-align: center;
                    color: white;
                }

                .net8-spinner {
                    border: 4px solid rgba(255, 255, 255, 0.3);
                    border-top: 4px solid white;
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px;
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .net8-error-header {
                    background: #f44336;
                    color: white;
                }

                .net8-error-message {
                    color: #d32f2f;
                    margin: 20px 0;
                }
            `;

            document.head.appendChild(style);
        }
    }

    // Expose to global scope
    window.Net8UI = Net8UI;

})(window);
