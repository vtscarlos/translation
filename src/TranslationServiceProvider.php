<?php namespace Vtscarlos\Translation;

use Illuminate\Translation\FileLoader as LaravelFileLoader;
use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;
use Vtscarlos\Translation\Cache\RepositoryFactory as CacheRepositoryFactory;
use Vtscarlos\Translation\Commands\CacheFlushCommand;
use Vtscarlos\Translation\Commands\FileLoaderCommand;
use Vtscarlos\Translation\Loaders\CacheLoader;
use Vtscarlos\Translation\Loaders\DatabaseLoader;
use Vtscarlos\Translation\Loaders\FileLoader;
use Vtscarlos\Translation\Loaders\MixedLoader;
use Vtscarlos\Translation\Middleware\TranslationMiddleware;
use Vtscarlos\Translation\Models\Translation;
use Vtscarlos\Translation\Repositories\LanguageRepository;
use Vtscarlos\Translation\Repositories\TranslationRepository;
use Vtscarlos\Translation\Routes\ResourceRegistrar;

class TranslationServiceProvider extends LaravelTranslationServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/translator.php' => config_path('translator.php'),
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translator.php', 'translator');

        parent::register();
        $this->registerCacheRepository();
        $this->registerFileLoader();
        $this->registerCacheFlusher();
        $this->app->singleton('translation.uri.localizer', UriLocalizer::class);
        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware('localize', TranslationMiddleware::class);
        // Fix issue with laravel prepending the locale to localize resource routes:
        $this->app->bind('Illuminate\Routing\ResourceRegistrar', ResourceRegistrar::class);
    }

    /**
     *  IOC alias provided by this Service Provider.
     *
     *  @return array
     */
    public function provides()
    {
        return array_merge(parent::provides(), ['translation.cache.repository', 'translation.uri.localizer', 'translation.loader']);
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $app = $this->app;
        $this->app->singleton('translation.loader', function ($app) {
            $source        = $app['config']->get('translator.source');
            $defaultLocale = $app['config']->get('app.locale');
            $loader        = null;
            switch ($source) {
                case 'mixed':
                    $laravelFileLoader = new LaravelFileLoader($app['files'], $app->basePath() . '/resources/lang');
                    $fileLoader        = new FileLoader($defaultLocale, $laravelFileLoader);
                    $databaseLoader    = new DatabaseLoader($defaultLocale, $app->make(TranslationRepository::class));
                    $loader            = new MixedLoader($defaultLocale, $fileLoader, $databaseLoader);
                    break;
                case 'mixed_db':
                    $laravelFileLoader = new LaravelFileLoader($app['files'], $app->basePath() . '/resources/lang');
                    $fileLoader        = new FileLoader($defaultLocale, $laravelFileLoader);
                    $databaseLoader    = new DatabaseLoader($defaultLocale, $app->make(TranslationRepository::class));
                    $loader            = new MixedLoader($defaultLocale, $databaseLoader, $fileLoader);
                    break;
                case 'database':
                    $loader = new DatabaseLoader($defaultLocale, $app->make(TranslationRepository::class));
                    break;
                default:case 'files':
                    $laravelFileLoader = new LaravelFileLoader($app['files'], $app->basePath() . '/resources/lang');
                    $loader            = new FileLoader($defaultLocale, $laravelFileLoader);
                    break;
            }
            if ($app['config']->get('translator.cache.enabled')) {
                //$cacheStore      = $app['cache']->getStore();
                //$cacheRepository = CacheRepositoryFactory::make($cacheStore, $app['config']->get('translator.cache.suffix'));
                $loader = new CacheLoader($defaultLocale, $app['translation.cache.repository'], $loader, $app['config']->get('translator.cache.timeout'));
            }
            return $loader;
        });
    }

    /**
     *  Register the translation cache repository
     *
     *  @return void
     */
    public function registerCacheRepository()
    {
        $this->app->singleton('translation.cache.repository', function ($app) {
            $cacheStore = $app['cache']->getStore();
            return CacheRepositoryFactory::make($cacheStore, $app['config']->get('translator.cache.suffix'));
        });
    }

    /**
     * Register the translator:load language file loader.
     *
     * @return void
     */
    protected function registerFileLoader()
    {
        $app                   = $this->app;
        $defaultLocale         = $app['config']->get('app.locale');
        $languageRepository    = $app->make(LanguageRepository::class);
        $translationRepository = $app->make(TranslationRepository::class);
        $translationsPath      = $app->basePath() . '/resources/lang';
        $command               = new FileLoaderCommand($languageRepository, $translationRepository, $app['files'], $translationsPath, $defaultLocale);

        $this->app['command.translator:load'] = $command;
        $this->commands('command.translator:load');
    }

    /**
     *  Flushes the translation cache
     *
     *  @return void
     */
    public function registerCacheFlusher()
    {
        //$cacheStore      = $this->app['cache']->getStore();
        //$cacheRepository = CacheRepositoryFactory::make($cacheStore, $this->app['config']->get('translator.cache.suffix'));
        $command = new CacheFlushCommand($this->app['translation.cache.repository'], $this->app['config']->get('translator.cache.enabled'));

        $this->app['command.translator:flush'] = $command;
        $this->commands('command.translator:flush');
    }
}
