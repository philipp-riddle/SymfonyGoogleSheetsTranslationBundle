<?php

namespace Phiil\GoogleSheetsTranslationBundle\Tests\Service;

use Phiil\GoogleSheetsTranslationBundle\Service\TranslationService;
use Phiil\GoogleSheetsTranslationBundle\Tests\Fixtures\PhiilSheetTestCase;

class TranslationServiceTest extends PhiilSheetTestCase
{
    public function testLocaleIsSupported_supportedLocales()
    {
        $service = $this->_getService();

        $this->assertTrue($service->localeIsSupported('en'), 'Translation service returned that locale "en" is not supported.');
        $this->assertTrue($service->localeIsSupported('de'), 'Translation service returned that locale "de" is not supported.');
    }

    public function testLocaleIsSupported_unsupportedLocales()
    {
        $service = $this->_getService();

        $this->assertFalse($service->localeIsSupported('cats'), 'Translation service returned that locale "cats" is supported.');
        $this->assertFalse($service->localeIsSupported('nomnom'), 'Translation service returned that locale "nomnom" is supported.');
    }

    public function testLocaleIsSupported_emptyLocale()
    {
        $service = $this->_getService();

        $this->assertFalse($service->localeIsSupported(''), 'Translation service returned that empty locale is supported.');
    }

    /**
     * HELPER FUNCTIONS TO EXECUTE THE TESTS
     */

    private function _getService(): TranslationService
    {
        return $this->get('phil_googlesheets_translations_bundle.translation_service');
    }
}
