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

namespace Glpi\System\Diagnostic;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use DBmysql;
use SebastianBergmann\Diff\Differ;

/**
 * @since 10.0.0
 */
class DatabaseSchemaIntegrityChecker {

   /**
    * DB instance.
    *
    * @var DBmysql
    */
   protected $db;

   /**
    * Do not check tokens related to "DYNAMIC" row format migration.
    *
    * @var bool
    */
   protected $ignore_dynamic_row_format_migration;

   /**
    * Do not check tokens related to migration from "MyISAM" to "InnoDB".
    *
    * @var bool
    */
   protected $ignore_innodb_migration;

   /**
    * Do not check tokens related to migration from "datetime" to "timestamp".
    *
    * @var bool
    */
   protected $ignore_timestamps_migration;

   /**
    * Do not check tokens related to migration from "utf8" to "utf8mb4".
    *
    * @var bool
    */
   protected $ignore_utf8mb4_migration;

   /**
    * Ignore differences that has no effect on application (columns and keys order for instance).
    *
    * @var bool
    */
   protected $strict;

   /**
    * Local cache for normalized SQL.
    *
    * @var array
    */
   private $normalized = [];

   /**
    * @param DBmysql $db                                 DB instance.
    * @param bool $strict                                Ignore differences that has no effect on application (columns and keys order for instance).
    * @param bool $ignore_innodb_migration               Do not check tokens related to migration from "MyISAM" to "InnoDB".
    * @param bool $ignore_timestamps_migration           Do not check tokens related to migration from "datetime" to "timestamp".
    * @param bool $ignore_utf8mb4_migration              Do not check tokens related to migration from "utf8" to "utf8mb4".
    * @param bool $ignore_dynamic_row_format_migration   Do not check tokens related to "DYNAMIC" row format migration.
    */
   public function __construct(
      DBmysql $db,
      bool $strict = true,
      bool $ignore_innodb_migration = false,
      bool $ignore_timestamps_migration = false,
      bool $ignore_utf8mb4_migration = false,
      bool $ignore_dynamic_row_format_migration = false
   ) {
      $this->db = $db;
      $this->strict = $strict;
      $this->ignore_dynamic_row_format_migration = $ignore_dynamic_row_format_migration;
      $this->ignore_innodb_migration = $ignore_innodb_migration;
      $this->ignore_timestamps_migration = $ignore_timestamps_migration;
      $this->ignore_utf8mb4_migration = $ignore_utf8mb4_migration;
   }

   /**
    * Check is there is differences between effective table structure and proper structure contained in "CREATE TABLE" sql query.
    *
    * @param string $table_name
    * @param string $proper_create_table_sql
    *
    * @return bool
    */
   public function hasDifferences(string $table_name, string $proper_create_table_sql): bool {
      $effective_create_table_sql = $this->getEffectiveCreateTableSql($table_name);

      $proper_create_table_sql    = $this->getNomalizedSql($proper_create_table_sql);
      $effective_create_table_sql = $this->getNomalizedSql($effective_create_table_sql);

      return $proper_create_table_sql !== $effective_create_table_sql;
   }

   /**
    * Get diff between effective table structure and proper structure contained in "CREATE TABLE" sql query.
    *
    * @param string $table
    * @param string $proper_create_table_sql
    *
    * @return string
    */
   public function getDiff(string $table_name, string $proper_create_table_sql): string {
      $effective_create_table_sql = $this->getEffectiveCreateTableSql($table_name);

      $proper_create_table_sql    = $this->getNomalizedSql($proper_create_table_sql);
      $effective_create_table_sql = $this->getNomalizedSql($effective_create_table_sql);

      if ($proper_create_table_sql === $effective_create_table_sql) {
         return '';
      }

      $differ = new Differ();
      return $differ->diff(
         $proper_create_table_sql,
         $effective_create_table_sql
      );
   }

   /**
    * Returns "CREATE TABLE" SQL returned by DB itself.
    *
    * @param string $table_name
    *
    * @return string
    */
   protected function getEffectiveCreateTableSql(string $table_name): string {
      if (($create_table_res = $this->db->query('SHOW CREATE TABLE ' . $this->db->quoteName($table_name))) === false) {
         if ($this->db->errno() == 1146) {
            return ''; // Table does not exists, effective create table is empty (will output full proper query as diff).
         }
         throw new \Exception(sprintf('Unable to get table "%s" structure', $table_name));
      }
      return $create_table_res->fetch_assoc()['Create Table'];
   }

