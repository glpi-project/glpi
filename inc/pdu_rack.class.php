<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

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
**/
class PDU_Rack extends CommonDBRelation {

   static public $itemtype_1 = 'Rack';
   static public $items_id_1 = 'racks_id';
   static public $itemtype_2 = 'PDU';
   static public $items_id_2 = 'pdus_id';
   static public $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
   static public $mustBeAttached_1      = false;
   static public $mustBeAttached_2      = false;

   const SIDE_LEFT   = 1;
   const SIDE_RIGHT  = 2;
   const SIDE_TOP    = 3;
   const SIDE_BOTTOM = 4;

   static function getTypeName($nb = 0) {
      return _n('Item', 'Item', $nb);
   }

   function post_getEmpty() {
      $this->fields['bgcolor'] = '#FF9D1F';
   }

   function getForbiddenStandardMassiveAction() {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'MassiveAction:update';
      $forbidden[] = 'CommonDBConnexity:affect';
      $forbidden[] = 'CommonDBConnexity:unaffect';

      return $forbidden;
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

   /**
    * Prepares and check validity of input (for update and add) and
    *
    * @param array $input Input data
    *
    * @return array
    */
   private function prepareInput($input) {
      $error_detected = [];

      $pdus_id  = $this->fields['pdus_id'];
      $racks_id = $this->fields['racks_id'];
      $position = $this->fields['position'];
      $side     = $this->fields['side'];

      //check for requirements
      if ($this->isNewItem()) {
         if (!isset($input['pdus_id'])) {
            $error_detected[] = __('A pdu is required');
         }

         if (!isset($input['racks_id'])) {
            $error_detected[] = __('A rack is required');
         }

         if (!isset($input['position'])) {
            $error_detected[] = __('A position is required');
         }

         if (!isset($input['side'])) {
            $error_detected[] = __('A side is required');
         }
      }

      if (isset($input['pdus_id'])) {
         $pdus_id = $input['pdus_id'];
      }
      if (isset($input['racks_id'])) {
         $racks_id = $input['racks_id'];
      }
      if (isset($input['position'])) {
         $position = $input['position'];
      }
      if (isset($input['side'])) {
         $side = $input['side'];
      }

      if (!count($error_detected)) {
         //check if required U are available at position
         $required_units = 1;

         $rack = new Rack;
         $rack->getFromDB($racks_id);

         $pdu = new PDU;
         $pdu->getFromDB($pdus_id);

         $filled = self::getFilled($rack, $side);

         $model = new PDUModel;
         if ($model->getFromDB($pdu->fields['pdumodels_id'])) {
            if ($model->fields['required_units'] > 1) {
               $required_units = $model->fields['required_units'];
            }
         }

         if (in_array($side, [self::SIDE_LEFT, self::SIDE_RIGHT])
             && ($position > $rack->fields['number_units']
                 || $position + $required_units  > $rack->fields['number_units'] + 1)) {
            $error_detected[] = __('Item is out of rack bounds');
         } else {
            for ($i = 0; $i < $required_units; $i++) {
               if ($filled[$position + $i] > 0
                   && $filled[$position + $i] != $pdus_id) {
                  $error_detected[] = __('Not enough space available to place item');
                  break;
               }
            }
         }
      }

      if (count($error_detected)) {
         foreach ($error_detected as $error) {
            Session::addMessageAfterRedirect(
               $error,
               true,
               ERROR
            );
         }
         return false;
      }

      return $input;

   }

   /**
    * Get already filled places
    * @param  Rack    $rack The current rack
    * @param  integer $side The side of rack to check
    * @return Array   [position -> racks_id | 0]
    */
   static function getFilled(Rack $rack, $side = 0) {
      $pdu    = new PDU;
      $model  = new PDUModel;
      $filled = array_fill(0, $rack->fields['number_units'], 0);

      $used = self::getForRackSide($rack, $side);
      foreach ($used as $current_pdu) {
         $required_units = 1;
         $pdu->getFromDB($current_pdu['pdus_id']);

         if (in_array($side, [self::SIDE_LEFT, self::SIDE_RIGHT])
             && $model->getFromDB($pdu->fields['pdumodels_id'])) {
            if ($model->fields['required_units'] > 1) {
               $required_units = $model->fields['required_units'];
            }
         }

         for ($i = 0; $i <= $required_units; $i++) {
            $position = $current_pdu['position'] + $i;
            $filled[$position] = $current_pdu['pdus_id'];
         }
      }

      return $filled;
   }

   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;

      // search used racked (or sided mounted) pdus
      $used = [];
      foreach ($DB->request([
         'FROM' => $this->getTable()
      ]) as $not_racked) {
         $used[] = $not_racked['pdus_id'];
      }
      foreach ($DB->request([
         'SELECT' => 'items_id',
         'FROM'   => Item_Rack::getTable(),
         'WHERE'  => [
            'itemtype' => 'PDU'
         ]
      ]) as $racked) {
         $used[] = $racked['items_id'];
      };

      echo "<div class='center'>";

      $this->initForm($ID, $options);
      $this->showFormHeader();

      $rack = new Rack();
      $rack->getFromDB($this->fields['racks_id']);

      $rand = mt_rand();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_pdus_id$rand'>".__('PDU')."</label></td>";
      echo "<td>";
      PDU::dropdown([
         'value'       => $this->fields["pdus_id"],
         'rand'        => $rand,
         'used'        => $used,
         'entity'      => $rack->fields['entities_id'],
         'entity_sons' => $rack->fields['is_recursive'],
      ]);
      echo "</td>";
      echo "<td><label for='dropdown_side$rand'>".__('Side (from rear perspective)')."</label></td>";
      echo "<td >";
      Dropdown::showFromArray(
         'side',
         self::getSides(), [
            'value' => $this->fields["side"],
            'rand'  => $rand,
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_racks_id$rand'>".__('Rack')."</label></td>";
      echo "<td>";
      Rack::dropdown(['value' => $this->fields["racks_id"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_position$rand'>".__('Position')."</label></td>";
      echo "<td >";
      Dropdown::showNumber(
         'position', [
            'value'  => $this->fields["position"],
            'min'    => 1,
            'max'    => $rack->fields['number_units'],
            'step'   => 1,
            // 'used'   => $rack->getFilled($this->fields['itemtype'], $this->fields['items_id']),
            'rand'   => $rand
         ]
      );
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='bgcolor$rand'>".__('Background color')."</label></td>";
      echo "<td>";
      Html::showColorField(
         'bgcolor', [
            'value'  => $this->fields['bgcolor'],
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   static function showListForRack(Rack $rack) {
      global $DB;

      echo "<h2>".__("Side pdus")."</h2>";

      $pdu     = new PDU;
      $canedit = $rack->canEdit($rack->getID());
      $rand    = mt_rand();
      $items   = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'racks_id' => $rack->getID()
         ]
      ]);

      if (!count($items)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No item found')."</th></tr>";
         echo "</table>";
      } else {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = [
               'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
               'container'       => 'mass'.__CLASS__.$rand
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".__('Item')."</th>";
         $header .= "<th>".__('Side')."</th>";
         $header .= "<th>".__('Position')."</th>";
         $header .= "</tr>";

         echo $header;
         foreach ($items as $row) {
            if ($pdu->getFromDB($row['pdus_id'])) {
               echo "<tr lass='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
                  echo "</td>";
               }
               echo "<td>".$pdu->getLink()."</td>";
               echo "<td>".self::getSideName($row['side'])."</td>";
               echo "<td>{$row['position']}</td>";
               echo "</tr>";
            }
         }
         echo $header;
         echo "</table>";

         if ($canedit && count($items)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
         }
         if ($canedit) {
            Html::closeForm();
         }
      }
   }

   static function showStatsForRack(Rack $rack) {
      global $DB, $CFG_GLPI;

      $pdu   = new PDU;
      $pdu_m = new PDUModel;
      $pra   = new self;
      $sides = self::getSides();

      $found_pdus = [];
      // find pdus from this relation
      $iterator = $DB->request([
         'FROM' => self::getTable(),
         'WHERE' => [
            'racks_id' => $rack->getID()
         ],
         'ORDER' => 'side'
      ]);
      foreach ($iterator as $current) {
         $found_pdus[] = [
            'pdus_id'  => $current['pdus_id'],
            'racked'   => false,
            'position' => $current['position'],
            'side'     => $current['side'],
            'bgcolor'  => $current['bgcolor'],
         ];
      }
      // find pdus from item_rack relation
      $iterator = $DB->request([
         'FROM' => Item_Rack::getTable(),
         'WHERE' => [
            'racks_id' => $rack->getID(),
            'itemtype' => 'PDU'
         ]
      ]);
      foreach ($iterator as $current) {
         $found_pdus[] = [
            'pdus_id'  => $current['items_id'],
            'racked'   => true,
            'position' => $current['position'],
            'side'     => false,
            'bgcolor'  => $current['bgcolor'],
         ];
      }

      echo "<div id='rack_pdus' class='rack_side_block'>";
      echo "<h2>".__("Power units")."</h2>";
      echo "<div class='rack_side_block_content'>";
      if (count($found_pdus)) {
         echo "<table class='pdu_list'>";
         foreach ($found_pdus as $current_pdu) {
            if ($pdu->getFromDB($current_pdu['pdus_id'])) {
               $bg_color = $current_pdu['bgcolor'];
               $fg_color = !empty($current_pdu['bgcolor'])
                              ? Html::getInvertedColor($current_pdu['bgcolor'])
                              : "";
               $fg_color_s = "color: $fg_color;";
               echo "<tr style='background-color: $bg_color; color: $fg_color;'>";
               echo "<td class='rack_position'>";
               if ($current_pdu['racked']) {
                  echo "<i class='fa fa-server fa-fw'
                           title='".__("Racked")." (".$current_pdu['position'].")'></i>";
               } else {
                  switch ($current_pdu['side']) {
                     case self::SIDE_LEFT:
                        echo "<i class='fa fa-arrow-left fa-fw'
                                 title='".__("On left")." (".$current_pdu['position'].")'></i>";
                        break;
                     case self::SIDE_RIGHT:
                        echo "<i class='fa fa-arrow-right fa-fw'
                                 title='".__("On right")." (".$current_pdu['position'].")'></i>";
                        break;
                     case self::SIDE_TOP:
                        echo "<i class='fa fa-arrow-up fa-fw'
                                 title='".__("On left")."'></i>";
                        break;
                     case self::SIDE_BOTTOM:
                        echo "<i class='fa fa-arrow-down fa-fw'
                                 title='".__("On left")."'></i>";
                        break;
                  }
               }
               echo "</td>";

               echo "<td>";
               echo "<a href='".$pdu->getLinkURL()."'style='$fg_color_s'>".$pdu->getName()."</a>";
               echo "</td>";

               echo "<td>";
               if ($pdu_m->getFromDB($pdu->fields['pdumodels_id'])) {
                  echo "<i class='fa fa-bolt'></i>";
                  echo $pdu_m->fields['max_power']."W";
               }
               echo "</td>";
               echo "</tr>";
            }
         }
         echo "</table>";
      }
      echo "<a id='add_pdu' class='sub_action'>";
      echo "<i class='fa fa-plus'></i>";
      echo _sx('button', "Add");
      echo "</a>";
      echo "</div>";
      echo "</div>";

      $ajax_url = $CFG_GLPI['root_doc']."/ajax/rack.php";
      $js = <<<JAVASCRIPT
      $(function() {
         $('#add_pdu').click(function(event) {
            event.preventDefault();
            $.ajax({
               url : "{$ajax_url}",
               data: {
                  racks_id: "{$rack->getID()}",
                  action: "show_pdu_form",
                  ajax: true,
               },
               success: function(data) {
                  $('#grid-dialog')
                     .html(data)
                     .dialog({
                        modal: true,
                        width: 'auto'
                     });
               }
            });
         });
      });
JAVASCRIPT;
      echo Html::scriptBlock($js);
   }

   static function showFirstForm($racks_id = 0) {

      $rand = mt_rand();
      echo "<label for='dropdown_sub_form$rand'>".__("The pdu will be")."</label>&nbsp;";
      Dropdown::showFromArray('sub_form', [
         'racked'    => __('racked'),
         'side_rack' => __('placed at rack side'),
      ], [
         'display_emptychoice' => true,
         'on_change'           => 'showAddPduSubForm()',
         'rand'                => $rand,
      ]);

      $pra_url = PDU_Rack::getFormURL()."?racks_id=$racks_id&ajax=true";
      $ira_url = Item_Rack::getFormURL()."?_onlypdu=true&orientation=0&position=1&racks_id=$racks_id&ajax=true";

      $js = <<<JAVASCRIPT
      var showAddPduSubForm = function() {
         var sub_form = $('#dropdown_sub_form{$rand}').val();

         var form_url = "";
         if (sub_form == "racked") {
            form_url = "{$ira_url}";
         } else if (sub_form == "side_rack") {
            form_url = "{$pra_url}";
         }

         if (form_url.length) {
            $('#pdu_add_sub_form$rand').load(form_url);
         } else {
            $('#pdu_add_sub_form$rand').html("");
         }
      }
JAVASCRIPT;
      echo Html::scriptBlock($js);
      echo "<div id='pdu_add_sub_form$rand'></div>";
   }

   static function showVizForRack(Rack $rack, $side) {
      global $CFG_GLPI;

      $rand  = mt_rand();
      $num_u = $rack->fields['number_units'];
      $pdu   = new PDU;
      $pdu_m = new PDUModel;
      $rel   = new self;

      $found_pdus_side = self::getForRackSide($rack, $side);
      // check if the rack has sided pdu on other side (to get symetrical view)
      $found_all_pdus_side = self::getForRackSide($rack, [$side, self::getOtherSide($side)]);

      $float = false;
      $add_class = "side_pdus_nofloat";
      if ($side === self::SIDE_LEFT
          || $side === self::SIDE_RIGHT) {
         $add_class = "side_pdus_float";
         $float = true;
      }
      if (count($found_all_pdus_side)) {
         echo "<div class='side_pdus $add_class'>";
         if ($float) {
            echo "<div class='side_pdus_graph grid-stack grid-stack-1'
                       id='side_pdus_$rand'
                       data-gs-width='1'
                       data-gs-height='".($rack->fields['number_units'] + 1)."'>";
         }

         foreach ($found_pdus_side as $current) {
            $bg_color   = $current['bgcolor'];
            $fg_color   = !empty($current['bgcolor'])
                             ? Html::getInvertedColor($current['bgcolor'])
                             : "";
            $fg_color_s = "color: $fg_color;";
            $picture = false;

            if ($rel->getFromDB($current['id'])
                && $pdu->getFromDB($current['pdus_id'])) {
               $y      = $num_u - $current['position'];
               $height = 1;
               $model_name = "";
               if ($pdu_m->getFromDB($pdu->fields['pdumodels_id'])) {
                  $height     = $pdu_m->fields['required_units'];
                  $y          = $num_u + 1 - $current['position'] - $height;
                  $picture    = $pdu_m->fields['picture_front'];
                  $model_name = $pdu_m->getName();
               }

               $tip = "<span class='tipcontent'>";
               $tip.= "<span>
                        <label>".__('Type').":</label>".
                        $pdu->getTypeName()."
                     </span>
                     <span>
                        <label>".__('name').":</label>".
                        $pdu->getName()."
                     </span>";
               if (!empty($pdu->fields['serial'])) {
                  $tip.= "<span>
                           <label>".__('serial').":</label>".
                           $pdu->fields['serial']."
                        </span>";
               }
               if (!empty($pdu->fields['otherserial'])) {
                  $tip.= "<span>
                           <label>".__('Inventory number').":</label>".
                           $pdu->fields['otherserial']."
                        </span>";
               }
               if (!empty($model_name)) {
                  $tip.= "<span>
                           <label>".__('model').":</label>
                           $model_name
                        </span>";
               }
               $tip.= "</span>";

               $picture_c = "";
               $item_rand = mt_rand();

               if ($picture) {
                  $picture_c = 'with_picture';
                  echo "<style>
                     #item_$item_rand:after {
                        width: ".($height * 21 - 9) ."px;
                        background: $bg_color url($picture) 0 0/100% no-repeat;
                     }
                  </style>";
               }

               echo "<div class='grid-stack-item $picture_c'
                       id='item_$item_rand'
                       data-gs-id='{$current['id']}'
                       data-gs-height='$height' data-gs-width='1'
                       data-gs-x='0' data-gs-y='$y'
                       style='background-color: $bg_color; color: $fg_color;'>
                  <div class='grid-stack-item-content' style='$fg_color_s'>
                     <i class='item_rack_icon fa fa-plug fa-rotate-270'></i>
                     <span class='rotated_text'>
                        <a href='".$pdu->getLinkURL()."'
                           class='itemrack_name'
                           title='".$pdu->getName()."'
                           style='$fg_color_s'>".$pdu->getName()."
                        </a>
                     </span>
                     <a href='".$rel->getLinkUrl()."' class='rel-link'>
                        <i class='fa fa-pencil fa-rotate-270'
                           style='$fg_color_s'
                           title='".__("Edit rack relation")."'></i>
                     </a>
                     $tip
                  </div>
               </div>";
            }
         }

         if ($float) {
            echo "<div class='grid-stack-item lock-bottom'
                    data-gs-no-resize='true' data-gs-no-move='true'
                    data-gs-height='1'       data-gs-width='1'
                    data-gs-x='0'            data-gs-y='$num_u'>
               </div>";

            echo "</div>"; // .side_pdus_graph
         }
         echo "</div>"; // .side_pdus

         $ajax_url = $CFG_GLPI['root_doc']."/ajax/rack.php";

         $js = <<<JAVASCRIPT
         $(function() {
            $('#side_pdus_$rand')
               .on('change', function(event, items) {
                  if (dirty) {
                     return;
                  }
                  var grid = $(event.target).data('gridstack');
                  $.each(items, function(index, item) {
                     var new_pos = grid_rack_units - item.y - item.height + 1
                     $.post("{$ajax_url}", {
                        id: item.id,
                        action: 'move_pdu',
                        position: new_pos,
                     }, function (answer) {
                        var answer = jQuery.parseJSON(answer);

                        // revert to old position
                        if (!answer.status) {
                           dirty = true;
                           grid.move(item.el, x_before_drag, y_before_drag);
                           dirty = false;
                           displayAjaxMessageAfterRedirect();
                        } else {
                           dirty = false;
                        }
                     });
                  });
               })
         });
JAVASCRIPT;
         if ($float) {
            echo Html::scriptBlock($js);
         }
      }
   }

   /**
    * Return all possible side in a rack where a pdu can be placed
    * @return Array (int => label)
    */
   static function getSides() {
      return [
         self::SIDE_LEFT   => __('Left'),
         self::SIDE_RIGHT  => __('Right'),
         self::SIDE_TOP    => __('Top'),
         self::SIDE_BOTTOM => __('Bottom'),
      ];
   }

   /**
    * Get a side name from its index
    * @param  integer $side See class constants and above `getSides`` method
    * @return string        the side name
    */
   static function getSideName($side) {
      return self::getSides()[$side];
   }

   /**
    * Return an iterator for all pdu used in a side of a rack
    * @param  Rack    $rack
    * @param  integer $side
    * @return Iterator
    */
   static function getForRackSide(Rack $rack, $side) {
      global $DB;

      return $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => [
            'racks_id' => $rack->getID(),
            'side'     => $side
         ],
         'ORDER' => 'position ASC'
      ]);
   }

   /**
    * Return an iterator for all used pdu in all racks
    *
    * @return  Iterator
    */
   static function getUsed() {
      global $DB;

      return $DB->request([
         'FROM'  => self::getTable()
      ]);
   }

   /**
    * Return the opposite side from a passed side
    * @param  integer $side
    * @return integer       the oposite side
    */
   static function getOtherSide($side) {
      switch ($side) {
         case self::SIDE_TOP;
            return self::SIDE_BOTTOM;
            break;
         case self::SIDE_BOTTOM;
            return self::SIDE_TOP;
            break;
         case self::SIDE_LEFT;
            return self::SIDE_RIGHT;
            break;
         case self::SIDE_RIGHT;
            return self::SIDE_LEFT;
            break;
      }
      return false;
   }
}
