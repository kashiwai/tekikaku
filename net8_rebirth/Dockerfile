FROM php:7.2-apache

# Debian Busterのリポジトリをアーカイブに変更（EOLのため）
RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i '/stretch-updates/d' /etc/apt/sources.list

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    wget \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能のインストール
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    gd \
    zip

# Apacheモジュール有効化
RUN a2enmod rewrite ssl headers

# カスタムPHP設定
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini

# Apacheカスタム設定
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# アプリケーションファイルをコピー
COPY net8/02.ソースファイル/net8_html /var/www/html

# 画像アップロード用ディレクトリ作成
RUN mkdir -p /var/www/html/data/img/model \
    && mkdir -p /var/www/html/data/uploads

# 権限設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/data/img/model \
    && chmod -R 777 /var/www/html/data/uploads

# ==========================================
# Cloud SQL Auth Proxy（トンネルプログラム）
# ==========================================
# Cloud SQL Proxyをダウンロード
RUN wget https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64 -O /usr/local/bin/cloud_sql_proxy \
    && chmod +x /usr/local/bin/cloud_sql_proxy

# GCPサービスアカウントキー用ディレクトリ作成
RUN mkdir -p /secrets

# GCPサービスアカウントキーをコピー
COPY avamodb-1e63f7e1a1f2.json /secrets/gcp-key.json
RUN chmod 600 /secrets/gcp-key.json

# 起動スクリプトをコピー
COPY start.sh /start.sh
RUN chmod +x /start.sh

# ポート公開（Railwayは環境変数PORTを使用）
EXPOSE ${PORT:-80}

# トンネルプログラム + Apache起動
CMD ["/start.sh"]
