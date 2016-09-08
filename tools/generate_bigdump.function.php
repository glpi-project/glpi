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


// BIG DUMP GENERATION FOR THE 0.6 VERSION

$IP       = array(10, 0, 0, 0);
$MAC      = array(8, 0, 20, 30, 40, 50);
$NETPOINT = array(0, 0, 0, 0);


/** Generate bigdump : Get next netpoint name
**/
function getNextNETPOINT() {
   global $NETPOINT;

   $type = array("V", "D", "I");
   $NETPOINT[3] = ($NETPOINT[3]+1)%3;

   if ($NETPOINT[3]==1) {
      $NETPOINT[2] = max(1, ($NETPOINT[2]+1)%255);

      if ($NETPOINT[2]==0) {
         $NETPOINT[1] = max(1, ($NETPOINT[1]+1)%255);

         if ($NETPOINT[1]==0) {
            $NETPOINT[0] = max(1, ($NETPOINT[0]+1)%255);
         }
      }
   }

   return $type[$NETPOINT[3]]."/".$NETPOINT[0]."/".$NETPOINT[1]."/".$NETPOINT[2];
}


/** Generate bigdump : Get next IP address
**/
function getNextIP() {
   global $IP;

   $IP[3] = max(1, ($IP[3]+1)%254);

   if ($IP[3]==1) {
      $IP[2] = max(1, ($IP[2]+1)%255);

      if ($IP[2]==0) {
         $IP[1] = max(1, ($IP[1]+1)%255);

         if ($IP[1]==0) {
            $IP[0] = max(1,($IP[0]+1)%255);
         }
      }
   }
   // Create IPnetwork
   if ($IP[3] == 1) {
      $net = new IPNetwork();
      $net->add(array('entities_id'  => 0,
                      'is_recursive' => 1,
                      'name'         => $IP[0].".".$IP[1].".".$IP[2].".0",
                      'addressable'  => 1,
                      'network'      => $IP[0].".".$IP[1].".".$IP[2].".0/255.255.255.0",
                      'gateway'      => $IP[0].".".$IP[1].".".$IP[2].".254"));
   }
   return array("ip"       => $IP[0].".".$IP[1].".".$IP[2].".".$IP[3],
                "gateway"  => $IP[0].".".$IP[1].".".$IP[2].".254",
                "subnet"   => $IP[0].".".$IP[1].".".$IP[2].".0",
                "netwmask" => "255.255.255.0");
}


/** Generate bigdump :  Get next MAC address
**/
function getNextMAC() {
   global $MAC;

   $MAC[5] = ($MAC[5]+1)%256;

   if ($MAC[5]==0) {
      $MAC[4] = ($MAC[4]+1)%256;

      if ($MAC[4]==0) {
         $MAC[3] = ($MAC[3]+1)%256;

         if ($MAC[3]==0) {
            $MAC[2] = ($MAC[2]+1)%256;

            if ($MAC[2]==0) {
               $MAC[1] = ($MAC[1]+1)%256;

               if ($MAC[1]==0) {
                  $MAC[0] = ($MAC[0]+1)%256;
               }
            }
         }
      }
   }

   return dechex($MAC[0]).":".dechex($MAC[1]).":".dechex($MAC[2]).":".dechex($MAC[3]).":".
          dechex($MAC[4]).":".dechex($MAC[5]);
}


/**  Generate bigdump : Create networkport ethernet
 *
 * @since version 0.84
 *
 * @param $itemtype        item type
 * @param $items_id        item ID
 * @param $entities_id     item entity ID
 * @param $locations_id    ID of the location trying to link with network equipment (default 0)
**/
function addNetworkEthernetPort($itemtype, $items_id, $entities_id, $locations_id=0) {
   global $NET_LOC, $NET_PORT, $MAX, $VLAN_LOC;

   // Add networking ports
   $newIP   = getNextIP();
   $newMAC  = getNextMAC();

   if (($itemtype == 'NetworkEquipment') && $locations_id) {
      // Find father locations_id;
      $loc = new Location();
      if ($loc->getFromDB($locations_id)) {
         $locations_id = $loc->getField('locations_id');
      } else {
         $locations_id = 0;
      }
   }

   //insert netpoint
   $netpoint   = new NetPoint();
   $netpointID = $netpoint->add(toolbox::addslashes_deep(
                                array('entities_id'  => $entities_id,
                                      'locations_id' => $locations_id,
                                      'name'         => getNextNETPOINT(),
                                      'comment'      => "comment 'netpoint $locations_id")));

   if ($locations_id && !isset($VLAN_LOC[$locations_id])) {
      $vlanID                  = mt_rand(1,$MAX["vlan"]);
      $VLAN_LOC[$locations_id] = $vlanID;
   }
   if (!isset($NET_PORT[$itemtype][$items_id])) {
      $NET_PORT[$itemtype][$items_id]=0;
   }
   $np          = new NetworkPort();
   $nv          = new NetworkPort_Vlan();
   $newportname = "port of $itemtype-$items_id";
   $refportID   = 0;

   if ($locations_id && isset($NET_LOC[$locations_id]) && $NET_LOC[$locations_id]) {
      $refportname  = "link 'port to  $itemtype-$items_id";
      $newportname .= " link to 'NetworkEquipment' -".$NET_LOC[$locations_id];
      $newMAC2      = getNextMAC();
      $newIP2      = getNextIP();

      // Create new port on ref item
      $param = toolbox::addslashes_deep(
               array('itemtype'                 => 'NetworkEquipment',
                     'items_id'                 => $NET_LOC[$locations_id],
                     'entities_id'              => $entities_id,
                     'logical_number'  => $NET_PORT['NetworkEquipment'][$NET_LOC[$locations_id]]++,
                     'name'                     => "name '$refportname",
                     'instantiation_type'       => 'NetworkPortEthernet',
                     'mac'                      => $newMAC2,
                     'comment'                  => "comment '$refportname",
                     'netpoints_id'             => $netpointID,
                     'NetworkName_name'         => "NetworkEquipment$itemtype-$items_id-$entities_id",
                     'NetworkName__ipaddresses' => array(-100 => $newIP2['ip']),
                     ));

                     $np->splitInputForElements($param);
      $refportID = $np->add($param);
      $np->updateDependencies(1);
      if (isset($VLAN_LOC[$locations_id]) && $refportID) {
         $nv->add(array('networkports_id' => $refportID,
                        'vlans_id'        => $VLAN_LOC[$locations_id]));
      }
   }

//    $query = "INSERT INTO `glpi_networkports`
//                VALUES (NULL, '$netwID', 'NetworkEquipment', '$ID_entity', '0',
//                      '".$NET_PORT['NetworkEquipment'][$netwID]++."',
//                      'link port to netw ".$NET_LOC[$data['locations_id']]."',
//                      '".$newIP['ip']."', '$newMAC', '$iface', '$netpointID',
//                      '".$newIP['netwmask']."', '".$newIP['gateway']."',
//                      '".$newIP['subnet']."','comment')";
//    $DB->query($query) or die("PB REQUETE ".$query);

   $param = toolbox::addslashes_deep(
            array('itemtype'                 => $itemtype,
                  'items_id'                 => $items_id,
                  'entities_id'              => $entities_id,
                  'logical_number'           => $NET_PORT[$itemtype][$items_id]++,
                  'name'                     => "name '$newportname",
                  'instantiation_type'       => 'NetworkPortEthernet',
                  'mac'                      => $newMAC,
                  'comment'                  => "comment '$newportname",
                  'netpoints_id'             => $netpointID,
                  'NetworkName_name'         => "$itemtype-$items_id-$entities_id",
                  'NetworkName__ipaddresses' => array(-100 => $newIP['ip']),
                  ));

                  $np->splitInputForElements($param);
   $newportID = $np->add($param);

   $np->updateDependencies(1);
   if (isset($VLAN_LOC[$locations_id]) && $newportID) {
      $nv->add(array('networkports_id' => $newportID,
                     'vlans_id'        => $VLAN_LOC[$locations_id]));
   }
   if ($locations_id && $refportID && $newportID) {
      // link ports
      $nn = new Networkport_Networkport();
      $nn->add(array('networkports_id_1' => $refportID,
                     'networkports_id_2' => $newportID,));
   } else {
      if ($locations_id) {
      }
   }
}


/**  Generate bigdump : make an item reservable
 *
 * @param $type      item type
 * @param $ID        item ID
 * @param $ID_entity item entity ID
**/
function addReservation($type, $ID, $ID_entity) {
   global $percent, $DB, $FIRST, $LAST;

   $current_year = date("Y");

   if (mt_rand(0,100)<$percent['reservationitems']) {
      $ri  = new Reservationitem();
      $r   = new Reservation();
      $tID = $ri->add(toolbox::addslashes_deep(
                      array('itemtype'     => $type,
                            'entities_id'  => $ID_entity,
                            'is_recursive' => 0,
                            'items_id'     => $ID,
                            'comment'      => "comment ' $ID $type",
                            'is_active'    => 1)));

      $date1 = strtotime('-2 week'); // reservations since 2 weeks
      $date2 = $date1;
      $i     = 0;

      while (mt_rand(0,100)<$percent['reservations']) {
         $date1 = $date2+HOUR_TIMESTAMP*(1+mt_rand(0,10)); // min 10 hours between each resa max
         $date2 = $date1+HOUR_TIMESTAMP*mt_rand(1,10); // A reservation from 1 to 5 hours
//          echo $tID.' '.date("Y-m-d H:i:s", $date1).'->'.date("Y-m-d H:i:s", $date2).'<br>';
         $r->add(toolbox::addslashes_deep(
                 array('reservationitems_id' => $tID,
                       'begin'               => date("Y-m-d H:i:s", $date1),
                       'end'                 => date("Y-m-d H:i:s", $date2),
                       'users_id'            => mt_rand($FIRST['users_normal'],
                                                        $LAST['users_postonly']),
                       'comment'             => "comments '$i ".Toolbox::getRandomString(15))));
         $i++;
      }
   }
}


/** Generate bigdump : add documents to an item
 *
 * @param $type   item type
 * @param $ID     item ID
**/
function addDocuments($type, $ID) {
   global $DOC_PER_ITEM, $DB, $FIRST, $LAST, $DOCUMENTS;

   $nb   = mt_rand(0, $DOC_PER_ITEM);
   $docs = array();

   for ($i=0 ; $i<$nb ; $i++) {
      $docs[] = mt_rand($FIRST["document"], $LAST["document"]);
   }
   $docs = array_unique($docs);
   $di   = new Document_Item();
   foreach ($docs as $val) {
      if (isset($DOCUMENTS[$val])) {
         list($entID, $recur) = explode('-',$DOCUMENTS[$val]);
         $di->add(array('documents_id' => $val,
                        'itemtype'     => $type,
                        'items_id'     => $ID,
                        'entities_id'  => $entID,
                        'is_recursive' => $recur));
      }
   }
}


/** Generate bigdump : add infocoms to an item
 *
 * @param $type            item type
 * @param $ID              item ID
 * @param $ID_entity       entity ID
 * @param $is_recursive    (default 0)
**/
function addInfocoms($type, $ID, $ID_entity, $is_recursive=0) {
   global $DB, $FIRST, $LAST;

   $current_year = date('Y');

   $orderdate     = strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
   $buydate       = $orderdate+mt_rand(0, 60)*DAY_TIMESTAMP;
   $deliverydate  = $orderdate+mt_rand(0, 60)*DAY_TIMESTAMP;
   $usedate       = $deliverydate+mt_rand(0, 60)*DAY_TIMESTAMP;
   $warrantydate  = $deliverydate;
   $inventorydate = $deliverydate;

   $orderdate     = date("Y-m-d", intval($orderdate));
   $buydate       = date("Y-m-d", intval($buydate));
   $deliverydate  = date("Y-m-d", intval($deliverydate));
   $usedate       = date("Y-m-d", intval($usedate));
   $warrantydate  = date("Y-m-d", intval($warrantydate));
   $inventorydate = date("Y-m-d", intval($inventorydate));

   $i = new Infocom();
   $i->add(toolbox::addslashes_deep(
           array('itemtype'           => $type,
                  'items_id'          => $ID,
                  'entities_id'       => $ID_entity,
                  'is_recursive'      => $is_recursive,
                  'buy_date'          => $buydate,
                  'use_date'          => $usedate,
                  'warranty_duration' => mt_rand(12,36),
                  'warranty_info'     => "infowar ' $type $ID",
                  'suppliers_id'      => mt_rand($FIRST["enterprises"], $LAST['enterprises']),
                  'order_number'      => "commande ' $type $ID",
                  'delivery_number'   => "BL ' $type $ID",
                  'immo_number'       => "immo ' $type $ID",
                  'value'             => mt_rand(0,5000),
                  'warranty_value'    => mt_rand(0,500),
                  'sink_time'         => mt_rand(1,7),
                  'sink_type'         => mt_rand(1,2),
                  'sink_coeff'        => mt_rand(2,5),
                  'comment'           => "comment ' $type $ID",
                  'bill'              => "bill ' $type $ID",
                  'budgets_id'        => mt_rand($FIRST['budget'], $LAST['budget']),
                  'order_date'        => $orderdate,
                  'delivery_date'     => $deliverydate,
                  'inventory_date'    => $inventorydate,
                  'warranty_date'     => $warrantydate)));
}


