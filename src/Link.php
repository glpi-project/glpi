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

/** Link Class
 **/
class Link extends CommonDBTM
{
   // From CommonDBTM
    public $dohistory                   = true;

    public static $rightname = 'link';
    public static $tags      = ['[LOGIN]', '[ID]', '[NAME]', '[LOCATION]', '[LOCATIONID]', '[IP]',
        '[MAC]', '[NETWORK]', '[DOMAIN]', '[SERIAL]', '[OTHERSERIAL]',
        '[USER]', '[GROUP]', '[REALNAME]', '[FIRSTNAME]'
    ];


    public static function getTypeName($nb = 0)
    {
        return _n('External link', 'External links', $nb);
    }


    /**
     * For plugins, add a tag to the links tags
     *
     * @param $tag    string    class name
     **/
    public static function registerTag($tag)
    {

        if (!in_array($tag, self::$tags)) {
            self::$tags[] = $tag;
        }
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (self::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $entity_criteria = getEntitiesRestrictCriteria(
                    Link::getTable(),
                    '',
                    self::getEntityRestrictForItem($item),
                    $item instanceof CommonDBTM ? $item->maybeRecursive() : false
                );

                $nb = countElementsInTable(
                    ['glpi_links_itemtypes','glpi_links'],
                    [
                        'glpi_links_itemtypes.links_id'  => new \QueryExpression(DBmysql::quoteName('glpi_links.id')),
                        'glpi_links_itemtypes.itemtype'  => $item->getType()
                    ] + $entity_criteria
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showForItem($item);
        return true;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Link_Itemtype', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Link_Itemtype::class,
            ]
        );
    }


    public function getEmpty()
    {
        if (!parent::getEmpty()) {
            return false;
        }

        //Keep the same behavior as in previous versions
        $this->fields['open_window'] = 1;
        return true;
    }


