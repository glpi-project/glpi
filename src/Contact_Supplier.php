<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class Contact_Supplier extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'Contact';
    public static $items_id_1 = 'contacts_id';

    public static $itemtype_2 = 'Supplier';
    public static $items_id_2 = 'suppliers_id';



    public static function getTypeName($nb = 0)
    {
        return _n('Link Contact/Supplier', 'Links Contact/Supplier', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate && Session::haveRight("contact_enterprise", READ)) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Supplier':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb =  self::countForItem($item);
                    }
                    return self::createTabEntry(Contact::getTypeName(Session::getPluralNumber()), $nb);

                case 'Contact':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Supplier::getTypeName(Session::getPluralNumber()), $nb);
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

            case 'Contact':
                self::showForContact($item);
                break;
        }
        return true;
    }


    /**
     * Print the HTML array for entreprises on the current contact
     *
     * @return void
     */
    public static function showForContact(Contact $contact)
    {

        $instID = $contact->fields['id'];

        if (!$contact->can($instID, READ)) {
            return;
        }

        $canedit = $contact->can($instID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($contact);
        $number = count($iterator);

        $suppliers = [];
        $used = [];
        foreach ($iterator as $data) {
            $suppliers[$data['linkid']] = $data;
            $used[$data['id']] = $data['id'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='contactsupplier_form$rand' id='contactsupplier_form$rand'
                method='post' action='";
            echo Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>" . __('Add a supplier') . "</tr>";

            echo "<tr class='tab_bg_2'><td class='center'>";
            echo "<input type='hidden' name='contacts_id' value='$instID'>";
            Supplier::dropdown(['used'        => $used,
                'entity'      => $contact->fields["entities_id"],
                'entity_sons' => $contact->fields["is_recursive"],
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
                'container'     => 'mass' . __CLASS__ . $rand,
            ];
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
        $header_end .= "<th>" . Supplier::getTypeName(1) . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . SupplierType::getTypeName(1) . "</th>";
        $header_end .= "<th>" . Phone::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Fax') . "</th>";
        $header_end .= "<th>" . __('Website') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        if ($number > 0) {
            Session::initNavigateListItems(
                'Supplier',
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Contact::getTypeName(1),
                    $contact->getName()
                )
            );

            foreach ($suppliers as $data) {
                $assocID = $data["linkid"];
                Session::addToNavigateListItems('Supplier', $data["id"]);
                $website           = $data["website"];

                if (!empty($website)) {
                    $website = $data["website"];

                    if (!preg_match("?https*://?", $website)) {
                        $website = "http://" . $website;
                    }
                    $website = "<a target=_blank href='$website'>" . $data["website"] . "</a>";
                }

                echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
                if ($canedit) {
                    echo "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $assocID) . "</td>";
                }
                echo "<td class='center'>";
                echo "<a href='" . Supplier::getFormURLWithID($data["id"]) . "'>" .
                   Dropdown::getDropdownName("glpi_suppliers", $data["id"]) . "</a></td>";
                echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data["entity"]);
                echo "</td>";
                echo "<td class='center'>" . Dropdown::getDropdownName("glpi_suppliertypes", $data["suppliertypes_id"]);
                echo "</td>";
                echo "<td class='center' width='80'>" . $data["phonenumber"] . "</td>";
                echo "<td class='center' width='80'>" . $data["fax"] . "</td>";
                echo "<td class='center'>" . $website . "</td>";
                echo "</tr>";
            }
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
     * Show contacts associated to an enterprise
     *
     * @param Supplier $supplier
     *
     * @return void
     */
    public static function showForSupplier(Supplier $supplier)
    {

        $instID = $supplier->fields['id'];
        if (!$supplier->can($instID, READ)) {
            return;
        }
        $canedit = $supplier->can($instID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($supplier);
        $number = count($iterator);

        $contacts = [];
        $used = [];
        foreach ($iterator as $data) {
            $contacts[$data['linkid']] = $data;
            $used[$data['id']] = $data['id'];
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='contactsupplier_form$rand' id='contactsupplier_form$rand'
                method='post' action='";
            echo Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>" . __('Add a contact') . "</tr>";

            echo "<tr class='tab_bg_2'><td class='center'>";
            echo "<input type='hidden' name='suppliers_id' value='$instID'>";

            Contact::dropdown(['used'        => $used,
                'entity'      => $supplier->fields["entities_id"],
                'entity_sons' => $supplier->fields["is_recursive"],
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
                'container'     => 'mass' . __CLASS__ . $rand,
            ];
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
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . Phone::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Phone 2') . "</th>";
        $header_end .= "<th>" . __('Mobile phone') . "</th>";
        $header_end .= "<th>" . __('Fax') . "</th>";
        $header_end .= "<th>" . _n('Email', 'Emails', 1) . "</th>";
        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        if ($number) {
            Session::initNavigateListItems(
                'Contact',
                //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Supplier::getTypeName(1),
                    $supplier->getName()
                )
            );

            foreach ($contacts as $data) {
                $assocID             = $data["linkid"];
                Session::addToNavigateListItems('Contact', $data["id"]);

                echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
                if ($canedit) {
                    echo "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $assocID) . "</td>";
                }
                echo "<td class='center'>";
                echo "<a href='" . Contact::getFormURLWithID($data["id"]) . "'>" .
                   sprintf(__('%1$s %2$s'), $data["name"], $data["firstname"]) . "</a></td>";
                echo "<td class='center' width='100'>" . Dropdown::getDropdownName(
                    "glpi_entities",
                    $data["entity"]
                );
                echo "</td>";
                echo "<td class='center' width='100'>" . $data["phone"] . "</td>";
                echo "<td class='center' width='100'>" . $data["phone2"] . "</td>";
                echo "<td class='center' width='100'>" . $data["mobile"] . "</td>";
                echo "<td class='center' width='100'>" . $data["fax"] . "</td>";
                echo "<td class='center'>";
                echo "<a href='mailto:" . $data["email"] . "'>" . $data["email"] . "</a></td>";
                echo "<td class='center'>" . Dropdown::getDropdownName(
                    "glpi_contacttypes",
                    $data["contacttypes_id"]
                ) . "</td>";
                echo "</tr>";
            }
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
