<?php

namespace VesninAndrey\Shopify;

use Config;
use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package( 'vesninandrey/laravel-shopify' );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booting( function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias( 'Shopify', 'VesninAndrey\Shopify\Facades\Shopify' );
        } );

        $this->app['shopify'] = $this->app->share( function ( $app ) {
            $config = [
                'apikey'       => Config::get( 'laravel-shopify::apikey' ),
                'apisecret'    => Config::get( 'laravel-shopify::apisecret' ),
                'password'     => Config::get( 'laravel-shopify::password' ),
                'domain'       => Config::get( 'laravel-shopify::domain' ),
                'access_token' => Config::get( 'laravel-shopify::access_token' )
            ];

            return new Shopify( $config );
        } );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array( 'shopify' );
    }

}