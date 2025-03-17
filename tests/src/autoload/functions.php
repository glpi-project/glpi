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

use Glpi\Socket;

function loadDataset()
{
    /**
     * @var array $CFG_GLPI
     * @var DBmysql $DB
     */
    global $CFG_GLPI, $DB;

    // Unit test data definition
    $data = [
        // bump this version to force reload of the full dataset, when content change
        '_version' => '4.13',

        // Type => array of entries
        'Entity' => [
            [
                'name'        => '_test_root_entity',
                'entities_id' => 0,
            ], [
                'name'        => '_test_child_1',
                'entities_id' => '_test_root_entity',
            ], [
                'name'        => '_test_child_2',
                'entities_id' => '_test_root_entity',
            ]
        ], 'Computer' => [
            [
                'name'        => '_test_pc01',
                'entities_id' => '_test_root_entity',
                'comment'     => 'Comment for computer _test_pc01',
            ], [
                'name'        => '_test_pc02',
                'entities_id' => '_test_root_entity',
                'comment'     => 'Comment for computer _test_pc02',
            ], [
                'name'        => '_test_pc03',
                'entities_id' => '_test_root_entity',
                'comment'     => 'Comment for computer _test_pc03',
                'contact'     => 'johndoe',
            ], [
                'name'        => '_test_pc11',
                'entities_id' => '_test_child_1',
            ], [
                'name'        => '_test_pc12',
                'entities_id' => '_test_child_1',
            ], [
                'name'        => '_test_pc13',
                'entities_id' => '_test_child_1',
                'comment'     => 'Comment for computer _test_pc13',
                'contact'     => 'johndoe',
            ], [
                'name'        => '_test_pc21',
                'entities_id' => '_test_child_2',
            ], [
                'name'        => '_test_pc22',
                'entities_id' => '_test_child_2',
            ], [
                'name'        => '_test_pc_with_encoded_comment',
                'entities_id' => '_test_root_entity',
                'comment'     => '&#60;&#62;', // "&#60;" => "<", "&#62;" => ">"
            ]
        ], 'ComputerModel' => [
            [
                'name'           => '_test_computermodel_1',
                'product_number' => 'CMP_ADEAF5E1',
            ], [
                'name'           => '_test_computermodel_2',
                'product_number' => 'CMP_567AEC68',
            ],
        ], 'Monitor' => [
            [
                'name'           => '_test_monitor_1',
                'entities_id' => '_test_root_entity',
            ], [
                'name'           => '_test_monitor_2',
                'entities_id' => '_test_root_entity',
            ],
        ], 'Software' => [
            [
                'name'         => '_test_soft',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
            ], [
                'name'         => '_test_soft2',
                'entities_id'  => '_test_child_2',
                'is_recursive' => 0,
            ], [
                'name'         => '_test_soft_3',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
            ]

        ], 'SoftwareVersion' => [
            [
                'name'        => '_test_softver_1',
                'entities_id' => '_test_root_entity',
                'is_recursive' => 1,
                'softwares_id' => '_test_soft',
            ], [
                'name'        => '_test_softver_2',
                'entities_id' => '_test_root_entity',
                'is_recursive' => 1,
                'softwares_id' => '_test_soft',
            ]
        ], 'NetworkEquipment' => [
            [
                'name'           => '_test_networkequipment_1',
                'entities_id' => '_test_root_entity',
            ], [
                'name'           => '_test_networkequipment_2',
                'entities_id' => '_test_root_entity',
            ],
        ], 'Peripheral' => [
            [
                'name'           => '_test_peripheral_1',
                'entities_id' => '_test_root_entity',
            ], [
                'name'           => '_test_peripheral_2',
                'entities_id' => '_test_root_entity',
            ],
        ], 'Printer' => [
            [
                'name'         => '_test_printer_all',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
            ], [
                'name'         => '_test_printer_ent0',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 0,
            ], [
                'name'         => '_test_printer_ent1',
                'entities_id'  => '_test_child_1',
                'is_recursive' => 0,
            ], [
                'name'         => '_test_printer_ent2',
                'entities_id'  => '_test_child_2',
                'is_recursive' => 0,
            ]
        ], 'Phone' => [
            [
                'name'           => '_test_phone_1',
                'entities_id' => '_test_root_entity',
            ], [
                'name'           => '_test_phone_2',
                'entities_id' => '_test_root_entity',
            ], [
                'name'           => 'PHONE-LNE-1',
                'entities_id' => '_test_root_entity',
            ], [
                'name'           => 'PHONE-LNE-2',
                'entities_id' => '_test_root_entity',
            ],
        ], 'User' => [
            [
                'name'          => TU_USER,
                'password'      => TU_PASS,
                'password2'     => TU_PASS,
                'entities_id'   => '_test_root_entity',
                'profiles_id'   => 4, // TODO manage test profiles
                '_entities_id'  => 0,
                '_profiles_id'  => 4,
                '_is_recursive' => 1,
            ],
            [
                'name'          => 'jsmith123',
                'realname'      => 'Smith',
                'firstname'     => 'John',
                'password'      => TU_PASS,
                'password2'     => TU_PASS,
                'entities_id'   => '_test_root_entity',
                'profiles_id'   => 4,
                '_entities_id'  => 0,
                '_profiles_id'  => 4,
                '_is_recursive' => 1,
            ]
        ], 'Group'   => [
            [
                'name'         => '_test_group_1',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'is_usergroup' => 1,
                'is_requester' => 1,
                'is_watcher'   => 1,
                'is_assign'    => 1,
            ],
            [
                'name'         => '_test_group_2',
                'entities_id'  => '_test_root_entity',
                'groups_id'    => '_test_group_1',
                'is_recursive' => 1,
                'is_usergroup' => 1,
                'is_requester' => 1,
                'is_watcher'   => 1,
                'is_assign'    => 1,
            ],
        ], 'TaskCategory' => [
            [
                'is_recursive' => 1,
                'name'         => '_cat_1',
                'completename' => '_cat_1',
                'comment'      => 'Comment for category _cat_1',
                'level'        => 1,
            ],
            [
                'is_recursive' => 1,
                'taskcategories_id' => '_cat_1',
                'name'         => '_subcat_1',
                'completename' => '_cat_1 > _subcat_1',
                'comment'      => 'Comment for sub-category _subcat_1',
                'level'        => 2,
            ],
            [
                'is_recursive' => 1,
                'taskcategories_id' => '_cat_1',
                'name'         => 'R&D',
                'completename' => '_cat_1 > R&D',
                'comment'      => 'Comment for sub-category _subcat_2',
                'level'        => 2,
            ],
        ], 'DropdownTranslation' => [
            [
                'items_id'   => '_cat_1',
                'itemtype'   => 'TaskCategory',
                'language'   => 'fr_FR',
                'field'      => 'name',
                'value'      => 'FR - _cat_1'
            ],
            [
                'items_id'   => '_cat_1',
                'itemtype'   => 'TaskCategory',
                'language'   => 'fr_FR',
                'field'      => 'comment',
                'value'      => 'FR - Commentaire pour catégorie _cat_1'
            ],
            [
                'items_id'   => '_subcat_1',
                'itemtype'   => 'TaskCategory',
                'language'   => 'fr_FR',
                'field'      => 'name',
                'value'      => 'FR - _subcat_1'
            ],
            [
                'items_id'   => '_subcat_1',
                'itemtype'   => 'TaskCategory',
                'language'   => 'fr_FR',
                'field'      => 'comment',
                'value'      => 'FR - Commentaire pour sous-catégorie _subcat_1'
            ]
        ], 'Contact' => [
            [
                'name'         => '_contact01_name',
                'firstname'    => '_contact01_firstname',
                'phone'        => '0123456789',
                'phone2'       => '0123456788',
                'mobile'       => '0623456789',
                'fax'          => '0123456787',
                'email'        => '_contact01_firstname._contact01_name@glpi.com',
                'comment'      => 'Comment for contact _contact01_name',
                'entities_id'  => '_test_root_entity'
            ]
        ], 'Supplier' => [
            [
                'name'         => '_suplier01_name',
                'phonenumber'  => '0123456789',
                'fax'          => '0123456787',
                'email'        => 'info@_supplier01_name.com',
                'comment'      => 'Comment for supplier _suplier01_name',
                'entities_id'  => '_test_root_entity'
            ],
            [
                'name'         => '_suplier02_name',
                'phonenumber'  => '0123456788',
                'fax'          => '0123456786',
                'email'        => 'info@_supplier02_name.com',
                'comment'      => 'Comment for supplier _suplier02_name',
                'entities_id'  => '_test_root_entity'
            ]
        ], 'Location' => [
            [
                'name'         => '_location01',
                'comment'      => 'Comment for location _location01'
            ],
            [
                'name'         => '_sublocation01',
                'locations_id' => '_location01',
                'comment'      => 'Comment for location _sublocation01'
            ],
            [
                'name'         => '_location02',
                'comment'      => 'Comment for location _sublocation02'
            ],
            [
                'name'         => '_location02 > _sublocation02',
                'comment'      => 'Comment for location _sublocation02',
                'code'         => 'code_sublocation02'
            ],
            [
                'name'         => '_location02 > _sublocation03',
                'comment'      => 'Comment for location _sublocation03',
                'alias'        => 'alias_sublocation03'
            ],
            [
                'name'         => '_location02 > _sublocation04',
                'comment'      => 'Comment for location _sublocation04',
                'code'         => 'code_sublocation04',
                'alias'        => 'alias_sublocation04'
            ],
            [
                'name'         => '_location01_subentity',
                'entities_id'  => '_test_root_entity',
                'comment'      => 'Comment for location _location01_subentity'
            ],
        ], Socket::class => [
            [
                'name'         => '_socket01',
                'locations_id' => '_location01',
                'comment'      => 'Comment for socket _socket01'
            ]
        ], 'BudgetType' => [
            [
                'name'         => '_budgettype01',
                'comment'      => 'Comment for budgettype _budgettype01'
            ]
        ], 'Budget' => [
            [
                'name'           => '_budget01',
                'comment'        => 'Comment for budget _budget01',
                'locations_id'   => '_location01',
                'budgettypes_id' => '_budgettype01',
                'begin_date'     => '2016-10-18',
                'end_date'       => '2016-12-31',
                'entities_id'     => '_test_root_entity'
            ]
        ], 'Ticket' => [
            [
                'name'           => '_ticket01',
                'content'        => 'Content for ticket _ticket01',
                'externalid'     => 'external_id',
                'users_id_recipient' => TU_USER,
                'entities_id'    => '_test_root_entity'
            ],
            [
                'name'           => '_ticket02',
                'content'        => 'Content for ticket _ticket02',
                'users_id_recipient' => TU_USER,
                'entities_id'    => '_test_root_entity'
            ],
            [
                'name'           => '_ticket03',
                'content'        => 'Content for ticket _ticket03',
                'users_id_recipient' => TU_USER,
                'entities_id'    => '_test_child_1'
            ],
            [
                'id'             => 100, // Force ID that will be used in imap test suite fixtures
                'name'           => '_ticket100',
                'content'        => 'Content for ticket _ticket100',
                'users_id_recipient' => TU_USER,
                'entities_id'    => '_test_root_entity'
            ],
            [
                'id'             => 101, // Force ID that will be used in imap test suite fixtures
                'name'           => '_ticket101',
                'content'        => 'Content for ticket _ticket101',
                'users_id_recipient' => TU_USER,
                'entities_id'    => '_test_root_entity'
            ],
        ], 'TicketTask' => [
            [
                'tickets_id'         => '_ticket01',
                'taskcategories_id'  => '_subcat_1',
                'users_id'           => TU_USER,
                'content'            => 'Task to be done',
                'is_private'         => 0,
                'users_id_tech'      => TU_USER,
                'date'               => '2016-10-19 11:50:50'
            ]
        ], 'UserEmail' => [
            [
                'users_id'     => TU_USER,
                'is_default'   => '1',
                'is_dynamic'   => '0',
                'email'        => TU_USER . '@glpi.com'
            ]
        ], 'KnowbaseItem' => [
            [
                'name'     => '_knowbaseitem01',
                'answer'   => 'Answer for Knowledge base entry _knowbaseitem01 apple juice turnover',
                'is_faq'   => 0,
                'users_id' => TU_USER,
                'date'     => '2016-11-17 12:27:48',
            ],
            [
                'name'     => '_knowbaseitem02',
                'answer'   => 'Answer for Knowledge base entry _knowbaseitem02 apple macintosh strudel',
                'is_faq'   => 0,
                'users_id' => TU_USER,
                'date'     => '2016-11-17 12:27:48',
            ]
        ], 'KnowbaseItem_Item' => [
            [
                'knowbaseitems_id' => '_knowbaseitem01',
                'itemtype'         => 'Ticket',
                'items_id'         => '_ticket01',
            ],
            [
                'knowbaseitems_id' => '_knowbaseitem01',
                'itemtype'         => 'Ticket',
                'items_id'         => '_ticket02',
            ],
            [
                'knowbaseitems_id' => '_knowbaseitem01',
                'itemtype'         => 'Ticket',
                'items_id'         => '_ticket03',
            ],
            [
                'knowbaseitems_id' => '_knowbaseitem02',
                'itemtype'         => 'Ticket',
                'items_id'         => '_ticket03',
            ],
            [
                'knowbaseitems_id' => '_knowbaseitem02',
                'itemtype'         => 'Computer',
                'items_id'         => '_test_pc21',
            ]
        ], 'Entity_KnowbaseItem' => [
            [
                'knowbaseitems_id' => '_knowbaseitem01',
                'entities_id'      => '_test_root_entity'
            ],
            [
                'knowbaseitems_id' => '_knowbaseitem02',
                'entities_id'      => '_test_child_1'
            ]
        ], 'DocumentType' => [
            [
                'name'          => 'markdown',
                'is_uploadable' => '1',
                'ext'           => 'md'
            ]
        ], 'Manufacturer' => [
            [
                'name'          => 'My Manufacturer',
            ]
        ], 'SoftwareLicense' => [
            [
                'name'         => '_test_softlic_1',
                'completename' => '_test_softlic_1',
                'level'        => 0,
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'number'       => 2,
                'softwares_id' => '_test_soft',
            ],
            [
                'name'         => '_test_softlic_2',
                'completename' => '_test_softlic_2',
                'level'        => 0,
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'number'       => 3,
                'softwares_id' => '_test_soft',
            ],
            [
                'name'         => '_test_softlic_3',
                'completename' => '_test_softlic_3',
                'level'        => 0,
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'number'       => 5,
                'softwares_id' => '_test_soft',
            ],
            [
                'name'         => '_test_softlic_4',
                'completename' => '_test_softlic_4',
                'level'        => 0,
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'number'       => 2,
                'softwares_id' => '_test_soft',
            ],
            [
                'name'         => '_test_softlic_child',
                'completename' => '_test_softlic_child',
                'level'        => 0,
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'number'       => 1,
                'softwares_id' => '_test_soft',
                'softwarelicenses_id' => '_test_softlic_1',
            ],
        ], 'Item_SoftwareLicense' => [
            [
                'softwarelicenses_id'   => '_test_softlic_1',
                'items_id'              => '_test_pc21',
                'itemtype'              => 'Computer',
            ], [
                'softwarelicenses_id'   => '_test_softlic_1',
                'items_id'              => '_test_pc01',
                'itemtype'              => 'Computer',
            ], [
                'softwarelicenses_id'   => '_test_softlic_1',
                'items_id'              => '_test_pc02',
                'itemtype'              => 'Computer',
            ], [
                'softwarelicenses_id'   => '_test_softlic_2',
                'items_id'              => '_test_pc02',
                'itemtype'              => 'Computer',
            ], [
                'softwarelicenses_id'   => '_test_softlic_3',
                'items_id'              => '_test_pc02',
                'itemtype'              => 'Computer',
            ], [
                'softwarelicenses_id'   => '_test_softlic_3',
                'items_id'              => '_test_pc21',
                'itemtype'              => 'Computer',
            ], [
                'softwarelicenses_id'   => '_test_softlic_2',
                'items_id'              => '_test_pc21',
                'itemtype'              => 'Computer',
            ]
        ], 'DeviceSimcard' => [
            [
                'designation'         => '_test_simcard_1',
                'entities_id'         => '_test_root_entity',
                'is_recursive'        => 1,
            ]
        ], 'DeviceSensor' => [
            [
                'designation'  => '_test_sensor_1',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1
            ]
        ], 'AuthLDAP' => [
            [
                'name'            => '_local_ldap',
                'host'            => 'openldap',
                'basedn'          => 'dc=glpi,dc=org',
                'rootdn'          => 'cn=Manager,dc=glpi,dc=org',
                'port'            => '3890',
                'condition'       => '(objectclass=inetOrgPerson)',
                'login_field'     => 'uid',
                'rootdn_passwd'   => 'insecure',
                'is_default'      => 1,
                'is_active'       => 0,
                'use_tls'         => 0,
                'email1_field'    => 'mail',
                'realname_field'  => 'cn',
                'firstname_field' => 'sn',
                'phone_field'     => 'telephonenumber',
                'comment_field'   => 'description',
                'title_field'     => 'title',
                'category_field'  => 'businesscategory',
                'language_field'  => 'preferredlanguage',
                'group_search_type'  => \AuthLDAP::GROUP_SEARCH_GROUP,
                'group_condition' => '(objectclass=groupOfNames)',
                'group_member_field' => 'member'
            ]
        ], 'Holiday'   => [
            [
                'name'         => 'X-Mas',
                'entities_id'  => '_test_root_entity',
                'is_recursive' => 1,
                'begin_date'   => '2018-12-29',
                'end_date'     => '2019-01-06'
            ]
        ], 'Glpi\\Dashboard\\Dashboard' => [
            [
                'key'     => 'test_dashboard',
                'name'    => 'Test_Dashboard',
                'context' => 'core',
            ], [
                'key'     => 'test_dashboard2',
                'name'    => 'Test_Dashboard_2',
                'context' => 'core'
            ], [
                'key'     => 'test_dashboard3',
                'name'    => 'Test_Dashboard_3',
                'context' => 'oustide_core',
            ]
        ], 'Glpi\\Dashboard\\Item' => [
            [
                'dashboards_dashboards_id' => 'Test_Dashboard',
                'gridstack_id'             => 'bn_count_Computer_1',
                'card_id'                  => 'bn_count_Computer',
                'x'                        => 0,
                'y'                        => 0,
                'width'                    => 2,
                'height'                   => 2,
                'card_options'             => '{"color": "#FFFFFF"}'
            ], [
                'dashboards_dashboards_id' => 'Test_Dashboard',
                'gridstack_id'             => 'bn_count_Computer_2',
                'card_id'                  => 'bn_count_Computer',
                'x'                        => 2,
                'y'                        => 0,
                'width'                    => 2,
                'height'                   => 2,
                'card_options'             => '{"color": "#FFFFFF"}'
            ], [
                'dashboards_dashboards_id' => 'Test_Dashboard',
                'gridstack_id'             => 'bn_count_Computer_3',
                'card_id'                  => 'bn_count_Computer',
                'x'                        => 4,
                'y'                        => 0,
                'width'                    => 2,
                'height'                   => 2,
                'card_options'             => '{"color": "#FFFFFF"}'
            ]
        ], 'Glpi\\Dashboard\\Right' => [
            [
                'dashboards_dashboards_id' => 'Test_Dashboard',
                'itemtype'                 => 'Entity',
                'items_id'                 => 0,
            ], [
                'dashboards_dashboards_id' => 'Test_Dashboard',
                'itemtype'                 => 'Profile',
                'items_id'                 => 3,
            ]
        ], 'Change' => [
            [
                'name'           => '_change01',
                'content'        => 'Content for ticket _change01',
                'users_id_recipient' => TU_USER,
                'entities_id'    => '_test_root_entity'
            ],
        ],
        'OAuthClient' => [
            [
                'redirect_uri' => ["/api.php/oauth2/redirection"],
                'grants' => ['password', 'client_credentials', 'authorization_code'],
                'scopes' => [],
                'is_active' => 1,
                'is_confidential' => 1,
                'name' => 'Test OAuth Client',
            ]
        ],
        'CartridgeItem' => [
            [
                'name'        => '_test_cartridgeitem01',
                'entities_id' => '_test_root_entity',
            ]
        ],
        'ConsumableItem' => [
            [
                'name'        => '_test_consumableitem01',
                'entities_id' => '_test_root_entity',
            ]
        ],
    ];

    // To bypass various right checks
    $session_bak = $_SESSION;
    $_SESSION['glpishowallentities'] = 1;
    $_SESSION['glpicronuserrunning'] = "cron_phpunit";
    $_SESSION['glpi_use_mode']       = Session::NORMAL_MODE;
    $_SESSION['glpiactive_entity']   = 0;
    $_SESSION['glpiactiveentities']  = [0];
    $_SESSION['glpiactiveentities_string'] = "'0'";
    $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
    $CFG_GLPI['root_doc']            = '/glpi';

    $DB->beginTransaction();

    Config::setConfigurationValues('core', ['url_base'     => GLPI_URI,
        'url_base_api' => GLPI_URI . '/apirest.php'
    ]);
    $CFG_GLPI['url_base']      = GLPI_URI;
    $CFG_GLPI['url_base_api']  = GLPI_URI . '/apirest.php';

    // make all caldav component available for tests (for default usage we don't VTODO)
    $CFG_GLPI['caldav_supported_components']  = ['VEVENT', 'VJOURNAL', 'VTODO'];

    $conf = Config::getConfigurationValues('phpunit');
    if (!(isset($conf['dataset']) && $conf['dataset'] == $data['_version'])) {
        $ids = [];
        foreach ($data as $type => $inputs) {
            if ($type[0] == '_') {
                continue;
            }
            foreach ($inputs as $input) {
               // Resolve FK
                foreach ($input as $k => $v) {
                    // $foreigntype = $type; // by default same type than current type (is the case of the dropdowns)
                    $foreigntype = false;
                    $match = [];
                    if (isForeignKeyField($k) && (preg_match("/(.*s)_id$/", $k, $match) || preg_match("/(.*s)_id_/", $k, $match))) {
                        $foreigntypetxt = array_pop($match);
                        if (substr($foreigntypetxt, 0, 1) !== '_') {
                            $foreigntype = getItemTypeForTable("glpi_$foreigntypetxt");
                        }
                    }
                    if ($foreigntype && isset($ids[$foreigntype][$v]) && !is_numeric($v)) {
                        $input[$k] = $ids[$foreigntype][$v];
                    } else if ($k == 'items_id'  &&  isset($input['itemtype']) && isset($ids[$input['itemtype']][$v]) && !is_numeric($v)) {
                        $input[$k] = $ids[$input['itemtype']][$v];
                    } else if ($foreigntype && $foreigntype != 'UNKNOWN' && !is_numeric($v)) {
                        // not found in ids array, then must get it from DB
                        $foreign_id = getItemByTypeName($foreigntype, $v, true);
                        $input[$k] = $foreign_id;

                        $ids[$foreigntype][$v] = $foreign_id; // cache ID
                    }
                }

                $item = getItemForItemtype($type);
                $name_field = $item::getNameField();

                if (isset($input[$name_field]) && $item->getFromDBByCrit([$name_field => $input[$name_field]])) {
                    // Update existing item
                    $item->update([$item::getIndexName() => $item->getID()] + $input);
                } else {
                    // Not found, create it
                    $item->add($input);
                }
                if (isset($input[$name_field])) {
                    $ids[$type][$input[$name_field]] = $item->getID(); // cache ID
                }
            }
        }
        Search::$search = [];
        Config::setConfigurationValues('phpunit', ['dataset' => $data['_version']]);
    }
    $DB->commit();

    $_SESSION = $session_bak; // Unset force session variables

    // Ensure cache is clear after dataset reload
    global $GLPI_CACHE;
    $GLPI_CACHE->clear();
}

/**
 * Test helper, search an item from its type and name
 * @template T of CommonDBTM
 * @param class-string<T>   $type
 * @param string            $name
 * @param bool              $onlyid
 * @phpstan-return ($onlyid is true ? Int : T)
 *      Item of $type class, or its id
 */
function getItemByTypeName(string $type, string $name, bool $onlyid = false): CommonDBTM|int
{
    $item = getItemForItemtype($type);
    $nameField = $type::getNameField();
    if (!$item->getFromDBByCrit([$nameField => $name])) {
        throw new \RuntimeException(sprintf('Unable to load the `%s` item with the name `%s`.', $type, $name));
    }
    return ($onlyid ? $item->getID() : $item);
}
