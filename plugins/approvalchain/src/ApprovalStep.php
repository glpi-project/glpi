<?php

namespace GlpiPlugin\Approvalchain;

use CommonDBTM;

/**
 * A step of a chain that is running on a ticket (snapshot of the definition).
 */
class ApprovalStep extends CommonDBTM
{
    public static $rightname = 'ticket';
    public $dohistory        = false;

    public const WAITING  = 1; // not reached yet
    public const PENDING  = 2; // awaiting decision
    public const APPROVED = 3;
    public const REFUSED  = 4;
    public const SKIPPED  = 5; // never reached (chain refused/cancelled)

    public static function getTypeName($nb = 0): string
    {
        return _n('Approval step', 'Approval steps', $nb, 'approvalchain');
    }

    public static function badge(int $status): string
    {
        $map = [
            self::WAITING  => ['secondary', __('Waiting', 'approvalchain')],
            self::PENDING  => ['warning',   __('Pending approval', 'approvalchain')],
            self::APPROVED => ['success',   __('Approved', 'approvalchain')],
            self::REFUSED  => ['danger',    __('Refused', 'approvalchain')],
            self::SKIPPED  => ['secondary', __('Skipped', 'approvalchain')],
        ];
        [$color, $label] = $map[$status] ?? ['secondary', '?'];
        return "<span class='badge bg-{$color}-lt'>" . Util::e($label) . "</span>";
    }
}
