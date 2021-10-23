<?php

namespace Phiil\GoogleSheetsTranslationBundle\Tests\Service;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;
use Phiil\GoogleSheetsTranslationBundle\Tests\Fixtures\PhiilTestCase;

class GoogleSheetsServiceTest extends PhiilTestCase
{
    private string $publicId1 = '1tPNKrSSPoQoJARdBfoBXgBPo-lEGe5U_7NutObXI2CI';
    private string $publicId2 = '1D2qOEgEKgMy7qh0B-PQMzdil8AoE5NvYMsNuusqM-IA';

    private int $moreLocalesSheet = 2;
    private int $emptySheet = 3;
    private int $noStringsSheet = 4;
    private int $skipRowSheet = 5;
    private int $skipColSheet = 6;
    private int $noLocalesSheet = 7;

    public function testParameters_sheetMode_number()
    {
        $service = $this->_getServiceWithParams($this->publicId1, 2);

        $this->assertTrue($service->getSheetMode() === GoogleSheetsService::SINGLE_SHEET_PAGE);
        $this->assertTrue($service->getSheetPage() === 2);
        
        $translations = $service->getTranslations(true);
        $this->assertCount(3, $service->getLocales());
        $this->assertCount(1, $translations['en']);

        $this->assertTrue(file_exists($this->projectDir . '/var/cache/translations/translations.json'), 'File didn\'t get exported');
    }

    public function testParameters_sheetMode_constant()
    {
        $service = $this->_getServiceWithParams($this->publicId1, GoogleSheetsService::ALL_SHEET_PAGES);
        $service->setPublicId($this->publicId2);
        $expected = [
            'en' => [
                'hello.world' => 'hello world!',
                'cats.cute' => 'cats are cute.',
            ],
            'de' => [
                'hello.world' => 'hallo welt!',
                'cats.cute' => 'katzen sind süß.',
            ],
        ];

        $this->assertTrue($service->getSheetMode() === GoogleSheetsService::ALL_SHEET_PAGES);
        $this->assertTrue($service->getSheetPage() === 1); // start page

        $translations = $service->getTranslations(true);
        $this->assertCount(2, $translations['en'], 'Merging went wrong with Sheetmode = ALL_SHEET_PAGES');
    }

    public function testParameters_sheetMode_default()
    {
        $service = $this->_getServiceWithParams($this->publicId1, '1');

        $this->assertTrue($service->getSheetMode() === GoogleSheetsService::SINGLE_SHEET_PAGE);
        $this->assertTrue($service->getSheetPage() === 1);

        $service = $this->_getServiceWithParams($this->publicId1, 'all');

        $this->assertTrue($service->getSheetMode() === GoogleSheetsService::ALL_SHEET_PAGES);
    }

    public function testParameters_sheetMode_invalid()
    {
        $this->expectException(\InvalidArgumentException::class, 'Entered a negative value for the sheet mode and the code did not throw any error.');
        $service = $this->_getServiceWithParams($this->publicId1, -1);
    }

    public function testParameters_publicId_default()
    {
        $service = $this->_getServiceWithParams($this->publicId1, 'all');

        $this->assertTrue($service->getPublicId() === $this->publicId1);
    }

    public function testParameters_publicId_invalid()
    {
        $this->expectException(\InvalidArgumentException::class, 'Entered an array for the publicId and the service didn\'t throw an error.');
        $service = $this->_getServiceWithParams(['test'], 'all');
    }

    public function testGetLocales_noPublicId()
    {
        $googleSheetsService = $this->_getService();
        $this->expectException(\InvalidArgumentException::class, 'Executed a get function without setting a public ID did not throw any error.');
        $googleSheetsService->getLocales(true);
    }

    public function testGetLocales_invalidPublicId()
    {
        $googleSheetsService = $this->_getService();
        $googleSheetsService->setPublicId('catsarecute');
        $this->expectException(\InvalidArgumentException::class, 'Executed a get function with an invalid public ID did not throw any error.');
        $googleSheetsService->getLocales(true);
    }

