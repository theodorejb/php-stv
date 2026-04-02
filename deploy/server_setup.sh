# starting with Ubuntu 24.04 x64 image

# Add PHP ppa and install PHP
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.5-fpm php8.5-xml php8.5-mysql php8.5-mbstring

# install nginx and zip/unzip
sudo apt install nginx zip unzip

# remove symlink to disable default site
rm /etc/nginx/sites-enabled/default

# create/deploy initial production build
bash deploy/build.sh # run locally, not on server

# enable site
ln -sfn /etc/nginx/sites-available/php-stv /etc/nginx/sites-enabled/
sudo systemctl reload nginx

# enable HTTPS - see https://certbot.eff.org/instructions?ws=nginx&os=snap
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/local/bin/certbot
sudo certbot --nginx
