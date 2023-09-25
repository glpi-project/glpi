<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Console\Application;
use Glpi\Event;
use Glpi\Mail\Protocol\ProtocolInterface;
use Glpi\Rules\RulesManager;
use Glpi\Toolbox\Sanitizer;
use Glpi\Toolbox\VersionParser;
use Laminas\Mail\Storage\AbstractStorage;
use Mexitek\PHPColors\Color;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Toolbox Class
 **/
class Toolbox
{
    /**
     * Wrapper for max_input_vars
     *
     * @since 0.84
     *
     * @return integer
     **/
    public static function get_max_input_vars()
    {

        $max = ini_get('max_input_vars');  // Security limit since PHP 5.3.9
        if (!$max) {
            $max = ini_get('suhosin.post.max_vars');  // Security limit from Suhosin
        }
        return $max;
    }


    /**
     * Convert first caracter in upper
     *
     * @since 0.83
     * @since 9.3 Rework
     *
     * @param string $str  string to change
     *
     * @return string
     **/
    public static function ucfirst($str)
    {
        $first_letter = mb_strtoupper(mb_substr($str, 0, 1));
        $str_end = mb_substr($str, 1, mb_strlen($str));
        return $first_letter . $str_end;
    }


    /**
     * to underline shortcut letter
     *
     * @since 0.83
     *
     * @param string $str       from dico
     * @param string $shortcut  letter of shortcut
     *
     * @return string
     **/
    public static function shortcut($str, $shortcut)
    {

        $pos = self::strpos(self::strtolower($str), self::strtolower($shortcut));
        if ($pos !== false) {
            return self::substr($str, 0, $pos) .
                "<u>" . self::substr($str, $pos, 1) . "</u>" .
                self::substr($str, $pos + 1);
        }
        return $str;
    }


    /**
     * substr function for utf8 string
     *
     * @param string  $str      string
     * @param string  $tofound  string to found
     * @param integer $offset   The search offset. If it is not specified, 0 is used.
     *
     * @return integer|false
     **/
    public static function strpos($str, $tofound, $offset = 0)
    {
        return mb_strpos($str, $tofound, $offset, "UTF-8");
    }



    /**
     *  Replace str_pad()
     *  who bug with utf8
     *
     * @param string  $input       input string
     * @param integer $pad_length  padding length
     * @param string  $pad_string  padding string
     * @param integer $pad_type    padding type
     *
     * @return string
     **/
    public static function str_pad($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT)
    {

        $diff = (strlen($input) - self::strlen($input));
        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }


    /**
     * strlen function for utf8 string
     *
     * @param string $str
     *
     * @return integer  length of the string
     **/
    public static function strlen($str)
    {
        return mb_strlen($str, "UTF-8");
    }


    /**
     * substr function for utf8 string
     *
     * @param string  $str
     * @param integer $start   start of the result substring
     * @param integer $length  The maximum length of the returned string if > 0 (default -1)
     *
     * @return string
     **/
    public static function substr($str, $start, $length = -1)
    {

        if ($length == -1) {
            $length = self::strlen($str) - $start;
        }
        return mb_substr($str, $start, $length, "UTF-8");
    }


    /**
     * strtolower function for utf8 string
     *
     * @param string $str
     *
     * @return string  lower case string
     **/
    public static function strtolower($str)
    {
        return mb_strtolower($str, "UTF-8");
    }


    /**
     * strtoupper function for utf8 string
     *
     * @param string $str
     *
     * @return string  upper case string
     **/
    public static function strtoupper($str)
    {
        return mb_strtoupper($str, "UTF-8");
    }


    /**
     * Is a string seems to be UTF-8 one ?
     *
     * @param $str string   string to analyse
     *
     * @return boolean
     **/
    public static function seems_utf8($str)
    {
        return mb_check_encoding($str, "UTF-8");
    }


    /**
     * Encode string to UTF-8
     *
     * @param string $string        string to convert
     * @param string $from_charset  original charset (if 'auto' try to autodetect)
     *
     * @return string  utf8 string
     **/
    public static function encodeInUtf8($string, $from_charset = "ISO-8859-1")
    {

        if (strcmp($from_charset, "auto") == 0) {
            $from_charset = mb_detect_encoding($string);
        }
        return mb_convert_encoding($string, "UTF-8", $from_charset);
    }


    /**
     * Decode string from UTF-8 to specified charset
     *
     * @param string $string      string to convert
     * @param string $to_charset  destination charset (default "ISO-8859-1")
     *
     * @return string  converted string
     **/
    public static function decodeFromUtf8($string, $to_charset = "ISO-8859-1")
    {
        return mb_convert_encoding($string, $to_charset, "UTF-8");
    }

    /**
     * @deprecated 10.0.0
     */
    public static function sodiumEncrypt($content, $key = null)
    {
        Toolbox::deprecated('Use "GLPIKey::encrypt()"');
        $glpikey = new GLPIKey();
        return $glpikey->encrypt($content, $key);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function sodiumDecrypt($content, $key = null)
    {
        Toolbox::deprecated('Use "GLPIKey::decrypt()"');
        $glpikey = new GLPIKey();
        return $glpikey->decrypt($content, $key);
    }


    /**
     * Prevent from XSS
     * Clean code
     *
     * @param array|string $value  item to prevent
     *
     * @return array|string  clean item
     *
     * @see unclean_cross_side_scripting_deep*
     *
     * @deprecated 10.0.0
     **/
    public static function clean_cross_side_scripting_deep($value)
    {
        Toolbox::deprecated('Use "Glpi\Toolbox\Sanitizer::encodeHtmlSpecialCharsRecursive()"');
        return Sanitizer::encodeHtmlSpecialCharsRecursive($value);
    }


    /**
     *  Invert fonction from clean_cross_side_scripting_deep
     *
     * @param array|string $value  item to unclean from clean_cross_side_scripting_deep
     *
     * @return array|string  unclean item
     *
     * @see clean_cross_side_scripting_deep()
     *
     * @deprecated 10.0.0
     **/
    public static function unclean_cross_side_scripting_deep($value)
    {
        Toolbox::deprecated('Use "Glpi\Toolbox\Sanitizer::decodeHtmlSpecialCharsRecursive()"');
        global $DB;
        return $DB->escape(Sanitizer::decodeHtmlSpecialCharsRecursive($value));
    }

    /**
     * Returns a safe configuration for htmLawed.
     *
     * @return array
     *
     * @since 9.5.4
     */
    public static function getHtmLawedSafeConfig(): array
    {
        $forbidden_elements = [
            'script',

            // header elements used to link external resources
            'link',
            'meta',

            // elements used to embed potential malicious external application
            'applet',
            'canvas',
            'embed',
            'object',

            // form elements
            'form',
            'button',
            'input',
            'select',
            'datalist',
            'option',
            'optgroup',
            'textarea',
        ];

        $config = [
            'elements'           => '* ' . implode('', array_map(fn($element) => '-' . $element, $forbidden_elements)),
            'deny_attribute'     => 'on*, srcdoc, formaction',
            'comment'            => 1, // 1: remove HTML comments (and do not display their contents)
            'cdata'              => 1, // 1: remove CDATA sections (and do not display their contents)
            'direct_list_nest'   => 1, // 1: Allow usage of ul/ol tags nested in other ul/ol tags
            'schemes'            => 'href: aim, app, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, tel, telnet, notes; *: file, http, https',
            'no_deprecated_attr' => 0, // 0: do not transform deprecated HTML attributes
        ];
        if (!GLPI_ALLOW_IFRAME_IN_RICH_TEXT) {
            $config['elements'] .= '-iframe';
        }

        return $config;
    }

    /**
     * Log in 'php-errors' all args
     *
     * @param Logger  $logger Logger instance, if any
     * @param integer $level  Log level (defaults to warning)
     * @param array   $args   Arguments (message to log, ...)
     *
     * @return void
     **/
    private static function log($logger = null, $level = Logger::WARNING, $args = null)
    {
        static $tps = 0;

        $extra = [];
        if (method_exists('Session', 'getLoginUserID')) {
            $extra['user'] = Session::getLoginUserID() . '@' . php_uname('n');
        }
        if ($tps && function_exists('memory_get_usage')) {
            $extra['mem_usage'] = number_format(microtime(true) - $tps, 3) . '", ' .
                      number_format(memory_get_usage() / 1024 / 1024, 2) . 'Mio)';
        }

        $msg = "";
        if (function_exists('debug_backtrace')) {
            $bt  = debug_backtrace();
            if (count($bt) > 2) {
                if (isset($bt[2]['class'])) {
                    $msg .= $bt[2]['class'] . '::';
                }
                $msg .= $bt[2]['function'] . '() in ';
            }
            $msg .= $bt[1]['file'] . ' line ' . $bt[1]['line'] . "\n";
        }

        if ($args == null) {
            $args = func_get_args();
        } else if (!is_array($args)) {
            $args = [$args];
        }

        foreach ($args as $arg) {
            if (is_array($arg) || is_object($arg)) {
                $msg .= str_replace("\n", "\n  ", print_r($arg, true));
            } else if (is_null($arg)) {
                $msg .= 'NULL ';
            } else if (is_bool($arg)) {
                $msg .= ($arg ? 'true' : 'false') . ' ';
            } else {
                $msg .= $arg . ' ';
            }
        }

        $tps = microtime(true);

        if ($logger === null) {
            global $PHPLOGGER;
            $logger = $PHPLOGGER;
        }

        try {
            $logger->addRecord($level, $msg, $extra);
        } catch (\Throwable $e) {
           //something went wrong, make sure logging does not cause fatal
            error_log($e);
        }

        global $SQLLOGGER;
        if (isCommandLine() && $level >= Logger::WARNING && $logger !== $SQLLOGGER) {
           // Do not output related messages to $SQLLOGGER as they are redundant with
           // output made by "ErrorHandler::handleSql*()" methods.
            echo $msg;
        }
    }

    /**
     * PHP debug log
     */
    public static function logDebug()
    {
        self::log(null, Logger::DEBUG, func_get_args());
    }

    /**
     * PHP notice log
     */
    public static function logNotice()
    {
        self::deprecated(
            'Use either native trigger_error($msg, E_USER_NOTICE) to log notices,'
            . ' either Glpi\\Application\\ErrorHandler::handleException() to log exceptions,'
            . ' either Toolbox::logInfo() or Toolbox::logDebug() to log messages not related to errors.'
        );
        self::log(null, Logger::NOTICE, func_get_args());
    }

    /**
     * PHP info log
     */
    public static function logInfo()
    {
        self::log(null, Logger::INFO, func_get_args());
    }

    /**
     * PHP warning log
     */
    public static function logWarning()
    {
        self::deprecated(
            'Use either native trigger_error($msg, E_USER_WARNING) to log warnings,'
            . ' either Glpi\\Application\\ErrorHandler::handleException() to log exceptions,'
            . ' either Toolbox::logInfo() or Toolbox::logDebug() to log messages not related to errors.'
        );
        self::log(null, Logger::WARNING, func_get_args());
    }

    /**
     * PHP error log
     */
    public static function logError()
    {
        self::deprecated(
            'Use either native trigger_error($msg, E_USER_WARNING) to log errors,'
            . ' either Glpi\\Application\\ErrorHandler::handleException() to log exceptions,'
            . ' either Toolbox::logInfo() or Toolbox::logDebug() to log messages not related to errors.'
        );
        self::log(null, Logger::ERROR, func_get_args());
    }

    /**
     * SQL debug log
     */
    public static function logSqlDebug()
    {
        global $SQLLOGGER;
        $args = func_get_args();
        self::log($SQLLOGGER, Logger::DEBUG, $args);
    }

    /**
     * SQL warning log
     */
    public static function logSqlWarning()
    {
        global $SQLLOGGER;
        $args = func_get_args();
        self::log($SQLLOGGER, Logger::WARNING, $args);
    }

    /**
     * SQL error log
     */
    public static function logSqlError()
    {
        global $SQLLOGGER;
        $args = func_get_args();
        self::log($SQLLOGGER, Logger::ERROR, $args);
    }


    /**
     * Generate a Backtrace
     *
     * @param string $log  Log file name (default php-errors) if false, return the string
     * @param string $hide Call to hide (but display script/line)
     * @param array  $skip Calls to not display at all
     *
     * @return string
     *
     * @since 0.85
     **/
    public static function backtrace($log = 'php-errors', $hide = '', array $skip = [])
    {

        if (function_exists("debug_backtrace")) {
            $message = "  Backtrace :\n";
            $traces  = debug_backtrace();
            foreach ($traces as $trace) {
                $script = (isset($trace["file"]) ? $trace["file"] : "") . ":" .
                        (isset($trace["line"]) ? $trace["line"] : "");
                if (strpos($script, GLPI_ROOT) === 0) {
                    $script = substr($script, strlen(GLPI_ROOT) + 1);
                }
                if (strlen($script) > 50) {
                    $script = "..." . substr($script, -47);
                } else {
                    $script = str_pad($script, 50);
                }
                $call = (isset($trace["class"]) ? $trace["class"] : "") .
                    (isset($trace["type"]) ? $trace["type"] : "") .
                    (isset($trace["function"]) ? $trace["function"] . "()" : "");
                if ($call == $hide) {
                    $call = '';
                }

                if (!in_array($call, $skip)) {
                    $message .= "  $script $call\n";
                }
            }
        } else {
            $message = "  Script : " . $_SERVER["SCRIPT_FILENAME"] . "\n";
        }

        if ($log) {
            self::logInFile($log, $message, true);
        }

        return $message;
    }

    /**
     * Send a deprecated message in log (with backtrace)
     * @param  string $message the message to send
     * @return void
     */
    public static function deprecated($message = "Called method is deprecated")
    {
        trigger_error($message, E_USER_DEPRECATED);
    }


    /**
     * Log a message in log file
     *
     * @param string  $name   name of the log file
     * @param string  $text   text to log
     * @param boolean $force  force log in file not seeing use_log_in_files config
     *
     * @return boolean
     **/
    public static function logInFile($name, $text, $force = false)
    {
        global $CFG_GLPI;

        $user = '';
        if (method_exists('Session', 'getLoginUserID')) {
            $user = " [" . Session::getLoginUserID() . '@' . php_uname('n') . "]";
        }

        $ok = true;
        if (
            (isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"])
            || $force
        ) {
            $ok = error_log(date("Y-m-d H:i:s") . "$user\n" . $text, 3, GLPI_LOG_DIR . "/" . $name . ".log");
        }

        global $application;
        if ($application instanceof Application) {
            $application->getOutput()->writeln('<comment>' . $text . '</comment>', OutputInterface::VERBOSITY_VERY_VERBOSE);
        } else if (
            isset($_SESSION['glpi_use_mode'])
            && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
            && isCommandLine()
        ) {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, $text);
            fclose($stderr);
        }
        return $ok;
    }


    /**
     * Switch error mode for GLPI
     *
     * @param integer|null $mode       From Session::*_MODE
     * @param boolean|null $debug_sql
     * @param boolean|null $debug_vars
     * @param boolean|null $log_in_files
     *
     * @return void
     *
     * @since 0.84
     **/
    public static function setDebugMode($mode = null, $debug_sql = null, $debug_vars = null, $log_in_files = null)
    {
        global $CFG_GLPI;

        if (isset($mode)) {
            $_SESSION['glpi_use_mode'] = $mode;
        }
        //FIXME Deprecate the debug_sql and debug_vars parameters in GLPI 10.1.0
        if (isset($debug_sql)) {
            $CFG_GLPI['debug_sql'] = $debug_sql;
        }
        if (isset($debug_vars)) {
            $CFG_GLPI['debug_vars'] = $debug_vars;
        }
        if (isset($log_in_files)) {
            $CFG_GLPI['use_log_in_files'] = $log_in_files;
        }

       // If debug mode activated : display some information
        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
           // Force reporting of all errors
            error_reporting(E_ALL);
           // Disable native error displaying as it will be done by custom handler
            ini_set('display_errors', 'Off');
        }
    }


