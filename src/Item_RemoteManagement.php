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

class Item_RemoteManagement extends CommonDBChild
{
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';
    public $dohistory              = true;

    public const TEAMVIEWER = 'teamviewer';
    public const LITEMANAGER = 'litemanager';
    public const ANYDESK = 'anydesk';
    public const MESHCENTRAL = 'meshcentral';
    public const SUPREMO = 'supremo';
    public const RUSTDESK = 'rustdesk';


    public static function getTypeName($nb = 0)
    {
        return __('Remote management');
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        switch ($item->getType()) {
            default:
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        self::getTable(),
                        [
                            'items_id'     => $item->getID(),
                            'itemtype'     => $item->getType()
                        ]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showForItem($item, $withtemplate);
        return true;
    }


    /**
     * Get remote managements related to a given item
     *
     * @param CommonDBTM $item  Item instance
     * @param string     $sort  Field to sort on
     * @param string     $order Sort order
     *
     * @return DBmysqlIterator
     */
    public static function getFromItem(CommonDBTM $item, $sort = null, $order = null): DBmysqlIterator
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'      => self::getTable(),
            'WHERE'     => [
                'itemtype'     => $item->getType(),
                'items_id'     => $item->fields['id']
            ]
        ]);
        return $iterator;
    }

    /**
     * Print the remote management
     *
     * @param CommonDBTM $item          Item object
     * @param boolean    $withtemplate  Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $ID = $item->fields['id'];
        $itemtype = $item->getType();

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $item->canEdit($ID);

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            echo "<div class='center firstbloc'>" .
               "<a class='btn btn-primary' href='" . self::getFormURL() . "?itemtype=$itemtype&items_id=$ID&amp;withtemplate=" .
                  $withtemplate . "'>";
            echo __('Add a remote management');
            echo "</a></div>\n";
        }

        echo "<div class='center'>";
        $iterator = self::getFromItem($item);

        $rand = mt_rand();
        if ($canedit && count($iterator)) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams
            = ['num_displayed'
                        => min($_SESSION['glpilist_limit'], count($iterator)),
                'container'
                        => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";
        $colspan = 9;
        echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName(count($iterator)) .
            "</th></tr>";

        if (count($iterator)) {
            $header = '<tr>';
            $header .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header .= "</th>";
            $header .= "<th>" . __('Remote ID') . "</th>";
            $header .= "<th>" . _n('Type', 'Types', 1) . "</th>";
            $header .= "<th>" . __('Automatic inventory') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $item::getTypeName(1),
                    $item->getName()
                )
            );

            $mgmt = new self();
            foreach ($iterator as $data) {
                $mgmt->getFromResultSet($data);
                echo "<tr class='tab_bg_2'>";

                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                echo "</td>";
                echo "<td>" . $mgmt->getRemoteLink() . "</td>";
                echo "<td>" . $mgmt->fields['type'] . "</td>";
                echo "<td>" . Dropdown::getYesNo($data['is_dynamic']) . "</td>";
                echo "</tr>";
                Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
        } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        echo "</div>";
    }


    /**
     * Get remote management system link
     *
     * @return string
     */
    public function getRemoteLink(): string
    {
        $link = '<a href="%s" target="_blank">%s</a>';
        $id = Html::entities_deep($this->fields['remoteid']);
        $href = null;
        switch ($this->fields['type']) {
            case self::TEAMVIEWER:
                $href = "https://start.teamviewer.com/$id";
                break;
            case self::ANYDESK:
                $href = "anydesk:$id";
                break;
            case self::SUPREMO:
                $href = "supremo:$id";
                break;
        }

        if ($href === null) {
            return $id;
        } else {
            return sprintf(
                $link,
                $href,
                $id
            );
        }
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'remoteid',
            'name'               => __('ID'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype)
    {
        $tab = [];

        $name = self::getTypeName(Session::getPluralNumber());
        $tab[] = [
            'id'                 => 'remote_management',
            'name'               => $name
        ];

        $tab[] = [
            'id'                 => '180',
            'table'              => self::getTable(),
            'field'              => 'remoteid',
            'name'               => __('ID'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'itemtype_item'
            ]
        ];

        $tab[] = [
            'id'                 => '181',
            'table'              => self::getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'forcegroupby'       => true,
            'width'              => 1000,
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item'
            ]
        ];

        return $tab;
    }


    public function showForm($ID, array $options = [])
    {
        $itemtype = null;
        if (isset($options['itemtype']) && !empty($options['itemtype'])) {
            $itemtype = $options['itemtype'];
        } else if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
            $itemtype = $this->fields['itemtype'];
        } else {
            throw new \RuntimeException('Unable to retrieve itemtype');
        }

        if (!Session::haveRight($itemtype::$rightname, READ)) {
            return false;
        }

        $item = new $itemtype();
        if ($ID > 0) {
            $this->check($ID, READ);
            $item->getFromDB($this->fields['items_id']);
        } else {
            $this->check(-1, CREATE, $options);
            $item->getFromDB($options['items_id']);
        }

        $this->showFormHeader($options);

        if ($this->isNewID($ID)) {
            echo "<input type='hidden' name='items_id' value='" . $options['items_id'] . "'>";
            echo "<input type='hidden' name='itemtype' value='" . $options['itemtype'] . "'>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Item', 'Items', 1) . "</td>";
        echo "<td>" . $item->getLink() . "</td>";
        echo "<td>" . __('Automatic inventory') . "</td>";
        echo "<td>";
        if ($ID && $this->fields['is_dynamic']) {
            echo __('Yes');
        } else {
            echo __('No');
        }
        echo "</td>";
        echo "</tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Remote ID') . "</td>";
        echo "<td>";
        echo Html::input('remoteid', ['value' => $this->fields['remoteid']]);
        echo "</td><td>" . _n('Type', 'Types', 1) . "</td>";
        $types = [
            self::TEAMVIEWER => 'TeamViewer',
            self::LITEMANAGER => 'LiteManager',
            self::ANYDESK => 'AnyDesk',
            self::MESHCENTRAL => 'MeshCentral',
            self::SUPREMO => 'SupRemo',
            self::RUSTDESK => 'RustDesk',
        ];
        echo "<td>";
        echo Dropdown::showFromArray(
            'type',
            $types,
            [
                'value'   => $this->fields['type'],
                'display' => false
            ]
        );
        echo "</td></tr>";

        $itemtype = $this->fields['itemtype'];
        $options['canedit'] = Session::haveRight($itemtype::$rightname, UPDATE);
        $this->showFormButtons($options);

        return true;
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public static function getIcon()
    {
        return "fas fa-laptop-house";
    }
}
