<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInited17e532aa9e766e4256c6bd2ff6b4b2
{
    public static $prefixesPsr0 = array (
        'O' => 
        array (
            'OfxParser' => 
            array (
                0 => __DIR__ . '/..' . '/asgrim/ofxparser/lib',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInited17e532aa9e766e4256c6bd2ff6b4b2::$prefixesPsr0;
            $loader->classMap = ComposerStaticInited17e532aa9e766e4256c6bd2ff6b4b2::$classMap;

        }, null, ClassLoader::class);
    }
}
