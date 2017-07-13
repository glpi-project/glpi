<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * Toolbox Class
**/
class Toolbox {

   /**
    * Wrapper for get_magic_quotes_runtime - deprecated
    *
    * @since version 0.83
    * @deprecated in 0.90.1
    *
    * @return boolean
   **/
   static function get_magic_quotes_runtime() {

      return 0;
   }


   /**
    * Wrapper for get_magic_quotes_gpc - deprecated
    *
    * @since version 0.83
    * @deprecated in 0.90.1
    *
    * @return boolean
   **/
   static function get_magic_quotes_gpc() {

      return 0;
   }


   /**
    * Wrapper for max_input_vars
    *
    * @since version 0.84
    *
    * @return integer
   **/
   static function get_max_input_vars() {

      $max = ini_get('max_input_vars');  // Security limit since PHP 5.3.9
      if (!$max) {
         $max = ini_get('suhosin.post.max_vars');  // Security limit from Suhosin
      }
      return $max;
   }


   /**
    * Convert first caracter in upper
    *
    * @since version 0.83
    *
    * @param $str string to change
    *
    * @return string changed
   **/
   static function ucfirst($str) {

      if ($str{0} >= "\xc3") {
         return (($str{1} >= "\xa0") ? ($str{0}.chr(ord($str{1})-32))
                                     : ($str{0}.$str{1})).substr($str,2);
      }
      return ucfirst($str);
    }


   /**
    * to underline shortcut letter
    *
    * @since version 0.83
    *
    * @param $str       string   from dico
    * @param $shortcut           letter of shortcut
    *
    * @return string
   **/

   static function shortcut($str, $shortcut) {

      $pos = self::strpos(self::strtolower($str), $shortcut);
      if ($pos !== false) {
         return self::substr($str, 0, $pos).
                "<u>". self::substr($str, $pos,1)."</u>".
                self::substr($str, $pos+1);
      }
      return $str;
   }


   /**
    * substr function for utf8 string
    *
    * @param $str       string   string
    * @param $tofound   string   string to found
    * @param $offset    integer  The search offset. If it is not specified, 0 is used.
    *                            (default 0)
    *
    * @return substring
   **/
   static function strpos($str, $tofound, $offset=0) {
      return mb_strpos($str, $tofound, $offset, "UTF-8");
   }



   /**
    *  Replace str_pad()
    *  who bug with utf8
    *
    * @param $input        string   input string
    * @param $pad_length   integer  padding length
    * @param $pad_string   string   padding string (default '')
    * @param $pad_type     integer  padding type (default STR_PAD_RIGHT)
    *
    * @return string
   **/
   static function str_pad($input, $pad_length, $pad_string=" ", $pad_type=STR_PAD_RIGHT) {

       $diff = (strlen($input) - self::strlen($input));
       return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
   }


   /**
    * strlen function for utf8 string
    *
    * @param $str string
    *
    * @return length of the string
   **/
   static function strlen($str) {
      return mb_strlen($str, "UTF-8");
   }


   /**
    * substr function for utf8 string
    *
    * @param $str       string
    * @param $start     integer  start of the result substring
    * @param $length    integer  The maximum length of the returned string if > 0 (default -1)
    *
    * @return substring
   **/
   static function substr($str, $start, $length=-1) {

      if ($length == -1) {
         $length = self::strlen($str)-$start;
      }
      return mb_substr($str, $start, $length, "UTF-8");
   }


   /**
    * strtolower function for utf8 string
    *
    * @param $str string
    *
    * @return lower case string
   **/
   static function strtolower($str) {
      return mb_strtolower($str, "UTF-8");
   }


   /**
    * strtoupper function for utf8 string
    *
    * @param $str string
    *
    * @return upper case string
   **/
   static function strtoupper($str) {
      return mb_strtoupper($str, "UTF-8");
   }


   /**
    * Is a string seems to be UTF-8 one ?
    *
    * @param $str string   string to analyze
    *
    * @return  boolean
   **/
   static function seems_utf8($str) {
      return mb_check_encoding($str, "UTF-8");
   }


   /**
    * Encode string to UTF-8
    *
    * @param $string       string   string to convert
    * @param $from_charset string   original charset (if 'auto' try to autodetect)
    *                               (default "ISO-8859-1")
    *
    * @return utf8 string
   **/
   static function encodeInUtf8($string, $from_charset="ISO-8859-1") {

      if (strcmp($from_charset,"auto") == 0) {
         $from_charset = mb_detect_encoding($string);
      }
      return mb_convert_encoding($string, "UTF-8", $from_charset);
   }


   /**
    * Decode string from UTF-8 to specified charset
    *
    * @param $string       string   string to convert
    * @param $to_charset   string   destination charset (default "ISO-8859-1")
    *
    * @return converted string
   **/
   static function decodeFromUtf8($string, $to_charset="ISO-8859-1") {
      return mb_convert_encoding($string, $to_charset, "UTF-8");
   }


   /**
    * Encrypt a string
    *
    * @param $string    string to encrypt
    * @param $key       string key used to encrypt
    *
    * @return encrypted string
   **/
   static function encrypt($string, $key) {

     $result = '';
     for($i=0 ; $i<strlen($string) ; $i++) {
       $char    = substr($string, $i, 1);
       $keychar = substr($key, ($i % strlen($key))-1, 1);
       $char    = chr(ord($char)+ord($keychar));
       $result .= $char;
     }

     return base64_encode($result);
   }


   /**
    * Decrypt a string
    *
    * @param $string    string to decrypt
    * @param $key       string key used to decrypt
    *
    * @return decrypted string
   **/
   static function decrypt($string, $key) {

     $result = '';
     $string = base64_decode($string);

     for($i=0 ; $i<strlen($string) ; $i++) {
       $char    = substr($string, $i, 1);
       $keychar = substr($key, ($i % strlen($key))-1, 1);
       $char    = chr(ord($char)-ord($keychar));
       $result .= $char;
     }

     return Toolbox::unclean_cross_side_scripting_deep($result);
   }


   /**
    * Prevent from XSS
    * Clean code
    *
    * @param $value array or string: item to prevent (array or string)
    *
    * @return clean item
    *
    * @see unclean_cross_side_scripting_deep*
   **/
   static function clean_cross_side_scripting_deep($value) {

      $in  = array('<', '>');
      $out = array('&lt;', '&gt;');

      $value = ((array) $value === $value)
                  ? array_map(array(__CLASS__, 'clean_cross_side_scripting_deep'), $value)
                  : (is_null($value)
                        ? NULL : (is_resource($value)
                                     ? $value : str_replace($in,$out,$value)));

      return $value;
   }


   /**
    *  Invert fonction from clean_cross_side_scripting_deep
    *
    * @param $value  array or string   item to unclean from clean_cross_side_scripting_deep
    *
    * @return unclean item
    *
    * @see clean_cross_side_scripting_deep
   **/
   static function unclean_cross_side_scripting_deep($value) {

      $in  = array('<', '>');
      $out = array('&lt;', '&gt;');

      $value = ((array) $value === $value)
                  ? array_map(array(__CLASS__, 'unclean_cross_side_scripting_deep'), $value)
                  : (is_null($value)
                        ? NULL : (is_resource($value)
                                     ? $value : str_replace($out,$in,$value)));

      return $value;
   }


   /**
    *  Invert fonction from clean_cross_side_scripting_deep to display HTML striping XSS code
    *
    * @since version 0.83.3
    *
    * @param $value array or string: item to unclean from clean_cross_side_scripting_deep
    *
    * @return unclean item
    *
    * @see clean_cross_side_scripting_deep
   **/
   static function unclean_html_cross_side_scripting_deep($value) {
      include_once(GLPI_HTMLAWED);

      $in  = array('<', '>');
      $out = array('&lt;', '&gt;');

      $value = ((array) $value === $value)
                  ? array_map(array(__CLASS__, 'unclean_html_cross_side_scripting_deep'), $value)
                  : (is_null($value)
                      ? NULL : (is_resource($value)
                                  ? $value : str_replace($out,$in,$value)));

      // revert unclean inside <pre>
      $count = preg_match_all('/(<pre[^>]*>)(.*?)(<\/pre>)/is', $value, $matches);
      for ($i = 0; $i < $count; ++$i) {
         $complete       = $matches[0][$i];
         $cleaned        = self::clean_cross_side_scripting_deep($matches[2][$i]);
         $cleancomplete  = $matches[1][$i].$cleaned.$matches[3][$i];
         $value          = str_replace($complete, $cleancomplete, $value);
      }

      $config                      = array('safe'=>1);
      $config["elements"]          = "*+iframe";
      $config["direct_list_nest"]  = 1;

      $value                       = htmLawed($value, $config);

      return $value;
   }


