<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

/**
 * State shared between the parts of one import run.
 */
class ImportContext
{
    /** @var string[] */
    private array $attributeList = [];

    /**
     * @param string[] $attributeList
     */
    public function setAttributeList(array $attributeList): void
    {
        $this->attributeList = array_values(
            array_filter($attributeList, fn ($item) => is_string($item) && $item !== '')
        );
    }

    /**
     * @return string[]
     */
    public function getAttributeList(): array
    {
        return $this->attributeList;
    }
}
