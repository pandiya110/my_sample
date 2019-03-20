<?php 
namespace CodePi\Base\Providers;

use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;
class LangServiceProvider extends LaravelTranslationServiceProvider
{
    
    public function boot()
    {
        //
        
//        $namespace = 'custom';
//        $path = base_path('Pi').'/Base/Lang';
//        \Lang::addNamespace($namespace, $path);
        
        $basePath = base_path('Pi');
        $folders = scandir($basePath);
        foreach ($folders as $key => $folder) {
            if (is_dir($basePath . '/' . $folder . '/Lang/')) {
               $namespace = $folder;
               $path =$basePath . '/' . $folder . '/Lang';
               $this->loadTranslationsFrom( $path, $namespace);
            }
        }
        
    }
    
}
