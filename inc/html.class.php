<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}

/**
 * Html Class
 * Inpired from Html/FormHelper for several functions
**/
class Html {


   /**
    * Clean display value deleting html tags
    *
    * @param $value string: string value
    *
    * @return clean value
   **/
   static function clean($value) {

      $specialfilter = array('@<div[^>]*?tooltip_picture[^>]*?>.*?</div[^>]*?>@si'); // Strip ToolTips
      $value         = preg_replace($specialfilter, '', $value);
      $specialfilter = array('@<div[^>]*?tooltip_text[^>]*?>.*?</div[^>]*?>@si'); // Strip ToolTips
      $value         = preg_replace($specialfilter, '', $value);
      $specialfilter = array('@<div[^>]*?tooltip_picture_border[^>]*?>.*?</div[^>]*?>@si'); // Strip ToolTips
      $value         = preg_replace($specialfilter, '', $value);
      $specialfilter = array('@<div[^>]*?invisible[^>]*?>.*?</div[^>]*?>@si'); // Strip ToolTips
      $value         = preg_replace($specialfilter, '', $value);

      $value = preg_replace("/<(p|br|div)( [^>]*)?".">/i", "\n", $value);
      $value = preg_replace("/(&nbsp;| )+/", " ", $value);


      $search        = array('@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
                             '@<style[^>]*?>.*?</style[^>]*?>@si', // Strip out style
                             '@<!DOCTYPE[^>]*?>@si', // Strip out !DOCTYPE
                              );

      $value = preg_replace($search, '', $value);

      include_once(GLPI_HTMLAWED);

      $value = htmLawed($value, array('elements' => 'none',
                                      'keep_bad' => 2, // remove tag / neutralize content
                                      'comment' => 1, // DROP
                                      'cdata'   => 1, // DROP
                                      ));

/*
      $specialfilter = array('@<span[^>]*?x-hidden[^>]*?>.*?</span[^>]*?>@si'); // Strip ToolTips
      $value         = preg_replace($specialfilter, ' ', $value);

      $search        = array('@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
                             '@<style[^>]*?>.*?</style[^>]*?>@si',   // Strip style tags properly
                             '@<[\/\!]*?[^<>]*?>@si',                // Strip out HTML tags
                             '@<![\s\S]*?--[ \t\n\r]*>@');           // Strip multi-line comments including CDATA

      $value = preg_replace($search, ' ', $value);

      // nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
      $value = str_replace("&#8217;", "'", $value);
*/
   // Problem with this regex : may crash
   //   $value = preg_replace("/ +/u", " ", $value);
      // Revert back htmlawed &amp; -> &
      $value = str_replace("&amp;", "&", $value);
      $value = str_replace(array("\r\n", "\r"), "\n", $value);
      $value = preg_replace("/(\n[ ]*){2,}/", "\n\n", $value, -1);

      return trim($value);
   }


   /**
    * Recursivly execute html_entity_decode on an Array
    *
    * @param $value string or array
    *
    * @return array of value (same struct as input)
   **/
   static function entity_decode_deep($value) {

      return (is_array($value) ? array_map(array(__CLASS__, 'entity_decode_deep'), $value)
                               : html_entity_decode($value, ENT_QUOTES, "UTF-8"));
   }


   /**
    * Recursivly execute htmlentities on an Array
    *
    * @param $value string or array
    *
    * @return array of value (same struct as input)
   **/
   static function entities_deep($value) {

      return (is_array($value) ? array_map(array(__CLASS__, 'entities_deep'), $value)
                               : htmlentities($value,ENT_QUOTES, "UTF-8"));
   }


   /**
    * Convert a date YY-MM-DD to DD-MM-YY for calendar
    *
    * @param $time date: date to convert
    *
    * @return $time or $date
   **/
   static function convDate($time) {

      if (is_null($time) || ($time == 'NULL')) {
         return NULL;
      }

      if (!isset($_SESSION["glpidate_format"])) {
         $_SESSION["glpidate_format"] = 0;
      }

      switch ($_SESSION['glpidate_format']) {
         case 1 : // DD-MM-YYYY
            $date  = substr($time, 8, 2)."-";  // day
            $date .= substr($time, 5, 2)."-"; // month
            $date .= substr($time, 0, 4);     // year
            return $date;

         case 2 : // MM-DD-YYYY
            $date  = substr($time, 5, 2)."-";  // month
            $date .= substr($time, 8, 2)."-"; // day
            $date .= substr($time, 0, 4);     // year
            return $date;

         default : // YYYY-MM-DD
            if (strlen($time)>10) {
               return substr($time, 0, 10);
            }
            return $time;
      }
   }


   /**
    * Convert a date YY-MM-DD HH:MM to DD-MM-YY HH:MM for display in a html table
    *
    * @param $time datetime: datetime to convert
    *
    * @return $time or $date
   **/
   static function convDateTime($time) {

      if (is_null($time) || ($time == 'NULL')) {
         return NULL;
      }

      return self::convDate($time).' '. substr($time, 11, 5);
   }


   /**
    * Clean string for input text field
    *
    * @param $string string: input text
    *
    * @return clean string
   **/
   static function cleanInputText($string) {
      return preg_replace('/\"/', '&quot;', $string);
   }


   /**
    * Clean all parameters of an URL. Get a clean URL
    *
    * @param $url string URL
    *
    * @return clean URL
   **/
   static function cleanParametersURL($url) {

      $url = preg_replace("/(\/[0-9a-zA-Z\.\-\_]+\.php).*/", "$1", $url);
      return preg_replace("/\?.*/", "", $url);
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


   /**
    *  Resume text for followup
    *
    * @param $string   string   string to resume
    * @param $length   integer  resume length (default 255)
    *
    * @return cut string
   **/
   static function resume_text($string, $length=255) {

      if (Toolbox::strlen($string) > $length) {
         $string = Toolbox::substr($string, 0, $length)."&nbsp;(...)";
      }

      return $string;
   }


   /**
    *  Resume a name for display
    *
    * @param $string   string   string to resume
    * @param $length   integer  resume length (default 255)
    *
    * @return cut string
    **/
   static function resume_name($string, $length=255) {

      if (strlen($string) > $length) {
         $string = Toolbox::substr($string, 0, $length)."...";
      }

      return $string;
   }


   /**
    * Clean post value for display in textarea
    *
    * @param $value string: string value
    *
    * @return clean value
   **/
   static function cleanPostForTextArea($value) {

      if (is_array($value)) {
         return array_map(array(__CLASS__, __METHOD__), $value);
      }
      $order   = array('\r\n',
                       '\n',
                       "\\'",
                       '\"',
                       '\\\\');
      $replace = array("\n",
                       "\n",
                       "'",
                       '"',
                       "\\");
      return str_replace($order, $replace, $value);
   }


   /**
    * Convert a number to correct display
    *
    * @param $number       float    Number to display
    * @param $edit         boolean  display number for edition ? (id edit use . in all case)
    *                               (false by default)
    * @param $forcedecimal integer  Force decimal number (do not use default value) (default -1)
    *
    * @return formatted number
   **/
   static function formatNumber($number, $edit=false, $forcedecimal=-1) {
      global $CFG_GLPI;

      // Php 5.3 : number_format() expects parameter 1 to be double,
      if ($number == "") {
         $number = 0;

      } else if ($number == "-") { // used for not defines value (from Infocom::Amort, p.e.)
         return "-";
      }

      $number  = doubleval($number);
      $decimal = $CFG_GLPI["decimal_number"];
      if ($forcedecimal>=0) {
         $decimal = $forcedecimal;
      }

      // Edit : clean display for mysql
      if ($edit) {
         return number_format($number, $decimal, '.', '');
      }

      // Display : clean display
      switch ($_SESSION['glpinumber_format']) {
         case 0 : // French
            return str_replace(' ', '&nbsp;', number_format($number, $decimal, '.', ' '));

         case 2 : // Other French
            return str_replace(' ', '&nbsp;', number_format($number, $decimal, ',', ' '));

         case 3 : // No space with dot
            return number_format($number, $decimal, '.', '');

         case 4 : // No space with comma
            return number_format($number, $decimal, ',', '');

         default: // English
            return number_format($number, $decimal, '.', ',');
      }
   }


   /**
    * Make a good string from the unix timestamp $sec
    *
    * @param $time         integer  timestamp
    * @param $display_sec  boolean  display seconds ? (true by default)
    * @param $use_days     boolean  use days for display ? (true by default)
    *
    * @return string
   **/
   static function timestampToString($time, $display_sec=true, $use_days=true) {

      $sign = '';
      if ($time < 0) {
         $sign = '- ';
         $time = abs($time);
      }
      $time = floor($time);

      // Force display seconds if time is null
      if ($time < MINUTE_TIMESTAMP) {
         $display_sec = true;
      }

      $units = Toolbox::getTimestampTimeUnits($time);
      if ($use_days) {
         if ($units['day'] > 0) {
            if ($display_sec) {
               //TRANS: %1$s is the sign (-or empty), %2$d number of days, %3$d number of hours,
               //       %4$d number of minutes, %5$d number of seconds
               return sprintf(__('%1$s%2$d days %3$d hours %4$d minutes %5$d seconds'), $sign,
                              $units['day'], $units['hour'], $units['minute'], $units['second']);
            }
            //TRANS:  %1$s is the sign (-or empty), %2$d number of days, %3$d number of hours,
            //        %4$d number of minutes
            return sprintf(__('%1$s%2$d days %3$d hours %4$d minutes'),
                           $sign, $units['day'], $units['hour'], $units['minute']);
         }
      } else {
         if ($units['day'] > 0) {
            $units['hour'] += 24*$units['day'];
         }
      }

      if ($units['hour'] > 0) {
         if ($display_sec) {
            //TRANS:  %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes,
            //        %4$d number of seconds
            return sprintf(__('%1$s%2$d hours %3$d minutes %4$d seconds'),
                           $sign, $units['hour'], $units['minute'], $units['second']);
         }
         //TRANS: %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes
         return sprintf(__('%1$s%2$d hours %3$d minutes'), $sign, $units['hour'], $units['minute']);
      }

      if ($units['minute'] > 0) {
         if ($display_sec) {
            //TRANS:  %1$s is the sign (-or empty), %2$d number of minutes,  %3$d number of seconds
            return sprintf(__('%1$s%2$d minutes %3$d seconds'), $sign, $units['minute'],
                           $units['second']);
         }
         //TRANS: %1$s is the sign (-or empty), %2$d number of minutes
         return sprintf(_n('%1$s%2$d minute', '%1$s%2$d minutes', $units['minute']), $sign,
                        $units['minute']);

      }

      if ($display_sec) {
         //TRANS:  %1$s is the sign (-or empty), %2$d number of seconds
         return sprintf(_n('%1$s%2$s second', '%1$s%2$s seconds', $units['second']), $sign,
                        $units['second']);
      }
      return '';
   }


   /**
    * Extract url from web link
    *
    * @param $value string value
    *
    * @return clean value
   **/
   static function weblink_extract($value) {

      $value = preg_replace('/<a\s+href\="([^"]+)"[^>]*>[^<]*<\/a>/i', "$1", $value);
      return $value;
   }


   /**
    * Redirection to $_SERVER['HTTP_REFERER'] page
    *
    * @return nothing
   **/
   static function back() {
      self::redirect($_SERVER['HTTP_REFERER']);
   }


   /**
    * Redirection hack
    *
    * @param $dest string: Redirection destination
    *
    * @return nothing
   **/
   static function redirect($dest) {

      $toadd = '';
      if (!strpos($dest,"?")) {
         $toadd = '?tokonq='.Toolbox::getRandomString(5);
      }

      echo "<script language=javascript>
            NomNav = navigator.appName;
            if (NomNav=='Konqueror') {
               window.location='".$dest.$toadd."';
            } else {
               window.location='".$dest."';
            }
         </script>";
      exit();
   }

   /**
    * Redirection to Login page
    *
    * @param $params       param to add to URL (default '')
    * @since version 0.85
    *
    * @return nothing
   **/
   static function redirectToLogin($params='') {
      global $CFG_GLPI;

      $dest     = $CFG_GLPI["root_doc"] . "/index.php";
      $url_dest = str_replace($CFG_GLPI["root_doc"],'',$_SERVER['REQUEST_URI']);
      $dest    .= "?redirect=".rawurlencode($url_dest);

      if (!empty($params)) {
         $dest .= '&'.$params;
      }
      $toadd = '';
      if (!strpos($dest,"?")) {
         $toadd = '&tokonq='.Toolbox::getRandomString(5);
      }

      echo "<script language=javascript>
            NomNav = navigator.appName;
            if (NomNav=='Konqueror') {
               window.location='".$dest.$toadd."';
            } else {
               window.location='".$dest."';
            }
         </script>";
      exit();
   }


   /**
    * Display common message for item not found
    *
    * @return Nothing
   **/
   static function displayNotFoundError() {
      global $CFG_GLPI, $HEADER_LOADED;

      if (!$HEADER_LOADED) {
         if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
            self::nullHeader(__('Access denied'));

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
            self::header(__('Access denied'));

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            self::helpHeader(__('Access denied'));
         }
      }
      echo "<div class='center'><br><br>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='".__s('Warning')."'>";
      echo "<br><br><span class='b'>" . __('Item not found') . "</span></div>";
      self::nullFooter();
      exit ();
   }


   /**
    * Display common message for privileges errors
    *
    * @return Nothing (die)
   **/
   static function displayRightError() {
      self::displayErrorAndDie(__("You don't have permission to perform this action."));
   }


   /**
    * Display a div containing a message set in session in the previous page
   **/
   static function displayMessageAfterRedirect() {

      // Affichage du message apres redirection
      if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
          && !empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) {

         echo "<div id='message_after_redirect' title='".__('Information')."'>";
         echo $_SESSION["MESSAGE_AFTER_REDIRECT"];
         echo "</div>";

         echo Html::scriptBlock("
            $(document).ready(function() {
               $('#message_after_redirect').dialog({
                  dialogClass: 'message_after_redirect',
                  minHeight: 40,
                  minWidth: 200,
                  position: {
                     my: 'right bottom',
                     at: 'right-20 bottom-20',
                     of: window,
                     collision: 'none'
                  },
                  autoOpen: false,
                  show: {
                    effect: 'slide',
                    direction: 'down',
                    'duration': 800
                  }
               })
               .dialog('open');

               // close dialog on outside click 
               $(document.body).on('click', function(e){
                  if ($('#message_after_redirect').dialog('isOpen')
                      && !$(e.target).is('.ui-dialog, a')
                      && !$(e.target).closest('.ui-dialog').length) {
                     $('#message_after_redirect').dialog('close');
                  }
               });
            });
         ");
      }

      // Clean message
      $_SESSION["MESSAGE_AFTER_REDIRECT"] = "";
   }


   /**
    * Common Title Function
    *
    * @param $ref_pic_link    Path to the image to display (default '')
    * @param $ref_pic_text    Alt text of the icon (default '')
    * @param $ref_title       Title to display (default '')
    * @param $ref_btts        Extra items to display array(link=>text...) (default '')
    *
    * @return nothing
   **/
   static function displayTitle($ref_pic_link="", $ref_pic_text="", $ref_title="", $ref_btts="") {

      $ref_pic_text = htmlentities($ref_pic_text, ENT_QUOTES, 'UTF-8');

      echo "<div class='center'><table class='tab_glpi'><tr>";
      if ($ref_pic_link!="") {
         $ref_pic_text = self::clean($ref_pic_text);
         echo "<td>".Html::image($ref_pic_link, array('alt' => $ref_pic_text))."</td>";
      }

      if ($ref_title != "") {
         echo "<td><span class='vsubmit'>&nbsp;".$ref_title."&nbsp;</span></td>";
      }

      if (is_array($ref_btts) && count($ref_btts)) {
         foreach ($ref_btts as $key => $val) {
            echo "<td><a class='vsubmit' href='".$key."'>".$val."</a></td>";
         }
      }
      echo "</tr></table></div>";
   }


   /**
   * Clean Display of Request
   *
   * @since version 0.83.1
   *
   * @param $request SQL request
   **/
   static function cleanSQLDisplay($request) {

      $request = str_replace("<","&lt;",$request);
      $request = str_replace(">","&gt;",$request);
      $request = str_ireplace("UNION","<br>UNION<br>",$request);
      $request = str_ireplace("FROM","<br>FROM",$request);
      $request = str_ireplace("WHERE","<br>WHERE",$request);
      $request = str_ireplace("INNER JOIN","<br>INNER JOIN",$request);
      $request = str_ireplace("LEFT JOIN","<br>LEFT JOIN",$request);
      $request = str_ireplace("ORDER BY","<br>ORDER BY",$request);
      $request = str_ireplace("SORT","<br>SORT",$request);

      return $request;
   }

   /**
    * Display Debug Information
    *
    * @param $with_session with session information (true by default)
   **/
   static function displayDebugInfos($with_session=true) {
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST, $SQL_TOTAL_TIMER, $DEBUG_AUTOLOAD;

      // Only for debug mode so not need to be translated
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
         $rand = mt_rand();
         echo "<div class='debug'>";
         echo "<h1 id='see_debug$rand' class='see_debug'><a name='see_debug'>See GLPI DEBUG</a></h1>";

         echo "<div id='debugtabs$rand'><ul>";
         if ($CFG_GLPI["debug_sql"]) {
            echo "<li><a href='#debugsql$rand'>SQL REQUEST</a></li>";
         }
         if ($CFG_GLPI["debug_vars"]) {
            echo "<li><a href='#debugautoload$rand'>AUTOLOAD</a></li>";
            echo "<li><a href='#debugpost$rand'>POST VARIABLE</a></li>";
            echo "<li><a href='#debugget$rand'>GET VARIABLE</a></li>";
            if ($with_session) {
               echo "<li><a href='#debugsession$rand'>SESSION VARIABLE</a></li>";
            }
            echo "<li><a href='#debugserver$rand'>SERVER VARIABLE</a></li>";
         }
         echo "</ul>";


         if ($CFG_GLPI["debug_sql"]) {
            echo "<div id='debugsql$rand'>";
            echo "<div class='b'>".$SQL_TOTAL_REQUEST." Queries ";
            echo "took  ".array_sum($DEBUG_SQL['times'])."s</div>";

            echo "<table class='tab_cadre'><tr><th>N&#176; </th><th>Queries</th><th>Time</th>";
            echo "<th>Errors</th></tr>";

            foreach ($DEBUG_SQL['queries'] as $num => $query) {
               echo "<tr class='tab_bg_".(($num%2)+1)."'><td>$num</td><td>";
               echo self::cleanSQLDisplay($query);
               echo "</td><td>";
               echo $DEBUG_SQL['times'][$num];
               echo "</td><td>";
               if (isset($DEBUG_SQL['errors'][$num])) {
                  echo $DEBUG_SQL['errors'][$num];
               } else {
                  echo "&nbsp;";
               }
               echo "</td></tr>";
            }
            echo "</table>";
            echo "</div>";
         }
         if ($CFG_GLPI["debug_vars"]) {
            echo "<div id='debugautoload$rand'>".implode(', ', $DEBUG_AUTOLOAD)."</div>";
            echo "<div id='debugpost$rand'>";
            self::printCleanArray($_POST, 0, true);
            echo "</div>";
            echo "<div id='debugget$rand'>";
            self::printCleanArray($_GET, 0, true);
            echo "</div>";
            if ($with_session) {
               echo "<div id='debugsession$rand'>";
               self::printCleanArray($_SESSION, 0, true);
               echo "</div>";
            }
            echo "<div id='debugserver$rand'>";
            self::printCleanArray($_SERVER, 0, true);
            echo "</div>";

         }

         echo Html::scriptBlock("
            $('#debugtabs$rand').tabs({
               collapsible: true
            }).addClass( 'ui-tabs-vertical ui-helper-clearfix' );

            $('<li class=\"close\"><button id= \"close_debug$rand\">close debug</button></li>')
               .appendTo('#debugtabs$rand ul');

            $('#close_debug$rand').button({
               icons: {
                  primary: 'ui-icon-close'
               },
               text: false
            }).click(function() {
                $('#debugtabs$rand').css('display', 'none');
            });

            $('#see_debug$rand').click(function() {
               console.log('see_debug #debugtabs$rand');
               $('#debugtabs$rand').css('display', 'block');
            });
         ");

         echo "</div></div>";
      }
   }


