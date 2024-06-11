<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;

class Pdf
{
    public static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0, CommonDBTM $checkitem = null)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $action_prefix = 'Pdf' . MassiveAction::CLASS_ACTION_SEPARATOR;
        $item = new $itemtype();
        if ($item instanceof CommonDBTM) {
            $actions[$action_prefix . 'print_as_pdf'] = '<i class="ti ti-file-type-pdf"> </i>' . __('Print as PDF');
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'print_as_pdf':
                $items = $ma->getItems();
                $itemtypes = array_keys($items);
                $firstItemtype = reset($itemtypes);
                $firstitem = reset($items[$firstItemtype]);
                TemplateRenderer::getInstance()->display('pages/tools/pdf.html.twig', [
                    'tabs' => self::getPrintableTabs($firstItemtype, $firstitem),
                ]);
                return true;
        }
        return false;
    }

    public static function getPrintableTypeLabel($itemtype, $item)
    {
        $instance = new $itemtype();
        $label = $instance->getTabNameForItem($item);
        if (empty($label)) {
            $label = '<span><i class="' . $itemtype::getIcon() . ' me-2"></i>' . $itemtype::getTypeName(0) . '</span>';
        }
        return $label;
    }

    public static function getPrintableTabs($itemtype, $item_id)
    {
        $item = new $itemtype();
        $item->getFromDB($item_id);

        $tabKeys = array_keys($item->defineTabs());
        $tabKeys = array_map(function ($value) {
            $parts = explode('$', $value);
            return $parts[0];
        }, $tabKeys);
        $tabKeys = array_diff($tabKeys, self::getUnprintableTypes());

        foreach ($tabKeys as $tabKey) {
            $cleanTabs[$tabKey] = self::getPrintableTypeLabel($tabKey, $item);
        }
        return $cleanTabs ?? [];
    }

    public static function getUnprintableTypes()
    {
        return [
            \Impact::class,
        ];
    }
}
