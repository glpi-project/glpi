<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Console\Migration;

use Glpi\Console\Migration\TimestampsCommand;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\GLPITestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class TimestampsCommandTest extends GLPITestCase
{
    private const TABLE = 'glpi_tmp_timestamp_migration_bounds';

    public function tearDown(): void
    {
        $this->getDbHandle()->query(sprintf('DROP TABLE IF EXISTS `%s`', self::TABLE));

        parent::tearDown();
    }

    public function testNormalizeOutOfRangeValues(): void
    {
        $original_timezone = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');
            $this->getDbHandle()->query("SET SESSION time_zone = '+00:00'");
            $this->createTestTable();

            $this->getDbHandle()->query(
                sprintf(
                    <<<'SQL'
INSERT IGNORE INTO `%s` (`id`, `nullable_date`, `required_date`) VALUES
   (1, NULL, '2020-01-01 00:00:00'),
   (2, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
   (3, '2050-01-01 00:00:00', '2050-01-01 00:00:00')
SQL,
                    self::TABLE
                )
            );

            $output = new BufferedOutput();
            $command = $this->getCommand($output);

            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'nullable_date', true);
            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'required_date', false);

            $rows = $this->fetchRows();

            $max_value = $this->getServerTimestampMaxValue();

            $this->assertSame(null, $rows[1]['nullable_date']);
            $this->assertSame('2020-01-01 00:00:00', $rows[1]['required_date']);
            $this->assertSame(null, $rows[2]['nullable_date']);
            $this->assertSame('1970-01-01 00:00:01', $rows[2]['required_date']);
            // 2050 is within the extended (2106) range on 64-bit MariaDB 11.5+ and is preserved;
            // on standard (32-bit / MySQL) servers it is clamped to the 2038 max.
            $this->assertSame($max_value === '2038-01-19 03:14:07' ? '2038-01-19 03:14:07' : '2050-01-01 00:00:00', $rows[3]['nullable_date']);
            $this->assertSame($max_value === '2038-01-19 03:14:07' ? '2038-01-19 03:14:07' : '2050-01-01 00:00:00', $rows[3]['required_date']);

            $display = $output->fetch();
            $this->assertStringContainsString(
                'row(s) from "glpi_tmp_timestamp_migration_bounds"."nullable_date"',
                $display
            );
            $this->assertStringContainsString(
                'row(s) from "glpi_tmp_timestamp_migration_bounds"."required_date"',
                $display
            );
            $this->assertStringContainsString('updated to "NULL"', $display);
            if ($max_value === '2038-01-19 03:14:07') {
                $this->assertStringContainsString('updated to "2038-01-19 03:14:07"', $display);
            }
        } finally {
            date_default_timezone_set($original_timezone);
        }
    }

    public function testNormalizedFutureValuesCanBeConvertedToTimestamp(): void
    {
        $original_timezone = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');
            $this->getDbHandle()->query("SET SESSION time_zone = '+00:00'");
            $this->createTestTable();

            $this->getDbHandle()->query(
                sprintf(
                    "INSERT INTO `%s` (`id`, `nullable_date`, `required_date`) VALUES (1, '2050-01-01 00:00:00', '2050-01-01 00:00:00')",
                    self::TABLE
                )
            );

            $command = $this->getCommand(new BufferedOutput());

            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'nullable_date', true);
            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'required_date', false);

            $this->getDbHandle()->query(
                sprintf(
                    'ALTER TABLE `%s` MODIFY COLUMN `nullable_date` TIMESTAMP NULL DEFAULT NULL, MODIFY COLUMN `required_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                    self::TABLE
                )
            );

            $rows = $this->fetchRows();

            $max_value = $this->getServerTimestampMaxValue();

            // 2050 is within the extended (2106) range on 64-bit MariaDB 11.5+ and is preserved;
            // on standard (32-bit / MySQL) servers it is clamped to the 2038 max.
            $expected = $max_value === '2038-01-19 03:14:07' ? '2038-01-19 03:14:07' : '2050-01-01 00:00:00';
            $this->assertSame($expected, $rows[1]['nullable_date']);
            $this->assertSame($expected, $rows[1]['required_date']);
            $this->assertNotSame('0000-00-00 00:00:00', $rows[1]['nullable_date']);
            $this->assertNotSame('0000-00-00 00:00:00', $rows[1]['required_date']);
        } finally {
            date_default_timezone_set($original_timezone);
        }
    }

    public function testNormalizeOutOfRangeValuesClampsDatesBeyondExtendedRange(): void
    {
        $original_timezone = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');
            $this->getDbHandle()->query("SET SESSION time_zone = '+00:00'");
            $this->createTestTable();

            // 2200-01-01 is beyond even the extended (2106) range and must always be clamped.
            $this->getDbHandle()->query(
                sprintf(
                    "INSERT INTO `%s` (`id`, `nullable_date`, `required_date`) VALUES (1, '2200-01-01 00:00:00', '2200-01-01 00:00:00')",
                    self::TABLE
                )
            );

            $command = $this->getCommand(new BufferedOutput());

            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'nullable_date', true);
            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'required_date', false);

            $rows = $this->fetchRows();

            $this->assertSame($this->getServerTimestampMaxValue(), $rows[1]['nullable_date']);
            $this->assertSame($this->getServerTimestampMaxValue(), $rows[1]['required_date']);
        } finally {
            date_default_timezone_set($original_timezone);
        }
    }

    public function testNormalizeOutOfRangeValuesDoesNotWarnWhenNoValueIsChanged(): void
    {
        $original_timezone = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');
            $this->getDbHandle()->query("SET SESSION time_zone = '+00:00'");
            $this->createTestTable();

            $this->getDbHandle()->query(
                sprintf(
                    "INSERT INTO `%s` (`id`, `nullable_date`, `required_date`) VALUES (1, NULL, '2020-01-01 00:00:00')",
                    self::TABLE
                )
            );

            $output = new BufferedOutput();
            $command = $this->getCommand($output);

            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'nullable_date', true);
            $this->callPrivateMethod($command, 'normalizeOutOfRangeValues', self::TABLE, 'required_date', false);

            $this->assertSame('', $output->fetch());
        } finally {
            date_default_timezone_set($original_timezone);
        }
    }

    private function createTestTable(): void
    {
        $this->getDbHandle()->query(sprintf('DROP TABLE IF EXISTS `%s`', self::TABLE));
        $this->getDbHandle()->query(
            sprintf(
                <<<'SQL'
CREATE TABLE `%s` (
   `id` int unsigned NOT NULL,
   `nullable_date` datetime NULL DEFAULT NULL,
   `required_date` datetime NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
                self::TABLE
            )
        );
    }

    private function getCommand(BufferedOutput $output): TimestampsCommand
    {
        global $DB;

        $command = new TimestampsCommand();
        $this->setPrivateProperty($command, 'db', $DB);
        $this->setPrivateProperty($command, 'output', $output);

        return $command;
    }

    /**
     * Probe the actual server TIMESTAMP upper bound using the same behavioral
     * detection as the command, so tests pass on both standard (2038) and
     * extended (2106, 64-bit MariaDB 11.5+) servers.
     */
    private function getServerTimestampMaxValue(): string
    {
        global $DB;

        try {
            $result = $DB->request([
                'SELECT' => new QueryExpression("CAST('2106-02-07 06:28:15' AS TIMESTAMP) AS probe"),
            ]);
            $row = $result->current();
            if (isset($row['probe']) && $row['probe'] === '2106-02-07 06:28:15') {
                return '2106-02-07 06:28:15';
            }
        } catch (\Throwable $e) {
            // extended range not supported
        }

        return '2038-01-19 03:14:07';
    }

    private function fetchRows(): array
    {
        $result = $this->getDbHandle()->query(
            sprintf('SELECT `id`, `nullable_date`, `required_date` FROM `%s` ORDER BY `id`', self::TABLE)
        );

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[(int) $row['id']] = $row;
        }

        return $rows;
    }
}
