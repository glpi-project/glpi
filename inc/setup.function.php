<?php

/*
 * @version $Id$
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


// FUNCTIONS Setup


function showMailServerConfig($value) {
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
   if (empty($value)) {
      $value = "&nbsp;";
   }
   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][170] . "&nbsp;:</td>";
   echo "<td><strong>$value</strong></td></tr>\n";
}

function constructMailServerConfig($input) {

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
?>
