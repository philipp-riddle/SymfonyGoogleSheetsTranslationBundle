<?php

namespace Phiil\GoogleSheetsTranslationBundle\Service;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;

class TranslationService
{
    private $sheetsService;

    public function __construct(GoogleSheetsService $sheetsService)
    {
        $this->sheetsService = $sheetsService;
    }

    public function localeIsSupported($locale)
    {
        return in_array($locale, array_values($this->sheetsService->getLocales()));
    }

    public function trans($string)
    {
        return $this->translator->trans($string);
    }
}
