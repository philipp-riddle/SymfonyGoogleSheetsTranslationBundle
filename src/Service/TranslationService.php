<?php

namespace Phiil\GoogleSheetsTranslationBundle\Service;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;
use Phiil\GoogleSheetsTranslationBundle\Translation\TranslationLoader;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Writer\TranslationWriter;

class TranslationService
{
    protected GoogleSheetsService $sheetsService;
    protected KernelInterface $kernel;

    public function __construct(GoogleSheetsService $sheetsService, KernelInterface $kernel)
    {
        $this->sheetsService = $sheetsService;
        $this->kernel = $kernel;
    }

    public function localeIsSupported(string $locale): bool
    {
        return in_array($locale, array_values($this->sheetsService->getLocales()));
    }

    /**
     * Uses symfony classes to dump the translations to a file.
     */
    public function update(array $locales, array $projects = ['messages'], bool $clearCache = true): void
    {
        $translationsDir = $this->kernel->getProjectDir().'/translations';
        $loader = new TranslationLoader($this->sheetsService);
        $writer = new TranslationWriter();
        $writer->addDumper('xlf', new XliffFileDumper());

        foreach ($projects as $project) {
            foreach ($locales as $locale) {
                $locale = $this->remapLocale($locale); // if we write the file without the remapped locale - BOOM! Exceptions, exceptions, ...
                $catalogue = $loader->load([], $locale, $project);
                $writer->write($catalogue, 'xlf', ['path' => $translationsDir]);

                if ($clearCache) {
                    $this->translationCacheService->clearSymfonyTranslationsCache($locale);
                }
            }
        }
    }

    /**
     * This adds support for languages with a ':' in it.
     */
    public function remapLocale(string $locale): string
    {
        $replaces = [
            ':' => '_',
        ];

        foreach ($replaces as $search => $replace) {
            $locale = \str_replace($search, $replace, $locale);
        }

        return $locale;
    }
}
