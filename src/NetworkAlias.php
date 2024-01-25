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

/**
 *  NetworkAlias Class
 *
 * @since 0.84
 **
 */
class NetworkAlias extends FQDNLabel
{
   // From CommonDBChild
    public static $itemtype           = 'NetworkName';
    public static $items_id           = 'networknames_id';
    public $dohistory                 = true;

    public static $checkParentRights = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;


    public static function getTypeName($nb = 0)
    {
        return _n('Network alias', 'Network aliases', $nb);
    }


    public function defineTabs($options = [])
    {

        $ong  = [];
        $this->addDefaultFormTab($ong);

        return $ong;
    }


    /**
     * Get the full name (internet name) of a NetworkName
     *
     * @param integer $ID  ID of the NetworkName
     *
     * @return string  its internet name, or empty string if invalid NetworkName
     **/
    public static function getInternetNameFromID($ID)
    {

        $networkAlias = new self();
        if ($networkAlias->can($ID, READ)) {
            return FQDNLabel::getInternetNameFromLabelAndDomainID(
                $networkAlias->fields["name"],
                $networkAlias->fields["fqdns_id"]
            );
        }
        return "";
    }


    /**
     * Print the network alias form
     *
     * @param $ID        integer ID of the item
     * @param $options   array
     *     - target for the Form
     *     - withtemplate template or basic computer
     *
     * @return void
     **/
    public function showForm($ID, $options = [])
    {

       // Show only simple form to add / edit
        if (isset($options['parent'])) {
            $options['networknames_id'] = $options['parent']->getID();
        }

        $this->initForm($ID, $options);

        $recursiveItems = $this->recursivelyGetItems();
        if (count($recursiveItems) == 0) {
            return false;
        }

        $lastItem = $recursiveItems[count($recursiveItems) - 1];

        $options['entities_id'] = $lastItem->getField('entities_id');
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>";
        $this->displayRecursiveItems($recursiveItems, 'Type');
        echo "&nbsp;:</td>\n<td>";

        if (!($ID > 0)) {
            echo "<input type='hidden' name='networknames_id' value='" .
               $this->fields["networknames_id"] . "'>\n";
        }
        $this->displayRecursiveItems($recursiveItems, "Link");
        echo "</td><td>" . __('Name') . "</td><td>\n";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . FQDN::getTypeName() . "</td><td>";
        Dropdown::show(
            getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
            ['value'        => $this->fields["fqdns_id"],
                'name'         => 'fqdns_id',
                'entity'       => $this->getEntityID(),
                'displaywith'  => ['view']
            ]
        );
        echo "</td>";
        echo "<td>" . __('Comments') . "</td>";
        echo "<td><textarea class='form-control' rows='4' name='comment' >" . $this->fields["comment"];
        echo "</textarea></td>\n";
        echo "</tr>\n";

        $this->showFormButtons($options);
        return true;
    }


    /**
     * @since 0.84
     *
     * @param $itemtype
     * @param $base                  HTMLTableBase object
     * @param $super                 HTMLTableSuperHeader object (default NULL)
     * @param $father                HTMLTableHeader object (default NULL)
     * @param $options      array
     **/
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        HTMLTableSuperHeader $super = null,
        HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column_name = __CLASS__;
        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        if ($itemtype != 'NetworkName') {
            return;
        }

