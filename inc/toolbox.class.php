<?php
/*
 * @version $Id:
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Toolbox
class Toolbox {


   /**
    * Convert first caracter in upper
    *
    * @since version 0.83
    *
    * @param $str string to change
    *
    * @return string changed
    */
   static function ucfirst($str) {

      // for foreign language
      $str[0] = mb_strtoupper($str[0], 'UTF-8');
      return $str;
    }


   /**
    * substr function for utf8 string
    *
    * @param $str string: string
    * @param $tofound string: string to found
    * @param $offset integer: The search offset. If it is not specified, 0 is used.
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
    * @param $input string: input string
    * @param $pad_length integer: padding length
    * @param $pad_string string: padding string
    * @param $pad_type: integer: padding type
    *
    * @return string
   **/
   static function str_pad($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT) {

       $diff = strlen($input) - self::strlen($input);
       return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
   }


   /**
    * strlen function for utf8 string
    *
    * @param $str string: string
    *
    * @return length of the string
   **/
   static function strlen($str) {
      return mb_strlen($str, "UTF-8");
   }


   /**
    * substr function for utf8 string
    *
    * @param $str string: string
    * @param $start integer: start of the result substring
    * @param $length integer: The maximum length of the returned string if > 0
    *
    * @return substring
   **/
   static function substr($str, $start, $length=-1) {

      if ($length==-1) {
         $length = self::strlen($str)-$start;
      }
      return mb_substr($str, $start, $length, "UTF-8");
   }


   /**
    * strtolower function for utf8 string
    *
    * @param $str string: string
    *
    * @return lower case string
   **/
   static function strtolower($str) {
      return mb_strtolower($str, "UTF-8");
   }


   /**
    * strtoupper function for utf8 string
    *
    * @param $str string: string
    *
    * @return upper case string
   **/
   static function strtoupper($str) {
      return mb_strtoupper($str, "UTF-8");
   }


   /**
    * Is a string seems to be UTF-8 one ?
    *
    * @param $str string: string to analyze
    *
    * @return  boolean
   **/
   static function seems_utf8($str) {
      return mb_check_encoding($str, "UTF-8");
   }


   /**
    * Encode string to UTF-8
    *
    * @param $string string: string to convert
    * @param $from_charset string: original charset (if 'auto' try to autodetect)
    *
    * @return utf8 string
   **/
   static function encodeInUtf8($string, $from_charset="ISO-8859-1") {

      if (strcmp($from_charset,"auto")==0) {
         $from_charset = mb_detect_encoding($string);
      }
      return mb_convert_encoding($string, "UTF-8", $from_charset);
   }


   /**
    * Decode string from UTF-8 to specified charset
    *
    * @param $string string: string to convert
    * @param $to_charset string: destination charset (default is ISO-8859-1)
    *
    * @return converted string
   **/
   static function decodeFromUtf8($string, $to_charset="ISO-8859-1") {
      return mb_convert_encoding($string, $to_charset, "UTF-8");
   }


   /**
    * Encrypt a string
    *
    * @param $string string to encrypt
    * @param $key string key used to encrypt
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
    * @param $string string to decrypt
    * @param $key string key used to decrypt
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

     return $result;
   }


   /** Returns the utf string corresponding to the unicode value
    * (from php.net, courtesy - romans@void.lv)
    *
    * @param $num integer: character code
   **/
