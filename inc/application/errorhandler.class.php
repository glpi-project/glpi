<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Application;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 9.5.0
 */
class ErrorHandler {

   /**
    * Map between error codes and log levels.
    *
    * @var array
    */
   const ERROR_LEVEL_MAP = [
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
      E_STRICT            => LogLevel::NOTICE,
      E_RECOVERABLE_ERROR => LogLevel::ERROR,
      E_DEPRECATED        => LogLevel::NOTICE,
      E_USER_DEPRECATED   => LogLevel::NOTICE,
   ];

   /**
    * Fatal errors list.
    *
    * @var array
    */
   const FATAL_ERRORS = [
      E_ERROR,
      E_PARSE,
      E_CORE_ERROR,
      E_COMPILE_ERROR,
      E_USER_ERROR,
      E_RECOVERABLE_ERROR,
   ];

   /**
    * Exit code to use on shutdown.
    *
    * @var int|null
    */
   private $exit_code = null;

   /**
    * Flag to indicate if error should be forwarded to PHP internal error handler.
    *
    * @var boolean
    */
   private $forward_to_internal_handler = true;

   /**
    * Logger instance.
    *
    * @var LoggerInterface
    */
   private $logger;

   /**
    * Last fatal error trace.
    *
    * @var string
    */
   private $last_fatal_trace;

   /**
    * Output handler to use. If not set, output will be directly echoed on a format depending on
    * execution context (Web VS CLI).
    *
    * @var OutputInterface|null
    */
   private $output_handler;

   /**
    * Reserved memory that will be used in case of an "out of memory" error.
    *
    * @var string
    */
   private $reserved_memory;

   /**
    * @param LoggerInterface|null $logger
    */
   public function __construct(LoggerInterface $logger = null) {
      $this->logger = $logger;
   }

   /**
    * Defines output handler.
    *
    * @param OutputInterface $output_handler
    *
    * @return void
    */
   public function setOutputHandler(OutputInterface $output_handler) {
      $this->output_handler = $output_handler;
   }

   /**
    * Register error handler callbacks.
    *
    * @return void
    */
   public function register() {
      set_error_handler([$this, 'handleError']);
      set_exception_handler([$this, 'handleException']);
      register_shutdown_function([$this, 'handleFatalError']);
      $this->reserved_memory = str_repeat('x', 50 * 1024); // reserve 50 kB of memory space
   }

   /**
    * Error handler.
    *
    * @param integer $error_code
    * @param string  $error_message
    * @param string  $filename
    * @param integer $line_number
    *
    * @return boolean
    */
   public function handleError($error_code, $error_message, $filename, $line_number) {

      // Have to false to forward to PHP internal error handler.
      $return = !$this->forward_to_internal_handler;

      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      array_shift($trace);  // Remove current method from trace
      $error_trace = $this->getTraceAsString($trace);

      if (in_array($error_code, self::FATAL_ERRORS)) {
         // Fatal errors are handled by shutdown function
         // (as some are not recoverable and cannot be handled here).
         // Store backtrace to be able to use it there.
         $this->last_fatal_trace = $error_trace;
         return $return;
      }

      if (!(error_reporting() & $error_code)) {
         // Do not handle error if '@' operator is used on errored expression
         // see https://www.php.net/manual/en/language.operators.errorcontrol.php
         return $return;
      }

      $error_type = sprintf(
         'PHP %s (%s)',
         $this->codeToString($error_code),
         $error_code
      );
      $error_description = sprintf(
         '%s in %s at line %s',
         $error_message,
         $filename,
         $line_number
      );

      $log_level = self::ERROR_LEVEL_MAP[$error_code];

      $this->logErrorMessage($error_type, $error_description, $error_trace, $log_level);
      $this->outputDebugMessage($error_type, $error_description, $log_level);

      return $return;
   }

   /**
    * SQL error handler.
    *
    * This handler is manually by application when a SQL error occured.
    *
    * @param integer $error_code
    * @param string  $error_message
    * @param string  $query
    *
    * @return void
    */
   public function handleSqlError($error_code, $error_message, $query) {
      $this->outputDebugMessage(
         sprintf('SQL Error "%s"', $error_code),
         sprintf('%s in query "%s"', $error_message, $query),
         self::ERROR_LEVEL_MAP[E_USER_ERROR],
         isCommandLine()
      );
   }

   /**
    * Exception handler.
    *
    * This handler is called by PHP prior to exiting, when an Exception is not catched.
    *
    * @param \Throwable $exception
    *
    * @return void
    */
   public function handleException(\Throwable $exception) {
      $this->exit_code = 255;

      $error_type = sprintf(
         'Uncaught Exception %s',
         get_class($exception)
      );
      $error_description = sprintf(
         '%s in %s at line %s',
         $exception->getMessage(),
         $exception->getFile(),
         $exception->getLine()
      );
      $error_trace = $this->getTraceAsString($exception->getTrace());

      $log_level = self::ERROR_LEVEL_MAP[E_ERROR];

      $this->logErrorMessage($error_type, $error_description, $error_trace, $log_level);
      $this->outputDebugMessage($error_type, $error_description, $log_level, isCommandLine());
   }

   /**
    * Handle fatal errors.
    *
    * @retun void
    */
   public function handleFatalError() {
      // Free reserved memory to be able to handle "out of memory" errors
      $this->reserved_memory = null;

      $error = error_get_last();
      if ($error && in_array($error['type'], self::FATAL_ERRORS)) {
         $this->exit_code = 255;

         $error_type = sprintf(
            'PHP %s (%s)',
            $this->codeToString($error['type']),
            $error['type']
         );
         $error_description = sprintf(
            '%s in %s at line %s',
            $error['message'],
            $error['file'],
            $error['line']
         );

         // debug_backtrace is not available in shutdown function
         // so get stored trace if any exists
         $error_trace = $this->last_fatal_trace ?? '';

         $log_level = self::ERROR_LEVEL_MAP[$error['type']];

         $this->logErrorMessage($error_type, $error_description, $error_trace, $log_level);
         $this->outputDebugMessage($error_type, $error_description, $log_level);
      }

      if ($this->exit_code !== null) {
         // If an exit code is defined, register a shutdown function that will be called after
         // thoose that are already defined, in order to exit the script with the correct code.
         $exit_code = $this->exit_code;
         register_shutdown_function(
            'register_shutdown_function',
            function () use ($exit_code) { exit($exit_code); }
         );
      }
   }

   /**
    * Defines if errors should be forward to PHP internal error handler.
    *
    * @param bool $forward_to_internal_handler
    *
    * @return void
    */
   public function setForwardToInternalHandler(bool $forward_to_internal_handler) {
      $this->forward_to_internal_handler = $forward_to_internal_handler;
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
   private function logErrorMessage(string $type, string $description, string $trace, string $log_level) {
      if (!($this->logger instanceof LoggerInterface)) {
         return;
      }

      $this->logger->log(
         $log_level,
         '  *** ' . $type . ': ' . $description . (!empty($trace) ? "\n" . $trace : '')
      );
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
   private function outputDebugMessage(string $error_type, string $message, string $log_level, bool $force = false) {

      if ((!$force
          && (!isset($_SESSION['glpi_use_mode']) || $_SESSION['glpi_use_mode'] != \Session::DEBUG_MODE)) || isAPI()) {
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
         echo '<div style="position:float-left; background-color:red; z-index:10000">'
            . '<span class="b">' . $error_type . ': </span>' . $message . '</div>';
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
   private function codeToString(int $error_code): string {
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
         E_STRICT            => 'Runtime Notice',
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
   private function getTraceAsString(array $trace): string {
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