/** Generate bigdump : add contracts to an item
 *
 * @param $type   item type
 * @param $ID     item ID
**/
function addContracts($type, $ID) {
   global $CONTRACT_PER_ITEM, $DB, $FIRST, $LAST;

   $nb  = mt_rand(0, $CONTRACT_PER_ITEM);
   $con = array();

   for ($i=0 ; $i<$nb ; $i++) {
      $con[] = mt_rand($FIRST["contract"], $LAST["contract"]);
   }
   $con = array_unique($con);
   $ci  = new Contract_Item();
   foreach ($con as $val) {
      $ci->add(array('contracts_id' => $val,
                     'itemtype'     => $type,
                     'items_id'     => $ID));
   }
}


/** Generate bigdump : add tickets to an item
 *
 * @param $type      item type
 * @param $ID        item ID
 * @param $ID_entity entity ID
**/
function addTracking($type, $ID, $ID_entity) {
   global $percent, $DB, $MAX, $FIRST, $LAST;

   $current_year = date("Y");

   while (mt_rand(0,100)<$percent['tracking_on_item']) {
      // ticket closed ?
      $status    = CommonITILObject::CLOSED;
      $closedate = "";
      $solvedate = "";

      $opendate  = time() - mt_rand(0, 365)*DAY_TIMESTAMP - mt_rand(0, 10)*HOUR_TIMESTAMP
                   - mt_rand(0, 60)*MINUTE_TIMESTAMP - mt_rand(0, 60);

      if (mt_rand(0,100)<$percent['closed_tracking']) {
         $rtype = mt_rand(0, 100);

         if ($rtype<20) {
            $status = CommonITILObject::SOLVED;
         } else {
            $status = CommonITILObject::CLOSED;
         }

      } else {
         $rtype = mt_rand(0, 100);

         if ($rtype<20) {
            $status = CommonITILObject::INCOMING;

         } else if ($rtype<40) {
            $status = CommonITILObject::WAITING;

         } else if ($rtype<80) {
            $status = CommonITILObject::PLANNED;
            $date3  = $opendate+mt_rand(10800, 7776000); // + entre 3 heures et 3 mois
            $date4  = $date3+10800; // + 3 heures

         } else {
            $status = CommonITILObject::ASSIGNED;
         }
      }

      // Author
      $users[0] = mt_rand($FIRST['users_normal'], $LAST['users_postonly']);

      // Assign user
      $users[1] = 0;

      if ($status != CommonITILObject::INCOMING) {
         $users[1] = mt_rand($FIRST['users_sadmin'], $LAST['users_admin']);
      }
      $enterprise = 0;

      if (mt_rand(0,100)<20) {
         $enterprise = mt_rand($FIRST["enterprises"], $LAST['enterprises']);
      }

      $firstactiontime = mt_rand(0, 10)*DAY_TIMESTAMP+mt_rand(0, 10)*HOUR_TIMESTAMP
                         +mt_rand(0, 60)*MINUTE_TIMESTAMP;
      $solvetime       = 0;
      $closetime       = 0;
      $actiontime      = 0;

      $solution        = "";
      $solutiontype    = 0;
      $due_date        = $opendate + $firstactiontime+mt_rand(0, 10)*DAY_TIMESTAMP+
                         mt_rand(0, 10)*HOUR_TIMESTAMP+mt_rand(0, 60)*MINUTE_TIMESTAMP;
      $duedatetoadd    = date("Y-m-d H:i:s", intval($due_date));


      if (($status == CommonITILObject::CLOSED) || ($status == CommonITILObject::SOLVED)) {
         $solvetime = $firstactiontime+mt_rand(0, 10)*DAY_TIMESTAMP+mt_rand(0, 10)*HOUR_TIMESTAMP+
                      mt_rand(0, 60)*MINUTE_TIMESTAMP;
         $solvedate = $opendate+$solvetime;
         $closedate = $opendate+$solvetime;
         $actiontime = mt_rand(0, 10)*HOUR_TIMESTAMP+
                      mt_rand(0, 60)*MINUTE_TIMESTAMP;
         if ($status == CommonITILObject::CLOSED) {
            $closetime = $solvetime+mt_rand(0, 5)*DAY_TIMESTAMP+mt_rand(0, 10)*HOUR_TIMESTAMP+
                         mt_rand(0, 60)*MINUTE_TIMESTAMP;
            $closedate = $opendate+$closetime;
         }
         $solutiontype = mt_rand($FIRST['solutiontypes'], $LAST['solutiontypes']);
         $solution     = "Solution '".Toolbox::getRandomString(20);
      }
      $updatedate = $opendate+max($firstactiontime, $solvetime, $closetime);
      $hour_cost  = 100;

      $closedatetoadd = 'NULL';
      if (!empty($closedate)) {
         $closedatetoadd = date("Y-m-d H:i:s", intval($closedate));
      }

      $solvedatetoadd = 'NULL';
      if (!empty($solvedate)) {
         $solvedatetoadd = date("Y-m-d H:i:s",intval($solvedate));
      }
      $t   = new Ticket();
      $tID = $t->add(toolbox::addslashes_deep(
                     array('entities_id'                 => $ID_entity,
                           'name'                        => "Title '".Toolbox::getRandomString(20),
                           'date'                        => date("Y-m-d H:i:s", intval($opendate)),
                           'closedate'                   => $closedatetoadd,
                           'solvedate'                   => $solvedatetoadd,
                           'date_mod'                    => date("Y-m-d H:i:s", intval($updatedate)),
                           'users_id_lastupdater'        => $users[0],
                           'status'                      => $status,
                           'users_id_recipient'          => $users[0],
                           'requesttypes_id'             => mt_rand(0,6),
                           '_suppliers_id_assign'        => $enterprise,
                           'itemtype'                    => $type,
                           'items_id'                    => $ID,
                           'content'                     => "tracking '".Toolbox::getRandomString(15),
                           'urgency'                     => mt_rand(1,5),
                           'impact'                      => mt_rand(1,5),
                           'priority'                    => mt_rand(1,5),
                           'itilcategories_id'           => mt_rand(0, $MAX['tracking_category']),
                           'type'                        => mt_rand(1,2),
                           'solutiontypes_id'            => $solutiontype,
                           'locations_id'                => mt_rand($FIRST['locations'],
                                                                    $LAST['locations']),
                           'solution'                    => $solution,
                           'actiontime'                  => $actiontime,
                           'due_date'                    => $duedatetoadd,
                           'close_delay_stat'            => $closetime,
                           'solve_delay_stat'            => $solvetime,
                           'takeintoaccount_delay_stat'  => $firstactiontime,
                           '_users_id_requester'         => $users[0],
                           '_users_id_assign'            => $users[1],
                           '_groups_id_assign'           => mt_rand($FIRST["techgroups"],
                                                                    $LAST['techgroups']),
                           '_groups_id_requester'        => mt_rand($FIRST["groups"], $LAST['groups']),
                     )));

      // Add followups
      $i     = 0;
      $fID   = 0;
      $first = true;
      $date  = 0;
      $tf    = new TicketFollowup();
      while (mt_rand(0,100)<$percent['followups']) {
         if ($first) {
            $date = $opendate+$firstactiontime;
            $first = false;

         } else {
            $date += mt_rand(3600, 7776000);
         }
         $tf->add(toolbox::addslashes_deep(
                  array('tickets_id'      => $tID,
                        'date'            => date("Y-m-d H:i:s", $date),
                        'users_id'        => $users[1],
                        'content'         => "followup $i '".Toolbox::getRandomString(15),
                        'requesttypes_id' => mt_rand(0, 3))));
         $i++;
      }
      $tt = new TicketTask();
      while (mt_rand(0,100)<$percent['tasks']) {
         $doplan=false;
         if ($first) {
            $date   = $opendate+$firstactiontime;
            $first  = false;
            $doplan = true;
         } else {
            $date += mt_rand(3600, 7776000);
         }

         $begin       = $end = 'NULL';
         $assign_user = 0;
         $state       = 1;
         if ($status == CommonITILObject::PLANNED && $doplan) {
            $endtask = date("Y-m-d H:i:s", $date4);
            if ($endtask < date("Y-m-d H:i:s")) {
               $state = 2; // done
            }
         }
         $params = toolbox::addslashes_deep(
                   array('tickets_id'        => $tID,
                         'taskcategories_id' => mt_rand($FIRST['taskcategory'], $LAST['taskcategory']),
                         'date'              => date("Y-m-d H:i:s",$date),
                         'users_id'          => $users[1],
                         'content'           => "task $i '".Toolbox::getRandomString(15),
                         'is_private'        => mt_rand(0,1),
                         'state'             => $state,
                         'users_id_tech'    => $users[1]));

         if ($status == CommonITILObject::PLANNED && $doplan) {
            $params['plan'] = array('begin'       => date("Y-m-d H:i:s", $date3),
                                    'end'         => $endtask);
         }
         $tt->add($params);
         $i++;
      }

      $tc = new TicketCost();
      $params = toolbox::addslashes_deep(
                array('tickets_id'        => $tID,
                     'entities_id'       => $ID_entity,
                     'begin_date'        => date("Y-m-d H:i:s", intval($opendate)),
                     'name'              => "C'ost",
                     'cost_time'         => $hour_cost,
                     'actiontime'        => floor($actiontime/2)));

      // Insert satisfaction for stats
      if ($status == CommonITILObject::CLOSED
          && mt_rand(0,100) < $percent['satisfaction']) {

         $answerdate = 'NULL';
         if (mt_rand(0,100) < $percent['answersatisfaction']) {
            $answerdate = $closedatetoadd;
         }
         $ts = new TicketSatisfaction();
         $ts->add(toolbox::addslashes_deep(
                  array('tickets_id'   => $tID,
                        'type'         => mt_rand(1,2),
                        'date_begin'   => $closedatetoadd,
                        'date_answer'  => $answerdate,
                        'satisfaction' => mt_rand(0,5),
                        'comment'      => "comment ' satisfaction $tID")));
      }

   }

}


