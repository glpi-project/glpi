<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\System\Diagnostic;

class DatabaseChecker extends \GLPITestCase {

   protected function sqlProvider() {
      return [
         // AUTO_INCREMENT, integer display width, and comments should not be included in differences.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_valid` tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB COMMENT='some comment with an escaped \' backquote'
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'name of the object',
  `is_valid` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15
SQL
            ,
            'version_string' => '5.6.50-log',
            'args'           => [
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],

         // Non strict check does not take care of columns/indexes order and ROW_FORMAT.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_valid` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `is_valid` (`is_valid`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_valid` tinyint NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB
SQL
            ,
            'version_string' => '5.7.34-standard',
            'args'           => [
               'strict' => false,
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],

         // Strict check takes care of columns/indexes order and ROW_FORMAT.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_valid` tinyint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `is_valid` (`is_valid`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_valid` tinyint NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_valid` (`is_valid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
SQL
            ,
            'version_string' => '5.7.34-standard',
            'args'           => [
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
 CREATE TABLE `table` (
   `id` int NOT NULL AUTO_INCREMENT,
+  `is_valid` tinyint NOT NULL,
   `name` varchar(255) NOT NULL,
-  `is_valid` tinyint NOT NULL,
   PRIMARY KEY (`id`),
-  UNIQUE KEY `name` (`name`),
-  KEY `is_valid` (`is_valid`)
-) ENGINE=InnoDB
+  KEY `is_valid` (`is_valid`),
+  UNIQUE KEY `name` (`name`)
+) ENGINE=InnoDB ROW_FORMAT=DYNAMIC

DIFF
            ,
         ],

         // DB using utf8mb4:
         // - should accept missing default charset/collate on columns if matching utf8mb4;
         // - should not accept non utf8mb4 charset;
         // - should accept 'mediumtext' instead of 'text'.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  `bis` varchar(100),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bis` varchar(100) CHARSET latin1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            'version_string' => '5.7.34-standard',
            'args'           => [
               'use_utf8mb4' => true,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
 CREATE TABLE `table` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL,
+  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
   `content` text,
-  `bis` varchar(100),
+  `bis` varchar(100) CHARSET latin1,
   PRIMARY KEY (`id`)
 ) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB

DIFF
            ,
         ],

         // DB using utf8:
         // - should accept missing default charset/collate on columns if matching utf8;
         // - should not accept non utf8 charset;
         // - should not accept 'mediumtext' instead of 'text'.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL
            ,
            'version_string' => '5.7.34-standard',
            'args'           => [
               'use_utf8mb4' => false,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
 CREATE TABLE `table` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `name` varchar(255) NOT NULL,
-  `content` text,
+  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
+  `content` mediumtext,
   PRIMARY KEY (`id`)
 ) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB

DIFF
            ,
         ],

         // Charset/collation difference should be ignored if related to utf8mb4 migration
         // when using ignore_utf8mb4_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL
            ,
            'version_string' => '10.1.48-MariaDB',
            'args'           => [
               'use_utf8mb4' => false,
               'ignore_utf8mb4_migration' => true,
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],

         // Charset/collation difference should NOT be ignored if related to utf8mb4 migration
         // when NOT using ignore_utf8mb4_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL
            ,
            'version_string' => '10.1.48-MariaDB',
            'args'           => [
               'use_utf8mb4' => false,
               'ignore_utf8mb4_migration' => false,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
 CREATE TABLE `table` (
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
-  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
+  `content` text,
   PRIMARY KEY (`id`)
-) COLLATE=utf8mb4_unicode_ci DEFAULT CHARSET=utf8mb4 ENGINE=InnoDB
+) COLLATE=utf8_unicode_ci DEFAULT CHARSET=utf8 ENGINE=InnoDB

