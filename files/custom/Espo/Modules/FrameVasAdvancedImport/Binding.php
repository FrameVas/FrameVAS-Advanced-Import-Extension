<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;
use Espo\Modules\FrameVasAdvancedImport\Tools\Import\ImportFactory;
use Espo\Tools\Import\ImportFactory as CoreImportFactory;

/**
 * Replaces the core import factory, so every import run (interactive, idle/background
 * and API) goes through the advanced foreign-field processing.
 */
class Binding implements BindingProcessor
{
    public function process(Binder $binder): void
    {
        $binder->bindImplementation(CoreImportFactory::class, ImportFactory::class);
    }
}
