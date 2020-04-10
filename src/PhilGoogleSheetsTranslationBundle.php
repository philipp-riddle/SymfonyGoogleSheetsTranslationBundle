<?php

namespace Phiil\GoogleSheetsTranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Phiil\GoogleSheetsTranslationBundle\DependencyInjection\PhilGoogleSheetsTranslationsBundleExtension;

class PhilGoogleSheetsTranslationBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new PhilGoogleSheetsTranslationsBundleExtension();
    }
}
