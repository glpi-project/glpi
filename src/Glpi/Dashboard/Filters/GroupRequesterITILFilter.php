<?php

namespace Glpi\Dashboard\Filters;

use Group_Item;

class GroupRequesterITILFilter extends AbstractITILGroupFilter
{
    public static function getName(): string
    {
        return __("Requester group");
    }

    public static function getId(): string
    {
        return "group_requester_itil";
    }

    protected static function getGroupType(): int
    {
        return Group_Item::GROUP_TYPE_NORMAL;
    }

    protected static function getITILSearchOptionID(): int
    {
        return 71;
    }
}
