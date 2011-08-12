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

// class Html
class Html {



   /**
    * Clean display value deleting html tags
    *
    *@param $value string: string value
    *
    *@return clean value
   **/
   static function clean($value) {

      $value = preg_replace("/<(p|br)( [^>]*)?".">/i", "\n", $value);

      $specialfilter = array('@<span[^>]*?x-hidden[^>]*?>.*?</span[^>]*?>@si'); // Strip ToolTips
      $value = preg_replace($specialfilter, ' ', $value);

      $search = array('@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
                      '@<style[^>]*?>.*?</style[^>]*?>@si',   // Strip style tags properly
                      '@<[\/\!]*?[^<>]*?>@si',                // Strip out HTML tags
                      '@<![\s\S]*?--[ \t\n\r]*>@');           // Strip multi-line comments including CDATA

      $value = preg_replace($search, ' ', $value);

      $value = preg_replace("/(&nbsp;| )+/", " ", $value);
      // nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
      $value = str_replace("&#8217;", "'", $value);

   // Problem with this regex : may crash
   //   $value = preg_replace("/ +/u", " ", $value);
      $value = preg_replace("/\n{2,}/", "\n\n", $value,-1);

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

      if (is_null($time) || $time=='NULL') {
         return NULL;
      }

      if (!isset($_SESSION["glpidate_format"])) {
         $_SESSION["glpidate_format"] = 0;
      }

      switch ($_SESSION['glpidate_format']) {
         case 1 : // DD-MM-YYYY
            $date = substr($time, 8, 2)."-";  // day
            $date .= substr($time, 5, 2)."-"; // month
            $date .= substr($time, 0, 4);     // year
            return $date;

         case 2 : // MM-DD-YYYY
            $date = substr($time, 5, 2)."-";  // month
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

      if (is_null($time) || $time=='NULL') {
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
     * @param $string string: string to resume
     * @param $length integer: resume length
     *
     * @return cut string
    **/
    static function resume_text($string, $length=255) {

       if (strlen($string)>$length) {
          $string = Toolbox::substr($string, 0, $length)."&nbsp;(...)";
       }

       return $string;
    }


    /**
     *  Resume a name for display
     *
     * @param $string string: string to resume
     * @param $length integer: resume length
     *
     * @return cut string
     **/
    static function resume_name($string, $length=255) {

       if (strlen($string)>$length) {
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
    * @param $number float: Number to display
    * @param $edit boolean: display number for edition ? (id edit use . in all case)
    * @param $forcedecimal integer: Force decimal number (do not use default value)
    *
    * @return formatted number
   **/
   static function formatNumber($number, $edit=false, $forcedecimal=-1) {
      global $CFG_GLPI;

      // Php 5.3 : number_format() expects parameter 1 to be double,
      if ($number=="") {
         $number = 0;

      } else if ($number=="-") { // used for not defines value (from Infocom::Amort, p.e.)
         return "-";
      }

      $number = doubleval($number);

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
         case 2 : // Other French
            return str_replace(' ', '&nbsp;', number_format($number, $decimal, ', ', ' '));

         case 0 : // French
            return str_replace(' ', '&nbsp;', number_format($number, $decimal, '.', ' '));

         default: // English
            return number_format($number, $decimal, '.', ', ');
      }
   }


   /**
    * Make a good string from the unix timestamp $sec
    *
    * @param $time integer: timestamp
    * @param $display_sec boolean: display seconds ?
    *
    * @return string
   **/
   static function timestampToString($time, $display_sec=true) {
      global $LANG;

      $sign = '';
      if ($time<0) {
         $sign = '- ';
         $time = abs($time);
      }
      $time = floor($time);

      // Force display seconds if time is null
      if ($time==0) {
         $display_sec = true;
      }

      $units = Toolbox::getTimestampTimeUnits($time);
      $out   = $sign;

      if ($units['day']>0) {
         $out .= " ".$units['day']."&nbsp;".Toolbox::ucfirst($LANG['calendar'][12]);
      }

      if ($units['hour']>0) {
         $out .= " ".$units['hour']."&nbsp;".Toolbox::ucfirst($LANG['gmt'][1]);
      }

      if ($units['minute']>0) {
         $out .= " ".$units['minute']."&nbsp;".$LANG['stats'][33];
      }

      if ($display_sec) {
         $out.=" ".$units['second']."&nbsp;".$LANG['stats'][34];
      }

      return $out;
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
    * Display common message for item not found
    *
    * @return Nothing
   **/
   static function displayNotFoundError() {
      global $LANG, $CFG_GLPI, $HEADER_LOADED;

      if (!$HEADER_LOADED) {
         if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
            nullHeader($LANG['login'][5]);

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
            commonHeader($LANG['login'][5]);

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            helpHeader($LANG['login'][5]);
         }
      }
      echo "<div class='center'><br><br>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='warning'><br><br>";
      echo "<strong>" . $LANG['common'][54] . "</strong></div>";
      nullFooter();
      exit ();
   }


   /**
    * Display common message for privileges errors
    *
    * @return Nothing (die)
   **/
   static function displayRightError() {
      global $LANG;

      displayErrorAndDie($LANG['common'][83]);
   }


   /**
    * Add confirmation on button or link before action
    *
    * @param $string string to display or array of string for using multilines
    * @param $additionalactions string additional actions to do on success confirmation
    *
    * @return nothing
   **/
   static function addConfirmationOnAction($string, $additionalactions='') {

      if (!is_array($string)) {
         $string = array($string);
      }
      $string = Toolbox::addslashes_deep($string);

      if (empty($additionalactions)) {
         $out = " onclick=\"return window.confirm('";
      } else {
         $out = " onclick=\"if (window.confirm('";
      }
      $out .= implode('\n',$string);
      $out .= "')";
      if (empty($additionalactions)) {
         $out .= ";\" ";
      } else {
         $out .= ") {".$additionalactions."};return true;\" ";
      }
      return $out;
   }


    /**
     * Create a Dynamic Progress Bar
     *
     * @param $msg initial message (under the bar)
     *
     * @return nothing
    **/
    static function createProgressBar ($msg="&nbsp;") {

       echo "<div class='doaction_cadre'>".
            "<div class='doaction_progress' id='doaction_progress'></div>".
            "</div><br>";

       echo "<script type='text/javascript'>";
       echo "var glpi_progressbar=new Ext.ProgressBar({
          text:\"$msg\",
          id:'progress_bar',
          applyTo:'doaction_progress'
       });";
       echo "</script>\n";
    }

    /**
     * Change the Message under the Progress Bar
     *
     * @param $msg message under the bar
     *
     * @return nothing
    **/
    static function changeProgressBarMessage($msg="&nbsp;") {
       echo "<script type='text/javascript'>glpi_progressbar.updateText(\"$msg\")</script>\n";
    }


    /**
     * Change the Progress Bar Position
     *
     * @param $crt Current Value (less then $max)
     * @param $tot Maximum Value
     * @param $msg message inside the bar (defaut is %)
     *
     * @return nothing
    **/
    static function changeProgressBarPosition ($crt, $tot, $msg="") {

       if (!$tot) {
          $pct = 0;

       } else if ($crt>$tot) {
          $pct = 1;

       } else {
          $pct = $crt/$tot;
       }
       echo "<script type='text/javascript'>glpi_progressbar.updateProgress(\"$pct\",\"$msg\");".
            "</script>\n";
       glpi_flush();
    }


    /**
     * Display a simple progress bar
     *
     * @param $width Width of the progress bar
     * @param $percent Percent of the progress bar
     * @param $options array options :
     *            - title : string title to display (default Progesssion)
     *            - simple : display a simple progress bar (no title / only percent)
     *            - forcepadding : boolean force str_pad to force refresh (default true)
     *
     * @return nothing
    **/
    static function displayProgressBar($width, $percent, $options=array()) {
       global $CFG_GLPI, $LANG;

       $param['title']        = $LANG['common'][47];
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
                   <table><tr><td class='center' style='background:url(".$CFG_GLPI["root_doc"].
                    "/pics/loader.png) repeat-x; padding: 0px;font-size: 10px;' width='".$percentwidth.
                    "px' height='12'>";

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
          glpi_flush();
       }
    }


}
?>