# Official WordPress image plus the PHP FTP extension (with FTPS support).
# Most shared hosts ship ext-ftp; the official image does not, and the FTP
# destination cannot be tested without it.
FROM wordpress:php8.2-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libssl-dev \
	&& docker-php-ext-configure ftp --with-openssl-dir=/usr \
	&& docker-php-ext-install ftp \
	&& rm -rf /var/lib/apt/lists/*
