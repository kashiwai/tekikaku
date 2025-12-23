/**
 * MeshCentral Railway Starter
 * 環境変数からポートを取得し、config.jsonを動的に更新して起動
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

// Railway環境変数からポート取得（デフォルト: 443）
const PORT = process.env.PORT || 443;
const RAILWAY_PUBLIC_DOMAIN = process.env.RAILWAY_PUBLIC_DOMAIN || 'localhost';

console.log(`[MeshCentral Starter] PORT: ${PORT}`);
console.log(`[MeshCentral Starter] DOMAIN: ${RAILWAY_PUBLIC_DOMAIN}`);

// config.jsonのパス
const configPath = path.join(__dirname, 'meshcentral-data', 'config.json');

// 設定ファイル読み込み
let config;
try {
    config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
} catch (e) {
    console.log('[MeshCentral Starter] Creating default config...');
    config = {
        settings: {},
        domains: { "": {} }
    };
}

// ポートを動的に設定
config.settings.port = parseInt(PORT);
config.settings.aliasPort = 443;  // 外部からは443でアクセス
config.settings.redirPort = 0;    // HTTPリダイレクト無効
config.settings.agentPort = parseInt(PORT);
config.settings.agentAliasPort = 443;

// ドメイン設定
if (RAILWAY_PUBLIC_DOMAIN !== 'localhost') {
    config.settings.cert = RAILWAY_PUBLIC_DOMAIN;
    config.domains[""].certUrl = `https://${RAILWAY_PUBLIC_DOMAIN}/`;
}

// TLSオフロード（Railwayのリバースプロキシ用）
config.settings.tlsOffload = "127.0.0.1";

// WebSocket設定
config.settings.webRTC = true;
config.settings.compression = true;
config.settings.wsCompression = true;

// セッションキー（環境変数から取得）
if (process.env.SESSION_KEY) {
    config.settings.sessionKey = process.env.SESSION_KEY;
}

// 設定保存
fs.mkdirSync(path.dirname(configPath), { recursive: true });
fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
console.log('[MeshCentral Starter] Config updated:', JSON.stringify(config.settings, null, 2));

// MeshCentral起動
console.log('[MeshCentral Starter] Starting MeshCentral...');
const meshcentral = spawn('node', ['node_modules/meshcentral'], {
    stdio: 'inherit',
    cwd: __dirname
});

meshcentral.on('error', (err) => {
    console.error('[MeshCentral Starter] Error:', err);
    process.exit(1);
});

meshcentral.on('exit', (code) => {
    console.log(`[MeshCentral Starter] Exited with code: ${code}`);
    process.exit(code);
});
