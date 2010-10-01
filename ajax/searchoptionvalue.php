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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"searchoptionvalue.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();
// echo "value";print_r($_REQUEST);
if (isset($_REQUEST['searchtype'])) {
   $searchopt=unserialize(stripslashes($_REQUEST['searchopt']));
   $_REQUEST['value']=rawurldecode(stripslashes($_REQUEST['value']));

   $addmeta="";
   if (isset($_REQUEST['meta'])&&$_REQUEST['meta']) {
      $addmeta='2';
   }

   $inputname='contains'.$addmeta.'['.$_REQUEST['num'].']';
   //print_r($searchopt);
   $display=false;

   // Fix table
   if (isset($searchopt['table'])) {
      switch ($searchopt['table']) {
         case "glpi_followup_requesttypes" :
               $searchopt['table']='glpi_requesttypes';
            break;

         case "glpi_suppliers_infocoms" :
               $searchopt['table']='glpi_suppliers';
            break;

         //TODO voir pour authmails qui est sacrifiée
         case "glpi_auth_tables" :
               $searchopt['table']='glpi_authldaps';
            break;
      }
   }


   switch ($_REQUEST['searchtype']) {
      case "equals" :
        // Specific cases with linkfield
        if (!$display && isset($searchopt['linkfield'])) {
            // Specific cases
            switch ($searchopt['table'].".".$searchopt['linkfield']) {
               case "glpi_users_validation.users_id_validate":
                  User::dropdown(array('name'      => $inputname,
                                       'value'     => $_REQUEST['value'],
                                       'comments'  => false,
                                       'all'       => -1,
                                       'right'     => 'validate_ticket'));
                  $display=true;
                  break;
               case "glpi_users_validation.users_id":
                  User::dropdown(array('name'      => $inputname,
                                       'value'     => $_REQUEST['value'],
                                       'comments'  => false,
                                       'all'       => -1,
                                       'right'     => 'create_validation'));
                  $display=true;
                  break;
            }
         }

        // Specific cases with linkfield
        if (!$display && isset($searchopt['linkfield'])) {
            switch ($_REQUEST['itemtype'].".".$searchopt['linkfield']) {
               case "Ticket.users_id_recipient" :
               case "Ticket.users_id" :
                  User::dropdownForTicket($inputname,$_REQUEST['value'],$searchopt['linkfield']);
                  $display=true;
                  break;
               case "Ticket.users_id_assign" :
                  User::dropdown(array('name'   => $inputname,
                                       'value'  => $_REQUEST['value'],
                                       'right'  => 'own_ticket',
                                       'all'    => 1));
                  $display=true;
                  break;
            }
        }

        if (!$display && isset($searchopt['field'])) {
            // Specific cases
            switch ($searchopt['table'].".".$searchopt['field']) {

               case "glpi_tickets.status" :
                  Ticket::dropdownStatus($inputname,$_REQUEST['value'],1);
                  $display=true;
                  break;
               case "glpi_tickets.priority" :
                  Ticket::dropdownPriority($inputname,$_REQUEST['value'],true,true);
                  $display=true;
                  break;
               case "glpi_tickets.impact" :
                  Ticket::dropdownImpact($inputname,$_REQUEST['value'],true);
                  $display=true;
                  break;
               case "glpi_tickets.urgency" :
                  Ticket::dropdownUrgency($inputname,$_REQUEST['value'],true);
                  $display=true;
                  break;
               case "glpi_tickets.global_validation" :
                  TicketValidation::dropdownStatus($inputname,array('value'=>$_REQUEST['value'],'all'=>1));
                  $display=true;
                  break;
               case "glpi_users.name":
                  User::dropdown(array('name'      => $inputname,
                                       'value'     => $_REQUEST['value'],
                                       'comments'  => false,
                                       'all'       => -1,
                                       'right'     => 'all'));
                  $display=true;
                  break;
               case "glpi_ticketvalidations.status" :
                  TicketValidation::dropdownStatus($inputname,array('value'=>$_REQUEST['value'],'all'=>1));
                  $display=true;
                  break;
            }

            // Standard datatype usage
            if (!$display && isset($searchopt['datatype'])) {
               switch ($searchopt['datatype']) {

                  case "bool":
                     Dropdown::showYesNo($inputname,$_REQUEST['value']);
                     $display=true;
                     break;
                  case "right":
                     // No access not displayed because empty not take into account for search
                     Profile::dropdownNoneReadWrite($inputname,$_REQUEST['value'],1,1,1);
                     $display=true;
                     break;
                  case "itemtypename":
                     Dropdown::dropdownUsedItemTypes($inputname,getItemTypeForTable($searchopt['table']),
                                    array('value'     => $_REQUEST['value'],
                                          'comments'  => 0));
                     $display=true;
                     break;
               }
            }

            // Standard field usage
            if (!$display) {
               switch ($searchopt['field']) {

                  case "name":
                  case "completename":
                     Dropdown::show(getItemTypeForTable($searchopt['table']),
                                    array('value'     => $_REQUEST['value'],
                                          'name'      => $inputname,
                                          'comments'  => 0));
                     $display=true;
                     break;
               }
            }
        }
        break;
   }


//    static function dropdownValue($table,$myname,$value='',$display_comment=1,$entity_restrict=-1,
//                           $update_item="",$used=array(),$auto_submit=0) {


   // Default case : text field
   if (!$display) {
        echo "<input type='text' size='13' name=\"$inputname\" value=\"".cleanInputText($_REQUEST['value'])."\" >";
   }
}

//ajaxFooter();


?>
