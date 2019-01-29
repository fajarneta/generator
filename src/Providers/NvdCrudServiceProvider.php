<?php

namespace App\Generator\src\Providers;

use Illuminate\Support\ServiceProvider;
use App\Generator\src\Commands\Crud;
use App\Generator\src\Commands\ModuleCrud;

class NvdCrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // echo "tes";
        $this->commands([Crud::class]);
        $this->commands([ModuleCrud::class]);
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('crud.php'),
            __DIR__.'/../metronic-templates' => base_path('resources/views/vendor/crud/metronic-templates'),
            __DIR__.'/../metronic4-templates' => base_path('resources/views/vendor/crud/metronic4-templates'),
            __DIR__.'/../core-metronic-templates' => base_path('resources/views/vendor/crud/core-metronic-templates'),
            __DIR__.'/../classic-templates' => base_path('resources/views/vendor/crud/classic-templates'),
            __DIR__.'/../single-page-templates' => base_path('resources/views/vendor/crud/single-page-templates'),
        ],'nvd');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
