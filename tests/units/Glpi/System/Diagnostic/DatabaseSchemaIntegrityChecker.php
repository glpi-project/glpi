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

namespace tests\units\Glpi\System\Diagnostic;

use Glpi\Toolbox\VersionParser;
use org\bovigo\vfs\vfsStream;

class DatabaseSchemaIntegrityChecker extends \GLPITestCase
{
    protected function schemaProvider(): iterable
    {
        $table_increment = 0;

        $convert_to_provider_entry = static function (array $tables, array $args) {
            return [
                'schema'               => implode(
                    "\n",
                    array_map(
                        function ($sql) {
                            return $sql . ';';
                        },
                        array_column($tables, 'raw_sql')
                    )
                ),
                'raw_tables'           => array_combine(array_column($tables, 'name'), array_column($tables, 'raw_sql')),
                'normalized_tables'    => array_combine(array_column($tables, 'name'), array_column($tables, 'normalized_sql')),
                'effective_tables'     => array_combine(array_column($tables, 'name'), array_column($tables, 'effective_sql')),
                'expected_differences' => array_filter(array_combine(array_column($tables, 'name'), array_column($tables, 'differences'))),
                'args'                 => $args,
            ];
        };

        // Checks related to normalization of tokens that may differ depending on
        // application used to export schema or tokens that have no incidence on data.

        $tables = [
            // Whitespaces, case, optional quotes, and funcion/constant usages should be normalized:
            // - extra whitespaces should be removed;
            // - quotes around default numeric values should be removed;
            // - quotes around collate value should be removed;
            // - auto_increment should be replaced by by AUTO_INCREMENT;
            // - current_timestamp() should be replaced by CURRENT_TIMESTAMP.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL auto_increment,
  `name` VARCHAR(255) NOT NULL,
  `nameid`varchar ( 255 ) NOT NULL,
  `description` TEXT
                NOT NULL
                CHARSET latin1 COLLATE 'latin1_general_ci',
  `value` INT       NOT NULL DEFAULT '0',
  `steps` FLOAT     NOT NULL DEFAULT '-0.7',
  `max`    int    NOT  NULL    DEFAULT    '100',
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint NOT NULL DEFAULT '0',
  PRIMARY   KEY     (`id`),
  UNIQUE KEY `nameid` (`name`), UNIQUE KEY `nameid` (`nameid`),
  FULLTEXT KEY `description` ( `description` ),
  KEY`is_deleted`(`is_deleted`),
  KEY `values` (
    `value`,
    `steps`,    
    `max`
  )
) ENGINE=MyISAM
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NOT NULL,
  `description` text NOT NULL CHARSET latin1 COLLATE latin1_general_ci,
  `value` int NOT NULL DEFAULT 0,
  `steps` float NOT NULL DEFAULT -0.7,
  `max` int NOT NULL DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nameid` (`name`),
  UNIQUE KEY `nameid` (`nameid`),
  FULLTEXT KEY `description` (`description`),
  KEY `is_deleted` (`is_deleted`),
  KEY `values` (`value`,`steps`,`max`)
) ENGINE=MyISAM
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NOT NULL,
  `description` text NOT NULL CHARSET latin1 COLLATE latin1_general_ci,
  `value` int NOT NULL DEFAULT 0,
  `steps` float NOT NULL DEFAULT -0.7,
  `max` int NOT NULL DEFAULT '100',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nameid` (`name`),
  UNIQUE KEY `nameid` (`nameid`),
  FULLTEXT KEY `description` (`description`),
  KEY `is_deleted` (`is_deleted`),
  KEY `values` (`value`, `steps`, `max`)
) ENGINE=MyISAM
SQL,
                'differences'    => null,
            ],
            // AUTO_INCREMENT, integer display width, and comments should be removed
            // and should not be included in diff.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_valid` tinyint(4) NOT NULL COMMENT 'is object valid ?',
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`) COMMENT 'this is a key'
) ENGINE=InnoDB COMMENT='some comment with an escaped \' backquote' AUTO_INCREMENT=15
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_valid` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`)
) ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'name of the object',
  `is_valid` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`)
) ENGINE=InnoDB AUTO_INCREMENT=15
SQL,
                'differences'    => null,
            ],
            // `;` char in comments should be handled correctly.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `do_count` tinyint(1) NOT NULL DEFAULT '2' COMMENT 'Do or do not count results on list display; see SavedSearch::COUNT_* constants',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='some comment with an escaped \' backquote' AUTO_INCREMENT=15
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `do_count` tinyint NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'name of the object',
  `do_count` tinyint NOT NULL DEFAULT 2 COMMENT 'Do or do not count results on list display; see SavedSearch::COUNT_* constants',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15
