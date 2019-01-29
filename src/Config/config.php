<?php

$config = [

    /*
     * Views that will be generated. If you wish to add your own view,
     * make sure to create a template first in the
     * '/resources/views/crud-templates/views' directory.
     * */
    'views' => [
        'index',
        // 'show',
        'form',
        // 'create',
        // 'edit',
    ],

    /*
     * Directory containing the templates
     * If you want to use your custom templates, specify them here
     * */
    'templates' => 'vendor.crud.metronic4-templates',
    'core_templates' => 'vendor.crud.core-metronic-templates',
    'modules_templates' => 'vendor.crud.metronic4-templates',
    // 'templates' => 'vendor.crudOry',
    // 'templates' => 'app.Generator.src.single-page-templates',

];

    /*
     * Layout template used when generating views
     * */
    $config['layout'] = $config['templates'].'.common.app';

return $config;
