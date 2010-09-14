<?php
/*
 * @version $Id: slalevel_ticket.class.php 12195 2010-08-26 11:20:05Z moyo $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Class Ticket links
class Ticket_Ticket extends CommonDBRelation {


   // From CommonDBRelation
   public $itemtype_1 = 'Ticket';
   public $items_id_1 = 'tickets_id_1';
   public $itemtype_2 = 'Ticket';
   public $items_id_2 = 'tickets_id_2';

   public $check_entities=false;

   function canCreateItem() {
      $ticket = new Ticket();
      print_r($this);
      return $ticket->can($this->fields['tickets_id_1'],'w') 
            || $ticket->can($this->fields['tickets_id_2'],'w');
   }
   /**
    * Get linked tickets to a ticket
    *
    *@param $ID ID of the ticket id
    *@return array of linked tickets  array(id=>linktype)
    *
   **/
   static function getLinkedTicketsTo ($ID) {
      global $DB;

      // Make new database object and fill variables
      if (empty($ID)) {
         return false;
      }

      $sql = "SELECT *
                FROM `glpi_tickets_tickets`
                WHERE `tickets_id_1` = '$ID'
                  OR `tickets_id_2` = '$ID'";

      $tickets=array();

      foreach ($DB->request($sql) as $data) {
         if ($data['tickets_id_1']!=$ID) {
            $tickets[$data['id']]=array('link'       => $data['link'],
                                        'tickets_id' => $data['tickets_id_1']);
         } else {
            $tickets[$data['id']]=array('link'       => $data['link'],
                                        'tickets_id' => $data['tickets_id_2']);
         }
      }

      ksort($tickets);
      return $tickets;
   }


   /**
    * Display linked tickets to a ticket
    *
    *@param $ID ID of the ticket id
    *@return nothing display
    *
   **/
   static function displayLinkedTicketsTo ($ID) {
      global $DB,$LANG,$CFG_GLPI;

      $tickets=self::getLinkedTicketsTo($ID);

      $canupdate = haveRight('update_ticket','1');

      $ticket=new Ticket();
      if (count($tickets)) {
         foreach ($tickets as $linkID => $data) {
            echo self::getLinkName($data['link'])."&nbsp;";
            if (!$_SESSION['glpiis_ids_visible']) {
               echo $LANG['common'][2]."&nbsp;".$data['tickets_id']."&nbsp;:&nbsp;";
            }
            if ($ticket->getFromDB($data['tickets_id'])) {
               echo $ticket->getLink();
               echo  "&nbsp;<img src=\"".$CFG_GLPI["root_doc"]."/pics/".$ticket->fields["status"].".png\"
                                 alt='".Ticket::getStatus($ticket->fields["status"])."' title='".
                                 Ticket::getStatus($ticket->fields["status"])."'>";
               if ($canupdate) {
                  echo "&nbsp;<a href=\"".$CFG_GLPI["root_doc"].
                        "/front/ticket.form.php?delete_link=delete_link&amp;id=$linkID&amp;tickets_id=$ID\" 
                           title='".$LANG['reservation'][6]."'>
                        <img src=\"".$CFG_GLPI["root_doc"]."/pics/delete.png\" 
                        alt='".$LANG['buttons'][6]."' title='".$LANG['buttons'][6]."'></a>";
               }
            }
   
            echo '<br>';
         }
      }
   }
   

   /**
    * Dropdown for links between tickets
    *
   * @param $myname select name
    * @param $value default value
    */
   static function dropdownLinks($myname, $value=LINK_TO) {
      global $LANG;

      $tmp[LINK_TO]        = $LANG['common'][97];
      $tmp[DUPLICATE_WITH] = $LANG['common'][98];
      Dropdown::showFromArray($myname, $tmp, array('value' => $value));
   }

   /**
    * Get Link Name
    *
   * @param $myname select name
    * @param $value default value
    */
   static function getLinkName($value) {
      global $LANG;

      $tmp[LINK_TO]        = $LANG['common'][97];
      $tmp[DUPLICATE_WITH] = $LANG['common'][98];

      if (isset($tmp[$value])) {
         return $tmp[$value];
      }
      return NOT_AVAILABLE;
   }
   function prepareInputForAdd($input) {
      $ticket= new Ticket();
      if (!isset($input['tickets_id_1']) || !isset($input['tickets_id_2']) 
         || $input['tickets_id_2'] == $input['tickets_id_1']
         || !$ticket->getFromDB($input['tickets_id_1'])
         || !$ticket->getFromDB($input['tickets_id_2'])) {
         return false;
      }
      
      return $input;
   }

  /**
    * Affect the same solution for duplicates tickets
    *
    *@param $ID ID of the ticket id
    *@return nothing do the change
    *
   **/
   static function manageLinkedTicketsOnSolved ($ID) {
      
      $ticket = new Ticket();

      if ($ticket->getfromDB($ID)) {
         $input['solution']               = addslashes($ticket->fields['solution']);
         $input['ticketsolutiontypes_id'] = addslashes($ticket->fields['ticketsolutiontypes_id']);

         $tickets=self::getLinkedTicketsTo($ID);
         if (count($tickets)) {
            foreach ($tickets as $data) {
               $input['id']=$data['tickets_id'];
               if ($ticket->can($input['id'],'w')) {
                  $ticket->update($input);
               }
            }
         }
      }
   }


}

?>