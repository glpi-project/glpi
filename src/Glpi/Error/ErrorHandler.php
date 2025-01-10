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

namespace Glpi\Error;

use GLPI;
use Glpi\Error\ErrorDisplayHandler\ConsoleErrorDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\EchoDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\HtmlErrorDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\ErrorDisplayHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\ErrorHandler as BaseErrorHandler;

final class ErrorHandler extends BaseErrorHandler
{
    public const FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;

    public const ERROR_LEVEL_MAP = [
        E_ERROR             => LogLevel::CRITICAL,
        E_WARNING           => LogLevel::WARNING,
        E_PARSE             => LogLevel::ALERT,
        E_NOTICE            => LogLevel::NOTICE,
        E_CORE_ERROR        => LogLevel::CRITICAL,
        E_CORE_WARNING      => LogLevel::WARNING,
        E_COMPILE_ERROR     => LogLevel::ALERT,
        E_COMPILE_WARNING   => LogLevel::WARNING,
        E_USER_ERROR        => LogLevel::ERROR,
        E_USER_WARNING      => LogLevel::WARNING,
        E_USER_NOTICE       => LogLevel::NOTICE,
        2048                => LogLevel::NOTICE, // 2048 = deprecated E_STRICT (since PHP 8.4)
        E_RECOVERABLE_ERROR => LogLevel::ERROR,
        E_DEPRECATED        => LogLevel::INFO,
        E_USER_DEPRECATED   => LogLevel::INFO,
    ];

    private const ERROR_CODE_MESSAGE = [
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parsing Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        2048                => 'Runtime Notice', // 2048 = deprecated E_STRICT (since PHP 8.4)
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        E_DEPRECATED        => 'Deprecated function',
        E_USER_DEPRECATED   => 'User deprecated function',
    ];

    /**
     * @var bool By default, the ErrorHandler will always display errors right in the current execution. This is done via output handlers.
     */
    private static bool $enable_output = true;

    private static Logger $currentLogger;

    private string $env;

    public function __construct(?BufferingLogger $bootstrappingLogger = null, bool $debug = false, string $env = \GLPI_ENVIRONMENT_TYPE)
    {
        parent::__construct($bootstrappingLogger, $debug);

        $this->env = $env;
        $this->scopeAt(self::FATAL_ERRORS, true);
        $this->screamAt(self::FATAL_ERRORS, true);
        $this->traceAt(self::FATAL_ERRORS, true);
        $this->throwAt(self::FATAL_ERRORS, true);

        $this->configureLogger();
        $this->configureErrorDisplay();
    }

    public static function enableOutput(): void
    {
        self::$enable_output = true;
    }

    public static function disableOutput(): void
    {
        self::$enable_output = false;
    }

    public static function getCurrentLogger(): Logger
    {
        if (!self::$currentLogger) {
            throw new \RuntimeException('Logger was fetched from an unbuilt ErrorHandler, probably because the call was made before Kernel was created. Please check your GLPI codebase is up-to-date and that all your PHP files are executed within a valid Kernel.');
        }

        return self::$currentLogger;
    }

    /**
     * @return array<ErrorDisplayHandler>
     */
    private static function getOutputHandlers(): array
    {
        static $handlers;

        if ($handlers) {
            return $handlers;
        }

        // ⚠ Order matters here: first for which "canOuput()" returns true will be used, so the most restrictive should be first.
        return $handlers = [
            new ConsoleErrorDisplayHandler(),
            new HtmlErrorDisplayHandler(),
            new EchoDisplayHandler(),
        ];
    }

    public static function displayErrorMessage(string $error_type, string $message, string $log_level, string $env = \GLPI_ENVIRONMENT_TYPE): void
    {
        if (!self::$enable_output) {
            return;
        }

        $is_dev_env         = $env === \GLPI::ENV_DEVELOPMENT;
        $is_debug_mode      = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE;
        $is_console_context = \isCommandLine();

        if (
            !$is_dev_env             // error messages are always displayed in development environment
            && !$is_debug_mode       // error messages are always displayed in debug mode
            && !$is_console_context  // error messages are always forwarded to the console output handler, that handles itself the verbosity level
        ) {
            return;
        }

        $output_handled = false;

        foreach (self::getOutputHandlers() as $handler) {
            if ($handler->canOutput($log_level, $env)) {
                $output_handled = true;
                $handler->displayErrorMessage($error_type, $message, $log_level, $env);
                break; // Only one display per handler
            }
        }

        if (!$output_handled) {
            throw new \RuntimeException('No error display handler was able to output the error message.');
        }
    }

    public function handleError(int $type, string $message, string $file, int $line): bool
    {
        $handled = parent::handleError($type, $message, $file, $line);

        if (self::$enable_output) {
            self::displayErrorMessage(
                \sprintf('PHP %s (%s)', self::ERROR_CODE_MESSAGE[$type] ?? 'Unknown error', $type),
                \sprintf('%s in %s at line %s', $message, $file, $line),
                self::ERROR_LEVEL_MAP[E_ERROR],
                $this->env,
            );
        }

        return $handled;
    }

    private function configureLogger(): void
    {
        if (\defined('GLPI_LOG_LVL')) {
            $minimum_log_level = GLPI_LOG_LVL;
        } else {
            $minimum_log_level = match (\GLPI_ENVIRONMENT_TYPE) {
                \GLPI::ENV_DEVELOPMENT => LogLevel::DEBUG,
                \GLPI::ENV_TESTING => LogLevel::NOTICE,
                default => LogLevel::WARNING,
            };
        }

        $logger = new Logger('glpierrorlog');
        $handler = new \Monolog\Handler\StreamHandler(
            GLPI_LOG_DIR . "/glpi-errors.log",
            $minimum_log_level
        );
        $handler->setFormatter(new LogLineFormatter());

        $logger->pushHandler($handler);
        $this->setDefaultLogger($logger);

        self::$currentLogger = $logger;
    }

    private function configureErrorDisplay(): void
    {
        // Adjust reporting level to the environment, to ensure that all the errors supposed to be logged are
        // actually reported, and to prevent reporting other errors.
        $reporting_level = E_ALL;
        foreach (self::ERROR_LEVEL_MAP as $value => $log_level) {
            if (
                $this->env !== GLPI::ENV_DEVELOPMENT
                && \in_array($log_level, [LogLevel::DEBUG, LogLevel::INFO], true)
            ) {
                // Do not report debug and info messages unless in development env.
                // Suppressing the INFO level will prevent deprecations to be pushed in other environments logs.
                //
                // Suppressing the deprecations in the testing environment is mandatory to prevent deprecations
                // triggered in vendor code to make our test suite fail.
                // We may review this part once we will have migrate all our test suite on PHPUnit.
                // For now, we rely on PHPStan to detect usages of deprecated code.
                $reporting_level &= ~$value;
            }

            if (
                $log_level === LogLevel::NOTICE
                && !\in_array($this->env, [GLPI::ENV_DEVELOPMENT, GLPI::ENV_TESTING], true)
            ) {
                // Do not report notice messages unless in development/testing env.
                // Notices are errors with no functional impact, so we do not want people to report them as issues.
                $reporting_level &= ~$value;
            }
        }
        \error_reporting($reporting_level);

        // Disable native error displaying as it will be handled by `self::outputDebugMessage()`.
        \ini_set('display_errors', 'Off');
    }
}