SQL,
                'differences'    => null,
            ],
            // Implicit NULL and implicit default values should be removed.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NULL DEFAULT NULL,
  `description` text NULL DEFAULT NULL,
  `value` int NOT NULL DEFAULT '0',
  `steps` float NULL DEFAULT '-0.7',
  `date` timestamp NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255),
  `description` text,
  `value` int NOT NULL DEFAULT 0,
  `steps` float DEFAULT -0.7,
  `date` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NULL,
  `description` text NULL,
  `value` int NOT NULL DEFAULT 0,
  `steps` float NULL DEFAULT -0.7,
  `date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL,
                'differences'    => null,
            ],
            // Indexes definition should be normalized:
            // - INDEX should be replaced by KEY;
            // - missing optional KEY should be added;
            // - missing index identifier should be added.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NOT NULL,
  `description` text,
  `is_deleted` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name` (`name`),
  UNIQUE `nameid` (`nameid`),
  FULLTEXT (`description`),
  INDEX `is_deleted` (`is_deleted`),
) ENGINE=MyISAM
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NOT NULL,
  `description` text,
  `is_deleted` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `nameid` (`nameid`),
  FULLTEXT KEY `description` (`description`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nameid` varchar(255) NOT NULL,
  `description` text,
  `is_deleted` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE `name` (`name`),
  UNIQUE `nameid` (`nameid`),
  FULLTEXT `description` (`description`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=MyISAM
SQL,
                'differences'    => null,
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => true,
                'use_utf8mb4' => true,
                'ignore_innodb_migration' => false,
                'ignore_timestamps_migration' => false,
                'ignore_utf8mb4_migration' => false,
                'ignore_dynamic_row_format_migration' => false,
                'ignore_unsigned_keys_migration' => false
            ]
        );

        // Checks using strict mode and including tokens related to migrations.

        $tables = [
            // Strict mode do not reorder columns/indexes and do not remove ROW_FORMAT=DYNAMIC.
            // Order differences should be included in diff.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `is_valid` tinyint NOT NULL,
  KEY `is_valid` (`is_valid`),
  UNIQUE KEY `name` (`name`),
  PRIMARY KEY (`id`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `is_valid` tinyint NOT NULL,
  KEY `is_valid` (`is_valid`),
  UNIQUE KEY `name` (`name`),
  PRIMARY KEY (`id`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_valid` tinyint NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
+  `is_valid` tinyint NOT NULL,
   `name` varchar(255) NOT NULL,
-  `description` text,
-  `is_valid` tinyint NOT NULL,
+  PRIMARY KEY (`id`),
   KEY `is_valid` (`is_valid`),
-  UNIQUE KEY `name` (`name`),
-  PRIMARY KEY (`id`),
-  FULLTEXT KEY `description` (`description`)
+  UNIQUE KEY `name` (`name`)
 ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC

DIFF,
                ]
            ],

            // utf8mb3 should be normalized to utf8.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'differences'    => null,
            ],

            // utf8mb4 related tokens:
            // - on fields: only non utf8mb4 collate are preserved,
            // - on table: collate and charset are preserved, even if utf8mb4.
            // Following differences should be ignored:
            // - missing default charset/collate on columns if matching utf8mb4;
            // - 'mediumtext' instead of 'text'.
            // - 'longtext' instead of 'mediumtext'.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text,
  `description` text,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
+  `name` varchar(255) NOT NULL,
   `content` text,
   `description` text,
-  `bis` varchar(100) CHARSET latin1,
+  `bis` varchar(100),
   PRIMARY KEY (`id`)
 ) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB

DIFF,
                ]
            ],

            // Charset/collation should NOT be removed/ignored if related to utf8mb4 migration
            // when NOT using ignore_utf8mb4_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `description` text CHARSET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `description` text CHARSET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARSET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL,
-  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
+  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
+  `content` text,
   `description` text CHARSET latin1 COLLATE latin1_general_ci,
   PRIMARY KEY (`id`)
-) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB
+) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB

DIFF,
                ]
            ],

            // timestamp should NOT be replaced by datetime/ignored when NOT using ignore_timestamps_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `created_at` timestamp NOT NULL,
-  `updated_at` timestamp,
+  `created_at` datetime NOT NULL,
+  `updated_at` datetime,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB

DIFF,
                ]
            ],

            // ROW_FORMAT should NOT be removed/ignored when NOT using ignore_dynamic_row_format_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=COMPACT
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
-) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
+) ENGINE=InnoDB ROW_FORMAT=COMPACT

DIFF,
                ]
            ],

            // ENGINE should NOT be removed/ignored when NOT using ignore_innodb_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
-) ENGINE=InnoDB
+) ENGINE=MyISAM

DIFF,
                ]
            ],

            // signed/unsigned on primary/foreign keys should NOT be removed/ignored when NOT using ignore_unsigned_keys_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `users_id` int unsigned NOT NULL,
  `users_id_tech` int DEFAULT NULL,
  `groups_id` int NOT NULL,
  `groups_id_tech` int unsigned DEFAULT NULL,
  `uid` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `users_id` int unsigned NOT NULL,
  `users_id_tech` int,
  `groups_id` int NOT NULL,
  `groups_id_tech` int unsigned,
  `uid` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `users_id` int NOT NULL,
  `users_id_tech` int unsigned DEFAULT NULL,
  `groups_id` int unsigned NOT NULL,
  `groups_id_tech` int DEFAULT NULL,
  `uid` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
-  `users_id` int unsigned NOT NULL,
-  `users_id_tech` int,
-  `groups_id` int NOT NULL,
-  `groups_id_tech` int unsigned,
+  `users_id` int NOT NULL,
+  `users_id_tech` int unsigned,
+  `groups_id` int unsigned NOT NULL,
+  `groups_id_tech` int,
   `uid` int unsigned NOT NULL,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB

