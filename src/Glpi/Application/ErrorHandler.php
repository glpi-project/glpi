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

namespace Glpi\Application;

use GLPI;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 9.5.0
 */
class ErrorHandler
{
    /**
     * Map between error codes and log levels.
     */
    private const ERROR_LEVEL_MAP = [
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
        2048                => LogLevel::NOTICE, // 2048 = deprecated E_STRICT
        E_RECOVERABLE_ERROR => LogLevel::ERROR,
        E_DEPRECATED        => LogLevel::INFO,
        E_USER_DEPRECATED   => LogLevel::INFO,
    ];

    /**
     * GLPI environment.
     */
    private string $env;

    /**
     * Logger instance.
     */
    private ?LoggerInterface $logger = null;

    /**
     * Indicates wether output is disabled.
     */
    private bool $output_disabled = false;

    /**
     * Output handler to use. If not set, output will be directly echoed on a format depending on
     * execution context (Web VS CLI).
     */
    private ?OutputInterface $output_handler = null;

    private function __construct(?LoggerInterface $logger = null, string $env = GLPI_ENVIRONMENT_TYPE)
    {
        $this->logger = $logger;
        $this->env = $env;
    }

    /**
     * Return singleton instance of self.
     *
     * @return ErrorHandler
     */
    public static function getInstance(): ErrorHandler
    {
        static $instance = null;

        if ($instance === null) {
            /** @var \Psr\Log\LoggerInterface $PHPLOGGER */
            global $PHPLOGGER;
            $instance = new self($PHPLOGGER);
        }

        return $instance;
    }

    /**
     * Enable output.
     *
     * @return void
     */
    public function enableOutput(): void
    {
        $this->output_disabled = false;
    }

    /**
     * Disable output.
     *
     * @return void
     */
    public function disableOutput(): void
    {
        $this->output_disabled = true;
    }

    /**
     * Defines output handler.
     *
     * @param OutputInterface $output_handler
     *
     * @return void
     */
    public function setOutputHandler(OutputInterface $output_handler): void
    {
        $this->output_handler = $output_handler;
    }

