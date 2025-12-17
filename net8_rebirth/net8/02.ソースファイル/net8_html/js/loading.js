/**
 * Page Loading Animation Controller
 * ページ遷移時のローディングアニメーション制御
 */

(function() {
    'use strict';

    // ローディングオーバーレイのHTML
    const loadingHTML = `
        <div class="page-loading-overlay" id="pageLoadingOverlay">
            <div class="loading-spinner-container">
                <div class="loading-spinner"></div>
                <div class="loading-text">
                    Loading<span class="loading-dots"><span></span><span></span><span></span></span>
                </div>
                <div class="loading-progress">
                    <div class="loading-progress-bar"></div>
                </div>
            </div>
        </div>
    `;

    // DOMにローディング要素を追加
    function createLoadingOverlay() {
        if (!document.getElementById('pageLoadingOverlay')) {
            document.body.insertAdjacentHTML('afterbegin', loadingHTML);

            // 管理画面かどうか判定してクラス追加
            const isAdmin = window.location.pathname.includes('xxxadmin') ||
                           window.location.pathname.includes('/admin/') ||
                           document.body.classList.contains('admin-page') ||
                           document.querySelector('.sb-nav-fixed');
            if (isAdmin) {
                document.body.classList.add('admin-page');
            }
        }
    }

    // ローディングを表示
    function showLoading() {
        const overlay = document.getElementById('pageLoadingOverlay');
        if (overlay) {
            overlay.classList.remove('hidden');
        }
    }

    // ローディングを非表示
    function hideLoading() {
        const overlay = document.getElementById('pageLoadingOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
    }

    // リンククリック時のハンドラー
    function handleLinkClick(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');

        // 以下の場合はローディングを表示しない
        if (!href) return;
        if (href.startsWith('#')) return;
        if (href.startsWith('javascript:')) return;
        if (href.startsWith('mailto:')) return;
        if (href.startsWith('tel:')) return;
        if (link.target === '_blank') return;
        if (link.hasAttribute('download')) return;
        if (e.ctrlKey || e.metaKey || e.shiftKey) return;

        // モーダル系のリンクは除外
        if (link.hasAttribute('data-toggle')) return;
        if (link.classList.contains('modal-link')) return;
        if (link.classList.contains('no-loading')) return;

        // ローディング表示
        showLoading();
    }

    // フォーム送信時のハンドラー
    function handleFormSubmit(e) {
        const form = e.target;

        // Ajax送信の場合は除外
        if (form.hasAttribute('data-ajax')) return;
        if (form.classList.contains('no-loading')) return;

        // ローディング表示
        showLoading();
    }

    // 初期化
    function init() {
        // ローディングオーバーレイを作成
        createLoadingOverlay();

        // ページロード完了時にローディングを非表示
        hideLoading();

        // リンククリックイベント
        document.addEventListener('click', handleLinkClick);

        // フォーム送信イベント
        document.addEventListener('submit', handleFormSubmit);

        // ブラウザの戻る/進むボタン対応
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                hideLoading();
            }
        });

        // beforeunloadでローディング表示（ページ離脱時）
        window.addEventListener('beforeunload', function() {
            showLoading();
        });
    }

    // DOM読み込み完了後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // グローバルに公開（手動制御用）
    window.PageLoading = {
        show: showLoading,
        hide: hideLoading
    };

})();
