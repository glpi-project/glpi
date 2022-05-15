<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

// Relation between Contracts and Suppliers
class Contract_Supplier extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1 = 'Contract';
    public static $items_id_1 = 'contracts_id';

    public static $itemtype_2 = 'Supplier';
    public static $items_id_2 = 'suppliers_id';


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Supplier':
                    if (Contract::canView()) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb =  self::countForItem($item);
                        }
                        return self::createTabEntry(
                            Contract::getTypeName(Session::getPluralNumber()),
                            $nb
                        );
                    }
                    break;

                case 'Contract':
                    if (Session::haveRight("contact_enterprise", READ)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                              $nb = self::countForItem($item);
                        }
                        return self::createTabEntry(Supplier::getTypeName(Session::getPluralNumber()), $nb);
                    }
                    break;
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Supplier':
                self::showForSupplier($item);
                break;

            case 'Contract':
                self::showForContract($item);
                break;
        }
        return true;
    }


    /**
     * Print an HTML array with contracts associated to the enterprise
     *
     * @since 0.84
     *
     * @param Supplier $supplier
     *
     * @return void
     **/
    public static function showForSupplier(Supplier $supplier)
    {

        $ID = $supplier->fields['id'];
        if (
            !Contract::canView()
            || !$supplier->can($ID, READ)
        ) {
            return;
        }
        $canedit = $supplier->can($ID, UPDATE);
        $rand    = mt_rand();

        $iterator = self::getListForItem($supplier);
        $number = count($iterator);

        $contracts = [];
        $used      = [];
        foreach ($iterator as $data) {
            $contracts[$data['linkid']]   = $data;
            $used[$data['id']]            = $data['id'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='contractsupplier_form$rand' id='contractsupplier_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<input type='hidden' name='suppliers_id' value='$ID'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add a contract') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='right'>";
            Contract::dropdown(['used'         => $used,
                'entity'       => $supplier->fields["entities_id"],
                'entity_sons'  => $supplier->fields["is_recursive"],
                'nochecklimit' => true
            ]);

            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container'     => 'mass' . __CLASS__ . $rand,
                'num_displayed' => min($_SESSION['glpilist_limit'], $number)
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixe'>";

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
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . _x('phone', 'Number') . "</th>";
        $header_end .= "<th>" . ContractType::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Start date') . "</th>";
        $header_end .= "<th>" . __('Initial contract period') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($contracts as $data) {
            $cID        = $data["id"];
            $assocID    = $data["linkid"];

            echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(__CLASS__, $assocID);
                echo "</td>";
            }
            $name = $data["name"];
            if (
                $_SESSION["glpiis_ids_visible"]
                || empty($data["name"])
            ) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
            }
            echo "<td class='center b'>
               <a href='" . Contract::getFormURLWithID($cID) . "'>" . $name . "</a>";
            echo "</td>";
            echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data["entity"]);
            echo "</td><td class='center'>" . $data["num"] . "</td>";
            echo "<td class='center'>" .
                Dropdown::getDropdownName("glpi_contracttypes", $data["contracttypes_id"]) . "</td>";
            echo "<td class='center'>" . Html::convDate($data["begin_date"]) . "</td>";
            echo "<td class='center'>";
            sprintf(_n('%d month', '%d months', $data["duration"]), $data["duration"]);

            if (($data["begin_date"] != '') && !empty($data["begin_date"])) {
                echo " -> " . Infocom::getWarrantyExpir($data["begin_date"], $data["duration"], 0, true);
            }
            echo "</td>";
            echo "</tr>";
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


    /**
     * Print the HTML array of suppliers for this contract
     *
     * @since 0.84
     *
     * @param $contract Contract object
     *
     * @return void
     **/
    public static function showForContract(Contract $contract)
    {

        $instID = $contract->fields['id'];

        if (
            !$contract->can($instID, READ)
            || !Session::haveRight("contact_enterprise", READ)
        ) {
            return;
        }
        $canedit = $contract->can($instID, UPDATE);
        $rand    = mt_rand();

        $iterator = self::getListForItem($contract);
        $number = count($iterator);

        $suppliers = [];
        $used      = [];
        foreach ($iterator as $data) {
            $suppliers[$data['linkid']]   = $data;
            $used[$data['id']]            = $data['id'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='contractsupplier_form$rand' id='contractsupplier_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<input type='hidden' name='contracts_id' value='$instID'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add a supplier') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='right'>";

            Supplier::dropdown(['used'         => $used,
                'entity'       => $contract->fields["entities_id"],
                'entity_sons'  => $contract->fields["is_recursive"]
            ]);
            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                'container'     => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixe'>";
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
        $header_end .= "<th>" . Supplier::getTypeName(1) . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . SupplierType::getTypeName(1) . "</th>";
        $header_end .= "<th>" . Phone::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Website') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($suppliers as $data) {
            $assocID = $data['linkid'];
            $website = $data['website'];
            if (!empty($website)) {
                if (!preg_match("?https*://?", $website)) {
                    $website = "http://" . $website;
                }
                $website = "<a target=_blank href='$website'>" . $data['website'] . "</a>";
            }
            $entID         = $data['id'];
            $entity        = $data['entity'];
            $entname       = Dropdown::getDropdownName("glpi_suppliers", $entID);
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(__CLASS__, $assocID);
                echo "</td>";
            }
            echo "<td class='center'>";
            if (
                $_SESSION["glpiis_ids_visible"]
                || empty($entname)
            ) {
                $entname = sprintf(__('%1$s (%2$s)'), $entname, $entID);
            }
            echo "<a href='" . Supplier::getFormURLWithID($entID) . "'>" . $entname;
            echo "</a></td>";
            echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $entity) . "</td>";
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_suppliertypes", $data['suppliertypes_id']) . "</td>";
            echo "<td class='center'>" . $data['phonenumber'] . "</td>";
            echo "<td class='center'>" . $website . "</td>";
            echo "</tr>";
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
}
