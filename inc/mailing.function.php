<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

function testMail() {
   global $CFG_GLPI,$LANG;

   $mmail=new glpi_phpmailer();
   $mmail->From=$CFG_GLPI["admin_email"];
   $mmail->FromName=$CFG_GLPI["admin_email"];
   $mmail->AddAddress($CFG_GLPI["admin_email"], "GLPI");
   $mmail->Subject="[GLPI] ".$LANG['mailing'][32];
   $mmail->Body=$LANG['mailing'][31]."\n-- \n".$CFG_GLPI["mailing_signature"];

   if(!$mmail->Send()) {
      addMessageAfterRedirect($LANG['setup'][206],false,ERROR);
   } else {
      addMessageAfterRedirect($LANG['setup'][205]);
   }
}

function showFormMailingType($type, $profiles) {
   global $LANG, $DB;

   $options="";
   // Get User mailing
   $query = "SELECT `glpi_mailingsettings`.`items_id` , `glpi_mailingsettings`.`id`
             FROM `glpi_mailingsettings`
             WHERE `glpi_mailingsettings`.`type`='$type'
                   AND `glpi_mailingsettings`.`mailingtype`='" . USER_MAILING_TYPE . "'
             ORDER BY `glpi_mailingsettings`.`items_id`";
   $result = $DB->query($query);
   if ($DB->numrows($result)) {
      while ($data = $DB->fetch_assoc($result)) {
         if (isset($profiles[USER_MAILING_TYPE."_".$data["items_id"]])) {
            unset($profiles[USER_MAILING_TYPE."_".$data["items_id"]]);
         }
         switch ($data["items_id"]) {
            case ADMIN_MAILING :
               $name = $LANG['setup'][237];
               break;

            case ADMIN_ENTITY_MAILING :
               $name = $LANG['setup'][237]." ".$LANG['entity'][0];
               break;

            case ASSIGN_MAILING :
               $name = $LANG['setup'][239];
               break;

            case AUTHOR_MAILING :
               $name = $LANG['job'][4];
               break;

            case USER_MAILING :
               $name = $LANG['common'][34] . " " . $LANG['common'][1];
               break;

            case OLD_ASSIGN_MAILING :
               $name = $LANG['setup'][236];
               break;

            case TECH_MAILING :
               $name = $LANG['common'][10];
               break;

            case RECIPIENT_MAILING :
               $name = $LANG['job'][3];
               break;

            case ASSIGN_ENT_MAILING :
               $name = $LANG['financial'][26];
               break;

            case ASSIGN_GROUP_MAILING :
               $name = $LANG['setup'][248];
               break;

            case SUPERVISOR_ASSIGN_GROUP_MAILING :
               $name = $LANG['common'][64]." ".$LANG['setup'][248];
               break;

            case SUPERVISOR_AUTHOR_GROUP_MAILING :
               $name = $LANG['common'][64]." ".$LANG['setup'][249];
               break;

            default :
               $name="&nbsp;";
               break;
         }
         $options.= "<option value='" . $data["id"] . "'>" . $name . "</option>";
      }
   }
   // Get Profile mailing
   $query = "SELECT `glpi_mailingsettings`.`items_id`, `glpi_mailingsettings`.`id`,
                    `glpi_profiles`.`name` AS prof
             FROM `glpi_mailingsettings`
             LEFT JOIN `glpi_profiles` ON (`glpi_mailingsettings`.`items_id` = `glpi_profiles`.`id`)
             WHERE `glpi_mailingsettings`.`type`='$type'
                   AND `glpi_mailingsettings`.`mailingtype`='" . PROFILE_MAILING_TYPE . "'
             ORDER BY prof";
   $result = $DB->query($query);
   if ($DB->numrows($result)) {
      while ($data = $DB->fetch_assoc($result)) {
         $options.= "<option value='" . $data["id"] . "'>" . $LANG['profiles'][22] . " " .
                     $data["prof"] . "</option>";
         if (isset($profiles[PROFILE_MAILING_TYPE."_".$data["items_id"]])) {
            unset($profiles[PROFILE_MAILING_TYPE."_".$data["items_id"]]);
         }
      }
   }
   // Get Group mailing
   $query = "SELECT `glpi_mailingsettings`.`items_id`, `glpi_mailingsettings`.`id`,
                    `glpi_groups`.`name` AS name
             FROM `glpi_mailingsettings`
             LEFT JOIN `glpi_groups` ON (`glpi_mailingsettings`.`items_id` = `glpi_groups`.`id`)
             WHERE `glpi_mailingsettings`.`type`='$type'
                   AND `glpi_mailingsettings`.`mailingtype`='" . GROUP_MAILING_TYPE . "'
             ORDER BY name;";
   $result = $DB->query($query);
   if ($DB->numrows($result)) {
      while ($data = $DB->fetch_assoc($result)) {
         $options.= "<option value='" . $data["id"] . "'>" . $LANG['common'][35] . " " .
                     $data["name"] . "</option>";
         if (isset($profiles[GROUP_MAILING_TYPE."_".$data["items_id"]])) {
            unset($profiles[GROUP_MAILING_TYPE."_".$data["items_id"]]);
         }
      }
   }
   echo "<td class='right'>";
   if (count($profiles)) {
      echo "<select name='mailing_to_add_" . $type . "[]' multiple size='5'>";
      foreach ($profiles as $key => $val) {
         list ($mailingtype, $items_id) = explode("_", $key);
         echo "<option value='$key'>" . $val . "</option>";
      }
      echo "</select>";
   }
   echo "</td><td class='center'>";
   if (count($profiles)) {
      echo "<input type='submit' class='submit' name='mailing_add_$type' value='" .
            $LANG['buttons'][8] . " >>'>";
   }
   echo "<br><br>";
   if (!empty($options)) {
      echo "<input type='submit' class='submit' name='mailing_delete_$type' value='<< " .
            $LANG['buttons'][6] . "'>";
   }
   echo "</td><td>";
   if (!empty($options)) {
      echo "<select name='mailing_to_delete_" . $type . "[]' multiple size='5'>";
      echo $options ."</select>";
   } else {
      echo "&nbsp;";
   }
   echo "</td>";
}

