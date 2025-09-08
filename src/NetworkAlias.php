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
     * Print the network alias form
     *
     * @param integer $ID ID of the item
     * @param array $options
     *     - target for the Form
     *     - withtemplate template or basic computer
     *
     * @return bool
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
        echo "&nbsp;:</td><td>";

        if ($ID <= 0) {
            echo "<input type='hidden' name='networknames_id' value='" . htmlescape($this->fields["networknames_id"]) . "'>";
        }
        $this->displayRecursiveItems($recursiveItems, "Link");
        echo "</td><td>" . __s('Name') . "</td><td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . htmlescape(FQDN::getTypeName()) . "</td><td>";
        Dropdown::show(
            getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
            ['value'        => $this->fields["fqdns_id"],
                'name'         => 'fqdns_id',
                'entity'       => $this->getEntityID(),
                'displaywith'  => ['view'],
            ]
        );
        echo "</td>";
        echo "<td>" . __s('Comments') . "</td>";
        echo "<td><textarea class='form-control' rows='4' name='comment' >" . htmlescape($this->fields["comment"]);
        echo "</textarea></td>";
        echo "</tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * @param string $itemtype
     * @param HTMLTableBase $base
     * @param HTMLTableSuperHeader|null $super
     * @param HTMLTableHeader|null $father
     * @param array $options
     * @throws Exception
     * @since 0.84
     */
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column_name = self::class;
        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        if ($itemtype !== NetworkName::class) {
            return;
        }

        $content = htmlescape(self::getTypeName());
        if (isset($options['column_links'][$column_name])) {
            $content = "<a href='" . htmlescape($options['column_links'][$column_name]) . "'>$content</a>";
        }
        $this_header = $base->addHeader($column_name, $content, $super, $father);
        $this_header->setItemType('NetworkAlias');
    }

    /**
     * @param HTMLTableRow|null $row
     * @param CommonDBTM|null $item
     * @param HTMLTableCell|null $father
     * @param array $options
     * @throws Exception
     * @since 0.84
     */
    public static function getHTMLTableCellsForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        global $DB;

        if (empty($item)) {
            if (empty($father)) {
                return;
            }
            $item = $father->getItem();
        }

        if ($item->getType() !== NetworkName::class) {
            return;
        }

        $column_name = self::class;
        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        $header = $row->getGroup()->getHeaderByName('Internet', $column_name);
        if (!$header) {
            return;
        }

        $createRow            = (isset($options['createRow']) && $options['createRow']);
        $alias                = new self();

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_networkaliases',
            'WHERE'  => ['networknames_id' => $item->getID()],
        ]);

        foreach ($iterator as $line) {
            if ($alias->getFromDB($line["id"])) {
                if ($createRow) {
                    $row = $row->createRow();
                }

                $content = '<a href="' . htmlescape($alias->getLinkURL()) . '">'
                    . htmlescape($alias->getInternetName())
                    . '</a>';
                $row->addCell($header, $content, $father, $alias);
            }
        }
    }

    /**
     * Show aliases for an item from its form
     *
     * Beware that the rendering can be different if readden from direct item form (ie : add new
     * NetworkAlias, remove, ...) or if readden from item of the item (for instance from the computer
     * form through NetworkPort::ShowForItem and NetworkName::ShowForItem).
     *
     * @param NetworkName $item
     * @param integer $withtemplate
     * @return false|void
     */
    public static function showForNetworkName(NetworkName $item, $withtemplate = 0)
    {
        global $CFG_GLPI, $DB;

        $ID = $item->getID();
        if (!$item->can($ID, READ)) {
            return false;
        }

        $canedit = $item->canEdit($ID);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'FROM'   => 'glpi_networkaliases',
            'WHERE'  => ['networknames_id' => $ID],
        ]);
        $number = count($iterator);

        $aliases = [];
        foreach ($iterator as $line) {
            $aliases[$line["id"]] = $line;
        }

        if ($canedit) {
            echo "<div class='firstbloc'>";

            echo Html::scriptBlock(
                "function viewAddAlias$rand() {"
                . Ajax::updateItemJsCode(
                    "viewnetworkalias$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    [
                        'type'            => self::class,
                        'parenttype'      => 'NetworkName',
                        'networknames_id' => $ID,
                        'id'              => -1,
                    ],
                    display: false
                )
                . "};"
            );

            echo "<a class='btn btn-primary' href='javascript:viewAddAlias$rand();'>";
            echo __s('Add a network alias') . "</a>";
            echo "</div>";
        }
        echo "<div id='viewnetworkalias$rand'></div>";

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . self::class . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                'container'     => 'mass' . self::class . $rand,
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';

        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . __s('Name') . "</th>";
        $header_end .= "<th>" . _sn('Internet domain', 'Internet domains', 1) . "</th>";
        $header_end .= "<th>" . htmlescape(Entity::getTypeName(1)) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($aliases as $data) {
            $id = (int) $data['id'];

            $showviewjs = ($canedit
                        ? "style='cursor:pointer' onClick=\"viewEditAlias" . $id . "$rand();\""
                        : '');
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(self::class, $id);
                echo "</td>";
            }
            $name = $data["name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $id);
            }
            echo "<td class='center b' $showviewjs>";
            if ($canedit) {
                $js = "function viewEditAlias" . $id . "$rand() {";
                $js .= Ajax::updateItemJsCode(
                    "viewnetworkalias$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    [
                        'type'             => self::class,
                        'parenttype'       => 'NetworkName',
                        'networknames_id'  => $ID,
                        'id'               => $id,
                    ],
                    display: false
                );
                $js .= "};";
                echo Html::scriptBlock($js);
            }
            echo "<a href='" . htmlescape(static::getFormURLWithID($id)) . "'>" . htmlescape($name) . "</a>";
            echo "</td>";
            echo "<td class='center' $showviewjs>" . htmlescape(Dropdown::getDropdownName(
                "glpi_fqdns",
                $data["fqdns_id"]
            ));
            echo "<td class='center' $showviewjs>" . htmlescape(Dropdown::getDropdownName(
                "glpi_entities",
                $data["entities_id"]
            ));
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
     * @param FQDN $item The FQDN owning the aliases
     * @param integer $withtemplate
     **/
    public static function showForFQDN(FQDN $item, $withtemplate)
    {
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

        $number = countElementsInTable($alias::getTable(), ['fqdns_id' => $item->getID() ]);

        echo "<br><div class='center'>";

        if ($number < 1) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . htmlescape(self::getTypeName(1)) . "</th><th>" . __s('No results found') . "</th></tr>";
            echo "</table>";
        } else {
            Html::printAjaxPager(self::getTypeName($number), $start, $number);

            echo "<table class='tab_cadre_fixe'><tr>";

            echo "<th><a href='javascript:reloadTab(\"order=alias\");'>" . htmlescape(self::getTypeName(1))
              . "</a></th>"; // Alias
            echo "<th><a href='javascript:reloadTab(\"order=realname\");'>" . __s("Computer's name")
              . "</a></th>";
            echo "<th>" . __s('Comments') . "</th>";
            echo "</tr>";

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
                    'glpi_networkaliases.comment AS comment',
                ],
                'FROM'      => 'glpi_networkaliases',
                'INNER JOIN' => [
                    'glpi_networknames'  => [
                        'ON' => [
                            'glpi_networkaliases'   => 'networknames_id',
                            'glpi_networknames'     => 'id',
                        ],
                    ],
                ],
                'WHERE'     => ['glpi_networkaliases.fqdns_id' => $item->getID()],
                'ORDERBY'   => $order,
                'LIMIT'     => $_SESSION['glpilist_limit'],
                'START'     => $start,
            ]);

            foreach ($iterator as $data) {
                Session::addToNavigateListItems($alias->getType(), $data["alias_id"]);
                if ($address->getFromDB($data["address_id"])) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td><a href='" . htmlescape($alias->getFormURLWithID($data['alias_id'])) . "'>"
                          . htmlescape($data['alias']) . "</a></td>";
                    echo "<td><a href='" . htmlescape($address->getLinkURL()) . "'>" . htmlescape($address->getInternetName())
                    . "</a></td>";
                    echo "<td>" . htmlescape($data['comment']) . "</td>";
                    echo "</tr>";
                }
            }

            echo "</table>";
            Html::printAjaxPager(self::getTypeName($number), $start, $number);
        }
        echo "</div>";
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case NetworkName::class:
                self::showForNetworkName($item, $withtemplate);
                break;
            case FQDN::class:
                self::showForFQDN($item, $withtemplate);
                break;
        }
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            ($item instanceof CommonDBTM)
            && $item->getID()
            && $item->can($item->getField('id'), READ)
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                switch ($item::class) {
                    case NetworkName::class:
                        $nb = countElementsInTable(
                            static::getTable(),
                            ['networknames_id' => $item->getID()]
                        );
                        break;

                    case FQDN::class:
                        $nb = countElementsInTable(
                            static::getTable(),
                            ['fqdns_id' => $item->getID()]
                        );
                }
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
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
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_networknames',
            'field'              => 'name',
            'name'               => NetworkName::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }
}
