<?php

namespace GlpiPlugin\Approvalchain;

use Dropdown;
use User;

final class Util
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** HTML-safe label for an approver (user or group). */
    public static function approver(int $users_id, int $groups_id): string
    {
        if ($users_id > 0) {
            return self::e(User::getFriendlyNameById($users_id));
        }
        if ($groups_id > 0) {
            return self::e(sprintf(
                __('Group: %s', 'approvalchain'),
                Dropdown::getDropdownName('glpi_groups', $groups_id)
            ));
        }
        return '<span class="text-danger">' . self::e(__('Not assigned', 'approvalchain')) . '</span>';
    }
}