/** Generate bigdump : generate global dropdowns
**/
function generateGlobalDropdowns() {
   global $MAX, $DB;

   $items = array("CD", "CD-RW", "DVD-R", "DVD+R", "DVD-RW", "DVD+RW", "ramette papier",
                  "disk'ette", "ZIP");

   $dp = new ConsumableItemType();
   for ($i=0 ; $i<$MAX['consumable_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d' consommable $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("phone d'power");
   $dp    = new PhonePowerSupply();
   for ($i=0 ; $i<$MAX['phone_power'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "power ' $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Grand", "Moyen", "Mic'ro", "1U", "5U");
   $dp    = new DeviceCaseType();
   for ($i=0 ; $i<$MAX['case_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "power ' $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Laser", "Jet d'Encre", "Encre Solide");
   $dp    = new CartridgeItemType();
   for ($i=0 ; $i<$MAX['cartridge_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d' cartouche $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Technicien", "Commercial", "Technico-Commercial", "President", "Secretaire",
                  "Directeur d'agence");
   $dp    = new ContactType();
   for ($i=0 ; $i<$MAX['contact_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d' contact $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }

   $items = array("Maintenance", "Support", "Location", "Adhesion");
   $dp    = new ContractType();
   for ($i=0 ; $i<$MAX['contract_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d' crontact $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Fournisseur", "Transporteur", "SSII", "Revendeur d'", "Assembleur", "SSLL",
                  "Financeur", "Assureur");
   $dp    = new SupplierType();
   for ($i=0 ; $i<$MAX['enttype'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d'entreprise $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("H.07.02", "I.07.56", "P51", "P52", "1.60", "4.06", "43-4071299", "1.0.14",
                  "3.0.1", "rev 1.0", "rev 1.1", "rev 1.2", "rev 1.2.1", "rev 2.0", "rev 3.0");
   $dp    = new NetworkEquipmentFirmware();
   for ($i=0 ; $i<$MAX['firmware'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "firmware  $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Fire'wire");
   $dp    = new InterfaceType();
   for ($i=0 ; $i<$MAX['interface'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d' disque dur $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("100 Base TX", "100 Base T4", "10 base T", "1000 Base SX", "1000 Base LX",
                  "1000 Base T", "ATM", "802.3 10 Base 2", "IEEE 803.3 10 Base 5");
   $dp    = new NetworkInterface();
   for ($i=0 ; $i<$MAX['iface'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type carte reseau $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Non", "Oui - generique", "Oui - specifique d'entite");
   $dp    = new AutoUpdateSystem();
   for ($i=0 ; $i<$MAX['auto_update'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type de mise a jour '$i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Assemble", "Latitude C600", "Latitude C700", "VAIO FX601", "VAIO FX905P",
                  "VAIO TR5MP", "L5000C", "A600K", "PowerBook G4");
   $dp    = new ComputerModel();
   for ($i=0 ; $i<$MAX['model'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "Modele $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment' $val")));
   }


   $items = array("4200 DTN", "4200 DN", "4200 N", "8400 ADP", "7300 ADP", "5550 DN",
                  "PIXMA iP8500", "Stylus Color 3000", "DeskJet 5950");
   $dp    = new PrinterModel();
   for ($i=0 ; $i<$MAX['model_printers'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "modele imprimante $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("LS902UTG", "MA203DT", "P97F+SB", "G220F", "10-30-75", "PLE438S-B0S",
                  "PLE481S-W", "L1740BQ", "L1920P", "SDM-X73H");
   $dp    = new MonitorModel();
   for ($i=0 ; $i<$MAX['model_monitors'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "modele moniteur $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("HP 4108GL", "HP 2524", "HP 5308", "7600", "Catalyst 4500", "Catalyst 2950",
                  "Catalyst 3750", "Catalyst 6500");
   $dp    = new NetworkEquipmentModel();
   for ($i=0 ; $i<$MAX['model_networking'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "modele materiel reseau $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("DCS-2100+", "DCS-2100G", "KD-P35B", "Optical 5000", "Cordless", "ASR 600",
                  "ASR 375", "CS21", "MX5020", "VS4121", "T3030", "T6060");
   $dp    = new PeripheralModel();
   for ($i=0 ; $i<$MAX['model_peripherals'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "modele peripherique $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Alcatel Temporis 22", "Aastra 5370ip", "Alcatel-Lucent 400 DECT Handset",
                  "BlackBerry Curve 9300");
   $dp    = new PhoneModel();
   for ($i=0 ; $i<$MAX['model_phones'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "modele phone $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment' $val")));
   }


   $items = array("SIC", "LMS", "LMP", "LEA", "SP2MI", "STIC", "MATH", "ENS-MECA", "POUBELLE",
                  "WIFI");
   $dp    = new Network();
   for ($i=0 ; $i<$MAX['network'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "reseau $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Windows XP Pro SP2", "Linux (Debian)", "Mac OS X", "Linux (Mandriva 2006)",
                  "Linux (Redhat)", "Windows 98", "Windows 2000", "Windows XP Pro SP1",
                  "LINUX (Suse)", "Linux (Mandriva 10.2)");
   $dp    = new OperatingSystem();
   for ($i=0 ; $i<$MAX['os'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "os $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("XP Pro", "XP Home", "10.0", "10.1", "10.2", "2006", "Sarge");
   $dp    = new operatingSystemVersion();
   for ($i=0 ; $i<$MAX['os_version'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "osversion $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Service Pack 1", "Service Pack 2", "Service Pack 3", "Service Pack 4");
   $dp    = new OperatingSystemServicePack();
   for ($i=0 ; $i<$MAX['os_sp'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "ossp $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("DDR2");
   $dp    = new DeviceMemoryType();
   for ($i=0 ; $i<$MAX['ram_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type de ram $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment' $val")));
   }

   $items = array('Bureautique', 'Calcul', "logiciel d'antivirus", 'Multimédia');
   $dp    = new SoftwareCategory();
   for ($i=0 ; $i<max(1,pow($MAX['softwarecategory'],1/2)) ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "category $i";
      }
      $newID = $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                                       'comment' => "comment $val")));

      for ($j=0 ; $j<mt_rand(0,pow($MAX['softwarecategory'],1/2)) ; $j++) {
         $newID2 = $dp->add(toolbox::addslashes_deep(array('name'    => "s-category '$j",
                                                           'comment' => "comment d' $val s-category $j",
                                                           'softwarecategories_id'
                                                                     => $newID)));
      }
   }
   $MAX['rubdocs'] = getMaxItem('glpi_softwarecategories');

   $dp = new SoftwareLicenseType();
   for ($i=0 ; $i<$MAX['licensetype'] ; $i++) {
      $val = "type ' $i";
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("SIC", "LMS", "LMP", "LEA", "SP2MI", "STIC", "MATH", "ENS-MECA", "POUBELLE",
                  "WIFI");
   $dp    = new Vlan();
   for ($i=0 ; $i<$MAX['vlan'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "VLAN $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val",
                                              'tag'     => $i)));
   }


   $items = array("Portable", "Desktop", "Tour");
   $dp    = new ComputerType();
   for ($i=0 ; $i<$MAX['type_computers'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type ordinateur $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Laser A4", "Jet d'Encre", "Laser A3", "Encre Solide A4", "Encre Solide A3");
   $dp    = new PrinterType();
   for ($i=0 ; $i<$MAX['type_printers'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type d'imprimante $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("TFT 17", "TFT 19", "TFT 21", "CRT 17", "CRT 19", "CRT 21", "CRT 15");
   $dp    = new MonitorType();
   for ($i=0 ; $i<$MAX['type_monitors'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type ecran $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Switch", "Routeur", "Hub", "Borne Wifi", "borne d'accueil");
   $dp    = new NetworkEquipmentType();
   for ($i=0 ; $i<$MAX['type_networking'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type de materiel reseau '$i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Clavier", "Souris", "Webcam", "Enceintes", "Scanner", "Clef USB", "d'autres");
   $dp    = new PeripheralType();
   for ($i=0 ; $i<$MAX['type_peripherals'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type de peripheriques '$i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Analogique", "IP", );
   $dp    = new PhoneType();
   for ($i=0 ; $i<$MAX['type_phones'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type de phone $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("DELL", "HP", "IIYAMA", "CANON", "EPSON", "LEXMARK", "ASUS", "MSI");
   $dp    = new Manufacturer();
   for ($i=0 ; $i<$MAX['manufacturer'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "manufacturer $i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment '$val")));
   }


   $items = array("Ingénieur", "Stagiaire", "Secrétaire", "ouvrier d'atelier");
   $dp    = new UserCategory();
   for ($i=0 ; $i<$MAX['user_type'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "user type d'$i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }


   $items = array("Président", "Agent Comptable", "Directeur d'agence");
   $dp    = new UserTitle();
   for ($i=0 ; $i<$MAX['user_title'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "user type '$i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                              'comment' => "comment $val")));
   }

   $items = array("Documentation", "Facture", "Bon Livraison", "Bon commande", "Capture d'Ecran",
                  "Dossier Technique");
   $dp    = new DocumentCategory();
   for ($i=0 ; $i<max(1,pow($MAX['rubdocs'],1/2)) ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "category $i";
      }
      $newID = $dp->add(toolbox::addslashes_deep(array('name'    => $val,
                                                       'comment' => "comment $val")));

      for ($j=0 ; $j<mt_rand(0,pow($MAX['rubdocs'],1/2)) ; $j++) {
         $newID2 = $dp->add(toolbox::addslashes_deep(
                            array('name'                        => "s-category '$j",
                                  'comment'                     => "comment d' $val s-category $j",
                                  'documentcategories_id'       => $newID)));
      }
   }
   $MAX['rubdocs'] = getMaxItem('glpi_documentcategories');

   $dp = new ItilCategory();
   // GLobal ticket categories : also specific ones by entity
   for ($i=0 ; $i<max(1,pow($MAX['tracking_category'],1/3)) ; $i++) {
      $newID = $dp->add(toolbox::addslashes_deep(
                        array('name'                        => "category '$i",
                              'comment'                     => "comment ' category $i",
                              'is_recursive'                => 1,
                              'tickettemplates_id_incident' => 1,
                              'tickettemplates_id_demand'   => 1)));

      for ($j=0 ; $j<mt_rand(0,pow($MAX['tracking_category'],1/2)) ; $j++) {
         $newID2 = $dp->add(toolbox::addslashes_deep(
                            array('name'                        => "s-category '$j",
                                  'comment'                     => "comment 'category $i s-category $j",
                                  'is_recursive'                => 1,
                                  'tickettemplates_id_incident' => 1,
                                  'tickettemplates_id_demand'   => 1,
                                  'itilcategories_id'           => $newID)));

         for ($k=0 ; $k<mt_rand(0,pow($MAX['tracking_category'],1/2)) ; $k++) {
            $newID3 = $dp->add(toolbox::addslashes_deep(
                               array('name'                        => "ss-category' $k",
                                     'comment'      => "comment ' category $i s-category $j ss-category $k",
                                     'is_recursive'                => 1,
                                     'tickettemplates_id_incident' => 1,
                                     'tickettemplates_id_demand'   => 1,
                                     'itilcategories_id'           => $newID2)));
         }
      }
   }

   $query = "OPTIMIZE TABLE `glpi_itilcategories`";
   $DB->query($query) or die("PB REQUETE ".$query);

   regenerateTreeCompleteName("glpi_itilcategories");

   $MAX['tracking_category'] = getMaxItem('glpi_itilcategories');

   // DEVICE
   $items = array("Textorm 6A19", "ARIA", "SLK3000B-EU", "Sonata II", "TA-212", "TA-551", "TA-581",
                  "TAC-T01", "CS-512", "Li PC-60891", "STT-TJ02S");
   $dp    = new DeviceCase();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "case $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment '$val",
                     'devicecasetypes_id' => mt_rand(0,$MAX["case_type"]),
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']))));
   }


   $items = array("Escalade 8006-2LP", "Escalade 8506-4LP", "2810SA", "1210SA", "DuoConnect",
                  "DU-420", "DUB-A2", "FastTrak SX4100B", "DC-395U", "TFU-H33PI");
   $dp    = new DeviceControl();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "control $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment ' $val",
                     'interfacetypes_id'  => mt_rand(0,$MAX["interface"]),
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']))));
   }


   $items = array("DUW1616", "DRW-1608P", "DW1625", "GSA-4160B", "GSA-4165B", "GSA-4167RBB",
                  "SHW-16H5S", "SOHW-1673SX", "DVR-110D", "PX-716AL", "PX-755A");
   $dp    = new DeviceDrive();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "drive $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment '$val",
                     'is_writer'          => mt_rand(0,1),
                     'speed'              => mt_rand(0,60),
                     'interfacetypes_id'  => mt_rand(0,$MAX["interface"]),
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']))));
   }


   $items = array("A9250/TD", "AX550/TD", "Extreme N5900", "V9520-X/TD", "All-In-Wonder X800 GT",
                  "GV-NX66256D", "GV-RX80256DE", "Excalibur 9600XT", "X1300 IceQ",
                  "WinFast PX6200 TD", "Millennium 750","NX6600GT");
   $dp    = new DeviceGraphicCard();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "gfxcard $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment ' $val",
                     'interfacetypes_id'  => mt_rand(0,$MAX["interface"]),
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']),
                     'memory_default'     => 256*mt_rand(0,8))));
   }


   $items = array("Deskstar 7K500", "Deskstar T7K250", "Atlas 15K II", "DiamondMax Plus",
                  "SpinPoint P - SP2514N", "Barracuda 7200.9", "WD2500JS", "WD1600JB", "WD1200JD");
   $dp    = new DeviceHardDrive();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "hdd  $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment' $val",
                     'interfacetypes_id'  => mt_rand(0,$MAX["interface"]),
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']),
                     'capacity_default'   => mt_rand(0,300),
                     'rpm'                => mt_rand(0,15000),
                     'cache'              => 51200*mt_rand(0,10))));
   }


   $items = array("DFE-530TX", "DFE-538TX", "PWLA8492MF", "PWLA8492MT", "USBVPN1", "GA311", "FA511",
                  "TEG-PCBUSR", "3C996-SX", "3C996B-T", "3C905C-TX-M");
   $dp    = new DeviceNetworkCard();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "iface  $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment' $val",
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']),
                     'bandwidth'          => mt_rand(0,1000))));
   }


   $items = array("AW8-MAX", "NV8", "AK86-L", "P4V88", "A8N-SLI", "A8N-VM", "K8V-MX", "K8N4-E",
                  "P5LD2", "GA-K8NE", "GA-8I945P Pro", "D945PBLL", "SE7525GP2", "865PE Neo3-F",
                  "K8N Neo4-F", "Thunder i7520 (S5360G2NR)", "Thunder K8SR - S2881UG2NR",
                  "Tiger K8QS Pro - S4882UG2NR", "Tomcat i875PF (S5105G2NR)");
   $dp    = new DeviceMotherBoard();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "moboard $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment' $val",
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']),
                     'chipset'            => 'chipset '.mt_rand(0,1000))));
   }


   $items = array("Instant TV Cardbus", "WinTV Express", "WinTV-NOVA-S-Plus", "WinTV-NOVA-T",
                  "WinTV-PVR-150");
   $dp    = new DevicePci();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "pci $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment '$val",
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']))));
   }


   $items = array("DB-Killer PW335", "DB-Killer PW385", "NeoHE 380", "NeoHE 450", "Phantom 500-PEC",
                  "TruePower 2.0 550", "Master RS-380", "EG375AX-VE-G-SFMA", "EG495AX");
   $dp    = new DevicePowerSupply();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "power $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment '$val",
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']),
                     'power'              => mt_rand(0,500).'W',
                     'is_atx'             => mt_rand(0,1))));
   }


   $items = array("Athlon 64 FX-57", "Athlon 64 FX-55", "Sempron 2400+", "Sempron 2600+",
                  "Celeron D 325", "Celeron D 330J", "Pentium 4 530J", "Pentium 4 631",
                  "Pentium D 830", "Pentium D 920");
   $dp    = new DeviceProcessor();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "processor $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'        => $val,
                     'is_recursive'       => 1,
                     'comment'            => "comment' $val",
                     'manufacturers_id'   => mt_rand(1,$MAX['manufacturer']),
                     'frequence'          => mt_rand(1000,3000),
                     'frequency_default'  => 1000+200*mt_rand(0,10),
                     'nbcores_default'    => mt_rand(1,8),
                     'nbthreads_default'  => mt_rand(1,4),
                     )));
   }


   $items = array("CM2X256A-5400C4", "CMX1024-3200C2", "CMXP512-3200XL", "TWIN2X1024-4300C3PRO",
                  "KTD-DM8400/1G", "KTH8348/1G", "KTD4400/256", "D6464D30A", "KTA-G5400/512",
                  "KVR667D2N5/1G", "KVR133X64C3/256");
   $dp    = new DeviceMemory();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "ram $i";
      }
      $dp->add(toolbox::addslashes_deep(
               array('designation'          => $val,
                     'is_recursive'         => 1,
                     'comment'              => "comment' $val",
                     'manufacturers_id'     => mt_rand(1,$MAX['manufacturer']),
                     'frequence'            => 100*mt_rand(0,10),
                     'size_default'         => 1024*mt_rand(0,6),
                     'devicememorytypes_id' => mt_rand(1,$MAX['ram_type']))));
   }


   $items = array("DDTS-100", "Audigy 2 ZS Platinum", "Audigy SE", "DJ Console Mk2",
                  "Gamesurround Muse Pocket USB", "Phase 22", "X-Fi Platinum", "Live! 24-bit",
                  "X-Fi Elite Pro");
   $dp    = new DeviceSoundCard();
   for ($i=0 ; $i<$MAX['device'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "sndcard $i";
      }
       $dp->add(toolbox::addslashes_deep(
                array('designation'         => $val,
                     'is_recursive'         => 1,
                     'comment'              => "comment '$val",
                     'manufacturers_id'     => mt_rand(1,$MAX['manufacturer']),
                     'type'                 => 'type '.mt_rand(0,100))));
   }

} // Fin generation global dropdowns


