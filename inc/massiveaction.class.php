<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/** @file
* @brief
*/

/**
 * Class that manages all the massive actions
 *
 * @todo all documentation !
 *
 * @since version 0.85
**/
class MassiveAction {

   const CLASS_ACTION_SEPARATOR  = ':';

   const NO_ACTION               = 0;
   const ACTION_OK               = 1;
   const ACTION_KO               = 2;
   const ACTION_NORIGHT          = 3;


   /**
    * Constructor of massive actions.
    * There is three stages and each one have its own objectives:
    * - initial: propose the actions and filter the checkboxes (only once)
    * - specialize: add action specific fields and filter items. There can be as many as needed!
    * - process: process the massive action (only once, but can be reload to avoid timeout)
    *
    * We trust all previous stages: we don't redo the checks
    *
    * @param $POST  something like $_POST
    * @param $GET   something like $_GET
    * @param $stage the current stage
    *
    * @return nothing (it is a constructor).
   **/
   function __construct (array $POST, array $GET, $stage) {
      global $CFG_GLPI;

      if (!empty($POST)) {

         if (!isset($POST['is_deleted'])) {
            $POST['is_deleted'] = 0;
         }

         $this->nb_items = 0;

         if ((isset($POST['item'])) || (isset($POST['items']))) {

            $remove_from_post = array();

            switch ($stage) {
               case 'initial' :
                  $POST['action_filter'] = array();
                  // 'specific_actions': restrict all possible actions or introduce new ones
                  // thus, don't try to load other actions and don't filter any item
                  if (isset($POST['specific_actions'])) {
                     $POST['actions'] = $POST['specific_actions'];
                     $specific_action = 1;
                     $dont_filter_for = array_keys($POST['actions']);
                  } else{
                     $specific_action = 0;
                     if (isset($POST['add_actions'])) {
                        $POST['actions'] = $POST['add_actions'];
                        $dont_filter_for = array_keys($POST['actions']);
                     } else {
                        $POST['actions'] = array();
                        $dont_filter_for = array();
                     }
                  }
                  if (count($dont_filter_for)) {
                     $POST['dont_filter_for'] = array_combine($dont_filter_for, $dont_filter_for);
                  } else {
                     $POST['dont_filter_for'] = array();
                  }
                  $remove_from_post[] = 'specific_actions';
                  $remove_from_post[] = 'add_actions';
                  $POST['items'] = array();
                  foreach ($POST['item'] as $itemtype => $ids) {
                     // initial are raw checkboxes: 0=unchecked or 1=checked
                     $items = array();
                     foreach ($ids as $id => $checked) {
                        if ($checked == 1) {
                           $items[$id] = $id;
                           $this->nb_items ++;
                        }
                     }
                     $POST['items'][$itemtype] = $items;
                     if (!$specific_action) {
                        $actions         = self::getAllMassiveActions($itemtype, $POST['is_deleted'],
                                                                      $this->getCheckItem($POST));
                        $POST['actions'] = array_merge($actions, $POST['actions']);
                        foreach ($actions as $action => $label) {
                           $POST['action_filter'][$action][] = $itemtype;
                           $POST['actions'][$action]         = $label;
                        }
                     }
                  }
                  if (empty($POST['actions'])) {
                     throw new Exception(__('No action available'));
                  }
                  // Initial items is used to define $_SESSION['glpimassiveactionselected']
                  $POST['initial_items'] = $POST['items'];
                  $remove_from_post[]    = 'item';
                  break;

               case 'specialize' :
                  if (!isset($POST['action'])) {
                     Toolbox::logDebug('Implementation error !');
                     throw new Exception(__('Implementation error !'));
                  }
                  if ($POST['action'] == -1) {
                     // Case when no action is choosen
                     exit();
                  }
                  if (isset($POST['actions'])) {
                     // First, get the name of current action !
                     if (!isset($POST['actions'][$POST['action']])) {
                        Toolbox::logDebug('Implementation error !');
                        throw new Exception(__('Implementation error !'));
                     }
                     $POST['action_name'] = $POST['actions'][$POST['action']];
                     $remove_from_post[]  = 'actions';

                     // Then filter the items regarding the action
                     if (!isset($POST['dont_filter_for'][$POST['action']])) {
                        if (isset($POST['action_filter'][$POST['action']])) {
                           $items = array();
                           foreach ($POST['action_filter'][$POST['action']] as $itemtype) {
                              if (isset($POST['items'][$itemtype])) {
                                 $items[$itemtype] = $POST['items'][$itemtype];
                              }
                           }
                           $POST['items'] = $items;
                        }
                     }
                     // Don't affect items that forbid the action
                     $items = array();
                     foreach ($POST['items'] as $itemtype => $ids) {
                        if ($item = getItemForItemtype($itemtype)) {
                           $forbidden = $item->getForbiddenStandardMassiveAction();
                           if (in_array($POST['action'], $forbidden)) {
                              continue;
                           }
                           $items[$itemtype] = $ids;
                        }
                     }
                     $POST['items']      = $items;
                     $remove_from_post[] = 'dont_filter_for';
                     $remove_from_post[] = 'action_filter';
                  }
                  // Some action works for only one itemtype. Then, we filter items.
                  if (isset($POST['specialize_itemtype'])) {
                     $itemtype = $POST['specialize_itemtype'];
                     if (isset($POST['items'][$itemtype])) {
                        $POST['items'] = array($itemtype => $POST['items'][$itemtype]);
                     } else {
                        $POST['items'] = array();
                     }
                     $remove_from_post[] = 'specialize_itemtype';
                  }
                  // Extract processor of the action
                  if (!isset($POST['processor'])) {
                     $action = explode(self::CLASS_ACTION_SEPARATOR, $POST['action']);
                     if (count($action) == 2) {
                        $POST['processor'] = $action[0];
                        $POST['action']    = $action[1];
                     } else {
                        $POST['processor'] = 'MassiveAction';
                        $POST['action']    = $POST['action'];
                     }
                  }
                  // Count number of items !
                  foreach ($POST['items'] as $itemtype => $ids) {
                     $this->nb_items += count($ids);
                  }
                  break;

               case 'process' :
                  if (isset($POST['initial_items'])) {
                     $_SESSION['glpimassiveactionselected'] = $POST['initial_items'];
                  } else {
                     $_SESSION['glpimassiveactionselected'] = array();
                  }

                  $remove_from_post = array('items', 'action', 'action_name', 'processor',
                                            'massiveaction', 'is_deleted', 'initial_items');

                  $this->identifier  = mt_rand();
                  $this->done        = array();
                  $this->nb_done     = 0;
                  $this->action_name = $POST['action_name'];
                  $this->results     = array('ok'      => 0,
                                             'ko'      => 0,
                                             'noright' => 0,
                                             'messages' => array());
                  foreach ($POST['items'] as $itemtype => $ids) {
                     $this->nb_items += count($ids);
                  }
                  if (isset($_SERVER['HTTP_REFERER'])) {
                     $this->redirect = $_SERVER['HTTP_REFERER'];
                  } else {
                     $this->redirect = $CFG_GLPI['root_doc']."/front/central.php";
                  }
                  // Don't display progress bars if delay is less than 1 second
                  $this->display_progress_bars = false;
                 break;
            }

            $this->POST = $POST;
            foreach (array('items', 'action', 'processor') as $field) {
               if (isset($this->POST[$field])) {
                  $this->$field = $this->POST[$field];
               }
            }
            foreach ($remove_from_post as $field) {
               if (isset($this->POST[$field])) {
                  unset($this->POST[$field]);
               }
            }
         }
         if ($this->nb_items == 0) {
            throw new Exception(__('No selected items'));
         }

      } else {
         if (($stage != 'process')
             || (!isset($_SESSION['current_massive_action'][$GET['identifier']]))) {
            Toolbox::logDebug('Implementation error !');
            throw new Exception(__('Implementation error !'));
         }
         $identifier = $GET['identifier'];
         foreach ($_SESSION['current_massive_action'][$identifier] as $attribute => $value) {
            $this->$attribute = $value;
         }
         if ($this->identifier != $identifier) {
            $this->error = __('Invalid process');
            return;
         }
         unset($_SESSION['current_massive_action'][$identifier]);
      }

      // Add process elements
      if ($stage == 'process') {

         if (!isset($this->remainings)) {
            $this->remainings = $this->items;
         }

         $this->fields_to_remove_when_reload = array('fields_to_remove_when_reload');

         $this->timer = new Timer();
         $this->timer->start();
         $this->fields_to_remove_when_reload[] = 'timer';

         $max_time = (get_cfg_var("max_execution_time") == 0) ? 60
                                                              : get_cfg_var("max_execution_time");

         $this->timeout_delay                  = ($max_time - 3);
         $this->fields_to_remove_when_reload[] = 'timeout_delay';

         if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
            $this->messaget_after_redirect = $_SESSION["MESSAGE_AFTER_REDIRECT"];
            unset($_SESSION["MESSAGE_AFTER_REDIRECT"]);
         }
      }
   }


   /**
    * Get the fields provided by previous stage through $_POST.
    * Beware that the fields that are common (items, action ...) are not provided
    *
    * @return array of the elements
   **/
   function getInput() {

      if (isset($this->POST)) {
         return $this->POST;
      }
      return array();
   }


   /**
    * Get current action
    *
    * @return a string with the current action or NULL if we are at initial stage
   **/
   function getAction() {

      if (isset($this->action)) {
         return $this->action;
      }
      return NULL;
   }


   /**
    * Get all items on which this action must work
    *
    * @return array of the items (empty if initial state)
   **/
   function getItems() {

      if (isset($this->items)) {
         return $this->items;
      }
      return array();
   }


   /**
    * Get remaining items
    *
    * @return array of the remaining items (empty if not in process state)
   **/
   function getRemainings() {

      if (isset($this->remainings)) {
         return $this->remainings;
      }
      return array();
   }


   /**
    * Destructor of the object
    * It is used when reloading the page during process to store informations in $_SESSION.
   **/
   function __destruct() {

      if (isset($this->identifier)) {
         // $this->identifier is unset by self::process() when the massive actions are finished
         foreach ($this->fields_to_remove_when_reload as $field) {
            unset($this->$field);
         }
         $_SESSION['current_massive_action'][$this->identifier] = get_object_vars ($this);
      }
   }


   /**
    * @param $POST
   **/
   function getCheckItem($POST) {

      if (!isset($this->check_item)) {
         if (isset($POST['check_itemtype'])) {
            if (!($this->check_item = getItemForItemtype($POST['check_itemtype']))) {
               exit();
            }
            if (isset($POST['check_items_id'])) {
               if (!$this->check_item->getFromDB($POST['check_items_id'])) {
                  exit();
               } else {
                  $this->check_item->getEmpty();
               }
            }
         } else {
            $this->check_item = NULL;
         }
      }
      return $this->check_item;
   }


   /**
    * Add hidden fields containing all the checked items to the current form
    *
    * @return nothing (display)
   **/
   function addHiddenFields() {

      if (empty($this->hidden_fields_defined)) {
         $this->hidden_fields_defined = true;

         $common_fields = array('action', 'processor', 'is_deleted', 'initial_items',
                                'item_itemtype', 'item_items_id', 'items', 'action_name');

         if (!empty($this->POST['massive_action_fields'])) {
            $common_fields = array_merge($common_fields, $this->POST['massive_action_fields']);
         }

        foreach ($common_fields as $field) {
            if (isset($this->POST[$field])) {
               echo Html::hidden($field, array('value' => $this->POST[$field]));
            }
         }
      }
   }


   /**
    * Extract itemtype from the input (ie.: $input['itemtype'] is defined or $input['item'] only
    * contains one type of item. If none is available and we can display selector (inside the modal
    * window), then display a dropdown to select the itemtype.
    * This is only usefull in case of itemtype specific massive actions (update, ...)
    *
    * @param $display_selector can we display the itemtype selector ?
    *
    * @return the itemtype or false if we cannot define it (and we cannot display the selector)
   **/
   function getItemtype($display_selector) {

      if (isset($this->items) && is_array($this->items)) {
         $keys = array_keys($this->items);
         if (count($keys) == 1) {
            return $keys[0];
         }

         if ($display_selector
             && (count($keys) > 1)) {
            $itemtypes = array(-1 => Dropdown::EMPTY_VALUE);
            foreach ($keys as $itemtype) {
               $itemtypes[$itemtype] = $itemtype::getTypeName(Session::getPluralNumber());
            }
            _e('Select the type of the item on which applying this action')."<br>\n";

            $rand = Dropdown::showFromArray('specialize_itemtype', $itemtypes);
            echo "<br><br>";

            $params                        = $this->POST;
            $params['specialize_itemtype'] = '__VALUE__';
            Ajax::updateItemOnSelectEvent("dropdown_specialize_itemtype$rand", "show_itemtype$rand",
                                          $_SERVER['REQUEST_URI'], $params);

            echo "<span id='show_itemtype$rand'>&nbsp;</span>\n";
            exit();
         }
      }

     return false;
   }


   /**
    * Get 'add to transfer list' action when needed
    *
    * @param $actions   array
   **/
   static function getAddTransferList(array &$actions) {

      if (Session::haveRight('transfer', READ)
          && Session::isMultiEntitiesMode()) {
         $actions[__CLASS__.self::CLASS_ACTION_SEPARATOR.'add_transfer_list']
                  = _x('button', 'Add to transfer list');
      }

   }


   /**
    * Get the standard massive actions
    *
    * @param $item                   the item for which we want the massive actions
    * @param $is_deleted             massive action for deleted items ?   (default 0)
    * @param $checkitem              link item to check right              (default NULL)
    *
    * @return an array of massive actions or false if $item is not valid
   **/
   static function getAllMassiveActions($item, $is_deleted=0, CommonDBTM $checkitem=NULL) {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      // TODO: when maybe* will be static, when can completely switch to $itemtype !
      if (is_string($item)) {
         $itemtype = $item;
         if (!($item = getItemForItemtype($itemtype))) {
            return false;
         }
      } else if ($item instanceof CommonDBTM) {
         $itemtype = $item->getType();
      } else {
         return false;
      }


      if (!is_null($checkitem)) {
         $canupdate = $checkitem->canUpdate();
         $candelete = $checkitem->canDelete();
         $canpurge  = $checkitem->canPurge();
      } else {
         $canupdate = $itemtype::canUpdate();
         $candelete = $itemtype::canDelete();
         $canpurge  = $itemtype::canPurge();
      }

      $actions   = array();
      $self_pref = __CLASS__.self::CLASS_ACTION_SEPARATOR;

      if ($is_deleted) {
         if ($canpurge) {
            if (in_array($itemtype, Item_Devices::getConcernedItems())) {
               $actions[$self_pref.'purge_item_but_devices']
                                             = _x('button', 'Delete permanently but keep devices');
               $actions[$self_pref.'purge']  = _x('button',  'Delete permanently and remove devices');
            } else {
               $actions[$self_pref.'purge']  = _x('button', 'Delete permanently');
            }
            $actions[$self_pref.'restore'] = _x('button', 'Restore');
         }

      } else {
         if (($_SESSION['glpiactiveprofile']['interface'] == 'central')
             && ($canupdate
                 || (InfoCom::canApplyOn($itemtype)
                     && Infocom::canUpdate()))) {

            //TRANS: select action 'update' (before doing it)
            $actions[$self_pref.'update'] = _x('button', 'Update');
         }

         Infocom::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

         CommonDBConnexity::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted,
                                                         $checkitem);

         // do not take into account is_deleted if items may be dynamic
         if ($item->maybeDeleted()
             && !$item->useDeletedToLockIfDynamic()) {
            if ($candelete) {
               $actions[$self_pref.'delete'] = _x('button', 'Put in dustbin');
            }
         } else if ($canpurge) {
            $actions[$self_pref.'purge'] = _x('button', 'Delete permanently');
            if ($item instanceof CommonDropdown) {
               $actions[$self_pref.'purge_but_item_linked']
                     = _x('button', 'Delete permanently even if linked items');
            }
         }

         Document::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
         Contract::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

         // Specific actions
         $actions += $item->getSpecificMassiveActions($checkitem);

         // Plugin Specific actions
         if (isset($PLUGIN_HOOKS['use_massive_action'])) {
            foreach ($PLUGIN_HOOKS['use_massive_action'] as $plugin => $val) {
               $plug_actions = Plugin::doOneHook($plugin, 'MassiveActions', $itemtype);

               if (count($plug_actions)) {
                  $actions += $plug_actions;
               }
            }
         }
      }

      Lock::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);

      // Manage forbidden actions : try complete action name or MassiveAction:action_name
      $forbidden_actions = $item->getForbiddenStandardMassiveAction();
      if (is_array($forbidden_actions) && count($forbidden_actions)) {
         foreach ($forbidden_actions as $actiontodel) {
            if (isset($actions[$actiontodel])) {
               unset($actions[$actiontodel]);
            } else {
               // Not found search adding MassiveAction prefix
               $actiontodel = $self_pref.$actiontodel;
               if (isset($actions[$actiontodel])) {
                  unset($actions[$actiontodel]);
               }
            }
         }
      }
      return $actions;
   }


   /**
    * Main entry of the modal window for massive actions
    *
    * @return nothing: display
   **/
   function showSubForm() {
      global $CFG_GLPI;

      $processor = $this->processor;

      if (!$processor::showMassiveActionsSubForm($this)) {
         $this->showDefaultSubForm();
      }

      $this->addHiddenFields();
   }


    /**
    * Class-specific method used to show the fields to specify the massive action
    *
    * @return nothing (display only)
   **/
   function showDefaultSubForm() {
      echo Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
   }


   /**
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'update':
            if (!isset($ma->POST['id_field'])) {
               $itemtypes        = array_keys($ma->items);
               $options_per_type = array();
               $options_counts   = array();
               foreach ($itemtypes as $itemtype) {
                  $options_per_type[$itemtype] = array();
                  $group                       = '';
                  $show_all                    = true;
                  $show_infocoms               = true;
                  $itemtable                   = getTableForItemType($itemtype);

                  if (InfoCom::canApplyOn($itemtype)
                      && (!$itemtype::canUpdate()
                          || !Infocom::canUpdate())) {
                     $show_all      = false;
                     $show_infocoms = Infocom::canUpdate();
                  }
                  foreach (Search::getCleanedOptions($itemtype, UPDATE) as $index => $option) {

                     if (!is_array($option)) {
                        $group                               = $option;
                        $options_per_type[$itemtype][$group] = array();
                     } else {
                        if (($option['field'] != 'id')
                            && ($index != 1)
                            // Permit entities_id is explicitly activate
                            && (($option["linkfield"] != 'entities_id')
                                || (isset($option['massiveaction']) && $option['massiveaction']))) {

                           if (!isset($option['massiveaction']) || $option['massiveaction']) {
                              if (($show_all)
                                  || (($show_infocoms
                                       && Search::isInfocomOption($itemtype, $index))
                                      || (!$show_infocoms
                                          && !Search::isInfocomOption($itemtype, $index)))) {
                                 $options_per_type[$itemtype][$group][$itemtype.':'.$index]
                                             = $option['name'];
                                 if ($itemtable == $option['table']) {
                                    $field_key = 'MAIN:'.$option['field'].':'.$index;
                                 } else {
                                    $field_key = $option['table'].':'.$option['field'].':'.$index;
                                 }
                                 if (!isset($options_count[$field_key])) {
                                    $options_count[$field_key] = array();
                                 }
                                 $options_count[$field_key][] = $itemtype.':'.$index.':'.$group;
                                 if (isset($option['MA_common_field'])) {
                                    if (!isset($options_count[$option['MA_common_field']])) {
                                       $options_count[$option['MA_common_field']] = array();
                                    }
                                    $options_count[$option['MA_common_field']][]
                                          = $itemtype.':'.$index.':'.$group;
                                 }
                              }
                           }
                        }
                     }
                  }
               }

               if (count($itemtypes) > 1) {
                  $options        = array(0 => Dropdown::EMPTY_VALUE);
                  $common_options = array();
                  foreach ($options_count as $field => $users) {
                     if (count($users) > 1) {
                        $labels = array();
                        foreach ($users as $user) {
                           $user      = explode(':', $user);
                           $itemtype  = $user[0];
                           $index     = $itemtype.':'.$user[1];
                           $group     = implode(':', array_slice($user, 2));
                           if (isset($options_per_type[$itemtype][$group][$index])) {
                              if (!in_array($options_per_type[$itemtype][$group][$index],
                                            $labels)) {
                                 $labels[] = $options_per_type[$itemtype][$group][$index];
                              }
                           }
                           $common_options[$field][] = $index;
                        }
                        $options[$group][$field] = implode('/', $labels);
                     }
                  }
                  $choose_itemtype  = true;
                  $itemtype_choices = array(-1 => Dropdown::EMPTY_VALUE);
                  foreach ($itemtypes as $itemtype) {
                     $itemtype_choices[$itemtype] = $itemtype::getTypeName(Session::getPluralNumber());
                  }
               } else {
                  $options         = array(0 => Dropdown::EMPTY_VALUE);
                  $options        += $options_per_type[$itemtypes[0]];
                  $common_options  = false;
                  $choose_itemtype = false;
               }
               $choose_field = (count($options) > 1);

               // Beware: "class='tab_cadre_fixe'" induce side effects ...
               echo "<table width='100%'><tr>";

               $colspan = 0;
               if ($choose_field) {
                  $colspan ++;
                  echo "<td>";
                  if ($common_options) {
                     echo __('Select the common field that you want to update');
                  } else {
                     echo __('Select the field that you want to update');
                  }
                  echo "</td>";
                  if ($choose_itemtype) {
                     $colspan ++;
                     echo "<td rowspan='2'>".__('or')."</td>";
                  }
               }

               if ($choose_itemtype) {
                  $colspan ++;
                  echo "<td>".__('Select the type of the item on which applying this action')."</td>";
               }

               echo "</tr><tr>";
               if ($choose_field) {
                  echo "<td>";
                  $field_rand = Dropdown::showFromArray('id_field', $options);
                  echo "</td>";
               }
               if ($choose_itemtype) {
                  echo "<td>";
                  $itemtype_rand = Dropdown::showFromArray('specialize_itemtype',
                                                           $itemtype_choices);
                  echo "</td>";
               }

               $next_step_rand = mt_rand();

               echo "</tr></table>";
               echo "<span id='update_next_step$next_step_rand'>&nbsp;</span>";

               if ($choose_field) {
                  $params                   = $ma->POST;
                  $params['id_field']       = '__VALUE__';
                  $params['common_options'] = $common_options;
                  Ajax::updateItemOnSelectEvent("dropdown_id_field$field_rand",
                                                "update_next_step$next_step_rand",
                                                $_SERVER['REQUEST_URI'], $params);
               }

               if ($choose_itemtype) {
                  $params                        = $ma->POST;
                  $params['specialize_itemtype'] = '__VALUE__';
                  $params['common_options']      = $common_options;
                  Ajax::updateItemOnSelectEvent("dropdown_specialize_itemtype$itemtype_rand",
                                                "update_next_step$next_step_rand",
                                                $_SERVER['REQUEST_URI'], $params);
               }
               // Only display the form for this stage
               exit();

            }

            if (!isset($ma->POST['common_options'])) {
               echo "<div class='center'><img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='".
                              __s('Warning')."'><br><br>";
               echo "<span class='b'>".__('Implementation error !')."</span><br>";
               echo "</div>";
               exit();
            }

            if ($ma->POST['common_options'] == 'false') {
               $search_options = array($ma->POST['id_field']);
            } else if (isset($ma->POST['common_options'][$ma->POST['id_field']])) {
               $search_options = $ma->POST['common_options'][$ma->POST['id_field']];
            } else {
               $search_options = array();
            }

            $items         = array();
            foreach ($search_options as $search_option) {
               $search_option = explode(':', $search_option);
               $itemtype      = $search_option[0];
               $index         = $search_option[1];

               if (!$item = getItemForItemtype($itemtype)) {
                  continue;
               }

               if (InfoCom::canApplyOn($itemtype)) {
                  Session::checkSeveralRightsOr(array($itemtype  => UPDATE,
                                                      "infocom"  => UPDATE));
               } else {
                  $item->checkGlobal(UPDATE);
               }

               $search = Search::getOptions($itemtype);
               if (!isset($search[$index])) {
                  exit();
               }
               $item->search = $search[$index];

               $items[] = $item;
            }

            if (count($items) == 0) {
               exit();
            }

            // TODO: ensure that all items are equivalent ...
            $item   = $items[0];
            $search = $item->search;

            $plugdisplay = false;
            if (($plug = isPluginItemType($item->getType()))
                // Specific for plugin which add link to core object
                || ($plug = isPluginItemType(getItemTypeForTable($item->search['table'])))) {
               $plugdisplay = Plugin::doOneHook($plug['plugin'], 'MassiveActionsFieldsDisplay',
                                                array('itemtype' => $item->getType(),
                                                      'options'  => $item->search));
            }

            if (empty($search["linkfield"])
                ||($search['table'] == 'glpi_infocoms')) {
               $fieldname = $search["field"];
            } else {
               $fieldname = $search["linkfield"];
            }

            if (!$plugdisplay) {
               $options = array();
               $values  = array();
               // For ticket template or aditional options of massive actions
               if (isset($ma->POST['options'])) {
                  $options = $ma->POST['options'];
               }
               if (isset($ma->POST['additionalvalues'])) {
                  $values = $ma->POST['additionalvalues'];
               }
               $values[$search["field"]] = '';
               echo $item->getValueToSelect($search, $fieldname, $values, $options);
            }

            $items_index = array();
            foreach ($search_options as $search_option) {
               $search_option = explode(':', $search_option);
               $items_index[$search_option[0]] = $search_option[1];
            }
            echo Html::hidden('search_options', array('value' => $items_index));
            echo Html::hidden('field', array('value' => $fieldname));
            echo "<br>\n";

            $submitname = _sx('button','Post');
            if (isset($ma->POST['submitname']) && $ma->POST['submitname']) {
               $submitname= stripslashes($ma->POST['submitname']);
            }
            echo Html::submit($submitname, array('name' => 'massiveaction'));

            return true;

      }
      return false;
   }


   /**
    * Update the progress bar
    *
    * Display and update the progress bar. If the delay is more than 1 second, then activate it
    *
    * @return nothing (display only)
   **/
   function updateProgressBars() {

      if ($this->timer->getTime() > 1) {
         // If the action's delay is more than one second, the display progress bars
         $this->display_progress_bars = true;
      }

      if ($this->display_progress_bars) {
         if (!isset($this->progress_bar_displayed)) {
            Html::progressBar('main_'.$this->identifier, array('create'  => true,
                                                               'message' => $this->action_name));
            $this->progress_bar_displayed         = true;
            $this->fields_to_remove_when_reload[] = 'progress_bar_displayed';
            if (count($this->items) > 1) {
               Html::progressBar('itemtype_'.$this->identifier, array('create'  => true));
            }
         }
         $percent = 100 * $this->nb_done / $this->nb_items;
         Html::progressBar('main_'.$this->identifier, array('percent' => $percent));
         if ((count($this->items) > 1) && isset($this->current_itemtype)) {
            $itemtype = $this->current_itemtype;
            if (isset($this->items[$itemtype])) {
               if (isset($this->done[$itemtype])) {
                  $nb_done = count($this->done[$itemtype]);
               } else {
                  $nb_done = 0;
               }
               $percent = 100 * $nb_done / count($this->items[$itemtype]);
               Html::progressBar('itemtype_'.$this->identifier,
                                 array('message' => $itemtype::getTypeName(Session::getPluralNumber()),
                                       'percent' => $percent));
            }
         }
      }
   }


   /**
    * Process the massive actions for all passed items. This a switch between different methods:
    * new system, old one and plugins ...
    *
    * @return an array of results (ok, ko, noright counts, redirect ...)
   **/
   function process() {

      if (!empty($this->remainings)) {

         $this->updateProgressBars();

         if (isset($this->messaget_after_redirect)) {
            $_SESSION["MESSAGE_AFTER_REDIRECT"] = $this->messaget_after_redirect;
            Html::displayMessageAfterRedirect();
            unset($this->messaget_after_redirect);
         }

         $processor = $this->processor;

         $this->processForSeveralItemtypes();
      }

      $this->results['redirect'] = $this->redirect;

      // unset $this->identifier to ensure the action won't register in $_SESSION
      unset($this->identifier);

      return $this->results;
   }


   /**
    * Process the specific massive actions for severl itemtypes
    * @return array of the results for the actions
   **/
   function processForSeveralItemtypes() {

      $processor = $this->processor;
      foreach ($this->remainings as $itemtype => $ids) {
         if ($item = getItemForItemtype($itemtype)) {
            $processor::processMassiveActionsForOneItemtype($this, $item, $ids);
         }
      }
   }


   /**
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $CFG_GLPI;

      $action = $ma->getAction();

      switch ($action) {
         case 'delete' :
            foreach ($ids as $id) {
               if ($item->can($id, DELETE)) {
                  if ($item->delete(array("id" => $id))) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            break;

         case 'restore' :
            foreach ($ids as $id) {
               if ($item->can($id, PURGE)) {
                  if ($item->restore(array("id" => $id))) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            break;

         case 'purge_item_but_devices' :
         case 'purge_but_item_linked' :
         case 'purge' :
            foreach ($ids as $id) {
               if ($item->can($id, PURGE)) {
                  $force = 1;
                  // Only mark deletion for
                  if ($item->maybeDeleted()
                      && $item->useDeletedToLockIfDynamic()
                      && $item->isDynamic()) {
                     $force = 0;
                  }
                  $delete_array = array('id' => $id);
                  if ($action == 'purge_item_but_devices') {
                     $delete_array['keep_devices'] = true;
                  }

                  if ($item instanceof CommonDropdown) {
                     if ($item->haveChildren()) {
                        if ($action != 'purge_but_item_linked') {
                           $force = 0;
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           $ma->addMessage(__("You can't delete that item by massive actions, because it has sub-items"));
                           $ma->addMessage(__("but you can do it by the form of the item"));
                           continue;
                        }
                     }
                     if ($item->isUsed()) {
                        if ($action != 'purge_but_item_linked') {
                           $force = 0;
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           $ma->addMessage(__("You can't delete that item, because it is used for one or more items"));
                           $ma->addMessage(__("but you can do it by the form of the item"));
                           continue;
                        }
                     }
                  }
                  if ($item->delete($delete_array, $force)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            break;

         case 'update' :
            if ((!isset($ma->POST['search_options']))
                || (!isset($ma->POST['search_options'][$item->getType()]))) {
               return false;
            }
            $index     = $ma->POST['search_options'][$item->getType()];
            $searchopt = Search::getCleanedOptions($item->getType(), UPDATE);
            $input     = $ma->POST;
            if (isset($searchopt[$index])) {
               /// Infocoms case
               if (!isPluginItemType($item->getType())
                   && Search::isInfocomOption($item->getType(), $index)) {

                  $ic               = new Infocom();
                  $link_entity_type = -1;
                  /// Specific entity item
                  if ($searchopt[$index]["table"] == "glpi_suppliers") {
                     $ent = new Supplier();
                     if ($ent->getFromDB($input[$input["field"]])) {
                        $link_entity_type = $ent->fields["entities_id"];
                     }
                  }
                  foreach ($ids as $key) {
                     if ($item->getFromDB($key)) {
                        if (($link_entity_type < 0)
                            || ($link_entity_type == $item->getEntityID())
                            || ($ent->fields["is_recursive"]
                                && in_array($link_entity_type,
                                            getAncestorsOf("glpi_entities",
                                                           $item->getEntityID())))) {
                           $input2["items_id"] = $key;
                           $input2["itemtype"] = $item->getType();

                           if ($ic->can(-1, CREATE, $input2)) {
                              // Add infocom if not exists
                              if (!$ic->getFromDBforDevice($item->getType(),$key)) {
                                 $input2["items_id"] = $key;
                                 $input2["itemtype"] = $item->getType();
                                 unset($ic->fields);
                                 $ic->add($input2);
                                 $ic->getFromDBforDevice($item->getType(), $key);
                              }
                              $id = $ic->fields["id"];
                              unset($ic->fields);
                              if ($ic->update(array('id'            => $id,
                                                    $input["field"] => $input[$input["field"]]))) {
                                 $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                              } else {
                                 $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                                 $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                              }
                           } else {
                              $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                              $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                           }
                        } else {
                           $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                     }
                  }

               } else { /// Not infocoms
                  $link_entity_type = array();
                  /// Specific entity item
                  $itemtable = getTableForItemType($item->getType());
                  $itemtype2 = getItemTypeForTable($searchopt[$index]["table"]);
                  if ($item2 = getItemForItemtype($itemtype2)) {
                     if (($index != 80) // No entities_id fields
                         && ($searchopt[$index]["table"] != $itemtable)
                         && $item2->isEntityAssign()
                         && $item->isEntityAssign()) {
                        if ($item2->getFromDB($input[$input["field"]])) {
                           if (isset($item2->fields["entities_id"])
                               && ($item2->fields["entities_id"] >= 0)) {

                              if (isset($item2->fields["is_recursive"])
                                  && $item2->fields["is_recursive"]) {
                                 $link_entity_type = getSonsOf("glpi_entities",
                                                               $item2->fields["entities_id"]);
                              } else {
                                 $link_entity_type[] = $item2->fields["entities_id"];
                              }
                           }
                        }
                     }
                  }
                  foreach ($ids as $key) {
                     if ($item->canEdit($key)
                         && $item->canMassiveAction($action, $input['field'],
                                                    $input[$input["field"]])) {
                        if ((count($link_entity_type) == 0)
                            || in_array($item->fields["entities_id"], $link_entity_type)) {
                           if ($item->update(array('id'            => $key,
                                                   $input["field"] => $input[$input["field"]]))) {
                              $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                           } else {
                              $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                              $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                           }
                        } else {
                           $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                     }
                  }
               }
            }
            break;

         case 'add_transfer_list' :
            $itemtype = $item->getType();
            if (!isset($_SESSION['glpitransfer_list'])) {
               $_SESSION['glpitransfer_list'] = array();
            }
            if (!isset($_SESSION['glpitransfer_list'][$itemtype])) {
               $_SESSION['glpitransfer_list'][$itemtype] = array();
            }
            foreach ($ids as $id) {
               $_SESSION['glpitransfer_list'][$itemtype][$id] = $id;
               $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
            }
            $ma->setRedirect($CFG_GLPI['root_doc'].'/front/transfer.action.php');
            break;

      }
   }


   /**
    * Set the page to redirect for specific actions. By default, call previous page.
    * This should be call once for the given action.
    *
    * @param $redirect link to the page
    *
    * @return nothing
   **/
   function setRedirect($redirect) {
      $this->redirect = $redirect;
   }


   /**
    * add a message to display when action is done.
    *
    * @param $message the message to add
    *
    * @return nothing
   **/
   function addMessage($message) {
      $this->results['messages'][] = $message;
   }


   /**
    * Set an item as done. If the delay is too long, then reload the page to continue the action.
    * Update the progress if necessary.
    *
    * @param $itemtype    the type of the item that has been done
    * @param $id          id or array of ids of the item(s) that have been done.
    * @param $result:
    *                self::NO_ACTION      in case of no specific action (used internally for older actions)
    *                MassiveAction::ACTION_OK      everything is OK for the action
    *                MassiveAction::ACTION_KO      something went wrong for the action
    *                MassiveAction::ACTION_NORIGHT not anough right for the action
   **/
   function itemDone($itemtype, $id, $result) {

      $this->current_itemtype = $itemtype;

      if (!isset($this->done[$itemtype])) {
         $this->done[$itemtype] = array();
      }

      if (is_array($id)) {
         $number = count($id);
         foreach ($id as $single) {
            unset($this->remainings[$itemtype][$single]);
            $this->done[$itemtype][] = $single;
         }
      } else {
         unset($this->remainings[$itemtype][$id]);
         $this->done[$itemtype][] = $id;
         $number = 1;
      }
      if (count($this->remainings[$itemtype]) == 0) {
         unset($this->remainings[$itemtype]);
      }

      switch ($result) {
         case MassiveAction::ACTION_OK :
            $this->results['ok'] += $number;
            break;

         case MassiveAction::ACTION_KO :
            $this->results['ko'] += $number;
            break;

         case MassiveAction::ACTION_NORIGHT :
            $this->results['noright'] += $number;
            break;
      }
      $this->nb_done += $number;

      // If delay is to big, then reload !
      if ($this->timer->getTime() > $this->timeout_delay) {
         Html::redirect($_SERVER['PHP_SELF'].'?identifier='.$this->identifier);
      }

      $this->updateProgressBars();
   }
}
?>
