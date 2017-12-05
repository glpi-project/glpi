<?php

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();
global $CFG_GLPI;
// Make a select box
if (isset($_POST["type"])
    && isset($_POST["actortype"])
    && isset($_POST["itemtype"])) {
   $rand = mt_rand();
   if ($item = getItemForItemtype($_POST["itemtype"])) {
      switch ($_POST["type"]) {
         case "user" :
            $right = 'all';
            // Only steal or own ticket whit empty assign
            if ($_POST["to_actor"]["_type"] == 'assign') {
               $right = "own_ticket";
               if (!$item->canAssign()) {
                  $right = 'id';
               }
            }

            $options_from = array('name'        => 'from_actor',
                'right'       => $right,
                'rand'        => $rand,
                'width'       => '150',
                'ldap_import' => true);
            $options_to = array('name'        => 'to_actor',
                'right'       => $right,
                'rand'        => $rand,
                'width'       => '150',
                'ldap_import' => true);
            
            $actors = [];
            foreach($_POST['tickets'] as $ticket_id) {
               $ticket = new Ticket();
               $ticket->getFromDB($ticket_id);

               foreach($ticket->getTicketActorsByType('user') as $actor_id => $actor)
                  foreach($actor as $actor_type) {
                     if ($actor_type == $_POST['actortype']) {
                        $actorObj = new User();
                        $actorObj->getFromDB($actor_id);
                        $actors[$actor_id] = $actorObj->getRawName();
                     }
                  }
            }
            $rand_from = Dropdown::showFromArray('from_actor',$actors,$options);
            $rand_to = User::dropdown($options_to);

            break;

         case "group" :
            $right = 'all';
            // Only steal or own ticket whit empty assign
            if ($_POST["to_actor"]["_type"] == 'assign') {
               $right = "own_ticket";
               if (!$item->canAssign()) {
                  $right = 'id';
               }
            }

            $options_from = array('name'        => 'from_actor',
                'right'       => $right,
                'rand'        => $rand,
                'width'       => '150',
                'ldap_import' => true);
            $options_to = array('name'        => 'to_actor',
                'right'       => $right,
                'rand'        => $rand,
                'width'       => '150',
                'ldap_import' => true);

            $actors = [];
            foreach($_POST['tickets'] as $ticket_id) {
               $ticket = new Ticket();
               $ticket->getFromDB($ticket_id);

               foreach($ticket->getTicketActorsByType('group') as $actor_id => $actor)
                  foreach($actor as $actor_type) {
                     if ($actor_type == $_POST['actortype']) {
                        $actorObj = new Group();
                        $actorObj->getFromDB($actor_id);
                        $actors[$actor_id] = $actorObj->getRawName();
                     }
                  }
            }
            $rand = Dropdown::showFromArray('from_actor',$actors,$options);
            $rand = Group::dropdown($options_to);
            break;
      }
   }
}
