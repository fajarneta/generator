# NVD CRUD Generator

Forked from : [engrnvd](https://github.com/engrnvd/laravel-crud-generator)

Contributor:
- [Fendi Tri Cahyono](https://github.com/endytc/generator)
- myself

## Notice
currently developed for Oracle 10g

## Requirements
- [Laravel 5.2](https://laravel.com/docs/5.2/)
- [Oracle DB driver for Laravel 4|5 via OCI8](https://github.com/yajra/laravel-oci8)
- [jQuery DataTables API for Laravel 4|5](https://github.com/yajra/laravel-datatables)
- [Laravel Modules](https://github.com/nWidart/laravel-modules)
- [Metronic (premium theme)](http://keenthemes.com/metronic-theme/)

## Installation

- Add to your `composer.json`:
  ```
  "repositories": [
        {
            "type": "git",
            "url":  "https://gitlab.com/dark_reiser/Generator.git"
        }
    ],
  ```
- then run command
  ```
  composer require dark_reiser/Generator dev-master
  ```
- add to your `provider` inside `config/app.php`
  ```
  App\Generator\src\Providers\NvdCrudServiceProvider::class,
  ```
- publish configuration file and view templates
  ```
  php artisan vendor:publish
  ```

## Configuration

- Configuration theme
  ```
  config/crud.php
  ```
- Theme
  ```
  /resources/views/vendor/crud/templates
  ```

## Usage

- to generate CRUD
  ```
  fa:crud <tableName>
  ```

- to generate CRUD inside module
  ```
  fa:module-crud <moduleName> <tableName>
  ```