DIFF,
                ]
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => true,
                'use_utf8mb4' => true,
                'ignore_innodb_migration' => false,
                'ignore_timestamps_migration' => false,
                'ignore_utf8mb4_migration' => false,
                'ignore_dynamic_row_format_migration' => false,
                'ignore_unsigned_keys_migration' => false
            ]
        );

        // Checks using non-strict mode and ignoring tokens related to migrations.

        $tables = [
            // Check should detect missing keys and columns.
            // Fields and indexes are reordered in non strict mode.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  `field` text,
  `computers_id` tinyint NOT NULL,
  `is_valid` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computers_id`,`is_valid`),
  UNIQUE KEY `name` (`name`),
  FULLTEXT KEY `content` (`content`),
  FULLTEXT KEY `field` (`field`),
  KEY `computers_id` (`computers_id`),
  KEY `is_valid` (`is_valid`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `computers_id` tinyint NOT NULL,
  `content` text,
  `field` text,
  `is_valid` tinyint NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `unicity` (`computers_id`,`is_valid`),
  FULLTEXT KEY `content` (`content`),
  FULLTEXT KEY `field` (`field`),
  KEY `computers_id` (`computers_id`),
  KEY `is_valid` (`is_valid`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text,
  `name` varchar(255) NOT NULL,
  `is_valid` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`),
  UNIQUE KEY `name` (`name`),
  FULLTEXT KEY `content` (`content`),
) ENGINE=InnoDB
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `computers_id` tinyint NOT NULL,
   `content` text,
-  `field` text,
   `is_valid` tinyint NOT NULL,
   `name` varchar(255) NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `name` (`name`),
-  UNIQUE KEY `unicity` (`computers_id`,`is_valid`),
   FULLTEXT KEY `content` (`content`),
-  FULLTEXT KEY `field` (`field`),
-  KEY `computers_id` (`computers_id`),
   KEY `is_valid` (`is_valid`)
 )

DIFF,
                ]
            ],

            // Charset/collation should be removed/ignored if related to utf8mb4 migration
            // when using ignore_utf8mb4_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `description` text CHARSET latin1,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text,
  `description` text CHARSET latin1,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARSET latin1,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
                'differences'    => null,
            ],

            // timestamp should be replaced by datetime/ignored when using ignore_timestamps_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'differences'    => null,
            ],

            // ROW_FORMAT should be removed/ignored when using ignore_dynamic_row_format_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=COMPACT
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=COMPACT
SQL,
                'differences'    => null,
            ],

            // ENGINE should be removed/ignored when using ignore_innodb_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL,
                'differences'    => null,
            ],

            // signed/unsigned on primary/foreign keys should be removed/ignored when using ignore_unsigned_keys_migration flag
            // unless it is on something else than primary/foreign keys
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groups_id_tech` int unsigned DEFAULT NULL,
  `groups_id` int NOT NULL,
  `uid` int unsigned NOT NULL,
  `users_id_tech` int DEFAULT NULL,
  `users_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groups_id_tech` int,
  `groups_id` int NOT NULL,
  `uid` int unsigned NOT NULL,
  `users_id_tech` int,
  `users_id` int NOT NULL,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groups_id_tech` int DEFAULT NULL,
  `groups_id` int unsigned NOT NULL,
  `uid` int unsigned NOT NULL,
  `users_id_tech` int unsigned DEFAULT NULL,
  `users_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'differences'    => null,
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => false,
                'use_utf8mb4' => true,
                'ignore_innodb_migration' => true,
                'ignore_timestamps_migration' => true,
                'ignore_utf8mb4_migration' => true,
                'ignore_dynamic_row_format_migration' => true,
                'ignore_unsigned_keys_migration' => true
            ]
        );

        // Checks related to utf8mb4 migration on a utf8mb3 table, including migration tokens.

        $tables = [
            // DB NOT using utf8mb4:
            // - on fields: only non utf8 collate are preserved,
            // - on table: collate and charset are preserved, evenf if utf8.
            // Following differences should NOT be ignored:
            // - missing default charset/collate on columns if matching utf8mb4;
            // - 'mediumtext' instead of 'text'.
            // - 'longtext' instead of 'mediumtext'.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` mediumtext,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL,
-  `content` text,
-  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
-  `bis` varchar(100) CHARSET latin1,
+  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
+  `content` mediumtext,
+  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
+  `bis` varchar(100),
   PRIMARY KEY (`id`)
 ) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB

DIFF,
                ]
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => true,
                'use_utf8mb4' => false,
                'ignore_innodb_migration' => false,
                'ignore_timestamps_migration' => false,
                'ignore_utf8mb4_migration' => false,
                'ignore_dynamic_row_format_migration' => false,
                'ignore_unsigned_keys_migration' => false
            ]
        );

        // Checks related to utf8mb4 migration on a utf8mb3 table, including migration tokens.

        $tables = [
            // Charset/collation should NOT be removed/ignored if related to utf8mb4 migration
            // when NOT using ignore_utf8mb4_migration flag.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `description` text CHARSET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text,
  `description` text CHARSET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARSET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
-  `content` text,
+  `name` varchar(255) NOT NULL,
+  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
   `description` text CHARSET latin1 COLLATE latin1_general_ci,
   PRIMARY KEY (`id`)
-) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB
+) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB

