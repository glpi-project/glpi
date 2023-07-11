<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * Update from 9.4.x to 9.5.0
 *
 * @return bool for success (will die for most error)
 **/
function update94xto950()
{
    global $CFG_GLPI, $DB, $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '9.5.0'));
    $migration->setVersion('9.5.0');

    /** Encrypted FS support  */
    if (!$DB->fieldExists("glpi_items_disks", "encryption_status")) {
        $migration->addField("glpi_items_disks", "encryption_status", "integer", [
            'after'  => "is_dynamic",
            'value'  => 0
        ]);
    }

    if (!$DB->fieldExists("glpi_items_disks", "encryption_tool")) {
        $migration->addField("glpi_items_disks", "encryption_tool", "string", [
            'after'  => "encryption_status"
        ]);
    }

    if (!$DB->fieldExists("glpi_items_disks", "encryption_algorithm")) {
        $migration->addField("glpi_items_disks", "encryption_algorithm", "string", [
            'after'  => "encryption_tool"
        ]);
    }

    if (!$DB->fieldExists("glpi_items_disks", "encryption_type")) {
        $migration->addField("glpi_items_disks", "encryption_type", "string", [
            'after'  => "encryption_algorithm"
        ]);
    }
    /** /Encrypted FS support  */

    /** Suppliers restriction */
    if (!$DB->fieldExists('glpi_suppliers', 'is_active')) {
        $migration->addField(
            'glpi_suppliers',
            'is_active',
            'bool',
            ['value' => 0]
        );
        $migration->addKey('glpi_suppliers', 'is_active');
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_suppliers',
                ['is_active' => 1],
                [true]
            )
        );
    }
    /** /Suppliers restriction */

    /** Timezones */
   //User timezone
    if (!$DB->fieldExists('glpi_users', 'timezone')) {
        $migration->addField("glpi_users", "timezone", "varchar(50) DEFAULT NULL");
    }
    $migration->displayWarning("DATETIME fields must be converted to TIMESTAMP for timezones to work. Run bin/console migration:timestamps");

   // Add a config entry for app timezone setting
    $migration->addConfig(['timezone' => null]);
    /** /Timezones */

   // Fix search Softwares performance
    $migration->dropKey('glpi_softwarelicenses', 'softwares_id_expire');
    $migration->addKey('glpi_softwarelicenses', [
        'softwares_id',
        'expire',
        'number'
    ], 'softwares_id_expire_number');

    /** Private supplier followup in glpi_entities */
    if (!$DB->fieldExists('glpi_entities', 'suppliers_as_private')) {
        $migration->addField(
            "glpi_entities",
            "suppliers_as_private",
            "integer",
            [
                'value'     => -2,               // Inherit as default value
                'update'    => 0,                // Not enabled for root entity
                'condition' => 'WHERE `id` = 0'
            ]
        );
    }
    /** /Private supplier followup in glpi_entities */

    /** Entities Custom CSS configuration fields */
   // Add 'custom_css' entities configuration fields
    if (!$DB->fieldExists('glpi_entities', 'enable_custom_css')) {
        $migration->addField(
            'glpi_entities',
            'enable_custom_css',
            'integer',
            [
                'value'     => -2, // Inherit as default value
                'update'    => '0', // Not enabled for root entity
                'condition' => 'WHERE `id` = 0'
            ]
        );
    }
    if (!$DB->fieldExists('glpi_entities', 'custom_css_code')) {
        $migration->addField('glpi_entities', 'custom_css_code', 'text');
    }
    /** /Entities Custom CSS configuration fields */

    /** Clusters */
    if (!$DB->tableExists('glpi_clustertypes')) {
        $query = "CREATE TABLE `glpi_clustertypes` (
         `id` int NOT NULL AUTO_INCREMENT,
         `entities_id` int NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "9.5 add table glpi_clustertypes");
    }

    if (!$DB->tableExists('glpi_clusters')) {
        $query = "CREATE TABLE `glpi_clusters` (
         `id` int NOT NULL AUTO_INCREMENT,
         `entities_id` int NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `users_id_tech` int NOT NULL DEFAULT '0',
         `groups_id_tech` int NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `states_id` int NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
         `comment` text COLLATE utf8_unicode_ci,
         `clustertypes_id` int NOT NULL DEFAULT '0',
         `autoupdatesystems_id` int NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `group_id_tech` (`groups_id_tech`),
         KEY `is_deleted` (`is_deleted`),
         KEY `states_id` (`states_id`),
         KEY `clustertypes_id` (`clustertypes_id`),
         KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "9.5 add table glpi_clusters");
    }

    if (!$DB->tableExists('glpi_items_clusters')) {
        $query = "CREATE TABLE `glpi_items_clusters` (
         `id` int NOT NULL AUTO_INCREMENT,
         `clusters_id` int NOT NULL DEFAULT '0',
         `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
         `items_id` int NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`clusters_id`,`itemtype`,`items_id`),
         KEY `item` (`itemtype`,`items_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "9.5 add table glpi_items_clusters");
    }

    $migration->addField('glpi_states', 'is_visible_cluster', 'bool', [
        'value' => 1,
        'after' => 'is_visible_pdu'
    ]);
    $migration->addKey('glpi_states', 'is_visible_cluster');

    $migration->addRight('cluster', ALLSTANDARDRIGHT);

    $ADDTODISPLAYPREF['Cluster'] = [31, 19];
    /** /Clusters */

    /** ITIL templates */
   //rename tables -- usefull only for 9.5 rolling release
    foreach (
        [
            'glpi_itiltemplates',
            'glpi_itiltemplatepredefinedfields',
            'glpi_itiltemplatemandatoryfields',
            'glpi_itiltemplatehiddenfields',
        ] as $table
    ) {
        if ($DB->tableExists($table)) {
            $migration->renameTable($table, str_replace('itil', 'ticket', $table));
        }
    }
   //rename fkeys -- usefull only for 9.5 rolling release
    foreach (
        [
            'glpi_entities'                        => 'itiltemplates_id',
            'glpi_profiles'                        => 'itiltemplates_id',
            'glpi_ticketrecurrents'                => 'itiltemplates_id',
            'glpi_tickettemplatehiddenfields'      => 'itiltemplates_id',
            'glpi_tickettemplatemandatoryfields'   => 'itiltemplates_id',
            'glpi_tickettemplatepredefinedfields'  => 'itiltemplates_id'
        ] as $table => $field
    ) {
        if ($DB->fieldExists($table, $field)) {
            $migration->changeField($table, $field, str_replace('itil', 'ticket', $field), 'integer');
        }
    }
    $migration->changeField('glpi_itilcategories', 'itiltemplates_id_incident', 'tickettemplates_id_incident', 'integer');
    $migration->changeField('glpi_itilcategories', 'itiltemplates_id_demand', 'tickettemplates_id_demand', 'integer');

   //rename profilerights values
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_profilerights',
            ['name' => 'itiltemplate'],
            ['name' => 'tickettemplate']
        )
    );

   //create template tables for other itil objects
    foreach (['change', 'problem'] as $itiltype) {
        if (!$DB->tableExists("glpi_{$itiltype}templates")) {
            $query = "CREATE TABLE `glpi_{$itiltype}templates` (
            `id` int NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->queryOrDie($query, "add table glpi_{$itiltype}templates");
            $migration->addPostQuery(
                $DB->buildInsert(
                    "glpi_{$itiltype}templates",
                    [
                        'id'           => 1,
                        'name'         => 'Default',
                        'is_recursive' => 1
                    ]
                )
            );
        }

        if (!$DB->tableExists("glpi_{$itiltype}templatehiddenfields")) {
            $query = "CREATE TABLE `glpi_{$itiltype}templatehiddenfields` (
            `id` int NOT NULL AUTO_INCREMENT,
            `{$itiltype}templates_id` int NOT NULL DEFAULT '0',
            `num` int NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`{$itiltype}templates_id`,`num`),
            KEY `{$itiltype}templates_id` (`{$itiltype}templates_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->queryOrDie($query, "add table glpi_{$itiltype}templatehiddenfields");
        }

        if (!$DB->tableExists("glpi_{$itiltype}templatemandatoryfields")) {
            $query = "CREATE TABLE `glpi_{$itiltype}templatemandatoryfields` (
            `id` int NOT NULL AUTO_INCREMENT,
            `{$itiltype}templates_id` int NOT NULL DEFAULT '0',
            `num` int NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`{$itiltype}templates_id`,`num`),
            KEY `{$itiltype}templates_id` (`{$itiltype}templates_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->queryOrDie($query, "add table glpi_{$itiltype}templatemandatoryfields");
            $migration->addPostQuery(
                $DB->buildInsert(
                    "glpi_{$itiltype}templatemandatoryfields",
                    [
                        'id'                       => 1,
                        $itiltype . 'templates_id'   => 1,
                        'num'                      => 21
                    ]
                )
            );
        }

        if (!$DB->tableExists("glpi_{$itiltype}templatepredefinedfields")) {
            $query = "CREATE TABLE `glpi_{$itiltype}templatepredefinedfields` (
            `id` int NOT NULL AUTO_INCREMENT,
            `{$itiltype}templates_id` int NOT NULL DEFAULT '0',
            `num` int NOT NULL DEFAULT '0',
            `value` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `{$itiltype}templates_id` (`{$itiltype}templates_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->queryOrDie($query, "add table glpi_{$itiltype}templatepredefinedfields");
        } else {
           //drop key -- usefull only for 9.5 rolling release
            $migration->dropKey("glpi_{$itiltype}templatepredefinedfields", 'unicity');
        }
    }
    /** /ITIL templates */

    /** add templates for followups */
    if (!$DB->tableExists('glpi_itilfollowuptemplates')) {
        $query = "CREATE TABLE `glpi_itilfollowuptemplates` (
         `id`              INT NOT NULL AUTO_INCREMENT,
         `date_creation`   TIMESTAMP NULL DEFAULT NULL,
         `date_mod`        TIMESTAMP NULL DEFAULT NULL,
         `entities_id`     INT NOT NULL DEFAULT '0',
         `is_recursive`    TINYINT NOT NULL DEFAULT '0',
         `name`            VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
         `content`         TEXT NULL COLLATE 'utf8_unicode_ci',
         `requesttypes_id` INT NOT NULL DEFAULT '0',
         `is_private`      TINYINT NOT NULL DEFAULT '0',
         `comment`         TEXT NULL COLLATE 'utf8_unicode_ci',
         PRIMARY KEY (`id`),
         INDEX `name` (`name`),
         INDEX `is_recursive` (`is_recursive`),
         INDEX `requesttypes_id` (`requesttypes_id`),
         INDEX `entities_id` (`entities_id`),
         INDEX `date_mod` (`date_mod`),
         INDEX `date_creation` (`date_creation`),
         INDEX `is_private` (`is_private`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_itilfollowuptemplates");
    }
    /** /add templates for followups */

    /** Add "date_creation" field on document_items */
    if (!$DB->fieldExists('glpi_documents_items', 'date_creation')) {
        $migration->addField('glpi_documents_items', 'date_creation', 'timestamp', ['null' => true]);
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_documents_items',
                [
                    'date_creation' => new \QueryExpression(
                        $DB->quoteName('date_mod')
                    )
                ],
                [true]
            )
        );
        $migration->addKey('glpi_documents_items', 'date_creation');
    }
    /** /Add "date_creation" field on document_items */

    /** Make datacenter pictures path relative */
    $doc_send_url = '/front/document.send.php?file=_pictures/';

    $fix_picture_fct = function ($path) use ($doc_send_url) {
       // Keep only part of URL corresponding to relative path inside GLPI_PICTURE_DIR
        return preg_replace('/^.*' . preg_quote($doc_send_url, '/') . '(.+)$/', '$1', $path);
    };

    $common_dc_model_tables = [
        'glpi_computermodels',
        'glpi_enclosuremodels',
        'glpi_monitormodels',
        'glpi_networkequipmentmodels',
        'glpi_pdumodels',
        'glpi_peripheralmodels',
    ];
    foreach ($common_dc_model_tables as $table) {
        $elements_to_fix = $DB->request(
            [
                'SELECT'    => ['id', 'picture_front', 'picture_rear'],
                'FROM'      => $table,
                'WHERE'     => [
                    'OR' => [
                        'picture_front' => ['LIKE', '%' . $doc_send_url . '%'],
                        'picture_rear' => ['LIKE', '%' . $doc_send_url . '%'],
                    ],
                ],
            ]
        );
        foreach ($elements_to_fix as $data) {
            $data['picture_front'] = $DB->escape($fix_picture_fct($data['picture_front']));
            $data['picture_rear']  = $DB->escape($fix_picture_fct($data['picture_rear']));
            $DB->updateOrDie($table, $data, ['id' => $data['id']]);
        }
    }

    $elements_to_fix = $DB->request(
        [
            'SELECT'    => ['id', 'blueprint'],
            'FROM'      => 'glpi_dcrooms',
            'WHERE'     => [
                'blueprint' => ['LIKE', '%' . $doc_send_url . '%'],
            ],
        ]
    );
    foreach ($elements_to_fix as $data) {
        $data['blueprint'] = $DB->escape($fix_picture_fct($data['blueprint']));
        $DB->updateOrDie('glpi_dcrooms', $data, ['id' => $data['id']]);
    }
    /** /Make datacenter pictures path relative */

    /** ITIL templates */
    if (!$DB->fieldExists('glpi_itilcategories', 'changetemplates_id')) {
        $migration->addField("glpi_itilcategories", "changetemplates_id", "integer", [
            'after'  => "tickettemplates_id_demand",
            'value'  => 0
        ]);
    }
    if (!$DB->fieldExists('glpi_itilcategories', 'problemtemplates_id')) {
        $migration->addField("glpi_itilcategories", "problemtemplates_id", "integer", [
            'after'  => "changetemplates_id",
            'value'  => 0
        ]);
    }

    $migration->addKey('glpi_itilcategories', 'changetemplates_id');
    $migration->addKey('glpi_itilcategories', 'problemtemplates_id');
    $migration->addKey('glpi_tickettemplatehiddenfields', 'tickettemplates_id');
    $migration->addKey('glpi_tickettemplatemandatoryfields', 'tickettemplates_id');
    $migration->addKey('glpi_tickettemplatepredefinedfields', 'tickettemplates_id');
    /** /ITiL templates */

    /** /Add Externals events for planning */
    if (!$DB->tableExists('glpi_planningexternalevents')) {
        $query = "CREATE TABLE `glpi_planningexternalevents` (
         `id` int NOT NULL AUTO_INCREMENT,
         `planningexternaleventtemplates_id` int NOT NULL DEFAULT '0',
         `entities_id` int NOT NULL DEFAULT '0',
         `is_recursive` TINYINT NOT NULL DEFAULT '1',
         `date` timestamp NULL DEFAULT NULL,
         `users_id` int NOT NULL DEFAULT '0',
         `users_id_guests` text COLLATE utf8_unicode_ci,
         `groups_id` int NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `text` text COLLATE utf8_unicode_ci,
         `begin` timestamp NULL DEFAULT NULL,
         `end` timestamp NULL DEFAULT NULL,
         `rrule` text COLLATE utf8_unicode_ci,
         `state` int NOT NULL DEFAULT '0',
         `planningeventcategories_id` int NOT NULL DEFAULT '0',
         `background` tinyint NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `planningexternaleventtemplates_id` (`planningexternaleventtemplates_id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `date` (`date`),
         KEY `begin` (`begin`),
         KEY `end` (`end`),
         KEY `users_id` (`users_id`),
         KEY `groups_id` (`groups_id`),
         KEY `state` (`state`),
         KEY `planningeventcategories_id` (`planningeventcategories_id`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_planningexternalevents");

        $new_rights = ALLSTANDARDRIGHT + PlanningExternalEvent::MANAGE_BG_EVENTS;
        $migration->addRight('externalevent', $new_rights, [
            'planning' => Planning::READMY
        ]);
    }

   // partial updates (for developers)
    if (!$DB->fieldExists('glpi_planningexternalevents', 'planningexternaleventtemplates_id')) {
        $migration->addField('glpi_planningexternalevents', 'planningexternaleventtemplates_id', 'int', [
            'after' => 'id'
        ]);
        $migration->addKey('glpi_planningexternalevents', 'planningexternaleventtemplates_id');
    }
    if (!$DB->fieldExists('glpi_planningexternalevents', 'is_recursive')) {
        $migration->addField('glpi_planningexternalevents', 'is_recursive', 'bool', [
            'after' => 'entities_id',
            'value' => 1
        ]);
        $migration->addKey('glpi_planningexternalevents', 'is_recursive');
    }
    if (!$DB->fieldExists('glpi_planningexternalevents', 'users_id_guests')) {
        $migration->addField('glpi_planningexternalevents', 'users_id_guests', 'text', [
            'after' => 'users_id',
        ]);
    }

    if (!$DB->tableExists('glpi_planningeventcategories')) {
        $query = "CREATE TABLE `glpi_planningeventcategories` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "add table glpi_planningeventcategories");
    }

   // partial update (for developers)
    if (!$DB->fieldExists('glpi_planningeventcategories', 'color')) {
        $migration->addField("glpi_planningeventcategories", "color", "string", [
            'after'  => "name"
        ]);
    }

    if (!$DB->tableExists('glpi_planningexternaleventtemplates')) {
        $query = "CREATE TABLE `glpi_planningexternaleventtemplates` (
         `id` int NOT NULL AUTO_INCREMENT,
         `entities_id` int NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `text` text COLLATE utf8_unicode_ci,
         `comment` text COLLATE utf8_unicode_ci,
         `duration` int NOT NULL DEFAULT '0',
         `before_time` int NOT NULL DEFAULT '0',
         `rrule` text COLLATE utf8_unicode_ci,
         `state` int NOT NULL DEFAULT '0',
         `planningeventcategories_id` int NOT NULL DEFAULT '0',
         `background` tinyint NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `state` (`state`),
         KEY `planningeventcategories_id` (`planningeventcategories_id`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_planningexternaleventtemplates");
    }
    /** /Add Externals events for planning */

    if (!$DB->fieldExists('glpi_entities', 'autopurge_delay')) {
        $migration->addField("glpi_entities", "autopurge_delay", "integer", [
            'after'  => "autoclose_delay",
            'value'  => Entity::CONFIG_NEVER
        ]);
    }

    CronTask::Register(
        'Ticket',
        'purgeticket',
        7 * DAY_TIMESTAMP,
        [
            'mode'  => CronTask::MODE_EXTERNAL,
            'state' => CronTask::STATE_DISABLE
        ]
    );
    /** /Add purge delay per entity */

    /** Clean oprhans documents crontask */
    CronTask::Register(
        'Document',
        'cleanorphans',
        7 * DAY_TIMESTAMP,
        [
            'mode'  => CronTask::MODE_EXTERNAL,
            'state' => CronTask::STATE_DISABLE
        ]
    );
    /** /Clean oprhans documents crontask */

    /** Item devices menu config */
    $migration->addConfig(['devices_in_menu' => exportArrayToDB(['Item_DeviceSimcard'])]);
    /** /Item devices menu config */

    if (!$DB->fieldExists("glpi_projects", "auto_percent_done")) {
        $migration->addField("glpi_projects", "auto_percent_done", "bool", [
            'after'  => "percent_done"
        ]);
    }
    if (!$DB->fieldExists("glpi_projecttasks", "auto_percent_done")) {
        $migration->addField("glpi_projecttasks", "auto_percent_done", "bool", [
            'after'  => "percent_done"
        ]);
    }
    /** /Add "code" field on glpi_itilcategories */
    if (!$DB->fieldExists("glpi_itilcategories", "code")) {
        $migration->addField("glpi_itilcategories", "code", "string", [
            'after'  => "groups_id"
        ]);
    }
    /** /Add "code" field on glpi_itilcategories */

   //Add over-quota option to software licenses to allow assignment after all alloted licenses are used
    if (!$DB->fieldExists('glpi_softwarelicenses', 'allow_overquota')) {
        if ($migration->addField('glpi_softwarelicenses', 'allow_overquota', 'bool')) {
            $migration->addKey('glpi_softwarelicenses', 'allow_overquota');
        }
    }

    /** Make software linkable to other itemtypes besides Computers */
    $migration->displayWarning('Updating software tables. This may take several minutes.');
    if (!$DB->tableExists('glpi_items_softwareversions')) {
        $migration->renameTable('glpi_computers_softwareversions', 'glpi_items_softwareversions');
        $migration->changeField(
            'glpi_items_softwareversions',
            'computers_id',
            'items_id',
            "int NOT NULL DEFAULT '0'"
        );
        $migration->addField(
            'glpi_items_softwareversions',
            'itemtype',
            "varchar(100) COLLATE utf8_unicode_ci NOT NULL",
            [
                'after' => 'items_id',
                'update' => "'Computer'", // Defines value for all existing elements
            ]
        );
        $migration->changeField('glpi_items_softwareversions', 'is_deleted_computer', 'is_deleted_item', 'bool');
        $migration->changeField('glpi_items_softwareversions', 'is_template_computer', 'is_template_item', 'bool');
        $migration->addKey('glpi_items_softwareversions', 'itemtype');
        $migration->dropKey('glpi_items_softwareversions', 'computers_id');
        $migration->addKey('glpi_items_softwareversions', 'items_id', 'items_id');
        $migration->addKey('glpi_items_softwareversions', [
            'itemtype',
            'items_id'
        ], 'item');
        $migration->dropKey('glpi_items_softwareversions', 'unicity');
        $migration->migrationOneTable('glpi_items_softwareversions');
        $migration->addKey('glpi_items_softwareversions', [
            'itemtype',
            'items_id',
            'softwareversions_id'
        ], 'unicity', 'UNIQUE');
    }

    if (!$DB->tableExists('glpi_items_softwarelicenses')) {
        $migration->renameTable('glpi_computers_softwarelicenses', 'glpi_items_softwarelicenses');
        $migration->changeField(
            'glpi_items_softwarelicenses',
            'computers_id',
            'items_id',
            "int NOT NULL DEFAULT '0'"
        );
        $migration->addField(
            'glpi_items_softwarelicenses',
            'itemtype',
            "varchar(100) COLLATE utf8_unicode_ci NOT NULL",
            [
                'after' => 'items_id',
                'update' => "'Computer'", // Defines value for all existing elements
            ]
        );
        $migration->addKey('glpi_items_softwarelicenses', 'itemtype');
        $migration->dropKey('glpi_items_softwarelicenses', 'computers_id');
        $migration->addKey('glpi_items_softwarelicenses', 'items_id', 'items_id');
        $migration->addKey('glpi_items_softwarelicenses', [
            'itemtype',
            'items_id'
        ], 'item');
    }

    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_configs',
            ['name' => 'purge_item_software_install'],
            ['name' => 'purge_computer_software_install', 'context' => 'core']
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_configs',
            ['name' => 'purge_software_item_install'],
            ['name' => 'purge_software_computer_install', 'context' => 'core']
        )
    );
    /** /Make software linkable to other itemtypes besides Computers */

    /** Add source item id to TicketTask. Used by tasks created by merging tickets */
    if (!$DB->fieldExists('glpi_tickettasks', 'sourceitems_id')) {
        if ($migration->addField('glpi_tickettasks', 'sourceitems_id', "int NOT NULL DEFAULT '0'")) {
            $migration->addKey('glpi_tickettasks', 'sourceitems_id');
        }
    }
    /** /Add source item id to TicketTask. Used by tasks created by merging tickets */

    /** Impact analysis */
   // Impact config
    $migration->addConfig(['impact_assets_list' => '[]']);

   // Impact dependencies
    if (!$DB->tableExists('glpi_impactrelations')) {
        $query = "CREATE TABLE `glpi_impactrelations` (
         `id` INT NOT NULL AUTO_INCREMENT,
         `itemtype_source` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
         `items_id_source` INT NOT NULL DEFAULT '0',
         `itemtype_impacted` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
         `items_id_impacted` INT NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (
            `itemtype_source`,
            `items_id_source`,
            `itemtype_impacted`,
            `items_id_impacted`
         ),
         KEY `source_asset` (`itemtype_source`, `items_id_source`),
         KEY `impacted_asset` (`itemtype_impacted`, `items_id_impacted`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_impacts");
    }

   // Impact compounds
    if (!$DB->tableExists('glpi_impactcompounds')) {
        $query = "CREATE TABLE `glpi_impactcompounds` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_impacts_compounds");
    }

   // Impact parents
    if (!$DB->tableExists('glpi_impactitems')) {
        $query = "CREATE TABLE `glpi_impactitems` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `itemtype` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `items_id` INT NOT NULL DEFAULT '0',
            `parent_id` INT NOT NULL DEFAULT '0',
            `zoom` FLOAT NOT NULL DEFAULT '0',
            `pan_x` FLOAT NOT NULL DEFAULT '0',
            `pan_y` FLOAT NOT NULL DEFAULT '0',
            `impact_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `impact_and_depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `position_x` FLOAT NOT NULL DEFAULT '0',
            `position_y` FLOAT NOT NULL DEFAULT '0',
            `show_depends` TINYINT NOT NULL DEFAULT '1',
            `show_impact` TINYINT NOT NULL DEFAULT '1',
            `max_depth` INT NOT NULL DEFAULT '5',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (
               `itemtype`,
               `items_id`
            ),
            KEY `source` (`itemtype`, `items_id`),
            KEY `parent_id` (`parent_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_impacts_parent");
    }
    /** /Impact analysis */

    /** Default template configuration for changes and problems */
    $migration->addKey('glpi_entities', 'tickettemplates_id');
    if (!$DB->fieldExists('glpi_entities', 'changetemplates_id')) {
        $migration->addField(
            'glpi_entities',
            'changetemplates_id',
            'integer',
            [
                'value'     => -2, // Inherit as default value
                'after'     => 'tickettemplates_id'
            ]
        );
    }
    $migration->addKey('glpi_entities', 'changetemplates_id');
    if (!$DB->fieldExists('glpi_entities', 'problemtemplates_id')) {
        $migration->addField(
            'glpi_entities',
            'problemtemplates_id',
            'integer',
            [
                'value'     => -2, // Inherit as default value
                'after'     => 'changetemplates_id'
            ]
        );
    }
    $migration->addKey('glpi_entities', 'problemtemplates_id');

    $migration->addKey('glpi_profiles', 'tickettemplates_id');
    if (!$DB->fieldExists('glpi_profiles', 'changetemplates_id')) {
        $migration->addField(
            'glpi_profiles',
            'changetemplates_id',
            'integer',
            [
                'value'     => 0, // Inherit as default value
                'after'     => 'tickettemplates_id'
            ]
        );
    }
    $migration->addKey('glpi_profiles', 'changetemplates_id');
    if (!$DB->fieldExists('glpi_profiles', 'problemtemplates_id')) {
        $migration->addField(
            'glpi_profiles',
            'problemtemplates_id',
            'integer',
            [
                'value'     => 0, // Inherit as default value
                'after'     => 'changetemplates_id'
            ]
        );
    }
    $migration->addKey('glpi_profiles', 'problemtemplates_id');
    /** /Default template configuration for changes and problems */

    /** Add Apple File System (All Apple devices since 2017) */
    if (countElementsInTable('glpi_filesystems', ['name' => 'APFS']) === 0) {
        $DB->insertOrDie('glpi_filesystems', [
            'name'   => 'APFS'
        ]);
    }
    /** /Add Apple File System (All Apple devices since 2017) */

    /** Fix indexes */
    $migration->dropKey('glpi_tickettemplatepredefinedfields', 'tickettemplates_id_id_num');
    /** /Fix indexes */

    /** Kanban */
    if (!$DB->tableExists('glpi_items_kanbans')) {
        $query = "CREATE TABLE `glpi_items_kanbans` (
         `id` int NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `items_id` int DEFAULT NULL,
         `users_id` int NOT NULL,
         `state` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`itemtype`,`items_id`,`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_kanbans");
    }
    if (!$DB->fieldExists('glpi_users', 'refresh_views')) {
        $migration->changeField('glpi_users', 'refresh_ticket_list', 'refresh_views', 'int DEFAULT NULL');
    }
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_configs',
            ['name' => 'refresh_views'],
            ['name' => 'refresh_ticket_list', 'context' => 'core']
        )
    );
    /** /Kanban */

    /** Add uuid on planning items */
    $planning_items_tables = [
        'glpi_planningexternalevents',
        'glpi_reminders',
        'glpi_projecttasks',
        'glpi_changetasks',
        'glpi_problemtasks',
        'glpi_tickettasks',
    ];
    foreach ($planning_items_tables as $table) {
        if (!$DB->fieldExists($table, 'uuid')) {
            $migration->addField(
                $table,
                'uuid',
                'string',
                [
                    'after'  => 'id'
                ]
            );
            $migration->addKey($table, 'uuid', '', 'UNIQUE');
        }
        $migration->addPostQuery(
            $DB->buildUpdate(
                $table,
                [
                    'uuid' => new \QueryExpression('UUID()'),
                ],
                [
                    'uuid' => null,
                ]
            )
        );
    }
    /** /Add uuid on planning items */

    /** Add glpi_vobjects table for CalDAV server */
    if (!$DB->tableExists('glpi_vobjects')) {
        $query = "CREATE TABLE `glpi_vobjects` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `items_id` int NOT NULL DEFAULT '0',
            `data` text COLLATE utf8_unicode_ci,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`itemtype`, `items_id`),
            KEY `item` (`itemtype`,`items_id`),
            KEY `date_mod` (`date_mod`),
            KEY `date_creation` (`date_creation`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_vobjects");
    }
    /** /Add glpi_vobjects table for CalDAV server */

    /** Fix mixed classes case in DB */
    $mixed_case_classes = [
        'AuthLdap' => 'AuthLDAP',
        'Crontask' => 'CronTask',
        'InfoCom'  => 'Infocom',
    ];
    foreach ($mixed_case_classes as $bad_case_classname => $classname) {
        $migration->renameItemtype($bad_case_classname, $classname, false);
    }
    /** /Fix mixed classes case in DB */

    /** Add geolocation to entity */
    $migration->addField("glpi_entities", "latitude", "string");
    $migration->addField("glpi_entities", "longitude", "string");
    $migration->addField("glpi_entities", "altitude", "string");
    /** Add geolocation to entity */

    /** Dashboards */
    $migration->addRight('dashboard', READ | UPDATE | CREATE | PURGE, [
        'config' => UPDATE
    ]);
    if (!$DB->tableExists('glpi_dashboards_dashboards')) {
        $query = "CREATE TABLE `glpi_dashboards_dashboards` (
         `id` int NOT NULL AUTO_INCREMENT,
         `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `context` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'core',
         PRIMARY KEY (`id`),
         UNIQUE KEY `key` (`key`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "add table glpi_dashboards_dashboards");
    }
    if (!$DB->tableExists('glpi_dashboards_items')) {
        $query = "CREATE TABLE `glpi_dashboards_items` (
        `id` int NOT NULL AUTO_INCREMENT,
        `dashboards_dashboards_id` int NOT NULL,
        `gridstack_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
        `card_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
        `x` int DEFAULT NULL,
        `y` int DEFAULT NULL,
        `width` int DEFAULT NULL,
        `height` int DEFAULT NULL,
        `card_options` text COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_dashboards_items");
    }
    if (!$DB->tableExists('glpi_dashboards_rights')) {
        $query = "CREATE TABLE `glpi_dashboards_rights` (
         `id` int NOT NULL AUTO_INCREMENT,
         `dashboards_dashboards_id` int NOT NULL,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `items_id` int NOT NULL,
         PRIMARY KEY (`id`),
         KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`),
         UNIQUE KEY `unicity` (`dashboards_dashboards_id`, `itemtype`,`items_id`)
       ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_dashboards_rights");
    }

   // migration from previous development versions
    $dashboards = Config::getConfigurationValues('core', ['dashboards']);
    if (count($dashboards)) {
        $dashboards = $dashboards['dashboards'];
        \Glpi\Dashboard\Dashboard::importFromJson($dashboards);
        Config::deleteConfigurationValues('core', ['dashboards']);
    }

   //delete prevous dashboards configuration (remove partial dev versions)
    Config::deleteConfigurationValues('core', [
        'default_dashboard_central',
        'default_dashboard_assets',
        'default_dashboard_helpdesk',
        'default_dashboard_mini_ticket',
    ]);

   // add default dashboards
    $migration->addConfig([
        'default_dashboard_central'     => 'central',
        'default_dashboard_assets'      => 'assets',
        'default_dashboard_helpdesk'    => 'assistance',
        'default_dashboard_mini_ticket' => 'mini_tickets',
    ]);

    if (!$DB->fieldExists('glpi_users', 'default_dashboard_central')) {
        $migration->addField("glpi_users", "default_dashboard_central", "varchar(100) DEFAULT NULL");
    }
    if (!$DB->fieldExists('glpi_users', 'default_dashboard_assets')) {
        $migration->addField("glpi_users", "default_dashboard_assets", "varchar(100) DEFAULT NULL");
    }
    if (!$DB->fieldExists('glpi_users', 'default_dashboard_helpdesk')) {
        $migration->addField("glpi_users", "default_dashboard_helpdesk", "varchar(100) DEFAULT NULL");
    }
    if (!$DB->fieldExists('glpi_users', 'default_dashboard_mini_ticket')) {
        $migration->addField("glpi_users", "default_dashboard_mini_ticket", "varchar(100) DEFAULT NULL");
    }

   // default dashboards
    if (countElementsInTable("glpi_dashboards_dashboards") === 0) {
        $dashboard_obj   = new \Glpi\Dashboard\Dashboard();
        $dashboards_data = include_once __DIR__ . "/update_9.4.x_to_9.5.0/dashboards.php";
        foreach ($dashboards_data as $default_dashboard) {
            $items = $default_dashboard['_items'];
            unset($default_dashboard['_items']);

           // add current dashboard
            $dashboard_id = $dashboard_obj->add($default_dashboard);

           // add items to this new dashboard
            $query = $DB->buildInsert(
                \Glpi\Dashboard\Item::getTable(),
                [
                    'dashboards_dashboards_id' => new QueryParam(),
                    'gridstack_id'             => new QueryParam(),
                    'card_id'                  => new QueryParam(),
                    'x'                        => new QueryParam(),
                    'y'                        => new QueryParam(),
                    'width'                    => new QueryParam(),
                    'height'                   => new QueryParam(),
                    'card_options'             => new QueryParam(),
                ]
            );
            $stmt = $DB->prepare($query);
            foreach ($items as $item) {
                 $stmt->bind_param(
                     'issiiiis',
                     $dashboard_id,
                     $item['gridstack_id'],
                     $item['card_id'],
                     $item['x'],
                     $item['y'],
                     $item['width'],
                     $item['height'],
                     $item['card_options']
                 );
                 $stmt->execute();
            }
        }
    }
    /** /Dashboards */

    /** Domains */
    if (!$DB->tableExists('glpi_domaintypes')) {
        $query = "CREATE TABLE `glpi_domaintypes` (
            `id` int NOT NULL        AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int NOT NULL        DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_domaintypes");
    }

    $dfields = [
        'domaintypes_id'        => 'integer',
        'date_expiration'       => 'timestamp',
        'users_id_tech'         => 'integer',
        'groups_id_tech'        => 'integer',
        'others'                => 'string',
        'is_deleted'            => 'boolean',
    ];
    $dindex = $dfields;
    unset($dindex['others']);
    $dindex = array_keys($dindex);
    $dindex[] = 'entities_id';

    $after = 'is_recursive';
    foreach ($dfields as $dfield => $dtype) {
        if (!$DB->fieldExists('glpi_domains', $dfield)) {
            $options = ['after' => $after];
            $migration->addField("glpi_domains", $dfield, $dtype, $options);
        }
        $after = $dfield;
    }

   //add indexes
    foreach ($dindex as $didx) {
        $migration->addKey('glpi_domains', $didx);
    }

    if (!$DB->tableExists('glpi_domains_items')) {
        $query = "CREATE TABLE `glpi_domains_items` (
            `id` int NOT NULL AUTO_INCREMENT,
            `domains_id` int NOT NULL DEFAULT '0',
            `items_id` int NOT NULL DEFAULT '0',
            `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`domains_id`, `itemtype`, `items_id`),
            KEY `domains_id` (`domains_id`),
            KEY `FK_device` (`items_id`, `itemtype`),
            KEY `item` (`itemtype`, `items_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_domains_items");
    }

    foreach (['Computer', 'NetworkEquipment', 'Printer'] as $itemtype) {
        if ($DB->fieldExists($itemtype::getTable(), 'domains_id')) {
            $iterator = $DB->request([
                'SELECT' => ['id', 'domains_id'],
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => ['domains_id' => ['>', 0]]
            ]);
            if (count($iterator)) {
                 //migrate existing data
                 $migration->migrationOneTable('glpi_domains_items');
                foreach ($iterator as $row) {
                    $DB->insertOrDie("glpi_domains_items", [
                        'domains_id'   => $row['domains_id'],
                        'itemtype'     => $itemtype,
                        'items_id'     => $row['id']
                    ]);
                }
            }
            $migration->dropField($itemtype::getTable(), 'domains_id');
        }
    }

    if (!$DB->fieldExists('glpi_entities', 'use_domains_alert')) {
        $migration->addField("glpi_entities", "use_domains_alert", "integer", [
            'after'  => "use_reservations_alert",
            'value'  => -2
        ]);
    }

    if (!$DB->fieldExists('glpi_entities', 'send_domains_alert_close_expiries_delay')) {
        $migration->addField("glpi_entities", "send_domains_alert_close_expiries_delay", "integer", [
            'after'  => "use_domains_alert",
            'value'  => -2
        ]);
    }

    if (!$DB->fieldExists('glpi_entities', 'send_domains_alert_expired_delay')) {
        $migration->addField("glpi_entities", "send_domains_alert_expired_delay", "integer", [
            'after'  => "send_domains_alert_close_expiries_delay",
            'value'  => -2
        ]);
    }

    $ADDTODISPLAYPREF['Domain'] = [3, 4, 2, 6, 7];
    $ADDTODISPLAYPREF['DomainRecord'] = [2, 3, ];

   //update preferences
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_displaypreferences',
            [
                'num'       => '205',
            ],
            [
                'num'       => '33',
                'itemtype'  => [
                    'Computer',
                    'NetworkEquipment',
                    'Printer'
                ]
            ]
        )
    );
    /** /Domains */

    /** Domains relations */
    if (!$DB->tableExists('glpi_domainrelations')) {
        $query = "CREATE TABLE `glpi_domainrelations` (
            `id` int NOT NULL        AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int NOT NULL        DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_domainrelations");
        $relations = DomainRelation::getDefaults();
        foreach ($relations as $relation) {
            $migration->addPostQuery(
                $DB->buildInsert(
                    DomainRelation::getTable(),
                    $relation
                )
            );
        }
    }

    if (!$DB->fieldExists('glpi_domains_items', 'domainrelations_id')) {
        $migration->addField('glpi_domains_items', 'domainrelations_id', 'integer');
        $migration->addKey('glpi_domains_items', 'domainrelations_id');
    }
    /** /Domains relations */

    /** Domain records */
    if (!$DB->tableExists('glpi_domainrecordtypes')) {
        $query = "CREATE TABLE `glpi_domainrecordtypes` (
            `id` int NOT NULL        AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int NOT NULL        DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_domainrecordtypes");
        $types = DomainRecordType::getDefaults();
        foreach ($types as $type) {
            unset($type['fields']); // This field was not present before GLPI 10.0
            $migration->addPostQuery(
                $DB->buildInsert(
                    DomainRecordType::getTable(),
                    $type
                )
            );
        }
    }

    if (!$DB->tableExists('glpi_domainrecords')) {
        $query = "CREATE TABLE `glpi_domainrecords` (
            `id` int NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `domains_id` int NOT NULL DEFAULT '0',
            `domainrecordtypes_id` int NOT NULL DEFAULT '0',
            `ttl` int NOT NULL,
            `users_id_tech` int NOT NULL DEFAULT '0',
            `groups_id_tech` int NOT NULL DEFAULT '0',
            `is_deleted` tinyint NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `entities_id` (`entities_id`),
            KEY `domains_id` (`domains_id`),
            KEY `domainrecordtypes_id` (`domainrecordtypes_id`),
            KEY `users_id_tech` (`users_id_tech`),
            KEY `groups_id_tech` (`groups_id_tech`),
            KEY `date_mod` (`date_mod`),
            KEY `is_deleted` (`is_deleted`),
            KEY `date_creation` (`date_creation`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_domainrecords");
    }

    if ($DB->fieldExists('glpi_domainrecords', 'status')) {
        $migration->dropField('glpi_domainrecords', 'status');
    }

    /** /Domain records */

    /** Domains expiration notifications */
    if (countElementsInTable('glpi_notifications', ['itemtype' => 'Domain']) === 0) {
        $DB->insertOrDie(
            'glpi_notificationtemplates',
            [
                'name'            => 'Alert domains',
                'itemtype'        => 'Domain',
                'date_mod'        => new \QueryExpression('NOW()'),
            ],
            'Add domains expiration notification template'
        );
        $notificationtemplate_id = $DB->insertId();

        $DB->insertOrDie(
            'glpi_notificationtemplatetranslations',
            [
                'notificationtemplates_id' => $notificationtemplate_id,
                'language'                 => '',
                'subject'                  => '##domain.action## : ##domain.entity##',
                'content_text'             => <<<PLAINTEXT
##lang.domain.entity## :##domain.entity##
##FOREACHdomains##
##lang.domain.name## : ##domain.name## - ##lang.domain.dateexpiration## : ##domain.dateexpiration##
##ENDFOREACHdomains##
PLAINTEXT
            ,
                'content_html'             => <<<HTML
&lt;p&gt;##lang.domain.entity## :##domain.entity##&lt;br /&gt; &lt;br /&gt;
##FOREACHdomains##&lt;br /&gt;
##lang.domain.name##  : ##domain.name## - ##lang.domain.dateexpiration## :  ##domain.dateexpiration##&lt;br /&gt;
##ENDFOREACHdomains##&lt;/p&gt;
HTML
            ,
            ],
            'Add domains expiration notification template translations'
        );

        $notifications_data = [
            [
                'event' => 'ExpiredDomains',
                'name'  => 'Alert expired domains',
            ],
            [
                'event' => 'DomainsWhichExpire',
                'name'  => 'Alert domains close expiries',
            ]
        ];
        foreach ($notifications_data as $notification_data) {
            $DB->insertOrDie(
                'glpi_notifications',
                [
                    'name'            => $notification_data['name'],
                    'entities_id'     => 0,
                    'itemtype'        => 'Domain',
                    'event'           => $notification_data['event'],
                    'comment'         => null,
                    'is_recursive'    => 1,
                    'is_active'       => 1,
                    'date_creation'   => new \QueryExpression('NOW()'),
                    'date_mod'        => new \QueryExpression('NOW()'),
                ],
                'Add domains expiration notification'
            );
            $notification_id = $DB->insertId();

            $DB->insertOrDie(
                'glpi_notifications_notificationtemplates',
                [
                    'notifications_id'         => $notification_id,
                    'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                    'notificationtemplates_id' => $notificationtemplate_id,
                ],
                'Add domains expiration notification template instance'
            );

            $DB->insertOrDie(
                'glpi_notificationtargets',
                [
                    'items_id'         => Notification::ITEM_TECH_IN_CHARGE,
                    'type'             => 1,
                    'notifications_id' => $notification_id,
                ],
                'Add domains expiration notification targets'
            );

            $DB->insertOrDie(
                'glpi_notificationtargets',
                [
                    'items_id'         => Notification::ITEM_TECH_GROUP_IN_CHARGE,
                    'type'             => 1,
                    'notifications_id' => $notification_id,
                ],
                'Add domains expiration notification targets'
            );
        }
    }
    /** /Domains expiration notifications */

    /** Impact context */

   // Create new impact_context table
    if (!$DB->tableExists('glpi_impactcontexts')) {
        $query = "CREATE TABLE `glpi_impactcontexts` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `positions` TEXT NOT NULL COLLATE 'utf8_unicode_ci',
            `zoom` FLOAT NOT NULL DEFAULT '0',
            `pan_x` FLOAT NOT NULL DEFAULT '0',
            `pan_y` FLOAT NOT NULL DEFAULT '0',
            `impact_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `impact_and_depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `show_depends` TINYINT NOT NULL DEFAULT '1',
            `show_impact` TINYINT NOT NULL DEFAULT '1',
            `max_depth` INT NOT NULL DEFAULT '5',
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, "add table glpi_impactcontexts");

       // Update glpi_impactitems
        $migration->dropField("glpi_impactitems", "zoom");
        $migration->dropField("glpi_impactitems", "pan_x");
        $migration->dropField("glpi_impactitems", "pan_y");
        $migration->dropField("glpi_impactitems", "impact_color");
        $migration->dropField("glpi_impactitems", "depends_color");
        $migration->dropField("glpi_impactitems", "impact_and_depends_color");
        $migration->dropField("glpi_impactitems", "position_x");
        $migration->dropField("glpi_impactitems", "position_y");
        $migration->dropField("glpi_impactitems", "show_depends");
        $migration->dropField("glpi_impactitems", "show_impact");
        $migration->dropField("glpi_impactitems", "max_depth");
        $migration->addField("glpi_impactitems", "impactcontexts_id", "integer");
        $migration->addField("glpi_impactitems", "is_slave", "bool", ['value' => 1]);
        $migration->addKey("glpi_impactitems", "impactcontexts_id", "impactcontexts_id");
    }
    /** /Impact context */

    /** SSO logout URL */
    $migration->addConfig(['ssologout_url' => '']);
    /** SSO logout URL */

    /** Document_Item unicity */
    $migration->dropKey('glpi_documents_items', 'unicity');
    $migration->migrationOneTable('glpi_documents_items');
    $migration->addKey(
        'glpi_documents_items',
        ['documents_id', 'itemtype', 'items_id', 'timeline_position'],
        'unicity',
        'UNIQUE'
    );
    $migration->migrationOneTable('glpi_documents_items');
    /** /Document_Item unicity */

    /** Appliances & webapps */
    require __DIR__ . '/update_9.4.x_to_9.5.0/appliances.php';
    /** /Appliances & webapps */

    /** update project and itil task templates to tinymce content **/
    $template_types = [
        'ProjectTaskTemplate' => 'description',
        'TaskTemplate'        => 'content'
    ];
    foreach ($template_types as $template_type => $fieldname) {
        $query = $DB->buildUpdate(
            $template_type::getTable(),
            [
                $fieldname => new QueryParam(),
            ],
            [
                'id'       => new QueryParam()
            ]
        );
        $stmt = $DB->prepare($query);

        $template_inst = new $template_type();
        $templates = $template_inst->find();
        foreach ($templates as $template) {
            $new_description = str_replace("\n", '<br>', $template[$fieldname]);
            $stmt->bind_param(
                'si',
                $new_description,
                $template['id']
            );
            $stmt->execute();
        }
    }
    /** /update project and itil task templates **/

    /** Add new option to mailcollector */
    $migration->addField("glpi_mailcollectors", "add_cc_to_observer", "boolean");
    /** /add new option to mailcollector */

    /** Password expiration policy */
    $migration->addConfig(
        [
            'password_expiration_delay'      => '-1',
            'password_expiration_notice'     => '-1',
            'password_expiration_lock_delay' => '-1',
        ]
    );
    if (!$DB->fieldExists('glpi_users', 'password_last_update')) {
        $migration->addField(
            'glpi_users',
            'password_last_update',
            'timestamp',
            [
                'null'   => true,
                'after'  => 'password',
            ]
        );
    }
    $passwordexpires_notif_count = countElementsInTable(
        'glpi_notifications',
        [
            'itemtype' => 'User',
            'event'    => 'passwordexpires',
        ]
    );
    if ($passwordexpires_notif_count === 0) {
        $DB->insertOrDie(
            'glpi_notifications',
            [
                'name'            => 'Password expires alert',
                'entities_id'     => 0,
                'itemtype'        => 'User',
                'event'           => 'passwordexpires',
                'comment'         => null,
                'is_recursive'    => 1,
                'is_active'       => 1,
                'date_creation'   => new \QueryExpression('NOW()'),
                'date_mod'        => new \QueryExpression('NOW()'),
            ],
            'Add password expires notification'
        );
        $notification_id = $DB->insertId();

        $DB->insertOrDie(
            'glpi_notificationtemplates',
            [
                'name'            => 'Password expires alert',
                'itemtype'        => 'User',
                'date_mod'        => new \QueryExpression('NOW()'),
            ],
            'Add password expires notification template'
        );
        $notificationtemplate_id = $DB->insertId();

        $DB->insertOrDie(
            'glpi_notifications_notificationtemplates',
            [
                'notifications_id'         => $notification_id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $notificationtemplate_id,
            ],
            'Add password expires notification template instance'
        );

        $DB->insertOrDie(
            'glpi_notificationtargets',
            [
                'items_id'         => 19,
                'type'             => 1,
                'notifications_id' => $notification_id,
            ],
            'Add password expires notification targets'
        );

        $DB->insertOrDie(
            'glpi_notificationtemplatetranslations',
            [
                'notificationtemplates_id' => $notificationtemplate_id,
                'language'                 => '',
                'subject'                  => '##user.action##',
                'content_text'             => <<<PLAINTEXT
##user.realname## ##user.firstname##,

##IFuser.password.has_expired=1##
##lang.password.has_expired.information##
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
##lang.password.expires_soon.information##
##ENDELSEuser.password.has_expired##
##lang.user.password.expiration.date##: ##user.password.expiration.date##
##IFuser.account.lock.date##
##lang.user.account.lock.date##: ##user.account.lock.date##
##ENDIFuser.account.lock.date##

##password.update.link## ##user.password.update.url##
PLAINTEXT
            ,
                'content_html'             => <<<HTML
&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;

##IFuser.password.has_expired=1##
&lt;p&gt;##lang.password.has_expired.information##&lt;/p&gt;
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
&lt;p&gt;##lang.password.expires_soon.information##&lt;/p&gt;
##ENDELSEuser.password.has_expired##
&lt;p&gt;##lang.user.password.expiration.date##: ##user.password.expiration.date##&lt;/p&gt;
##IFuser.account.lock.date##
&lt;p&gt;##lang.user.account.lock.date##: ##user.account.lock.date##&lt;/p&gt;
##ENDIFuser.account.lock.date##

&lt;p&gt;##lang.password.update.link## &lt;a href="##user.password.update.url##"&gt;##user.password.update.url##&lt;/a&gt;&lt;/p&gt;
HTML
            ,
            ],
            'Add password expires notification template translations'
        );
    }
    CronTask::Register(
        'User',
        'passwordexpiration',
        DAY_TIMESTAMP,
        [
            'mode'  => CronTask::MODE_EXTERNAL,
            'state' => CronTask::STATE_DISABLE,
            'param' => 100,
        ]
    );
    /** /Password expiration policy */

    /** Marketplace */
   // crontask
    CronTask::Register(
        'Glpi\\Marketplace\\Controller',
        'checkAllUpdates',
        DAY_TIMESTAMP,
        [
            'mode'  => CronTask::MODE_EXTERNAL,
            'state' => CronTask::STATE_WAITING,
        ]
    );

   // notification
    if (
        countElementsInTable('glpi_notifications', [
            'itemtype' => 'Glpi\\\\Marketplace\\\\Controller'
        ]) === 0
    ) {
        $DB->insertOrDie(
            'glpi_notificationtemplates',
            [
                'name'            => 'Plugin updates',
                'itemtype'        => 'Glpi\\\\Marketplace\\\\Controller',
                'date_mod'        => new \QueryExpression('NOW()'),
            ],
            'Add plugins updates notification template'
        );
        $notificationtemplate_id = $DB->insertId();

        $DB->insertOrDie(
            'glpi_notificationtemplatetranslations',
            [
                'notificationtemplates_id' => $notificationtemplate_id,
                'language'                 => '',
                'subject'                  => '##lang.plugins_updates_available##',
                'content_text'             => <<<PLAINTEXT
##lang.plugins_updates_available##

##FOREACHplugins##
##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##
##ENDFOREACHplugins##
PLAINTEXT
            ,
                'content_html'             => <<<HTML
&lt;p&gt;##lang.plugins_updates_available##&lt;/p&gt;
&lt;ul&gt;##FOREACHplugins##
&lt;li&gt;##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##&lt;/li&gt;
##ENDFOREACHplugins##&lt;/ul&gt;
HTML
            ,
            ],
            'Add plugins updates notification template translations'
        );

        $DB->insertOrDie(
            'glpi_notifications',
            [
                'name'            => 'Check plugin updates',
                'entities_id'     => 0,
                'itemtype'        => 'Glpi\\\\Marketplace\\\\Controller',
                'event'           => 'checkpluginsupdate',
                'comment'         => null,
                'is_recursive'    => 1,
                'is_active'       => 1,
                'date_creation'   => new \QueryExpression('NOW()'),
                'date_mod'        => new \QueryExpression('NOW()'),
            ],
            'Add plugins updates notification'
        );
        $notification_id = $DB->insertId();

        $DB->insertOrDie(
            'glpi_notifications_notificationtemplates',
            [
                'notifications_id'         => $notification_id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $notificationtemplate_id,
            ],
            'Add plugins updates notification template instance'
        );

        $DB->insertOrDie(
            'glpi_notificationtargets',
            [
                'items_id'         => Notification::GLOBAL_ADMINISTRATOR,
                'type'             => 1,
                'notifications_id' => $notification_id,
            ],
            'Add domains expiration notification targets'
        );
    }
    /** /Marketplace */

    /** Update default right assignement rule (only it exactly match previous default rule) */
    $prev_rule = [
        'entities_id'  => 0,
        'sub_type'     => 'RuleRight',
        'ranking'      => 1,
        'name'         => 'Root',
        'description'  => '',
        'match'        => 'OR',
        'is_active'    => 1,
        'is_recursive' => 0,
        'uuid'         => '500717c8-2bd6e957-53a12b5fd35745.02608131',
        'condition'    => 0,
    ];
    $rule = new Rule();
    if ($rule->getFromDBByCrit($prev_rule)) {
        $rule->getRuleWithCriteriasAndActions($rule->fields['id'], true, true);

        $prev_criteria = [
            [
                'rules_id'  => $rule->fields['id'],
                'criteria'  => 'uid',
                'condition' => 0,
                'pattern'   => '*',
            ],
            [
                'rules_id'  => $rule->fields['id'],
                'criteria'  => 'samaccountname',
                'condition' => 0,
                'pattern'   => '*',
            ],
            [
                'rules_id'  => $rule->fields['id'],
                'criteria'  => 'MAIL_EMAIL',
                'condition' => 0,
                'pattern'   => '*',
            ],
        ];
        $prev_actions = [
            [
                'rules_id'    => $rule->fields['id'],
                'action_type' => 'assign',
                'field'       => 'entities_id',
                'value'       => '0',
            ],
        ];

        $matching_criteria = 0;
        foreach ($rule->criterias as $criteria) {
            $existing_criteria = $criteria->fields;
            unset($existing_criteria['id']);
            if (in_array($existing_criteria, $prev_criteria)) {
                $matching_criteria++;
            }
        }

        $matching_actions  = 0;
        foreach ($rule->actions as $action) {
            $existing_action = $action->fields;
            unset($existing_action['id']);
            if (in_array($existing_action, $prev_actions)) {
                $matching_actions++;
            }
        }

        if (
            count($rule->criterias) == count($prev_criteria)
            && count($rule->criterias) == $matching_criteria
            && count($rule->actions) == count($prev_actions)
            && count($rule->actions) == $matching_actions
        ) {
           // rule matches previous default rule (same criteria and actions)
           // so we can replace criteria
            $DB->deleteOrDie('glpi_rulecriterias', ['rules_id' => $rule->fields['id']]);
            $DB->insertOrDie(
                'glpi_rulecriterias',
                [
                    'rules_id'  => $rule->fields['id'],
                    'criteria'  => 'TYPE',
                    'condition' => 0,
                    'pattern'   => Auth::LDAP,
                ],
                'Update default right assignement rule'
            );
            $DB->insertOrDie(
                'glpi_rulecriterias',
                [
                    'rules_id'  => $rule->fields['id'],
                    'criteria'  => 'TYPE',
                    'condition' => 0,
                    'pattern'   => Auth::MAIL,
                ],
                'Update default right assignement rule'
            );
        }
    }
    /** /Update default right assignement rule */

    /** Passive Datacenter equipments */
    if (!$DB->tableExists('glpi_passivedcequipments')) {
        $query = "CREATE TABLE `glpi_passivedcequipments` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `entities_id` int NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `locations_id` int NOT NULL DEFAULT '0',
         `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `passivedcequipmentmodels_id` int DEFAULT NULL,
         `passivedcequipmenttypes_id` int NOT NULL DEFAULT '0',
         `users_id_tech` int NOT NULL DEFAULT '0',
         `groups_id_tech` int NOT NULL DEFAULT '0',
         `is_template` tinyint NOT NULL DEFAULT '0',
         `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `states_id` int NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
         `comment` text COLLATE utf8_unicode_ci,
         `manufacturers_id` int NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `locations_id` (`locations_id`),
         KEY `passivedcequipmentmodels_id` (`passivedcequipmentmodels_id`),
         KEY `passivedcequipmenttypes_id` (`passivedcequipmenttypes_id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `group_id_tech` (`groups_id_tech`),
         KEY `is_template` (`is_template`),
         KEY `is_deleted` (`is_deleted`),
         KEY `states_id` (`states_id`),
         KEY `manufacturers_id` (`manufacturers_id`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "add table glpi_passivedcequipments");
    }
    if (!$DB->tableExists('glpi_passivedcequipmentmodels')) {
        $query = "CREATE TABLE `glpi_passivedcequipmentmodels` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `weight` int NOT NULL DEFAULT '0',
         `required_units` int NOT NULL DEFAULT '1',
         `depth` float NOT NULL DEFAULT 1,
         `power_connections` int NOT NULL DEFAULT '0',
         `power_consumption` int NOT NULL DEFAULT '0',
         `is_half_rack` tinyint NOT NULL DEFAULT '0',
         `picture_front` text COLLATE utf8_unicode_ci,
         `picture_rear` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`),
         KEY `product_number` (`product_number`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "add table glpi_passivedcequipmentmodels");
    }
    if (!$DB->tableExists('glpi_passivedcequipmenttypes')) {
        $query = "CREATE TABLE `glpi_passivedcequipmenttypes` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "add table glpi_passivedcequipmenttypes");
    }
    if (!$DB->fieldExists('glpi_states', 'is_visible_passivedcequipment')) {
        $migration->addField('glpi_states', 'is_visible_passivedcequipment', 'bool', [
            'value' => 1,
            'after' => 'is_visible_rack'
        ]);
        $migration->addKey('glpi_states', 'is_visible_passivedcequipment');
    }
    /** /Passive Datacenter equipments */

    if (!$DB->fieldExists('glpi_profiles', 'managed_domainrecordtypes')) {
        $migration->addField(
            'glpi_profiles',
            'managed_domainrecordtypes',
            'text',
            [
                'after'     => 'change_status'
            ]
        );
    }

    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_profiles',
            [
                'managed_domainrecordtypes' => exportArrayToDB([])
            ],
            [
                'managed_domainrecordtypes' => ['', null]
            ]
        )
    );

    // Add anonymize_support_agents to entity
    if (!$DB->fieldExists("glpi_entities", "anonymize_support_agents")) {
        $migration->addField(
            "glpi_entities",
            "anonymize_support_agents",
            "integer",
            [
                'after'     => "suppliers_as_private",
                'value'     => -2,               // Inherit as default value
                'update'    => '0',              // Not enabled for root entity
                'condition' => 'WHERE `id` = 0'
            ]
        );
    }

    /**  Reminders translations */
    $migration->addConfig(['translate_reminders' => 0]);
   //Create remindertranslations table
    if (!$DB->tableExists('glpi_remindertranslations')) {
        $query = "CREATE TABLE `glpi_remindertranslations` (
                 `id` int NOT NULL AUTO_INCREMENT,
                 `reminders_id` int NOT NULL DEFAULT '0',
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `name` text COLLATE utf8_unicode_ci,
                 `text` longtext COLLATE utf8_unicode_ci,
                 `users_id` int NOT NULL DEFAULT '0',
                 `date_mod` timestamp NULL DEFAULT NULL,
                 `date_creation` timestamp NULL DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 KEY `item` (`reminders_id`,`language`),
                 KEY `users_id` (`users_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->queryOrDie($query, "add table glpi_remindertranslations");
    }
    /**  Reminders translations */

    /**  Add default impact itemtypes */
    $impact_default = exportArrayToDB(Impact::getDefaultItemtypes());
    $migration->addConfig([Impact::CONF_ENABLED => $impact_default]);
    /**  /Add default impact itemtypes */

   // Add new field states in contract
    if (!$DB->fieldExists('glpi_states', 'is_visible_contract')) {
        $migration->addField('glpi_states', 'is_visible_contract', 'bool', [
            'value' => 1,
            'after' => 'is_visible_cluster'
        ]);
        $migration->addKey('glpi_states', 'is_visible_contract');
    }

    if (!$DB->fieldExists('glpi_contracts', 'states_id')) {
        $migration->addField('glpi_contracts', 'states_id', 'int', [
            'value' => 0,
            'after' => 'is_template'
        ]);
        $migration->addKey('glpi_contracts', 'states_id');
    }

   // No-reply notifications
    if (!$DB->fieldExists(Notification::getTable(), 'allow_response')) {
        $migration->addField(Notification::getTable(), 'allow_response', 'bool', [
            'value' => 1
        ]);
    }

    $migration->addConfig([
        'admin_email_noreply'      => "",
        'admin_email_noreply_name' => "",
    ]);
   // /No-reply notifications

   // use_kerberos
    if ($DB->fieldExists(MailCollector::getTable(), 'use_kerberos')) {
        $migration->dropField(MailCollector::getTable(), 'use_kerberos');
    }
   // /use_kerberos

   // add missing fields to simcard as they can be associated to tickets
    if (!$DB->fieldExists(Item_DeviceSimcard::getTable(), 'users_id')) {
        $migration->addField(Item_DeviceSimcard::getTable(), 'users_id', 'int', [
            'value' => 0,
            'after' => 'lines_id'
        ]);
        $migration->addKey(Item_DeviceSimcard::getTable(), 'users_id');
    }
    if (!$DB->fieldExists(Item_DeviceSimcard::getTable(), 'groups_id')) {
        $migration->addField(Item_DeviceSimcard::getTable(), 'groups_id', 'int', [
            'value' => 0,
            'after' => 'users_id'
        ]);
        $migration->addKey(Item_DeviceSimcard::getTable(), 'groups_id');
    }
   // /add missing fields to simcard as they can be associated to tickets

   // remove superflu is_helpdesk_visible
    if ($DB->fieldExists(Appliance::getTable(), 'is_helpdesk_visible')) {
        $migration->dropField(Appliance::getTable(), 'is_helpdesk_visible');
    }
    if ($DB->fieldExists(Domain::getTable(), 'is_helpdesk_visible')) {
        $migration->dropField(Domain::getTable(), 'is_helpdesk_visible');
    }
   // /remove superflu is_helpdesk_visible

   // GLPI Network registration key config
    $migration->addConfig(['glpinetwork_registration_key' => null]);

    if (isset($CFG_GLPI['glpinetwork_registration_key']) && !empty($CFG_GLPI['glpinetwork_registration_key'])) {
       // encrypt existing keys if not yet encrypted
       // if it can be base64 decoded then json decoded, we can consider that it was not encrypted
        if (
            ($b64_decoded = base64_decode($CFG_GLPI['glpinetwork_registration_key'], true)) !== false
            && json_decode($b64_decoded, true) !== null
        ) {
            Config::setConfigurationValues(
                'core',
                [
                    'glpinetwork_registration_key' => (new GLPIKey())->encrypt($CFG_GLPI['glpinetwork_registration_key'])
                ]
            );
        }
    }

   // /GLPI Network registration key config

   // ************ Keep it at the end **************
    foreach ($ADDTODISPLAYPREF as $type => $tab) {
        $rank = 1;
        foreach ($tab as $newval) {
            $DB->updateOrInsert("glpi_displaypreferences", [
                'rank'      => $rank++
            ], [
                'users_id'  => "0",
                'itemtype'  => $type,
                'num'       => $newval,
            ]);
        }
    }

    $migration->executeMigration();

    return $updateresult;
}
