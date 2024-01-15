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

class Pdu_Plug extends CommonDBRelation
{
    public static $itemtype_1 = 'PDU';
    public static $items_id_1 = 'pdus_id';
    public static $itemtype_2 = 'Plug';
    public static $items_id_2 = 'plugs_id';
    public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
    public static $mustBeAttached_1      = false;
    public static $mustBeAttached_2      = false;

    public static function getTypeName($nb = 0)
    {
        return _n('PDU plug', 'PDU plugs', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        switch ($item->getType()) {
            default:
                $field = $item->getType() == PDU::getType() ? 'pdus_id' : 'plugs_id';
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        self::getTable(),
                        [$field  => $item->getID()]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showItems($item);
        return true;
    }

    /**
     * Print items
     *
     * @param  PDU $pdu PDU instance
     *
     * @return void
     */
    public static function showItems(PDU $pdu)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $pdu->getID();
        $rand = mt_rand();

        if (
            !$pdu->getFromDB($ID)
            || !$pdu->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $pdu->canEdit($ID);

        $items = $DB->request([
            'SELECT' => ['id', 'plugs_id', 'number_plugs'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'pdus_id' => $pdu->getID()
            ]
        ]);

        if ($canedit) {
            $rand = mt_rand();
            echo "\n<form id='form_device_add$rand' name='form_device_add$rand'
               action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' method='post'>\n";
            echo "\t<input type='hidden' name='pdus_id' value='$ID'>\n";
           //echo "\t<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><td>";
            echo "<label for='dropdown_plugs_id$rand'>" . __('Add a new plug') . "</label></td><td>";
            Plug::dropdown([
                'name'   => "plugs_id",
                'rand'   => $rand
            ]);
            echo "</td><td>";
            echo "<label for='number_plugs'>" . __('Number');
            echo "</td><td>";
            echo Html::input(
                'number_plugs',
                [
                    'id'     => 'number_plugs',
                    'type'   => 'number',
                    'min'    => 1
                ]
            );
            echo "</td><td>";
            echo "<input type='submit' class='btn btn-primary' name='add' value='" . _sx('button', 'Add') . "'>";
            echo "</td></tr></table>";
            Html::closeForm();
        }

        $entries = [];
        foreach ($items as $row) {
            $item = new Plug();
            $item->getFromDB($row['plugs_id']);
            $entries[] = [
                'itemtype' => self::class,
                'id' => $row['id'],
                'plugs_id' => $item->getLink(),
                'number_plugs' => $row['number_plugs']
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'plugs_id' => __('Name'),
                'number_plugs' => __('Number')
            ],
            'formatters' => [
                'plugs_id' => 'raw_html'
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand
            ],
        ]);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'CommonDBConnexity:affect';
        $forbidden[] = 'CommonDBConnexity:unaffect';
        return $forbidden;
    }
}
