<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

use Espo\Core\FieldValidation\Failure;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;

/**
 * Relates an imported record to a related record found by a foreign field value.
 *
 * Example: the column mapped to `departmentCode` contains `300000018`; the Department
 * whose `idDepartment1C` equals `300000018` is found and set as `departmentId`.
 */
class ForeignLinkResolver
{
    public const FAILURE_NOT_FOUND = 'advancedImportNotFound';
    public const FAILURE_AMBIGUOUS = 'advancedImportAmbiguous';

    public const PARAM_NOT_FOUND_ACTION = 'advancedImportNotFoundAction';
    public const PARAM_MULTIPLE_ACTION = 'advancedImportMultipleAction';

    public const NOT_FOUND_ERROR = 'error';
    public const NOT_FOUND_SKIP = 'skip';

    public const MULTIPLE_ERROR = 'error';
    public const MULTIPLE_FIRST = 'first';

    private const CACHE_LIMIT = 10000;

    /** @var array<string, string> Lookup value => ID. Only unambiguous matches are cached. */
    private array $cache = [];

    public function __construct(
        private ForeignFieldProvider $provider,
        private EntityManager $entityManager,
        private Config $config,
    ) {}

    /**
     * @param string[] $attributeList Attributes mapped in the import.
     * @return Failure[]
     */
    public function process(CoreEntity $entity, array $attributeList): array
    {
        $failureList = [];

        foreach ($this->provider->getList($entity->getEntityType()) as $item) {
            if (!in_array($item->field, $attributeList, true)) {
                continue;
            }

            $failure = $this->processItem($entity, $item, $attributeList);

            if ($failure) {
                $failureList[] = $failure;
            }
        }

        return $failureList;
    }

    /**
     * @param string[] $attributeList
     */
    private function processItem(CoreEntity $entity, ForeignField $item, array $attributeList): ?Failure
    {
        if (!$entity->has($item->field)) {
            return null;
        }

        $value = $entity->get($item->field);

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || !is_scalar($value)) {
            return null;
        }

        $value = (string) $value;
        $idAttribute = $item->getIdAttribute();

        // A link ID mapped explicitly in the same row takes precedence.
        if (in_array($idAttribute, $attributeList, true) && $entity->get($idAttribute)) {
            return null;
        }

        // An existing record with an unchanged value is already related correctly.
        if (
            !$entity->isNew() &&
            $entity->get($idAttribute) &&
            !$entity->isAttributeChanged($item->field)
        ) {
            return null;
        }

        $idList = $this->find($entity->getEntityType(), $item, $value);

        if ($idList === []) {
            if ($this->getNotFoundAction() === self::NOT_FOUND_SKIP) {
                return null;
            }

            return new Failure($entity->getEntityType(), $item->field, self::FAILURE_NOT_FOUND);
        }

        if (count($idList) > 1 && $this->getMultipleAction() !== self::MULTIPLE_FIRST) {
            return new Failure($entity->getEntityType(), $item->field, self::FAILURE_AMBIGUOUS);
        }

        $entity->set($idAttribute, $idList[0]);

        return null;
    }

    /**
     * @return string[] At most two IDs; two means the value is ambiguous.
     */
    private function find(string $entityType, ForeignField $item, string $value): array
    {
        // Records of the imported type can be created by the import itself, so don't cache them.
        $useCache = $item->foreignEntityType !== $entityType;

        $key = $item->foreignEntityType . "\0" . $item->foreignAttribute . "\0" . $value;

        if ($useCache && isset($this->cache[$key])) {
            return [$this->cache[$key]];
        }

        $foreignDefs = $this->entityManager->getDefs()->getEntity($item->foreignEntityType);

        // The oldest record goes first, so "Use the first match" is predictable.
        $orderBy = $foreignDefs->hasAttribute('createdAt') ? 'createdAt' : 'id';

        $collection = $this->entityManager
            ->getRDBRepository($item->foreignEntityType)
            ->select(['id'])
            ->where([$item->foreignAttribute => $value])
            ->order($orderBy, Order::ASC)
            ->limit(0, 2)
            ->find();

        $idList = [];

        foreach ($collection as $foreignEntity) {
            $idList[] = $foreignEntity->getId();
        }

        if ($useCache && count($idList) === 1) {
            if (count($this->cache) >= self::CACHE_LIMIT) {
                $this->cache = [];
            }

            $this->cache[$key] = $idList[0];
        }

        return $idList;
    }

    private function getNotFoundAction(): string
    {
        $value = $this->config->get(self::PARAM_NOT_FOUND_ACTION);

        return $value === self::NOT_FOUND_SKIP ? self::NOT_FOUND_SKIP : self::NOT_FOUND_ERROR;
    }

    private function getMultipleAction(): string
    {
        $value = $this->config->get(self::PARAM_MULTIPLE_ACTION);

        return $value === self::MULTIPLE_FIRST ? self::MULTIPLE_FIRST : self::MULTIPLE_ERROR;
    }
}
