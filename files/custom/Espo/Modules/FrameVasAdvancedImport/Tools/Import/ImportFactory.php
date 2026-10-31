<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

use Espo\Core\AclManager;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\FieldValidation\FieldValidationManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Log;
use Espo\Tools\Import\Import;
use Espo\Tools\Import\ImportFactory as CoreImportFactory;
use Throwable;

/**
 * Creates the core import with the advanced foreign-field processing plugged in.
 * The replacements are bound for the import instance only and don't affect the rest of the system.
 */
class ImportFactory extends CoreImportFactory
{
    public function __construct(
        private InjectableFactory $advancedInjectableFactory,
        private Log $log,
    ) {
        parent::__construct($advancedInjectableFactory);
    }

    public function create(): Import
    {
        try {
            return $this->createAdvanced();
        } catch (Throwable $e) {
            $this->log->error(
                'FrameVAS Advanced Import: falling back to the standard import. ' . $e->getMessage()
            );

            return parent::create();
        }
    }

    private function createAdvanced(): Import
    {
        $factory = $this->advancedInjectableFactory;

        $context = new ImportContext();
        $provider = $factory->create(ForeignFieldProvider::class);

        $resolver = $factory->createWithBinding(
            ForeignLinkResolver::class,
            BindingContainerBuilder::create()
                ->bindInstance(ForeignFieldProvider::class, $provider)
                ->build()
        );

        $aclManager = $factory->create(ImportAclManager::class);
        $aclManager->setAdvancedImportProvider($provider);

        $fieldValidationManager = $factory->create(ImportFieldValidationManager::class);
        $fieldValidationManager->setAdvancedImport($resolver, $context);

        $import = $factory->createWithBinding(
            AdvancedImport::class,
            BindingContainerBuilder::create()
                ->bindInstance(AclManager::class, $aclManager)
                ->bindInstance(FieldValidationManager::class, $fieldValidationManager)
                ->build()
        );

        $import->setAdvancedImportContext($context);

        return $import;
    }
}