/*  NOT USED
   static function code2utf($num) {

      if ($num < 128) {
         return chr($num);
      }

      if ($num < 2048) {
         return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
      }

      if ($num < 65536) {
         return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
      }

      if ($num < 2097152) {
         return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) .
                chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
      }

      return '';
   }
*/

   /**
    * Log in 'php-errors' all args
   **/
   static function logDebug() {
      static $tps = 0;

      $msg = "";
      foreach (func_get_args() as $arg) {
         if (is_array($arg) || is_object($arg)) {
            $msg .= ' ' . print_r($arg, true);
         } else if (is_null($arg)) {
            $msg .= ' NULL';
         } else if (is_bool($arg)) {
            $msg .= ' '.($arg ? 'true' : 'false');
         } else {
            $msg .= ' ' . $arg;
         }
      }

      if ($tps && function_exists('memory_get_usage')) {
         $msg .= ' ('.number_format(microtime(true)-$tps,3).'", '.
                 number_format(memory_get_usage()/1024/1024,2).'Mio)';
      }

      $tps = microtime(true);
      self ::logInFile('php-errors', $msg."\n",true);
   }


   /**
    * Log a message in log file
    *
    * @param $name string: name of the log file
    * @param $text string: text to log
    * @param $force boolean: force log in file not seeing use_log_in_files config
   **/
   static function logInFile($name, $text, $force=false) {
      global $CFG_GLPI;

      $user = '';
      if (function_exists('getLoginUserID')) {
         $user = " [".getLoginUserID().'@'.php_uname('n')."]";
      }

      if (isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"]||$force) {
         error_log(convDateTime(date("Y-m-d H:i:s"))."$user\n".$text,
                   3, GLPI_LOG_DIR."/".$name.".log");
      }
   }


   /**
    * Specific error handler in Normal mode
    *
    * @param $errno integer: level of the error raised.
    * @param $errmsg string: error message.
    * @param $filename string: filename that the error was raised in.
    * @param $linenum integer: line number the error was raised at.
    * @param $vars array: that points to the active symbol table at the point the error occurred.
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

      $err = $errortype[$errno] . "($errno): $errmsg\n";
      if (in_array($errno, $user_errors)) {
         $err .= "Variables:".wddx_serialize_value($vars, "Variables")."\n";
      }

      if (function_exists("debug_backtrace")) {
         $err   .= "Backtrace :\n";
         $traces = debug_backtrace();
         foreach ($traces as $trace) {
            if (isset($trace["file"]) && isset($trace["line"])) {
               $err .= $trace["file"] . ":" . $trace["line"] . "\t\t"
                       . (isset($trace["class"]) ? $trace["class"] : "")
                       . (isset($trace["type"]) ? $trace["type"] : "")
                       . (isset($trace["function"]) ? $trace["function"]."()" : ""). "\n";
            }
         }

      } else {
         $err .= "Script: $filename, Line: $linenum\n" ;
      }

      // sauvegarde de l'erreur, et mail si c'est critique
      self::logInFile("php-errors", $err."\n");

      return $errortype[$errno];
   }


   /**
    * Specific error handler in Debug mode
    *
    * @param $errno integer: level of the error raised.
    * @param $errmsg string: error message.
    * @param $filename string: filename that the error was raised in.
    * @param $linenum integer: line number the error was raised at.
    * @param $vars array: that points to the active symbol table at the point the error occurred.
   **/
   static function userErrorHandlerDebug($errno, $errmsg, $filename, $linenum, $vars) {

      // For file record
      $type = self::userErrorHandlerNormal($errno, $errmsg, $filename, $linenum, $vars);

      // Display
      if (!isCommandLine()) {
         echo '<div style="position:fload-left; background-color:red; z-index:10000"><strong>PHP '.
                $type.': </strong>';
         echo $errmsg.' in '.$filename.' at line '.$linenum.'</div>';
      } else {
         echo 'PHP '.$type.': '.$errmsg.' in '.$filename.' at line '.$linenum."\n";
      }
   }


   /**
    * Send a file (not a document) to the navigator
    * See Document->send();
    *
    * @param $file string: storage filename
    * @param $filename string: file title
    *
    * @return nothing
   **/
   static function sendFile($file, $filename) {

      // Test securite : document in DOC_DIR
      $tmpfile = str_replace(GLPI_DOC_DIR, "", $file);

      if (strstr($tmpfile,"../") || strstr($tmpfile,"..\\")) {
         Event::log($file, "sendFile", 1, "security",
                    $_SESSION["glpiname"]." try to get a non standard file.");
         die("Security attack !!!");
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

      $value = is_array($value) ? array_map(array(__CLASS__, 'addslashes_deep'), $value)
                                : (is_null($value) ? NULL : mysql_real_escape_string($value));

      return $value;
   }


   /**
    * Strip slash  for variable & array
    *
    * @param $value array or string: item to stripslashes (array or string)
    * @return stripslashes item
   **/
   static function stripslashes_deep($value) {

      $value = is_array($value) ? array_map(array(__CLASS__, 'stripslashes_deep'), $value)
                                : (is_null($value) ? NULL : stripslashes($value));

      return $value;
   }


   /** Add an item to the navigate through search results list
    *
    * @param $itemtype device type
    * @param $ID ID of the item
   **/
   static function addToNavigateListItems($itemtype, $ID) {
      $_SESSION['glpilistitems'][$itemtype][] = $ID;
   }


   /**
    * Recursivly execute nl2br on an Array
    *
    * @param $value string or array
    *
    * @return array of value (same struct as input)
   **/
   static function nl2br_deep($value) {

      return (is_array($value) ? array_map(array(__CLASS__, 'nl2br_deep'), $value)
                               : nl2br($value));
   }
}
?>