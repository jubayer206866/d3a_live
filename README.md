# Setup guide
## Application Config
copy the content of `app-config-sample.php` to `app-config.php` and replace values in [] to match your environment.
```bash
cd application/config
cp app-config-sample.php app-config.php
vim app-config.php # edit the app-config.php file
```
## Dependencies install
Install the dependencies using composer
```bash
cd application
composer install
```
