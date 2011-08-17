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
            self::nullHeader($LANG['login'][5]);

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
            self::header($LANG['login'][5]);

         } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            seml::helpHeader($LANG['login'][5]);
         }
      }
      echo "<div class='center'><br><br>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='warning'><br><br>";
      echo "<strong>" . $LANG['common'][54] . "</strong></div>";
      self::nullFooter();
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


   /**
    * Print a nice HTML head for every page
    *
    * @param $title title of the page
    * @param $url not used anymore.
    * @param $sector sector in which the page displayed is
    * @param $item item corresponding to the page displayed
    * @param $option option corresponding to the page displayed
    *
   **/
   static function header($title, $url='', $sector="none", $item="none", $option="") {
      global $CFG_GLPI, $LANG, $PLUGIN_HOOKS, $HEADER_LOADED, $DB;

      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      includeCommonHtmlHeader($title);
      // Body
      echo "<body>";
      // Generate array for menu and check right


      // INVENTORY
      $showstate = false;
      $menu['inventory']['title'] = $LANG['Menu'][38];

      if (Session::haveRight("computer","r")) {
         $menu['inventory']['default'] = '/front/computer.php';

         $menu['inventory']['content']['computer']['title']           = $LANG['Menu'][0];
         $menu['inventory']['content']['computer']['shortcut']        = 'c';
         $menu['inventory']['content']['computer']['page']            = '/front/computer.php';
         $menu['inventory']['content']['computer']['links']['search'] = '/front/computer.php';

         if (Session::haveRight("computer","w")) {
            $menu['inventory']['content']['computer']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Computer&amp;add=1';
            $menu['inventory']['content']['computer']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=Computer&amp;add=0';
         }
         $showstate = true;
      }


      if (Session::haveRight("monitor","r")) {
         $menu['inventory']['content']['monitor']['title']           = $LANG['Menu'][3];
         $menu['inventory']['content']['monitor']['shortcut']        = 'm';
         $menu['inventory']['content']['monitor']['page']            = '/front/monitor.php';
         $menu['inventory']['content']['monitor']['links']['search'] = '/front/monitor.php';

         if (Session::haveRight("monitor","w")) {
            $menu['inventory']['content']['monitor']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Monitor&amp;add=1';
            $menu['inventory']['content']['monitor']['links']['template']
                               = '/front/setup.templates.php?'.'itemtype=Monitor&amp;add=0';
         }
         $showstate = true;
      }


      if (Session::haveRight("software","r")) {
         $menu['inventory']['content']['software']['title']           = $LANG['Menu'][4];
         $menu['inventory']['content']['software']['shortcut']        = 's';
         $menu['inventory']['content']['software']['page']            = '/front/software.php';
         $menu['inventory']['content']['software']['links']['search'] = '/front/software.php';

         if (Session::haveRight("software","w")) {
            $menu['inventory']['content']['software']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Software&amp;add=1';
            $menu['inventory']['content']['software']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=Software&amp;add=0';
         }
         $showstate = true;
      }


      if (Session::haveRight("networking","r")) {
         $menu['inventory']['content']['networking']['title']           = $LANG['Menu'][1];
         $menu['inventory']['content']['networking']['shortcut']        = 'n';
         $menu['inventory']['content']['networking']['page']            = '/front/networkequipment.php';
         $menu['inventory']['content']['networking']['links']['search'] = '/front/networkequipment.php';

         if (Session::haveRight("networking","w")) {
            $menu['inventory']['content']['networking']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=NetworkEquipment&amp;add=1';
            $menu['inventory']['content']['networking']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=NetworkEquipment&amp;add=0';
         }
         $showstate = true;
      }


      if (Session::haveRight("peripheral","r")) {
         $menu['inventory']['content']['peripheral']['title']           = $LANG['Menu'][16];
         $menu['inventory']['content']['peripheral']['shortcut']        = 'n';
         $menu['inventory']['content']['peripheral']['page']            = '/front/peripheral.php';
         $menu['inventory']['content']['peripheral']['links']['search'] = '/front/peripheral.php';

         if (Session::haveRight("peripheral","w")) {
            $menu['inventory']['content']['peripheral']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Peripheral&amp;add=1';
            $menu['inventory']['content']['peripheral']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=Peripheral&amp;add=0';
         }
         $showstate = true;
      }


      if (Session::haveRight("printer","r")) {
         $menu['inventory']['content']['printer']['title']           = $LANG['Menu'][2];
         $menu['inventory']['content']['printer']['shortcut']        = 'p';
         $menu['inventory']['content']['printer']['page']            = '/front/printer.php';
         $menu['inventory']['content']['printer']['links']['search'] = '/front/printer.php';

         if (Session::haveRight("printer","w")) {
            $menu['inventory']['content']['printer']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Printer&amp;add=1';
            $menu['inventory']['content']['printer']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=Printer&amp;add=0';
         }
         $showstate = true;
      }


      if (Session::haveRight("cartridge","r")) {
         $menu['inventory']['content']['cartridge']['title']           = $LANG['Menu'][21];
         $menu['inventory']['content']['cartridge']['shortcut']        = 'c';
         $menu['inventory']['content']['cartridge']['page']            = '/front/cartridgeitem.php';
         $menu['inventory']['content']['cartridge']['links']['search'] = '/front/cartridgeitem.php';

         if (Session::haveRight("cartridge","w")) {
            $menu['inventory']['content']['cartridge']['links']['add'] = '/front/cartridgeitem.form.php';
         }
      }


      if (Session::haveRight("consumable","r")) {
         $menu['inventory']['content']['consumable']['title']           = $LANG['Menu'][32];
         $menu['inventory']['content']['consumable']['shortcut']        = 'g';
         $menu['inventory']['content']['consumable']['page']            = '/front/consumableitem.php';
         $menu['inventory']['content']['consumable']['links']['search'] = '/front/consumableitem.php';

         if (Session::haveRight("consumable","w")) {
            $menu['inventory']['content']['consumable']['links']['add']
                                                                  = '/front/consumableitem.form.php';
         }

         $menu['inventory']['content']['consumable']['links']['summary']
                                                      = '/front/consumableitem.php?'.'synthese=yes';
      }


      if (Session::haveRight("phone","r")) {
         $menu['inventory']['content']['phone']['title']           = $LANG['Menu'][34];
         $menu['inventory']['content']['phone']['shortcut']        = 't';
         $menu['inventory']['content']['phone']['page']            = '/front/phone.php';
         $menu['inventory']['content']['phone']['links']['search'] = '/front/phone.php';

         if (Session::haveRight("phone","w")) {
            $menu['inventory']['content']['phone']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Phone&amp;add=1';
            $menu['inventory']['content']['phone']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=Phone&amp;add=0';
         }
         $showstate = true;
      }


      if ($showstate) {
         $menu['inventory']['content']['state']['title']            = $LANG['Menu'][28];
         $menu['inventory']['content']['state']['shortcut']         = 'n';
         $menu['inventory']['content']['state']['page']             = '/front/states.php';
         $menu['inventory']['content']['state']['links']['search']  = '/front/states.php';
         $menu['inventory']['content']['state']['links']['summary'] = '/front/states.php?synthese=yes';
      }



      // ASSISTANCE
      $menu['maintain']['title'] = $LANG['title'][24];

      if (Session::haveRight("observe_ticket","1")
          || Session::haveRight("show_all_ticket","1")
          || Session::haveRight("create_ticket","1")) {

         $menu['maintain']['default'] = '/front/ticket.php';

         $menu['maintain']['content']['ticket']['title']           = $LANG['Menu'][5];
         $menu['maintain']['content']['ticket']['shortcut']        = 't';
         $menu['maintain']['content']['ticket']['page']            = '/front/ticket.php';
         $menu['maintain']['content']['ticket']['links']['search'] = '/front/ticket.php';
         $menu['maintain']['content']['ticket']['links']['search'] = '/front/ticket.php';

         if (Session::haveRight('validate_ticket',1)) {
            $opt = array();
            $opt['reset']         = 'reset';
            $opt['field'][0]      = 55; // validation status
            $opt['searchtype'][0] = 'equals';
            $opt['contains'][0]   = 'waiting';
            $opt['link'][0]       = 'AND';

            $opt['field'][1]      = 59; // validation aprobator
            $opt['searchtype'][1] = 'equals';
            $opt['contains'][1]   = Session::getLoginUserID();
            $opt['link'][1]       = 'AND';


            $pic_validate = "<img title=\"".$LANG['validation'][15]."\" alt=\"".$LANG['validation'][15].
                              "\" src='".$CFG_GLPI["root_doc"]."/pics/menu_showall.png'>";

            $menu['maintain']['content']['ticket']['links'][$pic_validate]
                              = '/front/ticket.php?'.Toolbox::append_params($opt, '&amp;');
         }
      }

      if (Session::haveRight("create_ticket","1")) {
         $menu['maintain']['content']['ticket']['links']['add'] = '/front/ticket.form.php';
      }

      if (Session::haveRight("show_all_problem","1") || Session::haveRight("show_my_problem","1")) {
         $menu['maintain']['content']['problem']['title']           = $LANG['Menu'][7];
         $menu['maintain']['content']['problem']['shortcut']        = 'p';
         $menu['maintain']['content']['problem']['page']            = '/front/problem.php';
         $menu['maintain']['content']['problem']['links']['search'] = '/front/problem.php';
         if (Session::haveRight("edit_all_problem","1")) {
            $menu['maintain']['content']['problem']['links']['add'] = '/front/problem.form.php';
         }
      }

      if (Session::haveRight("show_all_change","1") || Session::haveRight("show_my_change","1")) {
         $menu['maintain']['content']['change']['title']           = $LANG['Menu'][8];
         $menu['maintain']['content']['change']['shortcut']        = 'c';
         $menu['maintain']['content']['change']['page']            = '/front/change.php';
         $menu['maintain']['content']['change']['links']['search'] = '/front/change.php';
         if (Session::haveRight("edit_all_change","1")) {
            $menu['maintain']['content']['change']['links']['add'] = '/front/change.form.php';
         }
      }

      if (Session::haveRight("show_planning","1") || Session::haveRight("show_all_planning","1")) {
         $menu['maintain']['content']['planning']['title']           = Toolbox::ucfirst($LANG['log'][16]);
         $menu['maintain']['content']['planning']['shortcut']        = 'l';
         $menu['maintain']['content']['planning']['page']            = '/front/planning.php';
         $menu['maintain']['content']['planning']['links']['search'] = '/front/planning.php';
      }

      if (Session::haveRight("statistic","1")) {
         $menu['maintain']['content']['stat']['title']    = $LANG['Menu'][13];
         $menu['maintain']['content']['stat']['shortcut'] = '1';
         $menu['maintain']['content']['stat']['page']     = '/front/stat.php';
      }



      // FINANCIAL
      $menu['financial']['title'] = $LANG['Menu'][26];

      if (Session::haveRight("budget", "r")) {
         $menu['financial']['default'] = '/front/budget.php';

         $menu['financial']['content']['budget']['title']           = $LANG['financial'][110];
         $menu['financial']['content']['budget']['shortcut']        = 'n';
         $menu['financial']['content']['budget']['page']            = '/front/budget.php';
         $menu['financial']['content']['budget']['links']['search'] = '/front/budget.php';

         if (Session::haveRight("budget","w")) {
            $menu['financial']['content']['budget']['links']['add']
                              = '/front/setup.templates.php?'.'itemtype=Budget&amp;add=1';
            $menu['financial']['content']['budget']['links']['template']
                              = '/front/setup.templates.php?'.'itemtype=Budget&amp;add=0';
         }
      }

      if (Session::haveRight("contact_enterprise", "r")) {
         $menu['financial']['content']['supplier']['title']           = $LANG['Menu'][23];
         $menu['financial']['content']['supplier']['shortcut']        = 'e';
         $menu['financial']['content']['supplier']['page']            = '/front/supplier.php';
         $menu['financial']['content']['supplier']['links']['search'] = '/front/supplier.php';


         $menu['financial']['content']['contact']['title']           = $LANG['Menu'][22];
         $menu['financial']['content']['contact']['shortcut']        = 't';
         $menu['financial']['content']['contact']['page']            = '/front/contact.php';
         $menu['financial']['content']['contact']['links']['search'] = '/front/contact.php';

         if (Session::haveRight("contact_enterprise", "w")) {
            $menu['financial']['content']['contact']['links']['add']  = '/front/contact.form.php';
            $menu['financial']['content']['supplier']['links']['add'] = '/front/supplier.form.php';
         }
      }


      if (Session::haveRight("contract", "r")) {
         $menu['financial']['content']['contract']['title']           = $LANG['Menu'][25];
         $menu['financial']['content']['contract']['shortcut']        = 'n';
         $menu['financial']['content']['contract']['page']            = '/front/contract.php';
         $menu['financial']['content']['contract']['links']['search'] = '/front/contract.php';

         if (Session::haveRight("contract", "w")) {
            $menu['financial']['content']['contract']['links']['add']
                              = '/front/setup.templates.php?itemtype=Contract&amp;add=1';
            $menu['financial']['content']['contract']['links']['template']
                              = '/front/setup.templates.php?itemtype=Contract&amp;add=0';
         }
      }


      if (Session::haveRight("document", "r")) {
         $menu['financial']['content']['document']['title']           = $LANG['Menu'][27];
         $menu['financial']['content']['document']['shortcut']        = 'd';
         $menu['financial']['content']['document']['page']            = '/front/document.php';
         $menu['financial']['content']['document']['links']['search'] = '/front/document.php';

         if (Session::haveRight("document","w")) {
            $menu['financial']['content']['document']['links']['add'] = '/front/document.form.php';
         }
      }



      // UTILS
      $menu['utils']['title'] = $LANG['Menu'][18];

      $menu['utils']['default'] = '/front/reminder.php';

      $menu['utils']['content']['reminder']['title']           = $LANG['title'][37];
      $menu['utils']['content']['reminder']['page']            = '/front/reminder.php';
      $menu['utils']['content']['reminder']['links']['search'] = '/front/reminder.php';
      $menu['utils']['content']['reminder']['links']['add']    = '/front/reminder.form.php';

      if (Session::haveRight("knowbase","r") || Session::haveRight("faq","r")) {
         if (Session::haveRight("knowbase","r")) {
            $menu['utils']['content']['knowbase']['title']        = $LANG['Menu'][19];
         } else {
            $menu['utils']['content']['knowbase']['title']        = $LANG['knowbase'][1];
         }
         $menu['utils']['content']['knowbase']['page']            = '/front/knowbaseitem.php';
         $menu['utils']['content']['knowbase']['links']['search'] = '/front/knowbaseitem.php';

         if (Session::haveRight("knowbase","w") || Session::haveRight("faq","w")) {
            $menu['utils']['content']['knowbase']['links']['add']
                                                            = '/front/knowbaseitem.form.php?id=new';
         }
      }


      if (Session::haveRight("reservation_helpdesk","1")
          || Session::haveRight("reservation_central","r")) {
         $menu['utils']['content']['reservation']['title']            = $LANG['Menu'][17];
         $menu['utils']['content']['reservation']['page']             = '/front/reservationitem.php';
         $menu['utils']['content']['reservation']['links']['search']  = '/front/reservationitem.php';
         $menu['utils']['content']['reservation']['links']['showall'] = '/front/reservation.php';
      }


      if (Session::haveRight("reports","r")) {
         $menu['utils']['content']['report']['title'] = $LANG['Menu'][6];
         $menu['utils']['content']['report']['page']  = '/front/report.php';
      }


      if ($CFG_GLPI["use_ocs_mode"] && Session::haveRight("ocsng","w")) {
         $menu['utils']['content']['ocsng']['title']                      = $LANG['ocsconfig'][0];
         $menu['utils']['content']['ocsng']['page']                       = '/front/ocsng.php';

         $menu['utils']['content']['ocsng']['options']['import']['title'] = $LANG['ocsng'][2];
         $menu['utils']['content']['ocsng']['options']['import']['page']  = '/front/ocsng.import.php';

         $menu['utils']['content']['ocsng']['options']['sync']['title']   = $LANG['ocsng'][1];
         $menu['utils']['content']['ocsng']['options']['sync']['page']    = '/front/ocsng.sync.php';

         $menu['utils']['content']['ocsng']['options']['clean']['title']  = $LANG['ocsng'][3];
         $menu['utils']['content']['ocsng']['options']['clean']['page']   = '/front/ocsng.clean.php';

         $menu['utils']['content']['ocsng']['options']['link']['title']   = $LANG['ocsng'][4];
         $menu['utils']['content']['ocsng']['options']['link']['page']    = '/front/ocsng.link.php';

      }



      // PLUGINS
      if (isset($PLUGIN_HOOKS["menu_entry"]) && count($PLUGIN_HOOKS["menu_entry"])) {
         $menu['plugins']['title'] = $LANG['common'][29];
         $plugins = array();

         foreach  ($PLUGIN_HOOKS["menu_entry"] as $plugin => $active) {
            if ($active) { // true or a string
               $function = "plugin_version_$plugin";

               if (function_exists($function)) {
                  $plugins[$plugin] = $function();
               }
            }
         }

         if (count($plugins)) {
            $list = array();

            foreach ($plugins as $key => $val) {
               $list[$key] = $val["name"];
            }
            asort($list);

            foreach ($list as $key => $val) {
               $menu['plugins']['content'][$key]['title'] = $val;
               $menu['plugins']['content'][$key]['page']  = '/plugins/'.$key.'/';

               if (is_string($PLUGIN_HOOKS["menu_entry"][$key])) {
                  $menu['plugins']['content'][$key]['page'] .= $PLUGIN_HOOKS["menu_entry"][$key];
               }

               // Set default link for plugins
               if (!isset($menu['plugins']['default'])) {
                  $menu['plugins']['default'] = $menu['plugins']['content'][$key]['page'];
               }

               if ($sector=="plugins" && $item==$key) {
                  if (isset($PLUGIN_HOOKS["submenu_entry"][$key])
                      && is_array($PLUGIN_HOOKS["submenu_entry"][$key])) {

                     foreach ($PLUGIN_HOOKS["submenu_entry"][$key] as $name => $link) {
                        // New complete option management
                        if ($name=="options") {
                           $menu['plugins']['content'][$key]['options'] = $link;
                        } else { // Keep it for compatibility

                           if (is_array($link)) {
                              // Simple link option
                              if (isset($link[$option])) {
                                 $menu['plugins']['content'][$key]['links'][$name]
                                                ='/plugins/'.$key.'/'.$link[$option];
                              }
                           } else {
                              $menu['plugins']['content'][$key]['links'][$name]
                                                ='/plugins/'.$key.'/'.$link;
                           }
                        }
                     }
                  }
               }
            }
         }
      }


      /// ADMINISTRATION
      $menu['admin']['title'] = $LANG['Menu'][15];

      if (Session::haveRight("user","r")) {
         $menu['admin']['default'] = '/front/user.php';

         $menu['admin']['content']['user']['title']           = $LANG['Menu'][14];
         $menu['admin']['content']['user']['shortcut']        = 'u';
         $menu['admin']['content']['user']['page']            = '/front/user.php';
         $menu['admin']['content']['user']['links']['search'] = '/front/user.php';

         if (Session::haveRight("user","w")) {
            $menu['admin']['content']['user']['links']['add'] = "/front/user.form.php";
         }

        $menu['admin']['content']['user']['options']['ldap']['title'] = $LANG['Menu'][9];
        $menu['admin']['content']['user']['options']['ldap']['page']  = "/front/ldap.php";
      }


      if (Session::haveRight("group","r")) {
         $menu['admin']['content']['group']['title']           = $LANG['Menu'][36];
         $menu['admin']['content']['group']['shortcut']        = 'g';
         $menu['admin']['content']['group']['page']            = '/front/group.php';
         $menu['admin']['content']['group']['links']['search'] = '/front/group.php';

         if (Session::haveRight("group","w")) {
            $menu['admin']['content']['group']['links']['add']             = "/front/group.form.php";
            $menu['admin']['content']['group']['options']['ldap']['title'] = $LANG['Menu'][9];
            $menu['admin']['content']['group']['options']['ldap']['page']  = "/front/ldap.group.php";
         }
      }


      if (Session::haveRight("entity","r")) {
         $menu['admin']['content']['entity']['title']           = $LANG['Menu'][37];
         $menu['admin']['content']['entity']['shortcut']        = 'z';
         $menu['admin']['content']['entity']['page']            = '/front/entity.php';
         $menu['admin']['content']['entity']['links']['search'] = '/front/entity.php';
         $menu['admin']['content']['entity']['links']['add']    = "/front/entity.form.php";
      }


      if (Session::haveRight("rule_ldap","r")
          || Session::haveRight("rule_ocs","r")
          || Session::haveRight("entity_rule_ticket","r")
          || Session::haveRight("rule_softwarecategories","r")
          || Session::haveRight("rule_mailcollector","r")) {

         $menu['admin']['content']['rule']['title']    = $LANG['rulesengine'][17];
         $menu['admin']['content']['rule']['shortcut'] = 'r';
         $menu['admin']['content']['rule']['page']     = '/front/rule.php';

         if ($sector=='admin' && $item == 'rule') {
            foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
               $rulecollection = new $rulecollectionclass();
               if ($rulecollection->canList()) {
                  $ruleclassname = $rulecollection->getRuleClassName();
                  $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['title']
                                 = $rulecollection->getRuleClass()->getTitle();
                  $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['page']
                                 = Toolbox::getItemTypeSearchURL($ruleclassname,false);
                  $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['links']['search']
                                 = Toolbox::getItemTypeSearchURL($ruleclassname,false);
                  if ($rulecollection->canCreate()) {
                     $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['links']['add']
                                    = Toolbox::getItemTypeFormURL($ruleclassname,false);
                  }
               }
            }
         }
      }


      if (Session::haveRight("transfer","r" ) && Session::isMultiEntitiesMode()) {
         $menu['admin']['content']['rule']['options']['transfer']['title'] = $LANG['transfer'][1];
         $menu['admin']['content']['rule']['options']['transfer']['page']  = "/front/transfer.php";
         $menu['admin']['content']['rule']['options']['transfer']['links']['search']
                                                                           = "/front/transfer.php";

         if (Session::haveRight("transfer","w")) {
            $menu['admin']['content']['rule']['options']['transfer']['links']['summary']
                                                                        = "/front/transfer.action.php";
            $menu['admin']['content']['rule']['options']['transfer']['links']['add']
                                                                        = "/front/transfer.form.php";
         }
      }


      if (Session::haveRight("rule_dictionnary_dropdown","r")
          || Session::haveRight("rule_dictionnary_software","r")
          || Session::haveRight("rule_dictionnary_printer","r")) {

         $menu['admin']['content']['dictionnary']['title']    = $LANG['rulesengine'][77];
         $menu['admin']['content']['dictionnary']['shortcut'] = 'r';
         $menu['admin']['content']['dictionnary']['page']     = '/front/dictionnary.php';

         if ($sector=='admin' && $item == 'dictionnary') {
            $menu['admin']['content']['dictionnary']['options']['manufacturers']['title']
                           = $LANG['common'][5];
            $menu['admin']['content']['dictionnary']['options']['manufacturers']['page']
                           = '/front/ruledictionnarymanufacturer.php';
            $menu['admin']['content']['dictionnary']['options']['manufacturers']['links']['search']
                           = '/front/ruledictionnarymanufacturer.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['manufacturers']['links']['add']
                              = '/front/ruledictionnarymanufacturer.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['software']['title']
                           = $LANG['Menu'][4];
            $menu['admin']['content']['dictionnary']['options']['software']['page']
                           = '/front/ruledictionnarysoftware.php';
            $menu['admin']['content']['dictionnary']['options']['software']['links']['search']
                           = '/front/ruledictionnarysoftware.php';

            if (Session::haveRight("rule_dictionnary_software","w")) {
               $menu['admin']['content']['dictionnary']['options']['software']['links']['add']
                              = '/front/ruledictionnarysoftware.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['model.computer']['title']
                           = $LANG['setup'][91];
            $menu['admin']['content']['dictionnary']['options']['model.computer']['page']
                           = '/front/ruledictionnarycomputermodel.php';
            $menu['admin']['content']['dictionnary']['options']['model.computer']['links']['search']
                           = '/front/ruledictionnarycomputermodel.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['model.computer']['links']['add']
                              = '/front/ruledictionnarycomputermodel.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['model.monitor']['title']
                           = $LANG['setup'][94];
            $menu['admin']['content']['dictionnary']['options']['model.monitor']['page']
                           = '/front/ruledictionnarymonitormodel.php';
            $menu['admin']['content']['dictionnary']['options']['model.monitor']['links']['search']
                           = '/front/ruledictionnarymonitormodel.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['model.monitor']['links']['add']
                              = '/front/ruledictionnarymonitormodel.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['model.printer']['title']
                           = $LANG['setup'][96];
            $menu['admin']['content']['dictionnary']['options']['model.printer']['page']
                           = '/front/ruledictionnaryprintermodel.php';
            $menu['admin']['content']['dictionnary']['options']['model.printer']['links']['search']
                           = '/front/ruledictionnaryprintermodel.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['model.printer']['links']['add']
                              = '/front/ruledictionnaryprintermodel.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['model.peripheral']['title']
                           = $LANG['setup'][97];
            $menu['admin']['content']['dictionnary']['options']['model.peripheral']['page']
                           = '/front/ruledictionnaryperipheralmodel.php';
            $menu['admin']['content']['dictionnary']['options']['model.peripheral']['links']['search']
                           = '/front/ruledictionnaryperipheralmodel.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['model.peripheral']['links']['add']
                              = '/front/ruledictionnaryperipheralmodel.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['model.networking']['title']
                           = $LANG['setup'][95];
            $menu['admin']['content']['dictionnary']['options']['model.networking']['page']
                           = '/front/ruledictionnarynetworkequipmentmodel.php';
            $menu['admin']['content']['dictionnary']['options']['model.networking']['links']['search']
                           = '/front/ruledictionnarynetworkequipmentmodel.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['model.networking']['links']['add']
                              = '/front/ruledictionnarynetworkequipmentmodel.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['model.phone']['title']
                           = $LANG['setup'][503];
            $menu['admin']['content']['dictionnary']['options']['model.phone']['page']
                           = '/front/ruledictionnaryphonemodel.php';
            $menu['admin']['content']['dictionnary']['options']['model.phone']['links']['search']
                           = '/front/ruledictionnaryphonemodel.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['model.phone']['links']['add']
                              = '/front/ruledictionnaryphonemodel.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['type.computer']['title']
                           = $LANG['setup'][4];
            $menu['admin']['content']['dictionnary']['options']['type.computer']['page']
                           = '/front/ruledictionnarycomputertype.php';
            $menu['admin']['content']['dictionnary']['options']['type.computer']['links']['search']
                           = '/front/ruledictionnarycomputertype.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['type.computer']['links']['add']
                              = '/front/ruledictionnarycomputertype.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['type.monitor']['title']
                           = $LANG['setup'][44];
            $menu['admin']['content']['dictionnary']['options']['type.monitor']['page']
                           = '/front/ruledictionnarymonitortype.php';
            $menu['admin']['content']['dictionnary']['options']['type.monitor']['links']['search']
                           = '/front/ruledictionnarymonitortype.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['type.monitor']['links']['add']
                              = '/front/ruledictionnarymonitortype.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['type.printer']['title']
                           = $LANG['setup'][43];
            $menu['admin']['content']['dictionnary']['options']['type.printer']['page']
                           = '/front/ruledictionnaryprintertype.php';
            $menu['admin']['content']['dictionnary']['options']['type.printer']['links']['search']
                           = '/front/ruledictionnaryprintertype.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['type.printer']['links']['add']
                              = '/front/ruledictionnaryprintertype.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['type.peripheral']['title']
                           = $LANG['setup'][69];
            $menu['admin']['content']['dictionnary']['options']['type.peripheral']['page']
                           = '/front/ruledictionnaryperipheraltype.php';
            $menu['admin']['content']['dictionnary']['options']['type.peripheral']['links']['search']
                           = '/front/ruledictionnaryperipheraltype.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['type.peripheral']['links']['add']
                              = '/front/ruledictionnaryperipheraltype.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['type.networking']['title']
                           = $LANG['setup'][42];
            $menu['admin']['content']['dictionnary']['options']['type.networking']['page']
                           = '/front/ruledictionnarynetworkequipmenttype.php';
            $menu['admin']['content']['dictionnary']['options']['type.networking']['links']['search']
                           = '/front/ruledictionnarynetworkequipmenttype.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['type.networking']['links']['add']
                              = '/front/ruledictionnarynetworkequipmenttype.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['type.phone']['title']
                           = $LANG['setup'][504];
            $menu['admin']['content']['dictionnary']['options']['type.phone']['page']
                           = '/front/ruledictionnaryphonetype.php';
            $menu['admin']['content']['dictionnary']['options']['type.phone']['links']['search']
                           = '/front/ruledictionnaryphonetype.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['type.phone']['links']['add']
                              = '/front/ruledictionnaryphonetype.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['os']['title'] = $LANG['computers'][9];
            $menu['admin']['content']['dictionnary']['options']['os']['page']
                           = '/front/ruledictionnaryoperatingsystem.php';
            $menu['admin']['content']['dictionnary']['options']['os']['links']['search']
                           = '/front/ruledictionnaryoperatingsystem.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['os']['links']['add']
                              = '/front/ruledictionnaryoperatingsystem.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['os_sp']['title']
                           = $LANG['computers'][53];
            $menu['admin']['content']['dictionnary']['options']['os_sp']['page']
                           = '/front/ruledictionnaryoperatingsystemservicepack.php';
            $menu['admin']['content']['dictionnary']['options']['os_sp']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemservicepack.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['os_sp']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemservicepack.form.php';
            }


            $menu['admin']['content']['dictionnary']['options']['os_version']['title']
                           = $LANG['computers'][52];
            $menu['admin']['content']['dictionnary']['options']['os_version']['page']
                           = '/front/ruledictionnaryoperatingsystemversion.php';
            $menu['admin']['content']['dictionnary']['options']['os_version']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemversion.php';

            if (Session::haveRight("rule_dictionnary_dropdown","w")) {
               $menu['admin']['content']['dictionnary']['options']['os_version']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemversion.form.php';
            }

            $menu['admin']['content']['dictionnary']['options']['printer']['title']
                           = $LANG['rulesengine'][39];
            $menu['admin']['content']['dictionnary']['options']['printer']['page']
                           = '/front/ruledictionnaryprinter.php';
            $menu['admin']['content']['dictionnary']['options']['printer']['links']['search']
                           = '/front/ruledictionnaryprinter.php';

            if (Session::haveRight("rule_dictionnary_printer","w")) {
               $menu['admin']['content']['dictionnary']['options']['printer']['links']['add']
                              = '/front/ruledictionnaryprinter.form.php';
            }

         }
      }


      if (Session::haveRight("profile","r")) {
         $menu['admin']['content']['profile']['title']           = $LANG['Menu'][35];
         $menu['admin']['content']['profile']['shortcut']        = 'p';
         $menu['admin']['content']['profile']['page']            = '/front/profile.php';
         $menu['admin']['content']['profile']['links']['search'] = "/front/profile.php";

         if (Session::haveRight("profile","w")) {
            $menu['admin']['content']['profile']['links']['add'] = "/front/profile.form.php";
         }
      }

      if (Session::haveRight("backup","w")) {
         $menu['admin']['content']['backup']['title']    = $LANG['Menu'][12];
         $menu['admin']['content']['backup']['shortcut'] = 'b';
         $menu['admin']['content']['backup']['page']     = '/front/backup.php';
      }


      if (Session::haveRight("logs","r")) {
         $menu['admin']['content']['log']['title']    = $LANG['Menu'][30];
         $menu['admin']['content']['log']['shortcut'] = 'l';
         $menu['admin']['content']['log']['page']     = '/front/event.php';
      }



      /// CONFIG
      $config    = array();
      $addconfig = array();
      $menu['config']['title'] = $LANG['common'][12];

      if (Session::haveRight("dropdown","r") || Session::haveRight("entity_dropdown","r")) {
         $menu['config']['content']['dropdowns']['title'] = $LANG['setup'][0];
         $menu['config']['content']['dropdowns']['page']  = '/front/dropdown.php';

         $menu['config']['default'] = '/front/dropdown.php';

         if ($item=="dropdowns") {
            $dps = Dropdown::getStandardDropdownItemTypes();

            foreach ($dps as $tab) {
               foreach ($tab as $key => $val) {
                  if ($key == $option) {
                     $tmp = new $key();
                     $menu['config']['content']['dropdowns']['options'][$option]['title'] = $val;
                     $menu['config']['content']['dropdowns']['options'][$option]['page']
                                    = $tmp->getSearchURL(false);
                     $menu['config']['content']['dropdowns']['options'][$option]['links']['search']
                                    = $tmp->getSearchURL(false);
                     if ($tmp->canCreate()) {
                        $menu['config']['content']['dropdowns']['options'][$option]['links']['add']
                                       = $tmp->getFormURL(false);
                     }
                  }
               }
            }
         }
      }


      if (Session::haveRight("device","w")) {
         $menu['config']['content']['device']['title'] = Toolbox::ucfirst($LANG['log'][18]);
         $menu['config']['content']['device']['page']  = '/front/device.php';

         if ($item=="device") {
            $dps = Dropdown::getDeviceItemTypes();

            foreach ($dps as $tab) {
               foreach ($tab as $key => $val) {
                  if ($key == $option) {
                     $tmp = new $key();
                     $menu['config']['content']['device']['options'][$option]['title'] = $val;
                     $menu['config']['content']['device']['options'][$option]['page']
                                    = $tmp->getSearchURL(false);
                     $menu['config']['content']['device']['options'][$option]['links']['search']
                                    = $tmp->getSearchURL(false);
                     if ($tmp->canCreate()) {
                        $menu['config']['content']['device']['options'][$option]['links']['add']
                                       = $tmp->getFormURL(false);
                     }
                  }
               }
            }
         }
      }


      if (($CFG_GLPI['use_mailing'] && Session::haveRight("notification","r"))
          || Session::haveRight("config","w")) {
         $menu['config']['content']['mailing']['title'] = $LANG['setup'][704];
         $menu['config']['content']['mailing']['page']  = '/front/setup.notification.php';
         $menu['config']['content']['mailing']['options']['notification']['title']
                                                        = $LANG['setup'][704];
         $menu['config']['content']['mailing']['options']['notification']['page']
                                                        = '/front/notification.php';
         $menu['config']['content']['mailing']['options']['notification']['links']['add']
                                                        = '/front/notification.form.php';
         $menu['config']['content']['mailing']['options']['notification']['links']['search']
                                                        = '/front/notification.php';
      }


      if (Session::haveRight("sla","r")) {
         $menu['config']['content']['sla']['title']           = $LANG['Menu'][43];
         $menu['config']['content']['sla']['page']            = '/front/sla.php';
         $menu['config']['content']['sla']['links']['search'] = "/front/sla.php";
         if (Session::haveRight("sla","w")) {
            $menu['config']['content']['sla']['links']['add']    = "/front/sla.form.php";
         }
      }

      if (Session::haveRight("config","w")) {

         $menu['config']['content']['config']['title'] = $LANG['setup'][703];
         $menu['config']['content']['config']['page']  = '/front/config.form.php';

         $menu['config']['content']['control']['title'] = $LANG['Menu'][41];
         $menu['config']['content']['control']['page']  = '/front/control.php';

         $menu['config']['content']['control']['options']['FieldUnicity']['title']
                        = $LANG['setup'][811];
         $menu['config']['content']['control']['options']['FieldUnicity']['page']
                        = '/front/fieldunicity.php';
         $menu['config']['content']['control']['options']['FieldUnicity']['links']['add']
                        = '/front/fieldunicity.form.php';
         $menu['config']['content']['control']['options']['FieldUnicity']['links']['search']
                        = '/front/fieldunicity.php';

         $menu['config']['content']['crontask']['title']           = $LANG['crontask'][0];
         $menu['config']['content']['crontask']['page']            = '/front/crontask.php';
         $menu['config']['content']['crontask']['links']['search'] = "/front/crontask.php";

         $menu['config']['content']['mailing']['options']['config']['title'] = $LANG['mailing'][118];
         $menu['config']['content']['mailing']['options']['config']['page']
                        = '/front/notificationmailsetting.form.php';

         $menu['config']['content']['mailing']['options']['notificationtemplate']['title']
                        = $LANG['mailing'][113];
         $menu['config']['content']['mailing']['options']['notificationtemplate']['page']
                        = '/front/notificationtemplate.php';
         $menu['config']['content']['mailing']['options']['notificationtemplate']['links']['add']
                        = '/front/notificationtemplate.form.php';
         $menu['config']['content']['mailing']['options']['notificationtemplate']['links']['search']
                        = '/front/notificationtemplate.php';

         $menu['config']['content']['extauth']['title'] = $LANG['login'][10];
         $menu['config']['content']['extauth']['page']  = '/front/setup.auth.php';

         $menu['config']['content']['extauth']['options']['ldap']['title'] = $LANG['Menu'][9];
         $menu['config']['content']['extauth']['options']['ldap']['page']  = '/front/authldap.php';

         $menu['config']['content']['extauth']['options']['imap']['title'] = $LANG['Menu'][10];
         $menu['config']['content']['extauth']['options']['imap']['page']  = '/front/authmail.php';

         $menu['config']['content']['extauth']['options']['others']['title'] = $LANG['common'][67];
         $menu['config']['content']['extauth']['options']['others']['page']  = '/front/auth.others.php';

         $menu['config']['content']['extauth']['options']['settings']['title'] = $LANG['common'][12];
         $menu['config']['content']['extauth']['options']['settings']['page']
                        = '/front/auth.settings.php';

         switch ($option) {
            case "ldap" : // LDAP
               $menu['config']['content']['extauth']['options']['ldap']['links']['search']
                              = '/front/authldap.php';
               $menu['config']['content']['extauth']['options']['ldap']['links']['add']
                              = '' .'/front/authldap.form.php';
               break;

            case "imap" : // IMAP
               $menu['config']['content']['extauth']['links']['search'] = '/front/authmail.php';
               $menu['config']['content']['extauth']['links']['add']    = '' .'/front/authmail.form.php';
               break;
         }

         $menu['config']['content']['mailcollector']['title'] = $LANG['Menu'][39];
         $menu['config']['content']['mailcollector']['page']  = '/front/mailcollector.php';

         if (Toolbox::canUseImapPop()) {
            $menu['config']['content']['mailcollector']['links']['search'] = '/front/mailcollector.php';
            $menu['config']['content']['mailcollector']['links']['add']
                                       = '/front/mailcollector.form.php';
            $menu['config']['content']['mailcollector']['options']['rejectedemails']['links']['search']
                                       = '/front/notimportedemail.php';
         }
      }


      if ($CFG_GLPI["use_ocs_mode"] && Session::haveRight("config","w")) {
         $menu['config']['content']['ocsng']['title']           = $LANG['ocsconfig'][24];
         $menu['config']['content']['ocsng']['page']            = '/front/ocsserver.php';
         $menu['config']['content']['ocsng']['links']['search'] = '/front/ocsserver.php';
         $menu['config']['content']['ocsng']['links']['add']    = '/front/ocsserver.form.php';
      }


      if (Session::haveRight("link","r")) {
         $menu['config']['content']['link']['title']           = $LANG['title'][33];
         $menu['config']['content']['link']['page']            = '/front/link.php';
         $menu['config']['content']['link']['hide']            = true;
         $menu['config']['content']['link']['links']['search'] = '/front/link.php';

         if (Session::haveRight("link","w")) {
            $menu['config']['content']['link']['links']['add'] = "/front/link.form.php";
         }
      }


      if (Session::haveRight("config","w")) {
         $menu['config']['content']['plugins']['title'] = $LANG['common'][29];
         $menu['config']['content']['plugins']['page']  = '/front/plugin.php';
      }



      // Special items
      $menu['preference']['title']   = $LANG['Menu'][11];
      $menu['preference']['default'] = '/front/preference.php';

      echo "<div id='header'>";
      echo "<div id='c_logo'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/central.php' title=\"".$LANG['central'][5]."\"></a>";
      echo "</div>";

      /// Prefs / Logout link
      echo "<div id='c_preference' >";
      echo "<ul>";

      echo "<li id='deconnexion'><a href='".$CFG_GLPI["root_doc"]."/logout.php";

      /// logout witout noAuto login for extauth
      if (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth']) {
         echo "?noAUTO=1";
      }
      echo "' title=\"".$LANG['central'][6]."\">".$LANG['central'][6]."</a>";

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         echo " (";
         echo formatUserName (0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                              $_SESSION["glpifirstname"], 0, 20);
         echo ")";
      }
      echo "</li>\n";

      echo "<li><a href='".
            (empty($CFG_GLPI["central_doc_url"])?"http://glpi-project.org/help-central":
            $CFG_GLPI["central_doc_url"])."' target='_blank' title=\"".$LANG['central'][7]."\">".
            $LANG['central'][7]."</a></li>";

      echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
                 $LANG['Menu'][11]."\">".$LANG['Menu'][11]."</a></li>";

      echo "</ul>";
      echo "<div class='sep'></div>";
      echo "</div>\n";

      /// Search engine
      echo "<div id='c_recherche' >\n";
      echo "<form method='get' action='".$CFG_GLPI["root_doc"]."/front/search.php'>\n";
      echo "<div id='boutonRecherche'>";
      echo "<input type='image' src='".$CFG_GLPI["root_doc"]."/pics/ok2.png' value='OK' title=\"".
             $LANG['buttons'][2]."\"  alt=\"".$LANG['buttons'][2]."\"></div>";
      echo "<div id='champRecherche'><input size='15' type='text' name='globalsearch' value='".
             $LANG['buttons'][0]."' onfocus=\"this.value='';\"></div>";
      echo "</form>";

      echo "<div class='sep'></div>\n";
      echo "</div>";

      ///Main menu
      echo "<div id='c_menu'>";
      echo "<ul id='menu'>";

      // Get object-variables and build the navigation-elements
      $i = 1;
      foreach ($menu as $part => $data) {
         if (isset($data['content']) && count($data['content'])) {
            echo "<li id='menu$i' onmouseover=\"javascript:menuAff('menu$i','menu');\" >";
            $link = "#";

            if (isset($data['default'])&&!empty($data['default'])) {
               $link = $CFG_GLPI["root_doc"].$data['default'];
            }

            if (Toolbox::strlen($data['title'])>14) {
               $data['title'] = Toolbox::substr($data['title'], 0, 14)."...";
            }
            echo "<a href='$link' class='itemP'>".$data['title']."</a>";
            echo "<ul class='ssmenu'>";

            // list menu item
            foreach ($data['content'] as $key => $val) {
               if (isset($val['page'])&&isset($val['title'])) {
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

                  if (isset($data['shortcut'])&&!empty($data['shortcut'])) {
                     echo " accesskey='".$val['shortcut']."'";
                  }
                  echo ">".$val['title']."</a></li>\n";
               }
            }
            echo "</ul></li>";
            $i++;
         }
      }

      echo "</ul>";
      echo "<div class='sep'></div>";
      echo "</div>";

      // End navigation bar
      // End headline
      // Le sous menu contextuel 1
      echo "<div id='c_ssmenu1' >";
      echo "<ul>";

      // list sous-menu item
      if (isset($menu[$sector])) {
         if (isset($menu[$sector]['content']) && is_array($menu[$sector]['content'])) {
            $ssmenu = $menu[$sector]['content'];

            if (count($ssmenu)>12) {
               foreach ($ssmenu as $key => $val) {
                  if (isset($val['hide'])) {
                     unset($ssmenu[$key]);
                  }
               }
               $ssmenu = array_splice($ssmenu,0,12);
            }

            foreach ($ssmenu as $key => $val) {
               if (isset($val['page'])&&isset($val['title'])) {
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

                  if (isset($val['shortcut'])&&!empty($val['shortcut'])) {
                     echo " accesskey='".$val['shortcut']."'";
                  }
                  echo ">".$val['title']."</a></li>\n";
               }
            }

         } else {
            echo "<li>&nbsp;</li>";
         }

      } else {
         echo "<li>&nbsp;</li>";
      }
      echo "</ul></div>";

      //  Le fil d ariane
      echo "<div id='c_ssmenu2' >";
      echo "<ul>";

      // Display item
      echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/central.php' title=\"".$LANG['central'][5]."\">".
                 $LANG['central'][5]."</a> ></li>";

      if (isset($menu[$sector])) {
         $link = "/front/central.php";

         if (isset($menu[$sector]['default'])) {
            $link = $menu[$sector]['default'];
         }
         echo "<li><a href='".$CFG_GLPI["root_doc"].$link."' title=\"".$menu[$sector]['title']."\">".
                    $menu[$sector]['title']."</a> ></li>";
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
            echo "<li><a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['page']."' ".
                       ($with_option?"":"class='here'")." title=\"".
                       $menu[$sector]['content'][$item]['title']."\" >".
                       $menu[$sector]['content'][$item]['title']."</a>".(!$with_option?"":" > ")."</li>";
         }

         if ($with_option) {
            echo "<li><a href='".$CFG_GLPI["root_doc"].
                       $menu[$sector]['content'][$item]['options'][$option]['page'].
                       "' class='here' title=\"".
                       $menu[$sector]['content'][$item]['options'][$option]['title']."\" >";
            echo self::resume_name($menu[$sector]['content'][$item]['options'][$option]['title'], 17);
            echo "</a></li>";
         }

         echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

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
         echo "<li>";
         if (isset($links['add'])) {
            echo "<a href='".$CFG_GLPI["root_doc"].$links['add']."'>";
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png' title=\"".
                   $LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"></a>";

         } else {
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add_off.png' title=\"".
                   $LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\">";
         }
         echo "</li>";

         // Search Item
         if (isset($links['search'])) {
            echo "<li><a href='".$CFG_GLPI["root_doc"].$links['search']."'>";
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_search.png' title=\"".
                   $LANG['buttons'][0]."\" alt=\"".$LANG['buttons'][0]."\"></a></li>";

         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/menu_search_off.png' title=\"".
                       $LANG['buttons'][0]."\" alt=\"".$LANG['buttons'][0]."\"></li>";
         }
         // Links
         if (count($links)>0) {
            foreach ($links as $key => $val) {

               switch ($key) {
                  case "add" :
                  case "search" :
                     break;

                  case "template" :
                     echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                                $LANG['common'][8]."\" alt=\"".$LANG['common'][8]."\" src='".
                                $CFG_GLPI["root_doc"]."/pics/menu_addtemplate.png'></a></li>";
                     break;

                  case "showall" :
                     echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                                $LANG['buttons'][40]."\" alt=\"".$LANG['buttons'][40]."\" src='".
                                $CFG_GLPI["root_doc"]."/pics/menu_showall.png'></a></li>";
                     break;

                  case "summary" :
                     echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                                $LANG['state'][1]."\" alt=\"".$LANG['state'][1]."\" src='".
                                $CFG_GLPI["root_doc"]."/pics/menu_show.png'></a></li>";
                     break;

                  case "config" :
                     echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                                $LANG['common'][12]."\" alt=\"".$LANG['common'][12]."\" src='".
                                $CFG_GLPI["root_doc"]."/pics/menu_config.png'></a></li>";
                     break;

                  default :
                     echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'>".$key."</a></li>";
                     break;
               }
            }
         }

      } else {
         echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";
         echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
               "&nbsp;&nbsp;&nbsp;&nbsp;</li>";
      }

      // Add common items
      echo "<li>";
      // Display MENU ALL
      echo "<div id='show_all_menu' onmouseover=\"completecleandisplay('show_all_menu');\">";
      $items_per_columns = 15;
      $i = -1;
      echo "<table><tr><td class='top'><table>";

      foreach ($menu as $part => $data) {
         if (isset($data['content']) && count($data['content'])) {

            if ($i>$items_per_columns) {
               $i = 0;
               echo "</table></td><td class='top'><table>";
            }
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

               if ($i>$items_per_columns) {
                  $i = 0;
                  echo "</table></td><td class='top'><table>";
               }

               if (isset($val['page']) && isset($val['title'])) {
                  echo "<tr><td><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

                  if (isset($data['shortcut']) && !empty($data['shortcut'])) {
                     echo " accesskey='".$val['shortcut']."'";
                  }
                  echo ">".$val['title']."</a></td></tr>\n";
                  $i++;
               }
            }
         }
      }
      echo "</table></td></tr></table>";

      echo "</div>";
      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      echo "</li>";

      /// Bookmark load
      echo "<li>";
      echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
             "/front/popup.php?popup=load_bookmark' ,'glpibookmarks', 'height=500, width=".
             (Bookmark::WIDTH+250).", top=100, left=100, scrollbars=yes' );w.focus();\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark.png' title=\"".$LANG['buttons'][52]." ".
             $LANG['bookmark'][1]."\"  alt=\"".$LANG['buttons'][52]." ".$LANG['bookmark'][1]."\">";
      echo "</a></li>";

      /// MENU ALL
      echo "<li >";
      echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/menu_all.png' ".
             "onclick=\"completecleandisplay('show_all_menu')\">";
      echo "</li>";
      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         showProfileSelecter($CFG_GLPI["root_doc"]."/front/central.php");
      }
      echo "</ul>";
      echo "</div>";

      echo "</div>\n"; // fin header

      echo "<div id='page' >";

      if ($DB->isSlave() && !$DB->first_connection) {
         echo "<div id='dbslave-float'>";
         echo "<a href='#see_debug'>".$LANG['setup'][809]."</a>";
         echo "</div>";
      }

      // call static function callcron() every 5min
      CronTask::callCron();
      displayMessageAfterRedirect();
   }


   /**
    * Print footer for every page
    *
    * @param $keepDB booleen, closeDBConnections if false
   **/
   static function footer($keepDB=false) {
      global $LANG, $CFG_GLPI, $FOOTER_LOADED, $TIMER_DEBUG;

      // Print foot for every page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

      echo "<div id='footer' >";
      echo "<table width='100%'><tr><td class='left'><span class='copyright'>";
      echo $TIMER_DEBUG->getTime()." s - ";

      if (function_exists("memory_get_usage")) {
         echo Toolbox::getSize(memory_get_usage());
      }
      echo "</span></td>";

      if (!empty($CFG_GLPI["founded_new_version"])) {
         echo "<td class='copyright'>".$LANG['setup'][301].
               "<a href='http://www.glpi-project.org' target='_blank' title=\"".$LANG['setup'][302]."\"> ".
                  preg_replace('/0$/','',$CFG_GLPI["founded_new_version"])."</a></td>";
      }
      echo "<td class='right'>";
      echo "<a href='http://glpi-project.org/'>";
      echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y").
             " by the INDEPNET Development Team.</span>";
      echo "</a></td>";
      echo "</tr></table></div>";

      if ($_SESSION['glpi_use_mode']==TRANSLATION_MODE) { // debug mode traduction
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI MODE TRANSLATION</a>";
         echo "</div>";
      }

      if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI MODE DEBUG</a>";
         echo "</div>";
      }
      displayDebugInfos();
      echo "</body></html>";

      if (!$keepDB) {
         closeDBConnections();
      }
   }


   /**
    * Display Ajax Footer for debug
   **/
   static function ajaxFooter() {

      if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
         $rand = mt_rand();
         echo "<div class='center' id='debugajax'>";
         echo "<a class='debug-float' href=\"javascript:showHideDiv('see_ajaxdebug$rand','','','');\">
                AJAX DEBUG</a></div>";
         echo "<div id='see_ajaxdebug$rand' name='see_ajaxdebug$rand' style=\"display:none;\">";
         displayDebugInfos(false);
         echo "</div></div>";
      }
   }


   /**
    * Print a nice HTML head for help page
    *
    * @param $title title of the page
    * @param $url not used anymore.
   **/
   static function helpHeader($title, $url='') {
      global $CFG_GLPI, $LANG, $HEADER_LOADED, $PLUGIN_HOOKS;

      // Print a nice HTML-head for help page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      includeCommonHtmlHeader($title);

      // Body
      echo "<body>";

      // Main Headline
      echo "<div id='header'>";
      echo "<div id='c_logo' >";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' accesskey='0' title=\"".
             $LANG['central'][5]."\"><span class='invisible'>Logo</span></a></div>";

      // Les préférences + lien déconnexion
      echo "<div id='c_preference' >";
      echo "<ul><li id='deconnexion'><a href='".$CFG_GLPI["root_doc"]."/logout.php' title=\"".
                                      $LANG['central'][6]."\">".$LANG['central'][6]."</a>";

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         echo "&nbsp;(";
         echo formatUserName (0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                              $_SESSION["glpifirstname"], 0, 20);
         echo ")";
      }
      echo "</li>\n";

      echo "<li><a href='".(empty($CFG_GLPI["helpdesk_doc_url"])?
                 "http://glpi-project.org/help-helpdesk":$CFG_GLPI["helpdesk_doc_url"]).
                 "' target='_blank' title=\"".$LANG['central'][7]."\"> ".$LANG['central'][7].
           "</a></li>";
      echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
                  $LANG['Menu'][11]."\">".$LANG['Menu'][11]."</a></li>\n";

      echo "</ul>";
      echo "<div class='sep'></div>";
      echo "</div>";

      //-- Le moteur de recherche --
      echo "<div id='c_recherche'>";
      echo "<div class='sep'></div>";
      echo "</div>";

      //-- Le menu principal --
      echo "<div id='c_menu'>";
      echo "<ul id='menu'>";

      // Build the navigation-elements

      // Home
      echo "<li id='menu1'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' title=\"".
             $LANG['job'][13]."\" class='itemP'>".$LANG['central'][5]."</a>";
      echo "</li>";

      //  Create ticket
      if (Session::haveRight("create_ticket","1")) {
         echo "<li id='menu2'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1' title=\"".
                $LANG['profiles'][5]."\" class='itemP'>".$LANG['profiles'][5]."</a>";
         echo "</li>";
      }

      //  Suivi ticket
      if (Session::haveRight("observe_ticket","1")) {
         echo "<li id='menu2'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php' title=\"".
                $LANG['title'][10]."\" class='itemP'>".$LANG['title'][28]."</a>";
         echo "</li>";
      }

      // Reservation
      if (Session::haveRight("reservation_helpdesk","1")) {
         echo "<li id='menu3'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/reservationitem.php' title=\"".
                $LANG['Menu'][17]."\" class='itemP'>".$LANG['Menu'][17]."</a>";
         echo "</li>";
      }

      // FAQ
      if (Session::haveRight("faq","r")) {
         echo "<li id='menu4' >";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.faq.php' title=\"".
                $LANG['knowbase'][1]."\" class='itemP'>".$LANG['Menu'][20]."</a>";
         echo "</li>";
      }

      // PLUGINS
      $plugins = array();
      if (isset($PLUGIN_HOOKS["helpdesk_menu_entry"])
          && count($PLUGIN_HOOKS["helpdesk_menu_entry"])) {

         foreach ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {
            if ($active) {
               $function = "plugin_version_$plugin";

               if (function_exists($function)) {
                  $plugins[$plugin] = $function();
               }
            }
         }
      }

      if (isset($plugins) && count($plugins)>0) {
         $list = array();

         foreach ($plugins as $key => $val) {
            $list[$key] = $val["name"];
         }

         asort($list);
         echo "<li id='menu5' onmouseover=\"javascript:menuAff('menu5','menu');\">";
         echo "<a href='#' title=\"".$LANG['common'][29]."\" class='itemP'>".
                $LANG['common'][29]."</a>";  // default none
         echo "<ul class='ssmenu'>";

         // list menu item
         foreach ($list as $key => $val) {
            $link = "";

            if (is_string($PLUGIN_HOOKS["helpdesk_menu_entry"][$key])) {
               $link = $PLUGIN_HOOKS["helpdesk_menu_entry"][$key];
            }
            echo "<li><a href='".$CFG_GLPI["root_doc"]."/plugins/".$key.$link."'>".
                       $plugins[$key]["name"]."</a></li>\n";
         }
         echo "</ul></li>";
      }
      echo "</ul>";
      echo "<div class='sep'></div>";

      echo "</div>";

      // End navigation bar
      // End headline
      ///Le sous menu contextuel 1
      echo "<div id='c_ssmenu1'>&nbsp;</div>";

      //  Le fil d ariane
      echo "<div id='c_ssmenu2'>";
      echo "<ul>";
      echo "<li><a href='#' title=''>".$LANG['central'][5]."></a></li>";
      echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

      if (Session::haveRight('validate_ticket',1)) {
         $opt = array();
         $opt['reset']         = 'reset';
         $opt['field'][0]      = 55; // validation status
         $opt['searchtype'][0] = 'equals';
         $opt['contains'][0]   = 'waiting';
         $opt['link'][0]       = 'AND';

         $opt['field'][1]      = 59; // validation aprobator
         $opt['searchtype'][1] = 'equals';
         $opt['contains'][1]   = Session::getLoginUserID();
         $opt['link'][1]       = 'AND';


         $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".Toolbox::append_params($opt,'&amp;');
         $pic_validate = "<a href='$url_validate'><img title=\"".$LANG['validation'][15]."\" alt=\"".
                           $LANG['validation'][15]."\" src='".
                           $CFG_GLPI["root_doc"]."/pics/menu_showall.png'></a>";
         echo "<li>$pic_validate</li>\n";

      }
      echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

      if (Session::haveRight('create_ticket',1) && strpos($_SERVER['PHP_SELF'],"ticket")) {
         echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png' title=\"".$LANG['buttons'][8].
                "\" alt=\"".$LANG['buttons'][8]."\"></a></li>";
      }

      echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

      /// Bookmark load
      echo "<li>";
      echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
             "/front/popup.php?popup=load_bookmark' ,'glpibookmarks', 'height=400, width=600, ".
             "top=100, left=100, scrollbars=yes' );w.focus();\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark.png' title=\"".$LANG['buttons'][52]." ".
             $LANG['bookmark'][1]."\" alt=\"".$LANG['buttons'][52]." ".$LANG['bookmark'][1]."\">";
      echo "</a></li>";

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         showProfileSelecter($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
      }
      echo "</ul></div>";

      echo "</div>"; // fin header
      echo "<div id='page' >";

      // call static function callcron() every 5min
      CronTask::callCron();
      displayMessageAfterRedirect();
   }


   /**
    * Print footer for help page
   **/
   static function helpFooter() {
      global $LANG, $CFG_GLPI, $FOOTER_LOADED;

      // Print foot for help page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

      echo "<div id='footer'>";
      echo "<table width='100%'><tr><td class='right'>";
      echo "<a href='http://glpi-project.org/'>";
      echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y").
             " by the INDEPNET Development Team.</span>";
      echo "</a></td></tr></table></div>";

      if ($_SESSION['glpi_use_mode']==TRANSLATION_MODE) { // debug mode traduction
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI MODE TRANSLATION</a>";
         echo "</div>";
      }

      if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
         echo "<div id='debug-float'>";
         echo "<a href='#see_debug'>GLPI MODE DEBUG</a>";
         echo "</div>";
      }
      displayDebugInfos();
      echo "</body></html>";
      closeDBConnections();
   }


   /**
    * Print a nice HTML head with no controls
    *
    * @param $title title of the page
    * @param $url not used anymore.
   **/
   static function nullHeader($title, $url='') {
      global $CFG_GLPI, $HEADER_LOADED, $LANG;

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
      header_nocache();

      if (isCommandLine()) {
         return true;
      }

      includeCommonHtmlHeader($title);

      // Body with configured stuff
      echo "<body>";
      echo "<div id='page'>";
      echo "<div id='bloc'>";
      echo "<div class='haut'></div>";
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
         echo "<div class='bas'></div></div></div>";

         echo "<div id='footer-login'>";
         echo "<a href='http://glpi-project.org/' title='Powered By Indepnet'>";
         echo 'GLPI version '.(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").
              ' Copyright (C) 2003-'.date("Y").' INDEPNET Development Team.';
         echo "</a></div>";

         echo "</body></html>";
      }
      closeDBConnections();
   }


   /**
    * Print a nice HTML head for popup window (nothing to display)
    *
    * @param $title title of the page
    * @param $url not used anymore.
   **/
   static function popHeader($title, $url='') {
      global $CFG_GLPI, $LANG, $PLUGIN_HOOKS, $HEADER_LOADED;

      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      includeCommonHtmlHeader($title); // Body
      echo "<body>";
      displayMessageAfterRedirect();
   }


   /**
    * Print footer for a popup window
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


}
?>