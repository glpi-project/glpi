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

namespace Glpi\System\Diagnostic;

use DBmysql;
use Glpi\Toolbox\DatabaseSchema;
use Glpi\Toolbox\VersionParser;
use Plugin;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

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
     * Differ instance.
     *
     * @var Differ
     */
    private $differ;

    /**
     * GLPI database version.
     *
     * @var string
     */
    private $db_version;

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

        $this->differ = new Differ(
            new UnifiedDiffOutputBuilder(
                sprintf(
                    "--- %s\n+++ %s\n",
                    __('Expected database schema'),
                    __('Current database schema')
                )
            )
        );
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

        return $this->diff(
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
     * @throws \RuntimeException Thrown if the specified schema file cannot be read.
     */
    public function extractSchemaFromFile(string $schema_path): array
    {
        if (
            !is_file($schema_path)
            || !is_readable($schema_path)
            || ($schema_sql = file_get_contents($schema_path)) === false
        ) {
            throw new \RuntimeException(sprintf(__('Unable to read installation file "%s".'), $schema_path));
        }

        $matches = [];
        preg_match_all('/(?<sql_query>CREATE TABLE[^`]*`(?<table_name>\w+)`.+?);$/ms', $schema_sql, $matches);
        $tables_names             = $matches['table_name'];
        $create_table_sql_queries = $matches['sql_query'];

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

        $result = [];

        foreach ($schema as $table_name => $create_table_sql) {
            $create_table_sql = $this->getNomalizedSql($create_table_sql);

            if (!$this->db->tableExists($table_name)) {
                $result[$table_name] = [
                    'type' => self::RESULT_TYPE_MISSING_TABLE,
                    'diff' => $this->diff($create_table_sql, '')
                ];
                continue;
            }

            $effective_create_table_sql = $this->getNomalizedSql($this->getEffectiveCreateTableSql($table_name));
            if ($create_table_sql !== $effective_create_table_sql) {
                $result[$table_name] = [
                    'type' => self::RESULT_TYPE_ALTERED_TABLE,
                    'diff' => $this->diff($create_table_sql, $effective_create_table_sql)
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
                    'diff' => $this->diff('', $effective_create_table_sql)
                ];
            }
        }

        return $result;
    }

    /**
     * Check if there is differences between effective schema and expected schema for given version/context.
     *
     * @param string|null $schema_version   Installed schema version
     * @param bool $include_unknown_tables  Indicates whether unknown existing tables should be include in results
     * @param string $context               Context used for unknown tables identification (could be 'core' or 'plugin:plugin_key')
     *
     * @return array    List of tables that differs from the expected schema.
     *                  Keys are table names, and each entry has following properties:
     *                      - `type`:       difference type, see self::RESULT_TYPE_* constants;
     *                      - `diff`:       diff string.
     *
     * @throws \RuntimeException Thrown if schema file is not available.
     */
    final public function checkCompleteSchemaForVersion(
        ?string $schema_version = null,
        bool $include_unknown_tables = false,
        string $context = 'core'
    ): array {
        $schema_path = $this->getSchemaPath($schema_version, $context);
        if ($schema_path === null) {
            throw new \RuntimeException('Schema file not available.');
        }

        return $this->checkCompleteSchema($schema_path, $include_unknown_tables, $context);
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

        $dbversion = $this->getDbVersion();

        // Clean whitespaces
        $create_table_sql = $this->normalizeWhitespaces($create_table_sql);

        // Extract information
        $matches = [];
        if (!preg_match('/^CREATE TABLE[^`]*`(?<table>\w+)` \((?<structure>.+)\)(?<properties>[^\)]*)$/is', $create_table_sql, $matches)) {
            return $create_table_sql;// Unable to normalize
        }

        $table_name     = $matches['table'];
        $structure_sql  = $matches['structure'];
        $properties_sql = $matches['properties'];

        $columns_matches = [];
        $indexes_matches = [];
        $properties_matches = [];
        if (
            !preg_match_all('/^\s*(?<column>`\w+`.+?),?$/im', $structure_sql, $columns_matches)
            || !preg_match_all('/^\s*(?<index>(CONSTRAINT|PRIMARY KEY|(UNIQUE|FULLTEXT)( (KEY|INDEX))?|KEY|INDEX).+?),?$/im', $structure_sql, $indexes_matches)
            || !preg_match_all('/\s+((?<property>[^=]+[^\s])\s*=\s*(?<value>(\w+|\'(\\.|[^"])+\')))/i', $properties_sql, $properties_matches)
        ) {
            return $create_table_sql;// Unable to normalize
        }
        $columns = $columns_matches['column'];
        $indexes = $indexes_matches['index'];
        $properties = array_combine($properties_matches['property'], $properties_matches['value']);

        // Normalize columns definitions
        if (!$this->strict) {
            usort(
                $columns,
                function (string $a, string $b) {
                    // Move id / AUTO_INCREMENT column first
                    if (preg_match('/(`id`|AUTO_INCREMENT)/i', $a)) {
                        return -1;
                    }
                    if (preg_match('/(`id`|AUTO_INCREMENT)/i', $b)) {
                        return 1;
                    }
                    return strcmp($a, $b);
                }
            );
        }

        // Lowercase types
        $column_pattern = '/^'
            // column name surrounded by backquotes
            . '(?<name>`\w+`)'
            // optional space
            . '\s*'
            // column type
            . '(?<type>[a-z]+)'
            // optional column length, preceded by optional space
            . '(\s*(?<length>\(\d+\)))?'
            // optional extra column properties
            . '(?<extra>[^a-z].*)?'
            . '$/i';
        $columns = preg_replace_callback(
            $column_pattern,
            function ($matches) {
                return $matches['name'] . ' ' . strtolower($matches['type']) . ($matches['length'] ?? '') . ($matches['extra'] ?? '');
            },
            $columns
        );

        $column_replacements = [
            // Remove comments
            '/ COMMENT \'.+\'/i' => '',
            // Remove integer display width
            '/( (tiny|small|medium|big)?int)\(\d+\)/i' => '$1',
            // Replace function current_timestamp() by CURRENT_TIMESTAMP (MySQL uses constant while MariaDB 10.2+ uses function)
            '/current_timestamp\(\)/i' => 'CURRENT_TIMESTAMP',
            // Uppercase AUTO_INCREMENT (it seems that some tools are output it in lower case)
            '/auto_increment/i' => 'AUTO_INCREMENT',
            // Remove implicit DEFAULT NULL
            '/ DEFAULT NULL/i' => '',
            // Remove implicit NULL
            '/ (?<!NOT )NULL/i' => '$1',
            // Remove implicit default for datetime/timestamps where column cannot be null
            '/ (timestamp|datetime) NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP/' => ' $1 NOT NULL',
            // Remove surrounding quotes on default numeric values (quotes are optional, MySQL uses quotes while MariaDB 10.2+ does not)
            '/(DEFAULT) \'([-|+]?\d+(\.\d+)?)\'/i' => '$1 $2',
            // Remove surrounding quotes on collate values (quotes are optional)
            '/(COLLATE) \'([-|+]?\w+(\.\d+)?)\'/i' => '$1 $2',
        ];
        if ($this->ignore_timestamps_migration) {
            $column_replacements['/(`\w+`)\s*timestamp/i'] = '$1 datetime';
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

        if (
            $table_name === 'glpi_notimportedemails'
            && $dbversion !== null
            && version_compare(VersionParser::getNormalizedVersion($dbversion), '10.0', '<')
        ) {
            // Before GLPI 10.0.0, COLLATE property was not explicitely defined for glpi_notimportedemails,
            // and charset was not the same as other tables.
            // If installed schema is < 10.0.0, we exclude CHARACTER SET and COLLATE properties from
            // diff as we cannot easilly predict effective values (COLLATE will depend on server configuration)
            // and, anyway, it will be fixed during GLPI 10.0.0 migration.
            $column_replacements['/( CHARACTER SET \w+)? COLLATE \w+/i'] = '';
        }
        if (
            $table_name === 'glpi_impactcontexts'
            && $dbversion !== null
            && version_compare(VersionParser::getNormalizedVersion($dbversion), '10.0.1', '<')
        ) {
            // Remove default value on glpi_impactcontexts.positions column.
            // The default cannot be added on MySQL server, so it is impossible to fix this diff prior to migration.
            //
            // Apply this "hack" only when current DB version is < 10.0.1, which is the version that removed
            // this default value on GLPI firstly installed in version <= 9.5.3.
            // see https://github.com/glpi-project/glpi/pull/8415
            // see https://github.com/glpi-project/glpi/pull/11662
            $column_replacements['/(`positions`.*)\s*DEFAULT\s*\'\'\s*(.*)/'] = '$1 $2';
        }

        $columns = preg_replace(array_keys($column_replacements), array_values($column_replacements), $columns);

        // Normalize indexes definitions
        $indexes_replacements = [
            // Remove comments
            '/ COMMENT \'.+\'/i' => '',
            // Always use `KEY` word
            '/INDEX\s*(`\w+`)/' => 'KEY $1',
            // Add `KEY` word when missing from UNIQUE/FULLTEXT
            '/(UNIQUE|FULLTEXT)\s*(`|\()/' => '$1 KEY $2',
            // Always have a key identifier (except on PRIMARY key)
            '/(?<!PRIMARY )KEY\s*\((`\w+`)\)/' => 'KEY $1 ($1)',
        ];
        $indexes = preg_replace(array_keys($indexes_replacements), array_values($indexes_replacements), $indexes);
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
        if (
            $table_name === 'glpi_notimportedemails'
            && $dbversion !== null
            && version_compare(VersionParser::getNormalizedVersion($dbversion), '10.0', '<')
        ) {
            // Before GLPI 10.0.0, COLLATE property was not explicitely defined for glpi_notimportedemails,
            // and charset was not the same as other tables.
            // If installed schema is < 10.0.0, we exclude DEFAULT CHARSET and COLLATE properties from
            // diff as we cannot easilly predict effective values (COLLATE will depend on server configuration)
            // and, anyway, it will be fixed during GLPI 10.0.0 migration.
            unset($properties['DEFAULT CHARSET']);
            unset($properties['COLLATE']);
        }
        ksort($properties);

        // Rebuild SQL
        $normalized_sql = "CREATE TABLE `{$table_name}` (\n";
        $definitions = array_merge($columns, $indexes);
        foreach ($definitions as $i => $definition) {
            $normalized_sql .= '  ' . trim($definition) . ($i < count($definitions) - 1 ? ',' : '') . "\n";
        }
        $normalized_sql .= ')';
        foreach ($properties as $key => $value) {
            $normalized_sql .= ' ' . trim($key) . '=' . trim($value);
        }

        // Store in local cache
        $this->normalized[$cache_key] = $normalized_sql;

        return $normalized_sql;
    }

    /**
     * Normalize whitespaces in "CREATE TABLE" sql query to make it easier to extract definitions and
     * to avoid detection of differences on whitespaces.
     *
     * @param string $sql
     *
     * @return string
     */
    private function normalizeWhitespaces(string $sql): string
    {
        $is_protected      = false;
        $is_quoted         = false;
        $parenthesis_level = 0;

        for ($i = 0; $i < strlen($sql); $i++) {
            if ($sql[$i] === '\\') {
                // backslash found, next char is ignored
                $i += 1;
                continue;
            }

            // Do not touch chars surrounded by backticks
            if ($sql[$i] === '`') {
                if ($parenthesis_level === 1) {
                    // Ensure there are spaces around column / indexes names.
                    if (!$is_protected && preg_match('/\s/', $sql[$i - 1]) !== 1) {
                        // Opening backtick, ensure there is a space before
                        $sql = substr($sql, 0, $i) . ' ' . substr($sql, $i);
                        $i++;
                    } else if ($is_protected && preg_match('/\s/', $sql[$i + 1]) !== 1) {
                        // Closing backtick, ensure there is a space before
                        $sql = substr($sql, 0, $i + 1) . ' ' . substr($sql, $i + 1);
                        $i++;
                    }
                }

                $is_protected = !$is_protected;
                continue;
            } else if ($is_protected) {
                continue;
            }

            // Do not touch chars surrounded by quotes
            if ($sql[$i] === '\'') {
                $is_quoted = !$is_quoted;
                continue;
            } else if ($is_quoted) {
                //exit();
                continue;
            }

            if ($sql[$i] === '(') {
                $parenthesis_level++;
            } else if ($sql[$i] === ')') {
                $parenthesis_level--;
            }

            // Replace \n, \t, ... by a simple space char
            if (preg_match('/\s/', $sql[$i]) && $sql[$i] !== ' ') {
                $sql[$i] = ' ';
            }

            // Ensure there is a new line:
            // - after columns/indexes definition opening parenthesis
            // - before columns/indexes definition opening parenthesis
            // - after each column/index definition
            if ($parenthesis_level === 1 && $sql[$i] === '(') {
                $sql = substr($sql, 0, $i + 1) . "\n" . substr($sql, $i + 1);
                $i++;
            } else if ($parenthesis_level === 0 && $sql[$i] === ')') {
                $sql = substr($sql, 0, $i) . "\n" . substr($sql, $i);
                $i++;
            } else if ($parenthesis_level === 1 && $sql[$i] === ',') {
                $sql = substr($sql, 0, $i + 1) . "\n" . substr($sql, $i + 1);
                $i++;
            }

            if (preg_match('/\s/', $sql[$i])) {
                if ($parenthesis_level === 2) {
                    // Remove whitespaces between tokens in index fields list, datatype length, ...
                    $sql = substr($sql, 0, $i) . substr($sql, $i + 1);
                    $i--;
                } else {
                    // Remove following whitespace chars if current char is a whitespace
                    $extraspaces = 0;
                    $max         = strlen($sql) - $i - 1;
                    while ($extraspaces < $max && preg_match('/\s/', $sql[$i + 1 + $extraspaces])) {
                        $extraspaces++;
                    }
                    if ($extraspaces > 0) {
                        $sql = substr($sql, 0, $i + 1) . substr($sql, $i + 1 + $extraspaces);
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Generate diff between proper and effective `CREATE TABLE` SQL string.
     */
    private function diff(string $proper_create_table_sql, string $effective_create_table_sql): string
    {
        $diff = $this->differ->diff(
            $proper_create_table_sql,
            $effective_create_table_sql
        );

        if (!$this->db->allow_signed_keys) {
            // Add `unsigned` to primary/foreign keys on lines preceded by a `-` (i.e. expected but not found in DB).
            $diff = preg_replace('/^-\s+(`id`|`.+_id(_.+)?`)\s+int(?!\s+unsigned)/im', '$0 unsigned', $diff);
        }

        return $diff;
    }

    /**
     * Get schema file path for given version.
     *
     * @param string|null $schema_version   Installed schema version
     * @param string $context               Context used for unknown tables identification (could be 'core' or 'plugin:plugin_key')
     *
     * @return string|null
     */
    private function getSchemaPath(?string $schema_version = null, string $context = 'core'): ?string
    {
        $schema_path = null;

        if ($context === 'core') {
            $schema_version_nohash = preg_replace('/@.+$/', '', $schema_version); // strip hash
            $schema_path = DatabaseSchema::getEmptySchemaPath($schema_version_nohash);
        } elseif (preg_match('/^plugin:(?<plugin_key>\w+)$/', $context) === 1) {
            $plugin_key = str_replace('plugin:', '', $context);
            Plugin::load($plugin_key); // Load setup file, to be able to check schema even on inactive plugins
            $function_name = sprintf('plugin_%s_getSchemaPath', $plugin_key);
            if (!function_exists($function_name)) {
                return null;
            }
            $schema_path = $function_name($schema_version);
        }

        return !empty($schema_path) && file_exists($schema_path)
            ? $schema_path
            : null;
    }

    /**
     * Check if schema integrity can be checked for given version/context.
     *
     * @param string|null $schema_version   Installed schema version
     * @param string $context               Context used for unknown tables identification (could be 'core' or 'plugin:plugin_key')
     *
     * @return bool
     */
    final public function canCheckIntegrity(?string $schema_version = null, string $context = 'core'): bool
    {
        if ($context === 'core') {
            $schema_version_nohash = preg_replace('/@.+$/', '', $schema_version); // strip hash
            $is_stable_version     = VersionParser::isStableRelease($schema_version_nohash);
            $is_latest_version     = $schema_version === GLPI_SCHEMA_VERSION;

            if (!$is_stable_version && !$is_latest_version) {
                // Cannot check integrity if version is not stable, unless installed version is latest one.
                return false;
            }
        }
        return $this->getSchemaPath($schema_version, $context) !== null;
    }

    /**
     * Returns GLPI database version.
     *
     * @return string|null
     */
    private function getDbVersion(): ?string
    {
        if ($this->db_version === null) {
            if ($this->db->tableExists('glpi_configs') && $this->db->fieldExists('glpi_configs', 'context')) {
                $dbversion_result = $this->db->request(
                    [
                        'FROM'   => 'glpi_configs',
                        'WHERE'  => [
                            'context' => 'core',
                            'name'    => 'dbversion',
                        ]
                    ]
                );
                if ($dbversion_result->count() > 0) {
                    // GLPI >= 9.2
                    $this->db_version = $dbversion_result->current()['value'];
                } else {
                    // GLPI >= 0.85
                    $dbversion_result = $this->db->request(
                        [
                            'FROM'   => 'glpi_configs',
                            'WHERE'  => [
                                'context' => 'core',
                                'name'    => 'version',
                            ]
                        ]
                    );
                    $this->db_version = $dbversion_result->current()['value'] ?? null;
                }
            } elseif ($this->db->tableExists('glpi_configs') && $this->db->fieldExists('glpi_configs', 'version')) {
                // GLPI < 0.85
                $this->db_version = $this->db->request(
                    [
                        'SELECT' => ['version'],
                        'FROM'   => 'glpi_configs',
                    ]
                )->current()['version'] ?? null;
            }
        }

        return $this->db_version;
    }
}
