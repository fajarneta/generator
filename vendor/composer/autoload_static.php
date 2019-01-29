<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit68398f53d0747174aa7854af0e26e476
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\Generator\\src\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\Generator\\src\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'App\\Generator\\src\\Column' => __DIR__ . '/../..' . '/src/Column.php',
        'App\\Generator\\src\\Commands\\Crud' => __DIR__ . '/../..' . '/src/Commands/Crud.php',
        'App\\Generator\\src\\Commands\\ModuleCrud' => __DIR__ . '/../..' . '/src/Commands/ModuleCrud.php',
        'App\\Generator\\src\\Db' => __DIR__ . '/../..' . '/src/Db.php',
        'App\\Generator\\src\\DbOracle' => __DIR__ . '/../..' . '/src/DbOracle.php',
        'App\\Generator\\src\\Form' => __DIR__ . '/../..' . '/src/Form.php',
        'App\\Generator\\src\\Html' => __DIR__ . '/../..' . '/src/Html.php',
        'App\\Generator\\src\\Providers\\NvdCrudServiceProvider' => __DIR__ . '/../..' . '/src/Providers/NvdCrudServiceProvider.php',
        'App\\Generator\\src\\Table' => __DIR__ . '/../..' . '/src/Table.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit68398f53d0747174aa7854af0e26e476::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit68398f53d0747174aa7854af0e26e476::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit68398f53d0747174aa7854af0e26e476::$classMap;

        }, null, ClassLoader::class);
    }
}
