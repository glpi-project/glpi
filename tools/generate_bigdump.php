<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

/** @file
* @brief
*/


// This script generate and populate a complete glpi DB
// A good way to test GLPI with a lot of data

define('DO_NOT_CHECK_HTTP_REFERER', 1);

include (__DIR__ . '/../inc/includes.php');
include (__DIR__ . '/generate_bigdump.function.php');

if (PHP_SAPI != 'cli') {
   echo "This script must be run from command line";
   exit();
}

$auth = new Auth();
if (!$auth->Login('glpi', 'glpi', true)) {
    exit('Authentication failed!');
}

// Force mailing to false
$CFG_GLPI["use_mailing"] = 0;

$entity_number = 10;

$multiplicator = 0.5;

$MAX['locations']         = 10;
$MAX['kbcategories']      = 8;
$MAX['tracking_category'] = 5;
$MAX_KBITEMS_BY_CAT       = 10;

// DROPDOWNS
$MAX['budget']                   = 10;
$MAX['consumable_type']          = 10;
$MAX['cartridge_type']           = 10;
$MAX['contact_type']             = 10;
$MAX['user_title']               = 10;
$MAX['user_type']                = 10;
$MAX['vlan']                     = 10;
$MAX['contract_type']            = 10;
$MAX['domain']                   = 10;
$MAX['enttype']                  = 10;
$MAX['firmware']                 = 10;
$MAX['interface']                = 10;
$MAX['case_type']                = 10;
$MAX['iface']                    = 10;
$MAX['model']                    = 10;
$MAX['network']                  = 10;
$MAX['os']                       = 10;
$MAX['os_version']               = 10;
$MAX['os_sp']                    = 10;
$MAX['ram_type']                 = 10;
$MAX['rubdocs']                  = 10;
$MAX['softwarecategory']         = 10;
$MAX['solutiontypes']            = 5;
$MAX['solutiontemplates']        = 5;
$MAX['taskcategory']             = 2;
$MAX['licensetype']              = 10;
$MAX['state']                    = 10;
$MAX['vlan']                     = 10;
$MAX['type_computers']           = 10;
$MAX['type_printers']            = 10;
$MAX['type_monitors']            = 10;
$MAX['type_peripherals']         = 10;
$MAX['type_networking']          = 10;
$MAX['type_phones']              = 10;
$MAX['model_printers']           = 10;
$MAX['model_monitors']           = 10;
$MAX['model_peripherals']        = 10;
$MAX['model_phones']             = 10;
$MAX['model_networking']         = 10;
$MAX['netpoint']                 = 50;
$MAX['auto_update']              = 10;
$MAX['phone_power']              = 10;
$MAX['manufacturer']             = 10;

// USERS
$MAX['users_sadmin']   = 1;
$MAX['users_admin']    = 50;
$MAX['users_normal']   = 50;
$MAX['users_postonly'] = 100;
$MAX['enterprises']    = 5;
$MAX['contacts']       = 10;
$MAX['groups']         = 3;

// INVENTORY ITEMS
$MAX['computers']             = 100;
$MAX['printers']              = 10;
$MAX['networking']            = $MAX['locations'];
$MAX['monitors']              = $MAX['computers'];
$MAX['type_of_consumables']   = 10;
$MAX['consumables_stock']     = 2;
$MAX['consumables_given']     = 4;
$MAX['type_of_cartridges']    = 5;
$MAX['cartridges_by_printer'] = 10;
$MAX['cartridges_stock']      = 2;
$MAX['device']                = 10;
$MAX['software']              = 50;
$MAX['softwareversions']      = 5;
$MAX['softwareinstall']       = $MAX['computers'];
$MAX['softwarelicenses']      = 2;
$MAX['global_peripherals']    = 10;

// DIRECT PERIPHERALS CONNECTED
$percent['peripherals'] = 5;

// DIRECT CONNECTED PRINTERS
$percent['printer'] = 5;

// PERCENT ELEMENTIN SPECIAL STATE
$percent['state'] = 70;

//PERIPHERALS
$MAX['connect_for_peripherals'] = 2;

// TRACKING :
$percent['tracking_on_item']   = 30;
$MAX['general_tracking']       = 100;
$percent['closed_tracking']    = 80;
$percent['followups']          = 50;
$percent['tasks']              = 50;
$percent['satisfaction']       = 50;
$percent['answersatisfaction'] = 50;

// RESERVATION
$percent['reservationitems'] = 1;
$percent['reservations']     = 40;

// DOCUMENT
$MAX['document'] = 10;
$DOC_PER_ITEM    = 2;

// CONTRACT
$MAX['contract']   = 10;
$CONTRACT_PER_ITEM = 1;

// DISK
$MAX_DISK = 5;

//Doc cache
$DOCUMENTS = array();


foreach ($MAX as $key => $val) {
   $MAX[$key]  = $multiplicator*$val;
   $LAST[$key] = 0;
}

$net_port = array();
$vlan_loc = array();

generateGlobalDropdowns();

DBmysql::optimize_tables();

// Force entity right
$_SESSION['glpiactive_profile']['entity'] = 127;

// Root entity
generate_entity(0);

// Entite
$added = 0;
$entity = new Entity ();
for ($i=0 ; $i<max(1,pow($entity_number,1/2))&&$added<$entity_number ; $i++) {
   $added++;
   $newID = $entity->add(array('name'      => "entity $i",
                               'comment'   => "comment entity $i"));
   generate_entity($newID);

   for ($j=0 ; $j<mt_rand(0,pow($entity_number,1/2))&&$added<$entity_number ; $j++) {
      $added++;
      $newID2 = $entity->add(array('name'         => "s-entity $j",
                                   'comment'      => "comment s-entity $j",
                                   'entities_id'  => $newID));
      generate_entity($newID2);

      for ($k=0 ; $k<mt_rand(0,pow($entity_number,1/2))&&$added<$entity_number ; $k++) {
         $added++;
         $newID3 = $entity->add(array('name'         => "ss-entity $k",
                                      'comment'      => "comment ss-entity $k",
                                      'entities_id'  => $newID2));
         generate_entity($newID3);
      }
   }
}

DBmysql::optimize_tables();
// clean messages;
$_SESSION["MESSAGE_AFTER_REDIRECT"]= [];