   /**
    * Display a Link to the last page using http_referer if available else use history.back
   **/
   static function displayBackLink() {

      if (isset($_SERVER['HTTP_REFERER'])) {
         echo "<a href='".$_SERVER['HTTP_REFERER']."'>".__('Back')."</a>";
      } else {
         echo "<a href='javascript:history.back();'>".__('Back')."</a>";
      }
   }


   /**
    * Simple Error message page
    *
    * @param $message   string   displayed before dying
    * @param $minimal            set to true do not display app menu (false by default)
    *
    * @return nothing as function kill script
   **/
   static function displayErrorAndDie ($message, $minimal=false) {
      global $CFG_GLPI, $HEADER_LOADED;

      if (!$HEADER_LOADED) {
         if ($minimal || !isset($_SESSION["glpiactiveprofile"]["interface"])) {
            self::nullHeader(__('Access denied'), '');

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
            self::header(__('Access denied'), '');

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            self::helpHeader(__('Access denied'), '');
         }
      }
      echo "<div class='center'><br><br>";
      echo Html::image($CFG_GLPI["root_doc"] . "/pics/warning.png", array('alt' => __('Warning')));
      echo "<br><br><span class='b'>$message</span></div>";
      self::nullFooter();
      exit ();
   }


   /**
    * Add confirmation on button or link before action
    *
    * @param $string             string   to display or array of string for using multilines
    * @param $additionalactions  string   additional actions to do on success confirmation
    *                                     (default '')
    *
    * @return nothing
   **/
   static function addConfirmationOnAction($string, $additionalactions='') {

      return "onclick=\"".Html::getConfirmationOnActionScript($string, $additionalactions)."\"";
   }


   /**
    * Get confirmation on button or link before action
    *
    * @since version 0.85
    *
    * @param $string             string   to display or array of string for using multilines
    * @param $additionalactions  string   additional actions to do on success confirmation
    *                                     (default '')
    *
    * @return confirmation script
   **/
   static function getConfirmationOnActionScript($string, $additionalactions='') {

      if (!is_array($string)) {
         $string = array($string);
      }
      $string            = Toolbox::addslashes_deep($string);
      $additionalactions = trim($additionalactions);
      $out               = "";
      $multiple          = false;
      $close_string      = '';
      // Manage multiple confirmation
      foreach ($string as $tab) {
         if (is_array($tab)) {
            $multiple      = true;
            $out          .="if (window.confirm('";
            $out          .= implode('\n',$tab);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
         }
      }
      // manage simple confirmation
      if (!$multiple) {
            $out          .="if (window.confirm('";
            $out          .= implode('\n',$string);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
      }
      $out .= $additionalactions.(substr($additionalactions, -1)!=';'?';':'').$close_string;
      return $out;
   }


   /**
    * Manage progresse bars
    *
    * @since version 0.85
    *
    * @param $id                 HTML ID of the progress bar
    * @param $options    array   progress status
    *                    - create    do we have to create it ?
    *                    - message   add or change the message
    *                    - percent   current level
    *
    *
    * @return nothing (display)
    **/
   static function progressBar($id, array $options=array()) {

      $params            = array();
      $params['create']  = false;
      $params['message'] = NULL;
      $params['percent'] = -1;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if ($params['create']) {
         echo "<div class='doaction_cadre'>";
         echo "<div class='doaction_progress' id='$id'>";
         echo "<div class='doaction_progress_text' id='".$id."_text' >&nbsp;</div>";
         echo "</div>";
         echo "</div><br>";
         echo Html::scriptBlock(self::jsGetElementbyID($id).".progressbar();");
      }

      if ($params['message'] !== NULL) {
         echo Html::scriptBlock(self::jsGetElementbyID($id.'_text').".text(\"".
                                addslashes($params['message'])."\");");
      }

      if (($params['percent'] >= 0)
          && ($params['percent'] <= 100)) {
         echo Html::scriptBlock(self::jsGetElementbyID($id).".progressbar('option', 'value', ".
                                $params['percent']." );");
      }

      if (!$params['create']) {
         Html::glpi_flush();
      }
   }


   /**
    * Create a Dynamic Progress Bar
    *
    * @param $msg initial message (under the bar) (default '&nbsp;')
    *
    * @return nothing
    **/
   static function createProgressBar($msg="&nbsp;") {

      $options = array('create' => true);
      if ($msg != "&nbsp;") {
         $options['message'] = $msg;
      }

      self::progressBar('doaction_progress', $options);
   }

   /**
    * Change the Message under the Progress Bar
    *
    * @param $msg message under the bar (default '&nbsp;')
    *
    * @return nothing
   **/
   static function changeProgressBarMessage($msg="&nbsp;") {

      self::progressBar('doaction_progress', array('message' => $msg));
      self::glpi_flush();
   }


   /**
    * Change the Progress Bar Position
    *
    * @param $crt   Current Value (less then $max)
    * @param $tot   Maximum Value
    * @param $msg   message inside the bar (default is %) (default '')
    *
    * @return nothing
   **/
   static function changeProgressBarPosition($crt, $tot, $msg="") {

      $options = array();

      if (!$tot) {
         $options['percent'] = 0;
      } else if ($crt>$tot) {
         $options['percent'] = 100;
      } else {
         $options['percent'] = 100*$crt/$tot;
      }

      if ($msg != "") {
         $options['message'] = $msg;
      }

      self::progressBar('doaction_progress', $options);
      self::glpi_flush();
   }


    /**
     * Display a simple progress bar
     *
     * @param $width       Width    of the progress bar
     * @param $percent     Percent  of the progress bar
     * @param $options     array of possible options:
     *            - title : string title to display (default Progesssion)
     *            - simple : display a simple progress bar (no title / only percent)
     *            - forcepadding : boolean force str_pad to force refresh (default true)
     *
     * @return nothing
    **/
    static function displayProgressBar($width, $percent, $options=array()) {
       global $CFG_GLPI;

       $param['title']        = __('Progress');
       $param['simple']       = false;
       $param['forcepadding'] = true;

       if (is_array($options) && count($options)) {
          foreach ($options as $key => $val) {
             $param[$key] = $val;
          }
       }

       $percentwidth = floor($percent*$width/100);
       $output       = "<div class='center'><table class='tab_cadre' width='".($width+20)."px'>";

       if (!$param['simple']) {
          $output .= "<tr><th class='center'>".$param['title']."&nbsp;".$percent."%</th></tr>";
       }
       $output .= "<tr><td>
                   <table class='tabcompact'><tr><td class='center' style='background:url(".$CFG_GLPI["root_doc"].
                    "/pics/loader.png) repeat-x; padding: 0px;font-size: 10px;' width='".
                    $percentwidth." px' height='12'>";

       if ($param['simple']) {
          $output .= $percent."%";
       } else {
          $output .= '&nbsp;';
       }

       $output .= "</td></tr></table></td>";
       $output .= "</tr></table>";
       $output .= "</div>";

       if (!$param['forcepadding']) {
          echo $output;
       } else {
          echo Toolbox::str_pad($output, 4096);
          self::glpi_flush();
       }
    }

