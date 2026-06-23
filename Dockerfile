# 1. 明确指定基于 Debian 的官方 FPM 镜像
FROM php:8.4-fpm

# 2. 核心优化：将 Debian 软件源切换为国内阿里云镜像源（适配 Debian 12 Bookworm 格式）
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list.d/debian.sources \
    && sed -i 's/security.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list.d/debian.sources

# 3. 使用 apt-get 安装 Debian 环境下的编译依赖与工具
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    pkg-config \
    ffmpeg \
    ghostscript \
    python3 \
    python3-pip \
    libreoffice-impress \
    fonts-wqy-zenhei \
    fonts-wqy-microhei \
    fonts-noto-cjk \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 安装 Python 依赖（使用预编译 wheel，避免从源码编译）
RUN pip3 install --break-system-packages \
    -i https://mirrors.aliyun.com/pypi/simple/ \
    --trusted-host mirrors.aliyun.com \
    --only-binary :all: \
    pymupdf==1.24.14 Pillow==10.4.0

# 4. 编译并安装 PHP 核心扩展（去掉了已内置的 mbstring 和 xml）
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql bcmath gd intl zip opcache

# 5. 引入 Composer 并配置国内全量镜像加速
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# 设置工作目录
WORKDIR /app

# 复制依赖文件
COPY composer.json composer.lock ./

# 安装依赖
RUN composer install --optimize-autoloader --no-dev --no-scripts

# 复制项目文件
COPY . .

# 复制 PHP 配置到正确位置
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# 复制 nginx 配置（替换 Debian 默认配置）
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 复制 PHP-FPM 配置（增加 worker 数，避免并发请求耗尽）
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# 复制 supervisor 配置
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 运行 Laravel 优化缓存
RUN composer dump-autoload --optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# 设置权限（Debian 下 FPM 用户同样是 www-data）
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# 暴露端口
EXPOSE 9000

# 启动 supervisor（同时启动 nginx 和 php-fpm）
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
