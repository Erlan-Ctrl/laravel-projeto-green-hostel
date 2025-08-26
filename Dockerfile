FROM php:8.4-apache

# Variáveis de ambiente
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
ENV APP_ENV local
ENV APP_DEBUG true

# Instalar dependências do sistema e extensões PHP
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        unzip \
        git \
        libzip-dev \
        zlib1g-dev \
        libonig-dev \
        curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar rewrite
RUN a2enmod rewrite

# Configurar DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Diretório de trabalho
WORKDIR /var/www/html

# Instalar Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar somente arquivos de dependência para aproveitar cache do Docker
COPY composer.json composer.lock ./

# Rodar composer install sem executar scripts que dependem do código ainda não copiado
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar restante do projeto
COPY . /var/www/html

# Ajustar permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expor porta 80
EXPOSE 80

CMD ["apache2-foreground"]
