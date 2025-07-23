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

use Glpi\Application\Environment;
use Glpi\Error\ErrorDisplayHandler\CliDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\ConsoleErrorDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\ErrorDisplayHandler;
use Glpi\Error\ErrorDisplayHandler\HtmlErrorDisplayHandler;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\ErrorHandler\ErrorHandler as BaseErrorHandler;
use Throwable;

use function Safe\ini_set;

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

    private const PSR_ERROR_LEVEL_VALUES = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    /**
     * Indicates whether the error messages should be buffered instead of being displayed immediately.
     */
    private static bool $is_buffer_active = true;

    /**
     * @var list<array{error_label: string, message: string, log_level: string}>
     */
    private static array $buffered_messages = [];

    private static LoggerInterface $currentLogger;

    public function __construct(LoggerInterface $logger)
    {
        $env = Environment::get();
        parent::__construct(debug: $env->shouldEnableExtraDevAndDebugTools());

        $this->scopeAt(E_ALL, true); // Preserve variables for all errors
        $this->traceAt(E_ALL, true); // Preserve stack trace for all errors
        $this->screamAt(self::FATAL_ERRORS, true); // Never silent fatal errors
        $this->throwAt(self::FATAL_ERRORS, true); // Convert fatal errors to exceptions

        $this->setDefaultLogger($logger, self::ERROR_LEVEL_MAP);

        self::$currentLogger = $logger;

        $this->configureErrorReporting();
        $this->disableNativeErrorDisplaying();
    }

    /**
     * Disable the message buffer and flush the messages present in the buffer.
     */
    public static function disableBufferAndFlushMessages(): void
    {
        self::$is_buffer_active = false;

        foreach (self::$buffered_messages as $key => $message_specs) {
            self::displayErrorMessage($message_specs['error_label'], $message_specs['message'], $message_specs['log_level']);
            unset(self::$buffered_messages[$key]);
        }
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
            new CliDisplayHandler(),
        ];
    }

    /**
     * Display a message corresponding to the given error.
     */
    public static function displayErrorMessage(string $error_label, string $message, string $log_level): void
    {
        if (self::$is_buffer_active) {
            self::$buffered_messages[] = [
                'error_label' => $error_label,
                'message'     => $message,
                'log_level'   => $log_level,
            ];
            return;
        }

        foreach (self::getOutputHandlers() as $handler) {
            if ($handler->canOutput()) {
                $handler->displayErrorMessage($error_label, $message, $log_level);
                break; // Only one display per handler
            }
        }
    }

    #[Override()]
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
            \sprintf('%s in %s at line %s', self::cleanPaths($message), self::cleanPaths($file), $line),
            self::ERROR_LEVEL_MAP[$type],
        );

        // Never forward any error to the PHP native PHP error handler.
        // We do not want the native PHP error handler to output anything, and we already logged errors that are supposed
        // to be logged in the GLPI error log.
        return true;
    }

    #[Override()]
    public function handleException(Throwable $exception): void
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
    public static function logCaughtException(Throwable $exception): void
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
    public static function displayCaughtExceptionMessage(Throwable $exception): void
    {
        self::displayErrorMessage(
            \sprintf('Caught %s', $exception::class),
            \sprintf('%s in %s at line %s', self::cleanPaths($exception->getMessage()), self::cleanPaths($exception->getFile()), $exception->getLine()),
            LogLevel::ERROR,
        );
    }

    /**
     * Adjust reporting level to the environment, to ensure that all the errors
     * supposed to be logged are actually reported, and to prevent reporting
     * other errors.
     */
    private function configureErrorReporting(): void
    {
        // Define base reporting level
        $reporting_level = E_ALL;

        // Compute max error level that should be reported
        $env_report_value = self::PSR_ERROR_LEVEL_VALUES[GLPI_LOG_LVL];

        foreach (self::ERROR_LEVEL_MAP as $value => $log_level) {
            $psr_level_value = self::PSR_ERROR_LEVEL_VALUES[$log_level];

            // Error must be removed from the reporting level if its level
            // is superior to the max level defined by the current env.
            if ($psr_level_value > $env_report_value) {
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
        ini_set('display_errors', 'Off');
    }

    private static function cleanPaths(string $message): string
    {
        return ErrorUtils::cleanPaths($message);
    }
}
