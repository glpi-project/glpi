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
use Glpi\DBAL\QueryExpression;
use Glpi\Toolbox\URL;

/**
 * External link class
 */
class Link extends CommonDBTM
{
   // From CommonDBTM
    public $dohistory                   = true;

    public static $rightname = 'link';
    public static $tags      = ['LOGIN', 'ID', 'NAME', 'LOCATION', 'LOCATIONID', 'IP',
        'MAC', 'NETWORK', 'DOMAIN', 'SERIAL', 'OTHERSERIAL',
        'USER', 'GROUP', 'REALNAME', 'FIRSTNAME'
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
            self::$tags[] = trim($tag, "[] \n\t");
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
                        'glpi_links_itemtypes.links_id'  => new QueryExpression(DBmysql::quoteName('glpi_links.id')),
                        'glpi_links_itemtypes.itemtype'  => $item->getType()
                    ] + $entity_criteria
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
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

    private function getTagCompletions()
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        static $completions = null;

        if ($completions === null) {
            $tags = self::$tags;
            $completions = [];
            foreach ($tags as $tag) {
                $completions[] = [
                    'name' => $tag,
                    'type' => 'Variable',
                ];
            }
            $itemtypes = array_column(iterator_to_array($DB->request([
                'SELECT' => ['itemtype'],
                'FROM'   => 'glpi_links_itemtypes',
                'WHERE'  => ['links_id' => $this->getID()]
            ])), 'itemtype');
            $itemtype_fields = [];
            foreach ($itemtypes as $itemtype) {
                $itemtype_fields[$itemtype] = array_column($DB->listFields($itemtype::getTable()), 'Field');
            }
            // Get all fields that exist for every itemtype
            $common_fields = array_intersect(...array_values($itemtype_fields));

            foreach ($common_fields as $field) {
                $completions[] = [
                    'name' => "item.$field",
                    'type' => 'Variable',
                ];
            }
        }
        return $completions;
    }

    /**
     * Print the link form
     *
     * @param integer $ID ID of the item
     * @param array $options
     *     - target filename : where to go when done.
     *
     * @return void
     **/
    public function showForm($ID, array $options = [])
    {
        TemplateRenderer::getInstance()->display('pages/setup/externallink.html.twig', [
            'item' => $this,
            'tag_options' => $this->getTagCompletions(),
            'params' => $options
        ]);
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
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'link',
            'name'               => __('Link or filename'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
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

    private static function getIPAndMACForItem(CommonDBTM $item, $get_ip = false, $get_mac = false): array
    {
        /**
         * @var \DBmysql $DB
         */
        global $DB;

        $ipmac = [];

        if ($item::class === NetworkEquipment::class) {
            if ($get_ip) {
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

            // If there is no entry, then, we must at least define the mac of the item ...
            if ($get_mac && count($ipmac) === 0) {
                $ipmac['mac0']['ip']    = '';
                $ipmac['mac0']['mac']   = $item->getField('mac');
            }
        }

        if ($get_ip) {
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
                    'glpi_networkports.itemtype'  => $item::class
                ]
            ]);
            foreach ($iterator as $data2) {
                $ipmac['ip' . $data2['id']]['ip']  = $data2["ip"];
                $ipmac['ip' . $data2['id']]['mac'] = $data2["mac"];
            }
        }

        if ($get_mac) {
            $criteria = [
                'SELECT' => [
                    'glpi_networkports.id',
                    'glpi_networkports.mac'
                ],
                'FROM'   => 'glpi_networkports',
                'WHERE'  => [
                    'glpi_networkports.items_id'  => $item->getID(),
                    'glpi_networkports.itemtype'  => $item::class
                ],
                'GROUP' => 'glpi_networkports.mac'
            ];

            if ($get_ip) {
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

        return $ipmac;
    }

    /**
     * Generate link(s).
     *
     * @param string        $link       original string content
     * @param CommonDBTM    $item       item used to make replacements
     * @param bool          $safe_url   indicates whether URL should be sanitized or not
     *
     * @return array of link contents (may have several when item have several IP / MAC cases)
     */
    public static function generateLinkContents($link, CommonDBTM $item, bool $safe_url = true)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $vars = [
            'ID' => $item->getID(),
            'LOGIN' => $_SESSION["glpiname"] ?? '',
            'NAME' => $item->getName(),
            'SERIAL' => $item->isField('serial') ? $item->getField('serial') : '',
            'OTHERSERIAL' => $item->isField('otherserial') ? $item->getField('otherserial') : '',
            'LOCATIONID' => $item->isField('locations_id') ? $item->getField('locations_id') : '',
            'DOMAIN' => '',
            'NETWORK' => $item->isField('networks_id') ? Dropdown::getDropdownName('glpi_networks', $item->getField('networks_id')) : '',
            'USER' => $item->isField('users_id') ? Dropdown::getDropdownName('glpi_users', $item->getField('users_id')) : '',
            'GROUP' => $item->isField('groups_id') ? Dropdown::getDropdownName('glpi_groups', $item->getField('groups_id')) : '',
            'REALNAME' => $item->isField('realname') ? $item->getField('realname') : '',
            'FIRSTNAME' => $item->isField('firstname') ? $item->getField('firstname') : '',
        ];
        $item_fields = $item->fields;
        $item::unsetUndisclosedFields($item_fields);
        if (count($item_fields)) {
            foreach ($item_fields as $k => $v) {
                $vars['item'][$k] = $v;
            }
        }

        $vars['LOCATION'] = $item->isField('locations_id') ?
            Dropdown::getDropdownName('glpi_locations', $item->getField('locations_id')) : '';

        if (in_array($item::class, $CFG_GLPI['domain_types'], true)) {
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
                        ]
                    ]
                ],
                'WHERE'     => [
                    'itemtype' => $item::class,
                    'items_id' => $item->getID()
                ]
            ]);
            if ($iterator->count()) {
                $vars['DOMAIN'] = $iterator->current()['name'];
            }
            $vars['DOMAINS'] = array_column(iterator_to_array($iterator), 'name');
        }

        // Render the common parts of the link (we will handle the IP and MAC later which could make several links)
        // We will replace the IP and MAC by twig placeholders again to preserve them
        $vars['IP'] = '{{ IP }}';
        $vars['MAC'] = '{{ MAC }}';
        $common_link = TemplateRenderer::getInstance()->renderFromStringTemplate($link, $vars, true);

        $replace_IP  = strstr($common_link, "{{ IP }}");
        $replace_MAC = strstr($common_link, "{{ MAC }}");

        if ($replace_IP || $replace_MAC) {
            $ipmac = self::getIPAndMACForItem($item, $replace_IP, $replace_MAC);

            $links = [];
            // If IP or MAC tags present but there is no info, no links will be generated
            if (count($ipmac)) {
                foreach ($ipmac as $key => $val) {
                    $links[$key] = TemplateRenderer::getInstance()->renderFromStringTemplate($common_link, [
                        'IP' => $val['ip'] ?? '',
                        'MAC' => $val['mac'] ?? ''
                    ]);
                }
            }
        } else {
            // IP and MAC not requested at all, so we only have one link
            $links = [$common_link];
        }

        if ($safe_url) {
            $links = array_map(static fn ($l) => URL::sanitizeURL($l) ?: '#', $links);
        }

        return $links;
    }

    /**
     * Show Links for an item
     *
     * @param CommonDBTM $item CommonDBTM object
     * @param integer $withtemplate  withtemplate param (default 0)
     * @deprecated 10.1.0
     * @see Link::showAllLinksForItem()
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        Toolbox::deprecated();
        self::showAllLinksForItem($item, self::class);
    }

    /**
     * Show all external and manual links for an item
     * @param CommonDBTM $item
     * @param 'ManualLink'|'Link'|null $restrict_type Restrict to a specific type of link
     * @return void
     */
    public static function showAllLinksForItem(CommonDBTM $item, ?string $restrict_type = null)
    {
        if ($item->isNewID($item->getID())) {
            return;
        }

        $buttons_params = [
            'add_msg' => _x('button', 'Add'),
            'configure_msg' => sprintf(__('Configure %s links'), $item::getTypeName(1)),
            'show_add' => ManualLink::canCreate() && ($restrict_type === null || $restrict_type === ManualLink::class),
            'show_configure' => self::canUpdate() && ($restrict_type === null || $restrict_type === self::class),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center my-3">
                    {% if show_add %}
                        <a class="btn btn-primary" href="{{ 'ManualLink'|itemtype_form_path ~ '?itemtype=' ~ item.getType() ~ '&items_id=' ~ item.fields[item.getIndexName()] }}">
                            <i class="fas fa-plus me-2"></i>
                            {{ add_msg }}
                        </a>
                    {% endif %}
                    {% if show_configure %}
                        <a class="btn btn-primary" href="{{ 'Link'|itemtype_search_path }}">
                            <i class="ti ti-settings me-2"></i>
                            {{ configure_msg }}
                        </a>
                    {% endif %}
                </div>
TWIG, $buttons_params);

        $entries = [];

        if (($restrict_type === null || $restrict_type === ManualLink::class) && ManualLink::canView()) {
            $manuallink = new ManualLink();

            $manual_links = ManualLink::getForItem($item);
            foreach ($manual_links as $row) {
                $manuallink->getFromResultSet($row);

                $entry = [
                    'itemtype' => ManualLink::class,
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'link' => ManualLink::getLinkHtml($row),
                    'comment' => $row['comment'],
                    'type' => _n('Item', 'Items', 1)
                ];
                $actions = '';

                if ($manuallink->canUpdateItem()) {
                    $actions .= '<a href="' . ManualLink::getFormURLWithID($row[$item->getIndexName()]) . '" title="' . _sx('button', 'Update') . '">';
                    $actions .= '<i class="fas fa-edit"></i>';
                    $actions .= '<span class="sr-only">' . _x('button', 'Update') . '</span>';
                    $actions .= '</a>';
                }
                $entry['actions'] = $actions;
                $entries[] = $entry;
            }
        }

        if (($restrict_type === null || $restrict_type === self::class) && self::canView()) {
            $ext_links = self::getLinksDataForItem($item);
            foreach ($ext_links as $data) {
                $links = self::getAllLinksFor($item, $data);

                foreach ($links as $link) {
                    $entries[] = [
                        'itemtype' => self::class,
                        'id' => $data['id'],
                        'name' => $link,
                        'link' => $link,
                        'type' => $item::getTypeName(Session::getPluralNumber()),
                        'comment' => ''
                    ];
                }
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nopager' => true,
            'superheader' => '',
            'columns' => [
                'type' => __('Linked to'),
                'link' => __('Link'),
                'comment' => __('Comment'),
                'actions' => __('Actions')
            ],
            'formatters' => [
                'link' => 'raw_html',
                'actions' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    /**
     * Show Links for an item
     *
     * @since 0.85
     *
     * @param CommonDBTM $item The item
     * @param array{id: int, name: string, link: string, data: string, open_window: ?bool} $params
     **/
    public static function getAllLinksFor($item, $params = [])
    {
        /** @var array $CFG_GLPI */
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

        $names = $item->generateLinkContents($params['name'], $item, false);
        $file  = trim($params['data']);

        if (empty($file)) {
            // Generate links
            $links = $item->generateLinkContents($params['link'], $item, true);
            $i     = 1;
            foreach ($links as $key => $val) {
                $val     = htmlspecialchars($val); // encode special chars as value was generated from a raw pattern
                $name    = ($names[$key] ?? reset($names));
                $newlink = '<a href="' . $val . '"';
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
            $files = $item->generateLinkContents($params['link'], $item, false);
            $links = $item->generateLinkContents($params['data'], $item, false);
            $i     = 1;
            foreach ($links as $key => $val) {
                $name = ($names[$key] ?? reset($names));
                if (isset($files[$key])) {
                    // a different name for each file, ex name = foo-[IP].txt
                    $file = $files[$key];
                } else {
                   // same name for all files, ex name = foo.txt
                    $file = reset($files);
                }
                $url             = $CFG_GLPI["root_doc"] . "/front/link.send.php?lID=" . $params['id'] .
                                 "&itemtype=" . $item::class .
                                 "&id=" . $item->getID() . "&rank=$key";
                $newlink         = '<a href="' . htmlspecialchars($url) . '" target="_blank">';
                $newlink        .= "<i class='fa-lg fa-fw fas fa-link me-2'></i>";
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
        if ($item::class === User::class) {
            $restrict = Profile_User::getEntitiesForUser($item->getID());
        }

        return $restrict;
    }

    public static function getLinksDataForItem(CommonDBTM $item)
    {
        /** @var \DBmysql $DB */
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
                'glpi_links_itemtypes.itemtype'  => $item::class,
            ] + getEntitiesRestrictCriteria('glpi_links', 'entities_id', $restrict, true),
            'ORDERBY'      => 'name'
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-link";
    }

    /**
     * Validate that the provided link/content Twig template can be parsed. May not catch issues arising from runtime data.
     * @param string $template
     * @return bool
     */
    private function validateLinkOrContent(string $template)
    {
        try {
            $fields = [
                'id' => 1,
                'name' => '',
                'serial' => '',
                'otherserial' => '',
                'locations_id' => 1,
                'networks_id' => 1,
                'users_id' => 1,
                'groups_id' => 1,
                'realname' => '',
                'firstname' => '',
            ];
            $fake_item = new class extends CommonDBTM {
            };
            $fake_item->fields = $fields;
            self::generateLinkContents($template, $fake_item, false);
        } catch (Throwable) {
            Session::addMessageAfterRedirect(
                __('Invalid link or content'),
                false,
                ERROR
            );
            return false;
        }
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input['link']) && !$this->validateLinkOrContent($input['link'])) {
            return false;
        }
        if (isset($input['data']) && !$this->validateLinkOrContent($input['data'])) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['link']) && !$this->validateLinkOrContent($input['link'])) {
            return false;
        }
        if (isset($input['data']) && !$this->validateLinkOrContent($input['data'])) {
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }
}
