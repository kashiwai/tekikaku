<?php
/**
 * 管理画面クリック問題修正スクリプト
 * Created: 2025-12-12
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>管理画面クリック問題診断・修正</title></head><body>";
echo "<h1>🔧 管理画面クリック問題診断・修正ツール</h1>";
echo "<hr>";

// ステップ1: 管理画面index.phpファイルの確認
echo "<h2>📂 STEP 1: 管理画面ファイル確認</h2>";

$admin_files = [
    '/var/www/html/data/xxxadmin/index.php',
    '/var/www/html/xxxadmin/index.php',
];

$found_index = false;
foreach ($admin_files as $file) {
    if (file_exists($file)) {
        $found_index = true;
        echo "<p>✅ 発見: $file (" . filesize($file) . " bytes)</p>";
        
        // CSSのオーバーレイ問題をチェック
        $content = file_get_contents($file);
        
        // 問題のあるCSS要素をチェック
        $css_issues = [];
        
        // 1. position: fixedで画面全体を覆うような要素
        if (preg_match('/position\s*:\s*fixed.*?width\s*:\s*100%/s', $content) || 
            preg_match('/position\s*:\s*fixed.*?height\s*:\s*100vh/s', $content)) {
            $css_issues[] = "position: fixed で画面全体を覆う要素が存在";
        }
        
        // 2. 高いz-indexの要素
        if (preg_match('/z-index\s*:\s*9999/', $content)) {
            $css_issues[] = "z-index: 9999 の高優先度要素が存在";
        }
        
        // 3. pointer-events: none の誤用
        if (preg_match('/pointer-events\s*:\s*none/', $content)) {
            $css_issues[] = "pointer-events: none が設定されている要素が存在";
        }
        
        // 4. overlay クラスの存在
        if (preg_match('/class\s*=\s*["\'][^"\']*overlay[^"\']*["\']/', $content)) {
            $css_issues[] = "overlay クラスが使用されている";
        }
        
        echo "<h3>🔍 CSS問題チェック結果:</h3>";
        if (empty($css_issues)) {
            echo "<p>✅ 明らかなCSS問題は検出されませんでした</p>";
        } else {
            foreach ($css_issues as $issue) {
                echo "<p>⚠️ $issue</p>";
            }
        }
        
        // アニメーション関連のチェック
        if (preg_match('/fade-in/', $content)) {
            echo "<p>📝 fade-in アニメーションが使用されています（アニメーション中はクリックできない場合があります）</p>";
        }
        
        break;
    } else {
        echo "<p>❌ 存在しない: $file</p>";
    }
}

if (!$found_index) {
    echo "<p>❌ 管理画面index.phpが見つかりません！</p>";
    echo "</body></html>";
    exit;
}

echo "<hr>";

// ステップ2: CSSの潜在的問題を修正
echo "<h2>🔧 STEP 2: CSS修正の実行</h2>";

// 修正されたCSSコードを生成
$fixed_css = '
<style>
/* 修正版CSS - クリック問題対応 */
body { 
    margin: 0; 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
    background: #f8fafc; 
    display: flex;
    pointer-events: auto; /* 明示的にクリック可能に */
}

.sidebar { 
    width: 260px; 
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); 
    height: 100vh; 
    position: fixed; 
    left: 0; 
    top: 0; 
    color: white; 
    overflow-y: auto;
    z-index: 100; /* 適切なz-index */
    pointer-events: auto; /* クリック可能 */
}

.main-content { 
    margin-left: 260px; 
    flex: 1;
    pointer-events: auto; /* クリック可能 */
    position: relative;
    z-index: 1;
}

/* アニメーション中もクリック可能にする */
.fade-in {
    pointer-events: auto !important;
}

/* 全てのボタンとリンクを明示的にクリック可能に */
a, button, .btn, .nav-item, .stat-card, .info-card {
    pointer-events: auto !important;
    cursor: pointer !important;
    position: relative;
    z-index: 10;
}

/* 統計カードのクリック可能化 */
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    cursor: pointer;
}

/* インフォカード内のボタン */
.info-card .btn {
    pointer-events: auto !important;
    z-index: 20;
}

/* ナビゲーション項目 */
.nav-item {
    pointer-events: auto !important;
    cursor: pointer !important;
}

/* オーバーレイ要素があれば無効化 */
.overlay, .backdrop, .modal-backdrop {
    display: none !important;
    pointer-events: none !important;
}
</style>';

