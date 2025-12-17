/**
 * play_embed/js/embed_player.js
 * 埋め込みプレイヤー専用JavaScript
 * Version: 1.0.0
 *
 * 親ウィンドウとの通信、イベント処理を担当
 */

(function() {
    'use strict';

    // 埋め込みプレイヤー管理クラス
    var EmbedPlayer = {
        // 初期化済みフラグ
        initialized: false,

        // 親ウィンドウへのメッセージ送信
        sendToParent: function(type, data) {
            if (window.parent !== window) {
                var message = {
                    type: type,
                    machineNo: machineno,
                    sessionId: sessionId,
                    timestamp: Date.now()
                };

                if (data) {
                    Object.assign(message, data);
                }

                window.parent.postMessage(message, '*');
                console.log('📤 Sent to parent:', type, data);
            }
        },

        // 親ウィンドウからのメッセージ受信
        handleParentMessage: function(event) {
            var data = event.data;

            if (!data || !data.type) return;

            console.log('📥 Received from parent:', data.type, data);

            switch (data.type) {
                case 'NET8_COMMAND':
                    EmbedPlayer.handleCommand(data.command, data.params);
                    break;

                case 'NET8_GET_STATUS':
                    EmbedPlayer.sendStatus();
                    break;

                case 'NET8_EXIT':
                    EmbedPlayer.exitGame();
                    break;

                case 'NET8_RELOAD':
                    location.reload();
                    break;
            }
        },

        // コマンド処理
        handleCommand: function(command, params) {
            console.log('🎮 Command:', command, params);

            switch (command) {
                case 'start':
                    $('#btn_start').click();
                    break;

                case 'stop':
                    $('#btn_stop').click();
                    break;

                case 'betUp':
                    $('#btn_bet_up').click();
                    break;

                case 'betDown':
                    $('#btn_bet_down').click();
                    break;

                case 'exit':
                    this.exitGame();
                    break;
            }
        },

        // ステータス送信
        sendStatus: function() {
            var status = {
                connected: typeof dataConnection !== 'undefined' && dataConnection && dataConnection.open,
                credit: parseInt($('#credit').text()) || 0,
                point: parseInt($('#point').text()) || 0,
                gameCount: parseInt($('#count').text()) || 0,
                bbCount: parseInt($('#bb_count').text()) || 0,
                rbCount: parseInt($('#rb_count').text()) || 0
            };

            this.sendToParent('NET8_STATUS', status);
        },

        // ゲーム終了
        exitGame: function() {
            console.log('🚪 Exit game requested');

            // 親ウィンドウに終了通知
            this.sendToParent('NET8_GAME_END', {
                reason: 'user_exit'
            });

            // 終了ボタンがあればクリック
            if ($('#btn_exit').length) {
                $('#btn_exit').click();
            } else if ($('.game-after-button').length) {
                $('.game-after-button').first().click();
            }
        },

        // 接続成功時のコールバック
        onConnected: function() {
            console.log('✅ WebRTC connected');

            // ローディング非表示
            $('#loading').fadeOut(300);

            // UIを表示
            $('nav.navbar').fadeIn(300);
            $('#control_panel').fadeIn(300);
            $('#status_bar').fadeIn(300);
            $('#exit_area').fadeIn(300);
            $('.embed-footer').fadeIn(300);

            // 親ウィンドウに通知
            this.sendToParent('NET8_CONNECTED', {
                cameraId: cameraid
            });
        },

        // 接続エラー時のコールバック
        onConnectionError: function(error) {
            console.error('❌ WebRTC connection error:', error);

            this.sendToParent('NET8_ERROR', {
                error: 'connection_failed',
                message: error
            });
        },

        // 接続切断時のコールバック
        onDisconnected: function(reason) {
            console.log('⚠️ WebRTC disconnected:', reason);

            this.sendToParent('NET8_DISCONNECTED', {
                reason: reason
            });
        },

        // ゲーム状態変更時のコールバック
        onGameStateChange: function(state, data) {
            this.sendToParent('NET8_GAME_STATE', {
                state: state,
                data: data
            });
        },

        // ポイント変更時のコールバック
        onPointChange: function(oldPoint, newPoint, delta) {
            this.sendToParent('NET8_POINT_CHANGE', {
                oldPoint: oldPoint,
                newPoint: newPoint,
                delta: delta
            });
        },

        // 初期化
        init: function() {
            if (this.initialized) return;

            console.log('🎮 EmbedPlayer initializing...');

            // 親ウィンドウからのメッセージリスナー
            window.addEventListener('message', this.handleParentMessage.bind(this), false);

            // 終了ボタンイベント
            $(document).on('click', '#btn_exit, .game-after-button', function() {
                EmbedPlayer.sendToParent('NET8_GAME_END', {
                    reason: 'user_exit'
                });
            });

            // 接続状態監視
            this.watchConnection();

            // 定期ステータス送信
            setInterval(function() {
                if (EmbedPlayer.isConnected()) {
                    EmbedPlayer.sendStatus();
                }
            }, 5000);

            this.initialized = true;

            // 準備完了通知
            this.sendToParent('NET8_INITIALIZED');

            console.log('✅ EmbedPlayer initialized');
        },

        // 接続状態監視
        watchConnection: function() {
            var self = this;
            var lastConnected = false;

            setInterval(function() {
                var connected = self.isConnected();

                if (connected !== lastConnected) {
                    if (connected) {
                        self.onConnected();
                    } else if (lastConnected) {
                        self.onDisconnected('connection_lost');
                    }
                    lastConnected = connected;
                }
            }, 500);
        },

        // 接続状態確認
        // view_auth.jsでは_sconnectがグローバル変数（dataConnectionはローカル）
        isConnected: function() {
            // _sconnect (view_auth.jsのグローバル変数) をチェック
            if (typeof _sconnect !== 'undefined' && _sconnect && _sconnect.open === true) {
                return true;
            }
            // dataConnection もフォールバックとしてチェック
            if (typeof dataConnection !== 'undefined' && dataConnection && dataConnection.open === true) {
                return true;
            }
            // 映像が流れているかもチェック（ビデオ要素の再生状態）
            var video = document.getElementById('video');
            if (video && video.srcObject && !video.paused && video.readyState >= 2) {
                return true;
            }
            return false;
        }
    };

    // DOM Ready時に初期化
    $(document).ready(function() {
        EmbedPlayer.init();
    });

    // グローバルに公開
    window.EmbedPlayer = EmbedPlayer;

    // play_v2のコールバックをオーバーライド（存在する場合）
    if (typeof window.onWebRTCConnected === 'undefined') {
        window.onWebRTCConnected = function() {
            EmbedPlayer.onConnected();
        };
    }

    if (typeof window.onWebRTCError === 'undefined') {
        window.onWebRTCError = function(error) {
            EmbedPlayer.onConnectionError(error);
        };
    }

    if (typeof window.onWebRTCDisconnected === 'undefined') {
        window.onWebRTCDisconnected = function(reason) {
            EmbedPlayer.onDisconnected(reason);
        };
    }

})();
