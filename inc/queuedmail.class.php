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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/** QueuedMail class
 *
 * @since version 0.85
**/
class QueuedMail extends CommonDBTM {

   static $rightname = 'queuedmail';


   static function getTypeName($nb=0) {
      return __('Mail queue');
   }


   static function canCreate() {
      // Everybody can create : human and cron
      return Session::getLoginUserID(false);
   }


   static function getForbiddenActionsForMenu() {
      return array('add');
   }


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL, $is_deleted=false) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin && !$is_deleted) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'sendmail'] = _x('button', 'Send');
      }

      return $actions;
   }


   /**
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      switch ($ma->getAction()) {
         case 'sendmail' :
            foreach ($ids as $id) {
               if ($item->canEdit($id)) {
                  if ($item->sendMailById($id)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   function prepareInputForAdd($input) {
      global $DB;

      if (!isset($input['create_time']) || empty($input['create_time'])) {
         $input['create_time'] = $_SESSION["glpi_currenttime"];
      }
      if (!isset($input['send_time']) || empty($input['send_time'])) {
         $toadd = 0;
         if (isset($input['entities_id'])) {
            $toadd = Entity::getUsedConfig('delay_send_emails', $input['entities_id']);
         }
         if ($toadd > 0) {
            $input['send_time'] = date("Y-m-d H:i:s",
                                       strtotime($_SESSION["glpi_currenttime"])
                                                      +$toadd*MINUTE_TIMESTAMP);
         } else {
            $input['send_time'] = $_SESSION["glpi_currenttime"];
         }
      }
      $input['sent_try'] = 0;
      if (isset($input['headers']) && is_array($input['headers']) && count($input['headers'])) {
         $input["headers"] = exportArrayToDB($input['headers']);
      } else {
        $input['headers'] = '';
      }

      if (isset($input['documents']) && is_array($input['documents']) && count($input['documents'])) {
         $input["documents"] = exportArrayToDB($input['documents']);
      } else {
        $input['documents'] = '';
      }

      // Force items_id to integer
      if (!isset($input['items_id']) || empty($input['items_id'])) {
         $input['items_id'] = 0;
      }

      // Drop existing mails in queue for the same event and item  and recipient
      if (isset($input['itemtype']) && !empty($input['itemtype'])
          && isset($input['entities_id']) && ($input['entities_id'] >= 0)
          && isset($input['items_id']) && ($input['items_id'] >= 0)
          && isset($input['notificationtemplates_id']) && !empty($input['notificationtemplates_id'])
          && isset($input['recipient'])) {
         $query = "NOT `is_deleted`
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'
                   AND `entities_id` = '".$input['entities_id']."'
                   AND `notificationtemplates_id` = '".$input['notificationtemplates_id']."'
                   AND `recipient` = '".$input['recipient']."'";
         foreach ($DB->request($this->getTable(),$query) as $data) {
            $this->delete(array('id' => $data['id']),1);
         }
      }

      return $input;
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Subject');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false; // implicit field is id
      $tab[2]['datatype']        = 'number';

      $tab[16]['table']           = $this->getTable();
      $tab[16]['field']           = 'create_time';
      $tab[16]['name']            = __('Creation date');
      $tab[16]['datatype']        = 'datetime';
      $tab[16]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'send_time';
      $tab[3]['name']            = __('Expected send date');
      $tab[3]['datatype']        = 'datetime';
      $tab[3]['massiveaction']   = false;

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'sent_time';
      $tab[4]['name']            = __('Send date');
      $tab[4]['datatype']        = 'datetime';
      $tab[4]['massiveaction']   = false;

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'sender';
      $tab[5]['name']            = __('Sender email');
      $tab[5]['datatype']        = 'text';
      $tab[5]['massiveaction']   = false;

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'sendername';
      $tab[6]['name']            = __('Sender name');
      $tab[6]['datatype']        = 'string';
      $tab[6]['massiveaction']   = false;

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'recipient';
      $tab[7]['name']            = __('Recipient email');
      $tab[7]['datatype']        = 'string';
      $tab[7]['massiveaction']   = false;

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'recipientname';
      $tab[8]['name']            = __('Recipient name');
      $tab[8]['datatype']        = 'string';
      $tab[8]['massiveaction']   = false;

      $tab[9]['table']           = $this->getTable();
      $tab[9]['field']           = 'replyto';
      $tab[9]['name']            = __('Reply-to email');
      $tab[9]['datatype']        = 'string';
      $tab[9]['massiveaction']   = false;

      $tab[10]['table']           = $this->getTable();
      $tab[10]['field']           = 'replytoname';
      $tab[10]['name']            = __('Reply-to name');
      $tab[10]['datatype']        = 'string';
      $tab[10]['massiveaction']   = false;

      $tab[11]['table']           = $this->getTable();
      $tab[11]['field']           = 'headers';
      $tab[11]['name']            = __('Additional headers');
      $tab[11]['datatype']        = 'specific';
      $tab[11]['massiveaction']   = false;

      $tab[12]['table']           = $this->getTable();
      $tab[12]['field']           = 'body_html';
      $tab[12]['name']            = __('Email HTML body');
      $tab[12]['datatype']        = 'text';
      $tab[12]['massiveaction']   = false;
      $tab[12]['htmltext']        = true;

      $tab[13]['table']           = $this->getTable();
      $tab[13]['field']           = 'body_text';
      $tab[13]['name']            = __('Email text body');
      $tab[13]['datatype']        = 'text';
      $tab[13]['massiveaction']   = false;

      $tab[14]['table']           = $this->getTable();
      $tab[14]['field']           = 'messageid';
      $tab[14]['name']            = __('Message ID');
      $tab[14]['datatype']        = 'string';
      $tab[14]['massiveaction']   = false;

      $tab[15]['table']           = $this->getTable();
      $tab[15]['field']           = 'sent_try';
      $tab[15]['name']            = __('Number of tries of sent');
      $tab[15]['datatype']        = 'integer';
      $tab[15]['massiveaction']   = false;


      $tab[20]['table']          = $this->getTable();
      $tab[20]['field']          = 'itemtype';
      $tab[20]['name']           = __('Type');
      $tab[20]['datatype']       = 'itemtype';
      $tab[20]['massiveaction']  = false;

      $tab[21]['table']            = $this->getTable();
      $tab[21]['field']            = 'items_id';
      $tab[21]['name']             = __('Associated item ID');
      $tab[21]['massiveaction']    = false;
      $tab[21]['datatype']         = 'integer';

      $tab[22]['table']           = 'glpi_notificationtemplates';
      $tab[22]['field']           = 'name';
      $tab[22]['name']            = _n('Notification template', 'Notification templates', 1);
      $tab[22]['massiveaction']   = false;
      $tab[22]['datatype']        = 'dropdown';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      return $tab;
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'headers' :
            $values[$field] = importArrayFromDB($values[$field]);
            $out='';
            if (is_array($values[$field]) && count($values[$field])) {
               foreach ($values[$field] as $key => $val) {
                  $out .= $key.': '.$val.'<br>';
               }
            }
            return $out;
            break;

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * Send mai lin queue
    *
    * @param $ID        integer ID of the item
    *
    * @return true if send false if not
   **/
   function sendMailById($ID) {
      global $CFG_GLPI;

      if ($this->getFromDB($ID)) {

         $mmail = new GLPIMailer();

         $headers = importArrayFromDB($this->fields['headers']);
         if (is_array($headers) && count($headers)) {
            foreach ($headers as $key => $val) {
               $mmail->AddCustomHeader("$key: $val");
            }
         }

         // Add custom header for mail grouping in reader
         $mmail->AddCustomHeader("In-Reply-To: <GLPI-".$this->fields["itemtype"]."-".
                                 $this->fields["items_id"].">");

      $mmail->SetFrom($this->fields['sender'], $this->fields['sendername']);

         if ($this->fields['replyto']) {
            $mmail->AddReplyTo($this->fields['replyto'], $this->fields['replytoname']);
         }
         $mmail->Subject  = $this->fields['name'];

         if (empty($this->fields['body_html'])) {
            $mmail->isHTML(false);
            $mmail->Body = $this->fields['body_text'];
         } else {
            $mmail->isHTML(true);
            $mmail->Body               = '';
            $this->fields['body_html'] = Html::entity_decode_deep($this->fields['body_html']);
            $documents                 = importArrayFromDB($this->fields['documents']);
            if (is_array($documents) && count($documents)) {
               $doc = new Document();
               foreach ($documents as $docID) {
                  $doc->getFromDB($docID);
                  // Add embeded image if tag present in ticket content
                  if (preg_match_all('/'.Document::getImageTag($doc->fields['tag']).'/',
                                     $this->fields['body_html'], $matches, PREG_PATTERN_ORDER)) {
                     $mmail->AddEmbeddedImage(GLPI_DOC_DIR."/".$doc->fields['filepath'],
                                              Document::getImageTag($doc->fields['tag']),
                                              $doc->fields['filename'],
                                              'base64',
                                              $doc->fields['mime']);
                  }
               }
            }
            $mmail->Body   .= $this->fields['body_html'];
            $mmail->AltBody = $this->fields['body_text'];
         }

         $mmail->AddAddress($this->fields['recipient'], $this->fields['recipientname']);

         if (!empty($this->fields['messageid'])) {
            $mmail->MessageID = "<".$this->fields['messageid'].">";
         }

         $messageerror = __('Error in sending the email');

         if (!$mmail->Send()) {
            Session::addMessageAfterRedirect($messageerror."<br>".$mmail->ErrorInfo, true);

            //TODO: add translations string
            Toolbox::logInFile(
               'mail-error',
               sprintf(
                  '%1$s: %2$s',
                  sprintf(
                     'Email not sent to %s',
                     $this->fields['recipient']
                  ),
                  $this->fields['name']."\n"
               )
            );

            $mmail->ClearAddresses();
            $this->update(array('id'        => $this->fields['id'],
                                'sent_try' => $this->fields['sent_try']+1));
            return false;

         } else {
            //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
            Toolbox::logInFile("mail",
                               sprintf(__('%1$s: %2$s'),
                                        sprintf(__('An email was sent to %s'),
                                                $this->fields['recipient']),
                                        $this->fields['name']."\n"));
            $mmail->ClearAddresses();
            $this->update(array('id'        => $this->fields['id'],
                                'sent_time' => $_SESSION['glpi_currenttime']));
            $this->delete(array('id'        => $this->fields['id']));
            return true;
         }

      } else {
         return false;
      }
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'queuedmail' :
            return array('description' => __('Send mails in queue'),
                         'parameter'   => __('Maximum emails to send at once'));

         case 'queuedmailclean' :
            return array('description' => __('Clean mail queue'),
                         'parameter'   => __('Days to keep sent emails'));

      }
      return array();
   }


   /**
    * Cron action on queued mails : send mails in queue
    *
    * @param $task for log, if NULL display (default NULL)
   **/
   static function cronQueuedMail($task=NULL) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }
      $cron_status = 0;

      // Send mail at least 1 minute after adding in queue to be sure that process on it is finished
      $send_time = date("Y-m-d H:i:s", strtotime("+1 minutes"));
      $query       = "SELECT `glpi_queuedmails`.*
                      FROM `glpi_queuedmails`
                      WHERE NOT `glpi_queuedmails`.`is_deleted`
                            AND `glpi_queuedmails`.`send_time` < '".$send_time."'
                      ORDER BY `glpi_queuedmails`.`send_time` ASC
                      LIMIT 0, ".$task->fields['param'];

      $mail = new self();
      foreach ($DB->request($query) as $data) {
         if ($mail->sendMailById($data['id'])) {
            $cron_status = 1;
            if (!is_null($task)) {
               $task->addVolume(1);
            }
         }
      }
      return $cron_status;
   }


   /**
    * Cron action on queued mails : clean mail queue
    *
    * @param $task for log, if NULL display (default NULL)
   **/
   static function cronQueuedMailClean($task=NULL) {
      global $DB;

      $vol = 0;

      // Expire mails in queue
      if ($task->fields['param'] > 0) {
         $secs      = $task->fields['param'] * DAY_TIMESTAMP;
         $send_time = date("U") - $secs;
         $query_exp = "DELETE
                       FROM `glpi_queuedmails`
                       WHERE `glpi_queuedmails`.`is_deleted`
                             AND UNIX_TIMESTAMP(send_time) < '".$send_time."'";

         $DB->query($query_exp);
         $vol = $DB->affected_rows();
      }

      $task->setVolume($vol);
      return ($vol > 0 ? 1 : 0);
   }


   /**
    * Force sending all mails in queue for a specific item
    *
    * @param $itemtype item type
    * @param $items_id id of the item
   **/
   static function forceSendFor($itemtype, $items_id) {
      global $DB;

      if (!empty($itemtype)
          && !empty($items_id)) {
         // Send mail at least 1 minute after adding in queue to be sure that process on it is finished
         $query = "SELECT `glpi_queuedmails`.*
                   FROM `glpi_queuedmails`
                   WHERE NOT `glpi_queuedmails`.`is_deleted`
                        AND `glpi_queuedmails`.`itemtype` = '$itemtype'
                        AND `glpi_queuedmails`.`items_id` = '$items_id'
                        AND `glpi_queuedmails`.`send_time` <= NOW()
                   ORDER BY `glpi_queuedmails`.`send_time` ASC";

         $mail = new self();
         foreach ($DB->request($query) as $data) {
            $mail->sendMailById($data['id']);
         }
      }
   }


   /**
    * Print the queued mail form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Session::haveRight("queuedmail", READ)) {
        return false;
      }

      $this->check($ID, READ);
      $options['canedit'] = false;

      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Type')."</td>";

      echo "<td>";
      if (!($item = getItemForItemtype($this->fields['itemtype']))) {
         echo NOT_AVAILABLE;
         echo "</td>";
         echo "<td>"._n('Item', 'Items', 1)."</td>";
         echo "<td>";
         echo NOT_AVAILABLE;
      } else {
         echo $item->getType();
         $item->getFromDB($this->fields['items_id']);
         echo "</td>";
         echo "<td>"._n('Item', 'Items', 1)."</td>";
         echo "<td>";
         echo $item->getLink();
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Notification template', 'Notification templates', 1)."</td>";
      echo "<td>";
      echo Dropdown::getDropdownName('glpi_notificationtemplates',
                                     $this->fields['notificationtemplates_id']);
      echo "</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Creation date')."</td>";
      echo "<td>";
      echo Html::convDateTime($this->fields['create_time']);
      echo "</td><td>".__('Expected send date')."</td>";
      echo "<td>".Html::convDateTime($this->fields['send_time'])."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Send date')."</td>";
      echo "<td>".Html::convDateTime($this->fields['sent_time'])."</td>";
      echo "<td>".__('Number of tries of sent')."</td>";
      echo "<td>".$this->fields['sent_try']."</td>";
      echo "</tr>";

      echo "<tr><th colspan='4'>".__('Email')."</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Sender email')."</td>";
      echo "<td>".$this->fields['sender']."</td>";
      echo "<td>".__('Sender name')."</td>";
      echo "<td>".$this->fields['sendername']."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Recipient email')."</td>";
      echo "<td>".$this->fields['recipient']."</td>";
      echo "<td>".__('Recipient name')."</td>";
      echo "<td>".$this->fields['recipientname']."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Reply-to email')."</td>";
      echo "<td>".$this->fields['replyto']."</td>";
      echo "<td>".__('Reply-to name')."</td>";
      echo "<td>".$this->fields['replytoname']."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Message ID')."</td>";
      echo "<td>".$this->fields['messageid']."</td>";
      echo "<td>".__('Additional headers')."</td>";
      echo "<td>".self::getSpecificValueToDisplay('headers', $this->fields)."</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Subject')."</td>";
      echo "<td colspan=3>".$this->fields['name']."</td>";
      echo "</tr>";

      echo "<tr><th colspan='2'>".__('Email HTML body')."</th>";
      echo "<th colspan='2'>".__('Email text body')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 top' >";
      echo "<td colspan='2' class='queuemail_preview'>".self::cleanHtml($this->fields['body_html'])."</td>";
      echo "<td colspan='2'>".nl2br($this->fields['body_text'], false)."</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;

   }


   /**
    * @since version 0.85
    *
    * @param $string
    **/
   static function cleanHtml($string) {

      $begin_strip     = -1;
      $end_strip       = -1;
      $begin_match     = "/<body>/";
      $end_match       = "/<\/body>/";
      $content         = explode("\n", $string);
      $newstring       = '';
      foreach ($content as $ID => $val) {
         // Get last tag for end
         if ($begin_strip >= 0) {
            if (preg_match($end_match,$val)) {
               $end_strip = $ID;
               continue;
            }
         }
         if (($begin_strip >= 0) && ($end_strip < 0)) {
            $newstring .= $val;
         }
         // Get first tag for begin
         if ($begin_strip < 0) {
            if (preg_match($begin_match,$val)) {
               $begin_strip = $ID;
            }
         }
      }
      return nl2br($newstring,false);
      return preg_replace($patterns, $replacements, $string);
   }

}
?>