        $content = self::getTypeName();
        if (isset($options['column_links'][$column_name])) {
            $content = "<a href='" . $options['column_links'][$column_name] . "'>$content</a>";
        }
        $this_header = $base->addHeader($column_name, $content, $super, $father);
        $this_header->setItemType('NetworkAlias');
    }


    /**
     * @since 0.84
     *
     * @param $row                HTMLTableRow object (default NULL)
     * @param $item               CommonDBTM object (default NULL)
     * @param $father             HTMLTableCell object (default NULL)
     * @param $options   array
     **/
    public static function getHTMLTableCellsForItem(
        HTMLTableRow $row = null,
        CommonDBTM $item = null,
        HTMLTableCell $father = null,
        array $options = []
    ) {
        /** @var \DBmysql $DB */
        global $DB;

        if (empty($item)) {
            if (empty($father)) {
                return;
            }
            $item = $father->getItem();
        }

        if ($item->getType() != 'NetworkName') {
            return;
        }

        $column_name = __CLASS__;
        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        $header = $row->getGroup()->getHeaderByName('Internet', $column_name);
        if (!$header) {
            return;
        }

        $createRow            = (isset($options['createRow']) && $options['createRow']);
        $options['createRow'] = false;
        $alias                = new self();

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_networkaliases',
            'WHERE'  => ['networknames_id' => $item->getID()]
        ]);

        foreach ($iterator as $line) {
            if ($alias->getFromDB($line["id"])) {
                if ($createRow) {
                    $row = $row->createRow();
                }

                $content = "<a href='" . $alias->getLinkURL() . "'>" . $alias->getInternetName() . "</a>";
                $row->addCell($header, $content, $father, $alias);
            }
        }
    }


    /**
     * \brief Show aliases for an item from its form
     * Beware that the rendering can be different if readden from direct item form (ie : add new
     * NetworkAlias, remove, ...) or if readden from item of the item (for instance from the computer
     * form through NetworkPort::ShowForItem and NetworkName::ShowForItem).
     *
     * @param $item                     NetworkName object
     * @param $withtemplate   integer   withtemplate param (default 0)
     **/
    public static function showForNetworkName(NetworkName $item, $withtemplate = 0)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $ID = $item->getID();
        if (!$item->can($ID, READ)) {
            return false;
        }

        $canedit = $item->canEdit($ID);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'FROM'   => 'glpi_networkaliases',
            'WHERE'  => ['networknames_id' => $ID]
        ]);
        $number = count($iterator);

        $aliases = [];
        foreach ($iterator as $line) {
            $aliases[$line["id"]] = $line;
        }

        if ($canedit) {
            echo "\n<div class='firstbloc'>";
            echo "<script type='text/javascript' >\n";
            echo "function viewAddAlias$rand() {\n";
            $params = ['type'            => __CLASS__,
                'parenttype'      => 'NetworkName',
                'networknames_id' => $ID,
                'id'              => -1
            ];
            Ajax::updateItemJsCode(
                "viewnetworkalias$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>";
            echo "<a class='btn btn-primary' href='javascript:viewAddAlias$rand();'>";
            echo __('Add a network alias') . "</a>\n";
            echo "</div>\n";
        }
        echo "<div id='viewnetworkalias$rand'></div>";

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                'container'     => 'mass' . __CLASS__ . $rand
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
        $header_end .= "<th>" . _n('Internet domain', 'Internet domains', 1) . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($aliases as $data) {
            $showviewjs = ($canedit
                        ? "style='cursor:pointer' onClick=\"viewEditAlias" . $data['id'] . "$rand();\""
                        : '');
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                echo "</td>";
            }
            $name = $data["name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
            }
            echo "<td class='center b' $showviewjs>";
            if ($canedit) {
                echo "\n<script type='text/javascript' >\n";
                echo "function viewEditAlias" . $data["id"] . "$rand() {\n";
                $params = ['type'             => __CLASS__,
                    'parenttype'       => 'NetworkName',
                    'networknames_id'  => $ID,
                    'id'               => $data["id"]
                ];
                Ajax::updateItemJsCode(
                    "viewnetworkalias$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    $params
                );
                echo "};";
                echo "</script>\n";
            }
            echo "<a href='" . static::getFormURLWithID($data["id"]) . "'>" . $name . "</a>";
            echo "</td>";
            echo "<td class='center' $showviewjs>" . Dropdown::getDropdownName(
                "glpi_fqdns",
                $data["fqdns_id"]
            );
            echo "<td class='center' $showviewjs>" . Dropdown::getDropdownName(
                "glpi_entities",
                $data["entities_id"]
            );
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
     * Show the aliases contained by the alias
     *
     * @param CommonGLPI $item          the FQDN owning the aliases
     * @param integer    $withtemplate  withtemplate param
     **/
    public static function showForFQDN(CommonGLPI $item, $withtemplate)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $alias   = new self();
        $address = new NetworkName();
        $item->check($item->getID(), READ);

        if (isset($_GET["start"])) {
            $start = $_GET["start"];
        } else {
            $start = 0;
        }
        if (!empty($_GET["order"])) {
            $order = $_GET["order"];
        } else {
            $order = "alias";
        }

        $number = countElementsInTable($alias->getTable(), ['fqdns_id' => $item->getID() ]);

        echo "<br><div class='center'>";

        if ($number < 1) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . self::getTypeName(1) . "</th><th>" . __('No item found') . "</th></tr>";
            echo "</table>\n";
        } else {
            Html::printAjaxPager(self::getTypeName($number), $start, $number);

            echo "<table class='tab_cadre_fixe'><tr>";

            echo "<th><a href='javascript:reloadTab(\"order=alias\");'>" . self::getTypeName(1) .
              "</a></th>"; // Alias
            echo "<th><a href='javascript:reloadTab(\"order=realname\");'>" . __("Computer's name") .
              "</a></th>";
            echo "<th>" . __('Comments') . "</th>";
            echo "</tr>\n";

            Session::initNavigateListItems(
                $item->getType(),
                //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(
                                            __('%1$s = %2$s'),
                                            self::getTypeName(1),
                                            $item->fields['name']
                                        )
            );

            $iterator = $DB->request([
                'SELECT'    => [
                    'glpi_networkaliases.id AS alias_id',
                    'glpi_networkaliases.name AS alias',
                    'glpi_networknames.id AS address_id',
                    'glpi_networkaliases.comment AS comment'
                ],
                'FROM'      => 'glpi_networkaliases',
                'INNER JOIN' => [
                    'glpi_networknames'  => [
                        'ON' => [
                            'glpi_networkaliases'   => 'networknames_id',
                            'glpi_networknames'     => 'id'
                        ]
                    ]
                ],
                'WHERE'     => ['glpi_networkaliases.fqdns_id' => $item->getID()],
                'ORDERBY'   => $order,
                'LIMIT'     => $_SESSION['glpilist_limit'],
                'START'     => $start
            ]);

            foreach ($iterator as $data) {
                Session::addToNavigateListItems($alias->getType(), $data["alias_id"]);
                if ($address->getFromDB($data["address_id"])) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td><a href='" . $alias->getFormURLWithID($data['alias_id']) . "'>" .
                          $data['alias'] . "</a></td>";
                    echo "<td><a href='" . $address->getLinkURL() . "'>" . $address->getInternetName() .
                    "</a></td>";
                    echo "<td>" . $data['comment'] . "</td>";
                    echo "</tr>\n";
                }
            }

            echo "</table>\n";
            Html::printAjaxPager(self::getTypeName($number), $start, $number);
        }
        echo "</div>\n";
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'NetworkName':
                self::showForNetworkName($item, $withtemplate);
                break;

            case 'FQDN':
                self::showForFQDN($item, $withtemplate);
                break;
        }
        return true;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            $item->getID()
            && $item->can($item->getField('id'), READ)
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                switch ($item->getType()) {
                    case 'NetworkName':
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['networknames_id' => $item->getID() ]
                        );
                        break;

                    case 'FQDN':
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['fqdns_id' => $item->getID() ]
                        );
                }
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_fqdns',
            'field'              => 'fqdn',
            'name'               => FQDN::getTypeName(1),
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_networknames',
            'field'              => 'name',
            'name'               => NetworkName::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }
}
