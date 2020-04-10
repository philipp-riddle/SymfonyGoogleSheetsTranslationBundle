<?php

namespace Phiil\GoogleSheetsTranslationBundle\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;

class TranslationLoader implements LoaderInterface
{
    private $locales;
    private $translations;

    public function __construct(GoogleSheetsService $sheetsService)
    {
        $this->sheetsService = $sheetsService;
        $this->locales = $this->sheetsService->getLocales();
        $this->translations = $this->sheetsService->getTranslations();
    }

    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);

        if (!in_array($locale, array_values($this->locales))) {
            return $catalogue;
        }

        $messages = [];

        foreach ($this->translations[$locale] as $translationString => $value) {
            $messages[$translationString] = $value;
        }

        $catalogue->add($messages, $domain);

        return $catalogue;
    }
}
