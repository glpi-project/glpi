<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


// FUNCTIONS Setup

function listTemplates($itemtype, $target, $add = 0) {
   global $DB, $CFG_GLPI, $LANG;

   if (!class_exists($itemtype)) {
      return false;
   }
   $item = new $itemtype();

   //Check is user have minimum right r
   if (!$item->canView() && !$item->canCreate()) {
      return false;
   }

   $query = "SELECT * FROM `".$item->getTable()."`
            WHERE `is_template` = '1' ";
   if ($item->isEntityAssign()) {
      $query .= getEntitiesRestrictRequest('AND', $item->getTable(), 'entities_id',
                     $_SESSION['glpiactive_entity'], $item->maybeRecursive());
   }
   $query .= " ORDER by `template_name`";

   if ($result = $DB->query($query)) {
      echo "<div class='center'><table class='tab_cadre' width='50%'>";
      if ($add) {
         echo "<tr><th>" . $LANG['common'][7] . " - ".$item->getTypeName()." :</th></tr>";
         echo "<tr><td class='tab_bg_1 center'>";
         echo "<a href=\"$target?id=-1&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;" .
                $LANG['common'][31] . "&nbsp;&nbsp;&nbsp;</a></td>";
         echo "</tr>";
      } else {
         echo "<tr><th colspan='2'>" . $LANG['common'][14] . " - ".$item->getTypeName()." :</th></tr>";
      }

      while ($data = $DB->fetch_array($result)) {
         $templname = $data["template_name"];
         if ($_SESSION["glpiis_ids_visible"] || empty($data["template_name"])) {
            $templname.= "(".$data["id"].")";
         }
         echo "<tr><td class='tab_bg_1 center'>";
         if ($item->canCreate() && !$add) {
            echo "<a href=\"$target?id=" . $data["id"] . "&amp;withtemplate=1\">";
            echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
            echo "<td class='tab_bg_2 center b'>";
            echo "<a href=\"$target?id=" . $data["id"] . "&amp;purge=purge&amp;withtemplate=1\">" .
                   $LANG['buttons'][6] . "</a></td>";
         } else {
            echo "<a href=\"$target?id=" . $data["id"] . "&amp;withtemplate=2\">";
            echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
         }
         echo "</tr>";
      }

      if ($item->canCreate() && !$add) {
         echo "<tr><td colspan='2' class='tab_bg_2 center b'>";
         echo "<a href=\"$target?withtemplate=1\">" . $LANG['common'][9] . "</a>";
         echo "</td></tr>";
      }
      echo "</table></div>\n";
   }
}

function showOtherAuthList($target) {
   global $DB, $LANG, $CFG_GLPI;

   if (!haveRight("config", "w")) {
      return false;
   }

   echo "<form name=cas action=\"$target\" method='post'>";
   echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe'>";

   // CAS config
   echo "<tr><th colspan='2'>" . $LANG['setup'][177];
   if (!empty($CFG_GLPI["cas_host"])) {
      echo " - ".$LANG['setup'][192];
   }
   echo "</th></tr>\n";

   if (function_exists('curl_init')
       && (version_compare(PHP_VERSION, '5', '>=') || (function_exists("domxml_open_mem")))) {

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][174] . "</td>";
      echo "<td><input type='text' name='cas_host' value=\"".$CFG_GLPI["cas_host"]."\"></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][175] . "</td>";
      echo "<td><input type='text' name='cas_port' value=\"".$CFG_GLPI["cas_port"]."\"></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][176] . "</td>";
      echo "<td><input type='text' name='cas_uri' value=\"".$CFG_GLPI["cas_uri"]."\"></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][182] . "</td>";
      echo "<td><input type='text' name='cas_logout' value=\"".$CFG_GLPI["cas_logout"]."\"></td></tr>\n";
   } else {
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<p class='red'>" . $LANG['setup'][178] . "</p>";
      echo "<p>" . $LANG['setup'][179] . "</p></td></tr>\n";
   }
   // X509 config
   echo "<tr><th colspan='2'>" . $LANG['setup'][190];
   if (!empty($CFG_GLPI["x509_email_field"])) {
      echo " - ".$LANG['setup'][192];
   }
   echo "</th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][191] . "</td>";
   echo "<td><input type='text' name='x509_email_field' value=\"".$CFG_GLPI["x509_email_field"]."\">";
   echo "</td></tr>\n";

   // Autres config
   echo "<tr><th colspan='2'>" . $LANG['common'][67];
   if (!empty($CFG_GLPI["existing_auth_server_field"])) {
      echo " - ".$LANG['setup'][192];
   }
   echo "</th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][193] . "</td>";
   echo "<td><select name='existing_auth_server_field'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='HTTP_AUTH_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="HTTP_AUTH_USER" ? " selected " : "") . ">".
          "HTTP_AUTH_USER</option>\n";
   echo "<option value='REMOTE_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="REMOTE_USER" ? " selected " : "") . ">".
          "REMOTE_USER</option>\n";
   echo "<option value='PHP_AUTH_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="PHP_AUTH_USER" ? " selected " : "") . ">".
          "PHP_AUTH_USER</option>\n";
   echo "<option value='USERNAME' " .
          ($CFG_GLPI["existing_auth_server_field"]=="USERNAME" ? " selected " : "") . ">".
          "USERNAME</option>\n";
   echo "<option value='REDIRECT_REMOTE_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="REDIRECT_REMOTE_USER" ? " selected " : "") .">".
          "REDIRECT_REMOTE_USER</option>\n";
   echo "</select>";
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][199] . "</td><td>";
   Dropdown::showYesNo('existing_auth_server_field_clean_domain',
                       $CFG_GLPI['existing_auth_server_field_clean_domain']);
   echo "</td></tr>\n";

   echo "<tr><th colspan='2'>" . $LANG['setup'][194]."</th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ldap'][4] . "</td><td>";
   Dropdown::show('AuthLDAP',
               array('name'   => 'authldaps_id_extra',
                     'value'  => $CFG_GLPI["authldaps_id_extra"]));
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
   echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][7]."\" >";
   echo "</td></tr>";

   echo "</table></div></form>\n";
}

