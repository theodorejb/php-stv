# starting with Ubuntu 20.04 x64 image

# Add PHP ppa and install PHP
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.0-fpm php8.0-xml php8.0-mysql php8.0-mbstring

# install nginx and zip/unzip
sudo apt install nginx
sudo apt install zip unzip

# remove symlink to disable default site
rm /etc/nginx/sites-enabled/default

# create/deploy initial production build
bash deploy/build.sh # run locally, not on server

# enable site
ln -sfn /etc/nginx/sites-available/php-stv /etc/nginx/sites-enabled/
sudo systemctl reload nginx

# enable HTTPS - see https://certbot.eff.org/lets-encrypt/ubuntufocal-nginx
sudo apt-get update
sudo apt-get install software-properties-common
sudo add-apt-repository universe
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx
