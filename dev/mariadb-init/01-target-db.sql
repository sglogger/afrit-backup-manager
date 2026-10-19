-- Second database for the migration target site (docker-compose service
-- "wordpress-target"). Runs once, when ./db_data is first initialised.
CREATE DATABASE IF NOT EXISTS wordpress_target CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON wordpress_target.* TO 'wordpress'@'%';
FLUSH PRIVILEGES;
