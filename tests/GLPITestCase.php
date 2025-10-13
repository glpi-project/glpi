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
use Glpi\Dropdown\DropdownDefinitionManager;
use Glpi\Search\SearchOption;
use Glpi\Tests\Log\TestHandler;
use Laminas\I18n\Translator\Translator;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use SebastianBergmann\Comparator\ComparisonFailure;

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
    private $log_handler;

    public function setUp(): void
    {
        /** @var Translator $TRANSLATE */
        global $TRANSLATE;

        $this->storeGlobals();

        global $DB;
        $DB->setTimezone('UTC');

        // By default, no session, not connected
        $this->resetSession();

        // Locale from previous session may persist until another login is done.
        if ($TRANSLATE->getLocale() !== "en_GB") {
            // Reload default language only if needed to prevent performance hit
            Session::loadLanguage();
        }

        // By default, there shouldn't be any pictures in the test files
        $this->resetPictures();

        // Ensure cache is clear
        global $GLPI_CACHE;
        $GLPI_CACHE->clear();

        // Init log handler
        global $PHPLOGGER;
        /** @var Logger $PHPLOGGER */
        $this->log_handler = new TestHandler(LogLevel::DEBUG);
        $PHPLOGGER->setHandlers([$this->log_handler]);

        vfsStreamWrapper::register();

        // Make sure the tester plugin is never deactived by a test as it would
        // impact others tests that depend on it.
        $this->assertTrue(Plugin::isPluginActive('tester'));
    }

    public function tearDown(): void
    {
        $this->resetGlobalsAndStaticValues();

        vfsStreamWrapper::unregister();

        // Make sure the tester plugin is never deactived by a test as it would
        // impact others tests that depend on it.
        $this->assertTrue(Plugin::isPluginActive('tester'));

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
            $this->assertIsArray($this->log_handler->getRecords());
            $clean_logs = array_map(
                static function (LogRecord $entry): array {
                    $clean_entry = [
                        'channel' => $entry->channel,
                        'level'   => $entry->level->name,
                        'message' => $entry->message,
                        'context' => [],
                    ];
                    if (isset($entry->context['exception']) && $entry->context['exception'] instanceof Throwable) {
                        /* @var \Throwable $exception */
                        $exception = $entry->context['exception'];
                        $clean_entry['context']['exception'] = [
                            'message' => $exception->getMessage(),
                            'trace'   => $exception->getTraceAsString(),
                        ];
                    }
                    return $clean_entry;
                },
                $this->log_handler->getRecords()
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

    protected function resetPictures()
    {
        // Delete contents of test files/_pictures
        $dir = GLPI_PICTURE_DIR;
        if (!str_contains($dir, '/tests/files/_pictures')) {
            throw new RuntimeException('Invalid picture dir: ' . $dir);
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
        $method = new ReflectionMethod($instance, $methodName);

        return $method->invoke($instance, ...$args);
    }

    /**
     * Call a private constructor, and get the created instance.
     *
     * @template T
     * @param class-string<T>   $classname  Class to instanciate
     * @param array             $args       Constructor arguments
     *
     * @return T
     */
    protected function callPrivateConstructor(string $classname, array $args = [])
    {
        $class = new ReflectionClass($classname);
        $instance = $class->newInstanceWithoutConstructor();

        $constructor = $class->getConstructor();
        $constructor->invokeArgs($instance, $args);

        return $instance;
    }

    /**
     * Get the value of a private property.
     *
     * @param mixed     $instance       Class instance
     * @param string    $propertyName   Property name
     * @param mixed     $default        Default value if property is not set
     */
    protected function setPrivateProperty($instance, string $propertyName, $value)
    {
        $property = new ReflectionProperty($instance, $propertyName);
        $property->setValue($instance, $value);
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

    /**
     * Check that the session contains messages for the given level.
     *
     * @param int $level one of the constant values (INFO, ERROR, WARNING) @see src/autoload/constants.php:105
     * @param array<string> $messages
     */
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

    /**
     * Check that the session contains the given message.
     */
    protected function hasSessionMessageThatContains(string $message, string $level): void
    {
        $this->has_failed = true;
        $this->assertTrue(
            isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]),
            'No messages for selected level!'
        );

        $found = false;
        foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'][$level] as $key => $record) {
            if (str_contains($record, $message)) {
                $found = true;
                unset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level][$key]);
                break;
            }
        }

        if ($found) {
            foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'] as $level => $records) {
                if ($records === []) {
                    unset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]);
                }
            }
        }

        $this->has_failed = !$found;
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
        $this->has_failed = true;

        $records = array_map(
            function ($record) {
                // Keep only useful info to display a comprehensive dump
                return [
                    'level'   => $record['level'],
                    'message' => $record['message'],
                ];
            },
            $this->log_handler->getRecords()
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

        $this->log_handler->dropFromRecords($matching['message'], $matching['level']);

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
        $this->has_failed = true;

        $matching = null;
        foreach ($this->log_handler->getRecords() as $record) {
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
        $this->log_handler->dropFromRecords($matching['message'], $matching['level']);

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
        /** @var DBmysql $db */
        global $DB;

        $reflection = new ReflectionClass(DBmysql::class);
        $property = $reflection->getProperty("dbh");
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
        SearchOption::clearSearchOptionCache();
        Dropdown::resetItemtypesStaticCache();

        // Reboot assets definitions
        AssetDefinitionManager::unsetInstance();
        AssetDefinitionManager::getInstance()->bootDefinitions();
        DropdownDefinitionManager::unsetInstance();
        DropdownDefinitionManager::getInstance()->bootDefinitions();
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

    /**
     * Set $_SESSION['glpi_currenttime'] and return the related DateTimeImmutable object.
     *
     * @param string $datetime expected format is "Y-m-d H:i:s"
     */
    protected function setCurrentTime(string $datetime): DateTimeImmutable
    {
        $time_regexp = '([01]?[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])'; // H:i:s format - 23:59:59
        $day_regexp = '\d{4}-\d{2}-\d{2}'; // Y-m-d format - 2026-31-12

        if (!preg_match("/^$day_regexp $time_regexp\$/", $datetime)) {
            throw new InvalidArgumentException('Unexpected datetime format : ' . $datetime . '. Expected format is "Y-m-d H:i:s"');
        }

        // set & return new current time
        $_SESSION['glpi_currenttime'] = $datetime;

        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $_SESSION['glpi_currenttime']);
    }

    /**
     * Return the minimal fields required for the creation of an item of the given class.
     *
     * @param class-string $class
     * @return array
     */
    protected function getMinimalCreationInput(string $class): array
    {
        if (!is_a($class, CommonDBTM::class, true)) {
            return [];
        }

        $input = [];

        $item = new $class();

        if ($item->isField($class::getNameField())) {
            $input[$class::getNameField()] = $this->getUniqueString();
        }

        if ($item->isField('entities_id')) {
            $input['entities_id'] = $this->getTestRootEntity(true);
        }

        switch ($class) {
            case Cartridge::class:
                $input['cartridgeitems_id'] = getItemByTypeName(CartridgeItem::class, '_test_cartridgeitem01', true);
                break;
            case Change::class:
                $input['content'] = $this->getUniqueString();
                break;
            case Consumable::class:
                $input['consumableitems_id'] = getItemByTypeName(ConsumableItem::class, '_test_consumableitem01', true);
                break;
            case DCRoom::class:
                $input['vis_cols'] = 20;
                $input['vis_rows'] = 20;
                break;
            case Item_DeviceSimcard::class:
                $input['itemtype']          = Computer::class;
                $input['items_id']          = getItemByTypeName(Computer::class, '_test_pc01', true);
                $input['devicesimcards_id'] = getItemByTypeName(DeviceSimcard::class, '_test_simcard_1', true);
                break;
            case Problem::class:
                $input['content'] = $this->getUniqueString();
                break;
            case SoftwareLicense::class:
                $input['softwares_id'] = getItemByTypeName(Software::class, '_test_soft', true);
                break;
            case Ticket::class:
                $input['content'] = $this->getUniqueString();
                break;
        }

        if (is_a($class, Item_Devices::class, true)) {
            $input[$class::$items_id_2] = 1; // Valid ID is not required (yet)
        }

        return $input;
    }

    /**
     * Assert that an array matches the expected array but ignore keys order.
     *
     * This method is usefull to compare multidimentional arrays easilly, when keys order does not matter.
     */
    protected function assertArrayIsEqualIgnoringKeysOrder(array $expected, array $actual, ?string $message = null): void
    {
        try {
            if (array_is_list($expected)) {
                // Array is a list, check that all entries are found in the actual value, ignoring their keys.
                foreach ($expected as $expected_entry) {
                    $found = false;
                    foreach ($actual as $actual_entry) {
                        try {
                            if (is_array($expected_entry)) {
                                $this->assertArrayIsEqualIgnoringKeysOrder($expected_entry, $actual_entry);
                            } else {
                                $this->assertEquals($expected_entry, $actual_entry);
                            }
                            $found = true; // No exception thrown means that the value matches.
                            break;
                        } catch (ExpectationFailedException) {
                            // Value does not match
                        }
                    }
                    if ($found === false) {
                        $this->assertTrue($found);
                    }
                }
            } else {
                // Array is not a list, check that all expected entries are found and are using the same keys.
                foreach ($expected as $key => $expected_entry) {
                    $this->assertArrayHasKey($key, $actual);
                    if (is_array($expected_entry)) {
                        $this->assertArrayIsEqualIgnoringKeysOrder($expected_entry, $actual[$key]);
                    } else {
                        $this->assertEquals($expected_entry, $actual[$key]);
                    }
                }
            }

            // Be sure that there is the same entries count (no missing or extra entries in the actual value).
            $this->assertEquals(count($expected), count($actual));
        } catch (ExpectationFailedException $e) {
            throw new ExpectationFailedException(
                $message ?? 'Failed asserting that two arrays are equal.',
                new ComparisonFailure($expected, $actual, json_encode($expected, JSON_PRETTY_PRINT), json_encode($actual, JSON_PRETTY_PRINT)),
                $e
            );
        }
    }
}