/** Generate bigdump : get max ID of a table
 *
 * @param $table table name
**/
function getMaxItem($table) {
   global $DB;

   $query = "SELECT MAX(`id`)
             FROM `$table`";
   $result = $DB->query($query) or die("PB REQUETE ".$query);

   return $DB->result($result, 0, 0);
}


/** Generate bigdump : generate items for an entity
 *
 * @param $ID_entity entity ID
**/
function generate_entity($ID_entity) {
   global $MAX, $DB, $percent, $FIRST, $LAST, $MAX_KBITEMS_BY_CAT, $MAX_DISK,
         $DOCUMENTS, $NET_PORT, $NET_LOC;

   regenerateTreeCompleteName("glpi_entities");

   $current_year = date("Y");


   // DOMAIN
   $items = array("SP2MI", "CAMPUS"," IUT86", "PRESIDENCE", "CEAT", "D'omaine");
   $dp    = new Domain();
   $FIRST["domain"] = getMaxItem("glpi_domains")+1;

   for ($i=0 ; $i<$MAX['domain'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "domain $ID_entity '$i";
      }
      $dp->add(toolbox::addslashes_deep(array('name'         => $val,
                                              'entities_id'  => $ID_entity,
                                              'is_recursive' => 1,
                                              'comment'      => "comment $val")));
   }
   $LAST["domain"] = getMaxItem("glpi_domains");


   // STATUS
   $items = array("Reparation", "En stock", "En fonction", "Retour SAV", "En attente d'");
   $dp    = new State();
   $FIRST["state"] = getMaxItem("glpi_states")+1;
   for ($i=0 ; $i<$MAX['state'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "State $ID_entity '$i";
      }
      $state_id = $dp->add(toolbox::addslashes_deep(array('name'         => $val,
                                                          'entities_id'  => $ID_entity,
                                                          'is_recursive' => 1,
                                                          'comment'      => "comment $val")));

      // generate sub status
      for ($j=0 ; $j<$MAX['state'] ; $j++) {
         $val2 = "Sub $val $j";

         $dp->add(toolbox::addslashes_deep(array('name'         => $val2,
                                                 'entities_id'  => $ID_entity,
                                                 'is_recursive' => 1,
                                                 'states_id'    => $state_id,
                                                 'comment'      => "comment $val")));
      }

   }
   $LAST["state"]      = getMaxItem("glpi_states");


   // glpi_groups
   $FIRST["groups"] = getMaxItem("glpi_groups")+1;
   $group           = new Group();
   for ($i=0 ; $i<$MAX['groups'] ; $i++) {
      $gID = $group->add(toolbox::addslashes_deep(
                         array('entities_id'  => $ID_entity,
                               'name'         => "group d'$i",
                               'comment'      => "comment group d'$i",
                               'is_assign'    => 0)));

      // Generate sub group
      for ($j=0 ; $j<$MAX['groups'] ; $j++) {
         $group->add(toolbox::addslashes_deep(
                     array('entities_id'  => $ID_entity,
                           'name'         => "subgroup d'$j",
                           'comment'      => "comment subgroup d'$j of group $i",
                           'groups_id'    => $gID,
                           'is_assign'    => 0)));
      }
   }

   $LAST["groups"]      = getMaxItem("glpi_groups");

   $FIRST["techgroups"] = $LAST["groups"]+1;

   for ($i=0 ; $i<$MAX['groups'] ; $i++) {
         $group->add(toolbox::addslashes_deep(
                     array('entities_id'  => $ID_entity,
                           'name'         => "tech group d'$i",
                           'comment'      => "comment tech group d'$i")));
   }

   $LAST["techgroups"] = getMaxItem("glpi_groups");
   regenerateTreeCompleteName("glpi_groups");


   // glpi_users
   $FIRST["users_sadmin"] = getMaxItem("glpi_users")+1;
   $user                  = new User();
   $gu                    = new Group_User();
   for ($i=0 ; $i<$MAX['users_sadmin'] ; $i++) {
      $users_id = $user->add(toolbox::addslashes_deep(
                             array('name'               => "sadmin$i-$ID_entity",
                                   'password'           => "sadmin'$i",
                                   'password2'          => "sadmin'$i",
                                   'phone'              => "tel $i",
                                   'phone2'             => "tel2 $i",
                                   'mobile'             => "mobile $i",
                                   'realname'           => "sadmin '$i name",
                                   'firstname'          => "sadmin '$i firstname",
                                   'comment'            => "comment' $i",
                                   'usertitles_id'      => mt_rand(0,$MAX['user_title']),
                                   'usercategories_id'  => mt_rand(0,$MAX['user_type']),
                                   '_profiles_id'       => 4,
                                   '_entities_id'       => $ID_entity,
                                   '_is_recursive'      => 1
                                   )));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['groups'], $LAST['groups'])));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['techgroups'], $LAST['techgroups'])));
   }
   $LAST["users_sadmin"] = getMaxItem("glpi_users");
   $FIRST["users_admin"] = getMaxItem("glpi_users")+1;

   for ($i=0 ; $i<$MAX['users_admin'] ; $i++) {

      $users_id = $user->add(toolbox::addslashes_deep(
                             array('name'               => "admin$i-$ID_entity",
                                   'password'           => "admin'$i",
                                   'password2'          => "admin'$i",
                                   'phone'              => "tel $i",
                                   'phone2'             => "tel2 $i",
                                   'mobile'             => "mobile $i",
                                   'realname'           => "admin$i 'name",
                                   'firstname'          => "admin$i 'firstname",
                                   'comment'            => "comment '$i",
                                   'usertitles_id'      => mt_rand(0,$MAX['user_title']),
                                   'usercategories_id'  => mt_rand(0,$MAX['user_type']),
                                   '_profiles_id'       => 3,
                                   '_entities_id'       => $ID_entity,
                                   '_is_recursive'      => 1)));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['groups'], $LAST['groups']),
                     'is_manager'   => 1,
                     'is_delegate'  => 1));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['techgroups'], $LAST['techgroups']),
                     'is_manager'   => 1,
                     'is_delegate'  => 1));
   }

   $LAST["users_admin"]   = getMaxItem("glpi_users");
   $FIRST["users_normal"] = getMaxItem("glpi_users")+1;

   for ($i=0 ; $i<$MAX['users_normal'] ; $i++) {
      $users_id = $user->add(toolbox::addslashes_deep(
                             array('name'               => "normal$i-$ID_entity",
                                   'password'           => "normal'$i",
                                   'password2'          => "normal'$i",
                                   'phone'              => "tel $i",
                                   'phone2'             => "tel2 $i",
                                   'mobile'             => "mobile $i",
                                   'realname'           => "normal$i 'name",
                                   'firstname'          => "normal$i 'firstname",
                                   'comment'            => "comment '$i",
                                   'usertitles_id'      => mt_rand(0,$MAX['user_title']),
                                   'usercategories_id'  => mt_rand(0,$MAX['user_type']),
                                   '_profiles_id'       => 2,
                                   '_entities_id'       => $ID_entity,
                                   '_is_recursive'      => 1)));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['groups'], $LAST['groups'])));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['techgroups'], $LAST['techgroups'])));
   }

   $LAST["users_normal"]    = getMaxItem("glpi_users");
   $FIRST["users_postonly"] = getMaxItem("glpi_users")+1;

   for ($i=0 ; $i<$MAX['users_postonly'] ; $i++) {
      $users_id = $user->add(toolbox::addslashes_deep(
                             array('name'               => "postonly$i-$ID_entity",
                                   'password'           => "postonly'$i",
                                   'password2'          => "postonly'$i",
                                   'phone'              => "tel $i",
                                   'phone2'             => "tel2 $i",
                                   'mobile'             => "mobile $i",
                                   'realname'           => "postonly$i 'name",
                                   'firstname'          => "postonly$i 'firstname",
                                   'comment'            => "comment' $i",
                                   'usertitles_id'      => mt_rand(0,$MAX['user_title']),
                                   'usercategories_id'  => mt_rand(0,$MAX['user_type']),
                                   '_profiles_id'       => 1,
                                   '_entities_id'       => $ID_entity,
                                   '_is_recursive'      => 1)));

      $gu->add(array('users_id'     => $users_id,
                     'groups_id'    => mt_rand($FIRST['groups'], $LAST['groups'])));
   }

   $LAST["users_postonly"] = getMaxItem("glpi_users");


   $FIRST["kbcategories"] = getMaxItem("glpi_knowbaseitemcategories")+1;
   $kbc                   = new KnowbaseItemCategory();

   for ($i=0 ; $i<max(1,pow($MAX['kbcategories'],1/3)) ; $i++) {
      $newID = $kbc->add(toolbox::addslashes_deep(
                         array('entities_id'     => $ID_entity,
                               'is_recursive'    => 1,
                               'name'            => "entity ' categorie $i",
                               'comment'         => "comment ' categorie $i")));

      for ($j=0 ; $j<mt_rand(0,pow($MAX['kbcategories'],1/2)) ; $j++) {
         $newID2 = $kbc->add(toolbox::addslashes_deep(
                             array('entities_id'                 => $ID_entity,
                                   'is_recursive'                => 1,
                                   'name'                        => "entity s-categorie '$j",
                                   'comment'                     => "comment s-categorie '$j",
                                   'knowbaseitemcategories_id'   => $newID)));

         for ($k=0 ; $k<mt_rand(0,pow($MAX['kbcategories'],1/2)) ; $k++) {
            $newID2 = $kbc->add(toolbox::addslashes_deep(
                                array('entities_id'               => $ID_entity,
                                      'is_recursive'              => 1,
                                      'name'                      => "entity ss-categorie' $k",
                                      'comment'                   => "comment ss-categorie' $k",
                                      'knowbaseitemcategories_id' => $newID2)));
         }
      }
   }

   $query = "OPTIMIZE TABLE `glpi_knowbaseitemcategories`";
   $DB->query($query) or die("PB REQUETE ".$query);

   regenerateTreeCompleteName("glpi_knowbaseitemcategories");
   $LAST["kbcategories"] = getMaxItem("glpi_knowbaseitemcategories");


   // LOCATIONS
   $added              = 0;
   $FIRST["locations"] = getMaxItem("glpi_locations")+1;
   $loc                = new Location();
   for ($i=0 ; $i<pow($MAX['locations'],1/5)&&$added<$MAX['locations'] ; $i++) {
      $added++;
      $newID = $loc->add(toolbox::addslashes_deep(
                         array('entities_id'     => $ID_entity,
                               'is_recursive'    => 1,
                               'name'            => "location' $i",
                               'comment'         => "comment 'location $i",
                               'building'        => "building $i")));

      for ($j=0 ; $j<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'] ; $j++) {
         $added++;
         $newID2 = $loc->add(toolbox::addslashes_deep(
                             array('entities_id'     => $ID_entity,
                                   'is_recursive'    => 1,
                                   'name'            => "s-location '$j",
                                   'comment'         => "comment s-location '$j",
                                   'building'        => "building $i",
                                   'room'            => "stage '$j",
                                   'locations_id'    => $newID)));

         for ($k=0 ; $k<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'] ; $k++) {
            $added++;
            $newID3 = $loc->add(toolbox::addslashes_deep(
                                array('entities_id'     => $ID_entity,
                                      'is_recursive'    => 1,
                                      'name'            => "ss-location '$k",
                                      'comment'         => "comment ss-location' $k",
                                      'building'        => "building $i",
                                      'room'            => "part' $k",
                                      'locations_id'    => $newID2)));

            for ($l=0 ; $l<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'] ; $l++) {
               $added++;
               $newID4 = $loc->add(toolbox::addslashes_deep(
                                   array('entities_id'     => $ID_entity,
                                         'is_recursive'    => 1,
                                         'name'            => "sss-location' $l",
                                         'comment'         => "comment sss-location' $l",
                                         'building'        => "building $i",
                                         'room'            => "room' $l",
                                         'locations_id'    => $newID3)));

               for ($m=0 ; $m<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'] ; $m++) {
                  $added++;
                  $newID5 = $loc->add(toolbox::addslashes_deep(
                                      array('entities_id'     => $ID_entity,
                                            'is_recursive'    => 1,
                                            'name'            => "ssss-location' $m",
                                            'comment'         => "comment ssss-location' $m",
                                            'building'        => "building $i",
                                            'room'            => "room' $l-$m",
                                            'locations_id'    => $newID4)));
               }
            }
         }
      }
   }

   $query = "OPTIMIZE TABLE `glpi_locations`";
   $DB->query($query) or die("PB REQUETE ".$query);

   regenerateTreeCompleteName("glpi_locations");
   $LAST["locations"]=getMaxItem("glpi_locations");


   // Task categories
   $added                 = 0;
   $FIRST["taskcategory"] = getMaxItem("glpi_taskcategories")+1;
   $tc                    = new TaskCategory();
   for ($i=0 ; $i<pow($MAX['taskcategory'],1/5)&&$added<$MAX['taskcategory'] ; $i++) {
      $added++;
      $newID = $tc->add(toolbox::addslashes_deep(
                        array('entities_id'     => $ID_entity,
                              'is_recursive'    => 1,
                              'name'            => "ent$ID_entity taskcategory' $i",
                              'comment'         => "comment ent$ID_entity taskcategory' $i")));

      for ($j=0 ; $j<mt_rand(0,pow($MAX['locations'],1/4))&&$added<$MAX['locations'] ; $j++) {
         $newID2 = $tc->add(toolbox::addslashes_deep(
                            array('entities_id'        => $ID_entity,
                                  'is_recursive'       => 1,
                                  'name'               => "ent$ID_entity taskcategory' $i",
                                  'comment'            => "comment ent$ID_entity taskcategory' $i",
                                  'taskcategories_id'  => $newID)));
         $added++;
      }
   }

   $query = "OPTIMIZE TABLE `glpi_taskcategories`";
   $DB->query($query) or die("PB REQUETE ".$query);

   regenerateTreeCompleteName("glpi_taskcategories");
   $LAST["taskcategory"] = getMaxItem("glpi_taskcategories");

   $ic = new ItilCategory();
   // Specific ticket categories
   $newID = $ic->add(toolbox::addslashes_deep(
                     array('entities_id'     => $ID_entity,
                           'is_recursive'    => 1,
                           'name'            => "category for entity' $ID_entity",
                           'comment'         => "comment category for entity' $ID_entity",
                           'users_id'        => mt_rand($FIRST['users_sadmin'],$LAST['users_admin']),
                           'groups_id'       => mt_rand($FIRST['techgroups'],$LAST['techgroups']),
                           'tickettemplates_id_incident' => 1,
                           'tickettemplates_id_demand'   => 1)));

   for ($i=0 ; $i<max(1,pow($MAX['tracking_category'],1/3)) ; $i++) {
      $ic->add(toolbox::addslashes_deep(
               array('entities_id'                 => $ID_entity,
                     'is_recursive'                => 1,
                     'name'                        => "scategory for entity' $ID_entity",
                     'comment'                     => "comment scategory for entity' $ID_entity",
                     'users_id'                    => mt_rand($FIRST['users_sadmin'],
                                                              $LAST['users_admin']),
                     'groups_id'                   => mt_rand($FIRST['techgroups'],
                                                              $LAST['techgroups']),
                     'tickettemplates_id_incident' => 1,
                     'tickettemplates_id_demand'   => 1,
                     'itilcategories_id'           => $newID)));
   }

   regenerateTreeCompleteName("glpi_itilcategories");

   $FIRST["solutiontypes"] = getMaxItem("glpi_solutiontypes")+1;

   $items = array("d'après KB");
   $st    = new SolutionType();
   for ($i=0 ; $i<$MAX['solutiontypes'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "type de solution ' $i";
      }
      $st->add(toolbox::addslashes_deep(array('name'         => $val,
                                              'comment'      => "comment $val",
                                              'entities_id'  => $ID_entity,
                                              'is_recursive' => 1)));
   }
   $LAST["solutiontypes"] = getMaxItem("glpi_solutiontypes");


   $FIRST["solutiontemplates"] = getMaxItem("glpi_solutiontemplates")+1;
   $nb_items                   = mt_rand(0,$MAX['solutiontemplates']);
   $st                         = new SolutionTemplate();
   for ($i=0 ; $i<$nb_items ; $i++) {
      $st-> add(toolbox::addslashes_deep(
                array('entities_id'        => $ID_entity,
                      'is_recursive'       => 1,
                      'name'               => "solution' $i-$ID_entity",
                      'content'            => "content solution' $i-$ID_entity",
                      'solutiontypes_id'   => mt_rand(0,$MAX['solutiontypes']),
                      'comment'            => "comment solution' $i-$ID_entity")));
   }

   $LAST["solutiontemplates"] = getMaxItem("glpi_solutiontemplates");

   // Add Specific questions
   $k                = 0;
   $FIRST["kbitems"] = getMaxItem("glpi_knowbaseitems")+1;
   $ki               = new KnowbaseItem();
   $eki              = new Entity_KnowbaseItem();
   for ($i=$FIRST['kbcategories'] ; $i<=$LAST['kbcategories'] ; $i++) {
      $nb = mt_rand(0,$MAX_KBITEMS_BY_CAT);
      for ($j=0 ; $j<$nb ; $j++) {
         $k++;
         $newID = $ki->add(toolbox::addslashes_deep(
                           array('knowbaseitemcategories_id'   => $i,
                                 'name'      => "Entity' $ID_entity Question $k",
                                 'answer'    => "Answer' $k".Toolbox::getRandomString(50),
                                 'is_faq'    => mt_rand(0,1),
                                 'users_id'  => mt_rand($FIRST['users_sadmin'],
                                                        $LAST['users_admin']))));

         $eki->add(array('entities_id'       => $ID_entity,
                        'knowbaseitems_id'   => $newID,
                        'is_recursive'       => 0));
      }
   }


   // Add global questions
   for ($i=$FIRST['kbcategories'] ; $i<=$LAST['kbcategories'] ; $i++) {
      $nb = mt_rand(0,$MAX_KBITEMS_BY_CAT);
      for ($j=0 ; $j<$nb ; $j++) {
         $k++;
         $newID = $ki->add(toolbox::addslashes_deep(
                           array('knowbaseitemcategories_id'   => $i,
                                 'name'      => "Entity' $ID_entity Recursive Question $k",
                                 'answer'    => "Answer' $k".Toolbox::getRandomString(50),
                                 'is_faq'    => mt_rand(0,1),
                                 'users_id'  => mt_rand($FIRST['users_sadmin'],
                                                        $LAST['users_admin']))));

         $eki->add(array('entities_id'       => $ID_entity,
                        'knowbaseitems_id'   => $newID,
                        'is_recursive'       => 1));
      }
   }

   $LAST["kbitems"] = getMaxItem("glpi_knowbaseitems");


   // Ajout documents  specific
   $FIRST["document"] = getMaxItem("glpi_documents")+1;
   $doc               = new Document();
   for ($i=0 ; $i<$MAX['document'] ; $i++) {
      $link = "";
      if (mt_rand(0,100)<50) {
         $link = "http://linktodoc/doc$i";
      }

      $docID = $doc->add(toolbox::addslashes_deep(
                         array('entities_id'           => $ID_entity,
                               'is_recursive'          => 0,
                               'name'                  => "document' $i-$ID_entity",
                               'documentcategories_id' => mt_rand(1,$MAX['rubdocs']),
                               'comment'               => "commen't $i",
                               'link'                  => $link,
                               'notepad'               => "notes document' $i")));

      $DOCUMENTS[$docID] = $ID_entity."-0";
   }


   // Global ones
   for ($i=0 ; $i<$MAX['document']/2 ; $i++) {
      $link = "";
      if (mt_rand(0,100)<50) {
         $link = "http://linktodoc/doc$i";
      }

      $docID = $doc->add(toolbox::addslashes_deep(
                         array('entities_id'           => $ID_entity,
                               'is_recursive'          => 1,
                               'name'                  => "Recursive document' $i-$ID_entity",
                               'documentcategories_id' => mt_rand(1,$MAX['rubdocs']),
                               'comment'               => "comment' $i",
                               'link'                  => $link,
                               'notepad'               => "notes document' $i")));

      $DOCUMENTS[$docID] = $ID_entity."-1";
   }

   $LAST["document"] = getMaxItem("glpi_documents");


   // Ajout budgets  specific
   $FIRST["budget"] = getMaxItem("glpi_budgets")+1;
   $b               = new Budget();
   for ($i=0 ; $i<$MAX['budget'] ; $i++) {
      $date1 = strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
      $date2 = $date1+MONTH_TIMESTAMP*12*mt_rand(1,4); // + entre 1 et 4 ans

      $b->add(toolbox::addslashes_deep(
              array('name'         => "budget' $i-$ID_entity",
                    'entities_id'  => $ID_entity,
                    'is_recusive'  => 0,
                    'comment'      => "comment' $i-$ID_entity",
                    'begin_date'   => date("Y-m-d",intval($date1)),
                    'end_date'     => date("Y-m-d",intval($date2)))));
   }
   $LAST["budget"] = getMaxItem("glpi_budgets");

   // GLobal ones
   for ($i=0 ; $i<$MAX['document']/2 ; $i++) {
      $date1 = strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
      $date2 = $date1+MONTH_TIMESTAMP*12*mt_rand(1,4); // + entre 1 et 4 ans

      $b->add(toolbox::addslashes_deep(
              array('name'         => "Recursive budget' $i-$ID_entity",
                    'entities_id'  => $ID_entity,
                    'is_recusive'  => 1,
                    'comment'      => "comment' $i-$ID_entity",
                    'begin_date'   => date("Y-m-d",intval($date1)),
                    'end_date'     => date("Y-m-d",intval($date2)))));

   }
   $LAST["document"] = getMaxItem("glpi_documents");


   // glpi_suppliers
   $items                = array("DELL", "IBM", "ACER", "Microsoft", "Epson", "Xerox",
                                 "Hewlett Packard", "Nikon", "Targus", "LG", "Samsung", "Lexmark");
   $FIRST["enterprises"] = getMaxItem("glpi_suppliers")+1;
   $ent                  = new Supplier();

   // Global ones
   for ($i=0 ; $i<$MAX['enterprises']/2 ; $i++) {
      if (isset($items[$i])) {
         $val = "Recursive ".$items[$i];
      } else {
         $val = "Recursive enterprise_".$i."_ID_entity";
      }
      $entID = $ent->add(toolbox::addslashes_deep(
                         array('entities_id'        => $ID_entity,
                               'is_recursive'       => 1,
                               'name'               => "Recursive' $val-$ID_entity",
                               'suppliertypes_id'   => mt_rand(1,$MAX['enttype']),
                               'address'            => "address' $i",
                               'postcode'           => "postcode $i",
                               'town'               => "town' $i",
                               'state'              => "state' $i",
                               'country'            => "country $i",
                               'website'            => "http://www.$val.com/",
                               'fax'                => "fax $i",
                               'email'              => "info@ent$i.com",
                               'notepad'            => "notes enterprises' $i")));

      addDocuments('Supplier', $entID);
   }


   // Specific ones
   for ($i=0 ; $i<$MAX['enterprises'] ; $i++) {
      if (isset($items[$i])) {
         $val = $items[$i];
      } else {
         $val = "enterprise_".$i."_ID_entity";
      }

      $entID = $ent->add(toolbox::addslashes_deep(
                         array('entities_id'        => $ID_entity,
                               'is_recursive'       => 0,
                               'name'               => "'$val-$ID_entity",
                               'suppliertypes_id'   => mt_rand(1,$MAX['enttype']),
                               'address'            => "address' $i",
                               'postcode'           => "postcode $i",
                               'town'               => "town' $i",
                               'state'              => "state' $i",
                               'country'            => "country $i",
                               'website'            => "http://www.$val.com/",
                               'fax'                => "fax $i",
                               'email'              => "info@ent$i.com",
                               'notepad'            => "notes supplier' $i",
                               'comment'            => "comment supplier' $i")));

      addDocuments('Supplier', $entID);
   }
   $LAST["enterprises"] = getMaxItem("glpi_suppliers");


   // Ajout contracts
   $FIRST["contract"] = getMaxItem("glpi_contracts")+1;
   $c                 = new Contract();
   $cs                = new Contract_Supplier();
   $cc                = new ContractCost();
   // Specific
   for ($i=0 ; $i<$MAX['contract'] ; $i++) {
      $date = mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);
      $contractID = $c->add(toolbox::addslashes_deep(
                            array('entities_id'        => $ID_entity,
                                  'is_recursive'       => 0,
                                  'name'               => "contract' $i-$ID_entity",
                                  'num'                => "num' $i",
                                  'contracttypes_id'   => mt_rand(1,$MAX['contract_type']),
                                  'begin_date'         => $date,
                                  'duration'           => mt_rand(1,36),
                                  'notice'             => mt_rand(1,3),
                                  'periodicity'        => mt_rand(1,36),
                                  'billing'            => mt_rand(1,36),
                                  'comment'            => "comment' $i",
                                  'accounting_number'  => "compta num' $i",
                                  'renewal'            => 1)));

      addDocuments('Contract', $contractID);

      // Add an enterprise
      $cs->add(array('contracts_id' => $contractID,
                     'suppliers_id' => mt_rand($FIRST["enterprises"], $LAST["enterprises"])));
      // Add a cost
      $cc->add(toolbox::addslashes_deep(
               array('contracts_id' => $contractID,
                     'cost'         => mt_rand(100,10000),
                     'name'         => "Initial 'cost",
                     'begin_date'   => $date,
                     'budgets_id'   => mt_rand($FIRST['budget'], $LAST['budget']))));
   }

   for ($i=0 ; $i<$MAX['contract']/2 ; $i++) {
      $date = mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);

      $contractID = $c->add(toolbox::addslashes_deep(
                            array('entities_id'        => $ID_entity,
                                  'is_recursive'       => 1,
                                  'name'               => "Recursive contract' $i-$ID_entity",
                                  'num'                => "num' $i",
                                  'cost'               => mt_rand(100,10000),
                                  'contracttypes_id'   => mt_rand(1,$MAX['contract_type']),
                                  'begin_date'         => $date,
                                  'duration'           => mt_rand(1,36),
                                  'notice'             => mt_rand(1,3),
                                  'periodicity'        => mt_rand(1,36),
                                  'billing'            => mt_rand(1,36),
                                  'comment'            => "comment' $i",
                                  'accounting_number'  => "compta num' $i",
                                  'renewal'            => 1)));

      addDocuments('Contract', $contractID);

      // Add an enterprise
      $cs->add(array('contracts_id' => $contractID,
                     'suppliers_id' => mt_rand($FIRST["enterprises"], $LAST["enterprises"])));
   }
   $LAST["contract"] = getMaxItem("glpi_contracts");


   // Ajout contacts
   $prenoms = array("Jean", "John", "Louis", "Pierre", "Auguste",
                    "Albert", "Julien", "Guillaume", "Bruno",
                    "Maurice", "Francois", "Laurent", "Richard",
                    "Henri", "Clement", "d'avy");
   $noms    = array("Dupont", "Smith", "Durand", "Martin", "Dubois",
                    "Dufour", "Dupin", "Duval", "Petit", "Grange",
                    "Bernard", "Bonnet", "Richard", "Leroy",
                    "Dumont", "Fontaine", "d'orleans");


   $FIRST["contacts"] = getMaxItem("glpi_contacts")+1;
   $c                 = new Contact();
   $cs                = new Contact_Supplier();
   for ($i=0 ; $i<$MAX['contacts'] ; $i++) {
      if (isset($noms[$i])) {
         $val = $noms[$i];
      } else {
         $val = "name $i";
      }
      if (isset($prenoms[$i])) {
         $val2 = $prenoms[$i];
      } else {
         $val2 = "firstname $i";
      }

      $contactID = $c->add(toolbox::addslashes_deep(
                           array('entities_id'        => $ID_entity,
                                 'is_recursive'       => 0,
                                 'name'               => "$val-$ID_entity",
                                 'firstname'          => $val2,
                                 'contacttypes_id'    => mt_rand(1,$MAX['contact_type']),
                                 'usertitles_id'      => mt_rand(0,$MAX['user_title']),
                                 'phone'              => "phone $i",
                                 'phone2'             => "phone2 $i",
                                 'mobile'             => "mobile $i",
                                 'fax'                => "fax $i",
                                 'email'              => "email $i",
                                 'address'            => "address' $i",
                                 'postcode'           => "postcode $i",
                                 'town'               => "town' $i",
                                 'state'              => "state' $i",
                                 'country'            => "country $i",
                                 'comment'            => "Comment' $i")));

      // Link with enterprise
      $cs->add(array('contacts_id'  => $contactID,
                     'suppliers_id' => mt_rand($FIRST['enterprises'],$LAST['enterprises'])));
   }

   for ($i=0 ; $i<$MAX['contacts']/2 ; $i++) {
      if (isset($items[$i])) {
         $val = "Recursive ".$items[$i];
      } else {
         $val = "Recursive contact $i";
      }
      $contactID = $c->add(toolbox::addslashes_deep(
                           array('entities_id'        => $ID_entity,
                                 'is_recursive'       => 0,
                                 'name'               => "$val'-$ID_entity",
                                 'contacttypes_id'    => mt_rand(1,$MAX['contact_type']),
                                 'usertitles_id'      => mt_rand(0,$MAX['user_title']),
                                 'phone'              => "phone $i",
                                 'phone2'             => "phone2 $i",
                                 'mobile'             => "mobile $i",
                                 'fax'                => "fax $i",
                                 'email'              => "email $i",
                                 'address'            => "address' $i",
                                 'postcode'           => "postcode $i",
                                 'town'               => "town' $i",
                                 'state'              => "state' $i",
                                 'country'            => "country $i",
                                 'comment'            => "Comment' $i")));

      // Link with enterprise
      $cs->add(array('contacts_id'  => $contactID,
                     'suppliers_id' => mt_rand($FIRST['enterprises'],$LAST['enterprises'])));
   }
   $LAST["contacts"] = getMaxItem("glpi_contacts");


   // TYPE DE CONSOMMABLES
   $FIRST["type_of_consumables"] = getMaxItem("glpi_consumableitems")+1;
   $ci                           = new Consumableitem();

   for ($i=0 ; $i<$MAX['type_of_consumables'] ; $i++) {
      $consID = $ci->add(toolbox::addslashes_deep(
                         array('entities_id'             => $ID_entity,
                               'is_recursive'            => mt_rand(0,1),
                               'name'                    => "consumable type' $i",
                               'ref'                     => "ref d' $i",
                               'locations_id'            => mt_rand($FIRST["locations"],
                                                                    $LAST['locations']),
                               'consumableitemtypes_id'  => mt_rand(0,$MAX['consumable_type']),
                               'manufacturers_id'        => mt_rand(1,$MAX['manufacturer']),
                               'users_id_tech'           => mt_rand($FIRST['users_sadmin'],
                                                                    $LAST['users_admin']),
                               'groups_id_tech'          => mt_rand($FIRST["groups"], $LAST["groups"]),
                               'comment'                 => "comment' $i",
                               'notepad'                 => "notes consumableitem' $i",
                               'alarm_threshold'         => mt_rand(0,10))));

      addDocuments('ConsumableItem', $consID);


      // AJOUT INFOCOMS
      addInfocoms('ConsumableItem', $consID, $ID_entity);

      // Ajout consommable en stock
      $c = new Consumable();
      for ($j=0 ; $j<mt_rand(0,$MAX['consumables_stock']) ; $j++) {
         $date = mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28);

         $ID = $c->add(array('entities_id'        => $ID_entity,
                             'consumableitems_id' => $consID,
                             'date_in'            => $date));

         // AJOUT INFOCOMS
         addInfocoms('Consumable', $ID, $ID_entity);
      }


      // Ajout consommable donne
      for ($j=0 ; $j<mt_rand(0,$MAX['consumables_given']) ; $j++) {
         $date = mt_rand(2000,$current_year-1)."-".mt_rand(1,12)."-".mt_rand(1,28);

         $ID = $c->add(array('entities_id'        => $ID_entity,
                             'consumableitems_id' => $consID,
                             'date_in'            => $date,
                             'date_out'           => date("Y-m-d")));

         // AJOUT INFOCOMS
         addInfocoms('Consumable', $ID, $ID_entity);

      }

   }
   $LAST["type_of_consumables"] = getMaxItem("glpi_consumableitems");


   // TYPE DE CARTOUCHES
   $FIRST["type_of_cartridges"] = getMaxItem("glpi_cartridgeitems")+1;
   $ct                          = new CartridgeItem();

   for ($i=0 ; $i<$MAX['type_of_cartridges'] ; $i++) {
      $cartID = $ct->add(toolbox::addslashes_deep(
                         array('entities_id'       => $ID_entity,
                               'is_recursive'      => mt_rand(0,1),
                               'name'              => "cartridge ' type $i",
                               'ref'               => "ref '$i",
                               'locations_id'      => mt_rand($FIRST["locations"], $LAST['locations']),
                               'manufacturers_id'  => mt_rand(1,$MAX['manufacturer']),
                               'users_id_tech'     => mt_rand($FIRST['users_sadmin'],
                                                              $LAST['users_admin']),
                               'groups_id_tech'    => mt_rand($FIRST["groups"], $LAST["groups"]),
                               'comment'           => "comment '$i",
                               'notepad'           => "notes cartridgeitem '$i",
                               'alarm_threshold'   => mt_rand(0,10))));

      addDocuments('CartridgeItem', $cartID);


      // AJOUT INFOCOMS
      addInfocoms('CartridgeItem', $cartID, $ID_entity);

      $c    = new Cartridge();
      $cipm = new CartridgeItem_PrinterModel();
      // Ajout cartouche en stock
      for ($j=0 ; $j<mt_rand(0,$MAX['cartridges_stock']) ; $j++) {
         $date = mt_rand(2000,$current_year-1)."-".mt_rand(1,12)."-".mt_rand(1,28);

         $ID = $c->add(array('entities_id'         => $ID_entity,
                             'cartridgeitems_id'   => $cartID,
                             'date_in'             => $date));

         // AJOUT INFOCOMS
         addInfocoms('Cartridge', $ID, $ID_entity);

      }

      // Assoc printer type to cartridge type
      $cipm->add(array('cartridgeitems_id'  => $cartID,
                       'printermodels_id'   => mt_rand(1,$MAX['type_printers']),
                     ));
   }
   $LAST["type_of_cartridges"] = getMaxItem("glpi_cartridgeitems");


   // Networking
   $NET_LOC             = array();
   $FIRST["networking"] = getMaxItem("glpi_networkequipments")+1;
   $FIRST["printers"]   = getMaxItem("glpi_printers")+1;
   $ne                  = new NetworkEquipment();
   $p                   = new Printer();
   $np                  = new Netpoint();
   $c                   = new Cartridge();

   foreach ($DB->request('glpi_locations') as $data) {
      $i          = $data["id"];
      $techID     = mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
      $gtechID    = mt_rand($FIRST["techgroups"],$LAST["techgroups"]);
      $infoIP     = getNextIP();
      $domainID   = mt_rand($FIRST['domain'],$LAST['domain']);
      $networkID  = mt_rand(1,$MAX['network']);

      // insert networking

      $netwID = $ne->add(toolbox::addslashes_deep(
                         array('entities_id'                   => $ID_entity,
                               'name'                          => "networking '$i-$ID_entity",
                               'ram'                           => mt_rand(32,256),
                               'serial'                        => Toolbox::getRandomString(10),
                               'otherserial'                   => Toolbox::getRandomString(10),
                               'contact'                       => "contact '$i",
                               'contact_num'                   => "num '$i",
                               'users_id_tech'                 => $techID,
                               'groups_id_tech'                => $gtechID,
                               'comment'                       => "comment '$i",
                               'locations_id'                  => $i,
                               'domains_id'                    => $domainID,
                               'networks_id'                   => $networkID,
                               'networkequipmenttypes_id'      => mt_rand(1,$MAX['type_networking']),
                               'networkequipmentmodels_id'     => mt_rand(1,$MAX['model_networking']),
                               'networkequipmentfirmwares_id'  => mt_rand(1,$MAX['firmware']),
                               'manufacturers_id'              => mt_rand(1,$MAX['enterprises']),
                               'mac'                           => getNextMAC(),
                               'ip'                            => $infoIP["ip"],
                               'notepad'                       => "notes networking '$i",
                               'users_id'                      => mt_rand($FIRST['users_sadmin'],
                                                                          $LAST['users_admin']),
                               'groups_id'                     => mt_rand($FIRST["groups"],
                                                                          $LAST["groups"]),
                               'states_id'                     => (mt_rand(0,100)<$percent['state']
                                                                     ?mt_rand($FIRST['state'],$LAST['state']):0)
                           )));
      $NET_LOC[$data['id']] = $netwID;
      addDocuments('NetworkEquipment', $netwID);
      addContracts('NetworkEquipment', $netwID);

      // AJOUT INFOCOMS
      addInfocoms('NetworkEquipment', $netwID, $ID_entity);

      // Link with father
      addNetworkEthernetPort('NetworkEquipment', $netwID, $ID_entity, $i);

      // Ajout imprimantes reseaux : 1 par loc + connexion d un matos reseau + ajout de cartouches
      // Add trackings
      addTracking('NetworkEquipment', $netwID, $ID_entity);

      $typeID  = mt_rand(1,$MAX['type_printers']);
      $modelID = mt_rand(1,$MAX['model_printers']);
      $recur   = mt_rand(0,1);

      $printID = $p->add(toolbox::addslashes_deep(
                         array('entities_id'      => $ID_entity,
                                'is_recursive'     => $recur,
                                'name'             => "printer of loc '$i",
                                'serial'           => Toolbox::getRandomString(10),
                                'otherserial'      => Toolbox::getRandomString(10),
                                'contact'          => "contact '$i",
                                'contact_num'      => "num' $i",
                                'users_id_tech'    => $techID,
                                'groups_id_tech'   => $gtechID,
                                'have_serial'      => mt_rand(0,1),
                                'have_parallel'    => mt_rand(0,1),
                                'have_usb'         => mt_rand(0,1),
                                'have_wifi'        => mt_rand(0,1),
                                'have_ethernet'    => mt_rand(0,1),
                                'comment'          => "comment' $i",
                                'memory_size'      => mt_rand(0,128),
                                'locations_id'     => $i,
                                'domains_id'       => $domainID,
                                'networks_id'      => $networkID,
                                'printertypes_id'  => $typeID,
                                'printermodels_id' => $modelID,
                                'manufacturers_id' => mt_rand(1,$MAX['enterprises']),
                                'is_global'        => 1,
                                'notepad'          => "notes printers '$i",
                                'users_id'         => mt_rand($FIRST['users_sadmin'],
                                                              $LAST['users_admin']),
                                'groups_id'        => mt_rand($FIRST["groups"], $LAST["groups"]),
                                'states_id'        => (mt_rand(0,100)<$percent['state']
                                                         ?mt_rand($FIRST['state'],$LAST['state']):0)
                           )));

      addDocuments('Printer', $printID);
      addContracts('Printer', $printID);

      // Add trackings
      addTracking('Printer', $printID, $ID_entity);

      // AJOUT INFOCOMS
      addInfocoms('Printer', $printID, $ID_entity, $recur);

      // Add Cartouches
      // Get compatible cartridge
      $query = "SELECT `cartridgeitems_id`
                FROM `glpi_cartridgeitems_printermodels`
                WHERE `printermodels_id` = '$typeID'";
      $result2 = $DB->query($query) or die("PB REQUETE ".$query);

      if ($DB->numrows($result2)>0) {
         $ctypeID = $DB->result($result2,0,0) or die (" PB RESULT ".$query);
         $printed = 0;
         $oldnb   = mt_rand(1,$MAX['cartridges_by_printer']);
         $date1   = strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
         $date2   = time();
         $inter   = abs(round(($date2-$date1)/$oldnb));

         // Add old cartridges
         for ($j=0 ; $j<$oldnb ; $j++) {
            $printed += mt_rand(0,5000);
            $c->add(array('entities_id'        => $ID_entity,
                          'cartridgeitems_id'  => $ctypeID,
                          'printers_id'        => $printID,
                          'date_in'            => date("Y-m-d",$date1),
                          'date_use'           => date("Y-m-d",$date1+$j*$inter),
                          'date_out'           => date("Y-m-d",$date1+($j+1)*$inter),
                          'pages'              => $printed));
         }

         // Add current cartridges
         $c->add(array('entities_id'        => $ID_entity,
                       'cartridgeitems_id'  => $ctypeID,
                       'printers_id'        => $printID,
                       'date_in'            => $date,
                       'date_use'           => date("Y-m-d",$date2)));
      }

      $iface = mt_rand(1,$MAX['iface']);

      // Add networking ports
      addNetworkEthernetPort('Printer', $printID, $ID_entity, $i);
   }
   unset($NET_LOC);
   $LAST["networking"] = getMaxItem("glpi_networkequipments");


   //////////// INVENTORY

   // glpi_computers
   $FIRST["computers"]   = getMaxItem("glpi_computers")+1;
   $FIRST["monitors"]    = getMaxItem("glpi_monitors")+1;
   $FIRST["phones"]      = getMaxItem("glpi_phones")+1;
   $FIRST["peripherals"] = getMaxItem("glpi_peripherals")+1;
   $c       = new Computer();
   $mon     = new Monitor();

   $cdevmb    = new Item_DeviceMotherBoard();
   $cdevproc  = new Item_DeviceProcessor();
   $cdevmem   = new Item_DeviceMemory();
   $cdevhd    = new Item_DeviceHardDrive();
   $cdevnc    = new Item_DeviceNetworkCard();
   $cdevdr    = new Item_DeviceDrive();
   $cdevcon   = new Item_DeviceControl();
   $cdevgc    = new Item_DeviceGraphicCard();
   $cdevsc    = new Item_DeviceSoundCard();
   $cdevpci   = new Item_DevicePci();
   $cdevcase  = new Item_DeviceCase();
   $cdevps    = new Item_DevicePowerSupply();

   $cdisk   = new ComputerDisk();
   $np      = new Netpoint();
   $ci      = new Computer_Item();
   $phone   = new Phone();
   $print   = new Printer();
   $periph  = new Peripheral();
   $cart    = new Cartridge();
   for ($i=0 ; $i<$MAX['computers'] ; $i++) {
      $loc       = mt_rand($FIRST["locations"],$LAST['locations']);
      $techID    = mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
      $userID    = mt_rand($FIRST['users_normal'],$LAST['users_postonly']);
      $groupID   = mt_rand($FIRST["groups"],$LAST["groups"]);
      $gtechID   = mt_rand($FIRST["techgroups"],$LAST["techgroups"]);
      $domainID  = mt_rand($FIRST['domain'],$LAST['domain']);
      $networkID = mt_rand(1,$MAX['network']);

      $compID = $c->add(toolbox::addslashes_deep(
                        array('entities_id'                    => $ID_entity,
                              'name'                           => "computer ' $i-$ID_entity",
                              'serial'                         => Toolbox::getRandomString(10),
                              'otherserial'                    => Toolbox::getRandomString(10),
                              'contact'                        => "contact' $i",
                              'contact_num'                    => "num' $i",
                              'users_id_tech'                  => $techID,
                              'groups_id_tech'                 => $gtechID,
                              'comment'                        => "comment' $i",
                              'operatingsystems_id'            => mt_rand(1,$MAX['os']),
                              'operatingsystemversions_id'     => mt_rand(1,$MAX['os_version']),
                              'operatingsystemservicepacks_id' => mt_rand(1,$MAX['os_sp']),
                              'os_license_number'              => "os sn $i",
                              'os_licenseid'                   => "os id $i",
                              'autoupdatesystems_id'           => mt_rand(1,$MAX['os_sp']),
                              'locations_id'                   => $loc,
                              'domains_id'                     => $domainID,
                              'networks_id'                    => $networkID,
                              'computertypes_id'               => mt_rand(1,$MAX['type_computers']),
                              'computermodels_id'              => mt_rand(1,$MAX['model']),
                              'manufacturers_id'               => mt_rand(1,$MAX['manufacturer']),
                              'is_global'                      => 1,
                              'notepad'                        => "notes computer '$i",
                              'users_id'                       => $userID,
                              'groups_id'                      => $groupID,
                              'states_id'                      => (mt_rand(0,100)<$percent['state']
                                                                     ?mt_rand($FIRST['state'],$LAST['state']):0),
                              'uuid'                           => Toolbox::getRandomString(30)
                           )));

      addDocuments('Computer', $compID);
      addContracts('Computer', $compID);

      $NET_PORT['Computer'][$compID] = 0;

      // Add trackings
      addTracking('Computer', $compID, $ID_entity);

      // Add reservation
      addReservation('Computer', $compID, $ID_entity);


      // AJOUT INFOCOMS
      addInfocoms('Computer', $compID, $ID_entity);

      // ADD DEVICE
      $cdevmb->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                          array('serial' => Toolbox::getRandomString(15)));

      $max   = mt_rand(1,4);
      $devid = mt_rand(1,$MAX['device']);
      for($nb = 0; $nb<$max; $nb++) {
         $cdevproc->addDevices(1, 'Computer', $compID, $devid,
                               array('serial' => Toolbox::getRandomString(15)));
      }
      $max   = mt_rand(1,4);
      $devid = mt_rand(1,$MAX['device']);
      for($nb = 0; $nb<$max; $nb++) {
         $cdevmem->addDevices(1, 'Computer', $compID, $devid,
                              array('serial' => Toolbox::getRandomString(15)));
      }

      $max = mt_rand(1,2);
      $devid = mt_rand(1,$MAX['device']);
      for($nb = 0; $nb<$max; $nb++) {
         $cdevhd->addDevices(1, 'Computer', $compID, $devid,
                             array('serial' => Toolbox::getRandomString(15)));
      }

      $cdevnc->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                          array('serial' => Toolbox::getRandomString(15),
                                'mac'    => getNextMAC()));

      $cdevdr->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                          array('serial' => Toolbox::getRandomString(15)));

      $cdevcon->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                           array('serial' => Toolbox::getRandomString(15)));

      $cdevgc->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                          array('serial' => Toolbox::getRandomString(15)));

      $cdevsc->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                          array('serial' => Toolbox::getRandomString(15)));

      $max = mt_rand(1,4);
      for($nb = 0; $nb<$max; $nb++) {
         $cdevpci->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                              array('serial' => Toolbox::getRandomString(15)));
      }

      $cdevcase->addDevices(1, 'Computer', $compID, mt_rand(1,$MAX['device']),
                            array('serial'=> Toolbox::getRandomString(15)));

      $max   = mt_rand(1,2);
      $devid = mt_rand(1,$MAX['device']);
      for($nb = 0; $nb<$max; $nb++) {
         $cdevps->addDevices(1, 'Computer', $compID, $devid,
                             array('serial' => Toolbox::getRandomString(15)));
      }

      // insert disk
      $nb_disk = mt_rand(1,$MAX_DISK);
      for ($j=1 ; $j<=$nb_disk ; $j++) {
         $totalsize = mt_rand(10000,1000000);
         $freesize  = mt_rand(0,$totalsize);

         $cdisk->add(toolbox::addslashes_deep(
                      array('entities_id'     => $ID_entity,
                            'computers_id'    => $compID,
                            'name'            => "disk '$j",
                            'device'          => "/dev/disk$j",
                            'mountpoint'      => "/mnt/disk$j",
                            'filesystems_id'  => mt_rand(1,10),
                            'totalsize'       => $totalsize,
                            'freesize'        => $freesize)));
      }


      // Add networking ports
      addNetworkEthernetPort('Computer', $compID, $ID_entity, $loc);

      // Ajout d'un ecran sur l'ordi
      $monID = $mon->add(toolbox::addslashes_deep(
                         array('entities_id'       => $ID_entity,
                               'name'              => "monitor' $i-$ID_entity",
                               'serial'            => Toolbox::getRandomString(10),
                               'otherserial'       => Toolbox::getRandomString(10),
                               'contact'           => "contact' $i",
                               'contact_num'       => "num' $i",
                               'users_id_tech'     => $techID,
                               'groups_id_tech'    => $gtechID,
                               'comment'           => "comment' $i",
                               'size'              => mt_rand(14,22),
                               'have_micro'        => mt_rand(0,1),
                               'have_speaker'      => mt_rand(0,1),
                               'have_subd'         => mt_rand(0,1),
                               'have_bnc'          => mt_rand(0,1),
                               'have_dvi'          => mt_rand(0,1),
                               'have_pivot'        => mt_rand(0,1),
                               'have_hdmi'         => mt_rand(0,1),
                               'have_displayport'  => mt_rand(0,1),
                               'locations_id'      => $loc,
                               'monitortypes_id'   => mt_rand(1,$MAX['type_monitors']),
                               'monitormodels_id'  => mt_rand(1,$MAX['model_monitors']),
                               'manufacturers_id'  => mt_rand(1,$MAX['manufacturer']),
                               'notepad'           => "notes monitor' $i",
                               'users_id'          => $userID,
                               'groups_id'         => $groupID,
                               'states_id'         => (mt_rand(0,100)<$percent['state']
                                                         ?mt_rand($FIRST['state'],$LAST['state']):0)
                           )));

      addDocuments('Monitor', $monID);
      addContracts('Monitor', $monID);

      // Add trackings
      addTracking('Monitor', $monID, $ID_entity);

      // AJOUT INFOCOMS
      addInfocoms('Monitor', $monID, $ID_entity);

      $ci->add(array('itemtype'     => 'Monitor',
                     'items_id'     => $monID,
                     'computers_id' => $compID,
      ));

      // Ajout d'un telephhone avec l'ordi
      $telID = $phone->add(toolbox::addslashes_deep(
                           array('entities_id'           => $ID_entity,
                                 'name'                  => "phone' $i-$ID_entity",
                                 'serial'                => Toolbox::getRandomString(10),
                                 'otherserial'           => Toolbox::getRandomString(10),
                                 'contact'               => "contact' $i",
                                 'contact_num'           => "num' $i",
                                 'users_id_tech'         => $techID,
                                 'groups_id_tech'        => $gtechID,
                                 'comment'               => "comment' $i",
                                 'firmware'              => Toolbox::getRandomString(10),
                                 'brand'                 => "brand' $i",
                                 'phonepowersupplies_id' => mt_rand(0,$MAX['phone_power']),
                                 'number_line'           => Toolbox::getRandomString(10),
                                 'have_headset'          => mt_rand(0,1),
                                 'have_hp'               => mt_rand(0,1),
                                 'locations_id'          => $loc,
                                 'phonetypes_id'         => mt_rand(1,$MAX['type_phones']),
                                 'phonemodels_id'        => mt_rand(1,$MAX['model_phones']),
                                 'manufacturers_id'      => mt_rand(1,$MAX['manufacturer']),
                                 'notepad'               => "notes phone' $i",
                                 'users_id'              => $userID,
                                 'groups_id'             => $groupID,
                                 'states_id'             => (mt_rand(0,100)<$percent['state']
                                                               ?mt_rand($FIRST['state'],$LAST['state']):0)
                           )));

      addDocuments('Phone', $telID);
      addContracts('Phone', $telID);

      // Add trackings
      addTracking('Phone', $telID, $ID_entity);

      // AJOUT INFOCOMS
      addInfocoms('Phone', $telID, $ID_entity);

      $ci->add(array('itemtype'     => 'Phone',
                     'items_id'     => $telID,
                     'computers_id' => $compID));

      // Ajout des periphs externes en connection directe
      while (mt_rand(0,100)<$percent['peripherals']) {

         $periphID = $periph->add(toolbox::addslashes_deep(
                                  array('entities_id'       => $ID_entity,
                                        'name'              => "periph of comp' $i-$ID_entity",
                                        'serial'            => Toolbox::getRandomString(10),
                                        'otherserial'       => Toolbox::getRandomString(10),
                                        'contact'           => "contact' $i",
                                        'contact_num'       => "num' $i",
                                        'users_id_tech'     => $techID,
                                        'groups_id_tech'    => $gtechID,
                                        'comment'           => "comment' $i",
                                        'brand'             => "brand' $i",
                                        'locations_id'      => $loc,
                                        'phonetypes_id'     => mt_rand(1,$MAX['type_peripherals']),
                                        'phonemodels_id'    => mt_rand(1,$MAX['model_peripherals']),
                                        'manufacturers_id'  => mt_rand(1,$MAX['manufacturer']),
                                        'notepad'           => "notes peripheral' $i",
                                        'users_id'          => $userID,
                                        'groups_id'         => $groupID,
                                        'states_id'         => (mt_rand(0,100)<$percent['state']
                                                                  ?mt_rand($FIRST['state'],$LAST['state']):0)
                                    )));

         addDocuments('Peripheral', $periphID);
         addContracts('Peripheral', $periphID);

         // Add trackings
         addTracking('Peripheral', $periphID, $ID_entity);

         // Add connection
         $ci->add(array('itemtype'     => 'Peripheral',
                        'items_id'     => $periphID,
                        'computers_id' => $compID));
      }


      // Ajout d'une imprimante connection directe pour X% des computers + ajout de cartouches
      if (mt_rand(0,100)<=$percent['printer']) {
         // Add printer
         $typeID  = mt_rand(1,$MAX['type_printers']);
         $modelID = mt_rand(1,$MAX['model_printers']);

         $printID = $p->add(toolbox::addslashes_deep(
                            array('entities_id'       => $ID_entity,
                                  'name'              => "printer of comp' $i-$ID_entity",
                                  'serial'            => Toolbox::getRandomString(10),
                                  'otherserial'       => Toolbox::getRandomString(10),
                                  'contact'           => "contact' $i",
                                  'contact_num'       => "num' $i",
                                  'users_id_tech'     => $techID,
                                  'groups_id_tech'    => $gtechID,
                                  'have_serial'       => mt_rand(0,1),
                                  'have_parallel'     => mt_rand(0,1),
                                  'have_usb'          => mt_rand(0,1),
                                  'have_wifi'         => mt_rand(0,1),
                                  'have_ethernet'     => mt_rand(0,1),
                                  'comment'           => "comment' $i",
                                  'memory_size'       => mt_rand(0,128),
                                  'locations_id'      => $loc,
                                  'domains_id'        => $domainID,
                                  'networks_id'       => $networkID,
                                  'printertypes_id'   => $typeID,
                                  'printermodels_id'  => $modelID,
                                  'manufacturers_id'  => mt_rand(1,$MAX['manufacturer']),
                                  'notepad'           => "notes printers '$i",
                                  'users_id'          => mt_rand($FIRST['users_postonly'],
                                                                 $LAST['users_postonly']),
                                  'groups_id'         => mt_rand($FIRST["groups"], $LAST["groups"]),
                                  'states_id'         => (mt_rand(0,100)<$percent['state']
                                                            ?mt_rand($FIRST['state'],$LAST['state']):0)
                              )));

         addDocuments('Printer', $printID);
         addContracts('Printer', $printID);

         // Add trackings
         addTracking('Printer', $printID, $ID_entity);

         // Add connection
         $ci->add(array('itemtype'     => 'Printer',
                        'items_id'     => $printID,
                        'computers_id' => $compID));

         // AJOUT INFOCOMS
         addInfocoms('Printer', $printID, $ID_entity);

         // Add Cartouches
         // Get compatible cartridge
         $query = "SELECT `cartridgeitems_id`
                   FROM `glpi_cartridgeitems_printermodels`
                   WHERE `printermodels_id` = '$typeID'";
         $result = $DB->query($query) or die("PB REQUETE ".$query);

         if ($DB->numrows($result)>0) {
            $ctypeID = $DB->result($result,0,0) or die (" PB RESULT ".$query);
            $printed = 0;
            $oldnb   = mt_rand(1,$MAX['cartridges_by_printer']);
            $date1   = strtotime(mt_rand(2000,$current_year)."-".mt_rand(1,12)."-".mt_rand(1,28));
            $date2   = time();
            $inter   = round(($date2-$date1)/$oldnb);

            // Add old cartridges
            for ($j=0 ; $j<$oldnb ; $j++) {
               $printed += mt_rand(0,5000);
               $cart->add(array('entities_id'        => $ID_entity,
                                'cartridgeitems_id'  => $ctypeID,
                                'printers_id'        => $printID,
                                'date_in'            => date("Y-m-d",$date1),
                                'date_use'           => date("Y-m-d",$date1+$j*$inter),
                                'date_out'           => date("Y-m-d",$date1+($j+1)*$inter),
                                'pages'              => $printed));
            }

            // Add current cartridges
            $cart->add(array('entities_id'        => $ID_entity,
                             'cartridgeitems_id'  => $ctypeID,
                             'printers_id'        => $printID,
                             'date_in'            => date("Y-m-d",$date1),
                             'date_use'           => date("Y-m-d",$date2)));
         }
      }
   }

   $LAST["computers"] = getMaxItem("glpi_computers");
   $LAST["monitors"]  = getMaxItem("glpi_monitors");
   $LAST["phones"]    = getMaxItem("glpi_phones");


   // Add global peripherals
   $periph = new Peripheral();
   $ci     = new Computer_Item();
   for ($i=0 ; $i<$MAX['global_peripherals'] ; $i++) {
      $techID  = mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
      $gtechID = mt_rand($FIRST["techgroups"],$LAST["techgroups"]);

      $periphID = $periph->add(toolbox::addslashes_deep(
                               array('entities_id'       => $ID_entity,
                                     'name'              => "periph '$i-$ID_entity",
                                     'serial'            => Toolbox::getRandomString(10),
                                     'otherserial'       => Toolbox::getRandomString(10),
                                     'contact'           => "contact' $i",
                                     'contact_num'       => "num' $i",
                                     'users_id_tech'     => $techID,
                                     'groups_id_tech'    => $gtechID,
                                     'comment'           => "comment' $i",
                                     'brand'             => "brand' $i",
                                     'locations_id'      => $loc,
                                     'phonetypes_id'     => mt_rand(1,$MAX['type_peripherals']),
                                     'phonemodels_id'    => mt_rand(1,$MAX['model_peripherals']),
                                     'manufacturers_id'  => mt_rand(1,$MAX['manufacturer']),
                                     'is_global'         => 1,
                                     'notepad'           => "notes peripheral' $i",
                                     'users_id'          => mt_rand($FIRST['users_normal'],
                                                                    $LAST['users_normal']),
                                     'groups_id'         => mt_rand($FIRST["groups"], $LAST["groups"]),
                                     'states_id'         => (mt_rand(0,100)<$percent['state']
                                                               ?mt_rand($FIRST['state'],$LAST['state']):0)
                                 )));

      addDocuments('Peripheral', $periphID);
      addContracts('Peripheral', $periphID);

      // Add trackings
      addTracking('Peripheral', $periphID, $ID_entity);

      // Add reservation
      addReservation('Peripheral', $periphID, $ID_entity);

      // AJOUT INFOCOMS
      addInfocoms('Peripheral', $periphID, $ID_entity);

      // Add connections
      $val = mt_rand(1,$MAX['connect_for_peripherals']);
      for ($j=1 ; $j<$val ; $j++) {
         $ci->add(array('itemtype'     => 'Peripheral',
                        'items_id'     => $periphID,
                        'computers_id' => mt_rand($FIRST["computers"],$LAST['computers'])));
      }
   }

   $LAST["peripherals"] = getMaxItem("glpi_peripherals");

   $FIRST["software"]   = getMaxItem("glpi_softwares")+1;

   // Ajout logiciels + licences associees a divers PCs
   $items = array(array("Open'Office", "1.1.4", "2.0", "2.0.1"),
                  array("Microsoft Office", "95", "97", "XP", "2000", "2003", "2007"),
                  array("Acrobat Reader", "6.0", "7.0", "7.04"),
                  array("Gimp", "2.0", "2.2"),
                  array("InkScape", "0.4"));
   $soft       = new Software();
   $softvers   = new SoftwareVersion();
   $softlic    = new SoftwareLicense();
   $csv        = new Computer_SoftwareVersion();
   $csl        = new Computer_SoftwareLicense();
   for ($i=0 ; $i<$MAX['software'] ; $i++) {

      if (isset($items[$i])) {
         $name = $items[$i][0];
      } else {
         $name = "software '$i";
      }

      $loc       = mt_rand(1,$MAX['locations']);
      $techID    = mt_rand($FIRST['users_sadmin'],$LAST['users_admin']);
      $gtechID   = mt_rand($FIRST["techgroups"],$LAST["techgroups"]);
      $recursive = mt_rand(0,1);

      $softID = $soft->add(toolbox::addslashes_deep(
                           array('entities_id'           => $ID_entity,
                                 'is_recursive'          => $recursive,
                                 'name'                  => $name,
                                 'comment'               => "comment '$i",
                                 'locations_id'          => $loc,
                                 'users_id_tech'         => $techID,
                                 'groups_id_tech'        => $gtechID,
                                 'manufacturers_id'      => mt_rand(1,$MAX['manufacturer']),
                                 'notepad'               => "notes software '$i",
                                 'users_id'              => mt_rand($FIRST['users_admin'],
                                                                    $LAST['users_admin']),
                                 'groups_id'             => mt_rand($FIRST["groups"], $LAST["groups"]),
                                 'is_helpdesk_visible'   => 1,
                                 'softwarecategories_id' => mt_rand(1,$MAX['softwarecategory'])
                              )));

      addDocuments('Software', $softID);
      addContracts('Software', $softID);

      // Add trackings
      addTracking('Software', $softID, $ID_entity);

      // AJOUT INFOCOMS
      addInfocoms('Software', $softID, $ID_entity);

      // Add versions
      $FIRST["version"] = getMaxItem("glpi_softwareversions")+1;

      if (isset($items[$i])) {
         $val2 = count($items[$i]);
      } else {
         $val2 = mt_rand(1,$MAX['softwareversions']+1);
      }

      for ($j=1 ; $j<=$val2 ; $j++) {
         if (isset($items[$i])) {
            $version = $items[$i][mt_rand(1,count($items[$i])-1)];
         } else {
            $version = "$j.0";
         }
         $os = mt_rand(1,$MAX['os']);

         $versID = $softvers->add(toolbox::addslashes_deep(
                                  array('entities_id'          => $ID_entity,
                                        'is_recursive'         => $recursive,
                                        'softwares_id'         => $softID,
                                        'name'                 => $version,
                                        'comment'              => "comment '$version",
                                        'states_id'            => (mt_rand(0,100)<$percent['state']
                                                                     ?mt_rand($FIRST['state'],$LAST['state']):0),
                                        'operatingsystems_id'  => $os)));

         $val3    = min($LAST["computers"]-$FIRST['computers'], mt_rand(1,$MAX['softwareinstall']));
         $comp_id = mt_rand($FIRST["computers"], $LAST['computers']);

         for ($k=0 ; $k<$val3 ; $k++) {
            $comp_id++;
            if ($comp_id>$LAST["computers"]) {
               $comp_id = $FIRST["computers"];
            }
            $csv->add(array('computers_id'        => $comp_id,
                            'softwareversions_id' => $versID));
         }
      }
      $LAST["version"] = getMaxItem("glpi_softwareversions");


      // Add licenses
      $val2 = mt_rand(1,$MAX['softwarelicenses']);

      for ($j=0 ; $j<$val2 ; $j++) {
         $softwareversions_id_buy = mt_rand($FIRST["version"],$LAST["version"]);
         $softwareversions_id_use = mt_rand($softwareversions_id_buy,$LAST["version"]);

         $nbused = min($LAST["computers"]-$FIRST['computers'], mt_rand(1,$MAX['softwareinstall']));

         $licID = $softlic->add(toolbox::addslashes_deep(
                                array('entities_id'               => $ID_entity,
                                      'is_recursive'              => $recursive,
                                      'softwares_id'              => $softID,
                                      'number'                    => $nbused,
                                      'softwarelicensetypes_id'   => mt_rand(1,$MAX['licensetype']),
                                      'name'                      => "license '$j",
                                      'serial'                    => "serial $j",
                                      'otherserial'               => "otherserial $j",
                                      'comment'                   => "comment license '$j",
                                      'softwareversions_id_buy'   => $softwareversions_id_buy,
                                      'softwareversions_id_use'   => $softwareversions_id_use)));

         $comp_id = mt_rand($FIRST["computers"], $LAST['computers']);

         for ($k=0 ; $k<$nbused ; $k++) {
            $comp_id++;
            if ($comp_id>$LAST["computers"]) {
               $comp_id = $FIRST["computers"];
            }
            $csl->add(array('computers_id'          => $comp_id,
                            'softwarelicenses_id'   => $licID));
         }
      }
   }
   $LAST["software"] = getMaxItem("glpi_softwares");
}
?>
