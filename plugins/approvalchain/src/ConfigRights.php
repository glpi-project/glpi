<?php

namespace GlpiPlugin\Approvalchain;

use Session;

/**
 * Chain definitions are administered with the core "config" right
 * (READ to view, UPDATE to create/edit/delete).
 */
trait ConfigRights
{
    public static function canView(): bool
    {
        return (bool) Session::haveRight('config', READ);
    }

    public static function canCreate(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }

    public static function canUpdate(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }

    public static function canDelete(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }

    public static function canPurge(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }
}
