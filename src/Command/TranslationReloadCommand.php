<?php

namespace Phiil\GoogleSheetsTranslationBundle\Command;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;
use Phiil\GoogleSheetsTranslationBundle\Service\TranslationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

class TranslationReloadCommand extends Command
{
    protected static $defaultName = 'phiil:translation:reload';

    private GoogleSheetsService $googleSheetsService;
    private TranslationService $translationService;

    public function __construct(GoogleSheetsService $googleSheetsService, TranslationService $translationService)
    {
        $this->googleSheetsService = $googleSheetsService;
        $this->translationService = $translationService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Reloads all the translations from your chosen GoogleSheet.')
            ->setHelp('Usage: ... phiil:translation:reload --exclude=de,en')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GoogleSheetsTranslationBundle: Reloading translations');

        try {
            $locales = $this->googleSheetsService->getLocales(true);
            $io->section('Fetching languages from your GoogleSheet & executing file operations to prepare everything for the TranslationLoader...');
        } catch (\Exception $exc) {
            $io->error(sprintf('Something went wrong: %s', $exc->getMessage()));

            return 1;
        }
        
        $setupLog = $this->setupTranslationDirectory($locales);

        if (!empty($setupLog)) {
            foreach ($setupLog as $logItem) {
                $io->text($logItem);
            }
        }

        $io->info('Fetching translations from the Google Sheet...');
        $translations = $this->googleSheetsService->getTranslations(true);
        $io->text('Done.');

        $io->info('Updating symfony translation data & files...');
        $this->translationService->update($locales);
        $io->text('Done.');

        $io->success('Reloaded your translations successfully.');
        $io->text(\sprintf('Translation Count: %d', \count($translations[0])));
        $io->text(\sprintf('Locale Count: %d', \count($locales)));

        return 0;
    }

    /**
     * Create the ./translations directory if it doesn't exist and touch files to prepare the translation loader
     */
    private function setupTranslationDirectory(array $locales) :array
    {
        $filesystem = new Filesystem();
        $log = [];

        $this->createTranslationsDirectory($filesystem, $log);
        $this->touchStandardFiles($filesystem, $locales, $log);
        
        return $log;
    }

    /**
     * Make sure that the translations directory exists
     */
    private function createTranslationsDirectory(Filesystem $filesystem, array &$log, string $directoryName = 'translations')
    {
        if (!$filesystem->exists('./' . $directoryName)) {
            $filesystem->mkdir('./' . $directoryName);
            $log[] = 'Created ./translations directory.';
        }
    }

    /**
     * If these files don't exist the translation loader doesn't work
     */
    private function touchStandardFiles(FileSystem $filesystem, array $locales, array &$log)
    {
        foreach ($locales as $index => $locale) {
            $fileName = './translations/messages.' . $locale . '.gs_trans';

            if (!$filesystem->exists($fileName)) {
                $filesystem->touch('./translations/messages.' . $locale . '.gs_trans');
                $log[] = sprintf('Touched %s.', $fileName);
            }
        }
    }
}
