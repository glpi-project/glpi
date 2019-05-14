<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @since 9.1
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Itemlock is dedicated to manage real-time lock of items in GLPI.
 *
 * Item locks are used to lock items like Ticket, Computer, Reminder, ..., see list in $CFG_GLPI['lock_lockable_objects']
 *
 * @author Olivier Moron
 * @since 9.1
 *
**/
class ObjectLock extends CommonDBTM {

   private $itemtype     = "";
   private $itemtypename = "";
   private $itemid       = 0;

   private static $shutdownregistered = false;


   /**
    * @see CommonGLPI::getTypeName()
    */
   static function getTypeName($nb = 0) {
      return _n('Object Lock', 'Object Locks', $nb);
   }


   /**
    * Summary of __construct
    *
    * @param $locitemtype       (default ObjectLoc
    * @param $locitemid         (default 0)
   **/
   function __construct($locitemtype = 'ObjectLock', $locitemid = 0) {

      $this->itemtype     = $locitemtype;
      $this->itemid       = $locitemid;
      $this->itemtypename = $locitemtype::getTypeName(1);
   }


   /**
    * Summary of getEntityID
    * @return 0
   **/
   function getEntityID() {
      return 0;
   }


   /**
    * Summary of getLockableObjects
    *
    * @return an array of lockable objects 'itemtype' => 'plural itemtype'
   **/
   static function getLockableObjects() {
      global $CFG_GLPI;

      $ret = [];
      foreach ($CFG_GLPI['lock_lockable_objects'] as $lo) {
         $ret[$lo] = $lo::getTypeName(Session::getPluralNumber());
      }
      asort($ret, SORT_STRING);
      return $ret;
   }


   /**
    * Summary of autoLockMode
    * Manages autolock mode
    *
    * @return bool: true if read-only profile lock has been set
   **/
   private function autoLockMode() {
      global $CFG_GLPI, $_SESSION, $_REQUEST;

      // if !autolock mode then we are going to view the item with read-only profile
      // if isset($_REQUEST['lockwrite']) then will behave like if automode was true but for this object only and for the lifetime of the session
      // look for lockwrite request
      if (isset($_REQUEST['lockwrite'])) {
         $_SESSION['glpilock_autolock_items'][ $this->itemtype ][$this->itemid] = 1;
      }

      $ret    = isset($_SESSION['glpilock_autolock_items'][ $this->itemtype ][ $this->itemid ])
                || $_SESSION['glpilock_autolock_mode'] == 1; // isset($_REQUEST['lockwrite'])
      $locked = $this->getLockedObjectInfo();
      if (!$ret && !$locked) {
         // open the object using read-only profile
         self::setReadonlyProfile();
         $this->setReadOnlyMessage();
      }
      return $ret || $locked;
   }


   /**
    * Summary of setLockedByYouMessage
    * Shows 'Locked by You!' message and proposes to unlock it
   **/
   private function setLockedByYouMessage() {
      global $CFG_GLPI;

      $ret = Html::scriptBlock("
         function unlockIt(obj) {
            $('#message_after_lock').fadeToggle();

            function callUnlock( ) {
               $.ajax({
                  url: '".$CFG_GLPI['root_doc']."/ajax/unlockobject.php',
                  cache: false,
                  data: 'unlock=1&force=1&id=".$this->fields['id']."',
                  success: function( data, textStatus, jqXHR ) { ".
                        Html::jsConfirmCallback(__('Reload page?'), __('Item unlocked!'), "function() {
                              window.location.reload(true);
                           }", "function() {
                              $('#message_after_lock').fadeToggle();
                           }") ."
                     },
                  error: function() { ".
                        Html::jsAlertCallback(__('Contact your GLPI admin!'), __('Item NOT unlocked!'), 'function(){$(\'#message_after_lock\').fadeToggle()}')."
                     }
               });
            }".
            Html::jsConfirmCallback(__('Unlock this item?'), $this->itemtypename." #".$this->itemid, "callUnlock", "function() {
                  $('#message_after_lock').fadeToggle();
               }"
            )."
         }

         ");

      echo $ret;

      $msg = "<table><tr><td class=red>";
      $msg .= __("Locked by you!")."</td>";
      $msg .= "<td><a class='vsubmit' onclick='javascript:unlockIt(this);'>"
              .sprintf(__('Unlock %1s #%2s'), $this->itemtypename, $this->itemid)."</a>";
      $msg .="</td></tr></table>";

      $this->displayLockMessage($msg);
   }