DIFF,
                ]
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => true,
                'use_utf8mb4' => false,
                'ignore_innodb_migration' => false,
                'ignore_timestamps_migration' => false,
                'ignore_utf8mb4_migration' => false,
                'ignore_dynamic_row_format_migration' => false,
                'ignore_unsigned_keys_migration' => false
            ]
        );

        // Checks related to utf8mb3/mb4 on a utf8mb3 database, ignoring migration tokens.

        $tables = [
            // DB NOT using utf8mb4:
            // - on fields: only non utf8 collate are preserved,
            // - on table: collate and charset are preserved, evenf if utf8.
            // Following differences should NOT be ignored:
            // - missing default charset/collate on columns if matching utf8mb4;
            // - 'mediumtext' instead of 'text'.
            // - 'longtext' instead of 'mediumtext'.
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  `description` text,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` mediumtext,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `table_{$table_increment}` (
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
-  `content` text,
-  `description` text,
-  `bis` varchar(100) CHARSET latin1,
+  `content` mediumtext,
+  `description` longtext,
+  `bis` varchar(100),
   PRIMARY KEY (`id`)
 )

DIFF,
                ]
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => true,
                'use_utf8mb4' => false,
                'ignore_innodb_migration' => true,
                'ignore_timestamps_migration' => true,
                'ignore_utf8mb4_migration' => true,
                'ignore_dynamic_row_format_migration' => true,
                'ignore_unsigned_keys_migration' => true
            ]
        );

        // Checks related to unsigned migration on a database not allowing signed keys, ignoring migration tokens.
        // "unsigned" should be visible added to expected primary/foreign keys in diff, but not in normalized SQL

        $tables = [
            [
                'name' => sprintf('table_%s', ++$table_increment),
                'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `users_id` int unsigned NOT NULL,
  `users_id_tech` int DEFAULT NULL,
  `groups_id` int NOT NULL,
  `groups_id_tech` int unsigned DEFAULT NULL,
  `projects_id` int NOT NULL DEFAULT 0,
  `uid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `users_id` int NOT NULL,
  `users_id_tech` int,
  `groups_id` int NOT NULL,
  `groups_id_tech` int,
  `projects_id` int NOT NULL DEFAULT 0,
  `uid` int,
  PRIMARY KEY (`id`)
)
SQL,
                'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `users_id` int NOT NULL,
  `groups_id_tech` int DEFAULT NULL,
  `projects_id` int DEFAULT NULL,
  `uid` int DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL,
                'differences'    => [
                    'type' => 'altered_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
   `users_id` int NOT NULL,
-  `users_id_tech` int unsigned,
-  `groups_id` int unsigned NOT NULL,
   `groups_id_tech` int,
-  `projects_id` int unsigned NOT NULL DEFAULT 0,
-  `uid` int,
+  `projects_id` int,
+  `uid` int DEFAULT 1,
   PRIMARY KEY (`id`)
 )

