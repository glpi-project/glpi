<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/// Class Plug
class Plug extends CommonDBChild
{
    public static $itemtype       = 'itemtype';
    public static $items_id       = 'items_id';
    public static $rightname      = 'datacenter';
    public $dohistory             = true;

    public $can_be_translated = false;

    public static $mustBeAttached     = false;

    public $auto_message_on_action = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Plug', 'Plugs', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-plug";
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', PDU::class, self::class];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);
        if (static::canUpdate() && is_null($checkitem)) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'unlink']    = "<i class='ti ti-plug'></i>" . _sx('button', 'Unlink plug');
        }
        return $actions;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'unlink':
                foreach ($ids as $id) {
                    $plug = new self();
                    if ($plug->getFromDB($id)) {
                        if ($plug->update(
                            [
                                'mainitemtype'  => '',
                                'mainitems_id'  => 0,
                                'id'            => $id,
                            ]
                        )
                        ) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            /** @var CommonDBTM $item */
            $nb = countElementsInTable(
                self::getTable(),
                [
                    'mainitemtype' => $item::class,
                    'mainitems_id' => $item->getID(),
                ]
            );
        }
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        return self::showItems($item);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/pdu.html.twig', [
            'item'      => $this,
            'params'    => $options,
        ]);
        return true;
    }

    /**
     * Print plugs linked to PDU
     *
     * @param CommonDBTM $item
     *
     * @return bool
     */
    public static function showItems(CommonDBTM $item): bool
    {
        global $DB;

        $ID = $item->getID();
        $rand = mt_rand();

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $item->canEdit($ID);

        $items = $DB->request([
            'SELECT' => ['*'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'mainitemtype' => $item::class,
                'mainitems_id' => $ID,
            ],
        ]);

        if (Plug::canCreate()) {
            $rand = mt_rand();
            echo "\n<form id='form_device_add$rand' name='form_device_add$rand'
               action='" . htmlescape(Toolbox::getItemTypeFormURL(self::class)) . "' method='post'>\n";
            echo "\t<input type='hidden' name='mainitems_id' value='$ID'>\n";
            echo "\t<input type='hidden' name='mainitemtype' value='" . $item::class . "'>\n";
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><td>";
            echo "<label for='dropdown_plugs_id$rand'>" . __s('Add a new plug') . "</label> <span class='form-help' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-html='true'
                     data-bs-title='" . __s('Name will by suffixed by number') . "'>?</span></td>";
            echo "<td>";
            echo Html::input(
                'name',
                [
                    'type'   => 'text',
                    'required' => true,
                    'placeholder' => __('Plug name'),
                ]
            );
            echo "</td><td>";
            echo Html::input(
                'number',
                [
                    'type'   => 'number',
                    'min'    => 1,
                    'placeholder' => __('Number of plugs to add'),
                    'required' => true,
                ]
            );
            echo "</td><td>";
            echo "<input type='submit' class='btn btn-primary' name='add_several' value='" . _sx('button', 'Add') . "'>";
            echo "</td></tr></table>";
            Html::closeForm();
        }

        $entries = [];
        foreach ($items as $row) {
            $plug = new Plug();
            $plug->getFromDB($row['id']);

            $itemtype_linked = "";
            if ($plug->fields['itemtype'] && $plug->fields['items_id']) {
                if (is_a($plug->fields['itemtype'], CommonDBTM::class, true)) {
                    $linked_item = new $plug->fields['itemtype']();
                    if ($linked_item->getFromDB($plug->fields['items_id'])) {
                        $itemtype_linked = $linked_item->getLink();
                    }
                }
            }

            $entries[] = [
                'name' => $plug->getLink(),
                'itemtype' => $plug::class,
                'items_id' => $plug->getID(),
                'custom_name' => $plug->fields['custom_name'],
                'linked_item' => $itemtype_linked,
                'id' => $row['id'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => Plug::getTypeName(0),
                'custom_name' => __('Custom name'),
                'linked_item' => __s('Associated asset'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'custom_name' => 'text',
                'linked_item' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);

        return true;
    }
}
