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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;

class DomainRecord extends CommonDBChild implements AssignableItemInterface
{
    use AssignableItem {
        canUpdate as canUpdateAssignableItem;
        canUpdateItem as canUpdateItemAssignableItem;
    }

    public const DEFAULT_TTL = 3600;

    public static $rightname              = 'domain';
    // From CommonDBChild
    public static $itemtype        = 'Domain';
    public static $items_id        = 'domains_id';
    public $dohistory              = true;
    public static $mustBeAttached  = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Domain record', 'Domains records', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', Domain::class, self::class];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::class === Domain::class) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb =  self::countForDomain($item);
            }
            return self::createTabEntry(_n('Record', 'Records', Session::getPluralNumber()), $nb, $item::class);
        }
        return '';
    }

    public static function countForDomain(Domain $item)
    {
        return countElementsInTable(
            self::getTable(),
            [
                "domains_id"   => $item->getID(),
            ]
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === Domain::class) {
            self::showForDomain($item);
        }
        return true;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab = array_merge($tab, parent::rawSearchOptions());

        $tab[] = [
            'id'                 => '2',
            'table'              => 'glpi_domains',
            'field'              => 'name',
            'name'               => Domain::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => DomainRecordType::getTable(),
            'field'              => 'name',
            'name'               => DomainRecordType::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'ttl',
            'name'               => __('TTL'),
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'data',
            'name'               => __('Data'),
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => 'glpi_groups',
            'field'              => 'name',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge'),
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'massiveaction'      => false,
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    public static function canCreate(): bool
    {
        if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
            return true;
        }
        return parent::canCreate();
    }

    public static function canUpdate(): bool
    {
        if (!self::canUpdateAssignableItem()) {
            return false;
        }
        if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
            return true;
        }
        return parent::canUpdate();
    }

    public static function canDelete(): bool
    {
        if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
            return true;
        }
        return parent::canDelete();
    }

    public static function canPurge(): bool
    {
        if (count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'])) {
            return true;
        }
        return parent::canPurge();
    }

    public function canCreateItem(): bool
    {
        return count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes']) > 0;
    }

    public function canUpdateItem(): bool
    {
        if (!$this->canUpdateItemAssignableItem()) {
            return false;
        }
        return parent::canUpdateItem()
         && (
             $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] === [-1]
         || in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'], true)
         );
    }

    public function canDeleteItem(): bool
    {
        return parent::canDeleteItem()
         && (
             $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] === [-1]
         || in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'], true)
         );
    }

    public function canPurgeItem(): bool
    {
        return parent::canPurgeItem()
         && (
             $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] === [-1]
         || in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'], true)
         );
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Item_Ticket::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    /**
     * Prepare input for add and update
     *
     * @param array   $input Input values
     * @param boolean $add   True when we're adding a record
     *
     * @return array|false
     */
    private function prepareInput($input, $add = false)
    {
        if (($add && empty($input['domains_id'])) || (isset($input['domains_id']) && empty($input['domains_id']))) {
            Session::addMessageAfterRedirect(
                __s('A domain is required'),
                true,
                ERROR
            );
            return false;
        }

        if ($add) {
            if (isset($input['date_creation']) && empty($input['date_creation'])) {
                $input['date_creation'] = 'NULL';
            }

            if (empty($input['ttl'])) {
                $input['ttl'] = self::DEFAULT_TTL;
            }
        }

        //search entity
        if ($add && !isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'] ?? 0;
            $input['is_recursive'] = $_SESSION['glpiactive_entity_recursive'] ?? 0;
            $domain = new Domain();
            if (isset($input['domains_id']) && $domain->getFromDB($input['domains_id'])) {
                $input['entities_id'] = $domain->fields['entities_id'];
                $input['is_recursive'] = $domain->fields['is_recursive'];
            }
        }

        if (!Session::isCron() && (isset($input['domainrecordtypes_id']) || isset($this->fields['domainrecordtypes_id']))) {
            if ($_SESSION['glpiactiveprofile']['managed_domainrecordtypes'] !== [-1]) {
                if (isset($input['domainrecordtypes_id']) && !(in_array($input['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'], true))) {
                    //no right to use selected type
                    Session::addMessageAfterRedirect(
                        __s('You are not allowed to use this type of records'),
                        true,
                        ERROR
                    );
                    return false;
                }
                if ($add === false && !(in_array($this->fields['domainrecordtypes_id'], $_SESSION['glpiactiveprofile']['managed_domainrecordtypes'], true))) {
                    //no right to change existing type
                    Session::addMessageAfterRedirect(
                        __s('You are not allowed to edit this type of records'),
                        true,
                        ERROR
                    );
                    return false;
                }
            }
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareGroupFields($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareInput($input, true);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareGroupFields($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareInput($input);
    }

    public function pre_updateInDB()
    {

        if (
            (in_array('data', $this->updates, true) || in_array('domainrecordtypes_id', $this->updates, true))
            && !array_key_exists('data_obj', $this->input)
        ) {
            // Remove data stored as obj if "data" or "record type" changed" and "data_obj" is not part of input.
            // It ensure that updates that "data_obj" will not contains obsolete values.
            $this->fields['data_obj'] = 'NULL';
            $this->updates[]          = 'data_obj';
        }
    }

    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
        }
        $domain = new Domain();
        $domain->getFromDB($this->fields['domains_id']);

        TemplateRenderer::getInstance()->display('pages/management/domainrecord.html.twig', [
            'item' => $this,
            'domain' => $domain,
        ]);
        return true;
    }

    /**
     * Show records for a domain
     *
     * @param Domain $domain Domain object
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showForDomain(Domain $domain)
    {
        global $DB;

        $instID = $domain->fields['id'];
        if (!$domain->can($instID, READ)) {
            return false;
        }
        $canedit = $domain->can($instID, UPDATE)
                 || count($_SESSION['glpiactiveprofile']['managed_domainrecordtypes']);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'SELECT'    => 'record.*',
            'FROM'      => self::getTable() . ' AS record',
            'WHERE'     => ['domains_id' => $instID],
            'LEFT JOIN' => [
                DomainRecordType::getTable() . ' AS rtype'  => [
                    'ON'  => [
                        'rtype'  => 'id',
                        'record' => 'domainrecordtypes_id',
                    ],
                ],
            ],
            'ORDER'     => ['rtype.name ASC', 'record.name ASC'],
        ]);

        if ($canedit) {
            $twig_params = [
                'domains_id' => $instID,
                'domain_record' => new self(),
                'condition' => [
                    'NOT' => [
                        'domains_id'   => ['>', 0],
                        'NOT'          => ['domains_id' => null],
                    ],
                ],
                'label' => __('Link a record'),
                'add_btn_msg' => _x('button', 'Add'),
                'add_new_btn_msg' => sprintf(__("New %s for this item"), self::getTypeName(1)),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                {% set rand = random() %}
                <div class="mb-3">
                    <form name="domain_form{{ rand }}" id="domain_form{{ rand }}" method="post"
                          action="{{ 'Domain'|itemtype_form_path }}" data-submit-once>
                        {{ inputs.hidden('_glpi_csrf_token', csrf_token()) }}
                        {{ inputs.hidden('domains_id', domains_id) }}

                        <div class="d-flex">
                            {{ fields.dropdownField('DomainRecord', 'domainrecords_id', 0, label, {
                                'condition': condition
                            }) }}
                            {{ fields.htmlField('', inputs.submit('addrecord', add_btn_msg, 1), null, {
                                no_label: true,
                                mb: '',
                                wrapper_class: 'ms-2'
                            }) }}
                        </div>
                    </form>
                    <hr class="mt-2 mb-n2">
                    <div id="new_record_form" class="d-none">
                        {{ include('pages/management/domainrecord.html.twig', {
                            item: domain_record,
                            domains_id: domains_id,
                            no_header: true,
                        }, with_context = false) }}
                    </div>
                    <div class="mt-4 text-center">
                        <button type="button" class="btn btn-primary" id="add_new_record_btn{{ rand }}">
                            {{ add_new_btn_msg }}
                        </button>
                        <script>
                            $('#add_new_record_btn{{ rand }}').on('click', function() {
                                $('#new_record_form').removeClass('d-none');
                                $(this).addClass('d-none');
                            });
                        </script>
                    </div>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        foreach ($iterator as $data) {
            $name = self::getDisplayName($domain, $data['name']);
            if ($_SESSION["glpiis_ids_visible"] || $name === '') {
                $name .= " (" . $data["id"] . ")";
            }

            $entries[] = [
                'itemtype' => self::class,
                'row_class' => isset($data['is_deleted']) && $data['is_deleted'] ? 'table-danger' : '',
                'id'       => $data['id'],
                'type'     => Dropdown::getDropdownName(DomainRecordType::getTable(), $data['domainrecordtypes_id']),
                'name'     => sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape(DomainRecord::getFormURLWithID($data['id'])),
                    htmlescape($name)
                ),
                'ttl'      => $data['ttl'],
                'data'     => $data['data'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1),
                'name' => __('Name'),
                'ttl' => __('TTL'),
                'data' => _n('Target', 'Targets', 1),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    public static function getDisplayName(Domain $domain, $name)
    {
        $name_txt = rtrim(
            str_replace(
                rtrim($domain->getCanonicalName(), '.'),
                '',
                $name
            ),
            '.'
        );
        if (empty($name_txt)) {
            //dns root
            $name_txt = '@';
        }
        return $name_txt;
    }

    public static function getIcon()
    {
        return "ti ti-file-search";
    }
}
