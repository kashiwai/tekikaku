#!/bin/bash
set -e

# MPMモジュールの競合を起動時に確実に解消
echo "Cleaning up MPM modules..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

# mpm_preforkのみ有効化（存在しない場合のみ作成）
if [ ! -f /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
fi
if [ ! -f /etc/apache2/mods-enabled/mpm_prefork.conf ]; then
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
fi

# 有効なMPMを表示
echo "Enabled MPM modules:"
ls -la /etc/apache2/mods-enabled/mpm_* 2>/dev/null || echo "No MPM found"

# Railway PORT環境変数を使ってApacheのポート設定を動的に変更
PORT=${PORT:-80}
echo "Using PORT: $PORT"

# ports.confを動的に書き換え
echo "Listen $PORT" > /etc/apache2/ports.conf

# VirtualHostのポートも変更
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Apache設定テスト
echo "Testing Apache configuration..."
apache2ctl -t

# Apache起動
echo "Starting Apache..."
exec apache2-foreground
