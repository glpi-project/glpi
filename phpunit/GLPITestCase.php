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

use Glpi\Asset\AssetDefinitionManager;
use Glpi\Tests\Log\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

// Main GLPI test case. All tests should extends this class.

class GLPITestCase extends TestCase
{
    private $int;
    private $str;
    protected $has_failed = false;
    private ?array $config_copy = null;
    private array $superglobals_copy = [];

    /**
     * @var TestHandler
     */
    private $php_log_handler;

    /**
     * @var TestHandler
     */
    private $sql_log_handler;

    public function setUp(): void
    {
        $this->storeGlobals();

        global $DB;
        $DB->setTimezone('UTC');

        // By default, no session, not connected
        $this->resetSession();

        // By default, there shouldn't be any pictures in the test files
        $this->resetPictures();

        // Ensure cache is clear
        global $GLPI_CACHE;
        $GLPI_CACHE->clear();

        // Init log handlers
        global $PHPLOGGER, $SQLLOGGER;
        /** @var \Monolog\Logger $PHPLOGGER */
        $this->php_log_handler = new TestHandler(LogLevel::DEBUG);
        $PHPLOGGER->setHandlers([$this->php_log_handler]);
        $this->sql_log_handler = new TestHandler(LogLevel::DEBUG);
        $SQLLOGGER->setHandlers([$this->sql_log_handler]);

        vfsStreamWrapper::register();
    }