    public function testGetLocales_getTranslations_emptySheet()
    {
        $googleSheetsService = $this->_getService();

        $googleSheetsService->setPublicId($this->publicId1);
        $googleSheetsService->setSheetPage($this->emptySheet); // use the second sheet which is completely empty

        $this->assertCount(0, $googleSheetsService->getLocales(true));
        $this->assertCount(0, $googleSheetsService->getTranslations());
    }

    public function testGetLocales_noLocales()
    {
        $googleSheetsService = $this->_getService();

        $googleSheetsService->setPublicId($this->publicId1);
        $googleSheetsService->setSheetPage($this->noLocalesSheet);

        $this->assertCount(0, $googleSheetsService->getLocales(true)); // no locales => no trans strings!
        $this->assertCount(0, $googleSheetsService->getTranslations());
    }

    /**
     * Check both sheets if they return the languages that are expected
     * Force reload to ensure that the returned data is actually from the sheet we want to load
     */
    public function testGetLocales_default()
    {
        $googleSheetsService = $this->_getService();

        $expected = [3 => 'en', 4 => 'de'];
        $googleSheetsService->setPublicId($this->publicId1);
        $locales = $googleSheetsService->getLocales(true);
        $this->_checkLocalesArray($expected, $locales, $googleSheetsService);

        $expected = [3 => 'en', 4 => 'de', 5 => 'sp'];
        $googleSheetsService->setSheetPage($this->moreLocalesSheet);
        $locales = $googleSheetsService->getLocales(true);
        $this->_checkLocalesArray($expected, $locales, $googleSheetsService);
    }

    public function testGetLocales_forceReload_twoSheets()
    {
        $expectedLocales1 = [3 => 'en', 4 => 'de'];
        $googleSheetsService = $this->_getService();
        $googleSheetsService->setPublicId($this->publicId1);

        $locales1 = $googleSheetsService->getLocales(true);

        $googleSheetsService->setSheetPage($this->moreLocalesSheet);
        $locales2 = $googleSheetsService->getLocales(true);

        $this->assertNotEquals($locales1, $locales2, 'The force reload did not work - two different queries with force reload returned the same result');
    }

    public function testGetLocales_withoutForceReload_twoSheets()
    {
        $googleSheetsService = $this->_getService();

        $googleSheetsService->setPublicId($this->publicId1);
        $locales1 = $googleSheetsService->getLocales(true);

        $googleSheetsService->setSheetPage($this->moreLocalesSheet);
        $locales2 = $googleSheetsService->getLocales(false); // the locales should not change because data gets loaded out of the cache

        $this->assertEquals($locales1, $locales2, 'The force reload did not work.');
    }

    public function testGetTranslations_default()
    {
        $googleSheetsService = $this->_getService();

        $expected = [
            'en' => ['hello.world' => 'hello world!'],
            'de' => ['hello.world' => 'hallo welt!'],
        ];
        $googleSheetsService->setPublicId($this->publicId1);
        $translations = $googleSheetsService->getTranslations(true); // added a string as a distraction - should be ignored

        $this->assertCount(1, $translations['en'], 'The service returned more translations than expected.');
        $this->assertCount(1, $translations['en'], 'The service returned more translations than expected.');
        $this->assertEquals($expected, $translations, 'The service returned different translations than expected.');

        $this->assertTrue(file_exists($this->projectDir . '/var/cache/translations/translations.json'), 'File didn\'t get exported');
    }

    public function testGetTranslations_noTransStrings()
    {
        $googleSheetsService = $this->_getService();

        $googleSheetsService->setPublicId($this->publicId1);
        $googleSheetsService->setSheetPage($this->noStringsSheet);
        $translations = $googleSheetsService->getTranslations(true); // should be empty

        $this->assertCount(2, $googleSheetsService->getLocales(false), 'Locales array should be the size of 2');
        $this->assertCount(0, $translations, 'Translations array should be empty.');
    }

