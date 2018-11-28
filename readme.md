# Juice Backups Package

Backup your application and database data.

## Note

This package use spatie/db-dumper to dump database data.
For supporting database type, please check the following
link and make sure meets the requirement.

[https://github.com/spatie/db-dumper](https://github.com/spatie/db-dumper)

## Installation

1. run composer require command `composer require juice/backups`

2. register `\Juice\Backups\BackupsServiceProvider::class` service provider

3. copy config file and set it up

   - Laravel - `php artisan vendor:publish --provider="Juice\Backups\BackupsServiceProvider"`

   - Lumen - `cp vendor/juice/backups/config/juice-backups.php config/`

     (make sure config directory exist)

4. run setup command `php artisan backup:setup`

5. done
