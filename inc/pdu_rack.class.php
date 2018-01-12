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
      global $DB, $CFG_GLPI;

      $pdu   = new PDU;
      $pdu_m = new PDUModel;
      $pra   = new self;
      $sides = self::getSides();

      $found_pdus = [];
      // find pdus from this relation
      foreach ($DB->request([
         'FROM' => self::getTable(),
         'WHERE' => [
            'racks_id' => $rack->getID()
         ],
         'ORDER' => 'side'
      ]) as $current) {
         $found_pdus[] = [
            'pdus_id'  => $current['pdus_id'],
            'racked'   => false,
            'position' => $current['position'],
            'side'     => $current['side'],
            'bgcolor'  => $current['bgcolor'],
         ];
      }
      // find pdus from item_rack relation
      foreach ($DB->request([
         'FROM' => Item_Rack::getTable(),
         'WHERE' => [
            'racks_id' => $rack->getID(),
            'itemtype' => 'PDU'
         ]
      ]) as $current) {
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

      $pra_url = PDU_Rack::getFormUrl()."?racks_id=$racks_id";
      $ira_url = Item_Rack::getFormUrl()."?orientation=0&position=1&racks_id=$racks_id";

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
            $('#pdu_add_sub_form$rand').load(form_url, {
               ajax: true,
            });
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
