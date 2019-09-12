<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit062d5926127b1926af919f75716152af
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Mdanter\\Ecc\\' => 12,
        ),
        'F' => 
        array (
            'FG\\' => 3,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Mdanter\\Ecc\\' => 
        array (
            0 => __DIR__ . '/..',
        ),
        'FG\\' => 
        array (
            0 => __DIR__ . '/..',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit062d5926127b1926af919f75716152af::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit062d5926127b1926af919f75716152af::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
