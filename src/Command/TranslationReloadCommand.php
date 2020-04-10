<?php

namespace Phiil\GoogleSheetsTranslationBundle\Command;

use Phiil\GoogleSheetsTranslationBundle\Service\GoogleSheetsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

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
        $this->googleSheetsService->getTranslations();
        return 0;
        $command = $this->getApplication()->find('translation:update');
        $arguments = [
            'command' => 'translation:update',
            '--force'  => true,
            '--clean' => true, // delete not found translations
            '--quiet' => true, // no output
            '--no-debug' => true,
        ];
        $locales = $this->googleSheetsService->getLocales();

        foreach ($locales as $locale) {
            $arguments['locale'] = $locale;
            $greetInput = new ArrayInput($arguments);
            $returnCode = $command->run($greetInput, $output);
        }

        return 0;
    }
}
