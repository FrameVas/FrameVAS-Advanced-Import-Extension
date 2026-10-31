<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

use Espo\Core\FieldValidation\FieldValidationManager;
use Espo\Core\FieldValidation\FieldValidationParams;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Entity;
use stdClass;

/**
 * Used only inside an import run.
 *
 * The core import validates each row right after setting its values and before saving.
 * Relating by foreign fields happens at this point, so a required link passes validation and
 * a value that matches nothing is reported as a validation error of the row.
 */
class ImportFieldValidationManager extends FieldValidationManager
{
    private ?ForeignLinkResolver $advancedImportResolver = null;
    private ?ImportContext $advancedImportContext = null;

    public function setAdvancedImport(ForeignLinkResolver $resolver, ImportContext $context): void
    {
        $this->advancedImportResolver = $resolver;
        $this->advancedImportContext = $context;
    }

    public function processAll(Entity $entity, ?stdClass $data = null, ?FieldValidationParams $params = null): array
    {
        $failureList = [];

        if (
            $this->advancedImportResolver &&
            $this->advancedImportContext &&
            $entity instanceof CoreEntity
        ) {
            $failureList = $this->advancedImportResolver->process(
                $entity,
                $this->advancedImportContext->getAttributeList()
            );
        }

        return array_merge($failureList, parent::processAll($entity, $data, $params));
    }
}
