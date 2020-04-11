<?php

namespace Phiil\GoogleSheetsTranslationBundle\Service;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationService
{
    protected $sheetsService;
    protected $translator;

    public function __construct(GoogleSheetsService $sheetsService)
    {
        $this->sheetsService = $sheetsService;
    }

    public function localeIsSupported(string $locale): bool
    {
        return in_array($locale, array_values($this->sheetsService->getLocales()));
    }
}
