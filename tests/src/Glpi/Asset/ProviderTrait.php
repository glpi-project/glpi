<?php

namespace Glpi\Tests\Glpi\Asset;

trait ProviderTrait
{
    /**
     * @return \Generator Item type that are both assets and assignable
     */
    public static function assignableAssetsItemtypeProvider(): \Generator
    {
        global $CFG_GLPI;
        $assignable_assets_itemtypes = array_intersect($CFG_GLPI['assignable_types'], $CFG_GLPI['asset_types']);

        foreach ($assignable_assets_itemtypes as $itemtype) {
            yield $itemtype => [
                'class' => $itemtype,
            ];
        }
    }
}
