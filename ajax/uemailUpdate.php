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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE = 1;

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkCentralAccess();

if (isset($_REQUEST['field']) && $_REQUEST["value"]>0) {

   $query = "SELECT `email`
            FROM `glpi_users`
            WHERE `id` = '".$_REQUEST["value"]."'";
   $email = "";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)) {
         $email = $DB->result($result, 0, "email");
      }
   }
   echo $LANG['job'][19].'&nbsp;:&nbsp;';

   $rand=Dropdown::showYesNo($_REQUEST['field'].'[use_notification]',true);

   echo '<br>'.$LANG['mailing'][118]."&nbsp;:&nbsp;";
   if (!empty($email) && NotificationMail::isUserAddressValid($email)) {
      echo $email;
   } else {
      echo "<input type='text' size='25' name='".$_REQUEST['field']."[alternative_email]'
               value='$email'>";
   }   
}

?>