DIFF
            ,
         ],

         // Charset/collation difference should not be ignored if not related to utf8mb4 migration
         // when using ignore_utf8mb4_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `content` text CHARACTER SET latin1 COLLATE latin1_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL
            ,
            'version_string' => '10.1.48-MariaDB',
            'args'           => [
               'use_utf8mb4' => false,
               'ignore_utf8mb4_migration' => true,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
 CREATE TABLE `table` (
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
-  `content` text,
+  `content` text CHARACTER SET latin1 COLLATE latin1_general_ci,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB

DIFF
            ,
         ],

         // datetime/timestamp difference should be ignored when using ignore_timestamps_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'version_string' => '10.1.48-MariaDB',
            'args'           => [
               'ignore_timestamps_migration' => true,
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],

         // datetime/timestamp difference should NOT be ignored when NOT using ignore_timestamps_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'version_string' => '10.1.48-MariaDB',
            'args'           => [
               'ignore_timestamps_migration' => false,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
 CREATE TABLE `table` (
   `id` int NOT NULL AUTO_INCREMENT,
-  `created_at` timestamp NOT NULL,
-  `updated_at` timestamp NULL,
+  `created_at` datetime NOT NULL,
+  `updated_at` datetime,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB

DIFF
            ,
         ],

         // ROW_FORMAT difference should be ignored when using ignore_dynamic_row_format_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=COMPACT
SQL
            ,
            'version_string' => '10.2.36-MariaDB',
            'args'           => [
               'ignore_dynamic_row_format_migration' => true,
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],

         // ROW_FORMAT difference should NOT be ignored when NOT using ignore_dynamic_row_format_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ROW_FORMAT=COMPACT
SQL
            ,
            'version_string' => '10.2.36-MariaDB',
            'args'           => [
               'ignore_dynamic_row_format_migration' => false,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
-) ENGINE=InnoDB
+) ENGINE=InnoDB ROW_FORMAT=COMPACT

DIFF
         ],

         // ENGINE difference should be ignored when using ignore_innodb_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL
            ,
            'version_string' => '10.2.36-MariaDB',
            'args'           => [
               'ignore_innodb_migration' => true,
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],

         // ENGINE difference should NOT be ignored when NOT using ignore_innodb_migration flag.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL
            ,
            'version_string' => '10.2.36-MariaDB',
            'args'           => [
               'ignore_innodb_migration' => false,
            ],
            'expected_has'   => true,
            'expected_diff'  => <<<DIFF
--- Original
+++ New
@@ @@
   `id` int NOT NULL AUTO_INCREMENT,
   `name` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
-) ENGINE=InnoDB
+) ENGINE=MyISAM

DIFF
         ],

         // DB on MariaDB 10.2+ resuls should be normalized by:
         // - surrounding default numeric values by quotes;
         // - replacing current_timestamp() by CURRENT_TIMESTAMP;
         // - removing DEFAULT NULL on text fields.
         [
            'proper_sql'     => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NULL,
  `value` int NOT NULL DEFAULT '0',
  `steps` float NOT NULL DEFAULT '-0.7',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
            ,
            'effective_sql'  => <<<SQL
CREATE TABLE `table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NULL DEFAULT NULL,
  `value` int NOT NULL DEFAULT 0,
  `steps` float NOT NULL DEFAULT -0.7,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
SQL
            ,
            'version_string' => '10.2.36-MariaDB',
            'args'           => [
               'ignore_innodb_migration' => true,
            ],
            'expected_has'   => false,
            'expected_diff'  => '',
         ],
      ];
   }

   /**
    * @dataProvider sqlProvider
    */
   public function testDifferences(
      string $proper_sql,
      string $effective_sql,
      string $version_string,
      array $args,
      bool $expected_has,
      string $expected_diff
   ) {

      $this->mockGenerator->orphanize('__construct');

      $db = new \mock\DBmysql();
      $db->use_utf8mb4 = $args['use_utf8mb4'] ?? true;
      $this->calling($db)->getVersion = $version_string;

      $this->mockGenerator->orphanize('__construct');
      $query_result = new \mock\mysqli_result();
      $this->calling($query_result)->fetch_assoc = ['Create Table' => $effective_sql];
      $this->calling($db)->query = $query_result;

      $this->newTestedInstance(
         $db,
         $args['strict'] ?? true,
         $args['ignore_innodb_migration'] ?? false,
         $args['ignore_timestamps_migration'] ?? false,
         $args['ignore_utf8mb4_migration'] ?? false,
         $args['ignore_dynamic_row_format_migration'] ?? false
      );

      $this->boolean($this->testedInstance->hasDifferences('table', $proper_sql))->isEqualTo($expected_has);
      $this->string($this->testedInstance->getDiff('table', $proper_sql))->isEqualTo($expected_diff);
   }
}