function updateMailNotifications($input) {
   global $DB;

   $type = "";
   $action = "";

   foreach ($input as $key => $val) {
      if (!strstr($key,"mailing_to_") && strstr($key,"mailing_")) {
         if (preg_match("/mailing_([a-z]+)_([a-z]+)/", $key, $matches)) {
            $type = $matches[2];
            $action = $matches[1];
         }
      }
   }
   if (count($input["mailing_to_" . $action . "_" . $type]) > 0) {
      foreach ($input["mailing_to_" . $action . "_" . $type] as $val) {
         switch ($action) {
            case "add" :
               list ($mailingtype, $items_id) = explode("_", $val);
               $query = "INSERT INTO
                         `glpi_mailingsettings` (`type`,`items_id`,`mailingtype`)
                         VALUES ('$type','$items_id','$mailingtype')";
               $DB->query($query);
               break;

            case "delete" :
               $query = "DELETE
                         FROM `glpi_mailingsettings`
                         WHERE `id`='$val'";
               $DB->query($query);
               break;
         }
      }
   }
}

/**
 * Determine if email is valid
 * @param $email email to check
 * @param $checkdns check dns entry
 * @return boolean
 * from http://www.linuxjournal.com/article/9585
 */
function isValidEmail($email,$checkdns=false)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex) {
      $isValid = false;
   } else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.') {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local)) {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain)) {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local))) {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
            $isValid = false;
         }
      }
      if ($checkdns) {
         if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = false;
         }
      } else if (!preg_match('/\\./', $domain)|| !preg_match("/[a-zA-Z0-9]$/", $domain)) {
         // domain has no dots or do not end by alphenum char
         $isValid = false;
      }
   }
   return $isValid;
}


/**
 * Determine if email is valid
 * @param $email email to check
 * @return boolean
 */
/*function isValidEmail($email="") {

   if ( !preg_match( "/^" .
                     "[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
                     "@" .
                     "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
                     "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld
                     "$/i", $email)) {
      return false;
   } else {
      return true;
   }
}
*/
function isAuthorMailingActivatedForHelpdesk() {
   global $DB,$CFG_GLPI;

   if ($CFG_GLPI['use_mailing']) {
      $query="SELECT COUNT(id)
              FROM `glpi_mailingsettings`
              WHERE `type` IN ('new','followup','update','finish')
                    AND `mailingtype` = '".USER_MAILING_TYPE."'
                    AND `items_id` = '".AUTHOR_MAILING."'";
      if ($result=$DB->query($query)) {
         if ($DB->result($result,0,0)>0) {
            return true;
         }
      }
   }
   return false;
}

?>