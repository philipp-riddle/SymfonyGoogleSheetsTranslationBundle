<?php

namespace Phiil\GoogleSheetsTranslationBundle\Tests\Fixtures;

class PhiilSheetTestCase extends PhiilTestCase
{
    protected function getContainer()
    {
        $kernel = new TestSheetKernel('test', true);
        $kernel->boot();
        
        return $kernel->getContainer();
    }
}
