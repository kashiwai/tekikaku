FROM mysql:5.7

# MySQL設定ファイル
COPY my.cnf /etc/mysql/conf.d/custom.cnf

# 初期化スクリプト（オプション）
# COPY init.sql /docker-entrypoint-initdb.d/

# データディレクトリの権限設定
RUN chown -R mysql:mysql /var/lib/mysql

# ポート公開
EXPOSE 3306

# ヘルスチェック
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD mysqladmin ping -h localhost -u root -p${MYSQL_ROOT_PASSWORD} || exit 1
