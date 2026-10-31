<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

/**
 * A foreign field of an imported entity type that can be used to relate records on import.
 *
 * Example: Employee.departmentCode -> link `department` -> Department.idDepartment1C.
 */
final class ForeignField
{
    public function __construct(
        /** The foreign field name on the imported entity type (also the attribute name). */
        public readonly string $field,
        /** The belongs-to link the foreign field goes through. */
        public readonly string $link,
        /** The related entity type. */
        public readonly string $foreignEntityType,
        /** The field of the related entity type. */
        public readonly string $foreignField,
        /** The ORM attribute of the related entity type used for matching. */
        public readonly string $foreignAttribute,
    ) {}

    public function getIdAttribute(): string
    {
        return $this->link . 'Id';
    }
}
