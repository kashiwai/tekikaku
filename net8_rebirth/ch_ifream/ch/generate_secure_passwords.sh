#!/bin/bash

# =====================================================
# NET8 セキュアパスワード生成スクリプト
# 実行: bash generate_secure_passwords.sh
# =====================================================

echo "=========================================="
echo "NET8 データベース用パスワード生成"
echo "=========================================="
echo ""

# パスワード生成関数
generate_password() {
    local name=$1
    local password=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-24)
    echo "【${name}】"
    echo "パスワード: ${password}"
    echo ""
}

# 3つのユーザー用パスワード生成
echo "以下のパスワードをコピーして使用してください:"
echo ""

generate_password "net8_app_secure（本番アプリ用）"
generate_password "net8_readonly（読み取り専用）"
generate_password "net8_admin（管理用）"

echo "=========================================="
echo "⚠️ 重要: これらのパスワードは安全に保管してください"
echo "=========================================="
echo ""
echo "次のステップ:"
echo "1. GCP Cloud SQL でこれらのパスワードを使ってユーザーを作成"
echo "2. Railway環境変数を更新"
echo "3. アプリケーションの動作確認"
echo ""
