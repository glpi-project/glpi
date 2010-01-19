<?php
/*
 * @version $Id: entitydata.class.php 10111 2010-01-12 09:03:26Z walid $
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Entity Data class
 */
class EntityNotification extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_entitynotifications';

   function getIndexName() {
      return 'entities_id';
   }

   function canCreate() {
      return haveRight('notification', 'w');
   }

   function canView() {
      return haveRight('notification', 'r');
   }

   function getEmpty() {
      global $CFG_GLPI;
      $this->fields['cartridges_alert_repeat'] = -1;
      $this->fields['consumables_alert_repeat'] = -1;
      $this->fields['use_licenses_alert'] = 0;
      $this->fields['mailing_signature'] = '';
   }

   /**
    *
    */
   static function showForm(Entity $entity) {
      global $DB, $LANG, $CFG_GLPI;

      $con_spotted=false;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r')) {
         return false;
      }
      $canedit=$entity->can($ID,'w');

      // Get data
      $entitynotification=new EntityNotification();
      if (!$entitynotification->getFromDB($ID)) {
         $entitynotification->getEmpty();
      }


      if ($canedit) {
         echo "<form method='post' name=form action='".getItemTypeFormURL(__CLASS__)."'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>".$LANG['setup'][240]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][203]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($entitynotification, "admin_email");
      echo "</td>";
      echo "<td rowspan='5' class='middle right'>" . $LANG['setup'][204] . "</td>";
      echo "<td rowspan='5' class='middle right'><textarea cols='60' rows='5' name=\"mailing_signature\" >".
                 $entitynotification->fields["mailing_signature"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][207]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($entitynotification, "admin_reply");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][245] . " " . $LANG['setup'][244] . "</td><td>";

      $times[-1] = $LANG['setup'][731];
      $times[0] = $LANG['setup'][307];
      $times[DAY_TIMESTAMP] = $LANG['setup'][305];
      $times[WEEK_TIMESTAMP] = $LANG['setup'][308];
      $times[MONTH_TIMESTAMP] = $LANG['setup'][309];
      Dropdown::showFromArray('cartridges_alert_repeat',
                              $times,
                              array('value'=>$entitynotification->fields['cartridges_alert_repeat']));
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][245] . " " . $LANG['setup'][243] . "</td><td>";
      Dropdown::showFromArray('consumables_alert_repeat',
                         $times,
                         array('value'=>$entitynotification->fields['consumables_alert_repeat']));

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td >" . $LANG['setup'][264] . "</td><td>";
      Dropdown::showYesNo("use_licenses_alert", $entitynotification->fields["use_licenses_alert"]);
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";
         if (isset($entitynotification->fields["id"])) {
            echo "<input type='hidden' name='id' value=\"".$entitynotification->fields["id"]."\">";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit' >";
         } else {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][7]."\" class='submit' >";
         }
         echo "</td></tr>";
         echo "</table></form>";
      } else {
         echo "</table>";
      }
   }
}

?>