    /**
     * Send a file (not a document) to the navigator
     * See Document->send();
     *
     * @param string      $file        storage filename
     * @param string      $filename    file title
     * @param string|null $mime        file mime type
     * @param boolean     $add_expires add expires headers maximize cacheability ?
     *
     * @return void
     **/
    public static function sendFile($file, $filename, $mime = null, $expires_headers = false)
    {

       // Test securite : document in DOC_DIR
        $tmpfile = str_replace(GLPI_DOC_DIR, "", $file);

        if (strstr($tmpfile, "../") || strstr($tmpfile, "..\\")) {
            Event::log(
                $file,
                "sendFile",
                1,
                "security",
                $_SESSION["glpiname"] . " try to get a non standard file."
            );
            echo "Security attack!!!";
            die(1);
        }

        if (!file_exists($file)) {
            echo "Error file $file does not exist";
            die(1);
        }

       // if $mime is defined, ignore mime type by extension
        if ($mime === null && preg_match('/\.(...)$/', $file)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file);
            finfo_close($finfo);
        }

       // don't download picture files, see them inline
        $attachment = "";
       // if not begin 'image/'
        if (
            strncmp($mime, 'image/', 6) !== 0
            && $mime != 'application/pdf'
            // svg vector of attack, force attachment
            // see https://github.com/glpi-project/glpi/issues/3873
            || $mime == 'image/svg+xml'
        ) {
            $attachment = ' attachment;';
        }

        $etag = md5_file($file);
        $lastModified = filemtime($file);

        // Make sure there is nothing in the output buffer (In case stuff was added by core or misbehaving plugin).
        // If there is any extra data, the sent file will be corrupted.
        // 1. Turn off any extra buffering level. Keep one buffering level if PHP output_buffering directive is not "off".
        $ob_config = ini_get('output_buffering');
        $max_buffering_level = $ob_config !== false && (strtolower($ob_config) === 'on' || (is_numeric($ob_config) && (int)$ob_config > 0))
            ? 1
            : 0;
        while (ob_get_level() > $max_buffering_level) {
            ob_end_clean();
        }
        // 2. Clean any buffered output in remaining level (output_buffering="on" case).
        if (ob_get_level() > 0) {
            ob_clean();
        }

        // Now send the file with header() magic
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
        header("Etag: $etag");
        header_remove('Pragma');
        header('Cache-Control: private');
        if ($expires_headers) {
            $max_age = WEEK_TIMESTAMP;
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
        }
        header(
            "Content-disposition:$attachment filename=\"" .
            addslashes(mb_convert_encoding($filename, 'ISO-8859-1', 'UTF-8')) .
            "\"; filename*=utf-8''" .
            rawurlencode($filename)
        );
        header("Content-type: " . $mime);