   /**
    * Summary of setLockedByMessage
    * Shows 'Locked by ' message and proposes to request unlock from locker
   **/
   private function setLockedByMessage() {
      global $CFG_GLPI;

      // should get locking user info
      $user = new User();
      $user->getFromDB($this->fields['users_id']);

      $useremail     = new UserEmail();
      $showAskUnlock = $useremail->getFromDBByCrit([
         'users_id'     => $this->fields['users_id'],
         'is_default'   => 1
      ]) && ($CFG_GLPI['notifications_mailing'] == 1);

      $completeUserName = formatUserName(0, $user->fields['name'], $user->fields['realname'],
                                         $user->fields['firstname']);

      if ($showAskUnlock) {
         $ret = Html::scriptBlock("
         function askUnlock() {
            $('#message_after_lock').fadeToggle();
            ". Html::jsConfirmCallback( __('Ask for unlock item?'), $this->itemtypename." #".$this->itemid, "function() {
                  $.ajax({
                     url: '".$CFG_GLPI['root_doc']."/ajax/unlockobject.php',
                     cache: false,
                     data: 'requestunlock=1&id=".$this->fields['id']."',
                     success: function( data, textStatus, jqXHR ) {
                           ".Html::jsAlertCallback($completeUserName, __('Request sent to'), "function() { $('#message_after_lock').fadeToggle(); }" )."
                        }
                     });
               }", "function() {
                  $('#message_after_lock').fadeToggle();
               }"
            ) ."
         }

         ");
         echo $ret;
      }

      $ret = Html::scriptBlock("
         $(function(){
            var lockStatusTimer;
            $('#alertMe').change(function( eventObject ){
               if( this.checked ) {
                  lockStatusTimer = setInterval( function() {
                     $.ajax({
                           url: '".$CFG_GLPI['root_doc']."/ajax/unlockobject.php',
                           cache: false,
                           data: 'lockstatus=1&id=".$this->fields['id']."',
                           success: function( data, textStatus, jqXHR ) {
                                 if( data == 0 ) {
                                    clearInterval(lockStatusTimer);
                                    $('#message_after_lock').fadeToggle();".
                                    Html::jsConfirmCallback(__('Reload page?'), __('Item unlocked!'), "function() {
                                       window.location.reload(true);
                                    }") ."
                                 }
                              }
                           });
                  },15000)
               } else {
                  clearInterval(lockStatusTimer);
               }
            });
         });
      ");
      echo $ret;

      $msg = "<table><tr><td class=red nowrap>";

      $msg .= __('Locked by ')."<a href='".$user->getLinkURL()."'>$completeUserName</a> -> ".
               Html::convDateTime($this->fields['date_mod']);
      $msg .= "</td><td nowrap>";
      if ($showAskUnlock) {
         $msg .= "<a class='vsubmit' onclick='javascript:askUnlock();'>".__('Ask for unlock')."</a>";
      }
      $msg .= "</td><td>&nbsp;&nbsp;</td><td>".__('Alert me when unlocked')."</td><td>&nbsp;".Html::getCheckbox(['id' => 'alertMe'])."</td></tr></table>";

      $this->displayLockMessage($msg);
   }


