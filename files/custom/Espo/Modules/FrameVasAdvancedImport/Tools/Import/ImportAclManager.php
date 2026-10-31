<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

namespace Espo\Modules\FrameVasAdvancedImport\Tools\Import;

use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Entities\User;

/**
 * Used only inside an import run.
 *
 * Foreign fields are read-only, so the core import drops them from the column mapping.
 * Here they are let through when the user is allowed to set the link they relate.
 */
class ImportAclManager extends AclManager
{
    private ?ForeignFieldProvider $advancedImportProvider = null;

    public function setAdvancedImportProvider(ForeignFieldProvider $provider): void
    {
        $this->advancedImportProvider = $provider;
    }

    public function getScopeForbiddenAttributeList(
        User $user,
        string $scope,
        string $action = Table::ACTION_READ,
        string $thresholdLevel = Table::LEVEL_NO
    ): array {

        $list = parent::getScopeForbiddenAttributeList($user, $scope, $action, $thresholdLevel);

        if (
            $action !== Table::ACTION_EDIT ||
            $thresholdLevel !== Table::LEVEL_NO ||
            $this->advancedImportProvider === null ||
            $list === []
        ) {
            return $list;
        }

        $allowedList = [];

        foreach ($this->advancedImportProvider->getList($scope) as $item) {
            if (!in_array($item->field, $list, true)) {
                continue;
            }

            if ($this->isForeignFieldAllowed($user, $scope, $item)) {
                $allowedList[] = $item->field;
            }
        }

        if ($allowedList === []) {
            return $list;
        }

        return array_values(array_diff($list, $allowedList));
    }

    private function isForeignFieldAllowed(User $user, string $scope, ForeignField $item): bool
    {
        // Relating sets the link, so the link itself must be editable.
        if (in_array($item->link, $this->getScopeForbiddenFieldList($user, $scope, Table::ACTION_EDIT), true)) {
            return false;
        }

        if (!$this->checkField($user, $scope, $item->field)) {
            return false;
        }

        return $this->checkScope($user, $item->foreignEntityType, Table::ACTION_READ);
    }
}
