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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class SLA
class SlaLevel extends RuleTicket {

   protected $rules_id_field    = 'slalevels_id';
   protected $ruleactionclass   = 'SlaLevelAction';
   // No criteria
   protected $rulecriteriaclass = '';
   
   public $right='sla';

   /**
   * Constructor
   **/
   function __construct() {
      // Override in order not to use glpi_rules table.
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['sla'][15];
      }
      return $LANG['sla'][6];
   }

   function cleanDBonPurge() {
      global $DB;


      parent::cleanDBOnPurge();

      $sql = "DELETE
              FROM `glpi_slalevels_tickets`
              WHERE `".$this->rules_id_field."` = '".$this->fields['id']."'";
      $DB->query($sql);

   }

   function showForSLA(SLA $sla) {

      global $DB,$CFG_GLPI, $LANG;

      $ID = $sla->getField('id');
      if (!$sla->can($ID,'r')) {
         return false;
      }

      $canedit = $sla->can($ID,'w');

      $rand=mt_rand();
      echo "<form name='slalevel_form$rand' id='slalevel_form$rand' method='post' action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

      if ($canedit) {
         echo "<div class='center first-bloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['sla'][4]."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>".$LANG['common'][16]."&nbsp;: ";
         echo "<input type='hidden' name='slas_id' value='$ID'>";
         echo "<input type='hidden' name='entities_id' value='".$sla->getEntityID()."'>";
         echo "<input type='hidden' name='is_recursive' value='".$sla->isRecursive()."'>";
         echo "<input  name='name' value=''>";
         echo "</td><td class='center'>".$LANG['sla'][3]."&nbsp;: ";

         self::dropdownExecutionTime('execution_time',
                        array('max_time'  => $sla->fields['resolution_time'],
                              'used'      => self::getAlreadyUsedExecutionTime($sla->fields['id'])));

         echo "</td><td class='center'>".$LANG['common'][60]."&nbsp;: ";
         Dropdown::showYesNo("is_active",1);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";

         echo "</table></div>";

         $query = "SELECT *
                  FROM `glpi_slalevels`
                  WHERE `slas_id` = '$ID'
                  ORDER BY `execution_time`";
         $result = $DB->query($query);

         if ($DB->numrows($result) >0) {
            echo "<div class='center'><table class='tab_cadre_fixehov'>";
            echo "<tr><th colspan='2'>".$LANG['common'][16]."</th>";
            echo "<th>".$LANG['sla'][3]."</th>";
            echo "<th>".$LANG['common'][60]."</th>";
            echo "</tr>";
            Session::initNavigateListItems('SlaLevel', $LANG['sla'][1]." - ".$sla->getName());

            while ($data = $DB->fetch_array($result)) {

               Session::addToNavigateListItems('SlaLevel',$data["id"]);

               echo "<tr class='tab_bg_2'>";
               echo "<td width='10'>";
               if ($canedit) {
                  echo "<input type='checkbox' name='item[".$data["id"]."]' value='1'>";
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";

               echo "<td>";
               if ($canedit) {
                  echo "<a href='".Toolbox::getItemTypeFormURL('SlaLevel')."?id=".$data["id"]."'>";
               }
                  echo $data["name"];
                  if (empty($data["name"])) {
                     echo "(".$data['id'].")";
                  }
               if ($canedit) {
                  echo "</a>";
               }
               echo "</td>";
               echo "<td>".($data["execution_time"]<>0?Html::timestampToString($data["execution_time"],
                                                                               false)
                                                      :$LANG['sla'][5])."</td>";
               echo "<td>".Dropdown::getYesNo($data["is_active"])."</td>";
               echo "</tr>";
               echo "<tr class='tab_bg_1'><td colspan='4'>";
               $this->getRuleWithCriteriasAndActions($data['id'],0,1);
               $this->showActionsList($data["id"],array('readonly'=>true));
               echo "</td></tr>";
            }

            Html::openArrowMassives("slalevel_form$rand",true);
            Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));

            echo "</table></div>";
         }
      }
      Html::closeForm();

   }


   function getActions() {
      global $LANG;

      $actions = parent::getActions();
      unset($actions['slas_id']);
      $actions['recall']['name'] = $LANG['sla'][9];
      $actions['recall']['type'] = 'yesonly';
      $actions['recall']['force_actions'] = array('send');
      return $actions;
   }


   /**
   * Show the rule
   *
   * @param $ID ID of the rule
   * @param $options options
   *
   * @return nothing
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;


      if (!$this->isNewID($ID)) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $canedit=$this->can($this->right,"w");

      $this->showTabs($options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"name");
      echo "</td>";
      echo "<td>".$LANG['common'][60]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo"</td></tr>\n";

      $sla=new SLA();
      $sla->getFromDB($this->fields['slas_id']);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['sla'][1]."&nbsp;:</td>";
      echo "<td>";
      echo $sla->getLink();
      echo "</td>";
      echo "<td>".$LANG['sla'][3]."</td>";
      echo "<td>";

      self::dropdownExecutionTime('execution_time',
                     array('max_time'  => $sla->fields['resolution_time'],
                           'used'      => self::getAlreadyUsedExecutionTime($sla->fields['id']),
                           'value'     => $this->fields['execution_time']));
      echo "</td></tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();
   }

   /**
   * Dropdown execution time for SLA
   *
   * @param $name string name of the select
   * @param $options array of options : may be :
   *           - value : default value
   *           - max_time : max time to use
   *           - used : already used values
   *
   * @return nothing
   **/
   static function dropdownExecutionTime ($name,$options=array()) {
         global $LANG;

         $p['value']='';
         $p['max_time']=4*DAY_TIMESTAMP;
         $p['used']=array();

         if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
               $p[$key]=$val;
            }
         }
         // Display default value;
         if (($key=array_search($p['value'],$p['used'])) !== false) {
            unset($p['used'][$key]);
         }

         $possible_values=array();
         for ($i=10 ; $i<60 ; $i+=10) {
            if (!in_array($i*MINUTE_TIMESTAMP,$p['used'])) {
               $possible_values[$i*MINUTE_TIMESTAMP]='+ '.$i." ".Toolbox::ucfirst($LANG['job'][22]);
            }
            if (!in_array(-$i*MINUTE_TIMESTAMP,$p['used'])) {
               if ($p['max_time'] >= $i*MINUTE_TIMESTAMP) {
                  $possible_values[-$i*MINUTE_TIMESTAMP]='- '.$i." ".Toolbox::ucfirst($LANG['job'][22]);
               }
            }
         }
                  
         for ($i=1 ; $i<24 ; $i++) {
            if (!in_array($i*HOUR_TIMESTAMP,$p['used'])) {
               $possible_values[$i*HOUR_TIMESTAMP]='+ '.$i." ".Toolbox::ucfirst($LANG['gmt'][1]);
            }
            if (!in_array(-$i*HOUR_TIMESTAMP,$p['used'])) {
               if ($p['max_time'] >= $i*HOUR_TIMESTAMP) {
                  $possible_values[-$i*HOUR_TIMESTAMP]='- '.$i." ".Toolbox::ucfirst($LANG['gmt'][1]);
               }
            }
         }
         for ($i=1 ; $i<30 ; $i++) {
            if (!in_array($i*DAY_TIMESTAMP,$p['used'])) {
               $possible_values[$i*DAY_TIMESTAMP]='+ '.$i." ".Toolbox::ucfirst($LANG['calendar'][12]);
            }
            if (!in_array(-$i*DAY_TIMESTAMP,$p['used'])) {
               if ($p['max_time'] >= $i*DAY_TIMESTAMP) {
                  $possible_values[-$i*DAY_TIMESTAMP]='- '.$i." ".Toolbox::ucfirst($LANG['calendar'][12]);
               }
            }
         }
         if (!in_array(0,$p['used'])) {
            $possible_values[0]=$LANG['sla'][5];
         }

         ksort($possible_values);
         Dropdown::showFromArray($name,$possible_values,array('value'=>$p['value']));

   }

   /**
   * Get already used execution time for a SLA
   *
   * @param $slas_id integer id of the SLA
   *
   * @return array of already used execution times
   **/
   static function getAlreadyUsedExecutionTime($slas_id) {
      global $DB;
      $result=array();
      $query="SELECT DISTINCT `execution_time` FROM `glpi_slalevels` WHERE `slas_id` = '$slas_id';";

      foreach ($DB->request($query) as $data) {
         $result[$data['execution_time']] = $data['execution_time'];
      }
      return $result;
   }


   /**
   * Get first level for a SLA
   *
   * @param $slas_id integer id of the SLA
   *
   * @return id of the sla level : 0 if not exists
   **/
   static function getFirstSlaLevel($slas_id) {
      global $DB;
      $query="SELECT `id`
               FROM `glpi_slalevels`
               WHERE `slas_id` = '$slas_id'
                  AND `is_active` = 1
               ORDER BY `execution_time` ASC LIMIT 1;";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            return $DB->result($result,0,0);
         }
      }
      return 0;
   }

   /**
   * Get next level for a SLA
   *
   * @param $slas_id integer id of the SLA
   * @param $slalevels_id integer id of the current SLA level
   *
   * @return id of the sla level : 0 if not exists
   **/
   static function getNextSlaLevel($slas_id,$slalevels_id) {
      global $DB;
      $query="SELECT `execution_time`
               FROM `glpi_slalevels`
               WHERE `id` = '$slalevels_id';";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $execution_time=$DB->result($result,0,0);
            $query="SELECT `id`
                     FROM `glpi_slalevels`
                     WHERE `slas_id` = '$slas_id'
                        AND `execution_time` > '$execution_time'
                        AND `is_active` = 1
                     ORDER BY `execution_time` ASC LIMIT 1 ;";
            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)) {
                  return $DB->result($result,0,0);
               }
            }
         }
      }
      return 0;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'SLA' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              countElementsInTable($this->getTable(),
                                                                   "slas_id = '".$item->getID()."'"));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='SLA') {
         $slalevel = new self();
         $slalevel->showForSLA($item);
      }
      return true;
   }

}

?>