    /**
     * Print the link form
     *
     * @param $ID      integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *
     * @return void
     **/
    public function showForm($ID, array $options = [])
    {

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td height='23'>" . __('Valid tags') . "</td>";
        echo "<td colspan='3'>";

        $count = count(self::$tags);
        $i     = 0;
        foreach (self::$tags as $tag) {
            echo $tag;
            echo "&nbsp;";
            $i++;
            if (($i % 8 == 0) && ($count > 1)) {
                echo "<br>";
            }
        }
        echo "<br>" . __('or') . "<br>[FIELD:<i>" . __('field name in DB') . "</i>] (" . __('Example:') . " [FIELD:name], [FIELD:content], ...)";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
        echo "<td colspan='3'>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Link or filename') . "</td>";
        echo "<td colspan='3'>";
        echo Html::input('link', ['value' => $this->fields['link'], 'size' => 84]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Open in a new window') . "</td><td>";
        Dropdown::showYesNo('open_window', $this->fields['open_window']);
        echo "</td><td colspan='2'>&nbsp;</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('File content') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='data' rows='10' cols='96'>" . $this->fields["data"] . "</textarea>";
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
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
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'link',
            'name'               => __('Link or filename'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    /**
     * Generate link
     *
     * @param $link    string   original string content
     * @param $item             CommonDBTM object: item used to make replacements
     *
     * @return array of link contents (may have several when item have several IP / MAC cases)
     **/
    public static function generateLinkContents($link, CommonDBTM $item)
    {
        global $DB, $CFG_GLPI;

       // Replace [FIELD:<field name>]
        $matches = [];
        if (preg_match_all('/\[FIELD:(\w+)\]/', $link, $matches)) {
            foreach ($matches[1] as $key => $field) {
                $item::unsetUndisclosedFields($item->fields);
                if ($item->isField($field)) {
                    $link = str_replace($matches[0][$key], $item->getField($field), $link);
                }
            }
        }

        if (strstr($link, "[ID]")) {
            $link = str_replace("[ID]", $item->fields['id'], $link);
        }
        if (
            strstr($link, "[LOGIN]")
            && isset($_SESSION["glpiname"])
        ) {
            $link = str_replace("[LOGIN]", $_SESSION["glpiname"], $link);
        }

        if (strstr($link, "[NAME]")) {
            $link = str_replace("[NAME]", $item->getName(), $link);
        }
        if (
            strstr($link, "[SERIAL]")
            && $item->isField('serial')
        ) {
            $link = str_replace("[SERIAL]", $item->getField('serial'), $link);
        }
        if (
            strstr($link, "[OTHERSERIAL]")
            && $item->isField('otherserial')
        ) {
            $link = str_replace("[OTHERSERIAL]", $item->getField('otherserial'), $link);
        }
        if (
            strstr($link, "[LOCATIONID]")
            && $item->isField('locations_id')
        ) {
            $link = str_replace("[LOCATIONID]", $item->getField('locations_id'), $link);
        }
        if (
            strstr($link, "[LOCATION]")
            && $item->isField('locations_id')
        ) {
            $link = str_replace(
                "[LOCATION]",
                Dropdown::getDropdownName(
                    "glpi_locations",
                    $item->getField('locations_id')
                ),
                $link
            );
        }
        if (
            strstr($link, "[DOMAIN]")
            && in_array($item->getType(), $CFG_GLPI['domain_types'], true)
        ) {
            $domain_table = Domain::getTable();
            $domain_item_table = Domain_Item::getTable();
            $iterator = $DB->request([
                'SELECT'    => ['name'],
                'FROM'      => $domain_table,
                'LEFT JOIN' => [
                    $domain_item_table => [
                        'FKEY'   => [
                            $domain_table        => 'id',
                            $domain_item_table   => 'domains_id'
                        ],
                        'AND'    => ['itemtype' => $item->getType()]
                    ]
                ],
                'WHERE'     => ['items_id' => $item->getID()]
            ]);
            if ($iterator->count()) {
                $link = str_replace("[DOMAIN]", $iterator->current()['name'], $link);
            }
        }
        if (
            strstr($link, "[NETWORK]")
            && $item->isField('networks_id')
        ) {
            $link = str_replace(
                "[NETWORK]",
                Dropdown::getDropdownName(
                    "glpi_networks",
                    $item->getField('networks_id')
                ),
                $link
            );
        }
        if (
            strstr($link, "[USER]")
            && $item->isField('users_id')
        ) {
            $link = str_replace(
                "[USER]",
                Dropdown::getDropdownName(
                    "glpi_users",
                    $item->getField('users_id')
                ),
                $link
            );
        }
        if (
            strstr($link, "[GROUP]")
            && $item->isField('groups_id')
        ) {
            $link = str_replace(
                "[GROUP]",
                Dropdown::getDropdownName(
                    "glpi_groups",
                    $item->getField('groups_id')
                ),
                $link
            );
        }
        if (
            strstr($link, "[REALNAME]")
            && $item->isField('realname')
        ) {
            $link = str_replace("[REALNAME]", $item->getField('realname'), $link);
        }
        if (
            strstr($link, "[FIRSTNAME]")
            && $item->isField('firstname')
        ) {
            $link = str_replace("[FIRSTNAME]", $item->getField('firstname'), $link);
        }

        $replace_IP  = strstr($link, "[IP]");
        $replace_MAC = strstr($link, "[MAC]");

        if (!$replace_IP && !$replace_MAC) {
            return [$link];
        }
       // Return several links id several IP / MAC

        $ipmac = [];
        if (get_class($item) == 'NetworkEquipment') {
            if ($replace_IP) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'glpi_ipaddresses.id',
                        'glpi_ipaddresses.name AS ip',
                    ],
                    'FROM'   => 'glpi_networknames',
                    'INNER JOIN'   => [
                        'glpi_ipaddresses'   => [
                            'ON' => [
                                'glpi_ipaddresses'   => 'items_id',
                                'glpi_networknames'  => 'id', [
                                    'AND' => [
                                        'glpi_ipaddresses.itemtype' => 'NetworkName'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'WHERE'        => [
                        'glpi_networknames.items_id'  => $item->getID(),
                        'glpi_networknames.itemtype'  => ['NetworkEquipment']
                    ]
                ]);
                foreach ($iterator as $data2) {
                     $ipmac['ip' . $data2['id']]['ip']  = $data2["ip"];
                     $ipmac['ip' . $data2['id']]['mac'] = $item->getField('mac');
                }
            }

            if ($replace_MAC) {
               // If there is no entry, then, we must at least define the mac of the item ...
                if (count($ipmac) == 0) {
                    $ipmac['mac0']['ip']    = '';
                    $ipmac['mac0']['mac']   = $item->getField('mac');
                }
            }
        }

        if ($replace_IP) {
            $iterator = $DB->request([
                'SELECT' => [
                    'glpi_ipaddresses.id',
                    'glpi_ipaddresses.name AS ip',
                    'glpi_networkports.mac'
                ],
                'FROM'   => 'glpi_networkports',
                'INNER JOIN'   => [
                    'glpi_networknames'   => [
                        'ON' => [
                            'glpi_networknames'  => 'items_id',
                            'glpi_networkports'  => 'id', [
                                'AND' => [
                                    'glpi_networknames.itemtype' => 'NetworkPort'
                                ]
                            ]
                        ]
                    ],
                    'glpi_ipaddresses'   => [
                        'ON' => [
                            'glpi_ipaddresses'   => 'items_id',
                            'glpi_networknames'  => 'id', [
                                'AND' => [
                                    'glpi_ipaddresses.itemtype' => 'NetworkName'
                                ]
                            ]
                        ]
                    ]
                ],
                'WHERE'        => [
                    'glpi_networkports.items_id'  => $item->getID(),
                    'glpi_networkports.itemtype'  => $item->getType()
                ]
            ]);
            foreach ($iterator as $data2) {
                $ipmac['ip' . $data2['id']]['ip']  = $data2["ip"];
                $ipmac['ip' . $data2['id']]['mac'] = $data2["mac"];
            }
        }

        if ($replace_MAC) {
            $criteria = [
                'SELECT' => [
                    'glpi_networkports.id',
                    'glpi_networkports.mac'
                ],
                'FROM'   => 'glpi_networkports',
                'WHERE'  => [
                    'glpi_networkports.items_id'  => $item->getID(),
                    'glpi_networkports.itemtype'  => $item->getType()
                ],
                'GROUP' => 'glpi_networkports.mac'
            ];

            if ($replace_IP) {
                $criteria['LEFT JOIN'] = [
                    'glpi_networknames' => [
                        'ON' => [
                            'glpi_networknames'  => 'items_id',
                            'glpi_networkports'  => 'id', [
                                'AND' => [
                                    'glpi_networknames.itemtype'  => 'NetworkPort'
                                ]
                            ]
                        ]
                    ]
                ];
                $criteria['WHERE']['glpi_networknames.id'] = null;
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data2) {
                $ipmac['mac' . $data2['id']]['ip']  = '';
                $ipmac['mac' . $data2['id']]['mac'] = $data2["mac"];
            }
        }

        $links = [];
        if (count($ipmac) > 0) {
            foreach ($ipmac as $key => $val) {
                $tmplink = $link;
                $disp    = 1;
                if (strstr($link, "[IP]")) {
                    if (empty($val['ip'])) {
                        $disp = 0;
                    } else {
                        $tmplink = str_replace("[IP]", $val['ip'], $tmplink);
                    }
                }
                if (strstr($link, "[MAC]")) {
                    if (empty($val['mac'])) {
                        $disp = 0;
                    } else {
                        $tmplink = str_replace("[MAC]", $val['mac'], $tmplink);
                    }
                }

                if ($disp) {
                    $links[$key] = $tmplink;
                }
            }
        }

        if (count($links)) {
            return $links;
        }
        return [$link];
    }


    /**
     * Show Links for an item
     *
     * @param $item                     CommonDBTM object
     * @param $withtemplate    integer  withtemplate param (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        if (!self::canView()) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }

        $iterator = self::getLinksDataForItem($item);

        echo "<div class='spaced'><table class='tab_cadrehov'>";

        echo "<tr class='tab_bg_2'>";
        echo "<th>" . self::getTypeName(Session::getPluralNumber()) . "</th>";
        echo "<th class='right'>";
        if (self::canUpdate()) {
            echo '<a class="btn btn-primary" href="' . self::getSearchURL() . '">';
            echo '<i class="fas fa-cog"></i>&nbsp;';
            echo __('Configure');
            echo '</a>';
        }
        echo "</th>";
        echo "</tr>";
        if (count($iterator)) {
            foreach ($iterator as $data) {
                $links = self::getAllLinksFor($item, $data);

                foreach ($links as $link) {
                    echo "<tr class='tab_bg_2'>";
                    echo "<td colspan='2'>$link</td></tr>";
                }
            }
        } else {
            echo "<tr class='tab_bg_2'><td>" . __('No link defined') . "</td></tr>";
        }
        echo "</table></div>";
    }

    /**
     * Show Links for an item
     *
     * @since 0.85
     *
     * @param $item                        CommonDBTM object
     * @param $params    array of params : must contain id / name / link / data
     **/
    public static function getAllLinksFor($item, $params = [])
    {
        global $CFG_GLPI;

        $computedlinks = [];
        if (
            !isset($params['name'])
            || !isset($params['link'])
            || !isset($params['data'])
            || !isset($params['id'])
        ) {
            return $computedlinks;
        }

        if (!isset($params['open_window'])) {
            $params['open_window'] = true;
        }

        if (empty($params['name'])) {
            $params['name'] = $params['link'];
        }

        $names = $item->generateLinkContents($params['name'], $item);
        $file  = trim($params['data']);

        if (empty($file)) {
           // Generate links
            $links = $item->generateLinkContents($params['link'], $item);
            $i     = 1;
            foreach ($links as $key => $val) {
                $name    = (isset($names[$key]) ? $names[$key] : reset($names));
                $url     = $val;
                $newlink = "<a href='$url'";
                if ($params['open_window']) {
                    $newlink .= " target='_blank'";
                }
                $newlink          .= ">";
                $linkname          = sprintf(__('%1$s #%2$s'), $name, $i);
                $newlink          .= sprintf(__('%1$s: %2$s'), $linkname, $val);
                $newlink          .= "</a>";
                $computedlinks[]   = $newlink;
                $i++;
            }
        } else {
           // Generate files
            $files = $item->generateLinkContents($params['link'], $item);
            $links = $item->generateLinkContents($params['data'], $item);
            $i     = 1;
            foreach ($links as $key => $val) {
                $name = (isset($names[$key]) ? $names[$key] : reset($names));
                if (isset($files[$key])) {
                    // a different name for each file, ex name = foo-[IP].txt
                    $file = $files[$key];
                } else {
                   // same name for all files, ex name = foo.txt
                    $file = reset($files);
                }
                $url             = $CFG_GLPI["root_doc"] . "/front/link.send.php?lID=" . $params['id'] .
                                 "&amp;itemtype=" . $item->getType() .
                                 "&amp;id=" . $item->getID() . "&amp;rank=$key";
                $newlink         = "<a href='$url' target='_blank'>";
                $newlink        .= "<i class='fa-lg fa-fw fas fa-link'></i>&nbsp;";
                $linkname        = sprintf(__('%1$s #%2$s'), $name, $i);
                $newlink        .= sprintf(__('%1$s: %2$s'), $linkname, $val);
                $newlink        .= "</a>";
                $computedlinks[] = $newlink;
                $i++;
            }
        }

        return $computedlinks;
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

       // "Fake" search options, processing is done in Search::giveItem() for glpi_links._virtual
        $newtab = [
            'id'                 => '145',
            'table'              => 'glpi_links',
            'field'              => '_virtual',
            'name'               => _n('External link', 'External links', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'forcegroupby'       => true,
            'nosort'             => '1',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_links_itemtypes',
                    'joinparams'         => [
                        'jointype'           => 'itemtypeonly'
                    ]
                ]
            ]
        ];

        $tab[] = $newtab;

        return $tab;
    }

    public static function getEntityRestrictForItem(CommonGLPI $item)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        $restrict = $item->getEntityID();
        if ($item->getType() == 'User') {
            $restrict = Profile_User::getEntitiesForUser($item->getID());
        }

        return $restrict;
    }

    public static function getLinksDataForItem(CommonDBTM $item)
    {
        global $DB;

        $restrict = self::getEntityRestrictForItem($item);

        return $DB->request([
            'SELECT'       => [
                'glpi_links.id',
                'glpi_links.link AS link',
                'glpi_links.name AS name',
                'glpi_links.data AS data',
                'glpi_links.open_window AS open_window'
            ],
            'FROM'         => 'glpi_links',
            'INNER JOIN'   => [
                'glpi_links_itemtypes'  => [
                    'ON' => [
                        'glpi_links_itemtypes'  => 'links_id',
                        'glpi_links'            => 'id'
                    ]
                ]
            ],
            'WHERE'        => [
                'glpi_links_itemtypes.itemtype'  => $item->getType(),
            ] + getEntitiesRestrictCriteria('glpi_links', 'entities_id', $restrict, true),
            'ORDERBY'      => 'name'
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-link";
    }
}
