<?php

namespace Phiil\GoogleSheetsTranslationBundle\Command;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class TranslationReloadCommand extends Command
{
    protected static $defaultName = 'phiil:translation:reload';

    private $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;

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
        
        $setupLog = $this->_setupTranslationDirectory($locales);

        if (!empty($setupLog)) {
            foreach ($setupLog as $logItem) {
                $io->text($logItem);
            }
        }

        $command = $this->getApplication()->find('translation:update');
        $arguments = [
            'command' => 'translation:update',
            '--force'  => true,
        ];

        $translations = $this->googleSheetsService->getTranslations();
        $translationsTotal = 0;
        $success = true;

        $io->text(sprintf('Found %s languages: %s', count($locales), implode(', ', $locales)));
        $io->section('Updating translation strings...');

        foreach ($locales as $locale) {
            $arguments['locale'] = $locale;
            $greetInput = new ArrayInput($arguments);
            $returnCode = $command->run($greetInput, new NullOutput());

            if ($returnCode === 0) {
                $translationsTotal += count($translations[$locale]);
                $io->text(sprintf('++ %s translations for %s', count($translations[$locale]), $locale));
            } else {
                $success = false;
                $io->warning(sprintf('!! Error while updating translations for %s: Please try again in a few seconds.', $locale));
            }
        }

        if ($success) {
            $io->success(sprintf('Yay, a total success! Added %s translations in total.', $translationsTotal));

            return 0;
        } 
        
        $io->error(sprintf('Uh oh. Something went wrong. Please try again in a few seconds (updated translations: %s)', $translationsTotal));

        return 0;
    }

    /**
     * Create the ./translations directory if it doesn't exist and touch files to prepare the translation loader
     */
    private function _setupTranslationDirectory(array $locales) :array
    {
        $filesystem = new Filesystem();
        $log = [];

        $this->_createTranslationsDirectory($filesystem, $log);
        $this->_touchStandardFiles($filesystem, $locales, $log);
        
        return $log;
    }

    /**
     * Make sure that the translations directory exists
     */
    private function _createTranslationsDirectory(Filesystem $filesystem, array &$log, string $directoryName = 'translations')
    {
        if (!$filesystem->exists('./' . $directoryName)) {
            $filesystem->mkdir('./' . $directoryName);
            $log[] = 'Created ./translations directory.';
        }
    }

    /**
     * If these files don't exist the translation loader doesn't work
     */
    private function _touchStandardFiles(FileSystem $filesystem, array $locales, array &$log)
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