echo "<h3>💾 修正版CSS:</h3>";
echo "<pre style='background:#f5f5f5; padding:10px; font-size:12px;'>";
echo htmlspecialchars($fixed_css);
echo "</pre>";

echo "<hr>";

// ステップ3: JavaScriptによるクリック問題の対処
echo "<h2>⚡ STEP 3: JavaScript修正コード</h2>";

$fix_js = "
<script>
// クリック問題修正スクリプト
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 クリック問題修正スクリプト開始');
    
    // 1. 全ての要素のpointer-eventsを確認・修正
    function fixPointerEvents() {
        const elements = document.querySelectorAll('a, button, .btn, .nav-item, .stat-card');
        elements.forEach(el => {
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
            el.style.position = 'relative';
            el.style.zIndex = '10';
        });
        console.log('✅ pointer-events修正:', elements.length + '個の要素');
    }
    
    // 2. オーバーレイ要素の強制削除
    function removeOverlays() {
        const overlays = document.querySelectorAll('.overlay, .backdrop, .modal-backdrop, [style*=\"position: fixed\"][style*=\"width: 100%\"]');
        overlays.forEach(overlay => {
            overlay.style.display = 'none';
            overlay.style.pointerEvents = 'none';
            console.log('🗑️ オーバーレイ削除:', overlay);
        });
    }
    
    // 3. 全体のクリックテストを実行
    function testClicks() {
        const clickableElements = document.querySelectorAll('a, button, .btn');
        let workingCount = 0;
        
        clickableElements.forEach((el, index) => {
            const rect = el.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const elementAtPoint = document.elementFromPoint(centerX, centerY);
            
            if (elementAtPoint === el || el.contains(elementAtPoint)) {
                workingCount++;
            } else {
                console.warn('⚠️ クリック障害:', el, 'が', elementAtPoint, 'に隠されています');
                // 強制修正
                el.style.zIndex = '1000';
                el.style.position = 'relative';
            }
        });
        
        console.log('✅ クリックテスト完了: ' + workingCount + '/' + clickableElements.length + ' 個が正常');
    }
    
    // 修正実行
    setTimeout(() => {
        fixPointerEvents();
        removeOverlays();
        testClicks();
    }, 100);
    
    // 5秒後に再チェック
    setTimeout(() => {
        fixPointerEvents();
        testClicks();
    }, 5000);
});
</script>";

echo "<h3>💾 JavaScript修正コード:</h3>";
echo "<pre style='background:#f5f5f5; padding:10px; font-size:12px;'>";
echo htmlspecialchars($fix_js);
echo "</pre>";

echo "<hr>";

// ステップ4: 簡易テストコード
echo "<h2>🧪 STEP 4: クリック問題テストページ</h2>";
echo '<p>以下のボタンでクリック機能をテストできます:</p>';

echo '<div style="margin: 20px 0;">';
echo '<button onclick="alert(\'ボタンクリック成功！\')" style="padding: 10px 20px; margin: 5px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">テストボタン1</button>';
echo '<button onclick="testAllButtons()" style="padding: 10px 20px; margin: 5px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer;">全ボタンテスト</button>';
echo '<a href="#" onclick="alert(\'リンククリック成功！\'); return false;" style="padding: 10px 20px; margin: 5px; background: #f59e0b; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">テストリンク</a>';
echo '</div>';

echo '<script>
function testAllButtons() {
    const buttons = document.querySelectorAll("button, a, .btn");
    let clickable = 0;
    let total = buttons.length;
    
    buttons.forEach(btn => {
        const rect = btn.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const elementAtPoint = document.elementFromPoint(centerX, centerY);
        
        if (elementAtPoint === btn || btn.contains(elementAtPoint)) {
            clickable++;
        }
    });
    
    alert(`クリックテスト結果: ${clickable}/${total} 個のボタンがクリック可能です`);
}
</script>';

echo "<hr>";
echo "<h2>🎯 STEP 5: 推奨される修正方法</h2>";
echo "<ol>";
echo "<li><strong>index.phpのCSSに上記の修正版CSSを追加</strong></li>";
echo "<li><strong>JavaScriptコードをbody終了タグ前に追加</strong></li>";
echo "<li><strong>アニメーション中でもクリック可能になるようpointer-events: auto を明示</strong></li>";
echo "<li><strong>z-indexの競合を解消</strong></li>";
echo "</ol>";

echo "<p><a href='/xxxadmin/index.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>管理画面に戻る</a></p>";

echo "</body></html>";
?>