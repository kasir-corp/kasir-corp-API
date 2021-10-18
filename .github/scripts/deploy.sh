#!/bin/bash
set -e pipeline

echo "Checkout to release branch"
git checkout release

echo "Pull from repository"
git pull

echo "Deploying application ..."

echo "Installing packages"
composer install

echo "Migrating database"
yes | php artisan migrate

echo "Clearing application and configuration cache"
php artisan cache:clear
php artisan config:clear
php artisan config:cache

echo "Application deployed!"
