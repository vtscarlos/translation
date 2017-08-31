<?php namespace Vtscarlos\Translation\Test\Loaders;

use Vtscarlos\Translation\Loaders\DatabaseLoader;
use Vtscarlos\Translation\Repositories\TranslationRepository;
use Vtscarlos\Translation\Test\TestCase;
use \Mockery;

class DatabaseLoaderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->translationRepository = \App::make(TranslationRepository::class);
        $this->loader                = new DatabaseLoader('es', $this->translationRepository);
    }

    public function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_returns_from_database()
    {
        $expected = [
            'simple' => 'text',
            'array'  => [
                'item'   => 'item',
                'nested' => [
                    'item' => 'nested',
                ],
            ],
        ];
        $translation = $this->translationRepository->create([
            'locale'    => 'es',
            'namespace' => '*',
            'group'     => 'group',
            'item'      => 'simple',
            'text'      => 'text',
        ]);
        $translation = $this->translationRepository->create([
            'locale'    => 'es',
            'namespace' => '*',
            'group'     => 'group',
            'item'      => 'array.item',
            'text'      => 'item',
        ]);
        $translation = $this->translationRepository->create([
            'locale'    => 'es',
            'namespace' => '*',
            'group'     => 'group',
            'item'      => 'array.nested.item',
            'text'      => 'nested',
        ]);
        $translations = $this->loader->loadSource('es', 'group');
        $this->assertEquals($expected, $translations);
    }
}
