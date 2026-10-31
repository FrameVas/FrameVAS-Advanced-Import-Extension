<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

use Espo\Tools\Import\Import;

/**
 * The core import, additionally remembering the column mapping for the foreign-field processing.
 */
class AdvancedImport extends Import
{
    private ?ImportContext $advancedImportContext = null;

    public function setAdvancedImportContext(ImportContext $context): void
    {
        $this->advancedImportContext = $context;
    }

    /**
     * @param string[] $attributeList
     */
    public function setAttributeList(array $attributeList): self
    {
        $this->advancedImportContext?->setAttributeList($attributeList);

        parent::setAttributeList($attributeList);

        return $this;
    }
}