DIFF,
                ],
            ],
        ];

        yield $convert_to_provider_entry(
            $tables,
            [
                'strict' => true,
                'allow_signed_keys' => false,
                'ignore_innodb_migration' => true,
                'ignore_timestamps_migration' => true,
                'ignore_utf8mb4_migration' => true,
                'ignore_dynamic_row_format_migration' => true,
                'ignore_unsigned_keys_migration' => true
            ]
        );

        // Always ignore key length when value is `250`,
        // but detect differences when value is not `250`.
        yield $convert_to_provider_entry(
            [
                [
                    'name' => sprintf('table_%s', ++$table_increment),
                    'raw_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `uid` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `key` (`key`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB
SQL,
                    'normalized_sql' => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `uid` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `key` (`key`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB
SQL,
                    'effective_sql'  => <<<SQL
CREATE TABLE `table_{$table_increment}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `uid` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(250)),
  KEY `key` (`key`),
  KEY `uid` (`uid`(100))
) ENGINE=InnoDB
SQL,
                    'differences'    => [
                        'type' => 'altered_table',
                        'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
   PRIMARY KEY (`id`),
   KEY `name` (`name`),
   KEY `key` (`key`),
-  KEY `uid` (`uid`)
+  KEY `uid` (`uid`(100))
 ) ENGINE=InnoDB

DIFF,
                    ],
                ],
            ],
            [
                'strict' => true,
                'allow_signed_keys' => false,
                'ignore_innodb_migration' => false,
                'ignore_timestamps_migration' => false,
                'ignore_utf8mb4_migration' => false,
                'ignore_dynamic_row_format_migration' => false,
                'ignore_unsigned_keys_migration' => false,
            ]
        );
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testGetNormalizedSql(
        string $schema, // ignored
        array $raw_tables,
        array $normalized_tables,
        array $effective_tables, // ignored
        array $expected_differences, // ignored
        array $args
    ) {
        $db = $this->geDbMock($args);

        $this->newTestedInstance(
            $db,
            $args['strict'],
            $args['ignore_innodb_migration'],
            $args['ignore_timestamps_migration'],
            $args['ignore_utf8mb4_migration'],
            $args['ignore_dynamic_row_format_migration'],
            $args['ignore_unsigned_keys_migration']
        );

        foreach ($raw_tables as $table_name => $raw_sql) {
            $this->string($this->callPrivateMethod($this->testedInstance, 'getNormalizedSql', $raw_sql))->isEqualTo($normalized_tables[$table_name]);
        }
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testTableDifferences(
        string $schema, // ignored
        array $raw_tables,
        array $normalized_tables, // ignored
        array $effective_tables,
        array $expected_differences,
        array $args
    ) {
        $db = $this->geDbMock($args);

        $this->newTestedInstance(
            $db,
            $args['strict'],
            $args['ignore_innodb_migration'],
            $args['ignore_timestamps_migration'],
            $args['ignore_utf8mb4_migration'],
            $args['ignore_dynamic_row_format_migration'],
            $args['ignore_unsigned_keys_migration']
        );

        foreach ($raw_tables as $table_name => $raw_sql) {
            $effective_sql = $effective_tables[$table_name];
            $expected_diff = $expected_differences[$table_name]['diff'] ?? '';

            $this->mockGenerator->orphanize('__construct');
            $query_result = new \mock\mysqli_result();
            $this->calling($query_result)->fetch_assoc = ['Create Table' => $effective_sql];
            $this->calling($db)->doQuery = $query_result;

            $this->boolean($this->testedInstance->hasDifferences($table_name, $raw_sql))->isEqualTo(!empty($expected_diff));
            $this->string($this->testedInstance->getDiff($table_name, $raw_sql))->isEqualTo($expected_diff);
        }
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testExtractSchemaFromFile(
        string $schema,
        array $raw_tables,
        array $normalized_tables, // ignored
        array $effective_tables, // ignored
        array $expected_differences, // ignored
        array $args // ignored
    ) {
        vfsStream::setup(
            'glpi',
            null,
            [
                'install' => [
                    'schema.sql' => $schema,
                ],
            ]
        );

        $db = $this->geDbMock($args);

        $this->newTestedInstance($db);
        $this->array($this->testedInstance->extractSchemaFromFile(vfsStream::url('glpi/install/schema.sql')))
            ->isEqualTo($raw_tables);
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testCheckCompleteSchema(
        string $schema,
        array $raw_tables, // ignored
        array $normalized_tables, // ignored
        array $effective_tables,
        array $expected_differences,
        array $args
    ) {
        vfsStream::setup(
            'glpi',
            null,
            [
                'install' => [
                    'schema.sql' => $schema,
                ],
            ]
        );

        $db = $this->geDbMock($args);

        $that = $this;
        $this->calling($db)->doQuery = function ($query) use ($effective_tables, $that) {
            $table_name = preg_replace('/SHOW CREATE TABLE `([^`]+)`/', '$1', $query);
            if (array_key_exists($table_name, $effective_tables)) {
                $that->mockGenerator->orphanize('__construct');
                $res = new \mock\mysqli_result();
                $that->calling($res)->fetch_assoc = ['Create Table' => $effective_tables[$table_name]];
                return $res;
            }
            return false;
        };

        $this->newTestedInstance(
            $db,
            $args['strict'],
            $args['ignore_innodb_migration'],
            $args['ignore_timestamps_migration'],
            $args['ignore_utf8mb4_migration'],
            $args['ignore_dynamic_row_format_migration'],
            $args['ignore_unsigned_keys_migration']
        );

        $expected_differences = array_filter($expected_differences); // Do not keep entries from data provider having "null" differences

        $this->array($this->testedInstance->checkCompleteSchema(vfsStream::url('glpi/install/schema.sql')))
            ->isEqualTo($expected_differences);
    }

    public function testCheckCompleteSchemaWithUnknownAndMissingTables()
    {
        $contexts = [
            'core'       => '',
            'plugin:foo' => 'plugin_foo_',
        ];

        foreach ($contexts as $context => $table_prefix) {
            $existingtable_sql = <<<SQL
CREATE TABLE `glpi_{$table_prefix}existingtable` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(100) NOT NULL,
  `items_id` int unsigned NOT NULL DEFAULT '0',
  `type` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`,`items_id`,`type`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL;
            $missingtable_sql = <<<SQL
CREATE TABLE `glpi_{$table_prefix}missingtable` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL;
            $unknowntable_sql = <<<SQL
CREATE TABLE `glpi_{$table_prefix}unknowntable` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL;

            vfsStream::setup(
                'glpi',
                null,
                [
                    'install' => [
                        'schema.sql' => <<<SQL
--
-- Presence of multiline comments
-- should not be an issue.
--

DROP TABLE IF EXISTS `glpi_somethings`;

{$existingtable_sql}

{$missingtable_sql}
SQL,
                    ],
                ]
            );

            $db = $this->geDbMock(['use_utf8mb4' => true]);

            $this->calling($db)->tableExists = function ($table_name) use ($table_prefix) {
                return $table_name !== "glpi_{$table_prefix}missingtable";
            };
            $that = $this;
            $this->calling($db)->listTables = [['TABLE_NAME' => "glpi_{$table_prefix}unknowntable"]]; // $DB->listTables() is used to list unknown tables
            $this->calling($db)->doQuery = function ($query) use ($that, $table_prefix, $existingtable_sql, $unknowntable_sql) {
                $table_name = preg_replace('/SHOW CREATE TABLE `([^`]+)`/', '$1', $query);
                $result = null;
                switch ($table_name) {
                    case "glpi_{$table_prefix}existingtable":
                        $result = ['Create Table' => $existingtable_sql];
                        break;
                    case "glpi_{$table_prefix}unknowntable":
                        $result = ['Create Table' => $unknowntable_sql];
                        break;
                }
                if ($result !== null) {
                    $that->mockGenerator->orphanize('__construct');
                    $res = new \mock\mysqli_result();
                    $that->calling($res)->fetch_assoc = $result;
                    return $res;
                }
                return false;
            };

            $this->newTestedInstance($db);

            $expected = [
                "glpi_{$table_prefix}missingtable" => [
                    'type' => 'missing_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
-CREATE TABLE `glpi_{$table_prefix}missingtable` (
-  `id` int unsigned NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL,
-  `description` text,
-  PRIMARY KEY (`id`)
-) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB ROW_FORMAT=DYNAMIC

DIFF,
                ],
                "glpi_{$table_prefix}unknowntable" => [
                    'type' => 'unknown_table',
                    'diff' => <<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
+CREATE TABLE `glpi_{$table_prefix}unknowntable` (
+  `id` int unsigned NOT NULL AUTO_INCREMENT,
+  `name` varchar(255) NOT NULL,
+  `description` text,
+  PRIMARY KEY (`id`)
+) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB ROW_FORMAT=DYNAMIC

DIFF,
                ],
            ];

            $this->array($this->testedInstance->checkCompleteSchema(vfsStream::url('glpi/install/schema.sql'), true, $context))
                ->isEqualTo($expected);

            // Check without unknown tables detection
            unset($expected["glpi_{$table_prefix}unknowntable"]);
            $this->array($this->testedInstance->checkCompleteSchema(vfsStream::url('glpi/install/schema.sql'), false))
                ->isEqualTo($expected);
        }
    }

    public function testImpactContextsPositionsDefaultValueHack()
    {
        // Schema contained in GLPI 9.5.0 database installation file
        $schema_sql_from_950 = <<<SQL
CREATE TABLE `glpi_impactcontexts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `positions` TEXT NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
  `zoom` FLOAT NOT NULL DEFAULT '0',
  `pan_x` FLOAT NOT NULL DEFAULT '0',
  `pan_y` FLOAT NOT NULL DEFAULT '0',
  `impact_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
  `depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
  `impact_and_depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
  `show_depends` TINYINT(1) NOT NULL DEFAULT '1',
  `show_impact` TINYINT(1) NOT NULL DEFAULT '1',
  `max_depth` INT(11) NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        // Schema contained in GLPI 10.0.1 database installation file
        $schema_sql_from_1001 = <<<SQL
CREATE TABLE `glpi_impactcontexts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `positions` mediumtext NOT NULL,
  `zoom` float NOT NULL DEFAULT '0',
  `pan_x` float NOT NULL DEFAULT '0',
  `pan_y` float NOT NULL DEFAULT '0',
  `impact_color` varchar(255) NOT NULL DEFAULT '',
  `depends_color` varchar(255) NOT NULL DEFAULT '',
  `impact_and_depends_color` varchar(255) NOT NULL DEFAULT '',
  `show_depends` tinyint NOT NULL DEFAULT '1',
  `show_impact` tinyint NOT NULL DEFAULT '1',
  `max_depth` int NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL;

        $db = $this->geDbMock();
        $that = $this;

        // Case 1: "DEFAULT ''" not returned by MySQL should not be detected as a difference for GLPI < 10.0.1
        $this->calling($db)->doQuery = function ($query) use ($that) {
            if (preg_match('/^SHOW CREATE TABLE/', $query) === 1) {
                $that->mockGenerator->orphanize('__construct');
                $res = new \mock\mysqli_result();
                $that->calling($res)->fetch_assoc = [
                    'Create Table' => <<<SQL
CREATE TABLE `glpi_impactcontexts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `positions` text CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL,
  `zoom` float NOT NULL DEFAULT '0',
  `pan_x` float NOT NULL DEFAULT '0',
  `pan_y` float NOT NULL DEFAULT '0',
  `impact_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `depends_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `impact_and_depends_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `show_depends` tinyint(1) NOT NULL DEFAULT '1',
  `show_impact` tinyint(1) NOT NULL DEFAULT '1',
  `max_depth` int NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;
SQL
                ];
                return $res;
            }
            return false;
        };

        $dbversions = [
            '9.5.0',
            '9.5.1',
            '9.5.2',
            '9.5.3',
            '9.5.4',
            '9.5.5',
            '9.5.6',
            '9.5.7',
            '9.5.8',
            '9.5.9',
            '9.5.10',
            '9.5.11',
            '10.0.0-beta1',
            '10.0.0-rc1',
            '10.0.0-rc2',
            '10.0.0-rc3',
            '10.0.0',
        ];
        foreach ($dbversions as $dbversion) {
            $this->newTestedInstance($db);
            $this->calling($db)->request = function ($query) use ($dbversion) {
                return new \ArrayIterator(
                    [
                        [
                            'context' => 'core',
                            'name'    => 'dbversion',
                            'value'   => $dbversion,
                        ]
                    ]
                );
            };
            $this->string($this->testedInstance->getDiff('glpi_impactcontexts', $schema_sql_from_950))->isEqualTo(null);
        }

        // Case 2: "DEFAULT ''" returned by MariaDB should be detected as a difference for GLPI >= 10.0.1
        $db->use_utf8mb4 = true;
        $this->calling($db)->doQuery = function ($query) use ($that) {
            if (preg_match('/^SHOW CREATE TABLE/', $query) === 1) {
                $that->mockGenerator->orphanize('__construct');
                $res = new \mock\mysqli_result();
                $that->calling($res)->fetch_assoc = [
                    'Create Table' => <<<SQL
CREATE TABLE `glpi_impactcontexts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `positions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zoom` float NOT NULL DEFAULT '0',
  `pan_x` float NOT NULL DEFAULT '0',
  `pan_y` float NOT NULL DEFAULT '0',
  `impact_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `depends_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `impact_and_depends_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `show_depends` tinyint(1) NOT NULL DEFAULT '1',
  `show_impact` tinyint(1) NOT NULL DEFAULT '1',
  `max_depth` int NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL
                ];
                return $res;
            }
            return false;
        };

        $dbversions = [
            '10.0.1',
            '10.0.2',
            '10.0.3',
            '10.1.0-dev',
            '10.1.0',
            '11.0.0-beta1',
            '11.0.0-rc2',
            '11.0.0',
        ];
        foreach ($dbversions as $dbversion) {
            $this->newTestedInstance($db);
            $this->calling($db)->request = function ($query) use ($dbversion) {
                return new \ArrayIterator(
                    [
                        [
                            'context' => 'core',
                            'name'    => 'dbversion',
                            'value'   => $dbversion,
                        ]
                    ]
                );
            };
            $this->string($this->testedInstance->getDiff('glpi_impactcontexts', $schema_sql_from_1001))->isEqualTo(<<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `glpi_impactcontexts` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
-  `positions` text NOT NULL,
+  `positions` text NOT NULL DEFAULT '',
   `zoom` float NOT NULL DEFAULT 0,
   `pan_x` float NOT NULL DEFAULT 0,
   `pan_y` float NOT NULL DEFAULT 0,

DIFF
            );
        }
    }

    public function testNotImportedEmailsCollateCheck()
    {
        $db = $this->geDbMock();

        // Case 1: COLLATE is ignored if DB version is < 10.0.0
        $schema_sql_from_955 = <<<SQL
CREATE TABLE `glpi_notimportedemails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `mailcollectors_id` int(11) NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text,
  `messageid` varchar(255) NOT NULL,
  `reason` int(11) NOT NULL DEFAULT '0',
  `users_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

        $that = $this;
        $this->calling($db)->doQuery = function ($query) use ($that) {
            if (preg_match('/^SHOW CREATE TABLE/', $query) === 1) {
                $that->mockGenerator->orphanize('__construct');
                $res = new \mock\mysqli_result();
                // Expected result for GLPI < 10.0.0
                $that->calling($res)->fetch_assoc = [
                    'Create Table' => <<<SQL
CREATE TABLE `glpi_notimportedemails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `to` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `mailcollectors_id` int NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `messageid` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `reason` int NOT NULL DEFAULT '0',
  `users_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
SQL
                ];
                return $res;
            }
            return false;
        };

        $dbversions = [
            '9.3.0',
            '9.5.0',
            '9.5.1',
            '9.5.2',
            '9.5.3',
            '9.5.4',
            '9.5.5',
            '9.5.6',
            '9.5.7',
            '9.5.8',
            '9.5.9',
            '9.5.10',
            '9.5.11',
        ];
        foreach ($dbversions as $dbversion) {
            $this->newTestedInstance($db);
            $this->calling($db)->request = function ($query) use ($dbversion) {
                return new \ArrayIterator(
                    [
                        [
                            'context' => 'core',
                            'name'    => 'dbversion',
                            'value'   => $dbversion,
                        ]
                    ]
                );
            };
            $this->string($this->testedInstance->getDiff('glpi_notimportedemails', $schema_sql_from_955))->isEqualTo(null);
        }

        // Case 2: COLLATE is NOT ignored if DB version is >= 10.0.0
        $schema_sql_from_1000_ok = <<<SQL
CREATE TABLE `glpi_notimportedemails` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `mailcollectors_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text,
  `messageid` varchar(255) NOT NULL,
  `reason` int NOT NULL DEFAULT '0',
  `users_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL;
        $schema_sql_from_1000_ko = <<<SQL
CREATE TABLE `glpi_notimportedemails` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `to` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `mailcollectors_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  `messageid` varchar(255) NOT NULL COLLATE latin1_general_ci,
  `reason` int NOT NULL DEFAULT '0',
  `users_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ROW_FORMAT=DYNAMIC;
SQL;

        $that = $this;
        $this->calling($db)->doQuery = function ($query) use ($that) {
            if (preg_match('/^SHOW CREATE TABLE/', $query) === 1) {
                $that->mockGenerator->orphanize('__construct');
                $res = new \mock\mysqli_result();
                // Expected result for GLPI >= 10.0.0
                $that->calling($res)->fetch_assoc = [
                    'Create Table' => <<<SQL
CREATE TABLE `glpi_notimportedemails` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mailcollectors_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `messageid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` int NOT NULL DEFAULT '0',
  `users_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `mailcollectors_id` (`mailcollectors_id`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL
                ];
                return $res;
            }
            return false;
        };

        $dbversions = [
            '10.0.0-beta1',
            '10.0.0-rc1',
            '10.0.0-rc2',
            '10.0.0-rc3',
            '10.0.0',
            '10.0.1',
            '10.0.2',
            '10.0.3',
            '10.1.0-dev',
            '10.1.0',
            '11.0.0-beta1',
            '11.0.0-rc2',
            '11.0.0',
        ];
        $db->use_utf8mb4 = true;
        foreach ($dbversions as $dbversion) {
            $this->newTestedInstance($db);
            $this->calling($db)->request = function ($query) use ($dbversion) {
                return new \ArrayIterator(
                    [
                        [
                            'context' => 'core',
                            'name'    => 'dbversion',
                            'value'   => $dbversion,
                        ]
                    ]
                );
            };
            $this->string($this->testedInstance->getDiff('glpi_notimportedemails', $schema_sql_from_1000_ok))->isEqualTo(null);
            $this->string($this->testedInstance->getDiff('glpi_notimportedemails', $schema_sql_from_1000_ko))->isEqualTo(<<<DIFF
--- Expected database schema
+++ Current database schema
@@ @@
 CREATE TABLE `glpi_notimportedemails` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
-  `from` varchar(255) COLLATE latin1_general_ci NOT NULL,
-  `to` varchar(255) COLLATE latin1_general_ci NOT NULL,
+  `from` varchar(255) NOT NULL,
+  `to` varchar(255) NOT NULL,
   `mailcollectors_id` int unsigned NOT NULL DEFAULT 0,
   `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
-  `subject` text CHARACTER SET latin1 COLLATE latin1_general_ci,
-  `messageid` varchar(255) NOT NULL COLLATE latin1_general_ci,
+  `subject` text,
+  `messageid` varchar(255) NOT NULL,
   `reason` int NOT NULL DEFAULT 0,
   `users_id` int unsigned NOT NULL DEFAULT 0,
   PRIMARY KEY (`id`),
   KEY `users_id` (`users_id`),
   KEY `mailcollectors_id` (`mailcollectors_id`)
-) COLLATE=latin1_general_ci DEFAULT CHARSET=latin1 ENGINE=InnoDB ROW_FORMAT=DYNAMIC
+) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB ROW_FORMAT=DYNAMIC

DIFF);
        }
    }

    protected function versionProvider(): iterable
    {
        $root = realpath(GLPI_ROOT);

        // Current version -> can check
        yield [
            'version'              => GLPI_SCHEMA_VERSION,
            'context'              => 'core',
            'expected_can_check'   => true,
            'expected_schema_path' => sprintf(
                '%s/install/mysql/glpi-empty.sql',
                $root
            ),
        ];

        // Stable version with hash -> can check
        yield [
            'version'              => '10.0.2@1530dc89c7d9b131c2e2ea59fe0d6260fe93d831',
            'context'              => 'core',
            'expected_can_check'   => true,
            'expected_schema_path' => sprintf('%s/install/mysql/glpi-10.0.2-empty.sql', $root),
        ];

        // Stable version WITHOUT hash -> can check
        yield [
            'version'              => '10.0.2',
            'context'              => 'core',
            'expected_can_check'   => true,
            'expected_schema_path' => sprintf('%s/install/mysql/glpi-10.0.2-empty.sql', $root),
        ];

        // Unstable version with hash -> cannot check
        yield [
            'version'              => '10.0.2-dev@b130dc89c7d9b131c2e2ea59fe0d6260fe93d831',
            'context'              => 'core',
            'expected_can_check'   => false,
            'expected_schema_path' => sprintf('%s/install/mysql/glpi-10.0.2-empty.sql', $root),
        ];

        // Unstable version WITHOUT hash -> cannot check
        yield [
            'version'              => '10.0.2-dev',
            'context'              => 'core',
            'expected_can_check'   => false,
            'expected_schema_path' => sprintf('%s/install/mysql/glpi-10.0.2-empty.sql', $root),
        ];

        // Old unsupported version -> cannot check
        yield [
            'version'              => '0.0.1',
            'context'              => 'core',
            'expected_can_check'   => false,
            'expected_schema_path' => null,
        ];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testGetSchemaPath(
        ?string $version,
        string $context,
        bool $expected_can_check,
        ?string $expected_schema_path
    ) {

        $db = $this->geDbMock();
        $this->newTestedInstance($db);

        $this->variable($this->callPrivateMethod($this->testedInstance, 'getSchemaPath', $version, $context))->isEqualTo($expected_schema_path);
    }

    /**
     * @dataProvider versionProvider
     */
    public function testCanCheckIntegrity(
        ?string $version,
        string $context,
        bool $expected_can_check,
        ?string $expected_schema_path
    ) {

        $db = $this->geDbMock();
        $this->newTestedInstance($db);

        $this->variable($this->testedInstance->canCheckIntegrity($version, $context))->isEqualTo($expected_can_check, $version);
    }

    /**
     * Return DB mock that implements minimal required behaviour.
     *
     * @return \DBmysql
     */
    private function geDbMock(array $options = []): \DBmysql
    {
        $this->mockGenerator->orphanize('__construct');
        $db = new \mock\DBmysql();
        $this->calling($db)->tableExists = true;
        $this->calling($db)->fieldExists = true;
        $this->calling($db)->request = new \ArrayIterator();

        foreach ($options as $property => $value) {
            if (property_exists($db, $property)) {
                $db->{$property} = $value;
            }
        }

        return $db;
    }
}
