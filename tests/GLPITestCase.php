<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Tests\Log\TestHandler;
use Monolog\Level;
use Psr\Log\LogLevel;

// Main GLPI test case. All tests should extends this class.

class GLPITestCase extends atoum
{
    private $int;
    protected $has_failed = false;

    /**
     * @var TestHandler
     */
    private $log_handler;

    public function beforeTestMethod($method)
    {
       // By default, no session, not connected
        $this->resetSession();

        // By default, there shouldn't be any pictures in the test files
        $this->resetPictures();

       // Ensure cache is clear
        global $GLPI_CACHE;
        $GLPI_CACHE->clear();

        // Init log handler
        global $PHPLOGGER;
        /** @var \Monolog\Logger $PHPLOGGER */
        $this->log_handler = new TestHandler(LogLevel::DEBUG);
        $PHPLOGGER->setHandlers([$this->log_handler]);
    }

    public function afterTestMethod($method)
    {
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT']) && !$this->has_failed) {
            unset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO]);
            $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo(
                [],
                sprintf(
                    "Some messages has not been handled in %s::%s:\n%s",
                    static::class,
                    $method,
                    print_r($_SESSION['MESSAGE_AFTER_REDIRECT'], true)
                )
            );
        }

        if (!$this->has_failed) {
            $this->array($this->log_handler->getRecords());
            $clean_logs = array_map(
                static function (\Monolog\LogRecord $entry): array {
                    return [
                        'channel' => $entry->channel,
                        'level'   => $entry->level->name,
                        'message' => $entry->message,
                    ];
                },
                $this->log_handler->getRecords()
            );
            $this->array($clean_logs)->isEmpty(
                sprintf(
                    "Unexpected entries in log in %s::%s:\n%s",
                    static::class,
                    $method,
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
        $this->boolean(isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]))->isTrue('No messages for selected level!');
        $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'][$level])->isIdenticalTo(
            $messages,
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
        $this->boolean(isset($_SESSION['MESSAGE_AFTER_REDIRECT'][$level]))->isFalse(
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
                // Keep only usefull info to display a comprehensive dump
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
        $this->variable($matching)->isNotNull(
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
        $this->variable($matching)->isNotNull('No matching log found.');
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
     * Return the minimal fields required for the creation of an item of the given class.
     *
     * @param string $class
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
}