       // HTTP_IF_NONE_MATCH takes precedence over HTTP_IF_MODIFIED_SINCE
       // http://tools.ietf.org/html/rfc7232#section-3.3
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304); //304 - Not Modified
            exit;
        }
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
            http_response_code(304); //304 - Not Modified
            exit;
        }

        readfile($file) or die("Error opening file $file");
    }


    /**
     *  Add slash for variable & array
     *
     * @param string|string[] $value value to add slashes
     *
     * @return string|string[]
     **/
    public static function addslashes_deep($value)
    {
        global $DB;

        $value = ((array) $value === $value)
                  ? array_map([__CLASS__, 'addslashes_deep'], $value)
                  : (is_null($value)
                       ? null : (is_resource($value) || is_object($value)
                       ? $value : $DB->escape(
                           str_replace(
                               ['&#039;', '&#39;', '&#x27;', '&apos;', '&quot;'],
                               ["'", "'", "'", "'", "\""],
                               $value
                           )
                       ))
                    );

        return $value;
    }


    /**
     * Strip slash  for variable & array
     *
     * @param array|string $value  item to stripslashes
     *
     * @return array|string stripslashes item
     **/
    public static function stripslashes_deep($value)
    {

        $value = ((array) $value === $value)
                  ? array_map([__CLASS__, 'stripslashes_deep'], $value)
                  : (is_null($value)
                        ? null : (is_resource($value) || is_object($value)
                                    ? $value : stripslashes($value)));

        return $value;
    }

    /** Converts an array of parameters into a query string to be appended to a URL.
     *
     * @param array  $array      parameters to append to the query string.
     * @param string $separator  separator may be defined as &amp; to display purpose
     * @param string $parent     This should be left blank (it is used internally by the function).
     *
     * @return string  Query string to append to a URL.
     **/
    public static function append_params($array, $separator = '&', $parent = '')
    {

        $params = [];
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $params[] = self::append_params(
                    $v,
                    $separator,
                    (empty($parent) ? rawurlencode($k)
                    : $parent . '%5B' . rawurlencode($k) . '%5D')
                );
            } else {
                $params[] = (!empty($parent) ? $parent . '%5B' . rawurlencode($k) . '%5D' : rawurlencode($k)) . '=' . rawurlencode($v);
            }
        }
        //Remove empty values
        $params = array_filter($params);
        return implode($separator, $params);
    }


    /**
     * Compute PHP memory_limit
     *
     * @param string $ininame  name of the ini ooption to retrieve (since 9.1)
     *
     * @return integer memory limit
     **/
    public static function getMemoryLimit($ininame = 'memory_limit')
    {

        $mem = ini_get($ininame);
        $matches = [];
        preg_match("/([-0-9]+)([KMG]*)/", $mem, $matches);
        $mem = "";

       // no K M or G
        if (isset($matches[1])) {
            $mem = $matches[1];
            if (isset($matches[2])) {
                switch ($matches[2]) {
                    case "G":
                        $mem *= 1024;
                        // nobreak;

                    case "M":
                        $mem *= 1024;
                        // nobreak;

                    case "K":
                        $mem *= 1024;
                        // nobreak;
                }
            }
        }

        return $mem;
    }


    /**
     * Check is current memory_limit is enough for GLPI
     *
     * @since 0.83
     *
     * @return integer
     *   0 if PHP not compiled with memory_limit support,
     *   1 no memory limit (memory_limit = -1),
     *   2 insufficient memory for GLPI,
     *   3 enough memory for GLPI
     **/
    public static function checkMemoryLimit()
    {

        $mem = self::getMemoryLimit();
        if ($mem == "") {
            return 0;
        }
        if ($mem == "-1") {
            return 1;
        }
        if ($mem < (64 * 1024 * 1024)) {
            return 2;
        }
        return 3;
    }


    /**
     * Get the filesize of a complete directory (from php.net)
     *
     * @param string $path  directory or file to get size
     *
     * @return null|integer
     *
     * @deprecated 10.0.0
     **/
    public static function filesizeDirectory($path)
    {
        Toolbox::deprecated();

        if (!is_dir($path)) {
            return filesize($path);
        }

        if ($handle = opendir($path)) {
            $size = 0;

            while (false !== ($file = readdir($handle))) {
                if (($file != '.') && ($file != '..')) {
                    $size += filesize($path . '/' . $file);
                    $size += self::filesizeDirectory($path . '/' . $file);
                }
            }

            closedir($handle);
            return $size;
        }

        return null;
    }


    /** Format a size passing a size in octet
     *
     * @param integer $size  Size in octet
     *
     * @return string  formatted size
     **/
    public static function getSize($size)
    {

       //TRANS: list of unit (o for octet)
        $bytes = [__('o'), __('Kio'), __('Mio'), __('Gio'), __('Tio')];
        foreach ($bytes as $val) {
            if ($size > 1024) {
                $size = $size / 1024;
            } else {
                break;
            }
        }
       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s %2$s'), round($size, 2), $val);
    }


    /**
     * Delete a directory and file contains in it
     *
     * @param string $dir  directory to delete
     *
     * @return void
     **/
    public static function deleteDir($dir)
    {

        if (file_exists($dir)) {
            chmod($dir, 0777);

            if (is_dir($dir)) {
                $id_dir = opendir($dir);
                while (($element = readdir($id_dir)) !== false) {
                    if (($element != ".") && ($element != "..")) {
                        if (is_dir($dir . "/" . $element)) {
                            self::deleteDir($dir . "/" . $element);
                        } else {
                            unlink($dir . "/" . $element);
                        }
                    }
                }
                closedir($id_dir);
                rmdir($dir);
            } else { // Delete file
                unlink($dir);
            }
        }
    }


    /**
     * Resize a picture to the new size
     * Always produce a JPG file!
     *
     * @since 0.85
     *
     * @param string  $source_path   path of the picture to be resized
     * @param string  $dest_path     path of the new resized picture
     * @param integer $new_width     new width after resized (default 71)
     * @param integer $new_height    new height after resized (default 71)
     * @param integer $img_y         y axis of picture (default 0)
     * @param integer $img_x         x axis of picture (default 0)
     * @param integer $img_width     width of picture (default 0)
     * @param integer $img_height    height of picture (default 0)
     * @param integer $max_size      max size of the picture (default 500, is set to 0 no resize)
     *
     * @return boolean
     **/
    public static function resizePicture(
        $source_path,
        $dest_path,
        $new_width = 71,
        $new_height = 71,
        $img_y = 0,
        $img_x = 0,
        $img_width = 0,
        $img_height = 0,
        $max_size = 500
    ) {

       //get img information (dimensions and extension)
        $img_infos  = getimagesize($source_path);
        if (empty($img_width)) {
            $img_width  = $img_infos[0];
        }
        if (empty($img_height)) {
            $img_height = $img_infos[1];
        }

        if (
            empty($max_size)
            && (
                !empty($new_width)
                || !empty($new_height)
            )
        ) {
            $max_size = ($new_width > $new_height ? $new_width : $new_height);
        }

        $source_aspect_ratio = $img_width / $img_height;
        if ($source_aspect_ratio < 1) {
            $new_width  = ceil($max_size * $source_aspect_ratio);
            $new_height = $max_size;
        } else {
            $new_width  = $max_size;
            $new_height = ceil($max_size / $source_aspect_ratio);
        }

        $img_type = $img_infos[2];

        switch ($img_type) {
            case IMAGETYPE_BMP:
                $source_res = imagecreatefrombmp($source_path);
                break;

            case IMAGETYPE_GIF:
                $source_res = imagecreatefromgif($source_path);
                break;

            case IMAGETYPE_JPEG:
                $source_res = imagecreatefromjpeg($source_path);
                break;

            case IMAGETYPE_PNG:
                $source_res = imagecreatefrompng($source_path);
                break;

            case IMAGETYPE_WEBP:
                $source_res = imagecreatefromwebp($source_path);
                break;

            default:
                return false;
        }

       //create new img resource for store thumbnail
        $source_dest = imagecreatetruecolor($new_width, $new_height);

       // set transparent background for PNG/GIF/WebP
        if ($img_type === IMAGETYPE_GIF || $img_type === IMAGETYPE_PNG || $img_type === IMAGETYPE_WEBP) {
            imagecolortransparent($source_dest, imagecolorallocatealpha($source_dest, 0, 0, 0, 127));
            imagealphablending($source_dest, false);
            imagesavealpha($source_dest, true);
        }

       //resize image
        imagecopyresampled(
            $source_dest,
            $source_res,
            0,
            0,
            $img_x,
            $img_y,
            $new_width,
            $new_height,
            $img_width,
            $img_height
        );

       //output img
        $result = null;
        switch ($img_type) {
            case IMAGETYPE_GIF:
            case IMAGETYPE_PNG:
                $result = imagepng($source_dest, $dest_path);
                break;

            case IMAGETYPE_WEBP:
                $result = imagewebp($source_dest, $dest_path);
                break;

            case IMAGETYPE_JPEG:
            default:
                $result = imagejpeg($source_dest, $dest_path, 90);
                break;
        }
        return $result;
    }


    /**
     * Check if new version is available
     *
     * @return string
     **/
    public static function checkNewVersionAvailable()
    {
       //parse github releases (get last version number)
        $error = "";
        $json_gh_releases = self::getURLContent("https://api.github.com/repos/glpi-project/glpi/releases", $error);
        if (empty($json_gh_releases)) {
            return $error;
        }

        $all_gh_releases = json_decode($json_gh_releases, true);
        $released_tags = [];
        foreach ($all_gh_releases as $release) {
            if ($release['prerelease'] == false) {
                $released_tags[] =  $release['tag_name'];
            }
        }
        usort($released_tags, 'version_compare');
        $latest_version = array_pop($released_tags);

        if (strlen(trim($latest_version)) == 0) {
            return $error;
        } else {
            $currentVersion = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
            if (version_compare($currentVersion, $latest_version, '<')) {
                Config::setConfigurationValues('core', ['founded_new_version' => $latest_version]);
                return sprintf(__('A new version is available: %s.'), $latest_version);
            } else {
                return __('You have the latest available version');
            }
        }
        return 1;
    }


    /**
     * Determine if Ldap is usable checking ldap extension existence
     *
     * @return boolean
     **/
    public static function canUseLdap()
    {
        return extension_loaded('ldap');
    }


    /**
     * Determine if CAS auth is usable checking lib existence
     *
     * @since 9.3
     *
     * @return boolean
     **/
    public static function canUseCas()
    {
        return class_exists('phpCAS');
    }


    /**
     * Check Write Access to a directory
     *
     * @param string $dir  directory to check
     *
     * @return integer
     *   0: OK,
     *   1: delete error,
     *   2: creation error
     **/
    public static function testWriteAccessToDirectory($dir)
    {

        $rand = rand();

       // Check directory creation which can be denied by SElinux
        $sdir = sprintf("%s/test_glpi_%08x", $dir, $rand);

        if (!mkdir($sdir)) {
            return 4;
        }

        if (!rmdir($sdir)) {
            return 3;
        }

       // Check file creation
        $path = sprintf("%s/test_glpi_%08x.txt", $dir, $rand);
        $fp   = fopen($path, 'w');

        if (empty($fp)) {
            return 2;
        }

        fwrite($fp, "This file was created for testing reasons. ");
        fclose($fp);
        $delete = unlink($path);

        if (!$delete) {
            return 1;
        }

        return 0;
    }


    /**
     * Get form URL for itemtype
     *
     * @param string  $itemtype  item type
     * @param boolean $full      path or relative one
     *
     * return string itemtype Form URL
     **/
    public static function getItemTypeFormURL($itemtype, $full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');

        if ($plug = isPluginItemType($itemtype)) {
           /* PluginFooBar => /plugins/foo/front/bar */
            $dir .= Plugin::getPhpDir(strtolower($plug['plugin']), false);
            $item = str_replace('\\', '/', strtolower($plug['class']));
        } else { // Standard case
            $item = strtolower($itemtype);
            if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
                $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
            }
        }

        return "$dir/front/$item.form.php";
    }


    /**
     * Get search URL for itemtype
     *
     * @param string  $itemtype  item type
     * @param boolean $full      path or relative one
     *
     * return string itemtype search URL
     **/
    public static function getItemTypeSearchURL($itemtype, $full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');

        if ($plug = isPluginItemType($itemtype)) {
            $dir .= Plugin::getPhpDir(strtolower($plug['plugin']), false);
            $item = str_replace('\\', '/', strtolower($plug['class']));
        } else { // Standard case
            if ($itemtype == 'Cartridge') {
                $itemtype = 'CartridgeItem';
            }
            if ($itemtype == 'Consumable') {
                $itemtype = 'ConsumableItem';
            }
            $item = strtolower($itemtype);
            if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
                $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
            }
        }

        return "$dir/front/$item.php";
    }


    /**
     * Get ajax tabs url for itemtype
     *
     * @param string  $itemtype  item type
     * @param boolean $full      path or relative one
     *
     * return string itemtype tabs URL
     **/
    public static function getItemTypeTabsURL($itemtype, $full = true)
    {
        global $CFG_GLPI;

        $filename = "/ajax/common.tabs.php";

        return ($full ? $CFG_GLPI['root_doc'] : '') . $filename;
    }


    /**
     * Get a random string
     *
     * @param integer $length of the random string
     *
     * @return string  random string
     *
     * @see https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
     **/
    public static function getRandomString($length)
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }


    /**
     * Split timestamp in time units
     *
     * @param integer $time  timestamp
     *
     * @return array
     **/
    public static function getTimestampTimeUnits($time)
    {

        $out = [];

        $time          = round(abs($time));
        $out['second'] = 0;
        $out['minute'] = 0;
        $out['hour']   = 0;
        $out['day']    = 0;

        $out['second'] = $time % MINUTE_TIMESTAMP;
        $time         -= $out['second'];

        if ($time > 0) {
            $out['minute'] = ($time % HOUR_TIMESTAMP) / MINUTE_TIMESTAMP;
            $time         -= $out['minute'] * MINUTE_TIMESTAMP;

            if ($time > 0) {
                $out['hour'] = ($time % DAY_TIMESTAMP) / HOUR_TIMESTAMP;
                $time       -= $out['hour'] * HOUR_TIMESTAMP;

                if ($time > 0) {
                    $out['day'] = $time / DAY_TIMESTAMP;
                }
            }
        }
        return $out;
    }


    /**
     * Check an url is safe.
     * Used to mitigate SSRF exploits.
     *
     * @since 10.0.3
     *
     * @param string    $url        URL to check
     * @param array     $allowlist  Allowlist (regex array)
     *
     * @return bool
     */
    public static function isUrlSafe(string $url, array $allowlist = GLPI_SERVERSIDE_URL_ALLOWLIST): bool
    {
        foreach ($allowlist as $allow_regex) {
            $result = preg_match($allow_regex, $url);
            if ($result === false) {
                trigger_error(
                    sprintf('Unable to validate URL safeness. Following regex is probably invalid: "%s".', $allow_regex),
                    E_USER_WARNING
                );
            } elseif ($result === 1) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get a web page. Use proxy if configured
     *
     * @param string  $url    URL to retrieve
     * @param string  $msgerr set if problem encountered (default NULL)
     * @param integer $rec    internal use only Must be 0 (default 0)
     *
     * @return string content of the page (or empty)
     **/
    public static function getURLContent($url, &$msgerr = null, $rec = 0)
    {
        $curl_error = null;
        $content = self::callCurl($url, [], $msgerr, $curl_error, true);
        return $content;
    }

    /**
     * Executes a curl call
     *
     * @param string $url         URL to retrieve
     * @param array  $eopts       Extra curl opts
     * @param string $msgerr      will contains a human readable error string if an error occurs of url returns empty contents
     * @param bool   $check_url_safeness    indicated whether the URL have to be filetered by safety checks
     * @param array  $curl_info   will contains contents provided by `curl_getinfo`
     *
     * @return string
     */
    public static function callCurl(
        $url,
        array $eopts = [],
        &$msgerr = null,
        &$curl_error = null,
        bool $check_url_safeness = false,
        ?array &$curl_info = null
    ) {
        global $CFG_GLPI;

        if ($check_url_safeness && !Toolbox::isUrlSafe($url)) {
            $msgerr = sprintf(
                __('URL "%s" is not considered safe and cannot be fetched from GLPI server.'),
                $url
            );
            trigger_error(sprintf('Unsafe URL "%s" fetching has been blocked.', $url), E_USER_NOTICE);
            return '';
        }

        $content = "";
        $taburl  = parse_url($url);

        $defaultport = 80;

       // Manage standard HTTPS port : scheme detection or port 443
        if (
            (isset($taburl["scheme"]) && $taburl["scheme"] == 'https')
            || (isset($taburl["port"]) && $taburl["port"] == '443')
        ) {
            $defaultport = 443;
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => "GLPI/" . trim($CFG_GLPI["version"]),
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_CONNECTTIMEOUT  => 5,
        ] + $eopts;

        if ($check_url_safeness) {
            $opts[CURLOPT_FOLLOWLOCATION] = false;
        }

        if (!empty($CFG_GLPI["proxy_name"])) {
           // Connection using proxy
            $opts += [
                CURLOPT_PROXY           => $CFG_GLPI['proxy_name'],
                CURLOPT_PROXYPORT       => $CFG_GLPI['proxy_port'],
                CURLOPT_PROXYTYPE       => CURLPROXY_HTTP
            ];

            if (!empty($CFG_GLPI["proxy_user"])) {
                $opts += [
                    CURLOPT_PROXYAUTH    => CURLAUTH_BASIC,
                    CURLOPT_PROXYUSERPWD => $CFG_GLPI["proxy_user"] . ":" . (new GLPIKey())->decrypt($CFG_GLPI["proxy_passwd"]),
                ];
            }

            if ($defaultport == 443) {
                $opts += [
                    CURLOPT_HTTPPROXYTUNNEL => 1
                ];
            }
        }

        curl_setopt_array($ch, $opts);
        $content = curl_exec($ch);
        $curl_error = curl_error($ch) ?: null;
        $curl_info = curl_getinfo($ch);
        $curl_redirect = $curl_info['redirect_url'] ?? null;
        curl_close($ch);

        if ($curl_error !== null) {
            if (empty($CFG_GLPI["proxy_name"])) {
               //TRANS: %s is the error string
                $msgerr = sprintf(
                    __('Connection failed. If you use a proxy, please configure it. (%s)'),
                    $curl_error
                );
            } else {
               //TRANS: %s is the error string
                $msgerr = sprintf(
                    __('Failed to connect to the proxy server (%s)'),
                    $curl_error
                );
            }
            $content = '';
        } else if (!empty($curl_redirect)) {
            return self::callCurl($curl_redirect, $eopts, $msgerr, $curl_error, $check_url_safeness, $curl_info);
        } else if (empty($content)) {
            $msgerr = __('No data available on the web site');
        }
        if (!empty($msgerr)) {
            trigger_error($msgerr, E_USER_WARNING);
        }
        return $content;
    }

    /**
     * Returns whether this is an AJAX (XMLHttpRequest) request.
     *
     * @return boolean whether this is an AJAX (XMLHttpRequest) request.
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }


    /**
     * @param $need
     * @param $tab
     *
     * @return boolean
     **/
    public static function key_exists_deep($need, $tab)
    {

        foreach ($tab as $key => $value) {
            if ($need == $key) {
                return true;
            }

            if (
                is_array($value)
                && self::key_exists_deep($need, $value)
            ) {
                return true;
            }
        }
        return false;
    }


    /**
     * Manage planning posted datas (must have begin + duration or end)
     * Compute end if duration is set
     *
     * @param array $data  data to process
     *
     * @return void
     **/
    public static function manageBeginAndEndPlanDates(&$data)
    {

        if (!isset($data['end'])) {
            if (
                isset($data['begin'])
                && isset($data['_duration'])
            ) {
                $begin_timestamp = strtotime($data['begin']);
                $data['end']     = date("Y-m-d H:i:s", $begin_timestamp + $data['_duration']);
                unset($data['_duration']);
            }
        }
    }


    /**
     * Manage login redirection
     *
     * @param string $where  where to redirect ?
     *
     * @return void
     **/
    public static function manageRedirect($where)
    {
        global $CFG_GLPI;

        if (!empty($where)) {
            if (Session::getCurrentInterface()) {
                // redirect to URL : URL must be rawurlencoded
                $decoded_where = rawurldecode($where);
                $matches = [];

                // redirect to full url -> check if it's based on glpi url
                if (preg_match('@(([^:/].+:)?//[^/]+)(/.+)?@', $decoded_where, $matches)) {
                    if ($matches[1] !== $CFG_GLPI['url_base']) {
                        Session::addMessageAfterRedirect('Redirection failed');
                        if (Session::getCurrentInterface() === "helpdesk") {
                            Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.public.php");
                        } else {
                            Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
                        }
                    } else {
                        Html::redirect($decoded_where);
                    }
                }

                // Redirect to relative url
                if ($decoded_where[0] == '/') {
                    // prevent exploit (//example.com) and force a redirect from glpi root
                    $redirect_to = $CFG_GLPI["root_doc"] . "/" . ltrim($decoded_where, '/');
                    Html::redirect($redirect_to);
                }

                $data = explode("_", $where);
                $forcetab = '';
                // forcetab for simple items
                if (isset($data[2])) {
                    $forcetab = 'forcetab=' . $data[2];
                }

                switch (Session::getCurrentInterface()) {
                    case "helpdesk":
                        switch (strtolower($data[0])) {
                              // Use for compatibility with old name
                            case "tracking":
                            case "ticket":
                                $data[0] = 'Ticket';
                             // redirect to item
                                if (
                                    isset($data[1])
                                    && is_numeric($data[1])
                                    && ($data[1] > 0)
                                ) {
                                    // Check entity
                                    if (
                                        ($item = getItemForItemtype($data[0]))
                                        && $item->isEntityAssign()
                                    ) {
                                        if ($item->getFromDB($data[1])) {
                                            if (!Session::haveAccessToEntity($item->getEntityID())) {
                                                Session::changeActiveEntities($item->getEntityID(), 1);
                                            }
                                        }
                                    }
                                  // force redirect to timeline when timeline is enabled and viewing
                                  // Tasks or Followups
                                    $forcetab = str_replace('TicketFollowup$1', 'Ticket$1', $forcetab);
                                    $forcetab = str_replace('TicketTask$1', 'Ticket$1', $forcetab);
                                    $forcetab = str_replace('ITILFollowup$1', 'Ticket$1', $forcetab);
                                    Html::redirect(Ticket::getFormURLWithID($data[1]) . "&$forcetab");
                                } else if (!empty($data[0])) { // redirect to list
                                    if ($item = getItemForItemtype($data[0])) {
                                        $searchUrl = $item->getSearchURL();
                                        $searchUrl .= strpos($searchUrl, '?') === false ? '?' : '&';
                                        $searchUrl .= $forcetab;
                                        Html::redirect($searchUrl);
                                    }
                                }

                                Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.public.php");
                                break;

                            case "preference":
                                Html::redirect($CFG_GLPI["root_doc"] . "/front/preference.php?$forcetab");
                                break;

                            case "reservation":
                                Html::redirect(Reservation::getFormURLWithID($data[1]) . "&$forcetab");
                                break;

                            default:
                                Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.public.php");
                                break;
                        }
                        break;

                    case "central":
                        switch (strtolower($data[0])) {
                            case "preference":
                                Html::redirect($CFG_GLPI["root_doc"] . "/front/preference.php?$forcetab");
                                break;

                           // Use for compatibility with old name
                           // no break
                            case "tracking":
                                $data[0] = "Ticket";
                             //var defined, use default case

                            default:
                             // redirect to item
                                if (
                                    !empty($data[0])
                                    && isset($data[1])
                                    && is_numeric($data[1])
                                    && ($data[1] > 0)
                                ) {
                                    // Check entity
                                    if ($item = getItemForItemtype($data[0])) {
                                        if ($item->isEntityAssign()) {
                                            if ($item->getFromDB($data[1])) {
                                                if (!Session::haveAccessToEntity($item->getEntityID())) {
                                                    Session::changeActiveEntities($item->getEntityID(), 1);
                                                }
                                            }
                                        }
                                    // force redirect to timeline when timeline is enabled
                                        $forcetab = str_replace('TicketFollowup$1', 'Ticket$1', $forcetab);
                                        $forcetab = str_replace('TicketTask$1', 'Ticket$1', $forcetab);
                                        $forcetab = str_replace('ITILFollowup$1', 'Ticket$1', $forcetab);
                                        Html::redirect($item->getFormURLWithID($data[1]) . "&$forcetab");
                                    }
                                } else if (!empty($data[0])) { // redirect to list
                                    if ($item = getItemForItemtype($data[0])) {
                                        $searchUrl = $item->getSearchURL();
                                        $searchUrl .= strpos($searchUrl, '?') === false ? '?' : '&';
                                        $searchUrl .= $forcetab;
                                        Html::redirect($searchUrl);
                                    }
                                }

                                Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
                                break;
                        }
                        break;
                }
            }
        }
    }


    /**
     * Convert a value in byte, kbyte, megabyte etc...
     *
     * @param string $val  config value (like 10k, 5M)
     *
     * @return integer $val
     **/
    public static function return_bytes_from_ini_vars($val)
    {

        $val  = trim($val);
        $last = self::strtolower($val[strlen($val) - 1]);
        $val  = (int)$val;

        switch ($last) {
           // Le modifieur 'G' est disponible depuis PHP 5.1.0
            case 'g':
                $val *= 1024;
                // no break;

            case 'm':
                $val *= 1024;
                // no break;

            case 'k':
                $val *= 1024;
                // no break;
        }

        return $val;
    }


    /**
     * Get max upload size from php config.
     *
     * @return int
     */
    public static function getPhpUploadSizeLimit(): int
    {
        $post_max   = Toolbox::return_bytes_from_ini_vars(ini_get("post_max_size"));
        $upload_max = Toolbox::return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
        $max_size   = $post_max > 0 ? min($post_max, $upload_max) : $upload_max;
        return $max_size;
    }

    /**
     * Parse imap open connect string
     *
     * @since 0.84
     *
     * @param string  $value      connect string
     * @param boolean $forceport  force compute port if not set
     *
     * @return array  parsed arguments (address, port, mailbox, type, ssl, tls, validate-cert
     *                norsh, secure and debug) : options are empty if not set
     *                and options have boolean values if set
     **/
    public static function parseMailServerConnectString($value, $forceport = false)
    {

        $tab = [];
        if (strstr($value, ":")) {
            $tab['address'] = str_replace("{", "", preg_replace("/:.*/", "", $value));
            $tab['port']    = preg_replace("/.*:/", "", preg_replace("/\/.*/", "", $value));
        } else {
            if (strstr($value, "/")) {
                $tab['address'] = str_replace("{", "", preg_replace("/\/.*/", "", $value));
            } else {
                $tab['address'] = str_replace("{", "", preg_replace("/}.*/", "", $value));
            }
            $tab['port'] = "";
        }
        $tab['mailbox'] = preg_replace("/.*}/", "", $value);

       // type follows first found "/" and ends on next "/" (or end of server string)
       // server string is surrounded by "{}" and can be followed by a folder name
       // i.e. "{mail.domain.org/imap/ssl}INBOX", or "{mail.domain.org/pop}"
        $type = preg_replace('/^\{[^\/]+\/([^\/]+)(?:\/.+)*\}.*/', '$1', $value);
        $tab['type'] = in_array($type, array_keys(self::getMailServerProtocols())) ? $type : '';

        $tab['ssl'] = false;
        if (strstr($value, "/ssl")) {
            $tab['ssl'] = true;
        }

        if ($forceport && empty($tab['port'])) {
            if ($tab['type'] == 'pop') {
                if ($tab['ssl']) {
                    $tab['port'] = 110;
                } else {
                    $tab['port'] = 995;
                }
            }
            if ($tab['type'] = 'imap') {
                if ($tab['ssl']) {
                    $tab['port'] = 993;
                } else {
                    $tab['port'] = 143;
                }
            }
        }
        $tab['tls'] = '';
        if (strstr($value, "/tls")) {
            $tab['tls'] = true;
        }
        if (strstr($value, "/notls")) {
            $tab['tls'] = false;
        }
        $tab['validate-cert'] = '';
        if (strstr($value, "/validate-cert")) {
            $tab['validate-cert'] = true;
        }
        if (strstr($value, "/novalidate-cert")) {
            $tab['validate-cert'] = false;
        }
        $tab['norsh'] = '';
        if (strstr($value, "/norsh")) {
            $tab['norsh'] = true;
        }
        $tab['secure'] = '';
        if (strstr($value, "/secure")) {
            $tab['secure'] = true;
        }
        $tab['debug'] = '';
        if (strstr($value, "/debug")) {
            $tab['debug'] = true;
        }

        return $tab;
    }


    /**
     * Display a mail server configuration form
     *
     * @param string $value  host connect string ex {localhost:993/imap/ssl}INBOX
     *
     * @return string  type of the server (imap/pop)
     **/
    public static function showMailServerConfig($value)
    {

        if (!Config::canUpdate()) {
            return false;
        }

        $tab = Toolbox::parseMailServerConnectString($value);

        echo "<tr class='tab_bg_1'><td>" . __('Server') . "</td>";
        echo "<td><input size='30' class='form-control' type='text' name='mail_server' value=\"" . $tab['address'] . "\">";
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('Connection options') . "</td><td>";
        $values = [];
        $protocols = Toolbox::getMailServerProtocols();
        foreach ($protocols as $key => $params) {
            $values['/' . $key] = $params['label'];
        }
        $svalue = (!empty($tab['type']) ? '/' . $tab['type'] : '');

        Dropdown::showFromArray(
            'server_type',
            $values,
            ['value'               => $svalue,
                'display_emptychoice' => true
            ]
        );
        $values = [//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/ssl' => __('SSL')
        ];

        $svalue = ($tab['ssl'] ? '/ssl' : '');

        Dropdown::showFromArray(
            'server_ssl',
            $values,
            ['value'               => $svalue,
                'display_emptychoice' => true
            ]
        );

        $values = [//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/tls' => __('TLS'),
                     //TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/notls' => __('NO-TLS'),
        ];

        $svalue = '';
        if (($tab['tls'] === true)) {
            $svalue = '/tls';
        }
        if (($tab['tls'] === false)) {
            $svalue = '/notls';
        }

        Dropdown::showFromArray(
            'server_tls',
            $values,
            ['value'               => $svalue,
                'width'               => '14%',
                'display_emptychoice' => true
            ]
        );

        $values = [//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/novalidate-cert' => __('NO-VALIDATE-CERT'),
                     //TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/validate-cert' => __('VALIDATE-CERT'),
        ];

        $svalue = '';
        if (($tab['validate-cert'] === false)) {
            $svalue = '/novalidate-cert';
        }
        if (($tab['validate-cert'] === true)) {
            $svalue = '/validate-cert';
        }

        Dropdown::showFromArray(
            'server_cert',
            $values,
            ['value'               => $svalue,
                'display_emptychoice' => true
            ]
        );

        $values = [//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/norsh' => __('NORSH')
        ];

        $svalue = ($tab['norsh'] === true ? '/norsh' : '');

        Dropdown::showFromArray(
            'server_rsh',
            $values,
            ['value'               => $svalue,
                'display_emptychoice' => true
            ]
        );

        $values = [//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/secure' => __('SECURE')
        ];

        $svalue = ($tab['secure'] === true ? '/secure' : '');

        Dropdown::showFromArray(
            'server_secure',
            $values,
            ['value'               => $svalue,
                'display_emptychoice' => true
            ]
        );

        $values = [//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
            '/debug' => __('DEBUG')
        ];

        $svalue = ($tab['debug'] === true ? '/debug' : '');

        Dropdown::showFromArray(
            'server_debug',
            $values,
            ['value'               => $svalue,
                'width'               => '12%',
                'display_emptychoice' => true
            ]
        );

        echo "<input type=hidden name=imap_string value='" . $value . "'>";
        echo "</td></tr>\n";

        if ($tab['type'] != 'pop') {
            echo "<tr class='tab_bg_1'><td>" . __('Incoming mail folder (optional, often INBOX)') . "</td>";
            echo "<td>";
            echo "<div class='btn-group btn-group-sm'>";
            echo "<input size='30' class='form-control' type='text' id='server_mailbox' name='server_mailbox' value=\"" . $tab['mailbox'] . "\" >";
            echo "<div class='btn btn-outline-secondary get-imap-folder'>";
            echo "<i class='fa fa-list pointer'></i>";
            echo "</div>";
            echo "</div></td></tr>\n";
        }

       //TRANS: for mail connection system
        echo "<tr class='tab_bg_1'><td>" . __('Port (optional)') . "</td>";
        echo "<td><input size='10' class='form-control' type='text' name='server_port' value='" . $tab['port'] . "'></td></tr>\n";
        if (empty($value)) {
            $value = "&nbsp;";
        }
       //TRANS: for mail connection system
        echo "<tr class='tab_bg_1'><td>" . __('Connection string') . "</td>";
        echo "<td class='b'>$value</td></tr>\n";

        return $tab['type'];
    }


    /**
     * @param array $input
     *
     * @return string
     **/
    public static function constructMailServerConfig($input)
    {

        $out = "";
        if (isset($input['mail_server']) && !empty($input['mail_server'])) {
            $out .= "{" . $input['mail_server'];
        } else {
            return $out;
        }
        if (isset($input['server_port']) && !empty($input['server_port'])) {
            $out .= ":" . $input['server_port'];
        }
        if (isset($input['server_type']) && !empty($input['server_type'])) {
            $out .= $input['server_type'];
        }
        if (isset($input['server_ssl']) && !empty($input['server_ssl'])) {
            $out .= $input['server_ssl'];
        }
        if (isset($input['server_cert']) && !empty($input['server_cert'])) {
            $out .= $input['server_cert'];
        }
        if (isset($input['server_tls']) && !empty($input['server_tls'])) {
            $out .= $input['server_tls'];
        }

        if (isset($input['server_rsh']) && !empty($input['server_rsh'])) {
            $out .= $input['server_rsh'];
        }
        if (isset($input['server_secure']) && !empty($input['server_secure'])) {
            $out .= $input['server_secure'];
        }
        if (isset($input['server_debug']) && !empty($input['server_debug'])) {
            $out .= $input['server_debug'];
        }
        $out .= "}";
        if (isset($input['server_mailbox']) && !empty($input['server_mailbox'])) {
            $out .= $input['server_mailbox'];
        }

        return $out;
    }

    /**
     * Retuns available mail servers protocols.
     *
     * For each returned element:
     *  - key is type used in connection string;
     *  - 'label' field is the label to display;
     *  - 'protocol_class' field is the protocol class to use (see Laminas\Mail\Protocol\Imap | Laminas\Mail\Protocol\Pop3);
     *  - 'storage_class' field is the storage class to use (see Laminas\Mail\Storage\Imap | Laminas\Mail\Storage\Pop3).
     *
     * @return array
     */
    private static function getMailServerProtocols(): array
    {
        $protocols = [
            'imap' => [
            //TRANS: IMAP mail server protocol
                'label'    => __('IMAP'),
                'protocol' => 'Laminas\Mail\Protocol\Imap',
                'storage'  => 'Laminas\Mail\Storage\Imap',
            ],
            'pop'  => [
            //TRANS: POP3 mail server protocol
                'label'    => __('POP'),
                'protocol' => 'Laminas\Mail\Protocol\Pop3',
                'storage'  => 'Laminas\Mail\Storage\Pop3',
            ]
        ];

        $additionnal_protocols = Plugin::doHookFunction('mail_server_protocols', []);
        if (is_array($additionnal_protocols)) {
            foreach ($additionnal_protocols as $key => $additionnal_protocol) {
                if (array_key_exists($key, $protocols)) {
                    trigger_error(
                        sprintf('Protocol "%s" is already defined and cannot be overwritten.', $key),
                        E_USER_WARNING
                    );
                     continue; // already exists, do not overwrite
                }

                if (
                    !array_key_exists('label', $additionnal_protocol)
                    || !array_key_exists('protocol', $additionnal_protocol)
                    || !array_key_exists('storage', $additionnal_protocol)
                ) {
                    trigger_error(
                        sprintf('Invalid specs for protocol "%s".', $key),
                        E_USER_WARNING
                    );
                    continue;
                }
                $protocols[$key] = $additionnal_protocol;
            }
        } else {
            trigger_error(
                'Invalid value returned by "mail_server_protocols" hook.',
                E_USER_WARNING
            );
        }

        return $protocols;
    }

    /**
     * Returns protocol instance for given mail server type.
     *
     * Class should implements Glpi\Mail\Protocol\ProtocolInterface
     * or should be \Laminas\Mail\Protocol\Imap|\Laminas\Mail\Protocol\Pop3 for native protocols.
     *
     * @param string $protocol_type
     *
     * @return null|\Glpi\Mail\Protocol\ProtocolInterface|\Laminas\Mail\Protocol\Imap|\Laminas\Mail\Protocol\Pop3
     */
    public static function getMailServerProtocolInstance(string $protocol_type)
    {
        $protocols = self::getMailServerProtocols();
        if (array_key_exists($protocol_type, $protocols)) {
            $protocol = $protocols[$protocol_type]['protocol'];
            if (is_callable($protocol)) {
                return call_user_func($protocol);
            } else if (
                class_exists($protocol)
                && (is_a($protocol, ProtocolInterface::class, true)
                 || is_a($protocol, \Laminas\Mail\Protocol\Imap::class, true)
                 || is_a($protocol, \Laminas\Mail\Protocol\Pop3::class, true))
            ) {
                return new $protocol();
            } else {
                trigger_error(
                    sprintf('Invalid specs for protocol "%s".', $protocol_type),
                    E_USER_WARNING
                );
            }
        }
        return null;
    }

    /**
     * Returns storage instance for given mail server type.
     *
     * Class should extends \Laminas\Mail\Storage\AbstractStorage.
     *
     * @param string $protocol_type
     * @param array  $params         Storage constructor params, as defined in AbstractStorage
     *
     * @return null|AbstractStorage
     */
    public static function getMailServerStorageInstance(string $protocol_type, array $params): ?AbstractStorage
    {
        $protocols = self::getMailServerProtocols();
        if (array_key_exists($protocol_type, $protocols)) {
            $storage = $protocols[$protocol_type]['storage'];
            if (is_callable($storage)) {
                return call_user_func($storage, $params);
            } else if (class_exists($storage) && is_a($storage, AbstractStorage::class, true)) {
                return new $storage($params);
            } else {
                trigger_error(
                    sprintf('Invalid specs for protocol "%s".', $protocol_type),
                    E_USER_WARNING
                );
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    public static function getDaysOfWeekArray()
    {

        $tab = [];

        $tab[0] = __("Sunday");
        $tab[1] = __("Monday");
        $tab[2] = __("Tuesday");
        $tab[3] = __("Wednesday");
        $tab[4] = __("Thursday");
        $tab[5] = __("Friday");
        $tab[6] = __("Saturday");

        return $tab;
    }

    /**
     * @return string[]
     */
    public static function getMonthsOfYearArray()
    {

        $tab = [];

        $tab[1]  = __("January");
        $tab[2]  = __("February");
        $tab[3]  = __("March");
        $tab[4]  = __("April");
        $tab[5]  = __("May");
        $tab[6]  = __("June");
        $tab[7]  = __("July");
        $tab[8]  = __("August");
        $tab[9]  = __("September");
        $tab[10] = __("October");
        $tab[11] = __("November");
        $tab[12] = __("December");

        return $tab;
    }


    /**
     * Do a in_array search comparing string using strcasecmp
     *
     * @since 0.84
     *
     * @param string $string  string to search
     * @param array  $data    array to search in
     *
     * @return boolean  string found ?
     **/
    public static function inArrayCaseCompare($string, $data = [])
    {

        if (count($data)) {
            foreach ($data as $tocheck) {
                if (strcasecmp($string, $tocheck) == 0) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Clean integer string value (strip all chars not - and spaces )
     *
     * @since versin 0.83.5
     *
     * @param string  $integer  integer string
     *
     * @return string  clean integer
     **/
    public static function cleanInteger($integer)
    {
        return preg_replace("/[^0-9-]/", "", (string)$integer);
    }


    /**
     * Clean decimal string value (strip all chars not - and spaces )
     *
     * @since versin 0.83.5
     *
     * @param string $decimal  float string
     *
     * @return string  clean decimal
     **/
    public static function cleanDecimal($decimal)
    {
        return preg_replace("/[^0-9\.-]/", "", $decimal);
    }


    /**
     * Clean new lines of a string
     *
     * @since versin 0.85
     *
     * @param string $string  string to clean
     *
     * @return string  clean string
     **/
    public static function cleanNewLines($string)
    {

        $string = preg_replace("/\r\n/", " ", $string);
        $string = preg_replace("/\n/", " ", $string);
        $string = preg_replace("/\r/", " ", $string);
        return $string;
    }


    /**
     * Create the GLPI default schema
     *
     * @param string  $lang Language to install
     * @param DBmysql $db   Database instance to use, will fallback to a new instance of DB if null
     *
     * @return void
     *
     * @since 9.1
     * @since 9.4.7 Added $db parameter
     **/
    public static function createSchema($lang = 'en_GB', DBmysql $database = null)
    {
        global $DB;

        if (null === $database) {
           // Use configured DB if no $db is defined in parameters
            include_once(GLPI_CONFIG_DIR . "/config_db.php");
            $database = new DB();
        }

       // Set global $DB as it is used in "Config::setConfigurationValues()" just after schema creation
        $DB = $database;

        if (!$DB->runFile(sprintf('%s/install/mysql/glpi-empty.sql', GLPI_ROOT))) {
            echo "Errors occurred inserting default database";
        } else {
           //dataset
            Session::loadLanguage($lang, false); // Load default language locales to translate empty data
            $tables = require_once(__DIR__ . '/../install/empty_data.php');
            Session::loadLanguage('', false); // Load back session language

            foreach ($tables as $table => $data) {
                $reference = array_replace(
                    $data[0],
                    array_fill_keys(
                        array_keys($data[0]),
                        new QueryParam()
                    )
                );

                $stmt = $DB->prepare($DB->buildInsert($table, $reference));
                if (false === $stmt) {
                     $msg = "Error preparing statement in table $table";
                     throw new \RuntimeException($msg);
                }

                $types = str_repeat('s', count($data[0]));
                foreach ($data as $row) {
                    $res = $stmt->bind_param($types, ...array_values($row));
                    if (false === $res) {
                        $msg = "Error binding params in table $table\n";
                        $msg .= print_r($row, true);
                        throw new \RuntimeException($msg);
                    }
                    $res = $stmt->execute();
                    if (false === $res) {
                        $msg = $stmt->error;
                        $msg .= "\nError execution statement in table $table\n";
                        $msg .= print_r($row, true);
                        throw new \RuntimeException($msg);
                    }
                    if (!isCommandLine()) {
                         // Flush will prevent proxy to timeout as it will receive data.
                         // Flush requires a content to be sent, so we sent spaces as multiple spaces
                         // will be shown as a single one on browser.
                         echo ' ';
                         Html::glpi_flush();
                    }
                }
            }

            // Initalize rules
            RulesManager::initializeRules();

           // update default language
            Config::setConfigurationValues(
                'core',
                [
                    'language'      => $lang,
                    'version'       => GLPI_VERSION,
                    'dbversion'     => GLPI_SCHEMA_VERSION,
                ]
            );

            if (defined('GLPI_SYSTEM_CRON')) {
               // Downstream packages may provide a good system cron
                $DB->updateOrDie(
                    'glpi_crontasks',
                    [
                        'mode'   => 2
                    ],
                    [
                        'name'      => ['!=', 'watcher'],
                        'allowmode' => ['&', 2]
                    ],
                    '4203'
                );
            }
        }
    }


    /**
     * Save a configuration file
     *
     * @since 0.84
     *
     * @param string $name        config file name
     * @param string $content     config file content
     * @param string $config_dir  configuration directory to write on
     *
     * @return boolean
     **/
    public static function writeConfig($name, $content, string $config_dir = GLPI_CONFIG_DIR)
    {

        $name = $config_dir . '/' . $name;
        $fp   = fopen($name, 'wt');
        if ($fp) {
            $fw = fwrite($fp, $content);
            fclose($fp);
            if (function_exists('opcache_invalidate')) {
                /* Invalidate Zend OPcache to ensure saved version used */
                opcache_invalidate($name, true);
            }
            return ($fw > 0);
        }
        return false;
    }


    /**
     * Prepare array passed on an input form
     *
     * @param array $value  passed array
     *
     * @return string  encoded array
     *
     * @since 0.83.91
     **/
    public static function prepareArrayForInput(array $value)
    {
        return base64_encode(json_encode($value));
    }


    /**
     * Decode array passed on an input form
     *
     * @param string $value  encoded value
     *
     * @return string  decoded array
     *
     * @since 0.83.91
     **/
    public static function decodeArrayFromInput($value)
    {

        if ($dec = base64_decode($value)) {
            if ($ret = json_decode($dec, true)) {
                return $ret;
            }
        }
        return [];
    }


    /**
     * Check valid referer accessing GLPI
     *
     * @since 0.84.2
     *
     * @return void  display error if not permit
     *
     * @deprecated 10.0.7
     **/
    public static function checkValidReferer()
    {
        Toolbox::deprecated('Checking `HTTP_REFERER` does not provide any security.');

        global $CFG_GLPI;

        $isvalidReferer = true;

        if (!isset($_SERVER['HTTP_REFERER'])) {
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                Html::displayErrorAndDie(
                    __("No HTTP_REFERER found in request. Reload previous page before doing action again."),
                    true
                );
                $isvalidReferer = false;
            }
        } else if (!is_array($url = parse_url($_SERVER['HTTP_REFERER']))) {
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                Html::displayErrorAndDie(
                    __("Error when parsing HTTP_REFERER. Reload previous page before doing action again."),
                    true
                );
                $isvalidReferer = false;
            }
        }

        if (
            !isset($url['host'])
            || (($url['host'] != $_SERVER['SERVER_NAME'])
            && (!isset($_SERVER['HTTP_X_FORWARDED_SERVER'])
               || ($url['host'] != $_SERVER['HTTP_X_FORWARDED_SERVER'])))
        ) {
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                Html::displayErrorAndDie(
                    __("None or Invalid host in HTTP_REFERER. Reload previous page before doing action again."),
                    true
                );
                $isvalidReferer = false;
            }
        }

        if (
            !isset($url['path'])
            || (!empty($CFG_GLPI['root_doc'])
            && (strpos($url['path'], $CFG_GLPI['root_doc']) !== 0))
        ) {
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                Html::displayErrorAndDie(
                    __("None or Invalid path in HTTP_REFERER. Reload previous page before doing action again."),
                    true
                );
                $isvalidReferer = false;
            }
        }

        if (!$isvalidReferer && $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE) {
            Html::displayErrorAndDie(
                __("The action you have requested is not allowed. Reload previous page before doing action again."),
                true
            );
        }
    }


    /**
     * Retrieve the mime type of a file
     *
     * @since 0.85.5
     *
     * @param string         $file  path of the file
     * @param boolean|string $type  check if $file is the correct type
     *
     * @return boolean|string (if $type not given) else boolean
     *
     **/
    public static function getMime($file, $type = false)
    {

        static $finfo = null;

        if (is_null($finfo)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
        }
        $mime = $finfo->file($file);
        if ($type) {
            $parts = explode('/', $mime, 2);
            return ($parts[0] == $type);
        }
        return ($mime);
    }


    /**
     * Summary of in_array_recursive
     *
     * @since 9.1
     *
     * @param mixed $needle
     * @param array $haystack
     * @param bool  $strict: If strict is set to TRUE then it will also
     *              check the types of the needle in the haystack.
     * @return bool
     */
    public static function in_array_recursive($needle, $haystack, $strict = false)
    {

        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

        foreach ($it as $element) {
            if ($strict) {
                if ($element === $needle) {
                    return true;
                }
            } else {
                if ($element == $needle) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Slugify
     *
     * @param string $string String to slugify
     * @param string $prefix Prefix to use (anchors cannot begin with a number)
     *
     * @return string
     */
    public static function slugify($string, $prefix = 'slug_')
    {
        $string = transliterator_transliterate("Any-Latin; Latin-ASCII; [^a-zA-Z0-9\.\ -_] Remove;", $string);
        $string = str_replace(' ', '-', self::strtolower($string));
        $string = preg_replace('~[^0-9a-z_\.]+~i', '-', $string);
        $string = trim($string, '-');
        if ($string == '') {
           //prevent empty slugs; see https://github.com/glpi-project/glpi/issues/2946
           //harcoded prefix string because html @id must begin with a letter
            $string = 'nok_' . Toolbox::getRandomString(10);
        } else if (ctype_digit(substr($string, 0, 1))) {
           //starts with a number; not ok to be used as an html id attribute
            $string = $prefix . $string;
        }
        return $string;
    }

    /**
     * Find documents data matching the tags found in the string
     * Tags are deduplicated
     *
     * @param string $content_text String to search tags from
     *
     * @return array data from documents having tags found
     */
    public static function getDocumentsFromTag(string $content_text): array
    {
        preg_match_all(
            '/' . Document::getImageTag('(([a-z0-9]+|[\.\-]?)+)') . '/',
            $content_text,
            $matches,
            PREG_PATTERN_ORDER
        );
        if (!isset($matches[1]) || count($matches[1]) == 0) {
            return [];
        }

        $document = new Document();
        return $document->find(['tag' => array_unique($matches[1])]);
    }

    /**
     * Convert tag to image
     *
     * @since 9.2
     *
     * @param string $content_text   text content of input
     * @param CommonDBTM $item       Glpi item where to convert image tag to image document
     * @param array $doc_data        list of filenames and tags
     *
     * @return string                the $content_text param after parsing
     **/
    public static function convertTagToImage($content_text, CommonDBTM $item, $doc_data = [], bool $add_link = true)
    {
        global $CFG_GLPI;

        $document = new Document();
        $matches  = [];
       // If no doc data available we match all tags in content
        if (!count($doc_data)) {
            $doc_data = Toolbox::getDocumentsFromTag($content_text);
        }

        if (count($doc_data)) {
            $base_path = $CFG_GLPI['root_doc'];

            $was_html_encoded = Sanitizer::isHtmlEncoded($content_text);
            $was_escaped      = Sanitizer::isDbEscaped($content_text);
            if ($was_html_encoded) {
                $content_text = Sanitizer::decodeHtmlSpecialChars($content_text);
            }
            if ($was_escaped) {
                $content_text = Sanitizer::dbUnescape($content_text);
            }

            foreach ($doc_data as $id => $image) {
                if (isset($image['tag'])) {
                   // Add only image files : try to detect mime type
                    if (
                        $document->getFromDB($id)
                        && strpos($document->fields['mime'], 'image/') !== false
                    ) {
                        // append object reference in image link
                        $linked_object = null;
                        if (
                              !($item instanceof CommonITILObject)
                              && isset($item->input['_job'])
                              && $item->input['_job'] instanceof CommonITILObject
                        ) {
                            $linked_object = $item->input['_job'];
                        } else if ($item instanceof CommonDBTM) {
                            $linked_object = $item;
                        }
                        $object_url_param = null !== $linked_object
                        ? sprintf('&itemtype=%s&items_id=%s', $linked_object->getType(), $linked_object->fields['id'])
                        : "";
                        $img = "<img alt='" . $image['tag'] . "' src='" . $base_path .
                          "/front/document.send.php?docid=" . $id . $object_url_param . "'/>";

                      // 1 - Replace direct tag (with prefix and suffix) by the image
                        $content_text = preg_replace(
                            '/' . Document::getImageTag($image['tag']) . '/',
                            $img,
                            $content_text
                        );

                         // 2 - Replace img with tag in id attribute by the image
                        $regex = '/<img[^>]+' . preg_quote($image['tag'], '/') . '[^<]+>/im';
                        preg_match_all($regex, $content_text, $matches);
                        foreach ($matches[0] as $match_img) {
                            //retrieve dimensions
                            $width = $height = null;
                            $attributes = [];
                            preg_match_all('/(width|height)="([^"]*)"/i', $match_img, $attributes);
                            if (isset($attributes[1][0])) {
                                ${$attributes[1][0]} = $attributes[2][0];
                            }
                            if (isset($attributes[1][1])) {
                                ${$attributes[1][1]} = $attributes[2][1];
                            }

                            if ($width == null || $height == null) {
                                $path = GLPI_DOC_DIR . "/" . $image['filepath'];
                                $img_infos  = getimagesize($path);
                                $width = $img_infos[0];
                                $height = $img_infos[1];
                            }

                            // replace image
                            $new_image =  Html::getImageHtmlTagForDocument(
                                $id,
                                $width,
                                $height,
                                $add_link,
                                $object_url_param
                            );
                            if (empty($new_image)) {
                                  $new_image = '#' . $image['tag'] . '#';
                            }
                            $content_text = str_replace(
                                $match_img,
                                $new_image,
                                $content_text
                            );
                            $content_text = $content_text;
                        }

                        // If the tag is from another ticket : link document to ticket
                        if (
                            $item instanceof Ticket
                            && $item->getID()
                            && isset($image['tickets_id'])
                            && $image['tickets_id'] != $item->getID()
                        ) {
                            $docitem = new Document_Item();
                            $docitem->add(['documents_id'  => $image['id'],
                                '_do_notif'     => false,
                                '_disablenotif' => true,
                                'itemtype'      => $item->getType(),
                                'items_id'      => $item->fields['id']
                            ]);
                        }
                    } else {
                      // Remove tag
                        $content_text = preg_replace(
                            '/' . Document::getImageTag($image['tag']) . '/',
                            '',
                            $content_text
                        );
                    }
                }
            }

            if ($was_html_encoded) {
                $content_text = Sanitizer::encodeHtmlSpecialChars($content_text);
            }
            if ($was_escaped) {
                $content_text = Sanitizer::dbEscape($content_text);
            }
        }

        return $content_text;
    }

    /**
     * Delete tag or image from ticket content
     *
     * @since 9.2
     *
     * @param string $content   html content of input
     * @param array $tags       list of tags to clen
     *
     * @return string  html content
     **/
    public static function cleanTagOrImage($content, array $tags)
    {
        $content = Sanitizer::unsanitize($content);

        foreach ($tags as $tag) {
            $content = preg_replace("/<img.*alt=['|\"]" . $tag . "['|\"][^>]*\>/", "<p></p>", $content);
        }

        $content = Sanitizer::sanitize($content);

        return $content;
    }

    /**
     * Decode JSON in GLPI
     * Because json can have been modified from Sanitizer
     *
     * @param string $encoded Encoded JSON
     * @param boolean $assoc  assoc parameter of json_encode native function
     *
     * @return mixed
     */
    public static function jsonDecode($encoded, $assoc = false)
    {
        if (!is_string($encoded)) {
            self::log(null, Logger::NOTICE, ['Only strings can be json to decode!']);
            return $encoded;
        }

        $json_data = null;
        if (self::isJSON($encoded)) {
            $json_data = $encoded;
        } else {
            //something went wrong... Try to unsanitize before decoding.
            $raw_encoded = Sanitizer::unsanitize($encoded);
            if (self::isJSON($raw_encoded)) {
                $json_data = $raw_encoded;
            }
        }

        if ($json_data === null) {
            self::log(null, Logger::NOTICE, ['Unable to decode JSON string! Is this really JSON?']);
            return $encoded;
        }

        $json = json_decode($json_data, $assoc);
        return $json;
    }


    /**
     * **Fast** JSON detection of a given var
     * From https://stackoverflow.com/a/45241792
     *
     * @param mixed the var to test
     *
     * @return bool
     */
    public static function isJSON($json): bool
    {
        // Numeric strings are always valid JSON.
        if (is_numeric($json)) {
            return true;
        }

        // A non-string value can never be a JSON string.
        if (!is_string($json)) {
            return false;
        }

        $json = trim($json);
        // Any non-numeric JSON string must be longer than 2 characters.
        if (strlen($json) < 2) {
            return false;
        }

        // "null" is valid JSON string.
        if ('null' === $json) {
            return true;
        }

        // "true" and "false" are valid JSON strings.
        if ('true' === $json) {
            return true;
        }
        if ('false' === $json) {
            return false;
        }

        // Any other JSON string has to be wrapped in {}, [] or "".
        if ('{' != $json[0] && '[' != $json[0] && '"' != $json[0]) {
            return false;
        }

        // Verify that the trailing character matches the first character.
        $last_char = $json[strlen($json) - 1];
        if ('{' == $json[0] && '}' != $last_char) {
            return false;
        }
        if ('[' == $json[0] && ']' != $last_char) {
            return false;
        }
        if ('"' == $json[0] && '"' != $last_char) {
            return false;
        }

        // See if the string contents are valid JSON.
        return null !== json_decode($json);
    }

    /**
     * Checks if a string starts with another one
     *
     * @since 9.1.5
     *
     * @param string $haystack String to check
     * @param string $needle   String to find
     *
     * @return boolean
     *
     * @deprecated 10.0.0
     */
    public static function startsWith($haystack, $needle)
    {
        Toolbox::deprecated('Use native str_starts_with() function.');
        return str_starts_with($haystack, $needle);
    }

    /**
     * Checks if a string starts with another one
     *
     * @since 9.2
     *
     * @param string $haystack String to check
     * @param string $needle   String to find
     *
     * @return boolean
     *
     * @deprecated 10.0.0
     */
    public static function endsWith($haystack, $needle)
    {
        Toolbox::deprecated('Use native str_ends_with() function.');
        return str_ends_with($haystack, $needle);
    }

    /**
     * gets the IP address of the client
     *
     * @since 9.2
     *
     * @return string the IP address
     */
    public static function getRemoteIpAddress()
    {
        return $_SERVER["REMOTE_ADDR"];
    }

    /**
     * Get available date formats
     *
     * @since 9.2
     *
     * @param string $type Type for (either 'php' or 'js')
     *
     * @return array
     */
    public static function getDateFormats($type)
    {
        $formats = [];
        switch ($type) {
            case 'js':
                $formats = [
                    0 => 'Y-m-d',
                    1 => 'd-m-Y',
                    2 => 'm-d-Y'
                ];
                break;
            case 'php':
                $formats = [
                    0 => __('YYYY-MM-DD'),
                    1 => __('DD-MM-YYYY'),
                    2 => __('MM-DD-YYYY')
                ];
                break;
            default:
                throw new \RuntimeException("Unknown type $type to get date formats.");
        }
        return $formats;
    }

    /**
     * Get current date format
     *
     * @since 9.2
     *
     * @param string $type Type for (either 'php', 'js')
     *
     * @return string
     */
    public static function getDateFormat($type)
    {
        $formats = self::getDateFormats($type);
        $format = $formats[$_SESSION["glpidate_format"] ?? 0];
        return $format;
    }

    /**
     * Get current date format for php
     *
     * @since 9.2
     *
     * @return string
     */
    public static function phpDateFormat()
    {
        return self::getDateFormat('php');
    }

    /**
     * Get available date formats for php
     *
     * @since 9.2
     *
     * @return array
     */
    public static function phpDateFormats()
    {
        return self::getDateFormats('php');
    }

    /**
     * Get current date format for javascript
     *
     * @since 9.2
     *
     * @return string
     */
    public static function jsDateFormat()
    {
        return self::getDateFormat('js');
    }

    /**
     * Get available date formats for javascript
     *
     * @since 9.2
     *
     * @return array
     */
    public static function jsDateFormats()
    {
        return self::getDateFormats('js');
    }

    /**
     * Format a web link adding http:// if missing
     *
     * @param string $link link to format
     *
     * @return string formatted link.
     **/
    public static function formatOutputWebLink($link)
    {
        if (!preg_match("/^https?/", $link)) {
            return "http://" . $link;
        }
        return $link;
    }

    /**
     * Convert a integer index into an excel like alpha index (A, B, ..., AA, AB, ...)
     * @since 9.3
     * @param  integer $index the numeric index
     * @return string         excel like string index
     */
    public static function getBijectiveIndex($index = 0)
    {
        $bij_str = "";
        while ((int) $index > 0) {
            $index--;
            $bij_str = chr($index % 26 + ord("A")) . $bij_str;
            $index = floor($index / 26);
        }
        return $bij_str;
    }

    /**
     * Get HTML content to display (cleaned)
     *
     * @since 9.1.8
     *
     * @param string $content Content to display
     *
     * @return string
     *
     * @deprecated 10.0.0
     */
    public static function getHtmlToDisplay($content)
    {
        Toolbox::deprecated('Use Glpi\Toolbox\RichText::getEnhancedHtml()');

        $content = Toolbox::unclean_cross_side_scripting_deep(
            $content
        );

        $content = Html::clean($content, false, 1);

       // If content does not contain <br> or <p> html tag, use nl2br
       // Required to correctly render linebreaks from "simple text mode" from GLPI prior to 9.4.0.
        if (!preg_match('/<br\s?\/?>/', $content) && !preg_match('/<p>/', $content)) {
            $content = nl2br($content);
        }

        return $content;
    }

    /**
     * Strip HTML tags from a string.
     *
     * @since 10.0.0
     *
     * @param string  $str
     *
     * @return string
     *
     * @TODO Unit test
     */
    public static function stripTags(string $str): string
    {
        $str = Sanitizer::getVerbatimValue($str);

        return strip_tags($str);
    }

    /**
     * Save a picture and return destination filepath.
     * /!\ This method is made to handle uploaded files and removes the source file filesystem.
     *
     * @param string|null $src          Source path of the picture
     * @param string      $uniq_prefix  Unique prefix that can be used to improve uniqueness of destination filename
     *
     * @return boolean|string      Destination filepath, relative to GLPI_PICTURE_DIR, or false on failure
     *
     * @since 9.5.0
     */
    public static function savePicture($src, $uniq_prefix = '')
    {

        if (!Document::isImage($src)) {
            return false;
        }

        $filename     = uniqid($uniq_prefix);
        $ext          = pathinfo($src, PATHINFO_EXTENSION);
        $subdirectory = substr($filename, -2); // subdirectory based on last 2 hex digit

        $i = 0;
        do {
           // Iterate on possible suffix while dest exists.
           // This case will almost never exists as dest is based on an unique id.
            $dest = GLPI_PICTURE_DIR
            . '/' . $subdirectory
            . '/' . $filename . ($i > 0 ? '_' . $i : '') . '.' . $ext;
            $i++;
        } while (file_exists($dest));

        if (!is_dir(GLPI_PICTURE_DIR . '/' . $subdirectory) && !mkdir(GLPI_PICTURE_DIR . '/' . $subdirectory)) {
            return false;
        }

        if (!rename($src, $dest)) {
            return false;
        }

        return substr($dest, strlen(GLPI_PICTURE_DIR . '/')); // Return dest relative to GLPI_PICTURE_DIR
    }


    /**
     * Delete a picture.
     *
     * @param string $path
     *
     * @return boolean
     *
     * @since 9.5.0
     */
    public static function deletePicture($path)
    {

        $fullpath = GLPI_PICTURE_DIR . '/' . $path;

        if (!file_exists($fullpath)) {
            return false;
        }

        $fullpath = realpath($fullpath);
        if (!str_starts_with($fullpath, realpath(GLPI_PICTURE_DIR))) {
           // Prevent deletion of a file ouside pictures directory
            return false;
        }

        return @unlink($fullpath);
    }


    /**
     * Get picture URL.
     *
     * @param string $path
     * @param bool  bool get full path
     *
     * @return null|string
     *
     * @since 9.5.0
     */
    public static function getPictureUrl($path, $full = true)
    {
        global $CFG_GLPI;

        $path = Html::cleanInputText($path); // prevent xss

        if (empty($path)) {
            return null;
        }

        return ($full ? $CFG_GLPI["root_doc"] : "") . '/front/document.send.php?file=_pictures/' . $path;
    }

    /**
     * Return a shortened number with a suffix (K, M, B, T)
     *
     * @param int $number to shorten
     * @param int $precision how much number after comma we need
     * @param bool $html do we return an html or a single string
     *
     * @return string shortened number
     */
    public static function shortenNumber($number = 0, $precision = 1, bool $html = true): string
    {

        $suffix = "";
        if (!is_numeric($number)) {
            if (preg_match("/^([0-9\.]+)(.*)/", $number, $matches)) {
                // Preformatted value: {Number}{Suffix}
                $formatted = $matches[1];
                $suffix = $matches[2];
            } else {
               // Unknwown format
                $formatted = $number;
            }
        } else if ($number < 900) {
            $formatted = number_format($number);
        } else if ($number < 900000) {
            $formatted = number_format($number / 1000, $precision);
            $suffix = "K";
        } else if ($number < 900000000) {
            $formatted = number_format($number / 1000000, $precision);
            $suffix = "M";
        } else if ($number < 900000000000) {
            $formatted = number_format($number / 1000000000, $precision);
            $suffix = "B";
        } else {
            $formatted = number_format($number / 1000000000000, $precision);
            $suffix = "T";
        }

        if (strpos($formatted, '.') === false) {
            $precision = 0;
        }

        if ($html) {
            $formatted = <<<HTML
            <span title="{$number}"
                  class="formatted-number"
                  data-precision='{$precision}'>
               <span class="number">$formatted</span>
               <span class="suffix">$suffix</span>
            </span>
HTML;
        } else {
            $formatted .= $suffix;
        }

        return $formatted;
    }


    /**
     * Get a fixed hex color for a input string
     * Inpsired by shahonseven/php-color-hash
     * @since 9.5
     *
     * @param string $str
     *
     * @return string hex color (ex #FAFAFA)
     */
    public static function getColorForString(string $str = ""): string
    {
        $seed  = 131;
        $seed2 = 137;
        $hash  = 0;
       // Make hash more sensitive for short string like 'a', 'b', 'c'
        $str .= 'x';
        $max = intval(9007199254740991 / $seed2);

       // Backport of Javascript function charCodeAt()
        $getCharCode = function ($c) {
            list(, $ord) = unpack('N', mb_convert_encoding($c, 'UCS-4BE', 'UTF-8'));
            return $ord;
        };

       // generate integer hash
        for ($i = 0, $ilen = mb_strlen($str, 'UTF-8'); $i < $ilen; $i++) {
            if ($hash > $max) {
                $hash = intval($hash / $seed2);
            }
            $hash = $hash * $seed + $getCharCode(mb_substr($str, $i, 1, 'UTF-8'));
        }

       //get Hsl
        $base_L = $base_S = [0.6, 0.65, 0.7];
        $H = $hash % 359;
        $hash = intval($hash / 360);
        $S = $base_S[$hash % count($base_S)];
        $hash = intval($hash / count($base_S));
        $L = $base_L[$hash % count($base_L)];
        $hsl = [
            'H' => $H,
            'S' => $S,
            'L' => $L
        ];

       // return hex
        return "#" . Color::hslToHex($hsl);
    }


    /**
     * Return a frontground color for a given background color
     * if bg color is light, we'll return dark fg color
     * else a light fg color
     *
     * @param string $color the background color in hexadecimal notation (ex #FFFFFF) to compute
     * @param int $offset how much we need to darken/lighten the color
     * @param bool $inherit_if_transparent if color contains an opacity value, and if this value is too transparent return 'inherit'
     *
     * @return string hexadecimal fg color (ex #FFFFFF)
     */
    public static function getFgColor(string $color = "", int $offset = 40, bool $inherit_if_transparent = false): string
    {
        $fg_color = "FFFFFF";
        if ($color !== "") {
            $color = str_replace("#", "", $color);

           // if transparency present, get only the color part
            if (strlen($color) === 8 && preg_match('/^[a-fA-F0-9]+$/', $color)) {
                $tmp = $color;
                $alpha = hexdec(substr($tmp, 6, 2));
                $color = substr($color, 0, 6);

                if ($alpha <= 100) {
                    return "inherit";
                }
            }

            $color_inst = new Color($color);

           // adapt luminance part
            if ($color_inst->isLight()) {
                $hsl = Color::hexToHsl($color);
                $hsl['L'] = max(0, $hsl['L'] - ($offset / 100));
                $fg_color = Color::hslToHex($hsl);
            } else {
                $hsl = Color::hexToHsl($color);
                $hsl['L'] = min(1, $hsl['L'] + ($offset / 100));
                $fg_color = Color::hslToHex($hsl);
            }
        }

        return "#" . $fg_color;
    }

    /**
     * Get an HTTP header value
     *
     * @since 9.5
     *
     * @param string $name
     *
     * @return mixed The header value or null if not found
     */
    public static function getHeader(string $name)
    {
       // Format expected header name
        $name = "HTTP_" . str_replace("-", "_", strtoupper($name));

        return $_SERVER[$name] ?? null;
    }

    /**
     * Check if the given class exist and extends CommonDBTM
     *
     * @param string $class
     * @return bool
     */
    public static function isCommonDBTM(string $class): bool
    {
        return class_exists($class) && is_subclass_of($class, 'CommonDBTM');
    }

    /**
     * Check if the given class exist and implement DeprecatedInterface
     *
     * @param string $class
     * @return bool
     */
    public static function isAPIDeprecated(string $class): bool
    {
        $deprecated = "Glpi\Api\Deprecated\DeprecatedInterface";

       // Insert namespace if missing
        if (strpos($class, "Glpi\Api\Deprecated") === false) {
            $class = "Glpi\Api\Deprecated\\$class";
        }

        return class_exists($class) && is_a($class, $deprecated, true);
    }

    /**
     * Check URL validity
     *
     * @param string $url The URL to check
     *
     * @return boolean
     */
    public static function isValidWebUrl($url): bool
    {
       // Verify absence of known disallowed characters.
       // It is still possible to have false positives, but a fireproof check would be too complex
       // (or would require usage of a dedicated lib).
        return (preg_match(
            "/^(?:http[s]?:\/\/(?:[^\s`!(){};'\",<>«»“”‘’+]+|[^\s`!()\[\]{};:'\".,<>?«»“”‘’+]))$/iu",
            Sanitizer::unsanitize($url)
        ) === 1);
    }

    /**
     * Checks if the given class or object has the specified trait.
     * This function checks the class itself and all parent classes for the trait.
     * @since 10.0.0
     * @param string|object $class The class or object
     * @param string $trait The trait
     * @return bool True if the class or its parents have the specified trait
     */
    public static function hasTrait($class, string $trait): bool
    {
       // Get traits of all parent classes
        do {
            $traits = class_uses($class, true);
            if (in_array($trait, $traits, true)) {
                return true;
            }
        } while ($class = get_parent_class($class));

        return false;
    }

   /*
    * Normalizes file name
    *
    * @param string filename
    *
    * @return string
    */
    public static function filename($filename): string
    {
       //remove extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = self::slugify(
            preg_replace(
                '/\.' . $ext . '$/',
                '',
                $filename
            ),
            '' //no prefix on filenames
        );

        $namesize = strlen($filename) + strlen($ext) + 1;
        if ($namesize > 255) {
           //limit to 255 characters
            $filename = substr($filename, 0, $namesize - 255);
        }

        if (!empty($ext)) {
            $filename .= '.' . $ext;
        }

        return $filename;
    }

    /**
     * Clean _target argument
     *
     * @param string $target Target argument
     *
     * @return string
     */
    public static function cleanTarget(string $target): string
    {
        global $CFG_GLPI;

        $file = preg_replace('/^' . preg_quote($CFG_GLPI['root_doc'], '/') . '/', '', $target);
        if (file_exists(GLPI_ROOT . $file)) {
            return $target;
        }

        return '';
    }

    /**
     * Get available tabs for a given item
     *
     * @param string   $itemtype Type of the item
     * @param int|string|null $itemtype Id the item, optional
     *
     * @return array
     */
    public static function getAvailablesTabs(string $itemtype, $id = null): array
    {
        $item = getItemForItemtype($itemtype);

        if (!$item) {
            return [];
        }

        if (!is_null($id) && !$item->isNewID($id)) {
            $item->getFromDB($id);
        }

        $options = [];
        if (isset($_GET['withtemplate'])) {
            $options['withtemplate'] = $_GET['withtemplate'];
        }

        $tabs = $item->defineAllTabs($options);
        if (isset($tabs['no_all_tab'])) {
            unset($tabs['no_all_tab']);
        }
       // Add all tab
        $tabs[-1] = 'All';

        return $tabs;
    }

    /**
     * Handle redirect after a profile switch.
     * Must be called after a right check failure.
     */
    public static function handleProfileChangeRedirect(): void
    {
        global $CFG_GLPI;

        $redirect = $_SESSION['_redirected_from_profile_selector'] ?? false;

        if ($redirect) {
            unset($_SESSION['_redirected_from_profile_selector']);
            Html::redirect($CFG_GLPI['root_doc'] . "/front/central.php");
        }
    }

    /**
     * Check if a mixed value (possibly a string) is an integer or a float
     *
     * @param mixed $value A possible float
     *
     * @return bool
     */
    public static function isFloat($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (!is_numeric($value)) {
            $type = gettype($value);

            trigger_error(
                "Calling isFloat on $type",
                E_USER_WARNING
            );
            return false;
        }

        return (floatval($value) - intval($value)) > 0;
    }

    /**
     * Get the number of decimals for a given value
     *
     * @param mixed $value A possible float
     *
     * @return int
     */
    public static function getDecimalNumbers($value): int
    {
        if (!is_numeric($value)) {
            $type = gettype($value);

            trigger_error(
                "Calling getDecimalNumbers on $type",
                E_USER_WARNING
            );
            return 0;
        }

        if (floatval($value) == intval($value)) {
            return 0;
        }

        return strlen(preg_replace('/\d*\./', '', floatval($value)));
    }

    /**
     * Try to convert to Mio the given input
     *
     * @param string $size Input string
     *
     * @return mixed The Mio value as an integer if we were able to parse the
     * input, else the unchanged input string
     */
    public static function getMioSizeFromString(string $size)
    {
        if (is_numeric($size)) {
            // Already a numeric value, no work to be done
            return $size;
        }

        if (!preg_match('/(\d+).*?(\w+)/', $size, $matches)) {
            // Unkown format, keep the string as it is
            return $size;
        }
        $supported_sizes = [
            'mo'  => 0,
            'mio' => 0,
            'go'  => 1,
            'gio' => 1,
            'to'  => 2,
            'tio' => 2,
        ];
        if (count($matches) >= 3 && isset($supported_sizes[strtolower($matches[2])])) {
            // Known format
            $size = $matches[1];
            $size *= pow(1024, $supported_sizes[strtolower($matches[2])]);
        }
        return $size;
    }
}
