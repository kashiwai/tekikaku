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

# Apacheモジュール有効化（圧縮モジュール追加）
RUN a2enmod rewrite ssl headers deflate filter

# Composerインストール（Google Cloud Storage用） - GCS SDK v1.23
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# カスタムPHP設定
COPY net8_rebirth/net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini

# デバッグ用：利用可能なファイルをリスト表示（COPY前に実行）- 2025-12-12 10:55:00
RUN echo "=========================================="
RUN echo "DEBUGGING RAILWAY BUILD CONTEXT - START"  
RUN echo "=========================================="
RUN echo "Current working directory:" && pwd
RUN echo "Root directory contents:" && ls -la / | head -20
RUN echo "Current directory contents:" && ls -la . | head -20
RUN echo "Searching for net8_rebirth:" && find / -maxdepth 3 -name "net8_rebirth" -type d 2>/dev/null || echo "net8_rebirth not found in first 3 levels"
RUN echo "=========================================="
RUN echo "DEBUGGING RAILWAY BUILD CONTEXT - END"
RUN echo "=========================================="

# Apacheカスタム設定
COPY net8_rebirth/net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# アプリケーションファイルをコピー（2025-12-12 デザインアップデート）  
COPY net8_rebirth/net8/02.ソースファイル/net8_html /var/www/html

# 依存関係インストール（Google Cloud Storage PHP SDK）
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

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
# Force rebuild at 2024-12-12 10:55 JST - デバッグ出力強制実行
