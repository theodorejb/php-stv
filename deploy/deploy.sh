#!/bin/bash

# this should be run from the production server after copying a new build archive
# this file must have unix line endings

set -e # exit when any command fails

# unzip to new incremented folder
date=$(date +%Y_%m_%d_%H%M%S)
folder="/var/www/php-stv_$date"
unzip -q ~/php-stv.zip -d $folder

# copy nginx configuration if it doesn't exist yet
cp --no-clobber $folder/php-stv_prod.conf /etc/nginx/sites-available/php-stv

# create/update nginx symlink to new folder
ln -sfn $folder /var/www/php-stv
sudo service nginx restart

# clean up old folders
find /var/www -type d -name "php-stv_*" ! -name "php-stv_$date" -prune -exec rm -r {} \;