   /**
    * Log in 'php-errors' all args
   **/
   static function logDebug() {
      static $tps = 0;

      $msg = "";
      if (function_exists('debug_backtrace')) {
         $bt  = debug_backtrace();
         $msg = '  From ';
         if (count($bt) > 1) {
            if (isset($bt[1]['class'])) {
               $msg .= $bt[1]['class'].'::';
            }
            $msg .= $bt[1]['function'].'() in ';
         }
         $msg .= $bt[0]['file'] . ' line ' . $bt[0]['line'];
      }

      if ($tps && function_exists('memory_get_usage')) {
         $msg .= ' ('.number_format(microtime(true)-$tps,3).'", '.
                      number_format(memory_get_usage()/1024/1024,2).'Mio)';
      }
      $msg .= "\n  ";

      foreach (func_get_args() as $arg) {
         if (is_array($arg) || is_object($arg)) {
            $msg .= str_replace("\n", "\n  ",print_r($arg, true));
         } else if (is_null($arg)) {
            $msg .= 'NULL ';
         } else if (is_bool($arg)) {
            $msg .= ($arg ? 'true' : 'false').' ';
         } else {
            $msg .= $arg . ' ';
         }
      }

      $tps = microtime(true);
      self::logInFile('php-errors', $msg."\n",true);
   }


   /**
    * Generate a Backtrace
    *
    * @param $log    String    log file name (default php-errors)
    *                          if false, return the strung
    * @param $hide   String    call to hide (but display script/line) (default '')
    * @param $skip   Array     of call to not display at all
    *
    * @since version 0.85
    *
    * @return string if $log is false
   **/
   static function backtrace($log='php-errors', $hide='', Array $skip=array()) {

      if (function_exists("debug_backtrace")) {
         $message = "  Backtrace :\n";
         $traces  = debug_backtrace();
         foreach ($traces as $trace) {
            $script = (isset($trace["file"]) ? $trace["file"] : "") . ":" .
                        (isset($trace["line"]) ? $trace["line"] : "");
            if (strpos($script, GLPI_ROOT)===0) {
               $script = substr($script, strlen(GLPI_ROOT)+1);
            }
            if (strlen($script)>50) {
               $script = "...".substr($script, -47);
            } else {
               $script = str_pad($script, 50);
            }
            $call = (isset($trace["class"]) ? $trace["class"] : "") .
                    (isset($trace["type"]) ? $trace["type"] : "") .
                    (isset($trace["function"]) ? $trace["function"]."()" : "");
            if ($call == $hide) {
               $call = '';
            }

            if (!in_array($call, $skip)) {
               $message .= "  $script $call\n";
            }
         }
      } else {
         $message = "  Script : " . $_SERVER["SCRIPT_FILENAME"]. "\n";
      }

      if ($log) {
         self::logInFile($log, $message, true);
      } else {
         return $message;
      }
   }


   /**
    * Log a message in log file
    *
    * @param $name   string   name of the log file
    * @param $text   string   text to log
    * @param $force  boolean  force log in file not seeing use_log_in_files config (false by default)
   **/
   static function logInFile($name, $text, $force=false) {
      global $CFG_GLPI;

      $user = '';
      if (method_exists('Session', 'getLoginUserID')) {
         $user = " [".Session::getLoginUserID().'@'.php_uname('n')."]";
      }

      $ok = true;
      if ((isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"])
          || $force) {
         $ok = error_log(date("Y-m-d H:i:s")."$user\n".$text, 3, GLPI_LOG_DIR."/".$name.".log");
      }

      if (isset($_SESSION['glpi_use_mode'])
          && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && isCommandLine()) {
         fwrite(STDERR, $text);
      }
      return $ok;
   }


   /**
    * Specific error handler in Normal mode
    *
    * @param $errno     integer  level of the error raised.
    * @param $errmsg    string   error message.
    * @param $filename  string   filename that the error was raised in.
    * @param $linenum   integer  line number the error was raised at.
    * @param $vars      array    that points to the active symbol table at the point the error occurred.
   **/
   static function userErrorHandlerNormal($errno, $errmsg, $filename, $linenum, $vars) {

      // Date et heure de l'erreur
      $errortype = array(E_ERROR           => 'Error',
                         E_WARNING         => 'Warning',
                         E_PARSE           => 'Parsing Error',
                         E_NOTICE          => 'Notice',
                         E_CORE_ERROR      => 'Core Error',
                         E_CORE_WARNING    => 'Core Warning',
                         E_COMPILE_ERROR   => 'Compile Error',
                         E_COMPILE_WARNING => 'Compile Warning',
                         E_USER_ERROR      => 'User Error',
                         E_USER_WARNING    => 'User Warning',
                         E_USER_NOTICE     => 'User Notice',
                         E_STRICT          => 'Runtime Notice',
                         // Need php 5.2.0
                         4096 /*E_RECOVERABLE_ERROR*/  => 'Catchable Fatal Error',
                         // Need php 5.3.0
                         8192 /* E_DEPRECATED */       => 'Deprecated function',
                         16384 /* E_USER_DEPRECATED */ => 'User deprecated function');
      // Les niveaux qui seront enregistrés
      $user_errors = array(E_USER_ERROR, E_USER_NOTICE, E_USER_WARNING);

      $err = '  *** PHP '.$errortype[$errno] . "($errno): $errmsg\n";
      if (in_array($errno, $user_errors)) {
         $err .= "Variables:".wddx_serialize_value($vars, "Variables")."\n";
      }

      $skip = array('Toolbox::backtrace()');
      if (isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         $hide   = "Toolbox::userErrorHandlerDebug()";
         $skip[] = "Toolbox::userErrorHandlerNormal()";
      } else {
         $hide = "Toolbox::userErrorHandlerNormal()";
      }

      $err .= self::backtrace(false, $hide, $skip);

      // Save error
      static::logInFile("php-errors", $err);

      return $errortype[$errno];
   }


   /**
    * Specific error handler in Debug mode
    *
    * @param $errno     integer  level of the error raised.
    * @param $errmsg    string   error message.
    * @param $filename  string   filename that the error was raised in.
    * @param $linenum   integer  line number the error was raised at.
    * @param $vars      array    that points to the active symbol table at the point the error occurred.
   **/
   static function userErrorHandlerDebug($errno, $errmsg, $filename, $linenum, $vars) {

      // For file record
      $type = self::userErrorHandlerNormal($errno, $errmsg, $filename, $linenum, $vars);

      // Display
      if (!isCommandLine()) {
         echo '<div style="position:float-left; background-color:red; z-index:10000">'.
              '<span class="b">PHP '.$type.': </span>';
         echo $errmsg.' in '.$filename.' at line '.$linenum.'</div>';
      } else {
         echo 'PHP '.$type.': '.$errmsg.' in '.$filename.' at line '.$linenum."\n";
      }
   }


   /**
    * Switch error mode for GLPI
    *
    * @param $mode         Integer  from Session::*_MODE (default NULL)
    * @param $debug_sql    Boolean  (default NULL)
    * @param $debug_vars   Boolean  (default NULL)
    * @param $log_in_files Boolean  (default NULL)
    *
    * @since version 0.84
   **/
   static function setDebugMode($mode=NULL, $debug_sql=NULL, $debug_vars=NULL, $log_in_files=NULL) {
      global $CFG_GLPI;

      if (isset($mode)) {
         $_SESSION['glpi_use_mode'] = $mode;
      }
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
         // display_errors only need for for E_ERROR, E_PARSE, ... which cannot be catched
         // Recommended development settings
         ini_set('display_errors', 'On');
         error_reporting(E_ALL | E_STRICT);
         set_error_handler(array('Toolbox','userErrorHandlerDebug'));

      } else {
         // Recommended production settings
         ini_set('display_errors', 'Off');
         error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
         set_error_handler(array('Toolbox', 'userErrorHandlerNormal'));
      }
   }


