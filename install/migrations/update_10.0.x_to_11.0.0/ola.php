<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @var       \DBmysql $DB
 * @var       \Migration $migration
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

$migration->log('Group managed olas', false);

remove_olas_fields_in_tickets($migration);
add_groups_id_field_in_olas($migration);
create_items_olas_table($migration);

$migration->executeMigration();
return;

// --- functions

function add_groups_id_field_in_olas(Migration $migration)
{
    $migration->addField(
        OLA::getTable(),
        Group::getForeignKeyField(),
        'fkey',
        [
            'value' => '0',
            'null' => false,
            'after' => 'slms_id',
        ]
    );

    // addKey requires the table to exist -> execute migration before
    $migration->executeMigration();
    $migration->addKey(OLA::getTable(), Group::getForeignKeyField());

    // solution temporaire pour le problème des groupes
    // - création d'un groupe
    // - ajout du groupe à l'ola
    // - ajout de l'utilisateur 'normal' au groupe
    // @todo problème a résoudre avec Alexandre
    if (!countElementsInTable(Group::getTable())) {
        $group = new Group();
        if (!$group->add(['name' => 'ola_group', 'comment' => 'ola_group temporaire à fixer'])) {
            ;
            throw new Exception('Impossible de créer le groupe ola_group');
        }
        $groups_id = $group->getID();
    } else {
        // le premier groupe trouvé
        $group = new Group();
        $groups_ids = $group->find([]);
        $groups_id = array_pop($groups_ids)['id'];
    }
    $migration->addPostQuery('UPDATE ' . OLA::getTable() . ' SET ' . Group::getForeignKeyField() . ' = ' . $groups_id);
    // ajout de 'normal' au group
    $user = new User();
    $user->getFromDBbyName('normal');
    $association = new Group_User();
    // synchrone delete
    /** @var \DB $DB */
    global $DB;
    $DB->delete(Group_User::getTable(), ['users_id' => $user->getID()]);

    if (!$association->add(['groups_id' => $groups_id, 'users_id' => $user->getID(), 'is_dynamic' => 0, 'is_manager' => 0])) {
        throw new Exception('Impossible d\'ajouter l\'utilisateur normal au groupe ola_group');
    }

    $migration->addPostQuery('UPDATE ' . OLA::getTable() . ' SET ' . Group::getForeignKeyField() . ' = ' . $groups_id);
    // fin du fix temporaire
}

function remove_olas_fields_in_tickets(Migration $migration): void
{
    $fields_to_remove = [
        'ola_waiting_duration',
        'olas_id_tto',
        'olas_id_ttr',
        'olalevels_id_ttr',
        'ola_tto_begin_date',
        'ola_ttr_begin_date',
        'internal_time_to_resolve',
        'internal_time_to_own',
    ];

    foreach ($fields_to_remove as $field) {
        $migration->dropField(Ticket::getTable(), $field);
    }
}
function create_items_olas_table(Migration $migration): void
{
    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $pk_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $query = "CREATE TABLE IF NOT EXISTS `glpi_items_olas` (
        `id`            int {$pk_sign} NOT NULL AUTO_INCREMENT,
        `itemtype`      varchar(255) NOT NULL,
        `items_id`      int unsigned NOT NULL,
        `olas_id`       int unsigned NOT NULL,
        -- `ola_type` int NOT NULL, -- 1: TTO, 2: TTR -- https://outline.teclib.com/doc/specification-de-la-gestion-des-ola-dans-glpi-aozoEQaxH4?commentId=53e8214a-e798-4b81-9df9-f8cf800cc613
        `start_time`    timestamp NULL DEFAULT NULL,
        `due_time`      timestamp NULL DEFAULT NULL,
        `end_time`      timestamp NULL DEFAULT NULL,
        -- `status` int NOT NULL,
        `waiting_time` int NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) 
         ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC;";

    $migration->addPreQuery($query);
    $migration->executeMigration();

    $migration->addKey(Item_Ola::getTable(), OLA::getForeignKeyField());
    $migration->addKey(Item_Ola::getTable(), ['itemtype', 'items_id'], 'item');
}
