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
use Glpi\Error\ErrorDisplayHandler\CliDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\HtmlErrorDisplayHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\ErrorHandler\ErrorHandler as BaseErrorHandler;

/**
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
final class ErrorHandler extends BaseErrorHandler
{
    /**
     * Errors that will be converted to exceptions.
     */
    public const FATAL_ERRORS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;

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
        E_RECOVERABLE_ERROR => LogLevel::ERROR,
        E_DEPRECATED        => LogLevel::INFO,
        E_USER_DEPRECATED   => LogLevel::INFO,
    ];

    private static LoggerInterface $currentLogger;

    private string $env;

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct(debug: \GLPI_ENVIRONMENT_TYPE === GLPI::ENV_DEVELOPMENT);

        $this->env = \GLPI_ENVIRONMENT_TYPE;

        $this->scopeAt(E_ALL, true); // Preserve variables for all errors
        $this->traceAt(E_ALL, true); // Preserve stack trace for all errors
        $this->screamAt(self::FATAL_ERRORS, true); // Never silent fatal errors
        $this->throwAt(self::FATAL_ERRORS, true); // Convert fatal errors to exceptions

        $logger ??= new NullLogger();
        $this->setDefaultLogger($logger, self::ERROR_LEVEL_MAP);

        self::$currentLogger = $logger;

        $this->configureErrorReporting();
        $this->disableNativeErrorDisplaying();
    }

    /**
     * @return array<\Glpi\Error\ErrorDisplayHandler\ErrorDisplayHandler>
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
            new CliDisplayHandler(),
        ];
    }

    /**
     * Display a message corresponding to the given error.
     */
    public static function displayErrorMessage(string $error_label, string $message, string $log_level): void
    {
        foreach (self::getOutputHandlers() as $handler) {
            if ($handler->canOutput()) {
                $handler->displayErrorMessage($error_label, $message, $log_level);
                break; // Only one display per handler
            }
        }
    }

    #[\Override()]
    public function handleError(int $type, string $message, string $file, int $line): bool
    {
        if (0 === (error_reporting() & $type)) {
            // GLPI does not log silented errors, they should just be ignored.
            return true;
        }

        // Append the error location to the error message the same way it is done in
        // `\Symfony\Component\HttpKernel\EventListener\ErrorListener::logKernelException()`
        $message .= sprintf(' at %s line %s', basename($file), $line);

        parent::handleError($type, $message, $file, $line);

        $error_type = match ($type) {
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
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_DEPRECATED        => 'Deprecated function',
            E_USER_DEPRECATED   => 'User deprecated function',
            default             => 'Unknown error',
        };

        self::displayErrorMessage(
            \sprintf('PHP %s (%s)', $error_type, $type),
            \sprintf('%s in %s at line %s', $this->cleanPaths($message), $this->cleanPaths($file), $line),
            self::ERROR_LEVEL_MAP[$type],
        );

        // Never forward any error to the PHP native PHP error handler.
        // We do not want the native PHP error handler to output anything, and we already logged errors that are supposed
        // to be logged in the GLPI error log.
        return true;
    }

    #[\Override()]
    public function handleException(\Throwable $exception): void
    {
        // /!\ Once the kernel is booted, the `\Symfony\Component\HttpKernel\EventListener\ErrorListener`
        // will handle the exceptions via the `kernel.exception` event.
        //
        // Therefore current handler is only effective for exception that are thrown before the complete kernel boot.

        parent::handleException($exception);
    }

    /**
     * Log an exception previously caught.
     *
     * @FIXME Can be done directly in the caller class if the `logger` service is set by the DI system.
     */
    public static function logCaughtException(\Throwable $exception): void
    {
        $message = \sprintf(
            'Caught %s: %s',
            $exception::class,
            $exception->getMessage(),
        );

        self::$currentLogger->error($message, ['exception' => $exception]);
    }

    /**
     * Displays an error message corresponding to a caught exception.
     *
     * @FIXME Usages of this method should be reviewed to display a message that make sense for the end-user
     *        (e.g. "An error occurred during the operation, please try again later")
     *        rather than this generic error message.
     *        Indeed, if the developer caught this exception, then it probably means that it is a legitimate case and
     *        a custom message should then be displayed, to indicate to the end user what happens or what he can
     *        do to fix this error.
     */
    public static function displayCaughtExceptionMessage(\Throwable $exception): void
    {
        self::displayErrorMessage(
            \sprintf('Caught %s', $exception::class),
            \sprintf('%s in %s at line %s', self::cleanPaths($exception->getMessage()), self::cleanPaths($exception->getFile()), $exception->getLine()),
            LogLevel::ERROR,
        );
    }

    /**
     * Adjust reporting level to the environment, to ensure that all the errors supposed to be logged are
     * actually reported, and to prevent reporting other errors.
     */
    private function configureErrorReporting(): void
    {
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
    }

    /**
     * Disable native error displaying.
     * It will be handled by `self::displayErrorMessage()`.
     */
    private function disableNativeErrorDisplaying(): void
    {
        \ini_set('display_errors', 'Off');
    }

    private static function cleanPaths(string $message): string
    {
        return ErrorUtils::cleanPaths($message);
    }
}
