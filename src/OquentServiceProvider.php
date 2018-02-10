<?php

namespace Erdemozveren\Oquent;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
class OquentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app['db']->extend('orientdb', function($config) {
            return new Connection($config);
        });
        AliasLoader::getInstance()->alias('Oquent\Model','Erdemozveren\Oquent\Model');
        AliasLoader::getInstance()->alias('Oquent\Record','PhpOrient\Protocols\Binary\Data\Record');
        AliasLoader::getInstance()->alias('Oquent\ID','PhpOrient\Protocols\Binary\Data\ID');
        AliasLoader::getInstance()->alias('Oquent\QueryException','Erdemozveren\Oquent\QueryException');
        AliasLoader::getInstance()->alias('Oquent\Schema\OClass','Erdemozveren\Oquent\Schema\OClass');
        AliasLoader::getInstance()->alias('Oquent\Query','Erdemozveren\Oquent\Query');
    }
}
