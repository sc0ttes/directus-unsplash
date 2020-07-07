# Directus Unsplash
Unsplash embed provider for Directus Docker image

# Disclaimer
I am neither associated with the Directus project nor Unsplash

# Configure
Add the following bits to your docker-compose file to mount the UnsplashProvider.php file, establish a symlink, change file ownership, and change the maximum file upload size to 50MB.
```
services:
 directus:
  entrypoint:
   - "bash"
   - "-c"
   - "/usr/local/bin/directus-entrypoint pwd;php-ini-add 'post_max_size=50M';php-ini-add 'upload_max_filesize=50M';ln -s /var/directus/custom/embeds/UnsplashProvider.php /var/directus/src/core/Directus/Embed/Provider/;chown -R www-data:www-data /var/directus/src/core/Directus/Embed/Provider/;apache2-foreground;"
  volumes:
   - ./data/custom:/var/directus/custom
```
