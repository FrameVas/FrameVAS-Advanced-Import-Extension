<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

/**
 * Finds foreign fields that can be used to relate records on import.
 * Works for any entity type, including custom ones, as it relies on metadata only.
 */
class ForeignFieldProvider
{
    /** Used if the metadata is missing. Keep in sync with `app.frameVasAdvancedImport`. */
    private const DEFAULT_FIELD_TYPE_LIST = [
        'varchar',
        'int',
        'float',
        'number',
        'enum',
        'enumInt',
        'enumFloat',
        'url',
        'email',
        'phone',
    ];

    /** @var array<string, ForeignField[]> */
    private array $cache = [];

    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
    ) {}

    /**
     * @return ForeignField[]
     */
    public function getList(string $entityType): array
    {
        if (!array_key_exists($entityType, $this->cache)) {
            $this->cache[$entityType] = $this->build($entityType);
        }

        return $this->cache[$entityType];
    }

    /**
     * @return ForeignField[]
     */
    private function build(string $entityType): array
    {
        $ormDefs = $this->entityManager->getDefs();

        if (!$ormDefs->hasEntity($entityType)) {
            return [];
        }

        $entityDefs = $ormDefs->getEntity($entityType);

        /** @var array<string, mixed> $fieldDefsMap */
        $fieldDefsMap = $this->metadata->get(['entityDefs', $entityType, 'fields']) ?? [];

        $typeList = $this->getSupportedTypeList();

        $list = [];

        foreach ($fieldDefsMap as $field => $defs) {
            $field = (string) $field;

            if (!is_array($defs) || ($defs['type'] ?? null) !== 'foreign') {
                continue;
            }

            if (!empty($defs['disabled']) || !empty($defs['importDisabled'])) {
                continue;
            }

            $link = $defs['link'] ?? null;
            $foreignField = $defs['field'] ?? null;

            if (!is_string($link) || $link === '' || !is_string($foreignField) || $foreignField === '') {
                continue;
            }

            $linkFieldDefs = $fieldDefsMap[$link] ?? null;

            if (
                !is_array($linkFieldDefs) ||
                !empty($linkFieldDefs['disabled']) ||
                !empty($linkFieldDefs['importDisabled'])
            ) {
                continue;
            }

            if (!$entityDefs->hasRelation($link) || !$entityDefs->hasAttribute($field)) {
                continue;
            }

            $relationDefs = $entityDefs->getRelation($link);

            if ($relationDefs->getType() !== RelationType::BELONGS_TO) {
                continue;
            }

            $foreignEntityType = $relationDefs->getForeignEntityType();

            if (!$ormDefs->hasEntity($foreignEntityType)) {
                continue;
            }

            $foreignFieldType = $this->metadata->get(['entityDefs', $foreignEntityType, 'fields', $foreignField, 'type']);

            if (!in_array($foreignFieldType, $typeList, true)) {
                continue;
            }

            $attributeDefs = $entityDefs->getAttribute($field);

            if ($attributeDefs->getType() !== AttributeType::FOREIGN) {
                continue;
            }

            $relation = $attributeDefs->getParam('relation');
            $foreignAttribute = $attributeDefs->getParam('foreign');

            // A foreign field can be defined as an expression (e.g. a person name); such are not supported.
            if (!is_string($foreignAttribute) || $foreignAttribute === '' || $relation !== $link) {
                continue;
            }

            if (!$ormDefs->getEntity($foreignEntityType)->hasAttribute($foreignAttribute)) {
                continue;
            }

            $list[] = new ForeignField(
                field: $field,
                link: $link,
                foreignEntityType: $foreignEntityType,
                foreignField: $foreignField,
                foreignAttribute: $foreignAttribute,
            );
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getSupportedTypeList(): array
    {
        $list = $this->metadata->get(['app', 'frameVasAdvancedImport', 'foreignFieldTypeList']);

        if (!is_array($list) || $list === []) {
            return self::DEFAULT_FIELD_TYPE_LIST;
        }

        return array_values(array_filter($list, fn ($item) => is_string($item)));
    }
}
