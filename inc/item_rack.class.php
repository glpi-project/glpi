<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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
class Item_Rack extends CommonDBRelation {

   static public $itemtype_1 = 'Rack';
   static public $items_id_1 = 'racks_id';
   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';
   static public $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
   static public $mustBeAttached_1      = false;
   static public $mustBeAttached_2      = false;

   static function getTypeName($nb = 0) {
      return _n('Item', 'Item', $nb);
   }

   /**
    * Count connection for an operating system
    *
    * @param Rack $rack Rack object instance
    *
    * @return integer
   **/
   static function countForRack(Rack $rack) {
      return countElementsInTable(self::getTable(),
                                  ['racks_id' => $rack->getID()]);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = 0;
      switch ($item->getType()) {
         default:
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable(
                  self::getTable(),
                  ['racks_id'  => $item->getID()]
               );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showItems($item, $withtemplate);
   }

   /**
    * Print racks items
    * @param  Rack   $rack the current rack instance
    * @return void
    */
   static function showItems(Rack $rack) {
      global $DB, $CFG_GLPI;

      $ID = $rack->getID();
      $rand = mt_rand();

      if (!$rack->getFromDB($ID)
          || !$rack->can($ID, READ)) {
         return false;
      }
      $canedit = $rack->canEdit($ID);

      $items = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'racks_id' => $rack->getID()
         ]
      ]);
      $link = new self();

      Session::initNavigateListItems(
         self::getType(),
         //TRANS : %1$s is the itemtype name,
         //        %2$s is the name of the item (used for headings of a list)
         sprintf(
            __('%1$s = %2$s'),
            $rack->getTypeName(1),
            $rack->getName()
         )
      );

      echo "<div id='switchview'>";
      echo "<i id='sviewlist' class='pointer fa fa-list-alt' title='".__('View as list')."'></i>";
      echo "<i id='sviewgraph' class='pointer fa fa-th-large selected' title='".__('View graphical representation')."'></i>";
      echo "</div>";

      $items = iterator_to_array($items);
      echo "<div id='viewlist'>";

      /*$rack = new self();*/
      if (!count($items)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No item found')."</th></tr>";
         echo "</table>";
      } else {
         if ($canedit) {
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
         $header .= "<th>".__('Position')."</th>";
         $header .= "<th>".__('Orientation')."</th>";
         $header .= "</tr>";

         echo $header;
         foreach ($items as $row) {
            $item = new $row['itemtype'];
            $item->getFromDB($row['items_id']);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
               echo "</td>";
            }
            echo "<td>" . $item->getLink() . "</td>";
            echo "<td>{$row['position']}</td>";
            $txt_orientation = $row['orientation'] == Rack::FRONT ? __('Front') : __('Rear');
            echo "<td>$txt_orientation</td>";
            echo "</tr>";
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
      echo "</div>";
      echo "<div id='viewgraph'>";

      $data = [];
      //all rows; empty
      for ($i = (int)$rack->fields['number_units']; $i > 0; --$i) {
         $data[Rack::FRONT][$i] = false;
         $data[Rack::REAR][$i] = false;
      }

      //fill rows
      $outbound = [];
      foreach ($items as $row) {
         $rel  = new self;
         $rel->getFromDB($row['id']);
         $item = new $row['itemtype'];
         $item->getFromDB($row['items_id']);

         $position = $row['position'];

         $gs_item = [
            'id'        => $row['id'],
            'name'      => $item->getName(),
            'x'         => $row['hpos'] >= 2 ? 1 : 0,
            'y'         => $rack->fields['number_units'] - $row['position'],
            'height'    => 1,
            'width'     => 2,
            'bgcolor'   => $row['bgcolor'],
            'picture_f' => null,
            'picture_r' => null,
            'url'       => $item->getLinkURL(),
            'rel_url'   => $rel->getLinkURL(),
            'rear'      => false,
            'half_rack' => false,
         ];

         $model_class = $item->getType() . 'Model';
         $modelsfield = strtolower($item->getType()) . 'models_id';
         $model = new $model_class;
         if ($model->getFromDB($item->fields[$modelsfield])) {
            $item->model = $model;

            if ($item->model->fields['required_units'] > 1) {
               $gs_item['height'] = $item->model->fields['required_units'];
               $gs_item['y']      = $rack->fields['number_units'] + 1
                                    - $row['position']
                                    - $item->model->fields['required_units'];
            }

            if ($item->model->fields['is_half_rack'] == 1) {
               $gs_item['half_rack'] = true;
               $gs_item['width'] = 1;
               $row['position'].= "_".$gs_item['x'];
               if ($row['orientation'] == Rack::REAR) {
                  $gs_item['x'] = $row['hpos'] == 2 ? 0 : 1;
               }
            }

            if (!empty($item->model->fields['picture_front'])) {
               $gs_item['picture_f'] = $item->model->fields['picture_front'];
            }
            if (!empty($item->model->fields['picture_rear'])) {
               $gs_item['picture_r'] = $item->model->fields['picture_rear'];
            }
         } else {
            $item->model = null;
         }

         if (isset($data[$row['orientation']][$position])) {
            $data[$row['orientation']][$row['position']] = [
               'row'     => $row,
               'item'    => $item,
               'gs_item' => $gs_item
            ];

            //add to other side if needed
            if ($item->model == null
                || $item->model->fields['depth'] >= 1) {
               $gs_item['rear'] = true;
               $flip_orientation = (int) !((bool) $row['orientation']);
               if ($gs_item['half_rack']) {
                  $gs_item['x'] = (int) !((bool) $gs_item['x']);
                  //$row['position'] = substr($row['position'], 0, -2)."_".$gs_item['x'];
               }
               $data[$flip_orientation][$row['position']] = [
                  'row'     => $row,
                  'item'    => $item,
                  'gs_item' => $gs_item
               ];
            }
         } else {
            $outbound[] = ['row' => $row, 'item' => $item, 'gs_item' => $gs_item];
         }
      }

      if (count($outbound)) {
         echo "<table class='outbound'><thead><th>";
         echo __('Following elements are out of rack bounds');
         echo "</th></thead><tbody>";
         foreach ($outbound as $out) {
            echo "<tr><td>".self::getCell($out)."</td></tr>";
         }
         echo "</tbody></table>";
      }

      echo '
      <div class="racks_row">
         <span class="racks_view_controls">
            <span class="mini_toggle active"
                  id="toggle_images">'.__('images').'</span>
            <span class="mini_toggle active"
                  id="toggle_text">'.__('texts').'</span>
            <div class="sep"></div>
         </span>
         <div class="racks_col rack_side">
            <h2>'.__('Front').'</h2>
            <ul class="indexes"></ul>
            <div class="grid-stack grid-stack-2 grid-rack" id="grid-front">
               <div class="racks_add"></div>';
      foreach ($data[Rack::FRONT] as $current_item) {
         echo self::getCell($current_item);
      }
      echo '   <div class="grid-stack-item lock-bottom"
                    data-gs-no-resize="true" data-gs-no-move="true"
                    data-gs-height="1" data-gs-width="2" data-gs-x="0" data-gs-y="'.$rack->fields['number_units'].'"></div>
            </div>
            <ul class="indexes"></ul>
         </div>
         <div class="racks_col rack_side">
            <h2>'.__('Rear').'</h2>
            <ul class="indexes"></ul>
            <div class="grid-stack grid-stack-2 grid-rack" id="grid2-rear">
               <div class="racks_add"></div>';
      foreach ($data[Rack::REAR] as $current_item) {
         echo self::getCell($current_item);
      }
      echo '   <div class="grid-stack-item lock-bottom"
                    data-gs-no-resize="true" data-gs-no-move="true"
                    data-gs-height="1" data-gs-width="2" data-gs-x="0" data-gs-y="'.$rack->fields['number_units'].'">
               </div>
            </div>
            <ul class="indexes"></ul>
         </div>
         <div class="racks_col">';
      self::showStats($rack, $data);
      echo '</div>'; // .racks_col
      echo '</div>'; // .racks_row
      echo '<div class="sep"></div>';
      echo "<div id='grid-dialog'></div>";
      echo "</div>"; // #viewgraph

      $rack_add_tip = __s('Insert an item here');
      $ajax_url     = $CFG_GLPI['root_doc']."/ajax/rack.php";

      $js = <<<JAVASCRIPT
      $(function() {
         $('#sviewlist').on('click', function() {
            $('#viewlist').show();
            $('#viewgraph').hide();
            $(this).addClass('selected');
            $('#sviewgraph').removeClass('selected');
         });
         $('#sviewgraph').on('click', function() {
            $('#viewlist').hide();
            $('#viewgraph').show();
            $(this).addClass('selected');
            $('#sviewlist').removeClass('selected');
         });

         $('#toggle_images').on('click', function(){
            $('#toggle_text').toggle();
            $(this).toggleClass('active');
            $('#viewgraph').toggleClass('clear_picture');
         });

         $('#toggle_text').on('click', function(){
            $(this).toggleClass('active');
            $('#viewgraph').toggleClass('clear_text');
         });

         $('.grid-stack').gridstack({
            width: 2,
            height: {$rack->fields['number_units']}+1,
            cellHeight: 20,
            verticalMargin: 1,
            float: true,
            disableOneColumnMode: true,
            animate: true,
            removeTimeout: 100,
            disableResize: true,
            draggable: {
              handle: '.grid-stack-item-content',
              appendTo: 'body',
              containment: '.grid-stack',
              cursor: 'move',
              scroll: true,
            }
         });

         for (var i = {$rack->fields['number_units']}; i >= 1; i--) {
            // add index number front of each rows
            $('.indexes').append('<li>' + i + '</li>');

            // append cells for adding new items
            $('.racks_add').append('<div class=\"cell_add\"><span class="tipcontent">{$rack_add_tip}</span></div>');
         }

         var lockAll = function() {
            // lock all item (prevent pushing down elements)
            $('.grid-stack').each(function (idx, gsEl) {
               $(gsEl).data('gridstack').locked('.grid-stack-item', true);
            });

            // add containment to items, this avoid bad collisions on the start of the grid
            $('.grid-stack .grid-stack-item').draggable('option', 'containment', 'parent');
         };
         lockAll(); // call it immediatly

         // grid events
         $('.cell_add').click(function() {
            var index = {$rack->fields['number_units']} - $(this).index();
            var parent_pos = $(this).parents('.racks_col').index();
            var parent = (parent_pos == 1
                           ? 0  // front
                           : 1); // rear
            var current_grid = $(this).parents('.grid-stack').data('gridstack');

            $.ajax({
                  url : "{$link->getFormURL()}",
                  data: {
                     racks_id: $ID,
                     orientation: parent,
                     unit: index,
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

         var x_before_drag = 0;
         var y_before_drag = 0;
         var dirty = false;
         var getHpos = function(x, is_half_rack, is_rack_rear) {
            if (!is_half_rack) {
               return 0;
            } else if (x == 0 && !is_rack_rear) {
               return 1;
            } else if (x == 0 && is_rack_rear) {
               return 2;
            } else if (x == 1 && is_rack_rear) {
               return 1;
            } else if (x == 1 && !is_rack_rear) {
               return 2;
            }
         };

         // drag&drop scenario:
         // - we start by storing position before drag
         // - we send position to db by ajax after drag stop event
         // - if ajax answer return a fail, we restore item to the old position
         //   and we display a message explaning the failure
         // - else we move the other side of asset (if exists)
         $('.grid-stack')
            .on('change', function(event, items) {
               if (dirty) {
                  return;
               }
               var grid = $(event.target).data('gridstack');
               var is_rack_rear = $(grid.container).parents('.racks_col').index() != 0;
               $.each(items, function(index, item) {
                  var is_half_rack = item.el.hasClass('half_rack');
                  var is_el_rear   = item.el.hasClass('rear');
                  var new_pos      = {$rack->fields['number_units']}
                                     - item.y
                                     - item.height
                                     + 1;
                  $.post('{$ajax_url}', {
                     id: item.id,
                     action: 'move_item',
                     position: new_pos,
                     hpos: getHpos(item.x, is_half_rack, is_rack_rear),
                  }, function(answer) {
                     var answer = jQuery.parseJSON(answer);

                     // revert to old position
                     if (!answer.status) {
                        dirty = true;
                        grid.move(item.el, x_before_drag, y_before_drag);
                        dirty = false;
                        displayAjaxMessageAfterRedirect();
                     } else {
                        // move other side if needed
                        var other_side_cls = $(item.el).hasClass('rear')
                           ? "front"
                           : "rear";
                        var other_side_el = $('.grid-stack-item.'+other_side_cls+'[data-gs-id='+item.id+']');

                        if (other_side_el.length) {
                           var other_side_grid = $(other_side_el).parent().data('gridstack');
                           new_x = item.x;
                           new_y = item.y;
                           if (item.width == 1) {
                              new_x = (item.x == 0 ? 1 : 0);
                           }
                           dirty = true;
                           other_side_grid.move(other_side_el, new_x, new_y);
                           dirty = false;
                        }
                     }
                  });
               });
            })
            .on('dragstart', function(event, ui) {
               var grid    = this;
               var element = $(event.target);
               var node    = element.data('_gridstack_node');

               // store position before drag
               x_before_drag = Number(node.x);
               y_before_drag = Number(node.y);

               // disable qtip
               element.qtip('hide', true);
            });

         $('#viewgraph .cell_add, #viewgraph .grid-stack-item').each(function() {
            var tipcontent = $(this).find('.tipcontent');
            if (tipcontent.length) {
               $(this).qtip({
                  position: {
                     my: 'left center',
                     at: 'right center',
                  },
                  content: {
                     text: tipcontent
                  },
                  style: {
                     classes: 'qtip-shadow qtip-bootstrap rack_tipcontent'
                  }
               });
            }
         });
      });
JAVASCRIPT;
      echo Html::scriptBlock($js);
   }

   /**
    * Display a mini stats block (wiehgt, power, etc) for the current rack instance
    * @param  Rack   $rack the current rack instance
    * @return void
    */
   static function showStats(Rack $rack) {
      global $DB;

      $items = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'racks_id' => $rack->getID()
         ]
      ]);

      $weight = 0;
      $power  = 0;
      $units  = [
         Rack::FRONT => array_fill(0, $rack->fields['number_units'], 0),
         Rack::REAR  => array_fill(0, $rack->fields['number_units'], 0),
      ];

      $rel = new self;
      while ($row = $items->next()) {
         $rel->getFromDB($row['id']);

         $item = new $row['itemtype'];
         $item->getFromDB($row['items_id']);

         $model_class = $item->getType() . 'Model';
         $modelsfield = strtolower($item->getType()) . 'models_id';
         $model = new $model_class;

         if ($model->getFromDB($item->fields[$modelsfield])) {
            $required_units = $model->fields['required_units'];

            for ($i = 0; $i < $model->fields['required_units']; $i++) {
               $units[$row['orientation']][$row['position'] + $i] = 1;
               if ($model->fields['depth'] == 1) {
                  $other_side = (int) !(bool) $row['orientation'];
                  $units[$other_side][$row['position'] + $i] = 1;
               }
            }

            $power += $model->fields['power_consumption'];
            $weight += $model->fields['weight'];
         } else {
            $units[Rack::FRONT][$row['position']] = 1;
            $units[Rack::REAR][$row['position']]  = 1;
         }
      }

      $nb_units = max(
         array_sum($units[Rack::FRONT]),
         array_sum($units[Rack::REAR])
      );

      $space_prct  = round(100 * $nb_units / max($rack->fields['number_units'], 1));
      $weight_prct = round(100 * $weight / max($rack->fields['max_weight'], 1));
      $power_prct  = round(100 * $power / max($rack->fields['max_power'], 1));

      echo "<div class='rack_stats'>";

      echo "<h3>".__("Space")."</h3>";
      Html::progressBar('rack_space', [
         'create' => true,
         'percent' => $space_prct,
         'message' => $space_prct."%",
      ]);

      echo "<h3>".__("Weight")."</h3>";
      Html::progressBar('rack_weight', [
         'create' => true,
         'percent' => $weight_prct,
         'message' => $weight." / ".$rack->fields['max_weight']
      ]);

      echo "<h3>".__("Power")."</h3>";
      Html::progressBar('rack_power', [
         'create' => true,
         'percent' => $power_prct,
         'message' => $power." / ".$rack->fields['max_power']
      ]);
      echo "</div>";
   }

   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;

      $colspan = 4;

      echo "<div class='center'>";

      $this->initForm($ID, $this->fields);
      $this->showFormHeader();

      $rack = new Rack();
      $rack->getFromDB($this->fields['racks_id']);

      $rand = mt_rand();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_itemtype$rand'>".__('Item type')."</label></td>";
      echo "<td>";

      $types = array_combine($CFG_GLPI['rackable_types'], $CFG_GLPI['rackable_types']);
      foreach ($types as $type => &$text) {
         $text = $type::getTypeName(1);
      }
      Dropdown::showFromArray(
         'itemtype',
         $types, [
            'display_emptychoice'   => true,
            'value'                 => $this->fields["itemtype"],
            'rand'                  => $rand
         ]
      );

      //get all used items
      $used = [];
      $iterator = $DB->request([
         'FROM'   => $this->getTable()
      ]);
      while ($row = $iterator->next()) {
         $used [$row['itemtype']][] = $row['items_id'];
      }

      //items part of an enclosure should not be listed
      $iterator = $DB->request([
         'FROM'   => Item_Enclosure::getTable()
      ]);
      while ($row = $iterator->next()) {
         $used[$row['itemtype']][] = $row['items_id'];
      }

      Ajax::updateItemOnSelectEvent(
         "dropdown_itemtype$rand",
         "items_id",
         $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php", [
            'idtable'   => '__VALUE__',
            'name'      => 'items_id',
            'value'     => $this->fields['items_id'],
            'rand'      => $rand,
            'used'      => $used
         ]
      );

      //TODO: update possible positions according to selected item number of units
      //TODO: update positions on rack selection
      //TODO: update hpos from item model info is_half_rack
      //TODO: update orientation according to item model depth

      echo "</td>";
      echo "<td><label for='dropdown_items_id$rand'>".__('Item')."</label></td>";
      echo "<td id='items_id'>";
      if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
         $itemtype = new $itemtype();
         $itemtype::dropdown([
            'name'   => "items_id",
            'value'  => $this->fields['items_id'],
            'rand'   => $rand
         ]);
      } else {
         Dropdown::showFromArray(
            'items_id',
            [], [
               'display_emptychoice'   => true,
               'rand'                  => $rand
            ]
         );
      }

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
            'used'   => $rack->getFilled($this->fields['itemtype'], $this->fields['items_id']),
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_orientation$rand'>".__('Orientation (front rack point of view)')."</label></td>";
      echo "<td >";
      Dropdown::showFromArray(
         'orientation', [
            Rack::FRONT => __('Front'),
            Rack::REAR  => __('Rear')
         ], [
            'value' => $this->fields["orientation"],
            'rand' => $rand
         ]
      );
      echo "</td>";
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

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_hpos$rand'>".__('Horizontal position (from rack point of view)')."</label></td>";
      echo "<td>";
      Dropdown::showFromArray(
         'hpos',
         [
            Rack::POS_NONE    => __('None'),
            Rack::POS_LEFT    => __('Left'),
            Rack::POS_RIGHT   => __('Right')
         ], [
            'value'  => $this->fields['hpos'],
            'rand'   =>$rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   function post_getEmpty() {
      $this->fields['bgcolor'] = '#69CEBA';
   }

   /**
    * Get cell content
    *
    * @param mixed $cell Rack cell (array or false)
    *
    * @return string
    */
   private static function getCell($cell) {
      if ($cell) {
         $item       = $cell['item'];
         $icon       = self::getIcon(get_class($item));
         $gs_item    = $cell['gs_item'];
         $rear       = $gs_item['rear'];
         $back_class = $rear
                         ? "rear"
                         : "front";
         $half_class = $gs_item['half_rack']
                         ? "half_rack"
                         : "";
         $bg_color   = $gs_item['bgcolor'];
         $fg_color   = Html::getInvertedColor($gs_item['bgcolor']);
         $fg_color_s = "color: $fg_color;";
         $img_class  = "";
         $img_s      = "none";
         if ($gs_item['picture_f'] && !$rear) {
            $img_s = "background: $bg_color url(\"".$gs_item['picture_f']."\")  no-repeat top left/100% 100%;";
            $img_class = 'with_picture';
         }
         if ($gs_item['picture_r'] && $rear) {
            $img_s = "background: $bg_color url(\"".$gs_item['picture_r']."\")  no-repeat top left/100% 100%;";
            $img_class = 'with_picture';
         }

         return "
         <div class='grid-stack-item $back_class $half_class $img_class'
               data-gs-width='{$gs_item['width']}' data-gs-height='{$gs_item['height']}'
               data-gs-x='{$gs_item['x']}' data-gs-y='{$gs_item['y']}'
               data-gs-id='{$gs_item['id']}'
               style='background-color: $bg_color; color: $fg_color;'>
            <div class='grid-stack-item-content' style='$fg_color_s $img_s'>
               $icon
               <a href='{$gs_item['url']}' class='itemrack_name' style='$fg_color_s'>{$gs_item['name']}</a>".
               (!$rear
                  ? "<a href='{$gs_item['rel_url']}'><i class='fa fa-link rel-link' style='$fg_color_s'></i></a>"
                  : "")."
               <span class='tipcontent'>
                  <span>
                     <label>".
                     ($rear
                        ? __("asset rear side")
                        : __("asset front side"))."
                     </label>
                  </span>
                  <span>
                     <label>".__('Type').":</label>".
                     $item::getTypeName()."
                  </span>
                  <span>
                     <label>".__('name').":</label>".
                     $item->fields['name']."
                  </span>
                  <span>
                     <label>".__('serial').":</label>".
                     $item->fields['serial']."
                  </span>
                  <span>
                     <label>".__('Inventory number').":</label>".
                     $item->fields['otherserial']."
                  </span>
                  <span>
                     <label>".__('model').":</label>".
                     (is_object($item->model)
                      && isset($item->model->fields['name'])
                        ? $item->model->fields['name']
                        : '')."
                  </span>
               </span>
            </div>
         </div>";
      }

      return false;
   }


   /**
    * Return an i html tag with a dedicated icon for the itemtype
    * @param  string $itemtype  A rackable itemtype
    * @return string           The i html tag
    */
   private static function getIcon($itemtype = "") {
      $icon = "";
      switch ($itemtype) {
         case "Computer":
            $icon = "fa-server";
            break;
         case "Monitor":
            $icon = "fa-television";
            break;
         case "NetworkEquipment":
            $icon = "fa-sitemap";
            break;
         case "Peripheral":
            $icon = "fa-usb";
            break;
         case "Enclosure":
            $icon = "fa-th";
            break;
         case "PDU":
            $icon = "fa-plug";
            break;
      }

      if (!empty($icon)) {
         $icon = "<i class='item_rack_icon fa $icon'></i>";
      }

      return $icon;
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

   /**
    * Prepares input (for update and add)
    *
    * @param array $input Input data
    *
    * @return array
    */
   private function prepareInput($input) {
      $error_detected = [];

      $itemtype = $this->fields['itemtype'];
      $items_id = $this->fields['items_id'];
      $racks_id = $this->fields['racks_id'];
      $position = $this->fields['position'];
      $hpos = $this->fields['hpos'];
      $orientation = $this->fields['orientation'];

      //check for requirements
      if ($this->isNewItem()) {
         if (!isset($input['itemtype'])) {
            $error_detected[] = __('An item type is required');
         }

         if (!isset($input['items_id'])) {
            $error_detected[] = __('An item is required');
         }

         if (!isset($input['racks_id'])) {
            $error_detected[] = __('A rack is required');
         }

         if (!isset($input['position'])) {
            $error_detected[] = __('A position is required');
         }
      }

      if (isset($input['itemtype'])) {
         $itemtype = $input['itemtype'];
      }
      if (isset($input['items_id'])) {
         $items_id = $input['items_id'];
      }
      if (isset($input['racks_id'])) {
         $racks_id = $input['racks_id'];
      }
      if (isset($input['position'])) {
         $position = $input['position'];
      }
      if (isset($input['hpos'])) {
         $hpos = $input['hpos'];
      }
      if (isset($input['orientation'])) {
         $orientation = $input['orientation'];
      }

      if (!count($error_detected)) {
         //check if required U are available at position
         $rack = new Rack();
         $rack->getFromDB($racks_id);

         $filled = $rack->getFilled($itemtype, $items_id);

         $item = new $itemtype;
         $item->getFromDB($items_id);
         $model_class = $item->getType() . 'Model';
         $modelsfield = strtolower($item->getType()) . 'models_id';
         $model = new $model_class;
         if ($model->getFromDB($item->fields[$modelsfield])) {
            $item->model = $model;
         } else {
            $item->model = null;
         }

         $required_units = 1;
         $width          = 1;
         $depth          = 1;
         if ($item->model != null) {
            if ($item->model->fields['required_units'] > 1) {
               $required_units = $item->model->fields['required_units'];
            }
            if ($item->model->fields['is_half_rack'] == 1) {
               if ($this->isNewItem() && !isset($input['hpos']) || $input['hpos'] == 0) {
                  $error_detected[] = __('You must define an horizontal position for this item');
               }
               $width = 0.5;
            }
            if ($item->model->fields['depth'] != 1) {
               if ($this->isNewItem() && !isset($input['orientation'])) {
                  $error_detected[] = __('You must define an orientation for this item');
               }
               $depth = $item->model->fields['depth'];
            }
         }

         if ($position > $rack->fields['number_units'] ||
            $position + $required_units  > $rack->fields['number_units'] + 1
         ) {
            $error_detected[] = __('Item is out of rack bounds');
         } else if (!count($error_detected)) {
            $i = 0;
            while ($i < $required_units) {
               $current_position = $position + $i;
               if (isset($filled[$current_position])) {
                  $content_filled = $filled[$current_position];

                  if ($hpos == Rack::POS_NONE || $hpos == Rack::POS_LEFT) {
                     $d = 0;
                     while ($d/4 < $depth) {
                        $pos = ($orientation == Rack::REAR) ? 3 - $d : $d;
                        $val = 1;
                        if (isset($content_filled[Rack::POS_LEFT][$pos]) && $content_filled[Rack::POS_LEFT][$pos] != 0) {
                           $error_detected[] = __('Not enough space available to place item');
                           break 2;
                        }
                        ++$d;
                     }
                  }

                  if ($hpos == Rack::POS_NONE || $hpos == Rack::POS_RIGHT) {
                     $d = 0;
                     while ($d/4 < $depth) {
                        $pos = ($orientation == Rack::REAR) ? 3 - $d : $d;
                        $val = 1;
                        if (isset($content_filled[Rack::POS_RIGHT][$pos]) && $content_filled[Rack::POS_RIGHT][$pos] != 0) {
                           $error_detected[] = __('Not enough space available to place item');
                           break 2;
                        }
                        ++$d;
                     }
                  }
               }
               ++$i;
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
}
