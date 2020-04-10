<?php

namespace Phiil\GoogleSheetsTranslationBundle\Service;

use Exception;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class GoogleSheetsService
{
    const ALL_SHEET_PAGES = 1;
    const SINGLE_SHEET_PAGE = 2;
    const MAX_SHEET_PAGES = 25;

    private $publicSheetsUrlPrefix = 'https://spreadsheets.google.com/feeds/cells/';
    private $publicSheetsUrlSuffix = '/public/full?alt=json';
    private $sheetMode; // have a look at ALL_SHEET_PAGES & MAX_SHEET_PAGES
    private $sheetPage = 1; // default - just load the first.
    private $publicId;

    private $sheetContent;
    private $translations;
    private $locales;

    private $container;
    private $cache;
    private $parser;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cache = new TranslationCacheService();
        $this->parser = new TranslationParser();

        $this->translations = [];
        $this->locales = [];
    }

    /**
     * Main function of this service.
     * Loads the translations and (if necessary) reloads the translations.
     * By default this service uses Symfony's cache to speed up these operations (especially if several languages are involved)
     *
     * @param $forceReload true if the cache values should be ignored and should be reloaded in any case
     */
    public function getTranslations(bool $forceReload = false) :array
    {
        if (!$forceReload && $this->_loadTranslationsFromCache()) {
            return $this->translations;
        }

        $this->_load();

        return $this->translations;
    }

    public function getLocales(bool $forceReload = true) :array
    {
        if (!$forceReload && $this->_loadLocalesFromCache()) {
            return $this->locales;
        }

        $this->_load();

        return $this->locales;
    }

    private function _load()
    {
        // get content from google
        if (empty($this->_loadContent())) {
            throw new Exception('Something went wrong - could not load the data from your GoogleSheet.');
        }

        // iterate over all the pages that have been queried
        foreach ($this->getSheetContent() as $sheetPage) {
            try {
                $this->parser->parseRawData($sheetPage); // parse it into a format we can use
            } catch (Exception $exc) {
                continue; // sheet is empty
            }
        }

        // get values from the parser
        $this->translations = $this->parser->getTranslations();
        $this->locales = $this->parser->getLocales();
        
        // save loaded values to the cache as JSON arrays
        $this->cache->saveCacheItemByName('translations', json_encode($this->translations));
        $this->cache->saveCacheItemByName('locales', json_encode($this->locales));
    }

    private function _loadContent()
    {
        return ($this->sheetContent = $this->_retrieveContents());
    }

    private function _loadTranslationsFromCache() :bool
    {
        if ($this->translations === null) {
            $this->translations = json_decode($this->cache->loadFromCache('translations'), true);
        }
        
        return $this->translations !== null;
    }

    private function _loadLocalesFromCache() :bool
    {
        if ($this->locales === null) {
            $this->locales = json_decode($this->cache->loadFromCache('locales'), true);
        }
        
        return $this->locales !== null;
    }

    /**
     * Gets the content from the GoogleSheet URL
     *
     * @return array empty array if something went wrong; array structure: [dataPage1, dataPage2, ..., datePageX]
     */
    private function _retrieveContents(): array
    {
        $sheetMode = $this->getSheetMode();
        $data = [];

        if (self::SINGLE_SHEET_PAGE === $sheetMode) {
            $this->_addPageContents($this->sheetPage, $data, true);
        } elseif (self::ALL_SHEET_PAGES === $sheetMode) {
            for ($i = 1; $i <= self::MAX_SHEET_PAGES; $i++) {
                if (!$this->_addPageContents($i, $data)) {
                    break; // this page doesn't exist
                }
            }
        } else {
            throw new InvalidArgumentException('Invalid sheet mode. Choose between SINGLE_SHEET_PAGE & ALL_SHEET_PAGES.');
        }
        
        return $data;
    }

    private function _addPageContents(int $pageId, array &$data, bool $exitOnError = false)
    {
        try {
            $this->setSheetPage($pageId);
            $contents = json_decode(\file_get_contents($this->getSheetsUrl()), true);
        } catch (InvalidArgumentException $exc) { // if the public ID is wrong
            throw $exc;
        } catch (Exception $exc) {
            if ($exitOnError) {
                throw new InvalidArgumentException('Invalid publicId (sheet could not be found).');
            }

            return false;
        }

        $contents = json_decode(\file_get_contents($this->getSheetsUrl()), true);

        if (!is_array($contents)) {
            return false;
        }

        $data[] = $contents;

        return true;
    }

    /**
     * GETTERS & SETTERS
     */

    public function getPublicId(): string
    {
        if ($this->publicId !== null) {
            return $this->publicId;
        }

        return $this->container->getParameter('googlesheets_translations.sheet.publicId');
    }

    public function setPublicId(string $publicId)
    {
        $this->publicId = $publicId;
    }

    public function getSheetMode(): int
    {
        if ($this->sheetMode !== null) {
            return $this->sheetMode;
        }

        $param = $this->container->getParameter('googlesheets_translations.sheet.mode');

        if ('all' === $param) {
            return self::ALL_SHEET_PAGES;
        }

        $pageId = intval($param);

        if (0 === $pageId) {
            throw new InvalidArgumentException('Invalid parameter choise - either enter "all" to load all sheet pages or an integer to define which sheet page should be loaded.');
        }

        $this->setSheetPage($pageId);

        return self::SINGLE_SHEET_PAGE;
    }

    public function setSheetPage(int $sheetPage)
    {
        $this->sheetPage = $sheetPage;
    }

    public function setSheetMode(int $sheetMode)
    {
        $this->sheetMode = $sheetMode;
    }

    /**
     * @throw InvalidArgumentException if the public ID hasn't been set at the time this function gets called
     */
    public function getSheetsUrl(): string
    {
        $publicId = $this->getPublicId();

        if (!$publicId || $publicId === '') {
            throw new InvalidArgumentException('Before trying to load your translations please set the publicId of your sheet.');
        }

        return $this->publicSheetsUrlPrefix . $this->getPublicId() . '/' . $this->sheetPage . $this->publicSheetsUrlSuffix;
    }

    public function getCache(): TranslationCacheService
    {
        return $this->cache;
    }

    public function getParser(): TranslationParser
    {
        return $this->parser;
    }

    public function getSheetContent(): array
    {
        return $this->sheetContent;
    }
}