function showMailServerConfig($value) {
   global $LANG;

   if (!haveRight("config", "w")) {
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
   echo "<td><input size='30' type='text' name='mail_server' value=\"" . $addr . "\" ></td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][168] . "&nbsp;:</td><td>";
   echo "<select name='server_type'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/imap' " .(strstr($value,"/imap") ? " selected " : "") . ">IMAP</option>\n";
   echo "<option value='/pop' " .(strstr($value,"/pop") ? " selected " : "") . ">POP</option>\n";
   echo "</select>&nbsp;";

   echo "<select name='server_ssl'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/ssl' " .(strstr($value,"/ssl") ? " selected " : "") . ">SSL</option>\n";
   echo "</select>&nbsp;";

   echo "<select name='server_tls'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/tls' " .(strstr($value,"/tls") ? " selected " : "") . ">TLS</option>\n";
   echo "<option value='/notls' " .(strstr($value,"/notls") ? " selected " : "").">NO-TLS</option>\n";
   echo "</select>&nbsp;";

   echo "<select name='server_cert'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/novalidate-cert' " .(strstr($value,"/novalidate-cert") ? " selected " : "") .
          ">NO-VALIDATE-CERT</option>\n";
   echo "<option value='/validate-cert' " .(strstr($value,"/validate-cert") ? " selected " : "") .
          ">VALIDATE-CERT</option>\n";
   echo "</select>\n";

   echo "<input type=hidden name=imap_string value='".$value."'>";
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][169] . "&nbsp;:</td>";
   echo "<td><input size='30' type='text' name='server_mailbox' value=\"" . $mailbox . "\" >";
   echo "</td></tr>\n";
   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][171] . "&nbsp;:</td>";
   echo "<td><input size='10' type='text' name='server_port' value='$port'></td></tr>\n";
   if (empty ($value)) {
      $value = "&nbsp;";
   }
   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][170] . "&nbsp;:</td>";
   echo "<td><strong>$value</strong></td></tr>\n";
}

function constructMailServerConfig($input) {

   $out = "";
   if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
      $out .= "{" . $input['mail_server'];
   } else {
      return $out;
   }
   if (isset ($input['server_port']) && !empty ($input['server_port'])) {
      $out .= ":" . $input['server_port'];
   }
   if (isset ($input['server_type'])) {
      $out .= $input['server_type'];
   }
   if (isset ($input['server_ssl'])) {
      $out .= $input['server_ssl'];
   }
   if (isset ($input['server_cert'])
       && (!empty($input['server_ssl']) || !empty($input['server_tls']))) {
      $out .= $input['server_cert'];
   }
   if (isset ($input['server_tls'])) {
      $out .= $input['server_tls'];
   }
   $out .= "}";
   if (isset ($input['server_mailbox'])) {
      $out .= $input['server_mailbox'];
   }

   return $out;
}
?>
