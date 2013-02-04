<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


// class Toolbox
class Toolbox {

   /**
    * Wrapper for get_magic_quotes_runtime
    *
    * @since version 0.83
    *
    * @return boolean
    */
   static function get_magic_quotes_runtime() {

      // Deprecated function(8192): Function get_magic_quotes_runtime() is deprecated
      if (PHP_VERSION_ID < 50400) {
         return get_magic_quotes_runtime();
      }
      return 0;
   }


   /**
    * Wrapper for get_magic_quotes_gpc
    *
    * @since version 0.83
    *
    * @return boolean
    */
   static function get_magic_quotes_gpc() {

      // Deprecated function(8192): Function get_magic_quotes_gpc() is deprecated
      if (PHP_VERSION_ID < 50400) {
         return get_magic_quotes_gpc();
      }
      return 0;
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
         return (($str{1}>="\xa0") ? ($str{0}.chr(ord($str{1})-32))
                                   : ($str{0}.$str{1})).substr($str,2);
      } else {
         return ucfirst($str);
      }
    }


   /**
    * to underline shortcut letter
    *
    * @since version 0.83
    *
    * @param $str string from dico
    * @param $shortcut letter of shortcut
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

      $value = is_array($value) ? array_map(array(__CLASS__, 'clean_cross_side_scripting_deep'),
                                            $value)
                                : (is_null($value) ? NULL : str_replace($in,$out,$value));

      return $value;
   }


   /**
    *  Invert fonction from clean_cross_side_scripting_deep
    *
    * @param $value array or string: item to unclean from clean_cross_side_scripting_deep
    *
    * @return unclean item
    *
    * @see clean_cross_side_scripting_deep
   **/
   static function unclean_cross_side_scripting_deep($value) {

      $in  = array('<', '>');
      $out = array('&lt;', '&gt;');

      $value = is_array($value) ? array_map(array(__CLASS__, 'unclean_cross_side_scripting_deep'),
                                            $value)
                                : (is_null($value) ? NULL : str_replace($out,$in,$value));

      return $value;
   }

   /**
    *  Invert fonction from clean_cross_side_scripting_deep to display HTML striping XSS code
    *
    * @since version 0.83.3
    * @param $value array or string: item to unclean from clean_cross_side_scripting_deep
    *
    * @return unclean item
    *
    * @see clean_cross_side_scripting_deep
   **/
   static function unclean_html_cross_side_scripting_deep($value) {
      $in  = array('<', '>');
      $out = array('&lt;', '&gt;');
      $value = is_array($value) ? array_map(array(__CLASS__, 'unclean_html_cross_side_scripting_deep'),
                                            $value)
                                : (is_null($value) ? NULL : str_replace($out,$in,$value));

      include_once(GLPI_HTMLAWED);

      $config = array('safe'=>1);
      $config["elements"] = "*+iframe";
      $value = htmLawed($value, $config);

      return $value;
   }


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
      self::logInFile('php-errors', $msg."\n",true);
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
      if (function_exists('Session::getLoginUserID')) {
         $user = " [".Session::getLoginUserID().'@'.php_uname('n')."]";
      }

      if (isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"]||$force) {
         error_log(Html::convDateTime(date("Y-m-d H:i:s"))."$user\n".$text,
                   3, GLPI_LOG_DIR."/".$name.".log");
      }

      if (isset($_SESSION['glpi_use_mode'])
          && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && isCommandLine()) {
         fwrite(STDERR, $text);
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
         echo '<div style="position:fload-left; background-color:red; z-index:10000">'.
              '<span class="b">PHP '.$type.': </span>';
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


   /** Converts an array of parameters into a query string to be appended to a URL.
    *
    * @param $array  array: parameters to append to the query string.
    * @param $separator separator : default is & : may be defined as &amp; to display purpose
    * @param $parent This should be left blank (it is used internally by the function).
    *
    * @return string  : Query string to append to a URL.
   **/
   static function append_params($array, $separator='&', $parent='') {

      $params = array();
      foreach ($array as $k => $v) {

         if (is_array($v)) {
            $params[] = self::append_params($v, $separator,
                                            (empty($parent) ? rawurlencode($k)
                                                            : $parent .'[' .rawurlencode($k) . ']'));
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
    * @return memory limit
   **/
   static function getMemoryLimit() {

      $mem = ini_get("memory_limit");
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
      if ($mem<64*1024*1024) {
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
      global $LANG;

      $error = 0;

      // Title
      echo "<tr><th>".$LANG['install'][6]."</th><th >".$LANG['install'][7]."</th></tr>";

      // Parser test
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][8]."</td>";

      // PHP Version  - exclude PHP3, PHP 4 and zend.ze1 compatibility
      if (substr(phpversion(),0,1) == "5") {
         // PHP > 5 ok, now check PHP zend.ze1_compatibility_mode
         if (ini_get("zend.ze1_compatibility_mode") == 1) {
            $error = 2;
            echo "<td class='red'>
                  <img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][10]."</td></tr>";
         } else {
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][11]."\"
                       title=\"".$LANG['install'][11]."\"></td></tr>";
         }

      } else { // PHP <5
         $error = 2;
         echo "<td class='red'>
               <img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][9]."</td></tr>";
      }

      // Check for mysql extension ni php
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][71]."</td>";
      if (!function_exists("mysql_query")) {
         echo "<td class='red'>";
         echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][72]."</td></tr>";
         $error = 2;
      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][73].
                    "\" title=\"".$LANG['install'][73]."\"></td></tr>";
      }

      // session test
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][12]."</td>";

      // check whether session are enabled at all!!
      if (!extension_loaded('session')) {
         $error = 2;
         echo "<td class='red b'>".$LANG['install'][13]."</td></tr>";

      } else if ((isset($_SESSION["Test_session_GLPI"]) && $_SESSION["Test_session_GLPI"] == 1) // From install
                 || isset($_SESSION["glpi_currenttime"])) { // From Update
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][14].
                    "\" title=\"".$LANG['install'][14]."\"></td></tr>";

      } else if ($error != 2) {
         echo "<td class='red'>";
         echo "<img src='".GLPI_ROOT."/pics/orangebutton.png'>".$LANG['install'][15]."</td></tr>";
         $error = 1;
      }

      //Test for session auto_start
      if (ini_get('session.auto_start')==1) {
         echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][68]."</td>";
         echo "<td class='red'>";
         echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][69]."</td></tr>";
         $error = 2;
      }

      //Test for option session use trans_id loaded or not.
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][74]."</td>";

      if (isset($_POST[session_name()]) || isset($_GET[session_name()])) {
         echo "<td class='red'>";
         echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][75]."</td></tr>";
         $error = 2;

      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][76].
                    "\" title=\"".$LANG['install'][76]."\"></td></tr>";
      }

      //Test for sybase extension loaded or not.
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][65]."</td>";

      if (ini_get('magic_quotes_sybase')) {
         echo "<td class='red'>";
         echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][66]."</td></tr>";
         $error = 2;

      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][67].
                    "\" title=\"".$LANG['install'][67]."\"></td></tr>";
      }
      
      //Test for ctype extension loaded or not (forhtmlawed)
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][79]."</td>";

      if (!function_exists('ctype_digit')) {
         echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][78]."></td></tr>";
         $error = 2;

      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][85].
                    "\" title=\"".$LANG['install'][85]."\"></td></tr>";
      }

      //Test for json_encode function.
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][102]."</td>";

      if (!function_exists('json_encode') || !function_exists('json_decode')) {
         echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][103]."></td></tr>";
         $error = 2;

      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][85].
                    "\" title=\"".$LANG['install'][85]."\"></td></tr>";
      }

      //Test for mbstring extension.
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][104]."</td>";

      if (!extension_loaded('mbstring')) {
         echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][105]."></td></tr>";
         $error = 2;

      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][85].
                    "\" title=\"".$LANG['install'][85]."\"></td></tr>";
      }

      // memory test
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][86]."</td>";

      //Get memory limit
      $mem = self::getMemoryLimit();
      switch (self::checkMemoryLimit()) {
         case 0: // memory_limit not compiled -> no memory limit
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][95]." - ".
                       $LANG['install'][89]."\" title=\"".$LANG['install'][95]." - ".
                       $LANG['install'][89]."\"></td></tr>";
            break;

         case 1: // memory_limit compiled and unlimited
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][96]." - ".
                       $LANG['install'][89]."\" title=\"".$LANG['install'][96]." - ".
                       $LANG['install'][89]."\"></td></tr>";
            break;

         case 2: //Insufficient memory
            $showmem = $mem/1048576;
            echo "<td class='red'>
                  <img src='".GLPI_ROOT."/pics/redbutton.png'><span class='b'>".
                   $LANG['install'][87]." $showmem ".$LANG['common'][82]."</span>".
                   "<br>".$LANG['install'][88]."<br>".$LANG['install'][90]."</td></tr>";
            $error = 2;
            break;

         case 3: //Got enough memory, going to the next step
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][91]." - ".
                       $LANG['install'][89]."\" title=\"".$LANG['install'][91]." - ".
                       $LANG['install'][89]."\"></td></tr>";
            break;
      }
      $suberr = Config::checkWriteAccessToDirs();
      if ($suberr > $error) {
         $error = $suberr;
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
    *  @return integer 0: OK, 1:Warning, 2:Error
    **/
   static function checkSELinux() {
      global $LANG;

      if (DIRECTORY_SEPARATOR!='/' || !file_exists('/usr/sbin/getenforce')) {
         // This is not a SELinux system
         return 0;
      }

      $mode = exec("/usr/sbin/getenforce");
      //TRANS: %s is mode name (Permissive, Enforcing of Disabled)
      $msg = $LANG['setup'][16].' '.$mode;
      echo "<tr class='tab_bg_1'><td class='left b'>$msg</td>";
      // All modes should be ok
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='$mode' title='$mode'></td></tr>";
      if (!strcasecmp($mode, 'Disabled')) {
         // Other test are not useful
         return 0;
      }

      $err = 0;

      // No need to check file context as checkWriteAccessToDirs will show issues

      // Enforcing mode will block some feature (notif, ...)
      // Permissive mode will write lot of stuff in audit.log

      $bools = array('httpd_can_network_connect', 'httpd_can_network_connect_db', 'httpd_can_sendmail');
      foreach ($bools as $bool) {
         $state = exec('/usr/sbin/getsebool '.$bool);
         //TRANS: %s is an option name
         $msg = $LANG['setup'][17].' '.$state;
         echo "<tr class='tab_bg_1'><td class='left b'>$msg</td>";
         if (substr($state, -2) == 'on') {
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='$state' title='$state'></td></tr>";
         } else {
            echo "<td><img src='".GLPI_ROOT."/pics/orangebutton.png' alt='$state' title='$state'></td></tr>";
            $err = 1;
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
            if ($file!='.' && $file!='..') {
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

      $bytes = array('o', 'Kio', 'Mio', 'Gio', 'Tio');
      foreach ($bytes as $val) {
         if ($size > 1024) {
            $size = $size / 1024;
         } else {
            break;
         }
      }
      return round($size, 2)." ".$val;
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
               if ($element != "." && $element != "..") {

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
    * Check if new version is available
    *
    * @param $auto boolean: check done autically ? (if not display result)
    * @param $messageafterredirect boolean: use message after redirect instead of display
    *
    * @return string explaining the result
   **/
   static function checkNewVersionAvailable($auto=true, $messageafterredirect=false) {
      global $LANG, $CFG_GLPI;

      if (!$auto && !Session::haveRight("check_update","r")) {
         return false;
      }

      if (!$auto && !$messageafterredirect) {
         echo "<br>";
      }

      $error = "";
      $latest_version = self::getURLContent("http://glpi-project.org/latest_version", $error);

      if (strlen(trim($latest_version))==0) {

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
         $splitted = explode(".", trim($CFG_GLPI["version"]));

         if ($splitted[0]<10) {
            $splitted[0] .= "0";
         }

         if ($splitted[1]<10) {
            $splitted[1] .= "0";
         }

         $cur_version = $splitted[0]*10000+$splitted[1]*100;

         if (isset($splitted[2])) {
            if ($splitted[2]<10) {
               $splitted[2] .= "0";
            }
            $cur_version += $splitted[2];
         }

         $splitted = explode(".", trim($latest_version));

         if ($splitted[0]<10) {
            $splitted[0] .= "0";
         }

         if ($splitted[1]<10) {
            $splitted[1] .= "0";
         }

         $lat_version = $splitted[0]*10000+$splitted[1]*100;

         if (isset($splitted[2])) {
            if ($splitted[2]<10) {
               $splitted[2] .= "0";
            }
            $lat_version += $splitted[2];
         }

         if ($cur_version < $lat_version) {
            $config_object = new Config();
            $input["id"]   = 1;
            $input["founded_new_version"] = $latest_version;
            $config_object->update($input);

            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($LANG['setup'][301]." ".$latest_version.
                                                   $LANG['setup'][302]);

               } else {
                  echo "<div class='center'>".$LANG['setup'][301]." ".$latest_version."</div>";
                  echo "<div class='center'>".$LANG['setup'][302]."</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($LANG['setup'][301]." ".$latest_version);
               } else {
                  return $LANG['setup'][301]." ".$latest_version;
               }
            }

         } else {
            if (!$auto) {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($LANG['setup'][303]);
               } else {
                  echo "<div class='center'>".$LANG['setup'][303]."</div>";
               }

            } else {
               if ($messageafterredirect) {
                  Session::addMessageAfterRedirect($LANG['setup'][303]);
               } else {
                  return $LANG['setup'][303];
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
    * @param $itemtype string: item type
    * @param $full path or relative one
    *
    * return string itemtype Form URL
   **/
   static function getItemTypeFormURL($itemtype, $full=true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if ($plug=isPluginItemType($itemtype)) {
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
    * @param $itemtype string: item type
    * @param $full path or relative one
    *
    * return string itemtype search URL
   **/
   static function getItemTypeSearchURL($itemtype, $full=true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if ($plug=isPluginItemType($itemtype)) {
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
    * @param $itemtype string: item type
    * @param $full path or relative one
    *
    * return string itemtype tabs URL
   **/
   static function getItemTypeTabsURL($itemtype, $full=true) {
      global $CFG_GLPI;

      /// To keep for plugins
      /// TODO drop also for plugins.

      $filename = "/ajax/common.tabs.php";
      if ($plug=isPluginItemType($itemtype)) {
         $dir      = "/plugins/".strtolower($plug['plugin']);
         $item     = strtolower($plug['class']);
         $tempname = $dir."/ajax/$item.tabs.php";
         if (file_exists(GLPI_ROOT.$tempname)) {
            $filename = $tempname;
         }
      }

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

      for ($a=0 ; $a<=$length ; $a++) {
         $b = rand(0, strlen($alphabet) - 1);
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

      $time = round(abs($time));
      $out['second'] = 0;
      $out['minute'] = 0;
      $out['hour']   = 0;
      $out['day']    = 0;

      $out['second'] = $time%MINUTE_TIMESTAMP;
      $time -= $out['second'];

      if ($time>0) {
         $out['minute'] = ($time%HOUR_TIMESTAMP)/MINUTE_TIMESTAMP;
         $time -= $out['minute']*MINUTE_TIMESTAMP;

         if ($time>0) {
            $out['hour'] = ($time%DAY_TIMESTAMP)/HOUR_TIMESTAMP;
            $time -= $out['hour']*HOUR_TIMESTAMP;

            if ($time>0) {
               $out['day'] = $time/DAY_TIMESTAMP;
            }
         }
      }
      return $out;
   }


   /**
    * Get a web page. Use proxy if configured
    *
    * @param $url string: to retrieve
    * @param $msgerr string: set if problem encountered
    * @param $rec integer: internal use only Must be 0
    *
    * @return content of the page (or empty)
   **/
   static function getURLContent ($url, &$msgerr=NULL, $rec=0) {
      global $LANG, $CFG_GLPI;

      $content = "";
      $taburl  = parse_url($url);

      // Connection directe
      if (empty($CFG_GLPI["proxy_name"])) {
         if ($fp=@fsockopen($taburl["host"], (isset($taburl["port"]) ? $taburl["port"] : 80),
                            $errno, $errstr, 1)) {

            if (isset($taburl["path"]) && $taburl["path"]!='/') {
               // retrieve path + args
               $request = "GET ".strstr($url, $taburl["path"])." HTTP/1.1\r\n";
            } else {
               $request = "GET / HTTP/1.1\r\n";
            }

            $request .= "Host: ".$taburl["host"]."\r\n";

         } else {
            if (isset($msgerr)) {
               $msgerr = $LANG['setup'][304] . " ($errstr)"; // failed direct connexion - try proxy
            }
            return "";
         }

      } else { // Connection using proxy
         $fp = fsockopen($CFG_GLPI["proxy_name"], $CFG_GLPI["proxy_port"], $errno, $errstr, 1);

         if ($fp) {
            $request  = "GET $url HTTP/1.1\r\n";
            $request .= "Host: ".$taburl["host"]."\r\n";
            if (!empty($CFG_GLPI["proxy_user"])) {
               $request .= "Proxy-Authorization: Basic " . base64_encode ($CFG_GLPI["proxy_user"].":".
                           self::decrypt($CFG_GLPI["proxy_passwd"], GLPIKEY)) . "\r\n";
            }

         } else {
            if (isset($msgerr)) {
               $msgerr = $LANG['setup'][311] . " ($errstr)"; // failed proxy connexion
            }
            return "";
         }
      }

      $request .= "User-Agent: GLPI/".trim($CFG_GLPI["version"])."\r\n";
      $request .= "Connection: Close\r\n\r\n";
      fwrite($fp, $request);

      $header = true ;
      $redir  = false;
      $errstr = "";
      while (!feof($fp)) {
         if ($buf=fgets($fp, 1024)) {
            if ($header) {

               if (strlen(trim($buf))==0) {
                  // Empty line = end of header
                  $header = false;

               } else if ($redir && preg_match("/^Location: (.*)$/", $buf, $rep)) {
                  if ($rec<9) {
                     $desturl = trim($rep[1]);
                     $taburl2 = parse_url($desturl);

                     if (isset($taburl2['host'])) {
                        // Redirect to another host
                        return (self::getURLContent($desturl, $errstr, $rec+1));
                     }

                     // redirect to same host
                     return (self::getURLContent((isset($taburl['scheme'])?$taburl['scheme']:'http').
                                                 "://".$taburl['host'].
                                                 (isset($taburl['port'])?':'.$taburl['port']:'').
                                                  $desturl, $errstr, $rec+1));
                  }

                  $errstr = "Too deep";
                  break;

               } else if (preg_match("/^HTTP.*200.*OK/", $buf)) {
                  // HTTP 200 = OK

               } else if (preg_match("/^HTTP.*302/", $buf)) {
                  // HTTP 302 = Moved Temporarily
                  $redir = true;

               } else if (preg_match("/^HTTP/", $buf)) {
                  // Other HTTP status = error
                  $errstr = trim($buf);
                  break;
               }

            } else {
               // Body
               $content .= $buf;
            }
         }
      } // eof

      fclose($fp);

      if (empty($content) && isset($msgerr)) {
         if (empty($errstr)) {
            $msgerr = $LANG['setup'][312]; // no data
         } else {
            $msgerr = $LANG['setup'][310] . " ($errstr)"; // HTTP error
         }
      }
      return $content;
   }

   /**
    *
   **/
   static function key_exists_deep($need, $tab) {

      foreach ($tab as $key => $value) {

         if ($need == $key) {
            return true;
         }

         if (is_array($value) && self::key_exists_deep($need, $value)) {
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
         if (isset($data['begin']) && isset($data['_duration'])) {
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
         $data = explode("_",$where);

         if (count($data)>=2
             && isset($_SESSION["glpiactiveprofile"]["interface"])
             && !empty($_SESSION["glpiactiveprofile"]["interface"])) {

            $forcetab = '';
            if (isset($data[2])) {
               $forcetab = 'forcetab='.$data[2];
            }
            // Plugin tab
            if (isset($data[3])) {
               $forcetab .= '_'.$data[3];
            }

            switch ($_SESSION["glpiactiveprofile"]["interface"]) {
               case "helpdesk" :
                  switch ($data[0]) {
                     case "plugin" :
                        $plugin = $data[1];
                        $valid  = false;
                        if (isset($PLUGIN_HOOKS['redirect_page'][$plugin])
                            && !empty($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                           // Simple redirect
                           if (!is_array($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                              if (isset($data[2]) && $data[2]>0) {
                                 $valid = true;
                                 $id    = $data[2];
                                 $page  = $PLUGIN_HOOKS['redirect_page'][$plugin];
                              }
                              $forcetabnum = 3 ;
                           } else { // Complex redirect
                              if (isset($data[2])
                                  && !empty($data[2])
                                  && isset($data[3])
                                  && $data[3] > 0
                                  && isset($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])
                                  && !empty($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])) {
                                 $valid = true;
                                 $id    = $data[3];
                                 $page  = $PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]];
                              }
                              $forcetabnum = 4 ;
                           }
                        }

                        if (isset($data[$forcetabnum])) {
                           $forcetab = 'forcetab='.$data[$forcetabnum];
                        }

                        if ($valid) {
                           Html::redirect($CFG_GLPI["root_doc"]."/plugins/$plugin/$page?id=$id&$forcetab");
                        } else {
                           Html::redirect($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?$forcetab");
                        }
                        break;

                     // Use for compatibility with old name
                     case "tracking" :
                     case "ticket" :
                        // Check entity
                        if (($item = getItemForItemtype($data[0]))
                              && $item->isEntityAssign()) {
                           if ($item->getFromDB($data[1])) {
                              if (!Session::haveAccessToEntity($item->getEntityID())) {
                                 Session::changeActiveEntities($item->getEntityID(),1);
                              }
                           }
                        }

                        Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$data[1].
                                     "&$forcetab");
                        break;

                     case "preference" :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/preference.php?$forcetab");
                        break;

                     default :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?$forcetab");
                        break;
                  }
                  break;

               case "central" :
                  switch ($data[0]) {
                     case "plugin" :
                        $plugin = $data[1];
                        $valid  = false;
                        if (isset($PLUGIN_HOOKS['redirect_page'][$plugin])
                            && !empty($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                           // Simple redirect
                           if (!is_array($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                              if (isset($data[2]) && $data[2]>0) {
                                 $valid = true;
                                 $id    = $data[2];
                                 $page  = $PLUGIN_HOOKS['redirect_page'][$plugin];
                              }
                              $forcetabnum = 3 ;
                           } else { // Complex redirect
                              if (isset($data[2])
                                  && !empty($data[2])
                                  && isset($data[3])
                                  && $data[3] > 0
                                  && isset($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])
                                  && !empty($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])) {
                                 $valid = true;
                                 $id    = $data[3];
                                 $page  = $PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]];
                              }
                              $forcetabnum = 4 ;
                           }
                        }

                        if (isset($data[$forcetabnum])) {
                           $forcetab = 'forcetab='.$data[$forcetabnum];
                        }

                        if ($valid) {
                           Html::redirect($CFG_GLPI["root_doc"]."/plugins/$plugin/$page?id=$id&$forcetab");
                        } else {
                           Html::redirect($CFG_GLPI["root_doc"]."/front/central.php?$forcetab");
                        }
                        break;

                     case "preference" :
                        Html::redirect($CFG_GLPI["root_doc"]."/front/preference.php?$forcetab");
                        break;

                     // Use for compatibility with old name
                     // no break
                     case "tracking" :
                        $data[0] = "ticket";

                     default :
                        if (!empty($data[0] )&& $data[1]>0) {
                           // Check entity
                           if (($item = getItemForItemtype($data[0]))
                                 && $item->isEntityAssign()) {
                              if ($item->getFromDB($data[1])) {
                                 if (!Session::haveAccessToEntity($item->getEntityID())) {
                                    Session::changeActiveEntities($item->getEntityID(),1);
                                 }
                              }
                           }
                           Html::redirect($CFG_GLPI["root_doc"]."/front/".$data[0].".form.php?id=".
                                        $data[1]."&$forcetab");
                        } else {
                           Html::redirect($CFG_GLPI["root_doc"]."/front/central.php?$forcetab");
                        }
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
      $last = self::strtolower($val{strlen($val)-1});

      switch($last) {
         // Le modifieur 'G' est disponible depuis PHP 5.1.0
         case 'g' :
            $val *= 1024;

         case 'm' :
            $val *= 1024;

         case 'k' :
            $val *= 1024;
      }

      return $val;
   }


   static function showMailServerConfig($value) {
      global $LANG;

      if (!Session::haveRight("config", "w")) {
         return false;
      }
      if (strstr($value,":")) {
         $addr = str_replace("{", "", preg_replace("/:.*/", "", $value));
         $port = preg_replace("/.*:/", "", preg_replace("/\/.*/", "", $value));
      } else {
         if (strstr($value,"/")) {
            $addr = str_replace("{", "", preg_replace("/\/.*/", "", $value));
         } else {
            $addr = str_replace("{", "", preg_replace("/}.*/", "", $value));
         }
         $port = "";
      }
      $mailbox = preg_replace("/.*}/", "", $value);

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][52] . "&nbsp;:</td>";
      echo "<td><input size='30' type='text' name='mail_server' value=\"" .$addr. "\"></td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][168] . "&nbsp;:</td><td>";
      echo "<select name='server_type'>";
      echo "<option value=''>&nbsp;</option>\n";
      echo "<option value='/imap' ".(strstr($value,"/imap") ?" selected ":"") . ">IMAP</option>\n";
      echo "<option value='/pop' ".(strstr($value,"/pop") ? " selected " : "") . ">POP</option>\n";
      echo "</select>&nbsp;";

      echo "<select name='server_ssl'>";
      echo "<option value=''>&nbsp;</option>\n";
      echo "<option value='/ssl' " .(strstr($value,"/ssl") ? " selected " : "") . ">SSL</option>\n";
      echo "</select>&nbsp;";

      echo "<select name='server_tls'>";
      echo "<option value=''>&nbsp;</option>\n";
      echo "<option value='/tls' ".(strstr($value,"/tls") ? " selected " : "") . ">TLS</option>\n";
      echo "<option value='/notls' ".(strstr($value,"/notls")?" selected ":"").">NO-TLS</option>\n";
      echo "</select>&nbsp;";

      echo "<select name='server_cert'>";
      echo "<option value=''>&nbsp;</option>\n";
      echo "<option value='/novalidate-cert' ".(strstr($value,"/novalidate-cert")?" selected ":"").
             ">NO-VALIDATE-CERT</option>\n";
      echo "<option value='/validate-cert' " .(strstr($value,"/validate-cert")?" selected ":"") .
             ">VALIDATE-CERT</option>\n";
      echo "</select>\n";

      echo "<input type=hidden name=imap_string value='".$value."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][169] . "&nbsp;:</td>";
      echo "<td><input size='30' type='text' name='server_mailbox' value=\"" . $mailbox . "\" >";
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][171] . "&nbsp;:</td>";
      echo "<td><input size='10' type='text' name='server_port' value='$port'></td></tr>\n";
      if (empty($value)) {
         $value = "&nbsp;";
      }
      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][170] . "&nbsp;:</td>";
      echo "<td class='b'>$value</td></tr>\n";
   }


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
      if (isset($input['server_type'])) {
         $out .= $input['server_type'];
      }
      if (isset($input['server_ssl'])) {
         $out .= $input['server_ssl'];
      }
      if (isset($input['server_cert'])
          && (!empty($input['server_ssl']) || !empty($input['server_tls']))) {
         $out .= $input['server_cert'];
      }
      if (isset($input['server_tls'])) {
         $out .= $input['server_tls'];
      }
      $out .= "}";
      if (isset($input['server_mailbox'])) {
         $out .= $input['server_mailbox'];
      }

      return $out;
   }

   /**
    * Clean integer value (strip all chars not - and spaces )
    *
    * @param $integer string: integer string
    * @since versin 0.83.5
    * @return clean integer
   **/
   static function cleanInteger($integer) {
      return preg_replace("/[^0-9-]/", "", $integer);
   }
}
?>