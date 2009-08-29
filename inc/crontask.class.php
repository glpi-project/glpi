<?php

/*
 * @version $Id: bookmark.class.php 8095 2009-03-19 18:27:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * CronTask class
 */
class CronTask extends CommonDBTM{

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_crontasks";
      $this->type=CRONTASK_TYPE;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['Menu'][13]; // Stat
      $ong[2]=$LANG['Menu'][30]; // Logs

      return $ong;
   }

   /**
    * Print the contact form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {

      global $CFG_GLPI, $LANG;

      if (!haveRight("config","r") || !$this->getFromDB($ID)) {
         return false;
      }

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]." : </td>";
      echo "<td><strong>";
      if (!empty($this->fields["module"])) {
         echo $this->fields["module"]." - ";
      }
      echo $this->fields["name"]."</strong></td>";
      $rowspan=6;
      echo "<td rowspan='$rowspan' class='middle right'>".$LANG['common'][25].
         "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='$rowspan'>.<textarea cols='45' ".
         "rows='$rowspan' name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][30]." : </td><td>";
      echo $this->getDescription($ID,$this->fields["module"],$this->fields["name"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][37]." : </td><td>";
      dropdownFrequency('frequency',$this->fields["frequency"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['joblist'][0]." : </td><td>";
      if ($this->fields["state"]==CRONTASK_STATE_RUNNING) {
         echo "<strong>" . $this->getStateName(CRONTASK_STATE_RUNNING);
      } else {
         dropdownArrayValues('state',
            array(CRONTASK_STATE_DISABLE=>$this->getStateName(CRONTASK_STATE_DISABLE),
                  CRONTASK_STATE_WAITING=>$this->getStateName(CRONTASK_STATE_WAITING)),
            $this->fields["state"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][36]." : </td><td>";
      $modes=array();
      if ($this->fields['allowmode']&CRONTASK_MODE_INTERNAL) {
         $modes[CRONTASK_MODE_INTERNAL]=$this->getModeName(CRONTASK_MODE_INTERNAL);
      }
      if ($this->fields['allowmode']&CRONTASK_MODE_EXTERNAL) {
         $modes[CRONTASK_MODE_EXTERNAL]=$this->getModeName(CRONTASK_MODE_EXTERNAL);
      }
      dropdownArrayValues('mode', $modes, $this->fields['mode']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][38]." : </td><td>";
      echo $LANG['buttons'][33]."&nbsp;:&nbsp;";
      dropdownInteger('hourmin', $this->fields['hourmin'],0,24);
      echo "  ".$LANG['buttons'][32]."&nbsp;:&nbsp;";
      dropdownInteger('hourmax', $this->fields['hourmax'],0,24);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['setup'][109]." : </td><td>";
      dropdownInteger('logs_lifetime', $this->fields['logs_lifetime'],0,360,10);
      echo "</td><td>".$LANG['crontask'][40]."&nbsp;:</td><td>";
      echo (empty($this->fields['lastrun'])
         ? $LANG['setup'][307] : convDateTime($this->fields['lastrun']));
      echo "</td></tr>";

      $label = $this->getParameterDescription($ID,$this->fields["module"],$this->fields["name"]);
      echo "<tr class='tab_bg_1'><td>";
      if (empty($label)) {
         echo "&nbsp;</td><td>&nbsp;";
      } else {
         echo $label."&nbsp;:</td><td>";
         dropdownInteger('param', $this->fields['param'],0,400,1);
      }
      echo "</td><td>".$LANG['crontask'][41]."&nbsp;:</td><td>";
      if ($this->fields["state"]==CRONTASK_STATE_DISABLE) {
         echo $this->getStateName(CRONTASK_STATE_DISABLE);
      } else if (empty($this->fields['lastrun'])) {
         echo $LANG['crontask'][41];
      } else {
         $next = strtotime($this->fields['lastrun'])+$this->fields['frequency'];
         $disp = date("Y-m-d H:i:s", $next);
         if ($next<time()) {
            echo $LANG['crontask'][42].' ('.convDateTime($disp).')';
         } else {
            echo convDateTime($disp);
         }
      }
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2,false);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }


   /**
    * Translate task description
    *
    * @param $id integer
    * @param $module string : name of plugin (empty for glpi core task)
    * @param $name string : name of the task
    * @return string
    */
   static public function getDescription($id, $module, $name) {
      global $LANG;

      if (empty($module)) {
         if ($id>=1 && $id<=12) {
            return $LANG['crontask'][$id];
         }
         return $LANG['crontask'][30].' '.$id;
      }
      // TODO plugin case
   }

   /**
    * Translate task parameter description
    *
    * @param $id integer
    * @param $module string : name of plugin (empty for glpi core task)
    * @param $name string : name of the task
    * @return string
    */
   static public function getParameterDescription($id, $module, $name) {
      global $LANG;

      if (empty($module)) {
         switch ($id) {
            case 1: // ocsng
               return $LANG['ocsconfig'][40];
               break;
            case 7: // events
               return $LANG['setup'][109];
               break;
            case 9: // mailgate
               return $LANG['crontask'][39];
               break;
            case 10: // dbreplicate
               return $LANG['setup'][806];
               break;
         }
         // No parameter
         return '';
      }
      // TODO plugin case
   }

   /**
    * Translate state to string
    *
    * @param $state integer
    * @return string
    */
   static public function getStateName($state) {
      global $LANG;

      switch ($state) {
         case CRONTASK_STATE_RUNNING:
            return $LANG['crontask'][33];
            break;
         case CRONTASK_STATE_WAITING:
            return $LANG['crontask'][32];
            break;
         case CRONTASK_STATE_DISABLE:
            return $LANG['crontask'][31];
            break;
      }
      return '???';
   }

   /**
    * Translate Mode to string
    *
    * @param $mode integer
    * @return string
    */
   static public function getModeName($mode) {
      global $LANG;

      switch ($mode) {
         case CRONTASK_MODE_INTERNAL:
            return $LANG['crontask'][34];
            break;
         case CRONTASK_MODE_EXTERNAL:
            return $LANG['crontask'][35];
            break;
      }
      return '???';
   }
}

?>