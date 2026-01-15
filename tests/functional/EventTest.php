<?php

namespace tests\units;

use Glpi\Event;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EventTest extends DbTestCase
{
    #[DataProvider('eventLogLevelThresholdDataProvider')]
    public function testEventLogThreshold(int $level, int $threshold, bool $should_be_logged_in_file): void
    {
        // --- arrange
        global $CFG_GLPI;

        $CFG_GLPI["use_log_in_files"] = 1; // ensure file logging is enabled
        $CFG_GLPI["event_loglevel"] = $threshold;
        $log_file = GLPI_LOG_DIR . "/event.log"; // event.log is hardcoded \Glpi\Event::log()
        $log_service = $this->getUniqueString();
        file_put_contents($log_file, ""); // empty log file for easier debugging

        // --- act
        Event::log(0, self::class, $level, $log_service, 'event');

        // --- assert
        $expected_count = $level <= $threshold ? 1 : 0;
        $this->assertEquals(
            $expected_count,
            countElementsInTable(
                Event::getTable(),
                ['service' => $log_service, 'level' => $level]
            )
        );

        $should_be_logged_in_file
            ? $this->assertStringContainsString(
            "[$log_service]",
            file_get_contents($log_file),
            implode(' ', ['service' => $log_service, 'level' => $level, 'should be logged in file' => $should_be_logged_in_file])
        )
            : $this->assertStringNotContainsString(
            "[$log_service]",
            file_get_contents($log_file),
            implode(' ', ['service' => $log_service, 'level' => $level, 'should be logged in file' => $should_be_logged_in_file])
        );
    }

    public static function eventLogLevelThresholdDataProvider(): iterable
    {
        foreach (range(1, 5) as $threshold) {
            foreach (range(1, 5) as $level) {
                yield "level=$level, threshold=$threshold" => [
                    'level' => $level,
                    'threshold' => $threshold,
                    'should_be_logged_in_file'
                    => ($level <= $threshold) // should be logged if level is equal or more critical than threshold (notice glpi level are inverted compared to PSR)
                        && $level <= 3 // no logging for level higher than 3
                    ,
                ];
            }
        }
    }
}
