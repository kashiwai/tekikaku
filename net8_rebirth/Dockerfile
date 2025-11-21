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
    netcat-openbsd \
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

# Composerインストール（Google Cloud Storage用） - GCS SDK v1.23
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# カスタムPHP設定
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini

# Apacheカスタム設定
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# アプリケーションファイルをコピー（2025-11-10 SmartTemplate対応）
COPY net8/02.ソースファイル/net8_html /var/www/html

# composer.jsonとcomposer.lockをコピー
COPY composer.json composer.lock* /var/www/html/

# 依存関係インストール（Google Cloud Storage PHP SDK）
WORKDIR /var/www/html
# composer.jsonが存在するか確認
RUN ls -la composer.json && cat composer.json
# Composerインストール実行（詳細表示）
RUN composer install --no-dev --optimize-autoloader --no-interaction --verbose || (echo "❌ Composer install failed" && exit 1)
# インストール結果確認
RUN ls -la vendor/ && ls -la vendor/google/ || echo "⚠️ vendor/google/ not found"

# 画像アップロード用ディレクトリ作成
RUN mkdir -p /var/www/html/data/img/model \
    && mkdir -p /var/www/html/data/uploads

# 権限設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/data/img/model \
    && chmod -R 777 /var/www/html/data/uploads

# ポート公開（Railwayは環境変数PORTを使用）
EXPOSE ${PORT:-80}

# Apache起動
CMD ["apache2-foreground"]