    public function testGetTranslations_skipRow()
    {
        $googleSheetsService = $this->_getService();

        $expected = [
            'en' => [
                'hello.world' => 'hello world!',
                'cats.cute' => 'cats are cute.',
            ],
            'de' => [
                'hello.world' => 'hallo welt!',
                'cats.cute' => 'katzen sind süß.',
            ],
        ];
        $googleSheetsService->setPublicId($this->publicId1);
        $googleSheetsService->setSheetPage($this->skipRowSheet);
        $translations = $googleSheetsService->getTranslations(true); // there are spaces in between the translations row-wise

        $this->assertCount(2, $translations['en'], 'The service returned more translations than expected.');
        $this->assertCount(2, $translations['en'], 'The service returned more translations than expected.');
        $this->assertEquals($expected, $translations, 'The service returned different translations than expected.');
    }

    public function testGetTranslations_skipCol()
    {
        $googleSheetsService = $this->_getService();

        $expected = [
            'en' => [
                'hello.world' => 'hello world!',
                'cats.cute' => 'cats are cute.',
            ],
            'de' => [
                'hello.world' => 'hallo welt!',
                'cats.cute' => 'katzen sind süß.',
            ],
        ];
        $googleSheetsService->setPublicId($this->publicId1);
        $googleSheetsService->setSheetPage($this->skipColSheet);
        $translations = $googleSheetsService->getTranslations(true); // in between en & de strings is one col as a distraction

        $this->assertCount(2, $translations['en'], 'The service returned more translations than expected.');
        $this->assertCount(2, $translations['en'], 'The service returned more translations than expected.');
        $this->assertEquals($expected, $translations, 'The service returned different translations than expected.');
    }

    public function testGetTranslations_sheetMode_allPages()
    {
        $googleSheetsService = $this->_getService();
        $googleSheetsService->setSheetMode(GoogleSheetsService::ALL_SHEET_PAGES);
        $googleSheetsService->setPublicId($this->publicId2);
        $expected = [
            'en' => [
                'hello.world' => 'hello world!',
                'cats.cute' => 'cats are cute.',
            ],
            'de' => [
                'hello.world' => 'hallo welt!',
                'cats.cute' => 'katzen sind süß.',
            ],
        ];

        $translations = $googleSheetsService->getTranslations(true); // there are two sheet pages - they should get merged

        $this->assertCount(2, $translations['en'], 'The service returned more translations than expected.');
        $this->assertCount(2, $translations['de'], 'The service returned more translations than expected.');
        $this->assertCount(2, $googleSheetsService->getLocales(), 'The service returned more locales than expected.');
        $this->assertEquals($expected, $translations, 'The returned translations did not match with the expected ones.');
    }

    /**
     * HELPER FUNCTIONS TO EXECUTE THE TESTS
     */

    private function _getService(): GoogleSheetsService
    {
        $service = $this->get('phil_googlesheets_translations_bundle.googlesheets_service');
        $service->setSheetMode(1);

        return $service;
    }

    private function _getServiceWithParams($riddleId, $mode)
    {
        return new GoogleSheetsService($riddleId, $mode, true, 'var/cache/translations', $this->projectDir);
    }

    private function _checkLocalesArray(array $expected, array $locales, GoogleSheetsService $googleSheetsService)
    {
        $this->assertCount(count($expected), $locales, 'The service returned more languages than expected (received: ' . count($locales) . ', expected : ' . count($expected) . ')');
        $this->assertTrue($expected === $locales, 'Didn\'t receive the languages that were expected (received: ' . var_export($locales, true) . ')');
        
        $cacheLocales = $googleSheetsService->getCache()->loadFromCache('locales');
        $this->assertEquals(json_decode($cacheLocales, true), $expected, 'the locales in the cache aren\'t equals to the expected ones.');
    }
}