    public function tearDown(): void
    {
        $this->resetGlobalsAndStaticValues();

        vfsStreamWrapper::unregister();

        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT']) && !$this->has_failed) {
            unset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO]);
            $this->assertSame(
                [],
                $_SESSION['MESSAGE_AFTER_REDIRECT'],
                sprintf(
                    "Some messages has not been handled in %s::%s:\n%s",
                    static::class,
                    __METHOD__/*$method*/,
                    print_r($_SESSION['MESSAGE_AFTER_REDIRECT'], true)
                )
            );
        }

        if (!$this->has_failed) {
            foreach ([$this->php_log_handler, $this->sql_log_handler] as $log_handler) {
                $this->assertIsArray($log_handler->getRecords());
                $clean_logs = array_map(
                    static function (\Monolog\LogRecord $entry): array {
                        return [
                            'channel' => $entry->channel,
                            'level'   => $entry->level->name,
                            'message' => $entry->message,
                        ];
                    },
                    $log_handler->getRecords()
                );
                $this->assertEmpty(
                    $clean_logs,
                    sprintf(
                        "Unexpected entries in log in %s::%s:\n%s",
                        static::class,
                        __METHOD__/*$method*/,
                        print_r($clean_logs, true)
                    )
                );
            }
        }
    }

    protected function resetPictures()
    {
        // Delete contents of test files/_pictures
        $dir = GLPI_PICTURE_DIR;
        if (!str_contains($dir, '/tests/files/_pictures')) {
            throw new \RuntimeException('Invalid picture dir: ' . $dir);
        }
        // Delete nested folders and files in dir
        $fn_delete = function ($dir, $parent) use (&$fn_delete) {
            $files = glob($dir . '/*') ?? [];
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $fn_delete($file, $parent);
                } else {
                    unlink($file);
                }
            }
            if ($dir !== $parent) {
                rmdir($dir);
            }
        };
        if (file_exists($dir) && is_dir($dir)) {
            $fn_delete($dir, $dir);
        }
    }

    /**
     * Call a private method, and get its return value.
     *
     * @param mixed     $instance   Class instance
     * @param string    $methodName Method to call
     * @param mixed     ...$arg     Method arguments
     *
     * @return mixed
     */
    protected function callPrivateMethod($instance, string $methodName, ...$args)
    {
        $method = new \ReflectionMethod($instance, $methodName);
        $method->setAccessible(true);

        return $method->invoke($instance, ...$args);
    }

    /**
     * Call a private constructor, and get the created instance.
     *
     * @param string    $classname  Class to instanciate
     * @param mixed     $arg        Constructor arguments
     *
     * @return mixed
     */
    protected function callPrivateConstructor($classname, $args)
    {
        $class = new ReflectionClass($classname);
        $instance = $class->newInstanceWithoutConstructor();

        $constructor = $class->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invokeArgs($instance, $args);

        return $instance;
    }

    protected function resetSession()
    {
        Session::destroy();
        Session::start();

        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        $_SESSION['glpiactive_entity'] = 0;

        global $CFG_GLPI;
        foreach ($CFG_GLPI['user_pref_field'] as $field) {
            if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
                $_SESSION["glpi$field"] = $CFG_GLPI[$field];
            }
        }
    }

    protected function hasSessionMessages(int $level, array $messages): void
    {
        $this->has_failed = true;
        $this->assertTrue(
            isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]),
            'No messages for selected level!'
        );
        $this->assertSame(
            $messages,
            $_SESSION['MESSAGE_AFTER_REDIRECT'][$level],
            'Expecting ' . print_r($messages, true) . 'got: ' . print_r($_SESSION['MESSAGE_AFTER_REDIRECT'][$level], true)
        );
        unset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]); //reset
        $this->has_failed = false;
    }

    protected function hasNoSessionMessages(array $levels)
    {
        foreach ($levels as $level) {
            $this->hasNoSessionMessage($level);
        }
    }

    protected function hasNoSessionMessage(int $level)
    {
        $this->has_failed = true;
        $this->assertFalse(
            isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]),
            sprintf(
                'Messages for level %s are present in session: %s',
                $level,
                print_r($_SESSION['MESSAGE_AFTER_REDIRECT'][$level] ?? [], true)
            )
        );
        $this->has_failed = false;
    }

    /**
     * Check in PHP log for a record that contains given message.
     *
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    protected function hasPhpLogRecordThatContains(string $message, string $level): void
    {
        $this->hasLogRecordThatContains($this->php_log_handler, $message, $level);
    }

    /**
     * Check in SQL log for a record that contains given message.
     *
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    protected function hasSqlLogRecordThatContains(string $message, string $level): void
    {
        $this->hasLogRecordThatContains($this->sql_log_handler, $message, $level);
    }

    /**
     * Check given log handler for a record that contains given message.
     *
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    private function hasLogRecordThatContains(TestHandler $handler, string $message, string $level): void
    {
        $this->has_failed = true;

        $records = array_map(
            function ($record) {
                // Keep only useful info to display a comprehensive dump
                return [
                    'level'   => $record['level'],
                    'message' => $record['message'],
                ];
            },
            $handler->getRecords()
        );

        $matching = null;
        foreach ($records as $record) {
            if (
                Level::fromValue($record['level']) === Level::fromName($level)
                && strpos($record['message'], $message) !== false
            ) {
                $matching = $record;
                break;
            }
        }
        $this->assertNotNull(
            $matching,
            sprintf("Message not found in log records\n- %s\n+ %s", $message, print_r($records, true))
        );

        $handler->dropFromRecords($matching['message'], $matching['level']);

        $this->has_failed = false;
    }

    /**
     * Check in PHP log for a record that matches given pattern.
     *
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    protected function hasPhpLogRecordThatMatches(string $pattern, string $level): void
    {
        $this->hasLogRecordThatMatches($this->php_log_handler, $pattern, $level);
    }

    /**
     * Check in SQL log for a record that matches given pattern.
     *
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    protected function hasSqlLogRecordThatMatches(string $pattern, string $level): void
    {
        $this->hasLogRecordThatMatches($this->sql_log_handler, $pattern, $level);
    }

    /**
     * Check given log handler for a record that matches given pattern.
     *
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    private function hasLogRecordThatMatches(TestHandler $handler, string $pattern, string $level): void
    {
        $this->has_failed = true;

        $matching = null;
        foreach ($handler->getRecords() as $record) {
            if (
                Level::fromValue($record['level']) === Level::fromName($level)
                && preg_match($pattern, $record['message']) === 1
            ) {
                $matching = $record;
                break;
            }
        }
        $this->assertNotNull(
            $matching,
            'No matching log found.'
        );
        $handler->dropFromRecords($matching['message'], $matching['level']);

        $this->has_failed = false;
    }

    /**
     * Get a unique random string
     */
    protected function getUniqueString()
    {
        return substr(
            str_shuffle(
                str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz", 5)
            ),
            0,
            16
        );
    }

    /**
     * Get a unique random integer
     */
    protected function getUniqueInteger()
    {
        if (is_null($this->int)) {
            return $this->int = mt_rand(1000, 10000);
        }
        return $this->int++;
    }

    /**
     * Get the "_test_root_entity" entity created by the tests's bootstrap file
     *
     * @param bool $only_id
     *
     * @return Entity|int
     */
    protected function getTestRootEntity(bool $only_id = false)
    {
        return getItemByTypeName('Entity', '_test_root_entity', $only_id);
    }

    /**
     * Get the raw database handle object.
     *
     * Useful when you need to run some queries that may not be allowed by
     * the DBMysql object.
     *
     * @return mysqli
     */
    protected function getDbHandle(): mysqli
    {
        /** @var \DBmysql $db */
        global $DB;

        $reflection = new ReflectionClass(\DBmysql::class);
        $property = $reflection->getProperty("dbh");
        $property->setAccessible(true);
        return $property->getValue($DB);
    }

    /**
     * Store Globals
     *
     * @return void
     */
    private function storeGlobals(): void
    {
        global $CFG_GLPI;

        // Super globals
        $this->superglobals_copy['GET'] = $_GET;
        $this->superglobals_copy['POST'] = $_POST;
        $this->superglobals_copy['REQUEST'] = $_REQUEST;
        $this->superglobals_copy['SERVER'] = $_SERVER;

        if ($this->config_copy === null) {
            $this->config_copy = $CFG_GLPI;
        }
    }

    /**
     * Reset globals and static variables
     *
     * @return void
     */
    private function resetGlobalsAndStaticValues(): void
    {
        // Super globals
        $_GET = $this->superglobals_copy['GET'];
        $_POST = $this->superglobals_copy['POST'];
        $_REQUEST = $this->superglobals_copy['REQUEST'];
        $_SERVER = $this->superglobals_copy['SERVER'];

        // Globals
        global $CFG_GLPI, $FOOTER_LOADED, $HEADER_LOADED;
        $CFG_GLPI = $this->config_copy;
        $FOOTER_LOADED = false;
        $HEADER_LOADED = false;


        // Statics values
        Log::$use_queue = false;
        CommonDBTM::clearSearchOptionCache();
        \Glpi\Search\SearchOption::clearSearchOptionCache();
        AssetDefinitionManager::unsetInstance();
        Dropdown::resetItemtypesStaticCache();
    }

    /**
     * Apply a DateTime modification using the given string.
     * Examples:
     * - $this->modifyCurrentTime('+1 second');
     * - $this->modifyCurrentTime('+5 hours');
     * - $this->modifyCurrentTime('-2 years');
     */
    protected function modifyCurrentTime(string $modification): void
    {
        $date = new DateTime(Session::getCurrentTime());
        $date->modify($modification);
        $_SESSION['glpi_currenttime'] = $date->format("Y-m-d H:i:s");
    }
}
