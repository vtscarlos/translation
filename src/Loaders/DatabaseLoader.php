<?php namespace Vtscarlos\Translation\Loaders;

use Illuminate\Contracts\Translation\Loader as LoaderContract;
use Vtscarlos\Translation\Repositories\TranslationRepository;

class DatabaseLoader extends Loader implements LoaderContract
{
    /**
     *  The default locale.
     *  @var string
     */
    protected $defaultLocale;

    /**
     *  Translations repository.
     *  @var \Vtscarlos\Translation\Repositories\TranslationRepository
     */
    protected $translationRepository;

    /**
     *  Create a new mixed loader instance.
     *
     *  @param  string                                                  $defaultLocale
     *  @param  \Vtscarlos\Translation\Repositories\TranslationRepository   $translationRepository
     */
    public function __construct($defaultLocale, TranslationRepository $translationRepository)
    {
        parent::__construct($defaultLocale);
        $this->translationRepository = $translationRepository;
    }

    /**
     *  Load the messages strictly for the given locale.
     *
     *  @param  string  $locale
     *  @param  string  $group
     *  @param  string  $namespace
     *  @return array
     */
    public function loadSource($locale, $group, $namespace = '*')
    {
        $dotArray = $this->translationRepository->loadSource($locale, $namespace, $group);
        $undot    = [];
        foreach ($dotArray as $item => $text) {
            array_set($undot, $item, $text);
        }
        return $undot;
    }

    /**
     *  Add a new namespace to the loader.
     *
     *  @param  string  $namespace
     *  @param  string  $hint
     *  @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }
}
