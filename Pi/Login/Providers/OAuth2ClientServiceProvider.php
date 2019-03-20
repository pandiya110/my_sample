<?php

namespace CodePi\Login\Providers;

use Illuminate\Support\ServiceProvider;
use CodePi\Login\Providers\OAuthClientProvider,
    Config;

class OAuth2ClientServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        //
        $this->app->singleton('oauth2cli', function () {
            return new OAuthClientProvider([
                'clientId' => Config::get('app.iviessoClientId'), // The client ID assigned to you by the provider
                'clientSecret' =>Config::get('app.iviessoClientSecret'), // The client password assigned to you by the provider
                'redirectUri' =>Config::get('app.iviessoRedirectUri'),
            ]);
        });
    }

}