   /**
    * Summary of setReadOnlyMessage
    * Shows 'Read-only!' message and propose to request a lock on the item
    * This function is used by autoLockMode function
   **/
   private function setReadOnlyMessage() {

      echo Html::scriptBlock("
         function requestLock() {
               window.location.assign( window.location.href + '&lockwrite=1');
               }
         ");

      $msg = "<table><tr><td class=red nowrap>";
      $msg .= __('Warning: read-only!')."</td>";
      $msg .= "<td nowrap><a class='vsubmit' onclick='javascript:requestLock();'>".
                __('Request write on ').$this->itemtypename." #".$this->itemid."</a>";
      $msg .="</td></tr></table>";

      $this->displayLockMessage($msg);
   }


   /**
    * Summary of lockObject
    * Tries to lock object and if yes output code to auto unlock it when leaving browser page.
    * If lock can't be set (i.e.: someone has already locked it), LockedBy message is shown accordingly,
    * and read-only profile is set
    * @return bool: true if locked
   **/
   private function lockObject() {
      global $CFG_GLPI;

      $ret = false;
      //if( $CFG_GLPI["lock_use_lock_item"] &&
      //    $CFG_GLPI["lock_lockprofile_id"] > 0 &&
      //    in_array($this->itemtype, $CFG_GLPI['lock_item_list']) ) {
      if (!($gotIt = $this->getFromDBByCrit(['itemtype' => $this->itemtype,
               'items_id' => $this->itemid]))
               && $id = $this->add(['itemtype' => $this->itemtype,
                                          'items_id' => $this->itemid,
                                          'users_id' => Session::getLoginUserID()])) {
         // add a script to unlock the Object
         echo Html::scriptBlock( "$(function() {
                  $(window).on('beforeunload', function() {
                     //debugger;
                      $.ajax({
                          url: '".$CFG_GLPI['root_doc']."/ajax/unlockobject.php',
                          cache: false,
                          data: 'unlock=1&id=$id'
                          });
                      });
                  });" );
         $ret = true;
      } else { // can't add a lock as another one is already existing
         if (!$gotIt) {
            $this->getFromDBByCrit([
               'itemtype'  => $this->itemtype,
               'items_id'  => $this->itemid
            ]);
         }
         // open the object as read-only as it is already locked by someone
         self::setReadonlyProfile();
         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $this->setLockedByMessage();
         } else {
            $this->setLockedByYouMessage();
         }
         // and if autolock was set for this item then unset it
         unset($_SESSION['glpilock_autolock_items'][ $this->itemtype ][ $this->itemid ]);
      }
      // }
      return $ret;
   }


   /**
    * Summary of getLockedObjectInfo
    *
    * @return bool: true if object is locked, and $this is filled with record from DB
   **/
   private function getLockedObjectInfo() {
      global $CFG_GLPI;

      $ret = false;
      if ($CFG_GLPI["lock_use_lock_item"]
          && ($CFG_GLPI["lock_lockprofile_id"] > 0)
          && Session::getCurrentInterface() == 'central'
          && in_array($this->itemtype, $CFG_GLPI['lock_item_list'])
          && $this->getFromDBByCrit(['itemtype' => $this->itemtype,
                                     'items_id' => $this->itemid])) {
         $ret = true;
      }
      return $ret;
   }


   /**
    * Summary of isLocked
    *
    * @param $itemtype
    * @param $items_id
    *
    * @return bool|ObjectLock: returns ObjectLock if locked, else false
   **/
   static function isLocked($itemtype, $items_id) {

      $ol = new self($itemtype, $items_id);
      return ($ol->getLockedObjectInfo( )?$ol:false);
   }


   /**
    * Summary of setReadOnlyProfile
    * Switches current profile with read-only profile
    * Registers a shutdown function to be sure that even in case of die() calls,
    * the switch back will be done: to ensure correct reset of normal profile
   **/
   static function setReadOnlyProfile() {
      global $CFG_GLPI, $_SESSION;

      // to prevent double set ReadOnlyProfile
      if (!isset($_SESSION['glpilocksavedprofile'])) {
         if (isset($CFG_GLPI['lock_lockprofile'])) {
            if (!self::$shutdownregistered) {
               // this is a security in case of a die that can prevent correct revert of profile
               register_shutdown_function([__CLASS__,  'revertProfile']);
               self::$shutdownregistered = true;
            }
            $_SESSION['glpilocksavedprofile'] = $_SESSION['glpiactiveprofile'];
            $_SESSION['glpiactiveprofile']    = $CFG_GLPI['lock_lockprofile'];

            // this mask is mandatory to prevent read of information
            // that are not permitted to view by active profile
            $rights = ProfileRight::getAllPossibleRights();
            foreach ($rights as $key => $val) {
               if (isset($_SESSION['glpilocksavedprofile'][$key])) {
                  $_SESSION['glpiactiveprofile'][$key]
                     = intval($_SESSION['glpilocksavedprofile'][$key])
                       & (isset($CFG_GLPI['lock_lockprofile'][$key])
                             ?intval($CFG_GLPI['lock_lockprofile'][$key]) :0);
               }
            }
            // don't forget entities
            $_SESSION['glpiactiveprofile']['entities'] = $_SESSION['glpilocksavedprofile']['entities'];
         }
      }
   }


   /**
    * Summary of revertProfile
    * Will revert normal user profile
   **/
   static function revertProfile() {
      global $_SESSION;

      if (isset($_SESSION['glpilocksavedprofile'])) {
         $_SESSION['glpiactiveprofile'] = $_SESSION['glpilocksavedprofile'];
         unset($_SESSION['glpilocksavedprofile']);
      }
   }

   /**
    * Summary of manageObjectLock
    * Is the main function to be called in order to lock an item
    *
    * @param  $itemtype
    * @param  $options
   **/
   static function manageObjectLock($itemtype, &$options) {
      global $CFG_GLPI;

      if (isset($options['id']) && ($options['id'] > 0)) {
         $ol       = new self($itemtype, $options['id']);
         $template = (isset($options['withtemplate'])
                      && ($options['withtemplate'] > 0) ? true : false);
         if ((Session::getCurrentInterface() == "central")
             && isset($CFG_GLPI["lock_use_lock_item"]) && $CFG_GLPI["lock_use_lock_item"]
             && ($CFG_GLPI["lock_lockprofile_id"] > 0)
             && in_array($itemtype, $CFG_GLPI['lock_item_list'])
             && Session::haveRightsOr($itemtype::$rightname, [UPDATE, DELETE, PURGE, UPDATENOTE])
             && !$template) {

            if (!$ol->autoLockMode()
                || !$ol->lockObject($options['id'])) {
               $options['locked'] = 1;
            }
         }
      }
   }


   /**
    * Summary of displayLockMessage
    * Shows a short message top-left of screen
    * This message is permanent, and can't be closed
    *
    * @param  $msg      : message to be shown
    * @param  $title    : if $title is '' then title bar it is not shown (default '')
   **/
   private function displayLockMessage($msg, $title = '') {

      $hideTitle = '';
      if ($title == '') {
         $hideTitle = "$('.ui-dialog-titlebar', ui.dialog | ui).hide();";
      }

      echo "<div id='message_after_lock' title='$title'>";
      echo $msg;
      echo "</div>";

      echo Html::scriptBlock("
         $(function() {
            $('#message_after_lock').dialog({
               dialogClass: 'message_after_redirect',
               minHeight: 10,
               width: 'auto',
               height: 'auto',
               position: {
                  my: 'left top',
                  at: 'left+20 top-30',
                  of: $('#page'),
                  collision: 'none'
               },
               autoOpen: false,
               create: function(event, ui) {
                  $hideTitle
                  $('.ui-dialog-titlebar-close', ui.dialog | ui).hide();
               },
               show: {
                  effect: 'slide',
                  direction: 'up',
                  duration: 800
               },
            })
            .dialog('open');
         });
      ");
   }


   /**
    * @see CommonDBTM::processMassiveActionsForOneItemtype
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      foreach ($ids as $items_id) {
         $itemtype = get_class($item);
         $lo       = new self($itemtype, $items_id);
         if ($lo->getLockedObjectInfo()) {
            $lo->deleteFromDB();
            Log::history($items_id, $itemtype, [0, '', ''], 0, Log::HISTORY_UNLOCK_ITEM);
            $ma->itemDone($itemtype, $items_id, MassiveAction::ACTION_OK);
         }
      }
   }


   static public function rawSearchOptionsToAdd($itemtype) {
      global $CFG_GLPI;
      $tab = [];

      if ((Session::getCurrentInterface() == "central")
          && isset($CFG_GLPI["lock_use_lock_item"]) && $CFG_GLPI["lock_use_lock_item"]
          && ($CFG_GLPI["lock_lockprofile_id"] > 0)
          && in_array($itemtype, $CFG_GLPI['lock_item_list'])) {

         $tab[] = [
            'id' => '205',
            'table'         => 'glpi_users',
            'field'         => 'name',
            'datatype'      => 'dropdown',
            'right'         => 'all',
            'name'          => __('Locked by'),
            'forcegroupby'  => true,
            'massiveaction' => false,
            'joinparams'    => [
               'jointype'   => '',
               'beforejoin' => [
                  'table'      => getTableForItemType('ObjectLock'),
                  'joinparams' => ['jointype' => "itemtype_item"]
               ]
            ]
         ];

         $tab[] = [
            'id'            => '206',
            'table'         => getTableForItemType('ObjectLock'),
            'field'         => 'date_mod',
            'datatype'      => 'datetime',
            'name'          => __('Locked date'),
            'joinparams'    => ['jointype' => 'itemtype_item'],
            'massiveaction' => false,
            'forcegroupby'  => true
         ];
      }

      return $tab;
   }

   /**
    * Summary of getRightsToAdd
    *
    * @param  $itemtype
    * @param  $interface   (default 'central')
    *
    * @return array: empty array if itemtype is not lockable; else returns UNLOCK right
   **/
   static function getRightsToAdd($itemtype, $interface = 'central') {
      global $CFG_GLPI;

      $ret = [];
      if (($interface == "central")
          && isset($CFG_GLPI["lock_use_lock_item"]) && $CFG_GLPI["lock_use_lock_item"]
          && ($CFG_GLPI["lock_lockprofile_id"] > 0)
          && in_array($itemtype, $CFG_GLPI['lock_lockable_objects'])) {
         $ret = [UNLOCK  => __('Unlock')];
      }
      return $ret;
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return array of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'unlockobject' :
            return ['description' => __('Unlock forgotten locked objects'),
                         'parameter'   => __('Timeout to force unlock (hours)')];
      }
      return [];
   }


   /**
    * Cron for unlocking forgotten locks
    *
    * @param $task : crontask object
    *
    * @return integer
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
   **/
   static function cronUnlockObject($task) {
      global $DB;

      // here we have to delete old locks
      $actionCode = 0; // by default
      $task->setVolume(0); // start with zero

      $lockedItems = getAllDataFromTable(
         getTableForItemType(__CLASS__), [
            'date_mod' => ['<', date("Y-m-d H:i:s", time() - ($task->fields['param'] * HOUR_TIMESTAMP))]
         ]
      );

      foreach ($lockedItems as $row) {
         $ol = new self;
         if ($ol->delete($row)) {
            $actionCode++;
            $item = new $row['itemtype']();
            $item->getFromDB($row['items_id']);
            $task->log($row['itemtype']." #".$row['items_id'].": ".$item->getLink());
            $task->addVolume(1);
            Log::history($row['items_id'], $row['itemtype'], [0, '', ''], 0,
                         Log::HISTORY_UNLOCK_ITEM);
         } else {
            return -1; // error can't delete record, then exit with error
         }
      }

      return $actionCode;
   }

}
