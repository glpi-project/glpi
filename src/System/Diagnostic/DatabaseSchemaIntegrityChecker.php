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

namespace Glpi\System\Diagnostic;

use DBmysql;
use RuntimeException;
use SebastianBergmann\Diff\Differ;

/**
 * @since 10.0.0
 */
class DatabaseSchemaIntegrityChecker
{
    /**
     * Result type used when table is altered.
     * @var string
     */
    public const RESULT_TYPE_ALTERED_TABLE = 'altered_table';

    /**
     * Result type used when table is missing.
     * @var string
     */
    public const RESULT_TYPE_MISSING_TABLE = 'missing_table';

    /**
     * Result type used when an unknown table is found in database.
     * @var string
     */
    public const RESULT_TYPE_UNKNOWN_TABLE = 'unknown_table';

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
     * Do not check tokens related to migration from signed to unsigned integers in primary/foreign keys.
     *
     * @var bool
     */
    protected $ignore_unsigned_keys_migration;

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
     * @param bool $ignore_unsigned_keys_migration        Do not check tokens related to migration from signed to unsigned integers in primary/foreign keys.
     */
    public function __construct(
        DBmysql $db,
        bool $strict = true,
        bool $ignore_innodb_migration = false,
        bool $ignore_timestamps_migration = false,
        bool $ignore_utf8mb4_migration = false,
        bool $ignore_dynamic_row_format_migration = false,
        bool $ignore_unsigned_keys_migration = false
    ) {
        $this->db = $db;
        $this->strict = $strict;
        $this->ignore_dynamic_row_format_migration = $ignore_dynamic_row_format_migration;
        $this->ignore_innodb_migration = $ignore_innodb_migration;
        $this->ignore_timestamps_migration = $ignore_timestamps_migration;
        $this->ignore_unsigned_keys_migration = $ignore_unsigned_keys_migration;
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
    public function hasDifferences(string $table_name, string $proper_create_table_sql): bool
    {
        return $this->getDiff($table_name, $proper_create_table_sql) !== '';
    }

    /**
     * Get diff between effective table structure and proper structure contained in "CREATE TABLE" sql query.
     *
     * @param string $table
     * @param string $proper_create_table_sql
     *
     * @return string
     */
    public function getDiff(string $table_name, string $proper_create_table_sql): string
    {
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
     * Extract the contents of the schema file.
     *
     * @param string $schema_path The absolute path to the schema file
     *
     * @return array    The parsed contents of the schema file.
     *                  Keys contains table names and values contains CREATE TABLE SQL queries.
     *
     * @throws RuntimeException Thrown if the specified schema file cannot be read.
     */
    public function extractSchemaFromFile(string $schema_path): array
    {
        if (
            !is_file($schema_path)
            || !is_readable($schema_path)
            || ($schema_sql = file_get_contents($schema_path)) === false
        ) {
            throw new RuntimeException(sprintf(__('Unable to read installation file "%s".'), $schema_path));
        }

        $matches = [];
        preg_match_all('/CREATE TABLE[^`]*`(.+)`[^;]+/', $schema_sql, $matches);
        $tables_names             = $matches[1];
        $create_table_sql_queries = $matches[0];

        $schema = [];
        foreach ($create_table_sql_queries as $index => $create_table_sql_query) {
            $schema[$tables_names[$index]] = $create_table_sql_query;
        }
        return $schema;
    }

    /**
     * Check if there is differences between effective schema and schema contained in given file.
     *
     * @param string $schema_path           The absolute path to the schema file
     * @param bool $include_unknown_tables  Indicates whether unknown existing tables should be include in results
     * @param string $context               Context used for unknown tables identification (could be 'core' or 'plugin:plugin_key')
     *
     * @return array    List of tables that differs from the expected schema.
     *                  Keys are table names, and each entry has following properties:
     *                      - `type`:       difference type, see self::RESULT_TYPE_* constants;
     *                      - `diff`:       diff string.
     */
    public function checkCompleteSchema(
        string $schema_path,
        bool $include_unknown_tables = false,
        string $context = 'core'
    ): array {
        $schema = $this->extractSchemaFromFile($schema_path);

        $this->db->clearSchemaCache(); // Ensure fetched table list is up-to-date

        $differ = new Differ();
        $result = [];

        foreach ($schema as $table_name => $create_table_sql) {
            $create_table_sql = $this->getNomalizedSql($create_table_sql);

            if (!$this->db->tableExists($table_name)) {
                $result[$table_name] = [
                    'type' => self::RESULT_TYPE_MISSING_TABLE,
                    'diff' => $differ->diff($create_table_sql, '')
                ];
                continue;
            }

            $effective_create_table_sql = $this->getNomalizedSql($this->getEffectiveCreateTableSql($table_name));
            if ($create_table_sql !== $effective_create_table_sql) {
                $result[$table_name] = [
                    'type' => self::RESULT_TYPE_ALTERED_TABLE,
                    'diff' => $differ->diff($create_table_sql, $effective_create_table_sql)
                ];
            }
        }

        if ($include_unknown_tables) {
            $unknown_tables_criteria = [
                [
                    'NOT' => [
                        'table_name' => array_keys($schema)
                    ]
                ],
            ];
            $is_context_valid = true;
            if ($context === 'core') {
                $unknown_tables_criteria[] = [
                    'NOT' => [
                        'table_name' => ['LIKE', 'glpi\_plugin\_%']
                    ]
                ];
            } elseif (preg_match('/^plugin:\w+$/', $context) === 1) {
                $unknown_tables_criteria[] = [
                    'table_name' => ['LIKE', sprintf('glpi\_plugin\_%s_%%', str_replace('plugin:', '', $context))]
                ];
            } else {
                trigger_error(sprintf('Invalid context "%s".', $context));
                $is_context_valid = false;
            }
            $unknown_table_iterator = $is_context_valid ? $this->db->listTables('glpi\_%', $unknown_tables_criteria) : [];
            foreach ($unknown_table_iterator as $unknown_table_data) {
                $table_name = $unknown_table_data['TABLE_NAME'];
                $effective_create_table_sql = $this->getNomalizedSql($this->getEffectiveCreateTableSql($table_name));
                $result[$table_name] = [
                    'type' => self::RESULT_TYPE_UNKNOWN_TABLE,
                    'diff' => $differ->diff('', $effective_create_table_sql)
                ];
            }
        }

        return $result;
    }

    /**
     * Returns "CREATE TABLE" SQL returned by DB itself.
     *
     * @param string $table_name
     *
     * @return string
     */
    protected function getEffectiveCreateTableSql(string $table_name): string
    {
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
    protected function getNomalizedSql(string $create_table_sql): string
    {
        $cache_key = md5($create_table_sql);
        if (array_key_exists($cache_key, $this->normalized)) {
            return $this->normalized[$cache_key];
        }

       // Extract information
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
        if (
            !preg_match_all('/^\s*(?<column>`\w+` .+?),?$/im', $structure_sql, $columns_matches)
            || !preg_match_all('/^\s*(?<index>(CONSTRAINT|(UNIQUE |PRIMARY |FULLTEXT )?KEY) .+?),?$/im', $structure_sql, $indexes_matches)
            || !preg_match_all('/\s+((?<property>[^=]+[^\s])\s*=\s*(?<value>(\w+|\'(\\.|[^"])+\')))/i', $properties_sql, $properties_matches)
        ) {
            return $create_table_sql;// Unable to normalize
        }
        $columns = $columns_matches['column'];
        $indexes = $indexes_matches['index'];
        $properties = array_combine($properties_matches['property'], $properties_matches['value']);

        $db_version_string = $this->db->getVersion();
        $db_server  = preg_match('/-MariaDB/', $db_version_string) ? 'MariaDB' : 'MySQL';
        $db_version = preg_replace('/^((\d+\.?)+).*$/', '$1', $db_version_string);

        // Normalize columns definitions
        if (!$this->strict) {
            usort(
                $columns,
                function (string $a, string $b) {
                    // Move id / AUTO_INCREMENT column first
                    if (preg_match('/(`id`|AUTO_INCREMENT)/', $a)) {
                        return -1;
                    }
                    if (preg_match('/(`id`|AUTO_INCREMENT)/', $b)) {
                        return 1;
                    }
                    return strcmp($a, $b);
                }
            );
        }
        $column_replacements = [
            // Remove comments
            '/ COMMENT \'.+\'/i' => '',
            // Remove integer display width
            '/( (tiny|small|medium|big)?int)\(\d+\)/i' => '$1',
        ];
        if ($db_server === 'MariaDB') {
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
        if ($this->ignore_unsigned_keys_migration) {
            $column_replacements['/(`id`|`.+_id(_.+)?`) int unsigned/i'] = '$1 int';
        }
        $columns = preg_replace(array_keys($column_replacements), array_values($column_replacements), $columns);

        // Normalize indexes definitions
        if (!$this->strict) {
            usort(
                $indexes,
                function (string $a, string $b) {
                    $order = [
                        'PRIMARY KEY',
                        'UNIQUE KEY',
                        'FULLTEXT KEY',
                        'KEY',
                        'CONSTRAINT',
                    ];
                    $a_priority = array_search(
                        preg_replace('/^(CONSTRAINT|(UNIQUE |PRIMARY |FULLTEXT )?KEY).+$/', '$1', $a),
                        $order
                    );
                    $b_priority = array_search(
                        preg_replace('/^(CONSTRAINT|(UNIQUE |PRIMARY |FULLTEXT )?KEY).+$/', '$1', $b),
                        $order
                    );
                    return $a_priority !== $b_priority
                        ? $a_priority - $b_priority // Index type is different, reorder by type
                        : strcmp($a, $b); // Index type is similar, reorder by name
                }
            );
        }

        // Normalize properties
        unset($properties['AUTO_INCREMENT']);
        unset($properties['COMMENT']);
        if ($this->ignore_dynamic_row_format_migration) {
            unset($properties['ROW_FORMAT']);
        }
        if (!$this->strict && ($properties['ROW_FORMAT'] ?? '') === 'DYNAMIC') {
            // MySQL 5.7+ and MariaDB 10.2+ does not ouput ROW_FORMAT when ROW_FORMAT has not been specified in creation query
            // and so uses default value.
            // Drop it if value is 'DYNAMIC' as we assume this is the default value.
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
            $normalized_sql .= '  ' . $definition . ($i < count($definitions) - 1 ? ',' : '') . "\n";
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
