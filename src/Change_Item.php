<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 * Change_Item Class
 *
 * Relation between Changes and Items
 **/
class Change_Item extends CommonItilObject_Item
{
   // From CommonDBRelation
    public static $itemtype_1          = 'Change';
    public static $items_id_1          = 'changes_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static function getTypeName($nb = 0)
    {
        return _n('Change item', 'Change items', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function prepareInputForAdd($input)
    {

       // Well, if I remember my PHP: empty(0) == true ...
        if (empty($input['changes_id']) || ($input['changes_id'] == 0)) {
            return false;
        }

       // Avoid duplicate entry
        if (
            countElementsInTable($this->getTable(), ['changes_id' => $input['changes_id'],
                'itemtype' => $input['itemtype'],
                'items_id' => $input['items_id']
            ]) > 0
        ) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }


    /**
     * Print the HTML array for Items linked to a change
     *
     * @param $change Change object
     *
     * @return boolean|void
     **/
    public static function showForChange(Change $change)
    {

        $instID = $change->fields['id'];

        if (!$change->can($instID, READ)) {
            return false;
        }
        $canedit = $change->canEdit($instID);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);
        $number = count($types_iterator);

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='changeitem_form$rand' id='changeitem_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            $types = [];
            foreach (array_keys($change->getAllTypesForHelpdesk()) as $key) {
                $types[] = $key;
            }
            Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                      => $types,
                'entity_restrict'
                                                      => ($change->fields['is_recursive']
                                                          ? getSonsOf(
                                                              'glpi_entities',
                                                              $change->fields['entities_id']
                                                          )
                                                          : $change->fields['entities_id'])
            ]);
            echo "</td><td class='center' width='30%'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "<input type='hidden' name='changes_id' value='$instID'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . __('Serial number') . "</th>";
        $header_end .= "<th>" . __('Inventory number') . "</th></tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($types_iterator as $type_row) {
            $itemtype = $type_row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            if ($item->canView()) {
                $iterator = self::getTypeItems($instID, $itemtype);
                $nb = count($iterator);

                $prem = true;
                foreach ($iterator as $data) {
                    $link     = $itemtype::getFormURLWithID($data['id']);
                    $linkname = $data["name"] ?? '';
                    if (
                        $_SESSION["glpiis_ids_visible"]
                        || empty($data["name"])
                    ) {
                        $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                    }
                    $name = "<a href=\"" . $link . "\">" . $linkname . "</a>";

                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                        echo "</td>";
                    }
                    if ($prem) {
                        $itemname = $item->getTypeName($nb);
                        echo "<td class='center top' rowspan='$nb'>" .
                         ($nb > 1 ? sprintf(__('%1$s: %2$s'), $itemname, $nb) : $itemname) . "</td>";
                        $prem = false;
                    }
                    echo "<td class='center'>";
                    echo Dropdown::getDropdownName("glpi_entities", $data['entity']) . "</td>";
                    echo "<td class='center" .
                      (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                    echo ">" . $name . "</td>";
                    echo "<td class='center'>" .
                      (isset($data["serial"]) ? "" . $data["serial"] . "" : "-") . "</td>";
                    echo "<td class='center'>" .
                      (isset($data["otherserial"]) ? "" . $data["otherserial"] . "" : "-") . "</td>";
                    echo "</tr>";
                }
            }
        }

        if ($number) {
            echo $header_begin . $header_bottom . $header_end;
        }

        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $DB;

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Change':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForMainItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);

                case 'User':
                case 'Group':
                case 'Supplier':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $from = 'glpi_changes_' . strtolower($item->getType() . 's');
                        $result = $DB->request([
                            'COUNT'  => 'cpt',
                            'FROM'   => $from,
                            'WHERE'  => [
                                $item->getForeignKeyField()   => $item->fields['id']
                            ]
                        ])->current();
                        $nb = $result['cpt'];
                    }
                    return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);

                default:
                    if (Session::haveRight("change", Change::READALL)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                              // Direct one
                              $nb = self::countForItem($item);
                              // Linked items
                              $linkeditems = $item->getLinkedItems();

                            if (count($linkeditems)) {
                                foreach ($linkeditems as $type => $tab) {
                                    foreach ($tab as $ID) {
                                        $typeitem = new $type();
                                        if ($typeitem->getFromDB($ID)) {
                                            $nb += self::countForItem($typeitem);
                                        }
                                    }
                                }
                            }
                        }
                        return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
                    }
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Change':
                self::showForChange($item);
                break;

            default:
                Change::showListForItem($item, $withtemplate);
        }
        return true;
    }
}
