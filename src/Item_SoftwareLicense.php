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
 * Manage link between items and software licenses.
 */
class Item_SoftwareLicense extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1 = 'itemtype';
    public static $items_id_1 = 'items_id';

    public static $itemtype_2 = 'SoftwareLicense';
    public static $items_id_2 = 'softwarelicenses_id';


    public function post_addItem()
    {

        SoftwareLicense::updateValidityIndicator($this->fields['softwarelicenses_id']);

        parent::post_addItem();
    }


    public function post_deleteFromDB()
    {

        SoftwareLicense::updateValidityIndicator($this->fields['softwarelicenses_id']);

        parent::post_deleteFromDB();
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
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
            'id'                 => '4',
            'table'              => 'glpi_softwarelicenses',
            'field'              => 'name',
            'name'               => _n('License', 'Licenses', 1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'comments'           => true,
            'nosort'             => true,
            'massiveaction'      => false,
            'additionalfields'   => ['itemtype']
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _x('software', 'Request source'),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        $input = $ma->getInput();
        switch ($ma->getAction()) {
            case 'move_license':
                if (isset($input['options'])) {
                    if (isset($input['options']['move'])) {
                        SoftwareLicense::dropdown([
                            'condition' => [
                                'glpi_softwarelicenses.softwares_id' => $input['options']['move']['softwares_id']
                            ],
                            'used'      => $input['options']['move']['used']
                        ]);
                         echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                         return true;
                    }
                }
                return false;

            case 'add':
                Software::dropdownLicenseToInstall(
                    'peer_softwarelicenses_id',
                    $_SESSION["glpiactive_entity"]
                );
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']) . "</span>";
                return true;

            case 'add_item':
                /** @var array $CFG_GLPI */
                global $CFG_GLPI;
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_2 center'>";
                echo "<td>";
                $rand = Dropdown::showItemTypes('itemtype', $CFG_GLPI['software_types'], [
                    'width'                 => 'unset'
                ]);

                $p = ['idtable'            => '__VALUE__',
                    'rand'                  => $rand,
                    'name'                  => "items_id",
                    'width'                 => 'unset'
                ];

                Ajax::updateItemOnSelectEvent(
                    "dropdown_itemtype$rand",
                    "results_itemtype$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownAllItems.php",
                    $p
                );

                echo "<span id='results_itemtype$rand'>\n";
                echo "</td><td>";
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']) . "</span>";
                echo "</td></tr>";

                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'move_license':
                $input = $ma->getInput();
                if (isset($input['softwarelicenses_id'])) {
                    foreach ($ids as $id) {
                        if ($item->can($id, UPDATE)) {
                         //Process rules
                            if (
                                $item->update(['id'  => $id,
                                    'softwarelicenses_id'
                                           => $input['softwarelicenses_id']
                                ])
                            ) {
                                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;

            case 'install':
                $csl = new self();
                $csv = new Item_SoftwareVersion();
                foreach ($ids as $id) {
                    if ($csl->getFromDB($id)) {
                        $sl = new SoftwareLicense();

                        if ($sl->getFromDB($csl->fields["softwarelicenses_id"])) {
                            $version = 0;
                            if ($sl->fields["softwareversions_id_use"] > 0) {
                                $version = $sl->fields["softwareversions_id_use"];
                            } else {
                                $version = $sl->fields["softwareversions_id_buy"];
                            }
                            if ($version > 0) {
                                $params = [
                                    'items_id'  => $csl->fields['items_id'],
                                    'itemtype'  => $csl->fields['itemtype'],
                                    'softwareversions_id' => $version
                                ];
                               //Get software name and manufacturer
                                if ($csv->can(-1, CREATE, $params)) {
                              //Process rules
                                    if ($csv->add($params)) {
                                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                    } else {
                                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                    }
                                } else {
                                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                                }
                            } else {
                                Session::addMessageAfterRedirect(__('A version is required!'), false, ERROR);
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    }
                }
                return;

            case 'add_item':
                $item_licence = new Item_SoftwareLicense();
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $input = [
                        'softwarelicenses_id'   => $id,
                        'items_id'        => $input['items_id'],
                        'itemtype'        => $input['itemtype']
                    ];
                    if ($item_licence->can(-1, UPDATE, $input)) {
                        if ($item_licence->add($input)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    /**
     * Get number of installed licenses of a license
     *
     * @param integer $softwarelicenses_id license ID
     * @param integer|string $entity       to search for item in (default = all entities)
     *                                     (default '') -1 means no entity restriction
     * @param string $itemtype             Item type to filter on. Use null for all itemtypes
     *
     * @return integer number of installations
     **/
    public static function countForLicense($softwarelicenses_id, $entity = '', $itemtype = null)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => ['itemtype'],
            'DISTINCT'  => true,
            'FROM'      => self::getTable(__CLASS__),
            'WHERE'     => [
                'softwarelicenses_id'   => $softwarelicenses_id
            ]
        ]);

        $target_types = [];
        if ($itemtype !== null) {
            $target_types = [$itemtype];
        } else {
            foreach ($iterator as $data) {
                $target_types[] = $data['itemtype'];
            }
        }

        $count = 0;
        foreach ($target_types as $itemtype) {
            $itemtable = $itemtype::getTable();
            $request = [
                'FROM'         => 'glpi_items_softwarelicenses',
                'COUNT'        => 'cpt',
                'INNER JOIN'   => [
                    $itemtable  => [
                        'FKEY'   => [
                            $itemtable                    => 'id',
                            'glpi_items_softwarelicenses' => 'items_id', [
                                'AND' => [
                                    'glpi_items_softwarelicenses.itemtype' => $itemtype
                                ]
                            ]
                        ]
                    ]
                ],
                'WHERE'        => [
                    'glpi_items_softwarelicenses.softwarelicenses_id'     => $softwarelicenses_id,
                    'glpi_items_softwarelicenses.is_deleted'              => 0
                ]
            ];
            if ($entity !== -1) {
                $request['WHERE'] += getEntitiesRestrictCriteria($itemtable, '', $entity);
            }
            $item = new $itemtype();
            if ($item->maybeDeleted()) {
                $request['WHERE']["$itemtable.is_deleted"] = 0;
            }
            if ($item->maybeTemplate()) {
                $request['WHERE']["$itemtable.is_template"] = 0;
            }
            $count += $DB->request($request)->current()['cpt'];
        }
        return $count;
    }


    /**
     * Get number of installed licenses of a software
     *
     * @param integer $softwares_id software ID
     *
     * @return integer number of installations
     **/
    public static function countForSoftware($softwares_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $license_table = SoftwareLicense::getTable();
        $item_license_table = self::getTable(__CLASS__);

        $iterator = $DB->request([
            'SELECT'    => ['itemtype'],
            'DISTINCT'  => true,
            'FROM'      => $item_license_table,
            'LEFT JOIN' => [
                $license_table => [
                    'FKEY'   => [
                        $license_table       => 'id',
                        $item_license_table  => 'softwarelicenses_id'
                    ]
                ]
            ],
            'WHERE'     => [
                'softwares_id'   => $softwares_id
            ]
        ]);

        $target_types = [];
        foreach ($iterator as $data) {
            $target_types[] = $data['itemtype'];
        }

        $count = 0;
        foreach ($target_types as $itemtype) {
            $itemtable = $itemtype::getTable();
            $request = [
                'FROM'         => 'glpi_softwarelicenses',
                'COUNT'        => 'cpt',
                'INNER JOIN'   => [
                    'glpi_items_softwarelicenses' => [
                        'FKEY'   => [
                            'glpi_softwarelicenses'          => 'id',
                            'glpi_items_softwarelicenses'    => 'softwarelicenses_id'
                        ]
                    ],
                    $itemtable  => [
                        'FKEY'   => [
                            $itemtable                    => 'id',
                            'glpi_items_softwarelicenses' => 'items_id', [
                                'AND' => [
                                    'glpi_items_softwarelicenses.itemtype' => $itemtype
                                ]
                            ]
                        ]
                    ]
                ],
                'WHERE'        => [
                    'glpi_softwarelicenses.softwares_id'      => $softwares_id,
                    'glpi_items_softwarelicenses.is_deleted'  => 0
                ] + getEntitiesRestrictCriteria($itemtable)
            ];
            $item = new $itemtype();
            if ($item->maybeDeleted()) {
                $request['WHERE']["$itemtable.is_deleted"] = 0;
            }
            if ($item->maybeTemplate()) {
                $request['WHERE']["$itemtable.is_template"] = 0;
            }
            $count += $DB->request($request)->current()['cpt'];
        }
        return $count;
    }


    /**
     * Show number of installation per entity
     *
     * @param SoftwareLicense $license SoftwareLicense instance
     *
     * @return void
     **/
    public static function showForLicenseByEntity(SoftwareLicense $license)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $softwarelicense_id = $license->getField('id');
        $license_table = SoftwareLicense::getTable();
        $item_license_table = self::getTable(__CLASS__);

        if (!Software::canView() || !$softwarelicense_id) {
            return false;
        }

        echo "<div class='center'>";
        echo "<table class='tab_cadre'><tr>";
        echo "<th>" . Entity::getTypeName(1) . "</th>";
        echo "<th>" . __('Number of affected items') . "</th>";
        echo "</tr>\n";

        $tot = 0;

        $iterator = $DB->request([
            'SELECT' => ['id', 'completename'],
            'FROM'   => 'glpi_entities',
            'WHERE'  => getEntitiesRestrictCriteria('glpi_entities'),
            'ORDER'  => ['completename']
        ]);

        $tab = "&nbsp;&nbsp;&nbsp;&nbsp;";
        foreach ($iterator as $data) {
            $itemtype_iterator = $DB->request([
                'SELECT'    => ['itemtype'],
                'DISTINCT'  => true,
                'FROM'      => $item_license_table,
                'LEFT JOIN' => [
                    $license_table => [
                        'FKEY'   => [
                            $license_table       => 'id',
                            $item_license_table  => 'softwarelicenses_id'
                        ]
                    ]
                ],
                'WHERE'     => [
                    $item_license_table . '.softwarelicenses_id'   => $softwarelicense_id
                ] + getEntitiesRestrictCriteria($license_table, '', $data['id'])
            ]);

            $target_types = [];
            foreach ($itemtype_iterator as $type) {
                 $target_types[] = $type['itemtype'];
            }

            if (count($target_types)) {
                echo "<tr class='tab_bg_2'><td colspan='2'>{$data["completename"]}</td></tr>";
                foreach ($target_types as $itemtype) {
                    $nb = self::countForLicense($softwarelicense_id, $data['id'], $itemtype);
                    echo "<tr class='tab_bg_2'><td>$tab$tab{$itemtype::getTypeName()}</td>";
                    echo "<td class='numeric'>{$nb}</td></tr>\n";
                    $tot += $nb;
                }
            }
        }

        if ($tot > 0) {
            echo "<tr class='tab_bg_1'><td class='center b'>" . __('Total') . "</td>";
            echo "<td class='numeric b '>" . $tot . "</td></tr>\n";
        } else {
            echo "<tr class='tab_bg_1'><td colspan='2 b'>" . __('No item found') . "</td></tr>\n";
        }
        echo "</table></div>";
    }


    /**
     * Show items linked to a License
     *
     * @param SoftwareLicense $license SoftwareLicense instance
     *
     * @return void
     **/
    public static function showForLicense(SoftwareLicense $license)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $searchID = $license->getField('id');

        if (!Software::canView() || !$searchID) {
            return false;
        }

        $canedit         = Session::haveRightsOr("software", [CREATE, UPDATE, DELETE, PURGE]);
        $canshowitems  = [];
        $item_license_table = self::getTable(__CLASS__);

        if (isset($_GET["start"])) {
            $start = $_GET["start"];
        } else {
            $start = 0;
        }

        if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
            $order = "DESC";
        } else {
            $order = "ASC";
        }

        if (isset($_GET["sort"]) && !empty($_GET["sort"])) {
           // manage several param like location,compname : order first
            $tmp  = explode(",", $_GET["sort"]);
            $sort = "`" . implode("` $order,`", $tmp) . "`";
        } else {
            $sort = "`entity` $order, `itemname`";
        }

       //SoftwareLicense ID
        $number = self::countForLicense($searchID);

        echo "<div class='center'>";

       //If the number of linked assets have reached the number defined in the license,
       //and over-quota is not allowed, do not allow to add more assets
        if (
            $canedit
            && ($license->getField('number') == -1 || $number < $license->getField('number')
            || $license->getField('allow_overquota'))
        ) {
            echo "<form method='post' action='" . Item_SoftwareLicense::getFormURL() . "'>";
            echo "<input type='hidden' name='softwarelicenses_id' value='$searchID'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2 center'>";
            echo "<td>";

            $rand = mt_rand();
            Dropdown::showItemTypes('itemtype', $CFG_GLPI['software_types'], [
                'value'                 => 'Computer',
                'rand'                  => $rand,
                'width'                 => 'unset',
                'display_emptychoice'   => false
            ]);

            $p = ['idtable'            => '__VALUE__',
                'rand'                  => $rand,
                'name'                  => "items_id",
                'width'                 => 'unset'
            ];

            Ajax::updateItemOnSelectEvent(
                "dropdown_itemtype$rand",
                "results_itemtype$rand",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownAllItems.php",
                $p
            );

           // We have a preselected value, so we want to trigger the item list to show immediately
            $js = <<<JAVASCRIPT
$(document).ready(function() {
   $("#dropdown_itemtype$rand").trigger({
      type: 'change'
   });
});
JAVASCRIPT;
            echo Html::scriptBlock($js);

            echo "<span id='results_itemtype$rand'>\n";
            echo "</td>";
            echo "<td><input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            $ajax_url = $CFG_GLPI['root_doc'] . '/ajax/dropdownAllItems.php';
            $js = <<<JAVASCRIPT
function updateItemDropdown(itemtype_el) {
   $.ajax({
      method: "POST",
      url: "$ajax_url",
      data: {
         name: 'items_id',
         idtable: itemtype_el.value
      },
      success: function(data) {
         $("[name='items_id']").select2('destroy').empty().replaceWith(data);
      }
   });
}
JAVASCRIPT;
            echo Html::scriptBlock($js);
        }

        if ($number < 1) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr>";
            echo "</table></div>\n";
            return;
        }

       // Display the pager
        Html::printAjaxPager(__('Affected items'), $start, $number);

        $queries = [];
        foreach ($CFG_GLPI['software_types'] as $itemtype) {
            $canshowitems[$itemtype] = $itemtype::canView();
            $itemtable = $itemtype::getTable();
            $query = [
                'SELECT' => [
                    $item_license_table . '.*',
                    'glpi_softwarelicenses.name AS license',
                    'glpi_softwarelicenses.id AS vID',
                    'glpi_softwarelicenses.softwares_id AS softid',
                    "{$itemtable}.name AS itemname",
                    "{$itemtable}.id AS iID",
                    new QueryExpression($DB->quoteValue($itemtype) . " AS " . $DB->quoteName('item_type')),
                ],
                'FROM'   => $item_license_table,
                'INNER JOIN' => [
                    'glpi_softwarelicenses' => [
                        'FKEY'   => [
                            $item_license_table     => 'softwarelicenses_id',
                            'glpi_softwarelicenses' => 'id'
                        ]
                    ]
                ],
                'LEFT JOIN' => [
                    $itemtable => [
                        'FKEY'   => [
                            $item_license_table     => 'items_id',
                            $itemtable        => 'id', [
                                'AND' => [
                                    $item_license_table . '.itemtype'  => $itemtype
                                ]
                            ]
                        ]
                    ]
                ],
                'WHERE'     => [
                    'glpi_softwarelicenses.id'                   => $searchID,
                    'glpi_items_softwarelicenses.is_deleted'     => 0
                ]
            ];
            if ($DB->fieldExists($itemtable, 'serial')) {
                $query['SELECT'][] = $itemtable . '.serial';
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName($itemtable . ".serial")
                );
            }
            if ($DB->fieldExists($itemtable, 'otherserial')) {
                $query['SELECT'][] = $itemtable . '.otherserial';
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName($itemtable . ".otherserial")
                );
            }
            if ($DB->fieldExists($itemtable, 'users_id')) {
                $query['SELECT'][] = 'glpi_users.name AS username';
                $query['SELECT'][] = 'glpi_users.id AS userid';
                $query['SELECT'][] = 'glpi_users.realname AS userrealname';
                $query['SELECT'][] = 'glpi_users.firstname AS userfirstname';
                $query['LEFT JOIN']['glpi_users'] = [
                    'FKEY'   => [
                        $itemtable     => 'users_id',
                        'glpi_users'   => 'id'
                    ]
                ];
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName($itemtable . ".username")
                );
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('-1') . " AS " . $DB->quoteName($itemtable . ".userid")
                );
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName($itemtable . ".userrealname")
                );
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName($itemtable . ".userfirstname")
                );
            }
            $entity_fkey  = Entity::getForeignKeyField();
            $entity_table = Entity::getTable();
            if ($DB->fieldExists($itemtable, $entity_fkey)) {
                $query['SELECT'][] = sprintf('%s.completename AS entity', $entity_table);
                $query['LEFT JOIN'][$entity_table] = [
                    'FKEY'   => [
                        $itemtable    => $entity_fkey,
                        $entity_table => 'id'
                    ]
                ];
                $query['WHERE'] += getEntitiesRestrictCriteria($itemtable, '', '', true);
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName('entity')
                );
            }
            $location_fkey  = Location::getForeignKeyField();
            $location_table = Location::getTable();
            if ($DB->fieldExists($itemtable, $location_fkey)) {
                $query['SELECT'][] = sprintf('%s.completename AS location', $location_table);
                $query['LEFT JOIN'][$location_table] = [
                    'FKEY'   => [
                        $itemtable      => $location_fkey,
                        $location_table => 'id'
                    ]
                ];
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName('location')
                );
            }
            $state_fkey  = State::getForeignKeyField();
            $state_table = State::getTable();
            if ($DB->fieldExists($itemtable, $state_fkey)) {
                $query['SELECT'][] = sprintf('%s.name AS state', $state_table);
                $query['LEFT JOIN'][$state_table] = [
                    'FKEY'   => [
                        $itemtable   => $state_fkey,
                        $state_table => 'id'
                    ]
                ];
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName('state')
                );
            }
            $group_fkey  = Group::getForeignKeyField();
            $group_table = Group::getTable();
            if ($DB->fieldExists($itemtable, $group_fkey)) {
                $query['SELECT'][] = sprintf('%s.name AS groupe', $group_table);
                $query['LEFT JOIN'][$group_table] = [
                    'FKEY'   => [
                        $itemtable    => $group_fkey,
                        $group_table  => 'id'
                    ]
                ];
            } else {
                $query['SELECT'][] = new QueryExpression(
                    $DB->quoteValue('') . " AS " . $DB->quoteName('groupe')
                );
            }
            if ($DB->fieldExists($itemtable, 'is_deleted')) {
                $query['WHERE']["{$itemtable}.is_deleted"] = 0;
            }
            if ($DB->fieldExists($itemtable, 'is_template')) {
                $query['WHERE']["{$itemtable}.is_template"] = 0;
            }
            $queries[] = $query;
        }
        $union = new QueryUnion($queries, true);
        $criteria = [
            'SELECT' => [],
            'FROM'   => $union,
            'ORDER'        => "$sort $order",
            'LIMIT'        => $_SESSION['glpilist_limit'],
            'START'        => $start
        ];
        $iterator = $DB->request($criteria);

        $rand = mt_rand();

        if ($data = $iterator->current()) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], count($iterator)),
                    'container'        => 'mass' . __CLASS__ . $rand,
                    'specific_actions' => ['purge' => _x('button', 'Delete permanently')]
                ];

               // show transfer only if multi licenses for this software
                if (self::countLicenses($data['softid']) > 1) {
                    $massiveactionparams['specific_actions'][__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'move_license'] = _x('button', 'Move');
                }

               // Options to update license
                $massiveactionparams['extraparams']['options']['move']['used'] = [$searchID];
                $massiveactionparams['extraparams']['options']['move']['softwares_id']
                                                                  = $license->fields['softwares_id'];

                Html::showMassiveActions($massiveactionparams);
            }

            $soft       = new Software();
            $soft->getFromDB($license->fields['softwares_id']);
            $showEntity = ($license->isRecursive());
            $linkUser   = User::canView();

            $text = sprintf(__('%1$s = %2$s'), Software::getTypeName(1), $soft->fields["name"]);
            $text = sprintf(__('%1$s - %2$s'), $text, $data["license"]);

            Session::initNavigateListItems($data['item_type'], $text);

            echo "<table class='tab_cadre_fixehov'>";

            $columns = ['item_type' => __('Item type'),
                'itemname'          => __('Name'),
                'entity'            => Entity::getTypeName(1),
                'serial'            => __('Serial number'),
                'otherserial'       => __('Inventory number'),
                'location,itemname' => Location::getTypeName(1),
                'state,itemname'    => __('Status'),
                'groupe,itemname'   => Group::getTypeName(1),
                'username,itemname' => User::getTypeName(1)
            ];
            if (!$showEntity) {
                unset($columns['entity']);
            }

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_begin  .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_end    .= "</th>";
            }

            foreach ($columns as $key => $val) {
               // Non order column
                if ($key[0] == '_') {
                    $header_end .= "<th>$val</th>";
                } else {
                    $header_end .= "<th" . ($sort == "`$key`" ? " class='order_$order'" : '') . ">" .
                              "<a href='javascript:reloadTab(\"sort=$key&amp;order=" .
                              (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>$val</a></th>";
                }
            }

            $header_end .= "</tr>\n";
            echo $header_begin . $header_top . $header_end;

            do {
                Session::addToNavigateListItems($data['item_type'], $data["iID"]);

                echo "<tr class='tab_bg_2'>";
                if ($canedit) {
                    echo "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $data["id"]) . "</td>";
                }

                echo "<td>{$data['item_type']}</td>";
                $itemname = $data['itemname'];
                if (empty($itemname) || $_SESSION['glpiis_ids_visible']) {
                    $itemname = sprintf(__('%1$s (%2$s)'), $itemname, $data['iID']);
                }

                if ($canshowitems[$data['item_type']]) {
                    echo "<td><a href='" . $data['item_type']::getFormURLWithID($data['iID']) . "'>$itemname</a></td>";
                } else {
                    echo "<td>" . $itemname . "</td>";
                }

                if ($showEntity) {
                    echo "<td>" . $data['entity'] . "</td>";
                }
                echo "<td>" . $data['serial'] . "</td>";
                echo "<td>" . $data['otherserial'] . "</td>";
                echo "<td>" . $data['location'] . "</td>";
                echo "<td>" . $data['state'] . "</td>";
                echo "<td>" . $data['groupe'] . "</td>";
                echo "<td>" . formatUserName(
                    $data['userid'],
                    $data['username'],
                    $data['userrealname'],
                    $data['userfirstname'],
                    $linkUser
                ) . "</td>";
                echo "</tr>\n";

                $iterator->next();
            } while ($data = $iterator->current());
            echo $header_begin . $header_bottom . $header_end;
            echo "</table>\n";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else { // Not found
            echo __('No item found');
        }
        Html::printAjaxPager(__('Affected items'), $start, $number);

        echo "</div>\n";
    }


    /**
     * Update license associated on a computer
     *
     * @param integer $licID               ID of the install software lienk
     * @param integer $softwarelicenses_id ID of the new license
     *
     * @return void
     **/
    public function upgrade($licID, $softwarelicenses_id)
    {

        if ($this->getFromDB($licID)) {
            $items_id = $this->fields['items_id'];
            $itemtype = $this->fields['itemtype'];
            $this->delete(['id' => $licID]);
            $this->add([
                'items_id'              => $items_id,
                'itemtype'              => $itemtype,
                'softwarelicenses_id'   => $softwarelicenses_id
            ]);
        }
    }


    /**
     * Get licenses list corresponding to an installation
     *
     * @param string $itemtype          Type of item
     * @param integer $items_id         ID of the item
     * @param integer $softwareversions_id ID of the version
     *
     * @return array
     **/
    public static function getLicenseForInstallation($itemtype, $items_id, $softwareversions_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $lic = [];
        $item_license_table = self::getTable(__CLASS__);

        $iterator = $DB->request([
            'SELECT'       => [
                'glpi_softwarelicenses.*',
                'glpi_softwarelicensetypes.name AS type'
            ],
            'FROM'         => 'glpi_softwarelicenses',
            'INNER JOIN'   => [
                $item_license_table  => [
                    'FKEY'   => [
                        $item_license_table     => 'softwarelicenses_id',
                        'glpi_softwarelicenses' => 'id'
                    ]
                ]
            ],
            'LEFT JOIN'    => [
                'glpi_softwarelicensetypes'   => [
                    'FKEY'   => [
                        'glpi_softwarelicenses'       => 'softwarelicensetypes_id',
                        'glpi_softwarelicensetypes'   => 'id'
                    ]
                ]
            ],
            'WHERE'        => [
                $item_license_table . '.itemtype'  => $itemtype,
                $item_license_table . '.items_id'  => $items_id,
                'OR'                                => [
                    'glpi_softwarelicenses.softwareversions_id_use' => $softwareversions_id,
                    'glpi_softwarelicenses.softwareversions_id_buy' => $softwareversions_id
                ]
            ]
        ]);

        foreach ($iterator as $data) {
            $lic[$data['id']] = $data;
        }
        return $lic;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $nb = 0;
        switch ($item->getType()) {
            case 'SoftwareLicense':
                /** @var \Item_SoftwareLicense $item */
                if (!$withtemplate) {
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForLicense($item->getID());
                    }
                    return [1 => __('Summary'),
                        2 => self::createTabEntry(
                            _n('Item', 'Items', Session::getPluralNumber()),
                            $nb
                        )
                    ];
                }
                break;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'SoftwareLicense') {
            switch ($tabnum) {
                case 1:
                    self::showForLicenseByEntity($item);
                    break;

                case 2:
                    self::showForLicense($item);
                    break;
            }
        }
        return true;
    }


    /**
     * Count number of licenses for a software
     *
     * @since 0.85
     *
     * @param integer $softwares_id Software ID
     *
     * @return int
     **/
    public static function countLicenses($softwares_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'FROM'   => 'glpi_softwarelicenses',
            'COUNT'  => 'cpt',
            'WHERE'  => [
                'softwares_id' => $softwares_id
            ] + getEntitiesRestrictCriteria('glpi_softwarelicenses')
        ])->current();
        return $result['cpt'];
    }
}