   /**
    * Include common HTML headers
    *
    * @param $title title used for the page (default '')
    *
    * @return nothing
   **/
   static function includeHeader($title='') {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");
      // Allow only frame from same server to prevent click-jacking
      header('x-frame-options:SAMEORIGIN');

      // Send extra expires header
      self::header_nocache();

      // Start the page
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
             \"http://www.w3.org/TR/html4/loose.dtd\">";
      echo "\n<html><head><title>GLPI - ".$title."</title>";
      echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";

      // Send extra expires header
      echo "<meta http-equiv='Expires' content='Fri, Jun 12 1981 08:20:00 GMT'>\n";
      echo "<meta http-equiv='Pragma' content='no-cache'>\n";
      echo "<meta http-equiv='Cache-Control' content='no-cache'>\n";
      echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";

      // auto desktop / mobile viewport
      echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";

      echo Html::css($CFG_GLPI["root_doc"]."/lib/jquery/css/smoothness/jquery-ui-1.10.4.custom.min.css");
      echo Html::css($CFG_GLPI["root_doc"]."/css/jstree/style.css");
      echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/rateit/rateit.css");
      echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/select2/select2.css");
      echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/qtip2/jquery.qtip.min.css");
      echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jcrop/jquery.Jcrop.min.css");
      echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/spectrum-colorpicker/spectrum.css");
      echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-gantt/css/style.css");
      echo Html::css($CFG_GLPI["root_doc"]."/css/jquery-glpi.css");

      //  CSS link
      echo Html::css($CFG_GLPI["root_doc"]."/css/styles.css");

      // CSS theme link
      if (isset($_SESSION["glpipalette"])) {
         echo Html::css($CFG_GLPI["root_doc"]."/css/palettes/".$_SESSION["glpipalette"].".css");
      }

      // surcharge CSS hack for IE
      echo "<!--[if lte IE 6]>" ;
      echo Html::css($CFG_GLPI["root_doc"]."/css/styles_ie.css");
      echo "<![endif]-->";
      echo Html::css($CFG_GLPI["root_doc"]."/css/print.css", array('media' => 'print'));
      echo "<link rel='shortcut icon' type='images/x-icon' href='".
             $CFG_GLPI["root_doc"]."/pics/favicon.ico' >\n";

      // Add specific css for plugins
      if (isset($PLUGIN_HOOKS['add_css']) && count($PLUGIN_HOOKS['add_css'])) {

         foreach ($PLUGIN_HOOKS["add_css"] as $plugin => $files) {
            if (is_array($files)) {
               foreach ($files as $file) {
                  if (file_exists(GLPI_ROOT."/plugins/$plugin/$file")) {
                     echo Html::css($CFG_GLPI["root_doc"]."/plugins/$plugin/$file");
                  }
               }
            } else {
               if (file_exists(GLPI_ROOT."/plugins/$plugin/$files")) {
                  echo Html::css($CFG_GLPI["root_doc"]."/plugins/$plugin/$files");
               }
            }
         }
      }

      // AJAX library
      if (isset($_SESSION['glpi_use_mode']) 
            && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/js/jquery-1.10.2.js");
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/js/jquery-ui-1.10.4.custom.js");
      } else {
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/js/jquery-1.10.2.min.js");
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/js/jquery-ui-1.10.4.custom.min.js");
      }

      echo Html::script($CFG_GLPI["root_doc"]."/lib/tiny_mce/tiny_mce.js");

      // PLugins jquery
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/backtotop/BackToTop.min.jquery.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/select2/select2.min.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/qtip2/jquery.qtip.min.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jstree/jquery.jstree.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/rateit/jquery.rateit.min.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-ui-timepicker-addon/jquery-ui-timepicker-addon.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-file-upload/js/jquery.iframe-transport.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-file-upload/js/jquery.fileupload.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jcrop/jquery.Jcrop.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/imagepaste/jquery.image_paste.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/spectrum-colorpicker/spectrum-min.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-gantt/js/jquery.fn.gantt.min.js");
      echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/autogrow/jquery.autogrow-textarea.js");

      // layout
      if (CommonGLPI::isLayoutWithMain() 
          && !CommonGLPI::isLayoutExcludedPage()) {
         echo Html::css($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-ui-scrollable-tabs/css/jquery.scrollabletab.css");
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-ui-scrollable-tabs/js/jquery.mousewheel.js");
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jqueryplugins/jquery-ui-scrollable-tabs/js/jquery.scrollabletab.js");
      }

      if (isset($_SESSION['glpilanguage'])) {
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/i18n/jquery.ui.datepicker-".
                     $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2].".js");
         $filename = "/lib/jqueryplugins/jquery-ui-timepicker-addon/i18n/jquery-ui-timepicker-".
                     $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2].".js";
         if (file_exists(GLPI_ROOT.$filename)) {
            echo Html::script($CFG_GLPI["root_doc"].$filename);
         }
         $filename = "/lib/jqueryplugins/select2/select2_locale_".
                     $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2].".js";
         if (file_exists(GLPI_ROOT.$filename)) {
            echo Html::script($CFG_GLPI["root_doc"].$filename);
         }
      }

      // Some Javascript-Functions which we may need later
      echo Html::script($CFG_GLPI["root_doc"].'/script.js');

      // Add specific javascript for plugins
      if (isset($PLUGIN_HOOKS['add_javascript']) && count($PLUGIN_HOOKS['add_javascript'])) {

         foreach ($PLUGIN_HOOKS["add_javascript"] as $plugin => $files) {
            if (is_array($files)) {
               foreach ($files as $file) {
                  if (file_exists(GLPI_ROOT."/plugins/$plugin/$file")) {
                     echo Html::script($CFG_GLPI["root_doc"]."/plugins/$plugin/$file");
                  }
               }
            } else {
               if (file_exists(GLPI_ROOT."/plugins/$plugin/$files")) {
                  echo Html::script($CFG_GLPI["root_doc"]."/plugins/$plugin/$files");
               }
            }
         }
      }

      // End of Head
      echo "</head>\n";
   }


   /**
    * @since version 0.90
    *
    * @return string
   **/
   static function getMenuInfos() {

      $menu['assets']['title']       = __('Assets');
      $menu['assets']['types']       = array('Computer', 'Monitor', 'Software',
                                             'NetworkEquipment', 'Peripheral', 'Printer',
                                             'CartridgeItem', 'ConsumableItem', 'Phone' );

      $menu['helpdesk']['title']     = __('Assistance');
      $menu['helpdesk']['types']     = array('Ticket', 'Problem', 'Change',
                                             'Planning', 'Stat', 'TicketRecurrent');

      $menu['management']['title']   = __('Management');
      $menu['management']['types']   = array('Budget', 'Supplier', 'Contact', 'Contract',
                                                'Document');

      $menu['tools']['title']        = __('Tools');
      $menu['tools']['types']        = array('Project', 'Reminder', 'RSSFeed', 'KnowbaseItem',
                                             'ReservationItem', 'Report', 'MigrationCleaner');

      $menu['plugins']['title']      = _n('Plugin', 'Plugins', Session::getPluralNumber());
      $menu['plugins']['types']      = array();

      $menu['admin']['title']        = __('Administration');
      $menu['admin']['types']        = array('User', 'Group', 'Entity', 'Rule',
                                             'Profile', 'QueuedMail', 'Backup', 'Event');

      $menu['config']['title']       = __('Setup');
      $menu['config']['types']       = array('CommonDropdown', 'CommonDevice', 'Notification',
                                             'SLA', 'Config', 'Control', 'Crontask', 'Auth',
                                             'MailCollector', 'Link', 'Plugin');

      // Special items
      $menu['preference']['title']   = __('My settings');
      $menu['preference']['default'] = '/front/preference.php';

      return $menu;
   }


   /**
    * Print a nice HTML head for every page
    *
    * @param $title     title of the page
    * @param $url       not used anymore (default '')
    * @param $sector    sector in which the page displayed is (default 'none')
    * @param $item      item corresponding to the page displayed (default 'none')
    * @param $option    option corresponding to the page displayed (default '')
   **/
   static function header($title, $url='', $sector="none", $item="none", $option="") {
      global $CFG_GLPI, $PLUGIN_HOOKS, $HEADER_LOADED, $DB;

      // If in modal : display popHeader
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popHeader($title, $url);
      }
      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Force lower case for sector and item
      $sector = strtolower($sector);
      $item   = strtolower($item);

      self::includeHeader($title);

      $body_class = "layout_".$_SESSION['glpilayout'];
      if ((strpos($_SERVER['REQUEST_URI'], ".form.php") !== false)
          && isset($_GET['id']) && ($_GET['id'] > 0)) {
         if (!CommonGLPI::isLayoutExcludedPage()) {
            $body_class.= " form";
         } else {
            $body_class = "";
         }
      }
      // Body
      echo "<body class='$body_class'>";
      // Generate array for menu and check right
      if (!isset($_SESSION['glpimenu'])
          || !is_array($_SESSION['glpimenu'])
          || (count($_SESSION['glpimenu']) == 0)) {

         // INVENTORY
         //don't change order in array
         $showallassets                 = false;
         $menu = self::getMenuInfos();

         // Permit to plugins to add entry to others sector !
         if (isset($PLUGIN_HOOKS["menu_toadd"]) && count($PLUGIN_HOOKS["menu_toadd"])) {

            foreach  ($PLUGIN_HOOKS["menu_toadd"] as $plugin => $items) {
               if (count($items)) {
                  foreach ($items as $key => $val) {
                     if (isset($menu[$key])) {
                        $menu[$key]['types'][] = $val;
                     }
                  }
               }
            }
         }

         foreach ($menu as $category => $datas) {
            if (isset($datas['types']) && count($datas['types'])) {
               foreach ($datas['types'] as $type) {
                  if ($data = $type::getMenuContent()) {
                     // Multi menu entries management
                     if (isset($data['is_multi_entries']) && $data['is_multi_entries']) {
                        if (!isset($menu[$category]['content'])) {
                           $menu[$category]['content'] = array();
                        }
                        $menu[$category]['content'] += $data;
                     } else {
                        $menu[$category]['content'][strtolower($type)] = $data;
                     }
                  }
               }
            }
            // Define default link :
            if (isset($menu[$category]['content']) && count($menu[$category]['content'])) {
               foreach ($menu[$category]['content'] as $val) {
                  if (isset($val['page'])) {
                     $menu[$category]['default'] = $val['page'];
                     break;
                  }
               }
            }
         }

         $allassets = array('Computer', 'Monitor', 'Peripheral', 'NetworkEquipment', 'Phone',
                            'Printer');

         foreach ($allassets as $type) {
            if (isset($menu['assets']['content'][strtolower($type)])) {
               $menu['assets']['content']['allassets']['title']            = __('Global');
               $menu['assets']['content']['allassets']['shortcut']         = '';
               $menu['assets']['content']['allassets']['page']             = '/front/allassets.php';
               $menu['assets']['content']['allassets']['links']['search']  = '/front/allassets.php';
               break;
            }
         }


         //  PLUGINS
//          if (isset($PLUGIN_HOOKS["menu_entry"]) && count($PLUGIN_HOOKS["menu_entry"])) {
//             $plugins = array();
//
//             foreach  ($PLUGIN_HOOKS["menu_entry"] as $plugin => $active) {
//                if ($active) { // true or a string
//                   $plugins[$plugin] = Plugin::getInfo($plugin);
//                }
//             }
//
//             if (count($plugins)) {
//                $list = array();
//
//                foreach ($plugins as $key => $val) {
//                   $list[$key] = $val["name"];
//                }
//                asort($list);
//
//                foreach ($list as $key => $val) {
//                   $menu['plugins']['content'][$key]['title'] = $val;
//                   $menu['plugins']['content'][$key]['page']  = '/plugins/'.$key.'/';
//
//                   if (is_string($PLUGIN_HOOKS["menu_entry"][$key])) {
//                      $menu['plugins']['content'][$key]['page'] .= $PLUGIN_HOOKS["menu_entry"][$key];
//                   }
//
//                   // Set default link for plugins
//                   if (!isset($menu['plugins']['default'])) {
//                      $menu['plugins']['default'] = $menu['plugins']['content'][$key]['page'];
//                   }
//
//                   if (($sector == "plugins")
//                      && ($item == $key)) {
//
//                      if (isset($PLUGIN_HOOKS["submenu_entry"][$key])
//                         && is_array($PLUGIN_HOOKS["submenu_entry"][$key])) {
//
//                         foreach ($PLUGIN_HOOKS["submenu_entry"][$key] as $name => $link) {
//                            // New complete option management
//                            if ($name == "options") {
//                               $menu['plugins']['content'][$key]['options'] = $link;
//                            } else { // Keep it for compatibility
//
//                               if (is_array($link)) {
//                                  // Simple link option
//                                  if (isset($link[$option])) {
//                                     $menu['plugins']['content'][$key]['links'][$name]
//                                                    ='/plugins/'.$key.'/'.$link[$option];
//                                  }
//                               } else {
//                                  $menu['plugins']['content'][$key]['links'][$name]
//                                                    ='/plugins/'.$key.'/'.$link;
//                               }
//                            }
//                         }
//                      }
//                   }
//                }
//             }
//          }


         $_SESSION['glpimenu'] = $menu;
//          echo 'menu load';
      } else {
         $menu = $_SESSION['glpimenu'];
      }

      $already_used_shortcut = array('1');


      echo "<div id='header'>";
      echo "<div id='header_top'>";
      echo "<div id='c_logo'>";
      echo Html::link('', $CFG_GLPI["root_doc"]."/front/central.php",
                      array('accesskey' => '1',
                            'title'     => __('Home')));
      echo "</div>";

      /// Prefs / Logout link
      echo "<div id='c_preference' >";
      echo "<ul>";

      echo "<li id='deconnexion'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/logout.php";
            /// logout witout noAuto login for extauth
      if (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth']) {
         echo "?noAUTO=1";
      }

      echo "' title=\"".__s('Logout')."\">";
      echo "<span id='logout_icon' title=\"".__s('Logout').
             "\"  alt=\"".__s('Logout')."\" class='button-icon' />";
      echo "</a>";
      echo "</li>\n";


      echo "<li id='preferences_link'><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
                 __s('My settings')."\">";
      echo "<span id='preferences_icon' title=\"".__s('My settings').
             "\"  alt=\"".__s('My settings')."\" class='button-icon' /></span>";

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         echo "<span id='myname'>";
         echo formatUserName (0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                              $_SESSION["glpifirstname"], 0, 20);
         echo "</span>";
      }
      echo "</a></li>";  

      /// Bookmark load
      echo "<li id='bookmark_link'>";
      Ajax::createIframeModalWindow('loadbookmark',
                                    $CFG_GLPI["root_doc"]."/front/bookmark.php?action=load",
                                    array('title'         => __('Load a bookmark'),
                                          'reloadonclose' => true));
      echo "<a href='#' onClick=\"".Html::jsGetElementbyID('loadbookmark').".dialog('open');\">";
      echo "<span id='bookmark_icon' title=\"".__s('Load a bookmark').
             "\"  alt=\"".__s('Load a bookmark')."\" class='button-icon' />";
      echo "</a></li>";

      echo "<li id='help_link'><a href='".
                 (empty($CFG_GLPI["central_doc_url"])
                   ? "http://glpi-project.org/help-central"
                   : $CFG_GLPI["central_doc_url"])."' target='_blank' title=\"".__s('Help')."\">".
                  "<span id='help_icon' title=\"".__s('Help').
                  "\"  alt=\"".__s('Help')."\" class='button-icon' />";
           "</a></li>";


      echo "<li id='language_link'><a href='".$CFG_GLPI["root_doc"].
                 "/front/preference.php?forcetab=User\$1' title=\"".
                 addslashes(Dropdown::getLanguageName($_SESSION['glpilanguage']))."\">".
                 Dropdown::getLanguageName($_SESSION['glpilanguage'])."</a></li>";


      /// Search engine
      echo "<li id='c_recherche'>\n";
      if ($CFG_GLPI['allow_search_global']) {
         echo "<form method='get' action='".$CFG_GLPI["root_doc"]."/front/search.php'>\n";
         echo "<span id='champRecherche'><input size='15' type='text' name='globalsearch'
                                         placeholder='". __s('Search')."'>";
         echo "</span>";
         Html::closeForm();
      }
      echo "</li>";

      
      echo "</ul>";
      echo "</div>\n";



      echo "</div>";

      ///Main menu
      echo "<div id='c_menu'>";
      echo "<ul id='menu'>";

      // Get object-variables and build the navigation-elements
      $i = 1;
      foreach ($menu as $part => $data) {
         if (isset($data['content']) && count($data['content'])) {
            $menu_class = "";
            if (isset($menu[$sector]) && $menu[$sector]['title'] == $data['title']) {
               $menu_class = "active";
            }

            echo "<li id='menu$i' class='$menu_class' onmouseover=\"javascript:menuAff('menu$i','menu');\" >";
            $link = "#";

            if (isset($data['default']) && !empty($data['default'])) {
               $link = $CFG_GLPI["root_doc"].$data['default'];
            }

            if (Toolbox::strlen($data['title']) > 14) {
               $data['title'] = Toolbox::substr($data['title'], 0, 14)."...";
            }
            echo "<a href='$link' class='itemP'>".$data['title']."</a>";
            echo "<ul class='ssmenu'>";


            // list menu item
            foreach ($data['content'] as $key => $val) {
               $menu_class       = "";
               $tmp_active_item  = explode("/", $item);
               $active_item      = array_pop($tmp_active_item);
               if (isset($menu[$sector]['content'])
                   && isset($menu[$sector]['content'][$active_item])
                   && isset($val['title'])
                   && ($menu[$sector]['content'][$active_item]['title'] == $val['title'])) {
                  $menu_class = "active";
               }
               if (isset($val['page'])
                   && isset($val['title'])) {
                  echo "<li class='$menu_class'><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

                  if (isset($val['shortcut']) && !empty($val['shortcut'])) {
                     if (!isset($already_used_shortcut[$val['shortcut']])) {
                        echo " accesskey='".$val['shortcut']."'";
                        $already_used_shortcut[$val['shortcut']] = $val['shortcut'];
                     }
                     echo ">".Toolbox::shortcut($val['title'], $val['shortcut'])."</a></li>\n";
                  } else {
                     echo ">".$val['title']."</a></li>\n";
                  }
               }
            }
            echo "</ul></li>";
            $i++;
         }
      }

      echo "</ul>"; // #menu

      // Display MENU ALL
      self::displayMenuAll($menu);

      // End navigation bar
      // End headline


      //  Le fil d ariane
      echo "<div id='c_ssmenu2' >";
      echo "<ul>";

      // Display item
      echo "<li class='breadcrumb_item'><a href='".$CFG_GLPI["root_doc"]."/front/central.php' title=\"". __s('Home')."\">".
            __('Home')."</a></li>";

      if (isset($menu[$sector])) {
         $link = "/front/central.php";

         if (isset($menu[$sector]['default'])) {
            $link = $menu[$sector]['default'];
         }
         echo "<li class='breadcrumb_item'><a href='".$CFG_GLPI["root_doc"].$link."' title=\"".$menu[$sector]['title']."\">".
                    $menu[$sector]['title']."</a></li>";
      }

      if (isset($menu[$sector]['content'][$item])) {
         // Title
         $with_option = false;

         if (!empty($option)
             && isset($menu[$sector]['content'][$item]['options'][$option]['title'])
             && isset($menu[$sector]['content'][$item]['options'][$option]['page'])) {

            $with_option = true;
         }

         if (isset($menu[$sector]['content'][$item]['page'])) {
            echo "<li class='breadcrumb_item'><a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['page']."' ".
                       ($with_option?"":"class='here'")." title=\"".
                       $menu[$sector]['content'][$item]['title']."\" >".
                       $menu[$sector]['content'][$item]['title']."</a>".
                 "</li>";
         }

         if ($with_option) {
            echo "<li class='breadcrumb_item'><a href='".$CFG_GLPI["root_doc"].
                       $menu[$sector]['content'][$item]['options'][$option]['page'].
                       "' class='here' title=\"".
                       $menu[$sector]['content'][$item]['options'][$option]['title']."\" >";
            echo self::resume_name($menu[$sector]['content'][$item]['options'][$option]['title'],
                                   17);
            echo "</a></li>";
         }

         $links = array();
         // Item with Option case
         if (!empty($option)
             && isset($menu[$sector]['content'][$item]['options'][$option]['links'])
             && is_array($menu[$sector]['content'][$item]['options'][$option]['links'])) {
            $links = $menu[$sector]['content'][$item]['options'][$option]['links'];

         // Without option case : only item links
         } else if (isset($menu[$sector]['content'][$item]['links'])
                    && is_array($menu[$sector]['content'][$item]['links'])) {

            $links = $menu[$sector]['content'][$item]['links'];
         }

         // Add item
         echo "<li class='icons_block'>";
         echo "<span>";
         if (isset($links['add'])) {
            echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_add.png",
                             array('alt' => __('Add'),
                                    'url' => $CFG_GLPI["root_doc"].$links['add']));
         } else {
            echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_add_off.png",
                             array('alt' => __('Add')));
         }
         echo "</span>";

         // Search Item
         echo "<span>";
         if (isset($links['search'])) {
            echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_search.png",
                             array('alt' => __('Search'),
                                   'url' => $CFG_GLPI["root_doc"].$links['search']));
         } else {
            echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_search_off.png",
                             array('alt' => __('Search')));
         }
         echo "</span>";
        // Links
         if (count($links) > 0) {
            foreach ($links as $key => $val) {

               switch ($key) {
                  case "add" :
                  case "search" :
                     break;

                  case "template" :
                     echo "<span>";
                     echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_addtemplate.png",
                                      array('alt' => __('Manage templates...'),
                                            'url' => $CFG_GLPI["root_doc"].$val));
                     echo "</span>";
                     break;

                  case "showall" :
                     echo "<span>";
                     echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_showall.png",
                                      array('alt' => __('Show all'),
                                            'url' => $CFG_GLPI["root_doc"].$val));
                     echo "</span>";
                     break;

                  case "summary" :
                     echo "<span>";
                     echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_show.png",
                                      array('alt' => __('Summary'),
                                            'url' => $CFG_GLPI["root_doc"].$val));
                     echo "</span>";
                     break;

                  case "config" :
                     echo "<span>";
                     echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_config.png",
                                      array('alt' => __('Setup'),
                                            'url' => $CFG_GLPI["root_doc"].$val));
                     echo "</span>";
                     break;

                  default :
                     echo "<span>".Html::link($key, $CFG_GLPI["root_doc"].$val)."</span>";
                     break;
               }
            }
         }
         echo "</li>";

      } else {
         echo "<li>&nbsp;</li>";
      }

      // Add common items




      // Profile selector
      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         self::showProfileSelecter($CFG_GLPI["root_doc"]."/front/central.php");
      }
      echo "</ul>";
      echo "</div>";

      echo "</div>\n"; // fin header

      echo "<div id='page' >";

      if ($DB->isSlave()
          && !$DB->first_connection) {
         echo "<div id='dbslave-float'>";
         echo "<a href='#see_debug'>".__('MySQL replica: read only')."</a>";
         echo "</div>";
      }

      // Back to top
      Html::scriptStart();
      echo "$(document).ready(function(){
               BackToTop({
               text : '^',
               class: 'vsubmit',
               autoShow : true,
               timeEffect : 100,
               autoShowOffset : '0',
               appearMethod : '',
               effectScroll : 'linear'
               });
            });";
      echo Html::scriptEnd();


      // call static function callcron() every 5min
      CronTask::callCron();
      self::displayMessageAfterRedirect();
   }


   /**
    * Print footer for every page
    *
    * @param $keepDB booleen, closeDBConnections if false (false by default)
   **/
   static function footer($keepDB=false) {
      global $CFG_GLPI, $FOOTER_LOADED, $TIMER_DEBUG;

      // If in modal : display popHeader
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popFooter();
      }

      // Print foot for every page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;
      echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

      echo "<div id='footer' >";
      echo "<table width='100%'><tr><td class='left'><span class='copyright'>";
      $timedebug = sprintf(_n('%s second', '%s seconds', $TIMER_DEBUG->getTime()),
                           $TIMER_DEBUG->getTime());

      if (function_exists("memory_get_usage")) {
         $timedebug = sprintf(__('%1$s - %2$s'), $timedebug, Toolbox::getSize(memory_get_usage()));
      }
      echo $timedebug;
      echo "</span></td>";

      if (!empty($CFG_GLPI["founded_new_version"])) {
         echo "<td class='copyright'>";
         $latest_version = "<a href='http://www.glpi-project.org' target='_blank' title=\"".
                              __s('You will find it on the GLPI-PROJECT.org site.')."\"> ".
                           preg_replace('/0$/','',$CFG_GLPI["founded_new_version"])."</a>";
         printf(__('A new version is available: %s.'), $latest_version);

         echo "</td>";
      }
      echo "<td class='right'>";
      echo "<a href='http://glpi-project.org/'>";
      echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C)".
           " 2015".
           /*"-".date("Y").*/ // TODO, decomment this in 2016
           " by Teclib'".
           " - Copyright (C) 2003-2015 INDEPNET Development Team".
           "</span>";
      echo "</a></td>";
      echo "</tr></table></div>";

      if ($_SESSION['glpi_use_mode'] == Session::TRANSLATION_MODE) { // debug mode traduction
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI TRANSLATION MODE</a>";
         echo "</div>";
      }

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI DEBUG MODE</a>";
         echo "</div>";
      }
      if ($CFG_GLPI['maintenance_mode']) { // mode maintenance
         echo "<div id='maintenance-float'>";
         echo "<a href='#see_maintenance'>GLPI MAINTENANCE MODE</a>";
         echo "</div>";
      }
      self::displayDebugInfos();
      echo "</body></html>";

      if (!$keepDB) {
         closeDBConnections();
      }
   }


   /**
    * Display Ajax Footer for debug
   **/
   static function ajaxFooter() {

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
         $rand = mt_rand();
         echo "<div class='center' id='debugajax'>";
         echo "<a class='debug-float' href=\"javascript:showHideDiv('see_ajaxdebug$rand','','','');\">
                AJAX DEBUG</a>";
         if (!isset($_GET['full_page_tab'])
             && strstr($_SERVER['REQUEST_URI'], '/ajax/common.tabs.php')) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "<a href='".$_SERVER['REQUEST_URI']."&full_page_tab=1' class='vsubmit'>Display only tab for debug</a>";
         }
         echo "</div>";
         echo "<div id='see_ajaxdebug$rand' name='see_ajaxdebug$rand' style=\"display:none;\">";
         self::displayDebugInfos(false);
         echo "</div></div>";
      }
   }


   /**
    * Print a simple HTML head with links
    *
    * @param $title        title of the page
    * @param $links array  of links to display
   **/
   static function simpleHeader($title, $links=array()) {
      global $CFG_GLPI, $HEADER_LOADED;

      // Print a nice HTML-head for help page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      self::includeHeader($title);

      // Body
      echo "<body>";

      // Main Headline
      echo "<div id='header'>";
      echo "<div id='header_top'>";

      echo "<div id='c_logo'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/' accesskey='1' title=\"".__s('Home')."\">".
           "<span class='invisible'>Logo</span></a></div>";

      // Les préférences + lien déconnexion
      echo "<div id='c_preference'>";
      echo "<div class='sep'></div>";
      echo "</div>";

      echo "</div>"; // end #header_top

      //-- Le menu principal --
      echo "<div id='c_menu'>";
      echo "<ul id='menu'>";

      // Build the navigation-elements
      if (count($links)) {
         $i = 1;

         foreach ($links as $name => $link) {
            echo "<li id='menu$i'>";
            echo "<a href='$link' title=\"".$name."\" class='itemP'>".$name."</a>";
            echo "</li>";
            $i++;
         }
      }
      echo "</ul></div>";
      // End navigation bar
      // End headline

      //  Le fil d ariane
      echo "<div id='c_ssmenu2'></div>";
      echo "</div>"; // fin header
      echo "<div id='page'>";

      // call static function callcron() every 5min
      CronTask::callCron();
   }


   /**
    * Print a nice HTML head for help page
    *
    * @param $title  title of the page
    * @param $url    not used anymore (default '')
   **/
   static function helpHeader($title, $url='') {
      global $CFG_GLPI, $HEADER_LOADED, $PLUGIN_HOOKS;

      // Print a nice HTML-head for help page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      self::includeHeader($title);

      // Body
      $body_class = "layout_".$_SESSION['glpilayout'];
      if ((strpos($_SERVER['REQUEST_URI'], "form.php") !== false)
          && isset($_GET['id']) && ($_GET['id'] > 0)) {
         if (!CommonGLPI::isLayoutExcludedPage()) {
            $body_class.= " form";
         } else {
            $body_class = "";
         }
      }
     echo "<body class='$body_class'>";

      // Main Headline
      echo "<div id='header'>";
      echo "<div id='header_top'>";

      echo "<div id='c_logo'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' accesskey='1' title=\"".
             __s('Home')."\"><span class='invisible'>Logo</span></a>";
      echo "</div>";

      // Les préférences + lien déconnexion
      echo "<div id='c_preference' >";
      echo "<ul>";

      echo "<li id='deconnexion'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/logout.php";
            /// logout witout noAuto login for extauth
      if (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth']) {
         echo "?noAUTO=1";
      }

      echo "' title=\"".__s('Logout')."\">";
      // check user id : header used for display messages when session logout
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/logout.png' title=\"".__s('Logout').
             "\"  alt=\"".__s('Logout')."\" class='button-icon'>";
      echo "</a>";
      echo "</li>\n";

      echo "<li id='preferences_link'><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
                 __s('My settings')."\">";
      echo "<span id='preferences_icon' title=\"".__s('My settings').
             "\"  alt=\"".__s('My settings')."\" class='button-icon' /></span>";

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         echo "<span id='myname'>";
         echo formatUserName (0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                              $_SESSION["glpifirstname"], 0, 20);
         echo "</span>";
      }
      echo "</a></li>";  

      echo "<li>";
      Ajax::createIframeModalWindow('loadbookmark',
                                    $CFG_GLPI["root_doc"]."/front/bookmark.php?action=load",
                                    array('title'         => __('Load a bookmark'),
                                          'reloadonclose' => true));
      echo "<a href='#' onClick=\"".Html::jsGetElementbyID('loadbookmark').".dialog('open');\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark.png' title=\"".__s('Load a bookmark').
             "\"  alt=\"".__s('Load a bookmark')."\" class='button-icon'>";
      echo "</a></li>";

      echo "<li id='help_link'>".
            "<a href='".(empty($CFG_GLPI["helpdesk_doc_url"])
                        ? "http://glpi-project.org/help-helpdesk"
                        : $CFG_GLPI["helpdesk_doc_url"])."' target='_blank' title=\"".__s('Help')."\">".
           "<img src='".$CFG_GLPI["root_doc"]."/pics/help.png' title=\"".__s('Help').
                  "\"  alt=\"".__s('Help')."\" class='button-icon'>";
           "</a></li>";

      echo "</ul>";
      echo "</div>";

      //-- Le moteur de recherche --
      echo "<div id='c_recherche'></div>";


      echo "</div>";

      //-- Le menu principal --
      echo "<div id='c_menu'>";

      // Build the navigation-elements
      $menu = array();

      //  Create ticket
      if (Session::haveRight("ticket", CREATE)) {
         $menu['create_ticket']['id']      = "menu1";
         $menu['create_ticket']['default'] = '/front/helpdesk.public.php?create_ticket=1';
         $menu['create_ticket']['title']   = __s('Create a ticket');
         $menu['create_ticket']['content'] = array(true);
      }
      
      //  Tickets
      if (Session::haveRight("ticket", CREATE) 
          || Session::haveRight("ticket", Ticket::READMY)
          || Session::haveRight("followup", TicketFollowup::SEEPUBLIC)) {
         $menu['tickets']['id']      = "menu2";
         $menu['tickets']['default'] = '/front/ticket.php';
         $menu['tickets']['title']   = _n('Ticket','Tickets', Session::getPluralNumber());
         $menu['tickets']['content'] = array(true);
      }
      
      // Reservation
      if (Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
         $menu['reservation']['id']      = "menu3";
         $menu['reservation']['default'] = '/front/reservationitem.php';
         $menu['reservation']['title']   = _n('Reservation', 'Reservations', Session::getPluralNumber());
         $menu['reservation']['content'] = array(true);
      }
      
      // FAQ
      if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
         $menu['faq']['id']      = "menu4";
         $menu['faq']['default'] = '/front/helpdesk.faq.php';
         $menu['faq']['title']   = __s('FAQ');
         $menu['faq']['content'] = array(true);
      }

      echo "<ul id='menu'>";

      // Display Home menu
      echo "<li id='menu1'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' title=\"".
             __s('Home')."\" class='itemP'>".__('Home')."</a>";
      echo "</li>";

      // display menu items
      foreach ($menu as $menu_item) {
         echo "<li id='".$menu_item['id']."'>";
         echo "<a href='".$CFG_GLPI["root_doc"].$menu_item['default']."' ".
                "title=\"".$menu_item['title']."\" class='itemP'>".$menu_item['title']."</a>";
         echo "</li>";
      }

      // Plugins
      $menu['plugins']['id']      = "menu5";
      $menu['plugins']['default'] = "#";
      $menu['plugins']['title']   = _sn('Plugin', 'Plugins', Session::getPluralNumber());
      $menu['plugins']['content'] = array();
      if (isset($PLUGIN_HOOKS["helpdesk_menu_entry"])
          && count($PLUGIN_HOOKS["helpdesk_menu_entry"])) {

         foreach ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {
            if ($active) {
               $infos = Plugin::getInfo($plugin);
               $link = "";
               if (is_string($PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin])) {
                  $link = $PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin];
               }
               $infos['page'] = $link;
               $infos['title'] = $infos['name'];
               $menu['plugins']['content'][$plugin] = $infos;
            }
         }
      }

      // Display plugins
      if (isset($menu['plugins']['content']) && count($menu['plugins']['content']) > 0) {
         asort($menu['plugins']['content']);
         echo "<li id='menu5' onmouseover=\"javascript:menuAff('menu5','menu');\">";
         echo "<a href='#' title=\"".
                _sn('Plugin', 'Plugins', Session::getPluralNumber())."\" class='itemP'>".
                __('Plugins')."</a>"; // default none
         echo "<ul class='ssmenu'>";

         // list menu item
         foreach ($menu['plugins']['content'] as $key => $val) {
            echo "<li><a href='".$CFG_GLPI["root_doc"]."/plugins/".$key.$val['page']."'>".
                       $val["title"]."</a></li>";
         }
         echo "</ul></li>";
      }
      echo "</ul>";

      // Display MENU ALL
      self::displayMenuAll($menu);

      echo "</div>";


      // End navigation bar
      // End headline

      //  Le fil d ariane
      echo "<div id='c_ssmenu2'>";
      echo "<ul>";
      echo "<li class='breadcrumb_item'>".
           "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' title=\"". __s('Home')."\">".
             __('Home')."</a></li>";


      if (TicketValidation::getValidateRights()) {
         $opt                              = array();
         $opt['reset']                     = 'reset';
         $opt['criteria'][0]['field']      = 55; // validation status
         $opt['criteria'][0]['searchtype'] = 'equals';
         $opt['criteria'][0]['value']      = TicketValidation::WAITING;
         $opt['criteria'][0]['link']       = 'AND';

         $opt['criteria'][1]['field']      = 59; // validation aprobator
         $opt['criteria'][1]['searchtype'] = 'equals';
         $opt['criteria'][1]['value']      = Session::getLoginUserID();
         $opt['criteria'][1]['link']       = 'AND';


         $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($opt,'&amp;');
         $pic_validate = "<a href='$url_validate'>".
                         "<img title=\"".__s('Ticket waiting for your approval')."\" alt=\"".
                           __s('Ticket waiting for your approval')."\" src='".
                           $CFG_GLPI["root_doc"]."/pics/menu_showall.png' class='pointer'></a>";
         echo "<li class='icons_block'>$pic_validate</li>\n";
      }

      if (Session::haveRight('ticket', CREATE)
          && strpos($_SERVER['PHP_SELF'],"ticket")) {
         echo "<li class='icons_block'><a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png' title=\"".__s('Add').
                "\" alt=\"".__s('Add')."\" class='pointer'></a></li>";
      }

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         self::showProfileSelecter($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
      }
      echo "</ul></div>";

      echo "</div>"; // fin header
      echo "<div id='page' >";

      // call static function callcron() every 5min
      CronTask::callCron();
      self::displayMessageAfterRedirect();
   }


   /**
    * Print footer for help page
   **/
   static function helpFooter() {
      global $CFG_GLPI, $FOOTER_LOADED;

      // Print foot for help page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

      echo "<div id='footer'>";
      echo "<table width='100%'><tr><td class='right'>";
      echo "<a href='http://glpi-project.org/'>";
      echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"].
           " Copyright (C) ".
           "2015-".
           //date("Y"). // TODO, decomment this in 2016
           " by Teclib'".
           " - Copyright (C) 2003-2015 INDEPNET Development Team".
           "</span>";
      echo "</a></td></tr></table></div>";

      if ($_SESSION['glpi_use_mode'] == Session::TRANSLATION_MODE) { // debug mode traduction
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI TRANSLATION MODE</a>";
         echo "</div>";
      }

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI DEBUG MODE</a>";
         echo "</div>";
      }
      self::displayDebugInfos();
      echo "</body></html>";
      closeDBConnections();
   }


   /**
    * Print a nice HTML head with no controls
    *
    * @param $title  title of the page
    * @param $url    not used anymore (default '')
   **/
   static function nullHeader($title, $url='') {
      global $CFG_GLPI, $HEADER_LOADED;

      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Print a nice HTML-head with no controls

      // Detect root_doc in case of error
      Config::detectRootDoc();

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");

      // Send extra expires header if configured
      self::header_nocache();

      if (isCommandLine()) {
         return true;
      }

      self::includeHeader($title);

      // Body with configured stuff
      echo "<body>";
      echo "<div id='page'>";
      echo "<br><br>";
      echo "<div id='bloc'>";
      echo "<div id='logo_bloc'></div>";
   }


   /**
    * Print footer for null page
   **/
   static function nullFooter() {
      global $CFG_GLPI, $FOOTER_LOADED;

      // Print foot for null page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      if (!isCommandLine()) {
         echo "</div></div>";

         echo "<div id='footer-login'>";
         echo "<a href='http://glpi-project.org/' title='Powered By Teclib'>";
         echo "GLPI version ".(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").
              " Copyright (C) ".
              "2015-".
              //date("Y"). // TODO, decomment this in 2016
              " By Teclib'".
              " - Copyright (C) 2003-2015 INDEPNET Development Team";
         echo "</a></div>";

         echo "</body></html>";
      }
      closeDBConnections();
   }


   /**
    * Print a nice HTML head for modal window (nothing to display)
    *
    * @param $title   title of the page
    * @param $url     not used anymore (default '')
    * @param $iframed indicate if page loaded in iframe - css target (default false)
   **/
   static function popHeader($title, $url='', $iframed = false) {
      global $CFG_GLPI, $PLUGIN_HOOKS, $HEADER_LOADED;

      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      self::includeHeader($title); // Body
      echo "<body class='".($iframed? "iframed": "")."'>";
      self::displayMessageAfterRedirect();
   }


   /**
    * Print footer for a modal window
   **/
   static function popFooter() {
      global $FOOTER_LOADED;

      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      // Print foot
      echo "</body></html>";
   }



   /**
    * Display responsive menu
    * @since 0.90.1
    * @param $menu array of menu items
    *    - key   : plugin system name
    *    - value : array of options 
    *       * id      : html id attribute
    *       * default : defaul url
    *       * title   : displayed label
    *       * content : menu sub items, array with theses options : 
    *          - page     : url
    *          - title    : displayed label
    *          - shortcut : keyboard shortcut letter
    */
   static function displayMenuAll($menu = array()) {
      global $CFG_GLPI;

      // Display MENU ALL
      echo "<div id='show_all_menu' class='invisible'>";
      $items_per_columns = 15;
      $i                 = -1;

      foreach ($menu as $part => $data) {
         if (isset($data['content']) && count($data['content'])) {
            echo "<table class='all_menu_block'>";
            $link = "#";

            if (isset($data['default']) && !empty($data['default'])) {
               $link = $CFG_GLPI["root_doc"].$data['default'];
            }

            echo "<tr><td class='tab_bg_1 b'>";
            echo "<a href='$link' title=\"".$data['title']."\" class='itemP'>".$data['title']."</a>";
            echo "</td></tr>";
            $i++;

            // list menu item
            foreach ($data['content'] as $key => $val) {

               if (isset($val['page'])
                   && isset($val['title'])) {
                  echo "<tr><td><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

                  if (isset($data['shortcut']) && !empty($data['shortcut'])) {
                     echo " accesskey='".$val['shortcut']."'";
                  }
                  echo ">".$val['title']."</a></td></tr>\n";
                  $i++;
               }
            }
            echo "</table>";
         }
      }

      echo "</div>";

      // init menu in jquery dialog
      Html::scriptStart();
      echo self::jsGetElementbyID('show_all_menu').".dialog({
         height: 'auto',
         width: 'auto',
         modal: true,
         autoOpen: false
         });";
      echo Html::scriptEnd();


      /// Button to toggle responsive menu
      echo "<a href='#' onClick=\"".self::jsGetElementbyID('show_all_menu').".dialog('open');\"
            id='menu_all_button' class='button-icon'>";
      echo "</a>";

      echo "</div>";
   }


   /**
    * Flush the current displayed items (do not works really fine)
   **/
   static function glpi_flush() {

      flush();
      if (function_exists("ob_flush")
          && (ob_get_length() !== FALSE)) {
         ob_flush();
      }
   }


   /**
    * Set page not to use the cache
   **/
   static function header_nocache() {

      header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe
   }



   /**
    * show arrow for massives actions : opening
    *
    * @param $formname  string
    * @param $fixed     boolean  used tab_cadre_fixe in both tables (false by default)
    * @param $ontop              display on top of the list (false by default)
    * @param $onright            display on right of the list (false by default)
    * \deprecated since 0.84
   **/
   static function openArrowMassives($formname, $fixed=false, $ontop=false, $onright=false) {
      global $CFG_GLPI;

      if ($fixed) {
         echo "<table class='tab_glpi' width='950px'>";
      } else {
         echo "<table class='tab_glpi' width='80%'>";
      }

      echo "<tr>";
      if (!$onright) {
         echo "<td><img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left".($ontop?'-top':'').".png'
                    alt=''></td>";
      } else {
         echo "<td class='left' width='80%'></td>";
      }
      echo "<td class='center' style='white-space:nowrap;'>";
      echo "<a onclick= \"if ( markCheckboxes('$formname') ) return false;\"
             href='#'>".__('Check all')."</a></td>";
      echo "<td>/</td>";
      echo "<td class='center' style='white-space:nowrap;'>";
      echo "<a onclick= \"if ( unMarkCheckboxes('$formname') ) return false;\"
             href='#'>".__('Uncheck all')."</a></td>";

      if ($onright) {
         echo "<td><img src='".$CFG_GLPI["root_doc"]."/pics/arrow-right".($ontop?'-top':'').".png'
                    alt=''>";
      } else {
         echo "<td class='left' width='80%'>";
      }

   }


   /**
    * show arrow for massives actions : closing
    *
    * @param $actions array of action : $name -> $label
    * @param $confirm array of confirmation string (optional)
    * \deprecated since 0.84
   **/
   static function closeArrowMassives($actions, $confirm=array()) {

      if (count($actions)) {
         foreach ($actions as $name => $label) {
            if (!empty($name)) {

               echo "<input type='submit' name='$name' ";
               if (is_array($confirm) && isset($confirm[$name])) {
                  echo self::addConfirmationOnAction($confirm[$name]);
               }
               echo "value=\"".addslashes($label)."\" class='submit'>&nbsp;";
            }
         }
      }
      echo "</td></tr>";
      echo "</table>";
   }


   /**
    * Display "check All as" checkbox
    *
    * @since version 0.84
    *
    * @param $container_id  string html of the container of checkboxes link to this check all checkbox
    * @param $rand          string rand value to use (default is auto generated) (default ''))
    *
    * @return nothing / display item
   **/
   static function checkAllAsCheckbox($container_id, $rand='') {
      echo Html::getCheckAllAsCheckbox($container_id, $rand);
   }


   /**
    * Get "check All as" checkbox
    *
    * @since version 0.84
    *
    * @param $container_id  string html of the container of checkboxes link to this check all checkbox
    * @param $rand          string rand value to use (default is auto generated)(default '')
    *
    * @return Get checkbox string
   **/
   static function getCheckAllAsCheckbox($container_id, $rand='') {

      if (empty($rand)) {
         $rand = mt_rand();
      }

      $out  = "<div class='form-group-checkbox'>
                  <input title='".__s('Check all as')."' type='checkbox' class='new_checkbox' ".
                   "name='_checkall_$rand' id='checkall_$rand' ".
                    "onclick= \"if ( checkAsCheckboxes('checkall_$rand', '$container_id'))
                                                   {return true;}\">
                  <label class='label-checkbox' for='checkall_$rand' title='".__s('Check all as')."'>
                     <span class='check'></span>
                     <span class='box'></span>
                  </label>
               </div>";

      return $out;
   }


   /**
    * Get the jquery criterion for massive checkbox update
    * We can filter checkboxes by a container or by a tag. We can also select checkboxes that have
    * a given tag and that are contained inside a container
    *
    * @since version 0.85
    *
    * @param $options array of parameters:
    *                - tag_for_massive tag of the checkboxes to update
    *                - container_id    if of the container of the checkboxes
    *
    * @return the javascript code for jquery criterion or empty string if it is not a
    *         massive update checkbox
   **/
   static function getCriterionForMassiveCheckboxes(array $options) {

      $params                    = array();
      $params['tag_for_massive'] = '';
      $params['container_id']    = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!empty($params['tag_for_massive'])
          || !empty($params['container_id'])) {
         // Filtering on the container !
         if (!empty($params['container_id'])) {
            $criterion = '#' . $params['container_id'] . ' ';
         } else {
            $criterion = '';
         }

         // We only want the checkbox input
         $criterion .= 'input[type="checkbox"]';

         // Only the given massive tag !
         if (!empty($params['tag_for_massive'])) {
            $criterion .= '[data-glpicore-cb-massive-tags~="' . $params['tag_for_massive'] . '"]';
         }

         // Only enabled checkbox
         $criterion .= ':enabled';

         return addslashes($criterion);
      }
      return '';
   }


   /**
    * Get a checkbox.
    *
    * @since version 0.85
    *
    * @param $options array of parameters:
    *                - title         its title
    *                - name          its name
    *                - id            its id
    *                - value         the value to set when checked
    *                - readonly      can we edit it ?
    *                - massive_tags  the tag to set for massive checkbox update
    *                - checked       is it checked or not ?
    *                - zero_on_empty do we send 0 on submit when it is not checked ?
    *                - specific_tags HTML5 tags to add
    *                - criterion     the criterion for massive checkbox
    *
    * @return the HTML code for the checkbox
   **/
   static function getCheckbox(array $options) {
      global $CFG_GLPI;

      $params                    = array();
      $params['title']           = '';
      $params['name']            = '';
      $params['rand']            = mt_rand();
      $params['id']              = "check_".$params['rand'];
      $params['value']           = 1;
      $params['readonly']        = false;
      $params['massive_tags']    = '';
      $params['checked']         = false;
      $params['zero_on_empty']   = true;
      $params['specific_tags']   = array();
      $params['criterion']       = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $out = "<span class='form-group-checkbox'>";
      $out.= "<input type='checkbox' class='new_checkbox' ";

      foreach (array('id', 'name', 'title', 'value') as $field) {
         if (!empty($params[$field])) {
            $out .= " $field='".$params[$field]."'";
         }
      }

      $criterion = self::getCriterionForMassiveCheckboxes($params['criterion']);
      if (!empty($criterion)) {
         $out .= " onClick='massiveUpdateCheckbox(\"$criterion\", this)'";
      }

      if ($params['zero_on_empty']) {
         $out                               .= " data-glpicore-cb-zero-on-empty='1'";
         $CFG_GLPI['checkbox-zero-on-empty'] = true;

      }

      if (!empty($params['massive_tags'])) {
         $params['specific_tags']['data-glpicore-cb-massive-tags'] = $params['massive_tags'];
      }

      if (!empty($params['specific_tags'])) {
         foreach ($params['specific_tags'] as $tag => $values) {
            if (is_array($values)) {
               $values = implode(' ', $values);
            }
            $out .= " $tag='$values'";
         }
      }

      if ($params['readonly']) {
         $out .= " disabled='disabled'";
      }

      if ($params['checked']) {
         $out .= " checked";
      }

      $out .= ">";
      $out .= "<label class='label-checkbox' title='".$params['title']."' for='".$params['id']."'>";
      $out .= " <span class='check'></span>";
      $out .= " <span class='box'></span>";
      $out .= "&nbsp;";
      $out .= "</label>";
      $out .= "</span>";

      return $out;
   }


   /**
    * @brief display a checkbox that $_POST 0 or 1 depending on if it is checked or not.
    * @see Html::getCheckbox()
    *
    * @since version 0.85
    *
    * @param $options   array
    *
    * @return nothing (display only)
   **/
   static function showCheckbox(array $options=array()) {
      echo self::getCheckbox($options);
   }


   /**
    * Get the massive action checkbox
    *
    * @since version 0.84
    *
    * @param $itemtype             Massive action itemtype
    * @param $id                   ID of the item
    * @param $options      array
    *
    * @return get checkbox
   **/
   static function getMassiveActionCheckBox($itemtype, $id, array $options=array()) {

      $options['checked']       = (isset($_SESSION['glpimassiveactionselected'][$itemtype][$id]));
      if (!isset($options['specific_tags']['data-glpicore-ma-tags'])) {
         $options['specific_tags']['data-glpicore-ma-tags'] = 'common';
      }
      $options['name']          = "item[$itemtype][".$id."]";
      $options['zero_on_empty'] = false;

      return self::getCheckbox($options);
   }


   /**
    * Show the massive action checkbox
    *
    * @since version 0.84
    *
    * @param $itemtype             Massive action itemtype
    * @param $id                   ID of the item
    * @param $options      array
    *
    * @return show checkbox
   **/
   static function showMassiveActionCheckBox($itemtype, $id, array $options=array()) {
      echo Html::getMassiveActionCheckBox($itemtype, $id, $options);
   }


   /**
    * Display open form for massive action
    *
    * @since version 0.84
    *
    * @param $name given name/id to the form   (default '')
    *
    * @return nothing / display item
   **/
   static function openMassiveActionsForm($name='') {
      echo Html::getOpenMassiveActionsForm($name);
   }


   /**
    * Get open form for massive action string
    *
    * @since version 0.84
    *
    * @param $name given name/id to the form   (default '')
    *
    * @return open form string
   **/
   static function getOpenMassiveActionsForm($name='') {
      global $CFG_GLPI;

      if (empty($name)) {
         $name = 'massaction_'.mt_rand();
      }
      return  "<form name='$name' id='$name' method='post'
               action='".$CFG_GLPI["root_doc"]."/front/massiveaction.php'>";
   }


   /**
    * Display massive actions
    *
    * @since 0.84 (before Search::displayMassiveActions)
    * @since version 0.85 only 1 parameter (in 0.84 $itemtype required)
    *
    * @todo replace 'hidden' by data-glpicore-ma-tags ?
    *
    * @param $options   array    of parameters
    * must contains :
    *    - container       : DOM ID of the container of the item checkboxes (since version 0.85)
    * may contains :
    *    - num_displayed   : integer number of displayed items. Permit to check suhosin limit. (default -1 not to check)
    *    - ontop           : boolean true if displayed on top (default true)
    *    - fixed           : boolean true if used with fixed table display (default true)
    *    - forcecreate     : boolean force creation of modal window (default = false).
    *            Modal is automatically created when displayed the ontop item.
    *            If only a bottom one is displayed use it
    *    - check_itemtype   : string alternate itemtype to check right if different from main itemtype (default empty)
    *    - check_items_id   : integer ID of the alternate item used to check right / optional (default empty)
    *    - is_deleted       : boolean is massive actions for deleted items ?
    *    - extraparams      : string extra URL parameters to pass to massive actions (default empty)
    *                         if ([extraparams]['hidden'] is set : add hidden fields to post)
    *    - specific_actions : array of specific actions (do not use standard one)
    *    - add_actions      : array of actions to add (do not use standard one)
    *    - confirm          : string of confirm message before massive action
    *    - item             : CommonDBTM object that has to be passed to the actions
    *    - tag_to_send      : the tag of the elements to send to the ajax window (default: common)
    *
    * @return nothing
   **/
   static function showMassiveActions($options=array()) {
      global $CFG_GLPI;

      /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

      $p['ontop']             = true;
      $p['num_displayed']     = -1;
      $p['fixed']             = true;
      $p['forcecreate']       = false;
      $p['check_itemtype']    = '';
      $p['check_items_id']    = '';
      $p['is_deleted']        = false;
      $p['extraparams']       = array();
      $p['width']             = 800;
      $p['height']            = 400;
      $p['specific_actions']  = array();
      $p['add_actions']       = array();
      $p['confirm']           = '';
      $p['rand']              = '';
      $p['container']         = '';
      $p['display_arrow']     = true;
      $p['title']             = _n('Action', 'Actions', Session::getPluralNumber());
      $p['item']              = false;
      $p['tag_to_send']      = 'common';

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $url = $CFG_GLPI['root_doc']."/ajax/massiveaction.php";
      if ($p['container']) {
         $p['extraparams']['container'] = $p['container'];
      }
      if ($p['is_deleted']) {
         $p['extraparams']['is_deleted'] = 1;
      }
      if (!empty($p['check_itemtype'])) {
         $p['extraparams']['check_itemtype'] = $p['check_itemtype'];
      }
      if (!empty($p['check_items_id'])) {
         $p['extraparams']['check_items_id'] = $p['check_items_id'];
      }
      if (is_array($p['specific_actions']) && count($p['specific_actions'])) {
         $p['extraparams']['specific_actions'] = $p['specific_actions'];
      }
      if (is_array($p['add_actions']) && count($p['add_actions'])) {
         $p['extraparams']['add_actions'] = $p['add_actions'];
      }
      if ($p['item'] instanceof CommonDBTM) {
         $p['extraparams']['item_itemtype'] = $p['item']->getType();
         $p['extraparams']['item_items_id'] = $p['item']->getID();
      }

      // Manage modal window
      if (isset($_REQUEST['_is_modal']) && $_REQUEST['_is_modal']) {
         $p['extraparams']['hidden']['_is_modal'] = 1;
      }


      if ($p['fixed']) {
         $width= '950px';
      } else {
         $width= '95%';
      }

      $identifier = md5($url.serialize($p['extraparams']).$p['rand']);
      $max        = Toolbox::get_max_input_vars();

      if (($p['num_displayed'] >= 0)
          && ($max > 0)
          && ($max < ($p['num_displayed']+10))) {
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            echo "<table class='tab_cadre' width='$width'><tr class='tab_bg_1'>".
                  "<td><span class='b'>";
            echo __('Selection too large, massive action disabled.')."</span>";
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               echo "<br>".__('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
            }
            echo "</td></tr></table>";
         }
      } else {
         // Create Modal window on top
         if ($p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            echo "<div id='massiveactioncontent$identifier'></div>";

            if (!empty($p['tag_to_send'])) {
               $js_modal_fields  = "            var items = $('";
               if (!empty($p['container'])) {
                  $js_modal_fields .= '[id='.$p['container'].'] ';
               }
               $js_modal_fields .= "[data-glpicore-ma-tags~=".$p['tag_to_send']."]')";
               $js_modal_fields .= ".each(function( index ) {\n";
               $js_modal_fields .= "              fields[$(this).attr('name')] = $(this).attr('value');\n";
               $js_modal_fields .= "              if (($(this).attr('type') == 'checkbox') && (!$(this).is(':checked'))) {\n";
               $js_modal_fields .= "                 fields[$(this).attr('name')] = 0;\n";
               $js_modal_fields .= "              }\n";
               $js_modal_fields .= "            });";
            } else {
               $js_modal_fields = "";
            }

            Ajax::createModalWindow('massiveaction_window'.$identifier,
                                    $url,
                                    array('title'           => $p['title'],
                                          'container'       => 'massiveactioncontent'.$identifier,
                                          'extraparams'     => $p['extraparams'],
                                          'width'           => $p['width'],
                                          'height'          => $p['height'],
                                          'js_modal_fields' => $js_modal_fields));
         }
         echo "<table class='tab_glpi' width='$width'><tr>";
         if ($p['display_arrow']) {
            echo "<td width='30px'><img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left".
                   ($p['ontop']?'-top':'').".png' alt=''></td>";
         }
         echo "<td width='100%' class='left'>";
         echo "<a class='vsubmit' ";
         if (is_array($p['confirm'] || strlen($p['confirm']))) {
            echo self::addConfirmationOnAction($p['confirm'], "massiveaction_window$identifier.dialog(\"open\");");
         }  else {
            echo "onclick='massiveaction_window$identifier.dialog(\"open\");'";
         }
         echo " href='#modal_massaction_content$identifier' title=\"".htmlentities($p['title'], ENT_QUOTES, 'UTF-8')."\">";
         echo $p['title']."</a>";
         echo "</td>";

         echo "</tr></table>";
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            // Clean selection
            $_SESSION['glpimassiveactionselected'] = array();
         }
      }
   }


   /**
    * Display Date form with calendar
    *
    * @param $element      name of the element
    * @param $value        default value to display (default '')
    * @param $maybeempty   may be empty ? (true by default)
    * @param $can_edit     could not modify element (true by default)
    * @param $minDate      minimum allowed date (default '')
    * @param $maxDate      maximum allowed date (default '')
    * @param $displayYear  should we set/diplay the year? (true by default)
    *
    * @return rand value used
    * \deprecated since 0.84 used Html::showDateField instead
   **/
   static function showDateFormItem($element, $value='', $maybeempty=true, $can_edit=true,
                                    $minDate='', $maxDate='', $displayYear=true) {
      return self::showDateField($element, array('value'       => $value,
                                                 'maybeempty'  => $maybeempty,
                                                 'canedit'     => $can_edit,
                                                 'min'         => $minDate,
                                                 'max'         => $maxDate,
                                                 'showyear' => $displayYear));
   }


   /**
    * Display Date form with calendar
    *
    * @since version 0.84
    *
    * @param $name      name of the element
    * @param $options  array of possible options:
    *      - value      : default value to display (default '')
    *      - maybeempty : may be empty ? (true by default)
    *      - canedit    :  could not modify element (true by default)
    *      - min        :  minimum allowed date (default '')
    *      - max        : maximum allowed date (default '')
    *      - showyear   : should we set/diplay the year? (true by default)
    *      - display    : boolean display of return string (default true)
    *      - rand       : specific rand value (default generated one)
    *
    * @return rand value used if displayes else string
   **/
   static function showDateField($name, $options=array()) {
      global $CFG_GLPI;

      $p['value']      = '';
      $p['maybeempty'] = true;
      $p['canedit']    = true;
      $p['min']        = '';
      $p['max']        = '';
      $p['showyear']   = true;
      $p['display']    = true;
      $p['rand']       = mt_rand();

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }
      $output = "<input id='showdate".$p['rand']."' type='text' size='10' name='_$name' ".
                  "value='".self::convDate($p['value'])."'>";
      $output .= Html::hidden($name, array('value' => $p['value'],
                                           'id'    => "hiddendate".$p['rand'],
                                           'size'  => 10));
      if ($p['maybeempty'] && $p['canedit']) {
         $output .= "<img src='".$CFG_GLPI['root_doc']."/pics/reset.png' alt=\"".__('Clear').
                      "\" id='resetdate".$p['rand']."' class='pointer'>";
      }

      $js = '';
      if ($p['maybeempty'] && $p['canedit']) {
         $js .= "$('#resetdate".$p['rand']."').click(function(){
                  $('#showdate".$p['rand']."').val('');
                  $('#hiddendate".$p['rand']."').val('');
                  });";
      }
      $js .= "$( '#showdate".$p['rand']."' ).datepicker({
                  altField: '#hiddendate".$p['rand']."',
                  altFormat: 'yy-mm-dd',
                  firstDay: 1,
                  showOtherMonths: true,
                  selectOtherMonths: true,
                  showButtonPanel: true,
                  changeMonth: true,
                  changeYear: true,
                  showOn: 'button',
                  showWeek: true,
                  buttonImage: '".$CFG_GLPI['root_doc']."/pics/calendar.png',
                  buttonImageOnly: true  ";

      if (!$p['canedit']) {
         $js .= ",disabled: true";
      }

      if (!empty($p['min'])) {
         $js .= ",minDate: '".self::convDate($p['min'])."'";
      }

      if (!empty($p['max'])) {
         $js .= ",maxDate: '".self::convDate($p['max'])."'";
      }

      switch ($_SESSION['glpidate_format']) {
         case 1 :
            $p['showyear'] ? $format='dd-mm-yy' : $format='dd-mm';
            break;

         case 2 :
            $p['showyear'] ? $format='mm-dd-yy' : $format='mm-dd';
            break;

         default :
            $p['showyear'] ? $format='yy-mm-dd' : $format='mm-dd';
      }
      $js .= ",dateFormat: '".$format."'";

      $js .= "});";
      $output .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }


   /**
    * Display Color field
    *
    * @since version 0.85
    *
    * @param $name            name of the element
    * @param $options  array  of possible options:
    *   - value      : default value to display (default '')
    *   - display    : boolean display or get string (default true)
    *   - rand       : specific random value (default generated one)
   **/
   static function showColorField($name, $options=array()) {

      $p['value']      = '';
      $p['rand']       = mt_rand();
      $p['display']    = true;
      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }
      $field_id = Html::cleanId("color_".$name.$p['rand']);
      $output   = "<input type='text' id='$field_id' name='$name' value='".$p['value']."'>";
      $js       = "$('#$field_id').spectrum({preferredFormat: 'hex'});";
      $output  .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }


   /**
    * Display DateTime form with calendar
    *
    * @param $element      name of the element
    * @param $value        default value to display (default '')
    * @param $time_step    step for time in minute (-1 use default config) (default -1)
    * @param $maybeempty   may be empty ? (true by default)
    * @param $can_edit     could not modify element (true by default)
    * @param $minDate      minimum allowed date (default '')
    * @param $maxDate      maximum allowed date (default '')
    * @param $minTime      minimum allowed time (default '')
    * @param $maxTime      maximum allowed time (default '')
    *
    * @return rand value used
    * \deprecated since 0.84 used Html::showDateTimeField instead
   **/
   static function showDateTimeFormItem($element, $value='', $time_step=-1, $maybeempty=true,
                                        $can_edit=true, $minDate='', $maxDate='', $minTime='',
                                        $maxTime='') {

      return self::showDateTimeField($element, array('value'      => $value,
                                                     'timestep'   => $time_step,
                                                     'maybeempty' => $maybeempty,
                                                     'canedit'    => $can_edit,
                                                     'mindate'    => $minDate,
                                                     'maxdate'    => $maxDate,
                                                     'mintime'    => $minTime,
                                                     'maxtime'    => $maxTime));
   }



   /**
    * Display DateTime form with calendar
    *
    * @since version 0.84
    *
    * @param $name            name of the element
    * @param $options  array  of possible options:
    *   - value      : default value to display (default '')
    *   - timestep   : step for time in minute (-1 use default config) (default -1)
    *   - maybeempty : may be empty ? (true by default)
    *   - canedit    : could not modify element (true by default)
    *   - mindate    : minimum allowed date (default '')
    *   - maxdate    : maximum allowed date (default '')
    *   - mintime    : minimum allowed time (default '')
    *   - maxtime    : maximum allowed time (default '')
    *   - showyear   : should we set/diplay the year? (true by default)
    *   - display    : boolean display or get string (default true)
    *   - rand       : specific random value (default generated one)
    *
    * @return rand value used if displayes else string
   **/
   static function showDateTimeField($name, $options = array()) {
      global $CFG_GLPI;

      $p['value']      = '';
      $p['maybeempty'] = true;
      $p['canedit']    = true;
      $p['mindate']    = '';
      $p['maxdate']    = '';
      $p['mintime']    = '';
      $p['maxtime']    = '';
      $p['timestep']   = -1;
      $p['showyear']   = true;
      $p['display']    = true;
      $p['rand']       = mt_rand();

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      if ($p['timestep'] < 0) {
         $p['timestep'] = $CFG_GLPI['time_step'];
      }

      $minHour   = 0;
      $maxHour   = 23;
      $minMinute = 0;
      $maxMinute = 59;

      $date_value = '';
      $hour_value = '';
      if (!empty($p['value'])) {
         list($date_value, $hour_value) = explode(' ', $p['value']);
      }

      if (!empty($p['mintime'])) {
         list($minHour, $minMinute) = explode(':', $p['mintime']);
         $minMinute = 0;

         // Check time in interval
         if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
            $hour_value = $p['mintime'];
         }
      }

      if (!empty($p['maxtime'])) {
         list($maxHour, $maxMinute) = explode(':', $p['maxtime']);
         $maxMinute = 59;

         // Check time in interval
         if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
            $hour_value = $p['maxtime'];
         }
      }

      // reconstruct value to be valid
      if (!empty($date_value)) {
         $p['value'] = $date_value.' '.$hour_value;
      }

      $output  = "<input id='showdate".$p['rand']."' type='text' name='_$name' value='".
                   self::convDateTime($p['value'])."'>";
      $output .= Html::hidden($name, array('value' => $p['value'], 'id' => "hiddendate".$p['rand']));
      if ($p['maybeempty'] && $p['canedit']) {
         $output .= "<img src='".$CFG_GLPI['root_doc']."/pics/reset.png' alt=\"".__('Clear').
                      "\" id='resetdate".$p['rand']."' class='pointer'>";
      }

      $js = "";
      if ($p['maybeempty'] && $p['canedit']) {
         $js .= "$('#resetdate".$p['rand']."').click(function(){
                  $('#showdate".$p['rand']."').val('');
                  $('#hiddendate".$p['rand']."').val('');
                  });";
      }

      $js .= "$( '#showdate".$p['rand']."' ).datetimepicker({
                  altField: '#hiddendate".$p['rand']."',
                  altFormat: 'yy-mm-dd',
                  altTimeFormat: 'HH:mm',
                  pickerTimeFormat : 'HH:mm',
                  altFieldTimeOnly: false,
                  firstDay: 1,
                  parse: 'loose',
                  showAnim: '',
                  stepMinute: ".$p['timestep'].",
                  showSecond: false,
                  showOtherMonths: true,
                  selectOtherMonths: true,
                  showButtonPanel: true,
                  changeMonth: true,
                  changeYear: true,
                  showOn: 'button',
                  showWeek: true,
                  controlType: 'select',
                  buttonImage: '".$CFG_GLPI['root_doc']."/pics/calendar.png',
                  buttonImageOnly: true";
      if (!$p['canedit']) {
         $js .= ",disabled: true";
      }

      if (!empty($p['min'])) {
         $js .= ",minDate: '".self::convDate($p['min'])."'";
      }

      if (!empty($p['max'])) {
         $js .= ",maxDate: '".self::convDate($p['max'])."'";
      }

      switch ($_SESSION['glpidate_format']) {
         case 1 :
            $p['showyear'] ? $format='dd-mm-yy' : $format='dd-mm';
            break;

         case 2 :
            $p['showyear'] ? $format='mm-dd-yy' : $format='mm-dd';
            break;

         default :
            $p['showyear'] ? $format='yy-mm-dd' : $format='mm-dd';
      }
      $js .= ",dateFormat: '".$format."'";
      $js .= ",timeFormat: 'HH:mm'";

      $js .= "});";

      $output .= Html::scriptBlock($js);


      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }

   /**
    * Show generic date search
    *
    * @param $element         name of the html element
    * @param $value           default value (default '')
    * @param $options   array of possible options:
    *      - with_time display with time selection ? (default false)
    *      - with_future display with future date selection ? (default false)
    *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
    *
    * @return rand value of dropdown
   **/
   static function showGenericDateTimeSearch($element, $value='', $options=array()) {
      global $CFG_GLPI;

      $p['with_time']          = false;
      $p['with_future']        = false;
      $p['with_days']          = true;
      $p['with_specific_date'] = true;
      $p['display']            = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $rand   = mt_rand();
      $output = '';
      // Validate value
      if (($value != 'NOW')
          && ($value != 'TODAY')
          && !preg_match("/\d{4}-\d{2}-\d{2}.*/",$value)
          && !strstr($value,'HOUR')
          && !strstr($value,'DAY')
          && !strstr($value,'WEEK')
          && !strstr($value,'MONTH')
          && !strstr($value,'YEAR')) {

         $value = "";
      }

      if (empty($value)) {
         $value = 'NOW';
      }
      $specific_value = date("Y-m-d H:i:s");

      if (preg_match("/\d{4}-\d{2}-\d{2}.*/",$value)) {
         $specific_value = $value;
         $value          = 0;
      }
      $output    .= "<table width='100%'><tr><td width='50%'>";

      $dates      = Html::getGenericDateTimeSearchItems($p);

      $output    .= Dropdown::showFromArray("_select_$element", $dates,
                                                  array('value'   => $value,
                                                        'display' => false,
                                                        'rand'    => $rand));
      $field_id   = Html::cleanId("dropdown__select_$element$rand");

      $output    .= "</td><td width='50%'>";
      $contentid  = Html::cleanId("displaygenericdate$element$rand");
      $output    .= "<span id='$contentid'></span>";

      $params     = array('value'         => '__VALUE__',
                          'name'          => $element,
                          'withtime'      => $p['with_time'],
                          'specificvalue' => $specific_value);

      $output    .= Ajax::updateItemOnSelectEvent($field_id, $contentid,
                                                  $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                                                  $params, false);
      $params['value']  = $value;
      $output    .= Ajax::updateItem($contentid, $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                                           $params, '', false);
      $output    .= "</td></tr></table>";

      if ($p['display']) {
         echo $output;
         return $rand;
      }
      return $output;
   }


   /**
    * Get items to display for showGenericDateTimeSearch
    *
    * @since version 0.83
    *
    * @param $options   array of possible options:
    *      - with_time display with time selection ? (default false)
    *      - with_future display with future date selection ? (default false)
    *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
    *
    * @return array of posible values
    * @see showGenericDateTimeSearch
   **/
   static function getGenericDateTimeSearchItems($options) {

      $params['with_time']          = false;
      $params['with_future']        = false;
      $params['with_days']          = true;
      $params['with_specific_date'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $dates = array();
      if ($params['with_time']) {
         $dates['NOW'] = __('Now');
         if ($params['with_days']) {
            $dates['TODAY'] = __('Today');
         }
      } else {
         $dates['NOW'] = __('Today');
      }

      if ($params['with_specific_date']) {
         $dates[0] = __('Specify a date');
      }

      if ($params['with_time']) {
         for ($i=1 ; $i<=24 ; $i++) {
            $dates['-'.$i.'HOUR'] = sprintf(_n('- %d hour', '- %d hours', $i), $i);
         }
      }

      for ($i=1 ; $i<=7 ; $i++) {
         $dates['-'.$i.'DAY'] = sprintf(_n('- %d day', '- %d days', $i), $i);
      }

      if ($params['with_days']) {
         $dates['LASTSUNDAY']    = __('last Sunday');
         $dates['LASTMONDAY']    = __('last Monday');
         $dates['LASTTUESDAY']   = __('last Tuesday');
         $dates['LASTWEDNESDAY'] = __('last Wednesday');
         $dates['LASTTHURSDAY']  = __('last Thursday');
         $dates['LASTFRIDAY']    = __('last Friday');
         $dates['LASTSATURDAY']  = __('last Saturday');
      }

      for ($i=1 ; $i<=10 ; $i++) {
         $dates['-'.$i.'WEEK'] = sprintf(_n('- %d week', '- %d weeks', $i), $i);
      }

      if ($params['with_days']) {
         $dates['BEGINMONTH']  = __('Beginning of the month');
      }

      for ($i=1 ; $i<=12 ; $i++) {
         $dates['-'.$i.'MONTH'] = sprintf(_n('- %d month', '- %d months', $i), $i);
      }

      if ($params['with_days']) {
         $dates['BEGINYEAR']  = __('Beginning of the year');
      }

      for ($i=1 ; $i<=10 ; $i++) {
         $dates['-'.$i.'YEAR'] = sprintf(_n('- %d year', '- %d years', $i), $i);
      }

      if ($params['with_future']) {
         if ($params['with_time']) {
            for ($i=1 ; $i<=24 ; $i++) {
               $dates[$i.'HOUR'] = sprintf(_n('+ %d hour', '+ %d hours', $i), $i);
            }
         }

         for ($i=1 ; $i<=7 ; $i++) {
            $dates[$i.'DAY'] = sprintf(_n('+ %d day', '+ %d days', $i), $i);
         }

         for ($i=1 ; $i<=10 ; $i++) {
            $dates[$i.'WEEK'] = sprintf(_n('+ %d week', '+ %d weeks', $i), $i);
         }

         for ($i=1 ; $i<=12 ; $i++) {
            $dates[$i.'MONTH'] = sprintf(_n('+ %d month', '+ %d months', $i), $i);
         }

         for ($i=1 ; $i<=10 ; $i++) {
            $dates[$i.'YEAR'] = sprintf(_n('+ %d year', '+ %d years', $i), $i);
         }
      }
      return $dates;

   }


    /**
    * Compute date / datetime value resulting of showGenericDateTimeSearch
    *
    * @since version 0.83
    *
    * @param $val          date / datetime   value passed
    * @param $force_day    boolean           force computation in days (false by default)
    * @param $specifictime timestamp         set specific timestamp (default '')
    *
    * @return computed date / datetime value
    * @see showGenericDateTimeSearch
   **/
   static function computeGenericDateTimeSearch($val, $force_day=false, $specifictime='') {

      if (empty($specifictime)) {
         $specifictime = strtotime($_SESSION["glpi_currenttime"]);
      }

      $format_use = "Y-m-d H:i:s";
      if ($force_day) {
         $format_use = "Y-m-d";
      }

      // Parsing relative date
      switch ($val) {
         case 'NOW' :
            return date($format_use, $specifictime);

         case 'TODAY' :
            return date("Y-m-d", $specifictime);
      }

      // Search on begin of month / year
      if (strstr($val,'BEGIN')) {
         $hour   = 0;
         $minute = 0;
         $second = 0;
         $month  = date("n", $specifictime);
         $day    = 1;
         $year   = date("Y", $specifictime);

         switch ($val) {
               case "BEGINYEAR":
                  $month = 1;
                  break;

               case "BEGINMONTH":
                  break;
         }

         return date($format_use, mktime ($hour, $minute, $second, $month, $day, $year));
      }

      // Search on Last monday, sunday...
      if (strstr($val,'LAST')) {
         $lastday = str_replace("LAST", "LAST ", $val);
         $hour   = 0;
         $minute = 0;
         $second = 0;
         $month  = date("n", strtotime($lastday));
         $day    = date("j", strtotime($lastday));
         $year   = date("Y", strtotime($lastday));

         return date($format_use, mktime ($hour, $minute, $second, $month, $day, $year));
      }

      // Search on +- x days, hours...
      if (preg_match("/^(-?)(\d+)(\w+)$/",$val,$matches)) {
         if (in_array($matches[3], array('YEAR', 'MONTH', 'WEEK', 'DAY', 'HOUR'))) {
            $nb = intval($matches[2]);
            if ($matches[1] == '-') {
               $nb = -$nb;
            }
            // Use it to have a clean delay computation (MONTH / YEAR have not always the same duration)
            $hour   = date("H", $specifictime);
            $minute = date("i", $specifictime);
            $second = 0;
            $month  = date("n", $specifictime);
            $day    = date("j", $specifictime);
            $year   = date("Y", $specifictime);

            switch ($matches[3]) {
               case "YEAR" :
                  $year += $nb;
                  break;

               case "MONTH" :
                  $month += $nb;
                  break;

               case "WEEK" :
                  $day += 7*$nb;
                  break;

               case "DAY" :
                  $day += $nb;
                  break;

               case "HOUR" :
                  $format_use = "Y-m-d H:i:s";
                  $hour      += $nb;
                  break;
            }
            return date($format_use, mktime ($hour, $minute, $second, $month, $day, $year));
         }
      }
      return $val;
   }


   /**
    * Print the form used to select profile if several are available
    *
    * @param $target target of the form
    *
    * @return nothing
   **/
   static function showProfileSelecter($target) {
      global $CFG_GLPI;

      if (count($_SESSION["glpiprofiles"])>1) {
         echo '<li class="profile-selector"><form name="form" method="post" action="'.$target.'">';
         $values = array();
         foreach ($_SESSION["glpiprofiles"] as $key => $val) {
            $values[$key] = $val['name'];
         }

         Dropdown::showFromArray('newprofile',$values,
                                 array('value'     => $_SESSION["glpiactiveprofile"]["id"],
                                       'width'     => '150px',
                                       'on_change' => 'submit()'));
         Html::closeForm();
         echo '</li>';
      }

      if (Session::isMultiEntitiesMode()) {
         echo "<li class='profile-selector'>";
         Ajax::createModalWindow('entity_window', $CFG_GLPI['root_doc']."/ajax/entitytree.php",
                                 array('title'       => __('Select the desired entity'),
                                       'extraparams' => array('target' => $target)));
         echo "<a onclick='entity_window.dialog(\"open\");' href='#modal_entity_content' title=\"".
                addslashes($_SESSION["glpiactive_entity_name"]).
                "\" class='entity_select' id='global_entity_select'>".
                $_SESSION["glpiactive_entity_shortname"]."</a>";

         echo "</li>";
      }
   }


   /**
    * Show a tooltip on an item
    *
    * @param $content   string   data to put in the tooltip
    * @param $options   array    of possible options:
    *   - applyto : string / id of the item to apply tooltip (default empty).
    *                  If not set display an icon
    *   - title : string / title to display (default empty)
    *   - contentid : string / id for the content html container (default auto generated) (used for ajax)
    *   - link : string / link to put on displayed image if contentid is empty
    *   - linkid : string / html id to put to the link link (used for ajax)
    *   - linktarget : string / target for the link
    *   - popup : string / popup action : link not needed to use it
    *   - img : string / url of a specific img to use
    *   - display : boolean / display the item : false return the datas
    *   - autoclose : boolean / autoclose the item : default true (false permit to scroll)
    *
    * @return nothing (print out an HTML div)
   **/
   static function showToolTip($content, $options=array()) {
      global $CFG_GLPI;

      $param['applyto']    = '';
      $param['title']      = '';
      $param['contentid']  = '';
      $param['link']       = '';
      $param['linkid']     = '';
      $param['linktarget'] = '';
      $param['img']        = $CFG_GLPI["root_doc"]."/pics/info-small.png";
      $param['popup']      = '';
      $param['ajax']       = '';
      $param['display']    = true;
      $param['autoclose']  = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // No empty content to have a clean display
      if (empty($content)) {
         $content = "&nbsp;";
      }
      $rand = mt_rand();
      $out  = '';

      // Force link for popup
      if (!empty($param['popup'])) {
         $param['link'] = '#';
      }

      if (empty($param['applyto'])) {
         if (!empty($param['link'])) {
            $out .= "<a id='".(!empty($param['linkid'])?$param['linkid']:"tooltiplink$rand")."'";

            if (!empty($param['linktarget'])) {
               $out .= " target='".$param['linktarget']."' ";
            }
            $out .= " href='".$param['link']."'";

            if (!empty($param['popup'])) {
               $out .= " onClick=\"".Html::jsGetElementbyID('tooltippopup'.$rand).".dialog('open');\" ";
            }
            $out .= '>';
         }
         $out .= "<img id='tooltip$rand' alt='ffff' src='".$param['img']."' class='pointer'>";

         if (!empty($param['link'])) {
            $out .= "</a>";
         }

         $param['applyto'] = "tooltip$rand";
      }

      if (empty($param['contentid'])) {
         $param['contentid'] = "content".$param['applyto'];
      }

      $out .= "<div id='".$param['contentid']."' class='invisible'>$content</div>";
      if (!empty($param['popup'])) {
         $out .= Ajax::createIframeModalWindow('tooltippopup'.$rand,
                                               $param['popup'],
                                               array('display' => false,
                                                     'width'   => 600,
                                                     'height'  => 300));
      }
      $js = "";
      $js .= Html::jsGetElementbyID($param['applyto']).".qtip({
         position: { viewport: $(window) },
         content: {text: ".Html::jsGetElementbyID($param['contentid']);
         if (!$param['autoclose']) {
            $js .=", title: {text: ' ',button: true}";
         }
      $js .= "}, style: { classes: 'qtip-shadow qtip-bootstrap'}";
      if (!$param['autoclose']) {
         $js .= ",show: {
                        solo: true, // ...and hide all other tooltips...
                }, hide: false,";
      }
      $js .= "});";
      $out .= Html::scriptBlock($js);

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }


    /**
    * Show div with auto completion
    *
    * @param $item            item object used for create dropdown
    * @param $field           field to search for autocompletion
    * @param $options   array of possible options:
    *    - name    : string / name of the select (default is field parameter)
    *    - value   : integer / preselected value (default value of the item object)
    *    - size    : integer / size of the text field
    *    - entity  : integer / restrict to a defined entity (default entity of the object if define)
    *                set to -1 not to take into account
    *    - user    : integer / restrict to a defined user (default -1 : no restriction)
    *    - option  : string / options to add to text field
    *    - display : boolean / if false get string
    *
    * @return nothing (print out an HTML div)
   **/
   static function autocompletionTextField(CommonDBTM $item, $field, $options=array()) {
      global $CFG_GLPI;

      $params['name']   = $field;
      $params['value']  = '';

      if (array_key_exists($field,$item->fields)) {
         $params['value'] = $item->fields[$field];
      }
      $params['size']   = 40;
      $params['entity'] = -1;

      if (array_key_exists('entities_id',$item->fields)) {
         $params['entity'] = $item->fields['entities_id'];
      }
      $params['user']   = -1;
      $params['option'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      $output = '';
      if ($CFG_GLPI["use_ajax_autocompletion"]) {
         $rand    = mt_rand();
         $name    = "field_".$params['name'].$rand;
         $output .=  "<input ".$params['option']." id='text$name' type='text' name='".
                       $params['name']."' value=\"".self::cleanInputText($params['value']).
                       "\" size='".$params['size']."'>\n";

         $parameters['itemtype'] = $item->getType();
         $parameters['field']    = $field;

               if ($params['entity'] >= 0) {
                  $parameters['entity_restrict']    = $params['entity'];
               }
               if ($params['user'] >= 0) {
                  $parameters['user_restrict']    = $params['user'];
               }

         $js = "  $( '#text$name' ).autocomplete({
                        source: '".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php?".Toolbox::append_params($parameters,'&')."',
                        minLength: 3,
                        });";

         $output .= Html::scriptBlock($js);

      } else {
         $output .=  "<input ".$params['option']." type='text' name='".$params['name']."'
                value=\"".self::cleanInputText($params['value'])."\" size='".$params['size']."'>\n";
      }

      if (!isset($options['display']) || $options['display']) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Init the Editor System to a textarea
    *
    * @param $name               name of the html textarea to use
    * @param $rand       rand    of the html textarea to use (if empty no image paste system)(default '')
    * @param $display    boolean display or get js script (true by default)
    *
    * @return nothing
   **/
   static function initEditorSystem($name, $rand='', $display=true) {
      global $CFG_GLPI;

      Html::scriptStart();
      $js = "function waitforpastedata(elem){
         var _html = elem.innerHTML;
         if(_html != undefined) {
            if (_html.match(/<img[^>]+src=\"data:image.*?;base64[^>]*?>/g)){
               _html = _html.replace(/<img[^>]+src=\"data:image.*?;base64[^>]*?>/g, '');
               tinyMCE.activeEditor.setContent(_html);
            } else {
               that = {
                  e: elem
               }
               that.callself = function () {
                  waitforpastedata(that.e)
               }
               setTimeout(that.callself,20);
            }
         }
      }

      tinyMCE.init({
         language : '".$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]."',
         mode : 'exact',
         browser_spellcheck : true,
         elements: '$name',
         valid_elements: '*[*]',
         plugins : 'table,directionality,searchreplace,paste,tabfocus,autoresize',
         paste_use_dialog : false,
         paste_auto_cleanup_on_paste : true,
         paste_convert_headers_to_strong : false,
         paste_strip_class_attributes : 'all',
         paste_remove_spans : true,
         paste_remove_styles : true,
         paste_retain_style_properties : '',
         paste_block_drop : true,
         paste_preprocess : function(pl, o) {
            _html = o.content;
            if (_html.match(/<img[^>]+src=\"data:image.*?;base64[^>]*?>/g)){
               _html = _html.replace(/<img[^>]+src=\"data:image.*?;base64[^>]*?>/g, '');
               o.content = _html;
            }
         },
         theme : 'advanced',
         entity_encoding : 'raw', 
         // directionality + search replace plugin
         theme_advanced_buttons1_add : 'ltr,rtl,search,replace',
         theme_advanced_toolbar_location : 'top',
         theme_advanced_toolbar_align : 'left',
         theme_advanced_statusbar_location : 'none',
         theme_advanced_resizing : 'true',
         theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent',
         theme_advanced_buttons2 : 'forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator',
         theme_advanced_buttons3 : '',
         setup : function(ed) {
         ed.onInit.add(function(ed) {
            // wake up the autoresize plugin
            setTimeout(
               function(){
                  ed.execCommand('mceAutoResize');
               }, 1);4204
               if (tinymce.isIE) {
                  tinymce.dom.Event.add(ed.getBody(), 'dragenter', function(e) {
                     return tinymce.dom.Event.cancel(e);
                  });
               } else {
                  tinymce.dom.Event.add(ed.getBody().parentNode, 'drop', function(e) {
                     tinymce.dom.Event.cancel(e);
                     tinymce.dom.Event.stop(e);
                  });
                  tinymce.dom.Event.add(ed.getBody().parentNode, 'paste', function(e) {
                     waitforpastedata(ed.getBody());
                  });
               }
            });
         }
      });
   ";

//         invalid_elements : 'script',
      if ($display) {
         echo  Html::scriptBlock($js);
      } else {
         return  Html::scriptBlock($js);
      }
   }

   /**
    * Init the Image paste System for tiny mce
    *
    * @since version 0.85
    *
    * @param $name          name of the html textarea to use
    * @param $rand          rand of the html textarea to use
    *
    * @return nothing
   **/
   static function initImagePasteSystem($name, $rand) {
      global $CFG_GLPI;
      
      echo Html::imagePaste(array('rand' => $rand));

      $params = array('name'         => $name,
                      'filename'     => self::generateImageName(),
                      'root_doc'     => $CFG_GLPI['root_doc'],
                      'rand'         => $rand,
                      'showfilesize' => 1,
                      'lang'         => array('pasteimage'   => _sx('button',
                                                                    'Drag and drop or paste image'),
                                              'itemnotfound' => __('Item not found'),
                                              'toolarge'     => __('Item is too large'),
                                              'save'         => _sx('button', 'Save'),
                                              'cancel'       => _sx('button', 'Cancel')));

      return html::scriptBlock("if (!tinyMCE.isIE) { // Chrome, Firefox plugin
                  tinyMCE.imagePaste = $(document).imagePaste(".json_encode($params).");
              } else { // IE plugin
                  tinyMCE.imagePaste = $(document).IE_support_imagePaste(".json_encode($params).");
              }
              uploadFile$rand();");
   }


   /**
    * Print Ajax pager for list in tab panel
    *
    * @param $title              displayed above
    * @param $start              from witch item we start
    * @param $numrows            total items
    * @param $additional_info    Additional information to display (default '')
    *
    * @return nothing (print a pager)
   **/
   static function printAjaxPager($title, $start, $numrows, $additional_info='') {
      global $CFG_GLPI;

      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      $forward = $start+$list_limit;

      // This is the end, my friend
      $end = $numrows-$list_limit;

      // Human readable count starts here
      $current_start = $start+1;

      // And the human is viewing from start to end
      $current_end = $current_start+$list_limit-1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }
      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }
      // Backward browsing
      if ($current_start-$list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start-$list_limit;
      }

      // Print it
      echo "<div><table class='tab_cadre_pager'>";
      if (!empty($title)) {
         echo "<tr><th colspan='6'>$title</th></tr>";
      }
      echo "<tr>\n";

      // Back and fast backward button
      if (!$start == 0) {
         echo "<th class='left'><a href='javascript:reloadTab(\"start=0\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".__s('Start').
                "\" title=\"".__s('Start')."\" class='pointer'></a></th>";
         echo "<th class='left'><a href='javascript:reloadTab(\"start=$back\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous').
                "\" title=\"".__s('Previous')."\" class='pointer'></th>";
      }

      echo "<td width='50%' class='tab_bg_2'>";
      self::printPagerForm();
      echo "</td>";
      if (!empty($additional_info)) {
         echo "<td class='tab_bg_2'>";
         echo $additional_info;
         echo "</td>";
      }
      // Print the "where am I?"
      echo "<td width='50%' class='tab_bg_2 b'>";
      //TRANS: %1$d, %2$d, %3$d are page numbers
      echo sprintf(__('From %1$d to %2$d on %3$d'), $current_start, $current_end, $numrows);
      echo "</td>\n";

      // Forward and fast forward button
      if ($forward < $numrows) {
         echo "<th class='right'><a href='javascript:reloadTab(\"start=$forward\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next').
                "\" title=\"".__s('Next')."\" class='pointer'></a></th>";
         echo "<th class='right'><a href='javascript:reloadTab(\"start=$end\");'>
               <img src='".$CFG_GLPI["root_doc"]."/pics/last.png' alt=\"".__s('End').
                "\" title=\"".__s('End')."\" class='pointer'></a></th>";
      }

      // End pager
      echo "</tr></table></div>";
   }


   /**
    * Clean Printing of and array in a table
    * ONLY FOR DEBUG
    *
    * @param $tab          the array to display
    * @param $pad          Pad used (default 0)
    * @param $jsexpand     Expand using JS ? (default  false)
    *
    * @return nothing
   **/
   static function printCleanArray($tab, $pad=0,$jsexpand=false) {

      if (count($tab)) {
         echo "<table class='tab_cadre'>";
         // For debug / no gettext
         echo "<tr><th>KEY</th><th>=></th><th>VALUE</th></tr>";

         foreach ($tab as $key => $val) {
            echo "<tr class='tab_bg_1'><td class='top right'>";
            echo $key;
            $is_array = is_array($val);
            $rand     = mt_rand();
            echo "</td><td class='top'>";
            if ($jsexpand && $is_array) {
               echo "<a class='pointer' href=\"javascript:showHideDiv('content$key$rand','','','')\">";
               echo "=></a>";
            } else {
               echo "=>";
            }
            echo "</td><td class='top tab_bg_1'>";

            if ($is_array) {
               echo "<div id='content$key$rand' ".($jsexpand?"style=\"display:none;\"":'').">";
               self::printCleanArray($val,$pad+1);
               echo "</div>";
            } else {
               if (is_bool($val)) {
                  if ($val) {
                     echo 'true';
                  } else {
                     echo 'false';
                  }
               } else {
                  if (is_object($val)) {
                     print_r($val);
                  } else {
                     echo htmlentities($val);
                  }
               }
            }
            echo "</td></tr>";
         }
         echo "</table>";
      } else {
         _e('Empty array');
      }
   }



   /**
    * Print pager for search option (first/previous/next/last)
    *
    * @param $start                       from witch item we start
    * @param $numrows                     total items
    * @param $target                      page would be open when click on the option (last,previous etc)
    * @param $parameters                  parameters would be passed on the URL.
    * @param $item_type_output            item type display - if >0 display export PDF et Sylk form
    *                                     (default 0)
    * @param $item_type_output_param      item type parameter for export (default 0)
    * @param $additional_info             Additional information to display (default '')
    *
    * @return nothing (print a pager)
    *
   **/
   static function printPager($start, $numrows, $target, $parameters, $item_type_output=0,
                              $item_type_output_param=0, $additional_info='') {
      global $CFG_GLPI;

      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      $forward = $start+$list_limit;

      // This is the end, my friend
      $end = $numrows-$list_limit;

      // Human readable count starts here

      $current_start = $start+1;

      // And the human is viewing from start to end
      $current_end = $current_start+$list_limit-1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }

      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }

      // Backward browsing
      if ($current_start-$list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start-$list_limit;
      }

      // Print it
      echo "<div><table class='tab_cadre_pager'>";
      echo "<tr>";

      if (strpos($target, '?') == false) {
         $fulltarget = $target."?".$parameters;
      } else {
         $fulltarget = $target."&".$parameters;
      }
      // Back and fast backward button
      if (!$start == 0) {
         echo "<th class='left'>";
         echo "<a href='$fulltarget&amp;start=0'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".__s('Start').
               "\" title=\"".__s('Start')."\" class='pointer'>";
         echo "</a></th>";
         echo "<th class='left'>";
         echo "<a href='$fulltarget&amp;start=$back'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous').
               "\" title=\"".__s('Previous')."\" class='pointer'>";
         echo "</a></th>";
      }

      // Print the "where am I?"
      echo "<td width='31%' class='tab_bg_2'>";
      self::printPagerForm("$fulltarget&amp;start=$start");
      echo "</td>";

      if (!empty($additional_info)) {
         echo "<td class='tab_bg_2'>";
         echo $additional_info;
         echo "</td>";
      }

      if (!empty($item_type_output)
          && isset($_SESSION["glpiactiveprofile"])
          && ($_SESSION["glpiactiveprofile"]["interface"] == "central")) {

         echo "<td class='tab_bg_2 responsive_hidden' width='30%'>";
         echo "<form method='GET' action='".$CFG_GLPI["root_doc"]."/front/report.dynamic.php'
                target='_blank'>";
         echo Html::hidden('item_type', array('value' => $item_type_output));

         if ($item_type_output_param != 0) {
            echo Html::hidden('item_type_param',
                              array('value' => Toolbox::prepareArrayForInput($item_type_output_param)));
         }
         $split = explode("&amp;",$parameters);

         for ($i=0 ; $i<count($split) ; $i++) {
            $pos    = Toolbox::strpos($split[$i], '=');
            $length = Toolbox::strlen($split[$i]);
            echo Html::hidden(Toolbox::substr($split[$i],0,$pos), array('value' => urldecode(Toolbox::substr($split[$i], $pos+1))));
         }

         Dropdown::showOutputFormat();
         Html::closeForm();
         echo "</td>" ;
      }

      echo "<td width='20%' class='tab_bg_2 b'>";
      //TRANS: %1$d, %2$d, %3$d are page numbers
      printf(__('From %1$d to %2$d on %3$d'), $current_start, $current_end, $numrows);
      echo "</td>\n";

      // Forward and fast forward button
      if ($forward<$numrows) {
         echo "<th class='right'>";
         echo "<a href='$fulltarget&amp;start=$forward'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next').
               "\" title=\"".__s('Next')."\" class='pointer'>";
         echo "</a></th>\n";

         echo "<th class='right'>";
         echo "<a href='$fulltarget&amp;start=$end'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/last.png' alt=\"".__s('End').
                "\" title=\"".__s('End')."\" class='pointer'>";
         echo "</a></th>\n";
      }
      // End pager
      echo "</tr></table></div>";
   }


   /**
    * Display the list_limit combo choice
    *
    * @param $action page would be posted when change the value (URL + param) (default '')
    *
    * ajax Pager will be displayed if empty
    *
    * @return nothing (print a combo)
   **/
   static function printPagerForm($action="") {

      if ($action) {
         echo "<form method='POST' action=\"$action\">";
         echo "<span class='responsive_hidden'>".__('Display (number of items)')."</span>&nbsp;";
         Dropdown::showListLimit("submit()");

      } else {
         echo "<form method='POST' action =''>\n";
         echo "<span class='responsive_hidden'>".__('Display (number of items)')."</span>&nbsp;";
         Dropdown::showListLimit("reloadTab(\"glpilist_limit=\"+this.value)");
      }
      Html::closeForm();
   }


   /**
    * Create a title for list, as  "List (5 on 35)"
    *
    * @param $string String  text for title
    * @param $num    Integer number of item displayed
    * @param $tot    Integer number of item existing
    *
    * @since version 0.83.1
    *
    * @return String
    **/
   static function makeTitle ($string, $num, $tot) {

      if (($num > 0) && ($num < $tot)) {
         // TRANS %1$d %2$d are numbers (displayed, total)
         $cpt = sprintf(__('%1$d on %2$d'), $num, $tot);
      } else  {
         // $num is 0, so means configured to display nothing
         // or $num == $tot
         $cpt = $tot;
      }
      return sprintf(__('%1$s (%2$s)'), $string, $cpt);
   }


   /**
    * create a minimal form for simple action
    *
    * @param $action   String   URL to call on submit
    * @param $btname   String   button name (maybe if name <> value)
    * @param $btlabel  String   button label
    * @param $fields   Array    field name => field  value
    * @param $btimage  String   button image uri (optional)   (default '')
    * @param $btoption String   optional button option        (default '')
    * @param $confirm  String   optional confirm message      (default '')
    *
    * @since version 0.84
   **/
   static function getSimpleForm($action, $btname, $btlabel, Array $fields=array(), $btimage='',
                                 $btoption='', $confirm='') {

      if (GLPI_USE_CSRF_CHECK) {
         $fields['_glpi_csrf_token'] = Session::getNewCSRFToken();
      }
      $fields['_glpi_simple_form'] = 1;
      $button                      = $btname;
      if (!is_array($btname)) {
         $button          = array();
         $button[$btname] = $btname;
      }
      $fields          = array_merge($button, $fields);
      $javascriptArray = array();
      foreach ($fields as $name => $value) {
         /// TODO : trouble :  urlencode not available for array / do not pass array fields...
         if (!is_array($value)) {
            // Javascript no gettext
            $javascriptArray[] = "'$name': '".urlencode($value)."'";
         }
      }

      $link = "<a ";

      if (!empty($btoption)) {
         $link .= ' '.$btoption.' ';
      }
      // Do not force class if already defined
      if (!strstr($btoption, 'class=')) {
         if (empty($btimage)) {
            $link .= " class='vsubmit' ";
         } else {
            $link .= " class='pointer' ";
         }
      }
      $btlabel = htmlentities($btlabel, ENT_QUOTES, 'UTF-8');
      $action  = " submitGetLink('$action', {" .implode(', ', $javascriptArray) ."});";

      if (is_array($confirm) || strlen($confirm)) {
         $link .= self::addConfirmationOnAction($confirm, $action);
      }  else {
         $link .= " onclick=\"$action\" ";
      }

      $link .= '>';
      if (empty($btimage)) {
         $link .= $btlabel;
      } else {
         $link .= "<img src='$btimage' title='$btlabel' alt='$btlabel' class='pointer'>";
      }
      $link .="</a>";

      return $link;

//       global $SIMPLE_FORMS;
//       $id = 'minimal_form'.mt_rand();
//
//       $SIMPLE_FORMS .= "<form method='post' id='$id' name='$id' action='$action'>";
//       if (is_array($fields) && count($fields)) {
//          foreach ($fields as $name => $value) {
//             $SIMPLE_FORMS .= "<input type='hidden' name='$name' value='$value'>";
//          }
//       }
//       $SIMPLE_FORMS .= "<input type='hidden' name='$btname' value='$btname'>";
//
//       echo "<a href='#' class='vsubmit' class='submit' $btoption
//             onClick=\"document.$id.submit()\">";
//       $btlabel = htmlentities($btlabel, ENT_QUOTES, 'UTF-8');
//       if (empty($btimage)) {
//          echo $btlabel;
//       } else {
//          echo "<img src='$btimage' title='$btlabel' alt='$btlabel'>";
//       }
//       echo "</a>";
//
//       $SIMPLE_FORMS .= Html::closeForm(false);
   }


   /**
    * create a minimal form for simple action
    *
    * @param $action   String   URL to call on submit
    * @param $btname   String   button name
    * @param $btlabel  String   button label
    * @param $fields   Array    field name => field  value
    * @param $btimage  String   button image uri (optional) (default '')
    * @param $btoption String   optional button option (default '')
    * @param $confirm  String   optional confirm message (default '')
    *
    * @since version 0.83.3
   **/
   static function showSimpleForm($action, $btname, $btlabel, Array $fields=array(), $btimage='',
                                  $btoption='', $confirm='') {

      echo self::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
   }


   /**
    * Create a close form part including CSRF token
    *
    * @param $display boolean Display or return string (default true)
    *
    * @since version 0.83.
    *
    * @return String
   **/
   static function closeForm ($display=true) {
      global $CFG_GLPI;

      $out = "\n";
      if (GLPI_USE_CSRF_CHECK) {
         $out .= Html::hidden('_glpi_csrf_token', array('value' => Session::getNewCSRFToken()))."\n";
      }

      if (isset($CFG_GLPI['checkbox-zero-on-empty']) && $CFG_GLPI['checkbox-zero-on-empty']) {
         $js = "   $('form').submit(function() {
         $('input[type=\"checkbox\"][data-glpicore-cb-zero-on-empty=\"1\"]:not(:checked)').each(function(index){
            // If the checkbox is not validated, we add a hidden field with '0' as value
            if ($(this).attr('name')) {
               $('<input>').attr({
                  type: 'hidden',
                  name: $(this).attr('name'),
                  value: '0'
               }).insertAfter($(this));
            }
         });
      });";
         $out .= Html::scriptBlock($js)."\n";
         unset($CFG_GLPI['checkbox-zero-on-empty']);
      }

      $out .= "</form>\n";
      if ($display) {
         echo $out;
         return true;
      }
      return $out;
   }


   /**
    * Get javascript code for hide an item
    *
    * @param $id string id of the dom element
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsHide($id) {
      return self::jsGetElementbyID($id).".hide();\n";
   }


   /**
    * Get javascript code for hide an item
    *
    * @param $id string id of the dom element
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsShow($id) {
      return self::jsGetElementbyID($id).".show();\n";
   }


   /**
    * Get javascript code for enable an item
    *
    * @param $id string id of the dom element
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsEnable($id) {
      return self::jsGetElementbyID($id).".removeAttr('disabled');\n";
   }


   /**
    * Get javascript code for disable an item
    *
    * @param $id string id of the dom element
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsDisable($id) {
      return self::jsGetElementbyID($id).".attr('disabled', 'disabled');\n";
   }


   /**
    * Clean ID used for HTML elements
    *
    * @param $id string id of the dom element
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function cleanId($id) {
      return str_replace(array('[',']'), '_', $id);
   }


   /**
    * Get javascript code to get item by id
    *
    * @param $id string id of the dom element
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsGetElementbyID($id) {
      return "$('#$id')";
   }


   /**
    * Set dropdown value
    *
    * @param $id      string   id of the dom element
    * @param $value   string   value to set
    *
    * @since version 0.85.
    *
    * @return string
   **/
   static function jsSetDropdownValue($id, $value) {
      return self::jsGetElementbyID($id).".val('$value').trigger('change');";
   }

   /**
    * Get item value
    *
    * @param $id      string   id of the dom element
    *
    * @since version 0.85.
    *
    * @return string
   **/
   static function jsGetDropdownValue($id) {
      return self::jsGetElementbyID($id).".val()";
   }


   /**
    * Adapt dropdown to clean JS
    *
    * @param $id       string   id of the dom element
    * @param $params   array    of parameters
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsAdaptDropdown($id, $params=array()) {
      global $CFG_GLPI;

      $width = '';
      if (isset($params["width"]) && !empty($params["width"])) {
         $width = $params["width"];
         unset($params["width"]);
      }
      $js = "$('#$id').select2({
                  width: '$width',
                  closeOnSelect: false,
                  dropdownAutoWidth: true,
                  quietMillis: 100,
                  minimumResultsForSearch: ".$CFG_GLPI['ajax_limit_count'].",
                  formatSelection: function(object, container) {
                     text = object.text;
                     if (object.element[0].parentElement.nodeName == 'OPTGROUP') {
                        text = object.element[0].parentElement.getAttribute('label') + ' - ' + text;
                     }
                     return text;
                  }

             });";
      return Html::scriptBlock($js);
   }


   /**
    * Create Ajax dropdown to clean JS
    *
    * @param $name
    * @param $field_id   string   id of the dom element
    * @param $url        string   URL to get datas
    * @param $params     array    of parameters
    *            must contains :
    *                   - 'value'     : default value selected
    *                   - 'valuename' : default name of selected value
    *
    * @since version 0.85.
    *
    * @return String
   **/
   static function jsAjaxDropdown($name, $field_id, $url, $params=array()) {
      global $CFG_GLPI;

      if (!isset($params['value'])) {
         $value = 0;
      } else {
         $value = $params['value'];
      }
      if (!isset($params['value'])) {
         $valuename = Dropdown::EMPTY_VALUE;
      } else {
         $valuename = $params['valuename'];
      }
      $on_change = '';
      if (isset($params["on_change"])) {
         $on_change = $params["on_change"];
         unset($params["on_change"]);
      }
      $width = '80%';
      if (isset($params["width"])) {
         $width = $params["width"];
         unset($params["width"]);
      }
      unset($params['value']);
      unset($params['valuename']);

      $options = array('value' => $value, 'id' => $field_id);
      if (!empty($params['specific_tags'])) {
         foreach ($params['specific_tags'] as $tag => $val) {
            if (is_array($val)) {
               $val = implode(' ', $val);
            }
            $options[$tag] = $val;
         }
      }

      $output = Html::hidden($name, $options);

      $js = "";
      $js .= " $('#$field_id').select2({
                        width: '$width',
                        minimumInputLength: 0,
                        quietMillis: 100,
                        dropdownAutoWidth: true,
                        minimumResultsForSearch: ".$CFG_GLPI['ajax_limit_count'].",
                        closeOnSelect: false,
                        ajax: {
                           url: '$url',
                           dataType: 'json',
                           data: function (term, page) {
                              return { ";
      foreach ($params as $key => $val) {
         // Specific boolean case
         if (is_bool($val)) {
            $js .= "$key: ".($val?1:0).",\n";
         } else {
            $js .= "$key: ".json_encode($val).",\n";
         }
      }

      $js .= "               searchText: term,
                                 page_limit: ".$CFG_GLPI['dropdown_max'].", // page size
                                 page: page, // page number
                              };
                           },
                           results: function (data, page) {
//                               var more = (page * ".$CFG_GLPI['dropdown_max'].") < data.total;
//                               alert(data.count+' '+".$CFG_GLPI['dropdown_max'].");
                              var more = (data.count >= ".$CFG_GLPI['dropdown_max'].");
                              return {results: data.results, more: more};
//                               return {results: data.results};
                           }
                        },
                        initSelection: function (element, callback) {
                           var id=$(element).val();
                           var defaultid = '$value';
                           if (id !== '') {
                              // No ajax call for first item
                              if (id === defaultid) {
                                var data = {id: ".json_encode($value).",
                                          text: ".json_encode($valuename)."};
                                 callback(data);
                              } else {
                                 $.ajax('$url', {
                                 data: {";
         foreach ($params as $key => $val) {
            $js .= "$key: ".json_encode($val).",\n";
         }

         $js .= "            _one_id: id},
                                 dataType: 'json',
                                 }).done(function(data) { callback(data); });
                              }
                           }

                        },
                        formatResult: function(result, container, query, escapeMarkup) {
                           var markup=[];
                           window.Select2.util.markMatch(result.text, query.term, markup, escapeMarkup);
                           if (result.level) {
                              var a='';
                              var i=result.level;
                              while (i>1) {
                                 a = a+'&nbsp;&nbsp;&nbsp;';
                                 i=i-1;
                              }
                              return a+'&raquo;'+markup.join('');
                           }
                           return markup.join('');
                        }

                     });";
      if (!empty($on_change)) {
         $js .= " $('#$field_id').on('change', function(e) {".
                  stripslashes($on_change)."});";
      }

      $output .= Html::scriptBlock($js);
      return $output;
   }


   /**
    * Creates a formatted IMG element.
    *
    * This method will set an empty alt attribute if no alt and no title is not supplied
    *
    * @since version 0.85
    *
    * @param $path             Path to the image file
    * @param $options   Array  of HTML attributes
    *        - `url` If provided an image link will be generated and the link will point at
    *               `$options['url']`.
    * @return string completed img tag
   **/
   static function image($path, $options=array()) {

      if (!isset($options['title'])) {
         $options['title'] = '';
      }

      if (!isset($options['alt'])) {
         $options['alt'] = $options['title'];
      }

      if (empty($options['title'])
          && !empty($options['alt'])) {
         $options['title'] = $options['alt'];
      }

      $url = false;
      if (!empty($options['url'])) {
         $url = $options['url'];
         unset($options['url']);
      }

      $image = sprintf('<img src="%1$s" %2$s class="pointer">', $path, Html::parseAttributes($options));
      if ($url) {
         return Html::link($image, $url);
      }
      return $image;
   }


   /**
    * Creates an HTML link.
    *
    * @since version 0.85
    *
    * @param $text               The content to be wrapped by a tags.
    * @param $url                URL parameter
    * @param $options   Array    of HTML attributes:
    *     - `confirm` JavaScript confirmation message.
    *     - `confirmaction` optional action to do on confirmation
    * @return string an `a` element.
   **/
   static function link($text, $url, $options=array()) {

      if (isset($options['confirm'])) {
         if (!empty($options['confirm'])) {
            $confirmMessage = $options['confirm'];
            $confirmAction  = '';
            if (isset($options['confirmaction'])) {
               if (!empty($options['confirmaction'])) {
                  $confirmAction = $options['confirmaction'];
               }
               unset($options['confirmaction']);
            }
            $options['onclick'] = Html::getConfirmationOnActionScript($options['confirm'],
                                                                      $confirmAction);
         }
         unset($options['confirm']);
      }
      // Do not escape title if it is an image
      if (!preg_match('/^<img.*/', $text)) {
         $text = Html::cleanInputText($text);
      }

      return sprintf('<a href="%1$s" %2$s>%3$s</a>', Html::cleanInputText($url),
                     Html::parseAttributes($options), $text);
   }


   /**
    * Creates a hidden input field.
    *
    * If value of options is an array then recursively parse it
    * to generate as many hidden input as necessary
    *
    * @since version 0.85
    *
    * @param $fieldName          Name of a field
    * @param $options    Array   of HTML attributes.
    *
    * @return string A generated hidden input
   **/
   static function hidden($fieldName, $options=array()) {

      if ((isset($options['value'])) && (is_array($options['value']))) {
         $result = '';
         foreach ($options['value'] as $key => $value) {
            $options2          = $options;
            $options2['value'] = $value;
            $result           .= static::hidden($fieldName.'['.$key.']', $options2)."\n";
         }
         return $result;
      }
      return sprintf('<input type="hidden" name="%1$s" %2$s>',
                     Html::cleanInputText($fieldName), Html::parseAttributes($options));
   }


   /**
    * Creates a text input field.
    *
    * @since version 0.85
    *
    * @param $fieldName          Name of a field
    * @param $options    Array   of HTML attributes.
    *
    * @return string A generated hidden input
   **/
   static function input($fieldName, $options=array()) {

      return sprintf('<input type="text" name="%1$s" %2$s>',
                     Html::cleanInputText($fieldName), Html::parseAttributes($options));
   }

   /**
    * Creates a submit button element. This method will generate input elements that
    * can be used to submit, and reset forms by using $options. Image submits can be created by supplying an
    * image option
    *
    * @since version 0.85
    *
    * @param $caption          caption of the input
    * @param $options    Array of options.
    *     - image : will use a submit image input
    *     - `confirm` JavaScript confirmation message.
    *     - `confirmaction` optional action to do on confirmation
    *
    * @return string A HTML submit button
   **/
   static function submit($caption, $options=array()) {

      $image = false;
      if (isset($options['image'])) {
         if (preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $options['image'])) {
            $image = $options['image'];
         }
         unset($options['image']);
      }

      // Set default class to submit
      if (!isset($options['class'])) {
         $options['class'] = 'submit';
      }
      if (isset($options['confirm'])) {
         if (!empty($options['confirm'])) {
            $confirmMessage = $options['confirm'];
            $confirmAction  = '';
            if (isset($options['confirmaction'])) {
               if (!empty($options['confirmaction'])) {
                  $confirmAction = $options['confirmaction'];
               }
               unset($options['confirmaction']);
            }
            $options['onclick'] = Html::getConfirmationOnActionScript($options['confirm'],
                                                                      $confirmAction);
         }
         unset($options['confirm']);
      }

      if ($image) {
         $options['title'] = $caption;
         $options['alt']   = $caption;
         return sprintf('<input type="image" src="%s" %s>',
               Html::cleanInputText($image), Html::parseAttributes($options));
      }
      return sprintf('<input type="submit" value="%s" %s>',
                     Html::cleanInputText($caption), Html::parseAttributes($options));
   }


   /**
    * Returns a space-delimited string with items of the $options array.
    *
    * @since version 0.85
    *
    * @param $options Array of options.
    *
    * @return string Composed attributes.
   **/
   static function parseAttributes($options=array()) {

      if (!is_string($options)) {
         $attributes = array();

         foreach ($options as $key => $value) {
            $attributes[] = Html::formatAttribute($key, $value);
         }
         $out = implode(' ', $attributes);
      } else {
         $out = $options;
      }
      return $out;
   }


   /**
    * Formats an individual attribute, and returns the string value of the composed attribute.
    *
    * @since version 0.85
    *
    * @param $key       The name of the attribute to create
    * @param $value     The value of the attribute to create.
    *
    * @return string The composed attribute.
   **/
   static function formatAttribute($key, $value) {

      if (is_array($value)) {
         $value = implode(' ' , $value);
      }

      return sprintf('%1$s="%2$s"', $key, Html::cleanInputText($value));
   }


   /**
    * Wrap $script in a script tag.
    *
    * @since version 0.85
    *
    * @param $script The script to wrap
    *
    * @return string
   **/
   static function scriptBlock($script) {

      $script = "\n" . '//<![CDATA[' . "\n\n" . $script . "\n\n" . '//]]>' . "\n";

      return sprintf('<script type="text/javascript">%s</script>', $script);
   }


   /**
    * Begin a script block that captures output until HtmlHelper::scriptEnd()
    * is called. This capturing block will capture all output between the methods
    * and create a scriptBlock from it.
    *
    * @since version 0.85
   **/
   static function scriptStart() {
      ob_start();
      return null;
   }


   /**
    * End a Buffered section of Javascript capturing.
    * Generates a script tag inline
    *
    * @since version 0.85
    *
    * @return mixed depending on the settings of scriptStart() either a script tag or null
   **/
   static function scriptEnd() {

      $buffer = ob_get_clean();
      $buffer = "$( document ).ready(function() {\n".$buffer."\n});";
      return Html::scriptBlock($buffer);
   }


   /**
    * Returns one or many script tags depending on the number of scripts given.
    *
    * @since version 0.85
    *
    * @param $url String of javascript file to include
    *
    * @return String of script tags
   **/
   static function script($url) {
      return sprintf('<script type="text/javascript" src="%1$s"></script>', $url);
   }


   /**
    * Creates a link element for CSS stylesheets.
    *
    * @since version 0.85
    *
    * @param $url       String   of javascript file to include
    * @param $options   Array    of HTML attributes.
    *
    * @return string CSS link tag
   **/
   static function css($url, $options=array()) {

      if (!isset($options['media'])) {
         $options['media'] = 'screen';
      }
      return sprintf('<link rel="stylesheet" type="text/css" href="%s" %s>', $url,
                     Html::parseAttributes($options));
   }

   /**
    * Creates an input file field. Send file names in _$name field as array.
    * Files are uploaded in files/_tmp/ directory
    *
    * @since version 0.85
    *
    * @param $options       array of options
    *    - name                string   field name (default filename)
    *    - multiple            boolean  allow multiple file upload (default false)
    *    - onlyimages          boolean  restrict to image files (default false)
    *    - showfilecontainer   string   DOM ID of the container showing file uploaded:
    *                                   use selector to display
    *    - showfilesize        boolean  show file size with file name
    *    - rand                string   already computed rand value
    *    - pasteZone           string   DOM ID of the paste zone
    *    - dropZone            string   DOM ID of the drop zone
    *
    * @return string input file field
   **/
   static function file($options=array()) {
      global $CFG_GLPI;

      $randupload             = mt_rand();

      $p['name']              = 'filename';
      $p['multiple']          = false;
      $p['onlyimages']        = false;
      $p['showfilecontainer'] = '';
      $p['showfilesize']      = true;
      $p['pasteZone']         = false;
      $p['dropZone']          = 'dropdoc'.$randupload;
      $p['rand']              = $randupload;
      $p['values']            = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $addshowfilecontainer = false;
      if (empty($p['showfilecontainer'])) {
         $addshowfilecontainer   = true;
         $p['showfilecontainer'] = "filedata$randupload";
      }

      //echo "<input type='file' name='filename' value='".$this->fields["filename"]."' size='39'>";
      $out  = "<div class='fileupload' id='".$p['dropZone']."'>";
      $out .= "<span class='b'>".__('Drag and drop your file here, or').'</span><br>';
      $out .= "<input id='fileupload$randupload' type='file' name='".$p['name']."[]' data-url='".
                $CFG_GLPI["root_doc"]."/front/fileupload.php?name=".$p['name'].
                "&amp;showfilesize=".$p['showfilesize']."'>";
      if ($addshowfilecontainer) {
         $out .= "<div id='".$p['showfilecontainer']."'></div>";
      }

      $script  = self::fileScript($p)."\n uploadFile".$p['rand']."();";
      $out    .= Html::scriptBlock($script);
      $out    .=  "<div id='progress$randupload' style='display:none'>".
                  "<div class='uploadbar' style='width: 0%;'></div></div>";
      $out .= "</div>";

      return $out;
   }

   /**
    * imagePaste : Show image paste for an item, with TinyMce
    *
    * @since version 0.85
    *
    * @param $options       array of options
    *     - name              string   field name (default filename)
    *     - multiple          boolean  allow multiple file upload (default false
    *     - onlyimages        boolean  restrict to image files (default false)
    *     - showfilecontainer string   DOM ID of the container showing file uploaded:
    *                                  use selector to display
    *     - imagePaste        boolean  image paste with tinyMce
    *     - dropZone          string   DOM ID of the drop zone
    *     - rand              string   already computed rand value
    *     - pasteZone         string   DOM ID of the paste zone
    *
    * @return nothing (print the image paste)
   **/
   static function imagePaste($options=array()) {

      $rand = mt_rand();

      $p['name']              = 'stock_image';
      $p['multiple']          = true;
      $p['onlyimages']        = true;
      $p['showfilecontainer'] = 'fileupload_info';
      $p['imagePaste']        = 1;
      $p['dropZone']          = 'image_paste';
      $p['rand']              = $rand;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      echo '<script type="text/javascript">';
      echo Html::fileScript($p);
      echo '</script>';

      echo "<div class='fileupload' id='".$p['dropZone']."'></div>\n";
   }


   /**
    * fileScript : file upload script
    *
    * @since version 0.85
    *
    * @param $options   array of possible options:
    *     - imagePaste        boolean  image paste with tinyMce
    *     - name              string   field name (default filename)
    *     - multiple          boolean  allow multiple file upload (default false)
    *     - onlyimages        boolean  restrict to image files (default false)
    *     - showfilecontainer string   DOM ID of the container showing file uploaded:
    *                                  use selector to display
    *     - pasteZone         string   DOM ID of the paste zone
    *     - dropZone          string   DOM ID of the drop zone
    *     - rand              string   already computed rand value
    *
    * @return nothing (print the image paste)
   **/
   static function fileScript($options=array()){
      global $CFG_GLPI;

      $randupload             = mt_rand();

      $p['imagePaste']        = 0;
      $p['name']              = 'filename';
      $p['multiple']          = false;
      $p['onlyimages']        = false;
      $p['showfilecontainer'] = '';
      $p['pasteZone']         = false;
      $p['dropZone']          = 'dropdoc'.$randupload;
      $p['rand']              = $randupload;
      $p['values']            = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $script = "fileindex".$p['rand']." = 0;
         function uploadFile".$p['rand']."() {
            $('#fileupload".$p['rand']."').fileupload({
               //forceIframeTransport: true,
               //replaceFileInput: false,
               dataType: 'json',";
      if ($p['pasteZone'] != false) {
         $script .= "pasteZone : $('#".$p['pasteZone']."'),";
      } else if (!$p['imagePaste']) {
         $script .= "pasteZone : false,";
      }
      if ($p['dropZone'] != false) {
         $script .= "dropZone : $('#".$p['dropZone']."'),";
      } else {
         $script .= "dropZone : false,";
      }
      if ($p['onlyimages']) {
         $script .= "acceptFileTypes: '/(\.|\/)(gif|jpe?g|png)$/i',";
      }
      $script .= "   progressall: function (e, data) {
                        var progress = parseInt(data.loaded / data.total * 100, 10);
                        $('#progress".$p['rand']."').show();
                        $('#progress".$p['rand']." .uploadbar').css({
                              'width':progress + '%'
                        });
                        $('#progress".$p['rand']." .uploadbar').text(progress + '%').show().delay(5000).fadeOut('slow');
                  },
               send: function (e, data) {
                  if (1==".(($p['imagePaste'])?1:0)."
                     && tinyMCE != undefined
                     && tinyMCE.imagePaste != undefined
                     && tinyMCE.imagePaste.pasteddata == undefined
                     && tinyMCE.imagePaste.stockimage == undefined) {

                     if (!tinyMCE.isIE) {
                        var reader = new FileReader();
                        reader.readAsDataURL(data.originalFiles[0]);//Convert the blob from clipboard to base64
                        reader.onloadend = function(e){
                           $('#desc_paste_image').html(e.target.result);
                           tinyMCE.imagePaste.processpaste($('#desc_paste_image'),
                                                           '"._sx('button', 'Paste image')."',
                                                           data.originalFiles[0]);
                        }
                     }
                     return false
                  }
               },
               done: function (e, data) {
                     var filedata = data;
                     // Load image tag, and display image uploaded
                     $.ajax({
                        type: 'POST',
                        url: '".$CFG_GLPI['root_doc']."/ajax/getFileTag.php',
                        data: {'data':data.result.".$p['name']."},
                        dataType: 'JSON',
                        success: function(tag){
                           $.each(filedata.result.".$p['name'].", function (index, file) {
                              if (file.error == undefined) {\n
                                 displayUploadedFile".$p['rand']."(file,tag[index]);
                                 ";
      if ($p['imagePaste']) {
         $script.= "             // Insert tag in textarea
                                 if (tinyMCE != undefined) {\n
                                    tinyMCE.activeEditor.execCommand('mceInsertContent', false, '<p>'+tag[index].tag+'</p>');\n
                                    if (tinyMCE.imagePaste != undefined) {
                                       tinyMCE.imagePaste.pasteddata = undefined;
                                       tinyMCE.imagePaste.stockimage = undefined;
                                    }
                                 }\n";
      }
      $script.="                 $('#progress".$p['rand']." .uploadbar').text('".__('Upload successful')."');\n
                                 $('#progress".$p['rand']." .uploadbar').css('width', '100%');\n
                              } else {\n
                                 $('#progress".$p['rand']." .uploadbar').text(file.error);\n
                                 $('#progress".$p['rand']." .uploadbar').css('width', '100%');\n
                              }
                           });
                        }
                    });

               }
            });
         };\n
         function displayUploadedFile".$p['rand']."(file, tag){
            var p = $('<p/>').attr('id',file.id).html('<b>".__('File')." : </b>'+file.display+' <b>".__('Tag')." : </b>'+tag.tag+' ').appendTo('#".$p['showfilecontainer']."');\n
            var p2 = $('<p/>').attr('id',file.id+'2').css({'display':'none'}).appendTo('#".$p['showfilecontainer']."');\n

            // File
            $('<input/>').attr('type', 'hidden').attr('name', '_".$p['name']."['+fileindex".$p['rand']."+']').attr('value',file.name).appendTo(p);\n

            // Tag
            $('<input/>').attr('type', 'hidden').attr('name', '_tag_".$p['name']."['+fileindex".$p['rand']."+']').attr('value', tag.name).appendTo(p);\n

            // Coordinates
            if (tinyMCE != undefined
                  && tinyMCE.imagePaste != undefined
                  && (tinyMCE.imagePaste.imageCoordinates != undefined || tinyMCE.imagePaste.imageCoordinates != null)) {
               $('<input/>').attr('type', 'hidden').attr('name', '_coordinates['+fileindex".$p['rand']."+']').attr('value', encodeURIComponent(JSON.stringify(tinyMCE.imagePaste.imageCoordinates))).appendTo(p2);
               tinyMCE.imagePaste.imageCoordinates = null;
            }

            // Delete button
            var elementsIdToRemove = {0:file.id, 1:file.id+'2'};
            $('<img src=\"".$CFG_GLPI['root_doc']."/pics/delete.png\" class=\"pointer\">').click(function(){\n
               deleteImagePasted(elementsIdToRemove, tag.tag);\n
            }).appendTo(p);\n
            ";
         if ($p['multiple']) {
            $script.= "             fileindex".$p['rand']." = fileindex".$p['rand']."+1;\n";
         }

         $script .= "}
         function deleteImagePasted(elementsIdToRemove, tagToRemove){\n
            // Remove file display lines
            $.each(elementsIdToRemove, function (index, id) {\n
                $('#'+id).remove();\n
            });\n
            ";
   if ($p['imagePaste']) {
      $script.= "
            // TINYMCE : Remove tag from textarea
            if (tinyMCE != undefined) {
               tinyMCE.activeEditor.setContent(tinyMCE.activeEditor.getContent().replace('<p>'+tagToRemove+'</p>', ''));\n
            }";
   }
   $script.= "
            // File counter
            if (fileindex".$p['rand']." > 0) {\n
               fileindex".$p['rand']."--;\n
            }
         };\n";

      if (is_array($p['values']) && isset($p['values']['filename'])
         && is_array($p['values']['filename']) && count($p['values']['filename'])) {
         foreach ($p['values']['filename'] as $key => $name) {
            if (isset($p['values']['tag'][$key])) {
               $file = GLPI_TMP_DIR.'/'.$p['values']['filename'][$key];
               if (file_exists($file)) {
                  $display = sprintf('%1$s %2$s', $p['values']['filename'][$key],
                                                  Toolbox::getSize(filesize($file)));
                  $script .= "var tag$key = {};
                              tag$key.tag = '".$p['values']['tag'][$key]."';
                              tag$key.name = '#".$p['values']['tag'][$key]."#';
                              var file$key= {};
                              file$key.name = '".addslashes($p['values']['filename'][$key])."'
                              file$key.display = '".addslashes($display)."';
                              file$key.id = 'file$key';
                              displayUploadedFile".$p['rand']."(file$key, tag$key);
                              ";
               }
            }
         }
      }
      return $script;
   }


   /**
    * @since version 0.85
    *
    * @return string
   **/
   static function generateImageName(){
      return 'pastedImage'.str_replace('-', '', Html::convDateTime(date('Y-m-d', time())));
   }


   /**
    * Display choice matrix
    *
    * @since version 0.85
    * @param $columns   array   of column field name => column label
    * @param $rows      array    of field name => array(
    *      'label' the label of the row
    *       'columns' an array of specific information regaring current row and given column indexed by column field_name
    *                 * a string if only have to display a string
    *                 * an array('value' => ???, 'readonly' => ???) that is used to Dropdown::showYesNo()
    * @param $options   array   possible:
    *       'title'         of the matrix
    *       'first_cell'    the content of the upper-left cell
    *       'row_check_all' set to true to display a checkbox to check all elements of the row
    *       'col_check_all' set to true to display a checkbox to check all elements of the col
    *       'rand'          random number to use for ids
    *
    * @return random value used to generate the ids
   **/
   static function showCheckboxMatrix(array $columns, array $rows, array $options=array()) {

      $param['title']                = '';
      $param['first_cell']           = '&nbsp;';
      $param['row_check_all']        = false;
      $param['col_check_all']        = false;
      $param['rotate_column_titles'] = false;
      $param['rand']                 = mt_rand();
      $param['table_class']          = 'tab_cadre_fixehov';
      $param['cell_class_method']    = NULL;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      $cb_options = array('title' => __s('Check/uncheck all'));

      $number_columns = (count($columns) + 1);
      if ($param['row_check_all']) {
         $number_columns += 1;
      }
      $width = round(100/$number_columns);
      echo "\n<table class='".$param['table_class']."'>\n";

      if (!empty($param['title'])) {
         echo "\t<tr>\n";
         echo "\t\t<th colspan='$number_columns'>".$param['title']."</th>\n";
         echo "\t</tr>\n";
      }

      echo "\t<tr class='tab_bg_1'>\n";
      echo "\t\t<td>".$param['first_cell']."</td>\n";
      foreach ($columns as $col_name => $column) {
         $nb_cb_per_col[$col_name] = array('total'   => 0,
                                           'checked' => 0);
         $col_id                   = Html::cleanId('col_label_'.$col_name.'_'.$param['rand']);

         echo "\t\t<td class='center b";
         if ($param['rotate_column_titles']) {
            echo " rotate";
         }
         echo "' id='$col_id' width='$width%'>";
         if (!is_array($column)) {
            $columns[$col_name] = $column = array('label' => $column);
         }
         if (isset($column['short'])
             && isset($column['long'])) {
            echo $column['short'];
            self::showToolTip($column['long'], array('applyto' => $col_id));
         } else {
            echo $column['label'];
         }
         echo "</td>\n";
      }
      if ($param['row_check_all']) {
         $col_id = Html::cleanId('col_of_table_'.$param['rand']);
         echo "\t\t<td class='center";
         if ($param['rotate_column_titles']) {
            echo " rotate";
         }
         echo "' id='$col_id'>".__('Select/unselect all')."</td>\n";
      }
      echo "\t</tr>\n";

      foreach ($rows as $row_name => $row) {

         if ((!is_string($row)) && (!is_array($row))) {
            continue;
         }

         echo "\t<tr class='tab_bg_1'>\n";

         if (is_string($row)) {
            echo "\t\t<th colspan='$number_columns'>$row</th>\n";
         } else {

            $row_id = Html::cleanId('row_label_'.$row_name.'_'.$param['rand']);
            if (isset($row['class'])) {
               $class = $row['class'];
            } else {
               $class = '';
            }
            echo "\t\t<td class='b $class' id='$row_id'>";
            if (!empty($row['label'])) {
               echo $row['label'];
            } else {
               echo "&nbsp;";
            }
            echo "</td>\n";

            $nb_cb_per_row = array('total'   => 0,
                                   'checked' => 0);

            foreach ($columns as $col_name => $column) {
               $class = '';
               if ((!empty($row['class'])) && (!empty($column['class']))) {
                  if (is_callable($param['cell_class_method'])) {
                     $class = $param['cell_class_method']($row['class'], $column['class']);
                  }
               } else if (!empty($row['class'])) {
                  $class = $row['class'];
               } else if (!empty($column['class'])) {
                  $class = $column['class'];
               }

               echo "\t\t<td class='center $class'>";

               // Warning: isset return false if the value is NULL ...
               if (array_key_exists($col_name, $row['columns'])) {
                  $content = $row['columns'][$col_name];
                  if (is_array($content)
                      && array_key_exists('checked', $content)) {
                     if (!array_key_exists('readonly', $content)) {
                        $content['readonly'] = false;
                     }
                     $content['massive_tags'] = array();
                     if ($param['row_check_all']) {
                        $content['massive_tags'][] = 'row_'.$row_name.'_'.$param['rand'];
                     }
                     if ($param['col_check_all']) {
                        $content['massive_tags'][] = 'col_'.$col_name.'_'.$param['rand'];
                     }
                     if ($param['row_check_all'] && $param['col_check_all']) {
                        $content['massive_tags'][] = 'table_'.$param['rand'];
                     }
                     $content['name'] = $row_name."[$col_name]";
                     $content['id']   = Html::cleanId('cb_'.$row_name.'_'.$col_name.'_'.
                                                      $param['rand']);
                     Html::showCheckbox($content);
                     $nb_cb_per_col[$col_name]['total'] ++;
                     $nb_cb_per_row['total'] ++;
                     if ($content['checked']) {
                        $nb_cb_per_col[$col_name]['checked'] ++;
                        $nb_cb_per_row['checked'] ++;
                     }
                  } else if (is_string($content)) {
                     echo $content;
                  } else {
                     echo "&nbsp;";
                  }
               } else {
                  echo "&nbsp;";
               }

               echo "</td>\n";
            }
         }
         if (($param['row_check_all'])
             && (!is_string($row))
             && ($nb_cb_per_row['total'] > 1)) {
            $cb_options['criterion']    = array('tag_for_massive' => 'row_'.$row_name.'_'.
                                                $param['rand']);
            $cb_options['massive_tags'] = 'table_'.$param['rand'];
            $cb_options['id']           = Html::cleanId('cb_checkall_row_'.$row_name.'_'.
                                                        $param['rand']);
            $cb_options['checked']      = ($nb_cb_per_row['checked']
                                             > ($nb_cb_per_row['total'] / 2));
            echo "\t\t<td class='center'>".Html::getCheckbox($cb_options)."</td>\n";
         }
         if ($nb_cb_per_row['total'] == 1) {
            echo "\t\t<td class='center'></td>\n";
         }
         echo "\t</tr>\n";
      }

      if ($param['col_check_all']) {
         echo "\t<tr class='tab_bg_1'>\n";
         echo "\t\t<td>".__('Select/unselect all')."</td>\n";
         foreach ($columns as $col_name => $column) {
            echo "\t\t<td class='center'>";
            if ($nb_cb_per_col[$col_name]['total'] > 1) {
               $cb_options['criterion']    = array('tag_for_massive' => 'col_'.$col_name.'_'.
                                                   $param['rand']);
               $cb_options['massive_tags'] = 'table_'.$param['rand'];
               $cb_options['id']           = Html::cleanId('cb_checkall_col_'.$col_name.'_'.
                                                           $param['rand']);
               $cb_options['checked']      = ($nb_cb_per_col[$col_name]['checked']
                                                > ($nb_cb_per_col[$col_name]['total'] / 2));
               echo Html::getCheckbox($cb_options);
            } else {
               echo "&nbsp;";
            }
            echo "</td>\n";
         }

         if ($param['row_check_all']) {
            $cb_options['criterion']    = array('tag_for_massive' => 'table_'.$param['rand']);
            $cb_options['massive_tags'] = '';
            $cb_options['id']           = Html::cleanId('cb_checkall_table_'.$param['rand']);
            echo "\t\t<td class='center'>".Html::getCheckbox($cb_options)."</td>\n";
         }
         echo "\t</tr>\n";
      }

      echo "</table>\n";

      return $param['rand'];
   }


}
?>
