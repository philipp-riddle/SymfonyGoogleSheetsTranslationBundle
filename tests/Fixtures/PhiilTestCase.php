<?php

namespace Phiil\GoogleSheetsTranslationBundle\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Phiil\GoogleSheetsTranslationBundle\Tests\Fixtures\TestKernel;

class PhiilTestCase extends WebTestCase
{
    protected string $projectDir;

    protected function setUp(): void
    {
        self::$container = $this->getContainer();
        $this->projectDir = dirname(dirname(dirname(__FILE__)));
    }

    protected function getContainer()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        
        return $kernel->getContainer();
    }

    protected function get(string $serviceId)
    {
        return self::$container->get($serviceId);
    }
}
