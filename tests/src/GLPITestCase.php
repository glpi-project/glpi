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

use Glpi\Tests\Log\TestHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

// Main GLPI test case. All tests should extends this class.

class GLPITestCase extends atoum
{
    private $int;
    private $str;
    protected $has_failed = false;

    /**
     * @var TestHandler
     */
    private $php_log_handler;

    /**
     * @var TestHandler
     */
    private $sql_log_handler;

    public function beforeTestMethod($method)
    {
       // By default, no session, not connected
        $this->resetSession();

       // Ensure cache is clear
        global $GLPI_CACHE;
        $GLPI_CACHE->clear();

        // Init log handlers
        global $PHPLOGGER, $SQLLOGGER;
        /** @var Monolog\Logger $PHPLOGGER */
        $this->php_log_handler = new TestHandler(LogLevel::DEBUG);
        $PHPLOGGER->setHandlers([$this->php_log_handler]);
        $this->sql_log_handler = new TestHandler(LogLevel::DEBUG);
        $SQLLOGGER->setHandlers([$this->sql_log_handler]);
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
            foreach ([$this->php_log_handler, $this->sql_log_handler] as $log_handler) {
                $this->array($log_handler->getRecords());
                $clean_logs = array_map(
                    static function (array $entry): array {
                        return [
                            'channel' => $entry['channel'],
                            'level'   => $entry['level_name'],
                            'message' => $entry['message'],
                        ];
                    },
                    $log_handler->getRecords()
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
                // Keep only usefull info to display a comprehensive dump
                return [
                    'level'   => $record['level'],
                    'message' => $record['message'],
                ];
            },
            $handler->getRecords()
        );

        $matching = null;
        foreach ($records as $record) {
            if ($record['level'] === Logger::toMonologLevel($level) && strpos($record['message'], $message) !== false) {
                $matching = $record;
                break;
            }
        }
        $this->variable($matching)->isNotNull(
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
            if ($record['level'] === Logger::toMonologLevel($level) && preg_match($pattern, $record['message']) === 1) {
                $matching = $record;
                break;
            }
        }
        $this->variable($matching)->isNotNull('No matching log found.');
        $handler->dropFromRecords($matching['message'], $matching['level']);

        $this->has_failed = false;
    }

    /**
     * Get a unique random string
     */
    protected function getUniqueString()
    {
        if (is_null($this->str)) {
            return $this->str = uniqid('str');
        }
        return $this->str .= 'x';
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
}
