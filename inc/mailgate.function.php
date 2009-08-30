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

/**
 * Cron action on mailgate : retrieve mail and create tickets
 * @return -1 : done but not finish 1 : done with success
 **/
function cron_mailgate($task){
   global $DB,$CFG_GLPI;

   $query="SELECT * FROM glpi_mailcollectors WHERE is_active = 1";
   if ($result=$DB->query($query)){
      $max = $task->fields['param'];
      if ($DB->numrows($result)>0){
         $mc=new MailCollect();

         while ($max>0 && $data=$DB->fetch_assoc($result)){

            $mc->maxfetch_emails = $max;

            $task->log("Collect mails from ".$data["host"]." for  ".getDropdownName("glpi_entities",$data["entities_id"])."\n");
            $message=$mc->collect($data["id"]);

            $task->log("$message\n");
            $task->addVolume($mc->fetch_emails);

            $max -= $mc->fetch_emails;
         }
      }
      if ($max == $task->fields['param']) {
         return 0; // Nothin to do
      } else if ($max > 0) {
         return 1; // done
      }
      return -1; // still messages to retrieve
   }
   return 0;
}
?>
