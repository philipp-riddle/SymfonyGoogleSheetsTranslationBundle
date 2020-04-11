<?php

namespace Phiil\GoogleSheetsTranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Phiil\GoogleSheetsTranslationBundle\DependencyInjection\PhiilGoogleSheetsTranslationsBundleExtension;

class PhiilGoogleSheetsTranslationBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new  PhiilGoogleSheetsTranslationsBundleExtension();
    }
}
