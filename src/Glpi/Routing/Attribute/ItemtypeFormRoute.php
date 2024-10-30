<?php

namespace Glpi\Routing\Attribute;

use Symfony\Component\Routing\Attribute\Route;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class ItemtypeFormRoute extends Route
{
    public function __construct(string $itemtype)
    {
        parent::__construct(
            path: '/' . $itemtype . '/Form',
            name: 'glpi_itemtype_' . \strtolower($itemtype) . '_form',
        );
    }
}
