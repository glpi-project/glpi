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

class Item_Cluster extends CommonDBRelation
{
    public static $itemtype_1 = 'Cluster';
    public static $items_id_1 = 'clusters_id';
    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';
    public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
    public static $mustBeAttached_1 = false; // FIXME It make no sense for a cluster item to not be attached to a Cluster.
    public static $mustBeAttached_2 = false; // FIXME It make no sense for a cluster item to not be attached to an Item.

    public static function getTypeName($nb = 0)
    {
        return _n('Cluster item', 'Cluster items', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countForMainItem($item);
        }
        return self::createTabEntry(_n('Item', 'Items', $nb), $nb, $item::getType(), 'ti ti-package');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showItems($item);
        return true;
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'MassiveAction:update';
        $forbidden[] = 'CommonDBConnexity:affect';
        $forbidden[] = 'CommonDBConnexity:unaffect';

        return $forbidden;
    }

    /**
     * Print enclosure items
     *
     * @return void
     **/
    public static function showItems(Cluster $cluster)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $cluster->fields['id'];
        $rand = mt_rand();

        if (
            !$cluster->getFromDB($ID)
            || !$cluster->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $cluster->canEdit($ID);

        $items = $DB->request([
            'SELECT' => ['id', 'itemtype', 'items_id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'clusters_id' => $ID
            ]
        ]);

        if ($cluster->canAddItem('itemtype')) {
            echo "<div class='mt-1 mb-3 text-center'>";
            Html::showSimpleForm(
                self::getFormURL(),
                '_add_fromitem',
                __('Add new item to this cluster...'),
                [
                    'cluster'   => $ID,
                    'position'  => 1
                ]
            );
            echo "</div>";
        }

        $entries = [];
        foreach ($items as $row) {
            $item = new $row['itemtype']();
            $item->getFromDB($row['items_id']);
            $entries[] = [
                'itemtype' => static::class,
                'id'       => $row['id'],
                'item'     => $item->getLink()
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'item' => _n('Item', 'Items', 1)
            ],
            'formatters' => [
                'item' => 'raw_html'
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

    public function showForm($ID, array $options = [])
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        echo "<div class='center'>";

        $this->initForm($ID, $options);
        $this->showFormHeader();

        $cluster = new Cluster();
        $cluster->getFromDB($this->fields['clusters_id']);

        $rand = mt_rand();

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='dropdown_itemtype$rand'>" . __('Item type') . "</label></td>";
        echo "<td>";
        $types = $CFG_GLPI['cluster_types'];
        $translated_types = [];
        foreach ($types as $type) {
            $translated_types[$type] = $type::getTypeName(1);
        }
        Dropdown::showFromArray(
            'itemtype',
            $translated_types,
            [
                'display_emptychoice'   => true,
                'value'                 => $this->fields["itemtype"],
                'rand'                  => $rand
            ]
        );

       //get all used items
        $used = [];
        $iterator = $DB->request([
            'FROM'   => $this->getTable()
        ]);
        foreach ($iterator as $row) {
            $used [$row['itemtype']][] = $row['items_id'];
        }

        Ajax::updateItemOnSelectEvent(
            "dropdown_itemtype$rand",
            "items_id",
            $CFG_GLPI["root_doc"] . "/ajax/dropdownAllItems.php",
            [
                'idtable'   => '__VALUE__',
                'name'      => 'items_id',
                'value'     => $this->fields['items_id'],
                'rand'      => $rand,
                'used'      => $used
            ]
        );

        echo "</td>";
        echo "<td><label for='dropdown_items_id$rand'>" . _n('Item', 'Items', 1) . "</label></td>";
        echo "<td id='items_id'>";
        if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
            $itemtype = new $itemtype();
            $itemtype::dropdown([
                'name'   => "items_id",
                'value'  => $this->fields['items_id'],
                'rand'   => $rand
            ]);
        } else {
            Dropdown::showFromArray(
                'items_id',
                [],
                [
                    'display_emptychoice'   => true,
                    'rand'                  => $rand
                ]
            );
        }

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='dropdown_clusters_id$rand'>" . Cluster::getTypeName(1) . "</label></td>";
        echo "<td>";
        Cluster::dropdown(['value' => $this->fields["clusters_id"], 'rand' => $rand]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    /**
     * Prepares input (for update and add)
     *
     * @param array $input Input data
     *
     * @return false|array
     */
    private function prepareInput($input)
    {
        $error_detected = [];

       //check for requirements
        if (
            ($this->isNewItem() && (!isset($input['itemtype']) || empty($input['itemtype'])))
            || (isset($input['itemtype']) && empty($input['itemtype']))
        ) {
            $error_detected[] = __('An item type is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['items_id']) || empty($input['items_id'])))
            || (isset($input['items_id']) && empty($input['items_id']))
        ) {
            $error_detected[] = __('An item is required');
        }
        if (
            ($this->isNewItem() && (!isset($input['clusters_id']) || empty($input['clusters_id'])))
            || (isset($input['clusters_id']) && empty($input['clusters_id']))
        ) {
            $error_detected[] = __('A cluster is required');
        }

        if (count($error_detected)) {
            foreach ($error_detected as $error) {
                Session::addMessageAfterRedirect(
                    $error,
                    true,
                    ERROR
                );
            }
            return false;
        }

        return $input;
    }


    public static function getIcon()
    {
        return Cluster::getIcon();
    }
}
