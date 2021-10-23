<?php

namespace Phiil\GoogleSheetsTranslationBundle\Service;

use Exception;

/**
 * This service parses the GoogleSheets array to an array of translations and locales.
 */

class TranslationParser
{
    private array $entries;
    private array $locales;
    private array $translations;

    public function __construct()
    {
        $this->entries = [];
        $this->locales = [];
        $this->translations = [];
    }

    public function parseRawData(array $data)
    {
        if (!$this->_loadEntries($data)) {
            throw new Exception('Could not load entries.');
        }

        $this->_loadLocales();
        $this->_loadTranslations();
    }

    /**
     * This function loads the translations.
     * The translations array has the following structure:
     * e.g.:
     *
     * [
     *  en: ['hello_world': 'hello world!', ...]
     *  de: ['hello_world': 'hallo welt!', ...]
     * ]
     *
     * @param $rowOffset defines how many rows the code should skip; default: 1 => skip the sheet head where name, locales & much more gets defined
     */
    private function _loadTranslations(int $rowOffset = 1)
    {
        $currentTranslationName = '';

        foreach ($this->entries as $entry) {
            $data = $entry['gs$cell'];
            $row = intval($data['row']);
            $col = intval($data['col']);
            $value = $data['$t'];

            if ($rowOffset >= $row) { // there are no translations in the sheet head (name, comments, locales, ... in first row)
                continue;
            }

            if (1 === $col) { // first col = translation string name
                $currentTranslationName = $value;
                continue;
            }

            if (!isset($this->locales[$col])) { // Locale not supported: Out of reach for us since there's no locale in that col; ignore this column
                continue;
            }

            $this->translations[$this->locales[$col]][$currentTranslationName] = $value;
        }
    }

    /**
     * This function gets the locales out of the content delivered from the GoogleSheet.
     *
     * The locale array has the following structure:
     *  - col index => locale
     *
     * The col index helps us to understand which locale we're currently processing in the _loadTranslations() method
     */
    private function _loadLocales(int $rowOffset = 1, int $colOffset = 3)
    {
        for ($i = $colOffset - 1; $i < count($this->entries); $i++) {
            $data = $this->entries[$i]['gs$cell'];
            $row = intval($data['row']);
            $col = intval($data['col']);

            if (1 !== $row) { // Nur in der ersten Zeile stehen die Locales
                break;
            }

            $this->locales[$col] = $data['$t'];
        }
    }

    /**
     * The relevant data is located inside $content['feed']['entry']
     *
     * @return bool false if the entries could not be loaded due to a faulty content array
     */
    private function _loadEntries(array $content) :bool
    {
        if (isset($content['feed']['entry'])) {
            $this->entries = $content['feed']['entry'];
        }

        return !empty($this->entries);
    }

    /**
     * GETTERS
     */

    public function getLocales() :array
    {
        return $this->locales;
    }

    public function getTranslations() :array
    {
        return $this->translations;
    }
}
