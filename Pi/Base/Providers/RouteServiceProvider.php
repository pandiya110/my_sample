<?php

namespace CodePi\Base\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

#use CodePi\Base\Providers\AppInstance;

class RouteServiceProvider extends ServiceProvider {

    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'CodePi';

    // protected $webNamespace = 'App\Http\Controllers\Web';
    // protected $apiNamespace = 'App\Http\Controllers\Api';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param \Illuminate\Routing\Router $router        	
     *
     * @return void
     */
    public function boot() {
        //
        //config()->set('poet.appinstanceid',AppInstance::getAppInstanceId());
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router        	
     *
     * @return void
     */
    public function map(Router $router) {
       
        /*
         * |--------------------------------------------------------------------------
         * | Web Router
         * |--------------------------------------------------------------------------
         */

        /*
         * $router->group(['namespace' => $this->webNamespace], function ($router) {
         * require app_path('Http/routes.web.php');
         * });
         */
        /*
         * |--------------------------------------------------------------------------
         * | Api Router
         * |--------------------------------------------------------------------------
         */

        /*
         * $router->group(['namespace' => $this->apiNamespace], function ($router) {
         * require app_path('Http/routes.api.php');
         * });
         */
        $router->group([
        'namespace' => $this->namespace
        ], function ($router) {

        //require app_path ( 'Http/routes.php' );

        $basePath = base_path('Pi');
        $folders = scandir($basePath);
        foreach ($folders as $key => $folder) {
        if (file_exists($basePath . '/' . $folder . '/Http/Routes/routes.php')) {

        require $basePath . '/' . $folder . '/Http/Routes/routes.php';
        }

        }
        });

        

    }
    
    

}
