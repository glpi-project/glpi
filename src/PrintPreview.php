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

class PrintPreview extends CommonDBTM
{
    public static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = false, CommonDBTM $checkitem = null)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $action_prefix = 'PrintPreview' . MassiveAction::CLASS_ACTION_SEPARATOR;
        $item = new $itemtype();
        if ($item instanceof CommonDBTM) {
            $actions[$action_prefix . 'print_preview'] = '<i class="' . self::getIcon() . '"></i>' . __s('Display to a printable view');
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'print_preview':
                $items = $ma->getItems();
                $itemtypes = array_keys($items);
                $firstItemtype = reset($itemtypes);
                if (!class_exists($firstItemtype)) {
                    return false;
                }
                $itemtype = new $firstItemtype();
                $fitem = $items[$firstItemtype] ?? [];
                $firstitem = reset($fitem);
                $tabs = [];
                if ($itemtype instanceof CommonITILObject) {
                    $tabs =
                    [
                        'Actors' => __('Actors'),
                        'Timeline' => __('Timeline')
                    ] + self::getPrintableTabs($firstItemtype, $firstitem);
                    $tabs[$firstItemtype] = __('Tickets Informations');
                } elseif ($itemtype instanceof CommonDBTM) {
                    $tabs = self::getPrintableTabs($firstItemtype, $firstitem);
                }
                TemplateRenderer::getInstance()->display('components/printpreview/print_preview.html.twig', [
                    'is_render' => false,
                    'tabs'      => $tabs,
                    'items_id'  => $firstitem,
                    'itemtype'  => $firstItemtype,
                ]);
                return true;
        }
        return false;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'print_preview':
                TemplateRenderer::getInstance()->render('components/printpreview/print_preview.html.twig', [
                    'is_render' => true,
                ]);
        }
    }

    public static function showPreview($ID, $options = [])
    {
        $unprintable = [
            'items_id' => '',
            'itemtype' => '',
            'csrf_token' => '',
            'generate_preview' => '',
        ];
        $itemtype = new $options['itemtype']();
        $item = $itemtype->getById($ID);

        $last_preview_date = date('Y-m-d H:i:s');

        $file_path = '/js/PrintPreview/' . 'print_' . strtolower($options['itemtype']) . '.js';
        if (file_exists(GLPI_ROOT . $file_path)) {
            $js_file = $file_path;
        }
        $instance = 'Asset';
        if ($item instanceof CommonITILObject) {
            $instance = "ITILObject";
        }

        TemplateRenderer::getInstance()->display('components/printpreview/print_preview.html.twig', [
            'is_render'             => true,
            'item'                  => $item,
            'itemtype'              => $options['itemtype'],
            'params'                => $options + ['formfooter' => false],
            'no_header'             => false,
            'no_inventory_footer'   => true,
            'no_form_buttons'       => true,
            'preview'               => true,
            'printable_tabs'        => array_diff($options, $unprintable),
            'last_preview_date'     => $last_preview_date,
            'js_file'               => $js_file ?? '',
            'instance'              => $instance,
        ]);

        return true;
    }

    /**
     * Get the form page URL for the current classe
     *
     * @param boolean $full  path or relative one
     **/
    public static function getFormURL($full = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');
        return "$dir/front/print_preview.form.php";
    }

    public static function getPrintableTypeLabel($itemtype, $item)
    {
        if (class_exists($itemtype)) {
            $instance = new $itemtype();
        } else {
            return '';
        }
        $label = $instance->getTabNameForItem($item);
        if (is_array($label)) {
            $label = reset($label);
        }
        if (empty($label)) {
            $label = '<span><i class="' . $itemtype::getIcon() . ' me-2"></i>' . htmlspecialchars($itemtype::getTypeName(Session::getPluralNumber())) . '</span>';
        }
        return $label;
    }

    public static function getPrintableTabs($itemtype, $item_id)
    {
        if (class_exists($itemtype)) {
            $item = new $itemtype();
        } else {
            return [];
        }
        $item->getFromDB($item_id);

        $tabKeys = array_keys($item->defineTabs());
        $tabKeys = array_map(function ($value) {
            $parts = explode('$', $value);
            return $parts[0];
        }, $tabKeys);
        $tabKeys = array_diff($tabKeys, self::getUnprintableTypes());

        foreach ($tabKeys as $tabKey) {
            $content = self::getPrintableTypeLabel($tabKey, $item);
            if ($tabKey != $itemtype && strpos($content, "class='badge") === false) {
                continue;
            }
            $cleanTabs[$tabKey] = self::getPrintableTypeLabel($tabKey, $item);
        }
        return $cleanTabs ?? [];
    }

    public static function getUnprintableTypes()
    {
        return [
            \Impact::class,
            \Log::class,
            self::class,
            \Software::class,
            \RuleMatchedLog::class,
            \Item_SoftwareVersion::class,
            \Item_Process::class,
        ];
    }

    public static function getIcon()
    {
        return 'ti ti-file-unknown';
    }
}