   /**
    * Send a file (not a document) to the navigator
    * See Document->send();
    *
    * @param $file      string: storage filename
    * @param $filename  string: file title
    *
    * @return nothing
   **/
   static function sendFile($file, $filename) {

      // Test securite : document in DOC_DIR
      $tmpfile = str_replace(GLPI_DOC_DIR, "", $file);

      if (strstr($tmpfile,"../") || strstr($tmpfile,"..\\")) {
         Event::log($file, "sendFile", 1, "security",
                    $_SESSION["glpiname"]." try to get a non standard file.");
         die("Security attack!!!");
      }

      if (!file_exists($file)) {
         die("Error file $file does not exist");
      }

      $splitter = explode("/", $file);
      $mime     = "application/octetstream";

      if (preg_match('/\.(...)$/', $file, $regs)) {
         switch ($regs[1]) {
            case "sql" :
               $mime = "text/x-sql";
               break;

            case "xml" :
               $mime = "text/xml";
               break;

            case "csv" :
               $mime = "text/csv";
               break;

            case "svg" :
               $mime = "image/svg+xml";
               break;

            case "png" :
               $mime = "image/png";
               break;
         }
      }

      // Now send the file with header() magic
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: filename=\"$filename\"");
      header("Content-type: ".$mime);

      readfile($file) or die ("Error opening file $file");
   }


   /**
    *  Add slash for variable & array
    *
    * @param $value array or string: value to add slashes (array or string)
    *
    * @return addslashes value
   **/
   static function addslashes_deep($value) {
      global $DB;

      $value = ((array) $value === $value)
                  ? array_map(array(__CLASS__, 'addslashes_deep'), $value)
                  : (is_null($value)
                       ? NULL : (is_resource($value)
                                  ? $value : $DB->escape($value)));

      return $value;
   }


   /**
    * Strip slash  for variable & array
    *
    * @param $value     array or string: item to stripslashes (array or string)
    *
    * @return stripslashes item
   **/
   static function stripslashes_deep($value) {

      $value = ((array) $value === $value)
                  ? array_map(array(__CLASS__, 'stripslashes_deep'), $value)
                  : (is_null($value)
                        ? NULL : (is_resource($value)
                                    ? $value :stripslashes($value)));

      return $value;
   }


   /** Converts an array of parameters into a query string to be appended to a URL.
    *
    * @param $array     array parameters to append to the query string.
    * @param $separator        separator may be defined as &amp; to display purpose
    *                         (default '&')
    * @param $parent          This should be left blank (it is used internally by the function).
    *                         (default '')
    *
    * @return string  : Query string to append to a URL.
   **/
   static function append_params($array, $separator='&', $parent='') {

      $params = array();
      foreach ($array as $k => $v) {

         if (is_array($v)) {
            $params[] = self::append_params($v, $separator,
                                            (empty($parent) ? rawurlencode($k)
                                                            : $parent .'['.rawurlencode($k).']'));
         } else {
            $params[] = (!empty($parent) ? $parent . '[' . rawurlencode($k) . ']'
                                         : rawurlencode($k)) . '=' . rawurlencode($v);
         }
      }
      return implode($separator, $params);
   }


   /**
    * Compute PHP memory_limit
    *
    * @param $ininame String name of the ini ooption to retrieve (since 9.1)
    *
    * @return memory limit
   **/
   static function getMemoryLimit($ininame='memory_limit') {

      $mem = ini_get($ininame);
      preg_match("/([-0-9]+)([KMG]*)/", $mem, $matches);
      $mem = "";

      // no K M or G
      if (isset($matches[1])) {
         $mem = $matches[1];
         if (isset($matches[2])) {
            switch ($matches[2]) {
               case "G" :
                  $mem *= 1024;
                  // nobreak;

               case "M" :
                  $mem *= 1024;
                  // nobreak;

               case "K" :
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
    * @since version 0.83
    *
    * @return 0 if PHP not compiled with memory_limit support
    *         1 no memory limit (memory_limit = -1)
    *         2 insufficient memory for GLPI
    *         3 enough memory for GLPI
   **/
   static function checkMemoryLimit() {

      $mem = self::getMemoryLimit();
      if ($mem == "") {
         return 0;
      }
      if ($mem == "-1") {
         return 1;
      }
      if ($mem < (64*1024*1024)) {
         return 2;
      }
      return 3;
   }


   /**
    * Common Checks needed to use GLPI
    *
    * @return 2 : creation error 1 : delete error 0: OK
   **/
   static function commonCheckForUseGLPI() {
      global $CFG_GLPI;

      $error = 0;

      // Title
      echo "<tr><th>".__('Test done')."</th><th >".__('Results')."</th></tr>";

      // Parser test
      echo "<tr class='tab_bg_1'><td class='b left'>".__('Testing PHP Parser')."</td>";

      // PHP Version  - exclude PHP3, PHP 4 and zend.ze1 compatibility
      if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
         // PHP > 5.4 ok, now check PHP zend.ze1_compatibility_mode
         if (ini_get("zend.ze1_compatibility_mode") == 1) {
            $error = 2;
            echo "<td class='red'>
                  <img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'>".
                  __('GLPI is not compatible with the option zend.ze1_compatibility_mode = On.').
                 "</td>";
         } else {
            echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                       __s('PHP version is at least 5.4.0 - Perfect!')."\"
                       title=\"".__s('PHP version is at least 5.4.0 - Perfect!')."\"></td>";
         }

      } else { // PHP <5
         $error = 2;
         echo "<td class='red'>
               <img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'>".
                __('You must install at least PHP 5.4.0.')."</td>";
      }
      echo "</tr>";

      // session test
      echo "<tr class='tab_bg_1'><td class='b left'>".__('Sessions test')."</td>";

      // check whether session are enabled at all!!
      if (!extension_loaded('session')) {
         $error = 2;
         echo "<td class='red b'>".__('Your parser PHP is not installed with sessions support!').
              "</td>";

      } else if ((isset($_SESSION["Test_session_GLPI"]) && ($_SESSION["Test_session_GLPI"] == 1)) // From install
                 || isset($_SESSION["glpi_currenttime"])) { // From Update
         echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                    __s('Sessions support is available - Perfect!').
                    "\" title=\"".__s('Sessions support is available - Perfect!')."\"></td>";

      } else if ($error != 2) {
         echo "<td class='red'>";
         echo "<img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                __('Make sure that sessions support has been activated in your php.ini')."</td>";
         $error = 1;
      }
      echo "</tr>";

      // Test for session auto_start
      if (ini_get('session.auto_start')==1) {
         echo "<tr class='tab_bg_1'><td class='b'>".__('Test session auto start')."</td>";
         echo "<td class='red'>";
         echo "<img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'>".
               __('session.auto_start is activated. See .htaccess file in the GLPI root for more information.').
               "</td></tr>";
         $error = 2;
      }

      // Test for option session use trans_id loaded or not.
      echo "<tr class='tab_bg_1'>";
      echo "<td class='left b'>".__('Test if Session_use_trans_sid is used')."</td>";

      if (isset($_POST[session_name()]) || isset($_GET[session_name()])) {
         echo "<td class='red'>";
         echo "<img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'>".
               __('You must desactivate the Session_use_trans_id option in your php.ini')."</td>";
         $error = 2;

      } else {
         echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                    __s('Ok - the sessions works (no problem with trans_id) - Perfect!').
                    "\" title=\"". __s('Ok - the sessions works (no problem with trans_id) - Perfect!').
                    "\"></td>";
      }
      echo "</tr>";

      $extensions_to_check = [
         'mysqli'   => [
            'required'  => true
         ],
         'ctype'    => [
            'required'  => true,
            'function'  => 'ctype_digit',
         ],
         'fileinfo' => [
            'required'  => true,
            'class'     => 'finfo'
         ],
         'json'     => [
            'required'  => true,
            'function'  => 'json_encode'
         ],
         'mbstring' => [
            'required'  => true,
         ],
         'zlib'     => [
            'required'  => true,
         ],
         'curl'      => [
            'required'  => true,
         ],
         'gd'       => [
            'required'  => true,
         ],
         'simplexml' => [
            'required'  => true,
         ],
         'xml'        => [
            'required'  => true,
            'function'  => 'utf8_decode'
         ],
         //to sync/connect from LDAP
         'ldap'       => [
            'required'  => false,
         ],
         //for mail collector
         'imap'       => [
            'required'  => false,
         ],
         //to enhance perfs
         'Zend OPcache' => [
            'required'  => false
         ],
         //to enhance perfs
         (PHP_MAJOR_VERSION < 7 ? 'APCu' : 'apcu-bc') => [
            'required'  => false,
            'function'  => 'apc_fetch'
         ],
         //for XMLRPC API
         'xmlrpc'     => [
            'required'  => false
         ]
      ];

      //check for PHP extensions
      foreach ($extensions_to_check as $ext => $params) {
         $success = true;

         if (isset($params['function'])) {
            if (!function_exists($params['function'])) {
                $success = false;
            }
         } else if (isset($params['class'])) {
            if (!class_exists($params['class'])) {
               $success = false;
            }
         } else {
            if (!extension_loaded($ext)) {
               $success = false;
            }
         }

         echo "<tr class=\"tab_bg_1\"><td class=\"left b\">" . sprintf(__('%s extension test'), $ext) . "</td>";
         if ($success) {
             $msg = sprintf(__('%s extension is installed'), $ext);
            echo "<td><img src=\"{$CFG_GLPI['root_doc']}/pics/ok_min.png\"
                    alt=\"$msg\"
                    title=\"$msg\"></td>";
         } else {
            if (isset($params['required']) && $params['required'] === true) {
               if ($error < 2) {
                  $error = 2;
               }
               echo "<td class=\"red\"><img src=\"{$CFG_GLPI['root_doc']}/pics/ko_min.png\"> " . sprintf(__('%s extension is missing'), $ext) . "</td>";
            } else {
               if ($error < 1) {
                  $error = 1;
               }
               echo "<td><img src=\"{$CFG_GLPI['root_doc']}/pics/warning_min.png\"> " . sprintf(__('%s extension is not present'), $ext) . "</td>";
            }
         }
         echo "</tr>";
      }

      // memory test
      echo "<tr class='tab_bg_1'><td class='left b'>".__('Allocated memory test')."</td>";

      //Get memory limit
      $mem = self::getMemoryLimit();
      switch (self::checkMemoryLimit()) {
         case 0 : // memory_limit not compiled -> no memory limit
         case 1 : // memory_limit compiled and unlimited
            echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                  __s('Unlimited memory - Perfect!')."\" title=\"".
                  __s('Unlimited memory - Perfect!')."\"></td>";
            break;

         case 2: //Insufficient memory
            $showmem = $mem/1048576;
            echo "<td class='red'><img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'>".
                 "<span class='b'>".sprintf(__('%1$s: %2$s'), __('Allocated memory'),
                                            sprintf(__('%1$s %2$s'), $showmem, __('Mio'))).
                 "</span>".
                 "<br>".__('A minimum of 64Mio is commonly required for GLPI.').
                 "<br>".__('Try increasing the memory_limit parameter in the php.ini file.').
                 "</td>";
            $error = 2;
            break;

         case 3: //Got enough memory, going to the next step
            echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                  __s('Allocated memory > 64Mio - Perfect!')."\" title=\"".
                  __s('Allocated memory > 64Mio - Perfect!')."\"></td>";
            break;
      }
      echo "</tr>";

      if (!isset($_REQUEST['skipCheckWriteAccessToDirs'])) {
         $suberr = Config::checkWriteAccessToDirs();
         if ($suberr > $error) {
            $error = $suberr;
         }
      }

      $suberr = self::checkSELinux();
      if ($suberr > $error) {
         $error = $suberr;
      }

      return $error;
   }


   /**
    * Check SELinux configuration
    *
    * @since version 0.84
    * @param $fordebug    Boolean true is displayed in system information
    *
    *  @return integer 0: OK, 1:Warning, 2:Error
   **/
   static function checkSELinux($fordebug=false) {
      global $CFG_GLPI;

      if ((DIRECTORY_SEPARATOR != '/')
          || !file_exists('/usr/sbin/getenforce')) {
         // This is not a SELinux system
         return 0;
      }
      $mode = exec("/usr/sbin/getenforce");
      if (empty($mode)) {
         $mode = "Unknown";
      }
      //TRANS: %s is mode name (Permissive, Enforcing of Disabled)
      $msg  = sprintf(__('SELinux mode is %s'), $mode);
      if ($fordebug) {
         echo "<img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"" . __s('OK') . "\">$msg\n";
      } else {
         echo "<tr class='tab_bg_1'><td class='left b'>$msg</td>";
         // All modes should be ok
         echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt='$mode' title='$msg'></td></tr>";
      }
      if (!strcasecmp($mode, 'Disabled')) {
         // Other test are not useful
         return 0;
      }

      $err = 0;

      // No need to check file context as checkWriteAccessToDirs will show issues

      // Enforcing mode will block some feature (notif, ...)
      // Permissive mode will write lot of stuff in audit.log

      if (!file_exists('/usr/sbin/getenforce')) {
         // should always be there
         return 0;
      }
      $bools = array('httpd_can_network_connect', 'httpd_can_network_connect_db',
                     'httpd_can_sendmail');
      $msg2 = __s('Some features may require this to be on');
      foreach ($bools as $bool) {
         $state = exec('/usr/sbin/getsebool '.$bool);
         if (empty($state)) {
            $state = "$bool --> unkwown";
         }
         //TRANS: %s is an option name
         $msg = sprintf(__('SELinux boolean configuration for %s'), $state);
         if ($fordebug) {
            if (substr($state, -2) == 'on') {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"". __s('OK') .
               "\" title=\"" . __s('OK') . "\">$msg\n";
            } else {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png' alt=\"". $msg2 .
               "\" title=\"$msg2\">$msg ($msg2)\n";
            }
         } else {
            if (substr($state, -2) == 'on') {
               echo "<tr class='tab_bg_1'><td class='left b'>$msg</td>";
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt='$state' title='$state'>".
                    "</td>";
            } else {
               echo "<tr class='tab_bg_1'><td class='left b'>$msg ($msg2)</td>";
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png' alt='$msg2' title='$msg2'>".
                    "</td>";
               $err = 1;
            }
            echo "</tr>";
         }
      }

      return $err;
   }


   /**
    * Get the filesize of a complete directory (from php.net)
    *
    * @param $path string: directory or file to get size
    *
    * @return size of the $path
   **/
   static function filesizeDirectory($path) {

      if (!is_dir($path)) {
         return filesize($path);
      }

      if ($handle = opendir($path)) {
         $size = 0;

         while (false !== ($file = readdir($handle))) {
            if (($file != '.') && ($file != '..')) {
               $size += filesize($path.'/'.$file);
               $size += self::filesizeDirectory($path.'/'.$file);
            }
         }

         closedir($handle);
         return $size;
      }
   }


   /** Format a size passing a size in octet
    *
    * @param   $size integer: Size in octet
    *
    * @return  formatted size
   **/
   static function getSize($size) {

      //TRANS: list of unit (o for octet)
      $bytes = array(__('o'), __('Kio'), __('Mio'), __('Gio'), __('Tio'));
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
    * @param $dir string: directory to delete
   **/
   static function deleteDir($dir) {

      if (file_exists($dir)) {
         chmod($dir, 0777);

         if (is_dir($dir)) {
            $id_dir = opendir($dir);
            while (($element = readdir($id_dir)) !== false) {
               if (($element != ".") && ($element != "..")) {

                  if (is_dir($dir."/".$element)) {
                     self::deleteDir($dir."/".$element);
                  } else {
                     unlink($dir."/".$element);
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
    *
    * @since version 0.85
    *
    * @param $source_path   string   path of the picture to be resized
    * @param $dest_path     string   path of the new resized picture
    * @param $new_width     string   new width after resized (default 71)
    * @param $new_height    string   new height after resized (default 71)
    * @param $img_y         string   y axis of picture (default 0)
    * @param $img_x         string   x axis of picture (default 0)
    * @param $img_width     string   width of picture (default 0)
    * @param $img_height    string   height of picture (default 0)
    * @param $max_size      integer  max size of the picture (default 500, is set to 0 no resize)
    *
    * @return bool : true or false
   **/
   static function resizePicture($source_path, $dest_path, $new_width=71, $new_height=71,
                                 $img_y=0, $img_x=0, $img_width=0, $img_height=0, $max_size=500) {

      //get img informations (dimensions and extension)
      $img_infos  = getimagesize($source_path);
      if (empty($img_width)) {
         $img_width  = $img_infos[0];
      }
      if (empty($img_height)) {
         $img_height = $img_infos[1];
      }
      if (empty($new_width)) {
         $new_width  = $img_infos[0];
      }
      if (empty($new_height)) {
         $new_height = $img_infos[1];
      }

      // Image max size is 500 pixels : is set to 0 no resize
      if ($max_size>0) {
         if (($img_width > $max_size)
            || ($img_height > $max_size)) {
            $source_aspect_ratio = $img_width / $img_height;
            if ($source_aspect_ratio < 1) {
            $new_width  = $max_size * $source_aspect_ratio;
            $new_height = $max_size;
            } else {
            $new_width  = $max_size;
            $new_height = $max_size / $source_aspect_ratio;
            }
         }
      }

      $img_type = $img_infos[2];

      switch ($img_type) {
         case IMAGETYPE_BMP :
            $source_res = imagecreatefromwbmp($source_path);
            break;

         case IMAGETYPE_GIF :
            $source_res = imagecreatefromgif($source_path);
            break;

         case IMAGETYPE_JPEG :
            $source_res = imagecreatefromjpeg($source_path);
            break;

         case IMAGETYPE_PNG :
            $source_res = imagecreatefrompng($source_path);
            break;

         default :
            return false;
      }

      //create new img resource for store thumbnail
      $source_dest = imagecreatetruecolor($new_width, $new_height);

      //resize image
      imagecopyresampled($source_dest, $source_res, 0, 0, $img_x, $img_y,
                         $new_width, $new_height, $img_width, $img_height);

      //output img
      return imagejpeg($source_dest, $dest_path, 90);
   }


   /**
    * Check if new version is available
    *
    * @param $auto                  boolean: check done autically ? (if not display result)
    *                                        (true by default)
    * @param $messageafterredirect  boolean: use message after redirect instead of display
    *                                        (false by default)
    *
    * @return string explaining the result
   **/
   static function checkNewVersionAvailable($auto=true, $messageafterredirect=false) {
      global $CFG_GLPI;

      if (!$auto
          && !Session::haveRight('backup', Backup::CHECKUPDATE)) {
         return false;
      }

      if (!$auto && !$messageafterredirect) {
         echo "<br>";
      }

      //parse github releases (get last version number)
      $error = "";
      $json_gh_releases = self::getURLContent("https://api.github.com/repos/glpi-project/glpi/releases", $error);
      $all_gh_releases = json_decode($json_gh_releases, true);
      $released_tags = array();
      foreach ($all_gh_releases as $release) {
         if ($release['prerelease'] == false) {
            $released_tags[] =  $release['tag_name'];
         }
      }
      usort($released_tags, 'version_compare');
      $latest_version = array_pop($released_tags);

      if (strlen(trim($latest_version)) == 0) {
         if (!$auto) {
            if ($messageafterredirect) {
               Session::addMessageAfterRedirect($error, true, ERROR);
            } else {
               echo "<div class='center'>$error</div>";
            }
         } else {
            return $error;
         }

      } else {
         if (version_compare($CFG_GLPI["version"], $latest_version, '<')) {
            $config_object                = new Config();
            $input["id"]                  = 1;
            $input["founded_new_version"] = $latest_version;
            $config_object->update($input);

            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(sprintf(__('A new version is available: %s.'),
                                                           $latest_version));
                  Session::addMessageAfterRedirect(__('You will find it on the GLPI-PROJECT.org site.'));
               } else {
                  echo "<div class='center'>".sprintf(__('A new version is available: %s.'),
                                                      $latest_version)."</div>";
                  echo "<div class='center'>".__('You will find it on the GLPI-PROJECT.org site.').
                       "</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(sprintf(__('A new version is available: %s.'),
                                                           $latest_version));
               } else {
                  return sprintf(__('A new version is available: %s.'), $latest_version);
               }
            }

         } else {
            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(__('You have the latest available version'));
               } else {
                  echo "<div class='center'>".__('You have the latest available version')."</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect(__('You have the latest available version'));
               } else {
                  return __('You have the latest available version');
               }
            }
         }
      }
      return 1;
   }


   /**
    * Determine if Imap/Pop is usable checking extension existence
    *
    * @return boolean
   **/
   static function canUseImapPop() {
      return extension_loaded('imap');
   }


   /**
    * Determine if Ldap is usable checking ldap extension existence
    *
    * @return boolean
   **/
   static function canUseLdap() {
      return extension_loaded('ldap');
   }


   /**
    * Check Write Access to a directory
    *
    * @param $dir string: directory to check
    *
    * @return 2 : creation error 1 : delete error 0: OK
   **/
   static function testWriteAccessToDirectory($dir) {

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

      $fw = fwrite($fp, "This file was created for testing reasons. ");
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
    * @param $itemtype  string   item type
    * @param $full               path or relative one (true by default)
    *
    * return string itemtype Form URL
   **/
   static function getItemTypeFormURL($itemtype, $full=true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if ($plug = isPluginItemType($itemtype)) {
         /* PluginFooBar => /plugins/foo/front/bar */
         $dir .= "/plugins/".strtolower($plug['plugin']);
         $item = strtolower($plug['class']);

      } else { // Standard case
         $item = strtolower($itemtype);
      }

      return "$dir/front/$item.form.php";
   }


   /**
    * Get search URL for itemtype
    *
    * @param $itemtype  string   item type
    * @param $full               path or relative one (true by default)
    *
    * return string itemtype search URL
   **/
   static function getItemTypeSearchURL($itemtype, $full=true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if ($plug = isPluginItemType($itemtype)) {
         $dir .=  "/plugins/".strtolower($plug['plugin']);
         $item = strtolower($plug['class']);

      } else { // Standard case
         if ($itemtype == 'Cartridge') {
            $itemtype = 'CartridgeItem';
         }
         if ($itemtype == 'Consumable') {
            $itemtype = 'ConsumableItem';
         }
         $item = strtolower($itemtype);
      }

      return "$dir/front/$item.php";
   }


   /**
    * Get ajax tabs url for itemtype
    *
    * @param $itemtype  string   item type
    * @param $full               path or relative one (true by default)
    *
    * return string itemtype tabs URL
   **/
   static function getItemTypeTabsURL($itemtype, $full=true) {
      global $CFG_GLPI;

      $filename = "/ajax/common.tabs.php";

      return ($full ? $CFG_GLPI['root_doc'] : '').$filename;
   }


   /**
    * Get a random string
    *
    * @param $length integer: length of the random string
    *
    * @return random string
   **/
   static function getRandomString($length) {

      $alphabet  = "1234567890abcdefghijklmnopqrstuvwxyz";
      $rndstring = "";

      for ($a=0 ; $a<$length ; $a++) {
         if (function_exists('random_int')) { // PHP 7+
            $b = random_int(0, strlen($alphabet) - 1);
         } else {
            $b = mt_rand(0, strlen($alphabet) - 1);
         }
         $rndstring .= $alphabet[$b];
      }
      return $rndstring;
   }


   /**
    * Split timestamp in time units
    *
    * @param $time integer: timestamp
    *
    * @return string
   **/
   static function getTimestampTimeUnits($time) {

      $time          = round(abs($time));
      $out['second'] = 0;
      $out['minute'] = 0;
      $out['hour']   = 0;
      $out['day']    = 0;

      $out['second'] = $time%MINUTE_TIMESTAMP;
      $time         -= $out['second'];

      if ($time > 0) {
         $out['minute'] = ($time%HOUR_TIMESTAMP)/MINUTE_TIMESTAMP;
         $time         -= $out['minute']*MINUTE_TIMESTAMP;

         if ($time > 0) {
            $out['hour'] = ($time%DAY_TIMESTAMP)/HOUR_TIMESTAMP;
            $time       -= $out['hour']*HOUR_TIMESTAMP;

            if ($time > 0) {
               $out['day'] = $time/DAY_TIMESTAMP;
            }
         }
      }
      return $out;
   }


   /**
    * Get a web page. Use proxy if configured
    *
    * @param string  $url    URL to retrieve
    * @param string  $msgerr set if problem encountered (default NULL)
    * @param integer $rec    internal use only Must be 0 (default 0)
    *
    * @return content of the page (or empty)
   **/
   static function getURLContent ($url, &$msgerr=NULL, $rec=0) {
      global $CFG_GLPI;

      $content = "";
      $taburl  = parse_url($url);

      $hostscheme  = '';
      $defaultport = 80;

      // Manage standard HTTPS port : scheme detection or port 443
      if ((isset($taburl["scheme"]) && $taburl["scheme"]=='https')
         || (isset($taburl["port"]) && $taburl["port"]=='443')) {
         $hostscheme  = 'ssl://';
         $defaultport = 443;
      }

      $ch = curl_init($url);
      $opts = [
         CURLOPT_URL             => $url,
         CURLOPT_USERAGENT       => "GLPI/".trim($CFG_GLPI["version"]),
         CURLOPT_RETURNTRANSFER  => 1
      ];

      if (!empty($CFG_GLPI["proxy_name"])) {
         // Connection using proxy
         $opts += [
            CURLOPT_PROXY           => $CFG_GLPI['proxy_name'],
            CURLOPT_PROXYPORT       => $CFG_GLPI['proxy_port'],
            CURLOPT_PROXYTYPE       => CURLPROXY_HTTP,
            CURLOPT_HTTPPROXYTUNNEL => 1
         ];

         if (!empty($CFG_GLPI["proxy_user"])) {
            $opts += [
               CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
               CURLOPT_PROXYUSERPWD => $CFG_GLPI["proxy_user"] . ":" . self::decrypt($CFG_GLPI["proxy_passwd"], GLPIKEY)
            ];
         }

      }

      curl_setopt_array($ch, $opts);
      $content = curl_exec($ch);
      $errstr = curl_error($ch);
      curl_close($ch);

      if ($errstr) {
         if (empty($CFG_GLPI["proxy_name"])) {
            //TRANS: %s is the error string
            $msgerr = sprintf(
               __('Connection failed. If you use a proxy, please configure it. (%s)'),
               $errstr
            );
         } else {
            //TRANS: %s is the error string
            $msgerr = sprintf(
               __('Failed to connect to the proxy server (%s)'),
               $errstr
            );
         }
         return '';
      }

      if (empty($content)) {
         $msgerr = __('No data available on the web site');
      }
      return $content;
   }


   /**
    * @param $need
    * @param $tab
   **/
   static function key_exists_deep($need, $tab) {

      foreach ($tab as $key => $value) {

         if ($need == $key) {
            return true;
         }

         if (is_array($value)
             && self::key_exists_deep($need, $value)) {
            return true;
         }

      }
      return false;
   }


   /**
    * Manage planning posted datas (must have begin + duration or end)
    * Compute end if duration is set
    *
    * @param $data array data to process
    *
    * @return processed datas
   **/
   static function manageBeginAndEndPlanDates(&$data) {

      if (!isset($data['end'])) {
         if (isset($data['begin'])
             && isset($data['_duration'])) {
            $begin_timestamp = strtotime($data['begin']);
            $data['end']     = date("Y-m-d H:i:s", $begin_timestamp+$data['_duration']);
            unset($data['_duration']);
         }
      }
   }


   /**
    * Manage login redirection
    *
    * @param $where string: where to redirect ?
   **/
   static function manageRedirect($where) {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      if (!empty($where)) {

         if (isset($_SESSION["glpiactiveprofile"]["interface"])
             && !empty($_SESSION["glpiactiveprofile"]["interface"])) {
            $decoded_where = rawurldecode($where);
            // redirect to URL : URL must be rawurlencoded
            if ($link = preg_match('/(https?:\/\/[^\/]+)\/.+/',$decoded_where, $matches)) {
               if($matches[1] !== $CFG_GLPI['url_base']) {
                  Session::addMessageAfterRedirect('Redirection failed');
                  if($_SESSION["glpiactiveprofile"]["interface"] === "helpdesk") {
                     Html::redirect($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
                  } else {
                     Html::redirect($CFG_GLPI["root_doc"]."/front/central.php");
                  }
               } else {
                  Html::redirect($decoded_where);
               }
            }
            // Redirect based on GLPI_ROOT : URL must be rawurlencoded
            if ($decoded_where[0] == '/') {
//                echo $decoded_where;exit();
               Html::redirect($CFG_GLPI["root_doc"].$decoded_where);
            }


            $data = explode("_", $where);
            $forcetab = '';
            // forcetab for simple items
            if (isset($data[2])) {
               $forcetab = 'forcetab='.$data[2];
            }

            switch ($_SESSION["glpiactiveprofile"]["interface"]) {
               case "helpdesk" :
                  switch (strtolower($data[0])) {
                     // Use for compatibility with old name
                     case "tracking" :
                     case "ticket" :
                        $data[0] = 'Ticket';
                        // redirect to item
                        if (isset($data[1])
                            && is_numeric($data[1])
                            && ($data[1] > 0)) {
                           // Check entity
                           if (($item = getItemForItemtype($data[0]))
                               && $item->isEntityAssign()) {
                              if ($item->getFromDB($data[1])) {
                                 if (!Session::haveAccessToEntity($item->getEntityID())) {
                                    Session::changeActiveEntities($item->getEntityID(),1);
                                 }
                              }
                           }
                           if ($_SESSION['glpiticket_timeline'] == 1) {
                              // force redirect to timeline when timeline is enabled and viewing
                              // Tasks or Followups
                              $forcetab = str_replace( 'TicketFollowup$1', 'Ticket$1', $forcetab);
                              $forcetab = str_replace( 'TicketTask$1', 'Ticket$1', $forcetab);
                           }
                           Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
                                          $data[1]."&$forcetab");
                        // redirect to list
                        } else if (!empty($data[0])) {
                           if ($item = getItemForItemtype($data[0])) {
                              Html::redirect($item->getSearchURL()."?$forcetab");
                           }
                        }

                        Html::redirect($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
                        break;

                     case "preference" :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/preference.php?$forcetab");
                        break;

                     case "reservation" :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/reservation.form.php?id=".
                                       $data[1]."&$forcetab");
                        break;

                     default :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
                        break;
                  }
                  break;

               case "central" :
                  switch (strtolower($data[0])) {
                     case "preference" :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/preference.php?$forcetab");
                        break;

                     // Use for compatibility with old name
                     // no break
                     case "tracking" :
                        $data[0] = "Ticket";

                     default :
                        // redirect to item
                        if (!empty($data[0] )
                            && isset($data[1])
                            && is_numeric($data[1])
                            && ($data[1] > 0)) {
                           // Check entity
                           if ($item = getItemForItemtype($data[0])) {
                              if ($item->isEntityAssign()) {
                                 if ($item->getFromDB($data[1])) {
                                    if (!Session::haveAccessToEntity($item->getEntityID())) {
                                       Session::changeActiveEntities($item->getEntityID(),1);
                                    }
                                 }
                              }
                              if ($_SESSION['glpiticket_timeline'] == 1 && $item->getType() == 'Ticket') {
                                 // force redirect to timeline when timeline is enabled
                                 $forcetab = str_replace( 'TicketFollowup$1', 'Ticket$1', $forcetab);
                                 $forcetab = str_replace( 'TicketTask$1', 'Ticket$1', $forcetab);
                              }
                              Html::redirect($item->getFormURL()."?id=".$data[1]."&$forcetab");
                           }
                        // redirect to list
                        } else if (!empty($data[0])) {
                           if ($item = getItemForItemtype($data[0])) {
                              Html::redirect($item->getSearchURL()."?$forcetab");
                           }
                        }

                        Html::redirect($CFG_GLPI["root_doc"]."/front/central.php");
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
    * @param $val string: config value (like 10k, 5M)
    *
    * @return $val
   **/
   static function return_bytes_from_ini_vars($val) {

      $val  = trim($val);
      $last = self::strtolower($val[strlen($val)-1]);
      $val  = (int)$val;

      switch($last) {
         // Le modifieur 'G' est disponible depuis PHP 5.1.0
         case 'g' :
            $val *= 1024;
            // no break;

         case 'm' :
            $val *= 1024;
            // no break;

         case 'k' :
            $val *= 1024;
            // no break;
      }

      return $val;
   }

   /**
    * Parse imap open connect string
    *
    * @since version 0.84
    *
    * @param $value string: connect string
    * @param $forceport boolean: force compute port if not set (false by default)
    *
    * @return array of parsed arguments (address, port, mailbox, type, ssl, tls, validate-cert
    *         norsh, secure and debug) : options are empty if not set
    *                                    and options have boolean values if set
   **/
   static function parseMailServerConnectString($value, $forceport=false) {

      $tab = array();
      if (strstr($value,":")) {
         $tab['address'] = str_replace("{", "", preg_replace("/:.*/", "", $value));
         $tab['port']    = preg_replace("/.*:/", "", preg_replace("/\/.*/", "", $value));

      } else {
         if (strstr($value,"/")) {
            $tab['address'] = str_replace("{", "", preg_replace("/\/.*/", "", $value));
         } else {
            $tab['address'] = str_replace("{", "", preg_replace("/}.*/", "", $value));
         }
         $tab['port'] = "";
      }
      $tab['mailbox'] = preg_replace("/.*}/", "", $value);

      $tab['type']    = '';
      if (strstr($value,"/imap")) {
         $tab['type'] = 'imap';
      } else if (strstr($value,"/pop")) {
         $tab['type'] = 'pop';
      }
      $tab['ssl'] = false;
      if (strstr($value,"/ssl")) {
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
      if (strstr($value,"/tls")) {
         $tab['tls'] = true;
      }
      if (strstr($value,"/notls")) {
         $tab['tls'] = false;
      }
      $tab['validate-cert'] = '';
      if (strstr($value,"/validate-cert")) {
         $tab['validate-cert'] = true;
      }
      if (strstr($value,"/novalidate-cert")) {
         $tab['validate-cert'] = false;
      }
      $tab['norsh'] = '';
      if (strstr($value,"/norsh")) {
         $tab['norsh'] = true;
      }
      $tab['secure'] = '';
      if (strstr($value,"/secure")) {
         $tab['secure'] = true;
      }
      $tab['debug'] = '';
      if (strstr($value,"/debug")) {
         $tab['debug'] = true;
      }

      return $tab;
   }


   /**
    * Display a mail server configuration form
    *
    * @param $value String host connect string ex
    *                      {localhost:993/imap/ssl}INBOX
    *
    * @return String type of the server (imap/pop)
   **/
   static function showMailServerConfig($value) {

      if (!Config::canUpdate()) {
         return false;
      }

      $tab = Toolbox::parseMailServerConnectString($value);

      echo "<tr class='tab_bg_1'><td>" . __('Server') . "</td>";
      echo "<td><input size='30' type='text' name='mail_server' value=\"" .$tab['address']. "\">";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . __('Connection options') . "</td><td>";
      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/imap' => __('IMAP'),
                     //TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/pop' => __('POP'),);

      $svalue = (!empty($tab['type'])?'/'.$tab['type']:'');

      Dropdown::showFromArray('server_type', $values,
                              array('value'               => $svalue,
                                    'display_emptychoice' => true));
      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/ssl' => __('SSL'));

      $svalue = ($tab['ssl']?'/ssl':'');

      Dropdown::showFromArray('server_ssl', $values,
                              array('value'               => $svalue,
                                    'display_emptychoice' => true));

      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/tls' => __('TLS'),
                     //TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/notls' => __('NO-TLS'),);

      $svalue = '';
      if (($tab['tls'] === true)) {
         $svalue = '/tls';
      }
      if (($tab['tls'] === false)) {
         $svalue = '/notls';
      }

      Dropdown::showFromArray('server_tls', $values,
                              array('value'               => $svalue,
                                    'width'               => '14%',
                                    'display_emptychoice' => true));

      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/novalidate-cert' => __('NO-VALIDATE-CERT'),
                     //TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/validate-cert' => __('VALIDATE-CERT'),);

      $svalue = '';
      if (($tab['validate-cert'] === false)) {
         $svalue = '/novalidate-cert';
      }
      if (($tab['validate-cert'] === true)) {
         $svalue = '/validate-cert';
      }

      Dropdown::showFromArray('server_cert', $values,
                              array('value'               => $svalue,
                                    'display_emptychoice' => true));

      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/norsh' => __('NORSH'));

      $svalue = ($tab['norsh'] === true?'/norsh':'');

      Dropdown::showFromArray('server_rsh', $values,
                              array('value'               => $svalue,
                                    'display_emptychoice' => true));

      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/secure' => __('SECURE'));

      $svalue = ($tab['secure'] === true?'/secure':'');

      Dropdown::showFromArray('server_secure', $values,
                              array('value'               => $svalue,
                                    'display_emptychoice' => true));

      $values = array(//TRANS: imap_open option see http://www.php.net/manual/en/function.imap-open.php
                     '/debug' => __('DEBUG'));

      $svalue = ($tab['debug'] === true?'/debug':'');

      Dropdown::showFromArray('server_debug', $values,
                              array('value'               => $svalue,
                                    'width'               => '12%',
                                    'display_emptychoice' => true));


      echo "<input type=hidden name=imap_string value='".$value."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>". __('Incoming mail folder (optional, often INBOX)')."</td>";
      echo "<td><input size='30' type='text' name='server_mailbox' value=\"" . $tab['mailbox'] . "\" >";
      echo "</td></tr>\n";

      //TRANS: for mail connection system
      echo "<tr class='tab_bg_1'><td>" . __('Port (optional)') . "</td>";
      echo "<td><input size='10' type='text' name='server_port' value='".$tab['port']."'></td></tr>\n";
      if (empty($value)) {
         $value = "&nbsp;";
      }
      //TRANS: for mail connection system
      echo "<tr class='tab_bg_1'><td>" . __('Connection string') . "</td>";
      echo "<td class='b'>$value</td></tr>\n";

      return $tab['type'];
   }


   /**
    * @param $input
   **/
   static function constructMailServerConfig($input) {

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
      if (isset($input['server_cert']) && !empty($input['server_cert'])
          && (!empty($input['server_ssl']) || !empty($input['server_tls']))) {
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


   static function getDaysOfWeekArray() {

      $tab[0] = __("Sunday");
      $tab[1] = __("Monday");
      $tab[2] = __("Tuesday");
      $tab[3] = __("Wednesday");
      $tab[4] = __("Thursday");
      $tab[5] = __("Friday");
      $tab[6] = __("Saturday");

      return $tab;
   }


   static function getMonthsOfYearArray() {

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
    * @since version 0.84
    *
    * @param $string    string   to search
    * @param $datas     array    to search to search
    *
    * @return boolean : string founded ?
   **/
   static function inArrayCaseCompare($string, $datas=array()) {

      if (count($datas)) {
         foreach ($datas as $tocheck) {
            if (strcasecmp($string, $tocheck) == 0) {
               return true;
            }
         }
      }
      return false;
   }


   /**
    * Clean integer value (strip all chars not - and spaces )
    *
    * @since versin 0.83.5
    *
    * @param $integer string   integer string
    *
    * @return clean integer
   **/
   static function cleanInteger($integer) {
      return preg_replace("/[^0-9-]/", "", $integer);
   }


   /**
    * Clean decimal value (strip all chars not - and spaces )
    *
    * @since versin 0.83.5
    *
    * @param $decimal string    float string
    *
    * @return clean integer
   **/
   static function cleanDecimal($decimal) {
      return preg_replace("/[^0-9\.-]/", "", $decimal);
   }


   /**
    * Clean new lines of a string
    *
    * @since versin 0.85
    *
    * @param $string string     string to clean
    *
    * @return clean string
   **/
   static function cleanNewLines($string) {

      $string = preg_replace("/\r\n/", " ", $string);
      $string = preg_replace("/\n/", " ", $string);
      $string = preg_replace("/\r/", " ", $string);
      return $string;
   }


   /**
    * Create the GLPI default schema
    *
    * @since 9.1
    *
    * @param $lang
    *
    * @return nothing
   **/
   static function createSchema($lang='en_GB') {
      global $CFG_GLPI, $DB;

      include_once (GLPI_CONFIG_DIR . "/config_db.php");

      $DB = new DB();
      if (!$DB->runFile(GLPI_ROOT ."/install/mysql/glpi-" . GLPI_SCHEMA_VERSION . "-empty.sql")) {
         echo "Errors occurred inserting default database";
      } else {
         // update default language
         Config::setConfigurationValues(
            'core',
            array(
               'language' => $lang,
               'version'  => GLPI_VERSION
            )
         );
         $query = "UPDATE `glpi_users`
                   SET `language` = NULL";
         $DB->queryOrDie($query, "4203");

         if (defined('GLPI_SYSTEM_CRON')) {
            // Downstream packages may provide a good system cron
            $query = "UPDATE `glpi_crontasks` SET `mode`=2 WHERE `name`!='watcher' AND (`allowmode` & 2)";
            $DB->queryOrDie($query, "4203");
         }
      }
   }


   /**
    * Save a configuration file
    *
    * @since version 0.84
    *
    * @param $name      string   config file name
    * @param $content   string   config file content
    *
    * @return boolean
   **/
   static function writeConfig($name, $content) {

      $name = GLPI_CONFIG_DIR . '/'.$name;
      $fp   = fopen($name, 'wt');
      if ($fp) {
         $fw = fwrite($fp, $content);
         fclose($fp);
         if (function_exists('opcache_invalidate')) {
            /* Invalidate Zend OPcache to ensure saved version used */
            opcache_invalidate($name, true);
         }
         return ($fw>0);
      }
      return false;
   }


   /**
    * Prepare array passed on an input form
    *
    * @param $value array   passed array
    *
    * @since version 0.83.91
    *
    * @return string encoded array
   **/
   static function prepareArrayForInput(array $value) {
      return base64_encode(json_encode($value));
   }


   /**
    * Decode array passed on an input form
    *
    * @param $value string   encoded value
    *
    * @since version 0.83.91
    *
    * @return string decoded array
   **/
   static function decodeArrayFromInput($value) {

      if ($dec = base64_decode($value)) {
         if ($ret = json_decode($dec,true)) {
            return $ret;
         }
      }
      return array();
   }


   /**
    * Check valid referer accessing GLPI
    *
    * @since version 0.84.2
    *
    * @return nothing : display error if not permit
   **/
   static function checkValidReferer() {
      global $CFG_GLPI;

      $isvalidReferer = true;

      if (!isset($_SERVER['HTTP_REFERER'])){
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Html::displayErrorAndDie(__("No HTTP_REFERER found in request. Reload previous page before doing action again."),
                                  true);
            $isvalidReferer = false;
         }
      }
      else if (!is_array($url = parse_url($_SERVER['HTTP_REFERER']))){
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Html::displayErrorAndDie(__("Error when parsing HTTP_REFERER. Reload previous page before doing action again."),
                                  true);
            $isvalidReferer = false;
         }
      }

      if(!isset($url['host'])
          || (($url['host'] != $_SERVER['SERVER_NAME'])
            && (!isset($_SERVER['HTTP_X_FORWARDED_SERVER'])
               || ($url['host'] != $_SERVER['HTTP_X_FORWARDED_SERVER'])))){
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Html::displayErrorAndDie(__("None or Invalid host in HTTP_REFERER. Reload previous page before doing action again."),
                                  true);
            $isvalidReferer = false;
         }
      }

      if(!isset($url['path'])
          || (!empty($CFG_GLPI['root_doc'])
            && (strpos($url['path'], $CFG_GLPI['root_doc']) !== 0))) {
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Html::displayErrorAndDie(__("None or Invalid path in HTTP_REFERER. Reload previous page before doing action again."),
                                  true);
            $isvalidReferer = false;
         }
      }

      if(!$isvalidReferer && $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE){
            Html::displayErrorAndDie(__("The action you have requested is not allowed. Reload previous page before doing action again."),
                                  true);
      }
   }


   /**
    * Check if the given object is of the type $class_name. Can be identical or a subclass.
    * This method emulates PHP 5.3.9: is_a with allow_string == true
    *
    * @todo: remove when prerequisite > 5.3.9 !
    *
    * @since version 0.85
    *
    * @param $object        can be an object or a string contining the class name
    * @param $class_name    the name of the class to compare
    *
    * @return true if $object is an instance of $class_name
    *
   **/
   static function is_a($object, $class_name) {

      if (is_object($object)) {
         return is_a($object, $class_name);
      }
      if (is_string($object)) {
         if ($object == $class_name) {
            return true;
         }
         return is_subclass_of($object, $class_name);
      }
      return false;
   }


   /**
    * Retrieve the mime type of a file
    *
    * @since version 0.85.5
    *
    * @param $file   string      path of the file
    * @param $type   string      check if $file is the correct type (false by default)
    *
    * @return string (if $type not given) else boolean
    *
   **/
   static function getMime($file, $type=false) {

      static $finfo = NULL;

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
    * @since version 9.1
    *
    * @param mixed $needle
    * @param array $haystack
    * @param bool  $strict: If strict is set to TRUE then it will also
    *              check the types of the needle in the haystack.
    * @return bool
    */
   static function in_array_recursive($needle, $haystack, $strict = false) {

      $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

      foreach($it AS $element) {
         if( $strict ) {
            if($element === $needle) {
               return true;
            }
         } else {
            if($element == $needle) {
               return true;
            }
         }
      }
      return false;
   }

   /**
    * Sanitize received values
    *
    * @param array $array
    *
    * @return array
    */
   static public function sanitize($array) {
      $array = array_map('Toolbox::addslashes_deep', $array);
      $array = array_map('Toolbox::clean_cross_side_scripting_deep', $array);
      return $array;
   }

   /**
    * Decode JSON in GLPI
    * Because json can have been modified from addslashes_deep
    *
    * @param string $encoded Encoded JSON
    *
    * @return mixed
    */
   static public function jsonDecode($encoded) {
      if (!is_string($encoded)) {
         Toolbox::logDebug('Only strings can be json to decode!');
         return $encoded;
      }

      $json = json_decode($encoded);

      if (json_last_error() != JSON_ERROR_NONE) {
         //something went wrong... Try to stripslashes before decoding.
         $json = json_decode(self::stripslashes_deep($encoded));
         if (json_last_error() != JSON_ERROR_NONE) {
            Toolbox::logDebug('Unable to decode JSON string! Is this really JSON?');
            return $encoded;
         }
      }

      return $json;
   }

   /**
    * Checks if a string starts with anotehr one
    *
    * @since 9.1.5
    *
    * @param string $haystack String to check
    * @param string $needle   String to find
    *
    * @return boolean
    */
   static public function startsWith($haystack, $needle) {
      $length = strlen($needle);
      return (substr($haystack, 0, $length) === $needle);
   }
}
