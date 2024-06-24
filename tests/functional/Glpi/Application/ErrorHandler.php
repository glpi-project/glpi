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

namespace tests\units\Glpi\Application;

use GLPI;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Session;

class ErrorHandler extends \GLPITestCase
{
    /**
     * @return array
     */
    protected function errorProvider(): iterable
    {
        $log_prefix = '/';
        $log_suffix = '.*' . preg_quote(' in ' . __FILE__ . ' at line ', '/') . '\d+' . '/i';

        foreach ([Session::NORMAL_MODE, Session::DEBUG_MODE] as $session_mode) {
            foreach ([GLPI::ENV_DEVELOPMENT, GLPI::ENV_TESTING, GLPI::ENV_STAGING, GLPI::ENV_PRODUCTION] as $env) {
                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_call'           => function () {
                        file_get_contents('this-file-does-not-exists');
                    },
                    'expected_log_level'   => Level::Warning,
                    'expected_msg_pattern' => $log_prefix
                        . preg_quote('PHP Warning (' . E_WARNING . '): file_get_contents(this-file-does-not-exists): failed to open stream: No such file or directory', '/')
                        . $log_suffix,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_call'           => function () {
                        trigger_error('this is a warning', E_USER_WARNING);
                    },
                    'expected_log_level'   => Level::Warning,
                    'expected_msg_pattern' => $log_prefix
                        . preg_quote('PHP User Warning (' . E_USER_WARNING . '): this is a warning', '/')
                        . $log_suffix,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_call'           => function () {
                        trigger_error('some notice', E_USER_NOTICE);
                    },
                    'expected_log_level'   => Level::Notice,
                    'expected_msg_pattern' => $log_prefix
                        . preg_quote('PHP User Notice (' . E_USER_NOTICE . '): some notice', '/')
                        . $log_suffix,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_call'           => function () {
                        trigger_error('this method is deprecated', E_USER_DEPRECATED);
                    },
                    'expected_log_level'   => Level::Info,
                    'expected_msg_pattern' => $log_prefix
                        . preg_quote('PHP User deprecated function (' . E_USER_DEPRECATED . '): this method is deprecated', '/')
                        . $log_suffix,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_call'           => function () {
                        $param = new \ReflectionParameter([\Config::class, 'getTypeName'], 0);
                        $param->isCallable();
                    },
                    'expected_log_level'   => Level::Info,
                    'expected_msg_pattern' => $log_prefix
                        . preg_quote('PHP Deprecated function (' . E_DEPRECATED . '): Method ReflectionParameter::isCallable() is deprecated', '/')
                        . $log_suffix,
                ];
            }
        }
    }

    /**
     * Test that errors triggered are correctly processed by registered error handler.
     *
     * Nota: Fatal errors cannot be tested that way, as they will result in exiting current execution.
     * Related constants are E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR.
     *
     * @dataProvider errorProvider
     */
    public function testRegisteredErrorHandler(
        int $session_mode,
        string $env,
        callable $error_call,
        Level $expected_log_level,
        string $expected_msg_pattern
    ) {
        $handler = new TestHandler();
        $logger = $this->newMockInstance('Monolog\\Logger', null, null, ['test-logger', [$handler]]);

        $previous_use_mode         = $_SESSION['glpi_use_mode'];

        $error_handler = $this->callPrivateConstructor(
            \Glpi\Application\ErrorHandler::class,
            [$logger, $env]
        );
        $error_handler->setForwardToInternalHandler(false);
        $error_handler->register();

        // Assert that nothing is logged when using '@' operator
        $_SESSION['glpi_use_mode'] = $session_mode;
        @$error_call();
        $_SESSION['glpi_use_mode'] = $previous_use_mode;
        $this->integer(count($handler->getRecords()))->isEqualTo(0);
        $this->output->isEmpty();

        // Assert that error handler acts as expected when not using '@' operator
        $_SESSION['glpi_use_mode'] = $session_mode;
        $error_call();
        $_SESSION['glpi_use_mode'] = $previous_use_mode;
        $this->integer(count($handler->getRecords()))->isEqualTo(1);
        $this->boolean($handler->hasRecordThatMatches($expected_msg_pattern, $expected_log_level));
        if (
            $env === GLPI::ENV_DEVELOPMENT
            || (
                $session_mode === Session::DEBUG_MODE
                && ($expected_log_level->isHigherThan(Level::Info)  || !in_array($env, [GLPI::ENV_STAGING, GLPI::ENV_PRODUCTION]))
            )
        ) {
            $this->output->matches($expected_msg_pattern);
        } else {
            $this->output->isEmpty();
        }
    }

    protected function handleErrorProvider(): iterable
    {

        $log_prefix = '/';
        $log_suffix = ' \(\d+\): .*' . preg_quote(' in ' . __FILE__ . ' at line ', '/') . '\d+' . '/';

        foreach ([Session::NORMAL_MODE, Session::DEBUG_MODE] as $session_mode) {
            foreach ([GLPI::ENV_DEVELOPMENT, GLPI::ENV_TESTING, GLPI::ENV_STAGING, GLPI::ENV_PRODUCTION] as $env) {
                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_ERROR,
                    'expected_log_level'   => Level::Critical,
                    'expected_msg_pattern' => $log_prefix . 'PHP Error' . $log_suffix,
                    'is_fatal_error'       => true,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_WARNING,
                    'expected_log_level'   => Level::Warning,
                    'expected_msg_pattern' => $log_prefix . 'PHP Warning' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_PARSE,
                    'expected_log_level'   => Level::Alert,
                    'expected_msg_pattern' => $log_prefix . 'PHP Parsing Error' . $log_suffix,
                    'is_fatal_error'       => true,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_NOTICE,
                    'expected_log_level'   => Level::Notice,
                    'expected_msg_pattern' => $log_prefix . 'PHP Notice' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_CORE_ERROR,
                    'expected_log_level'   => Level::Critical,
                    'expected_msg_pattern' => $log_prefix . 'PHP Core Error' . $log_suffix,
                    'is_fatal_error'       => true,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_CORE_WARNING,
                    'expected_log_level'   => Level::Warning,
                    'expected_msg_pattern' => $log_prefix . 'PHP Core Warning' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_COMPILE_ERROR,
                    'expected_log_level'   => Level::Alert,
                    'expected_msg_pattern' => $log_prefix . 'PHP Compile Error' . $log_suffix,
                    'is_fatal_error'       => true,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_COMPILE_WARNING,
                    'expected_log_level'   => Level::Warning,
                    'expected_msg_pattern' => $log_prefix . 'PHP Compile Warning' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_USER_ERROR,
                    'expected_log_level'   => Level::Error,
                    'expected_msg_pattern' => $log_prefix . 'PHP User Error' . $log_suffix,
                    'is_fatal_error'       => true,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_USER_WARNING,
                    'expected_log_level'   => Level::Warning,
                    'expected_msg_pattern' => $log_prefix . 'PHP User Warning' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_USER_NOTICE,
                    'expected_log_level'   => Level::Notice,
                    'expected_msg_pattern' => $log_prefix . 'PHP User Notice' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_RECOVERABLE_ERROR,
                    'expected_log_level'   => Level::Error,
                    'expected_msg_pattern' => $log_prefix . 'PHP Catchable Fatal Error' . $log_suffix,
                    'is_fatal_error'       => true,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_DEPRECATED,
                    'expected_log_level'   => Level::Info,
                    'expected_msg_pattern' => $log_prefix . 'PHP Deprecated function' . $log_suffix,
                    'is_fatal_error'       => false,
                ];

                yield [
                    'session_mode'         => $session_mode,
                    'env'                  => $env,
                    'error_code'           => E_USER_DEPRECATED,
                    'expected_log_level'   => Level::Info,
                    'expected_msg_pattern' => $log_prefix . 'PHP User deprecated function' . $log_suffix,
                    'is_fatal_error'       => false,
                ];
            }
        }
    }

    /**
     * Test error handler and fatal error handler methods.
     *
     * @dataProvider handleErrorProvider
     */
    public function testHandleErrorAndHandleFatalError(
        int $session_mode,
        string $env,
        int $error_code,
        Level $expected_log_level,
        string $expected_msg_pattern,
        bool $is_fatal_error
    ) {
        $handler = new TestHandler();
        $logger = $this->newMockInstance('Monolog\\Logger', null, null, ['test-logger', [$handler]]);

        $previous_use_mode         = $_SESSION['glpi_use_mode'];

        $error_handler = $this->callPrivateConstructor(
            \Glpi\Application\ErrorHandler::class,
            [$logger, $env]
        );
        $error_handler->setForwardToInternalHandler(false);

        // Assert that nothing is logged when using '@' operator
        $_SESSION['glpi_use_mode'] = $session_mode;
        @$error_handler->handleError($error_code, 'err_msg', __FILE__, __LINE__);
        $_SESSION['glpi_use_mode'] = $previous_use_mode;
        $this->integer(count($handler->getRecords()))->isEqualTo(0);
        $this->output->isEmpty();

        // Assert that error handler acts as expected when not using '@' operator
        // Fatal error are not logged by function, but other errors should be
        $_SESSION['glpi_use_mode'] = $session_mode;
        $error_handler->handleError($error_code, 'err_msg', __FILE__, __LINE__);
        $_SESSION['glpi_use_mode'] = $previous_use_mode;

        if ($is_fatal_error) {
            // If error is a Fatal error, message logging should be delegated to
            // the 'handleFatalError' method which will be used as shutdown function.
            $this->integer(count($handler->getRecords()))->isEqualTo(0);

            $this->function->error_get_last = [
                'type'    => $error_code,
                'message' => 'err_msg',
                'file'    => __FILE__,
                'line'    => __LINE__,
            ];
            $_SESSION['glpi_use_mode'] = $session_mode;
            $error_handler->handleFatalError();
            $_SESSION['glpi_use_mode'] = $previous_use_mode;
        }

        $this->integer(count($handler->getRecords()))->isEqualTo(1);
        $this->boolean($handler->hasRecordThatMatches($expected_msg_pattern, $expected_log_level));

        if (
            $env === GLPI::ENV_DEVELOPMENT
            || (
                $session_mode === Session::DEBUG_MODE
                && ($expected_log_level->isHigherThan(Level::Info)  || !in_array($env, [GLPI::ENV_STAGING, GLPI::ENV_PRODUCTION]))
            )
        ) {
            $this->output->matches($expected_msg_pattern);
        } else {
            $this->output->isEmpty();
        }
    }

    /**
     * Test exception handler.
     */
    public function testHandleException()
    {
        $exception = new \RuntimeException('Something went wrong');
        $expected_msg_pattern = '/'
         . preg_quote('Uncaught Exception RuntimeException: Something went wrong in ' . __FILE__ . ' at line ', '/')
         . '\d+'
         . '/';

        $previous_use_mode         = $_SESSION['glpi_use_mode'];

        foreach ([Session::NORMAL_MODE, Session::DEBUG_MODE] as $session_mode) {
            foreach ([GLPI::ENV_DEVELOPMENT, GLPI::ENV_TESTING, GLPI::ENV_STAGING, GLPI::ENV_PRODUCTION] as $env) {
                $handler = new TestHandler();
                $logger = $this->newMockInstance('Monolog\\Logger', null, null, ['test-logger', [$handler]]);

                $error_handler = $this->callPrivateConstructor(
                    \Glpi\Application\ErrorHandler::class,
                    [$logger, $env]
                );

                // Assert that exception handler logs exception and output error when quiet parameter is not set
                $_SESSION['glpi_use_mode'] = $session_mode;
                $error_handler->handleException($exception);
                $_SESSION['glpi_use_mode'] = $previous_use_mode;

                $this->integer(count($handler->getRecords()))->isEqualTo(1);
                $this->boolean($handler->hasRecordThatMatches($expected_msg_pattern, Level::Critical));
                if ($env === GLPI::ENV_DEVELOPMENT || $session_mode === Session::DEBUG_MODE) {
                    $this->output->matches($expected_msg_pattern);
                } else {
                    $this->output->isEmpty();
                }
                $handler->reset(); // Remove records

                // Assert that exception handler logs exception and DO NOT output error when parameter mode is set to true
                $_SESSION['glpi_use_mode'] = $session_mode;
                $error_handler->handleException($exception, true);
                $_SESSION['glpi_use_mode'] = $previous_use_mode;

                $this->integer(count($handler->getRecords()))->isEqualTo(1);
                $this->boolean($handler->hasRecordThatMatches($expected_msg_pattern, Level::Critical));
                $this->output->isEmpty();
                $handler->reset(); // Remove records

                // Assert that exception handler logs exception and output error when parameter mode is set to false
                $_SESSION['glpi_use_mode'] = $session_mode;
                $error_handler->handleException($exception, false);
                $_SESSION['glpi_use_mode'] = $previous_use_mode;

                $this->integer(count($handler->getRecords()))->isEqualTo(1);
                $this->boolean($handler->hasRecordThatMatches($expected_msg_pattern, Level::Critical));
                if ($env === GLPI::ENV_DEVELOPMENT || $session_mode === Session::DEBUG_MODE) {
                    $this->output->matches($expected_msg_pattern);
                } else {
                    $this->output->isEmpty();
                }
            }
        }
    }
}