    /**
     * Register error reporting & display.
     */
    public function register(): void
    {
        // Adjust reporting level to the environment, to ensure that all the errors supposed to be logged are
        // actually reported, and to prevent reporting other errors.
        $reporting_level = E_ALL;
        foreach (self::ERROR_LEVEL_MAP as $value => $log_level) {
            if (
                $this->env !== GLPI::ENV_DEVELOPMENT
                && \in_array($log_level, [LogLevel::DEBUG, LogLevel::INFO])
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
                !\in_array($this->env, [GLPI::ENV_DEVELOPMENT, GLPI::ENV_TESTING], true)
                && $log_level === LogLevel::NOTICE
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

    public function logPhpError(int $error_code, string $error_message, string $filename, int $line_number): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace);  // Remove current method from trace
        $error_trace = $this->getTraceAsString($trace);

        $this->logErrorMessage(
            \sprintf('PHP %s (%s)', $this->codeToString($error_code), $error_code),
            \sprintf('%s in %s at line %s', $error_message, $filename, $line_number),
            $error_trace,
            self::ERROR_LEVEL_MAP[$error_code],
        );
    }

    public function outputPhpError(int $error_code, string $error_message, string $filename, int $line_number): void
    {
        $this->outputDebugMessage(
            \sprintf('PHP %s (%s)', $this->codeToString($error_code), $error_code),
            \sprintf('%s in %s at line %s', $error_message, $filename, $line_number),
            self::ERROR_LEVEL_MAP[E_ERROR],
        );
    }

    public function logException(\Throwable $exception): void
    {
        $this->logErrorMessage(
            \sprintf('Uncaught Exception %s', \get_class($exception)),
            \sprintf('%s in %s at line %s', $exception->getMessage(), $exception->getFile(), $exception->getLine()),
            $this->getTraceAsString($exception->getTrace()),
            self::ERROR_LEVEL_MAP[E_ERROR],
        );
    }

    public function outputExceptionMessage(\Throwable $exception): void
    {
        $this->outputDebugMessage(
            \sprintf('Uncaught Exception %s', \get_class($exception)),
            \sprintf('%s in %s at line %s', $exception->getMessage(), $exception->getFile(), $exception->getLine()),
            self::ERROR_LEVEL_MAP[E_ERROR],
        );
    }

    /**
     * Log message related to error.
     *
     * @param string $type
     * @param string $description
     * @param string $trace
     * @param string $log_level
     *
     * @return void
     */
    private function logErrorMessage(string $type, string $description, string $trace, string $log_level): void
    {
        if (!($this->logger instanceof LoggerInterface)) {
            return;
        }

        try {
            $this->logger->log(
                $log_level,
                '  *** ' . $type . ': ' . $description . (!empty($trace) ? "\n" . $trace : '')
            );
        } catch (\Throwable $e) {
            $this->outputDebugMessage(
                'Error',
                'An error has occurred, but the trace of this error could not recorded because of a problem accessing the log file.',
                LogLevel::CRITICAL,
                true // Force output to warn administrator there is something wrong with the error log
            );
        }
    }

    /**
     * Output debug message related to error.
     *
     * @param string  $error_type
     * @param string  $message
     * @param string  $log_level
     * @param boolean $force
     *
     * @return void
     */
    private function outputDebugMessage(string $error_type, string $message, string $log_level, bool $force = false): void
    {
        if ($this->output_disabled) {
            return;
        }

        $is_dev_env         = $this->env === GLPI::ENV_DEVELOPMENT;
        $is_debug_mode      = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE;
        $is_console_context = $this->output_handler instanceof OutputInterface;

        if (
            !$force
            && !$is_dev_env          // error messages are always output in development environment
            && !$is_debug_mode       // error messages are always output in debug mode
            && !$is_console_context  // error messages are always forwarded to the console output handler, that handles itself the verbosity level
        ) {
            return;
        }

        if ($this->output_handler instanceof OutputInterface) {
            $format = 'comment';
            switch ($log_level) {
                case LogLevel::EMERGENCY:
                case LogLevel::ALERT:
                case LogLevel::CRITICAL:
                case LogLevel::ERROR:
                    $format    = 'error';
                    $verbosity = OutputInterface::VERBOSITY_QUIET;
                    break;
                case LogLevel::WARNING:
                    $verbosity = OutputInterface::VERBOSITY_NORMAL;
                    break;
                case LogLevel::NOTICE:
                case LogLevel::INFO:
                default:
                    $verbosity = OutputInterface::VERBOSITY_VERBOSE;
                    break;
                case LogLevel::DEBUG:
                    $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
                    break;
            }
            $message = $error_type . ': ' . $message;
            if (null !== $format) {
                $message = sprintf('<%1$s>%2$s</%1$s>', $format, $message);
            }
            $this->output_handler->writeln($message, $verbosity);
        } else if (!isCommandLine()) {
            echo '<div class="alert alert-important alert-danger glpi-debug-alert" style="z-index:10000">'
            . '<span class="b">' . \htmlescape($error_type) . ': </span>' . \htmlescape($message) . '</div>';
        } else {
            echo $error_type . ': ' . $message . "\n";
        }
    }

    /**
     * Get error type as string from error code.
     *
     * @param int $error_code
     *
     * @return string
     */
    private function codeToString(int $error_code): string
    {
        $map = [
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
            2048                => 'Runtime Notice', // 2048 = deprecated E_STRICT
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_DEPRECATED        => 'Deprecated function',
            E_USER_DEPRECATED   => 'User deprecated function',
        ];

        return $map[$error_code] ?? 'Unknown error';
    }

    /**
     * Get trace as string.
     *
     * @param array $trace
     *
     * @return string
     */
    private function getTraceAsString(array $trace): string
    {
        if (empty($trace)) {
            return '';
        }

        $message = "  Backtrace :\n";

        foreach ($trace as $item) {
            $script = ($item['file'] ?? '') . ':' . ($item['line'] ?? '');
            if (strpos($script, GLPI_ROOT) === 0) {
                $script = substr($script, strlen(GLPI_ROOT) + 1);
            }
            if (strlen($script) > 50) {
                $script = '...' . substr($script, -47);
            } else {
                $script = str_pad($script, 50);
            }

            $call = ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? '');
            if (!empty($call)) {
                $call .= '()';
            }
            $message .= "  $script $call\n";
        }

        return $message;
    }
}