   /**
    * Returns normalized SQL.
    * Normalization replace/remove tokens that can reveal differences we do not want to see.
    *
    * @param string $create_table_sql
    *
    * @return string
    */
   protected function getNomalizedSql(string $create_table_sql): string {
      $cache_key = md5($create_table_sql);
      if (array_key_exists($cache_key, $this->normalized)) {
         return $this->normalized[$cache_key];
      }

      // Extract informations
      $matches = [];
      if (!preg_match('/^CREATE TABLE `(?<table>\w+)` \((?<structure>.+)\)(?<properties>[^\)]*)$/is', $create_table_sql, $matches)) {
         return $create_table_sql;// Unable to normalize
      }

      $table_name     = $matches['table'];
      $structure_sql  = $matches['structure'];
      $properties_sql = $matches['properties'];

      $columns_matches = [];
      $indexes_matches = [];
      $properties_matches = [];
      if (!preg_match_all('/^\s*(?<column>`\w+` .+?),?$/im', $structure_sql, $columns_matches)
          || !preg_match_all('/^\s*(?<index>(CONSTRAINT|(UNIQUE |PRIMARY |FULLTEXT )?KEY) .+?),?$/im', $structure_sql, $indexes_matches)
          || !preg_match_all('/\s+((?<property>[^=]+[^\s])\s*=\s*(?<value>(\w+|\'(\\.|[^"])+\')))/i', $properties_sql, $properties_matches)) {
         return $create_table_sql;// Unable to normalize
      }
      $columns = $columns_matches['column'];
      $indexes = $indexes_matches['index'];
      $properties = array_combine($properties_matches['property'], $properties_matches['value']);

      $db_version_string = $this->db->getVersion();
      $db_server  = preg_match('/-MariaDB/', $db_version_string) ? 'MariaDB': 'MySQL';
      $db_version = preg_replace('/^((\d+\.?)+).*$/', '$1', $db_version_string);

      // Normalize columns definitions
      if (!$this->strict) {
         sort($columns);
      }
      $column_replacements = [
         // Remove comments
         '/ COMMENT \'.+\'/i' => '',
         // Remove integer display width
         '/( (tiny|small|medium|big)?int)\(\d+\)/i' => '$1',
      ];
      if ($db_server === 'MariaDB' && version_compare($db_version, '10.2', '>=')) {
         // Add surrounding quotes on default numeric values (MySQL has quotes while MariaDB 10.2+ has not)
         $column_replacements['/(DEFAULT) ([-|+]?\d+(\.\d+)?)/i'] = '$1 \'$2\'';
         // Replace function current_timestamp() by CURRENT_TIMESTAMP (MySQL uses constant while MariaDB 10.2+ uses function)
         $column_replacements['/current_timestamp\(\)/i'] = 'CURRENT_TIMESTAMP';
         // Remove DEFAULT NULL on text fields (MySQL has not while MariaDB 10.2+ has)
         $column_replacements['/( (medium|long)?(blob|text)[^,]*) DEFAULT NULL/i'] = '$1';
      }
      if ($this->ignore_timestamps_migration) {
         $column_replacements['/ timestamp( NULL)?/i'] = ' datetime';
      }
      // Normalize utf8mb3 to utf8
      $column_replacements['/utf8mb3/i'] = 'utf8';
      if ($this->ignore_utf8mb4_migration) {
         $column_replacements['/( CHARACTER SET (utf8|utf8mb4))? COLLATE (utf8_unicode_ci|utf8mb4_unicode_ci)/i'] = '';
      }
      if ($this->db->use_utf8mb4) {
         // Remove default charset / collate
         $column_replacements['/( CHARACTER SET utf8mb4)? COLLATE utf8mb4_unicode_ci/i'] = '';
         // "text" fields were replaced by larger type during utf8mb4 migration.
         // As is it not really possible to know if checked database has been modified by utf8mb4 migration
         // or not, we normalize "mediumtext" and "longtext" fields to "text".
         $column_replacements['/(medium|long)text/i'] = 'text';
      } else {
         // Remove default charset / collate
         $column_replacements['/( CHARACTER SET utf8)? COLLATE utf8_unicode_ci/i'] = '';
      }
      $columns = preg_replace(array_keys($column_replacements), array_values($column_replacements), $columns);

      // Normalize indexes definitions
      if (!$this->strict) {
         sort($indexes);
      }

      // Normalize properties
      unset($properties['AUTO_INCREMENT']);
      unset($properties['COMMENT']);
      if ($this->ignore_dynamic_row_format_migration) {
         unset($properties['ROW_FORMAT']);
      }
      if (!$this->strict && ($properties['ROW_FORMAT'] ?? '') === 'DYNAMIC'
         && (
            ($db_server === 'MySQL' && version_compare($db_version, '5.7', '>='))
            || ($db_server === 'MariaDB' && version_compare($db_version, '10.2', '>='))
         )) {
         // MySQL 5.7+ and MariaDB 10.2+ does not ouput ROW_FORMAT when ROW_FORMAT has not been specified in creation query
         // and so uses default value.
         unset($properties['ROW_FORMAT']);
      }
      if ($this->ignore_innodb_migration) {
         unset($properties['ENGINE']);
      }
      // Normalize utf8mb3 to utf8
      if (array_key_exists('DEFAULT CHARSET', $properties)) {
         $properties['DEFAULT CHARSET'] = str_replace('utf8mb3', 'utf8', $properties['DEFAULT CHARSET']);
      }
      if (array_key_exists('COLLATE', $properties)) {
         $properties['COLLATE'] = str_replace('utf8mb3', 'utf8', $properties['COLLATE']);
      }
      if ($this->ignore_utf8mb4_migration) {
         // Remove non specific character set / collate
         if (in_array($properties['DEFAULT CHARSET'] ?? '', ['utf8', 'utf8mb4'])) {
            unset($properties['DEFAULT CHARSET']);
         }
         if (in_array($properties['COLLATE'] ?? '', ['utf8_unicode_ci', 'utf8mb4_unicode_ci'])) {
            unset($properties['COLLATE']);
         }
      }
      ksort($properties);

      // Rebuild SQL
      $normalized_sql = "CREATE TABLE `{$table_name}` (\n";
      $definitions = array_merge($columns, $indexes);
      foreach ($definitions as $i => $definition) {
         $normalized_sql .= '  ' . $definition . ($i < count($definitions) -1 ? ',' : '') . "\n";
      }
      $normalized_sql .= ')';
      foreach ($properties as $key => $value) {
         $normalized_sql .= ' ' . trim($key) . '=' . trim($value);
      }

      // Store in local cache
      $this->normalized[$cache_key] = $normalized_sql;

      return $normalized_sql;
   }
}
