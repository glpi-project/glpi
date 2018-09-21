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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Ajax Class
**/
class Ajax {

   /**
    * Create modal window
    * After display it using $name.dialog("open");
    *
    * @since 0.84
    *
    * @param string   $name    name of the js object
    * @param string   $url     URL to display in modal
    * @param string[] $options Possible options:
    *     - width      (default 800)
    *     - height     (default 400)
    *     - modal      is a modal window? (default true)
    *     - container  specify a html element to render (default empty to html.body)
    *     - title      window title (default empty)
    *     - display    display or get string? (default true)
    *
    * @return void|string (see $options['display'])
    */
   static function createModalWindow($name, $url, $options = []) {

      $param = ['width'           => 800,
                     'height'          => 400,
                     'modal'           => true,
                     'container'       => '',
                     'title'           => '',
                     'extraparams'     => [],
                     'display'         => true,
                     'js_modal_fields' => ''];

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }

      $out  = "<script type='text/javascript'>\n";
      $out .= "var $name;";
      $out .= "$(function() {";
      $out .= "$name=";
      if (!empty($param['container'])) {
         $out .= Html::jsGetElementbyID(Html::cleanId($param['container']));
      } else {
         $out .= "$('<div />')";
      }
      $out .= ".dialog({\n
         width:".$param['width'].",\n
         autoOpen: false,\n
         height:".$param['height'].",\n
         modal: ".($param['modal']?'true':'false').",\n
         title: \"".addslashes($param['title'])."\",\n
         open: function (){
            var fields = ";
      if (is_array($param['extraparams']) && count($param['extraparams'])) {
         $out .= json_encode($param['extraparams'], JSON_FORCE_OBJECT);
      } else {
         $out .= '{}';
      }
      $out .= ";\n";
      if (!empty($param['js_modal_fields'])) {
         $out .= $param['js_modal_fields']."\n";
      }
      $out .= "            $(this).load('$url', fields);
         }
      });\n";
      $out .= "});";
      $out .= "</script>\n";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }

   /**
    * Create a side slide panel
    *
    * @param string $name    name of the js object
    * @param array  $options Possible options:
    *          - title       Title to display
    *          - position    position (either left or right - defaults to right)
    *          - display     display or get string? (default true)
    *          - icon        Path to aditional icon
    *          - icon_url    Link for aditional icon
    *          - icon_txt    Alternative text and title for aditional icon_
    *
    * @return void|string (see $options['display'])
    */
   static function createSlidePanel($name, $options = []) {
      global $CFG_GLPI;

      $param = [
         'title'     => '',
         'position'  => 'right',
         'url'       => '',
         'display'   => true,
         'icon'      => false,
         'icon_url'  => false,
         'icon_txt'  => false
      ];

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }

      $out  =  "<script type='text/javascript'>\n";
      $out .= "$(function() {";
      $out .= "$('<div id=\'$name\' class=\'slidepanel on{$param['position']}\'><div class=\"header\">" .
         "<button type=\'button\' class=\'close ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close\' title=\'". __s('Close') . "\'><span class=\'ui-button-icon-primary ui-icon ui-icon-closethick\'></span><span class=\'ui-button-text\'>". __('Close') ."</span></button>";

      if ($param['icon']) {
         $icon = "<img class=\'icon\' src=\'{$CFG_GLPI['root_doc']}{$param['icon']}\' alt=\'{$param['icon_txt']}\' title=\'{$param['icon_txt']}\'/>";
         if ($param['icon_url']) {
            $out .= "<a href=\'{$param['icon_url']}\'>$icon</a>";
         } else {
            $out .= $icon;
         }
      }

      if ($param['title'] != '') {
         $out .= "<h3>{$param['title']}</h3>";
      }

      $out .= "</div><div class=\'contents\'></div></div>')
         .hide()
         .appendTo('body');\n";
      $out .= "$('#{$name} .close').on('click', function() {
         $('#$name').hide(
            'slow',
            function () {
               $(this).find('.contents').empty();
            }
         );
       });\n";
      $out .= "$('#{$name}Link').on('click', function() {
         $('#$name').show(
            'slow',
            function() {
               _load$name();
            }
         );
      });\n";
      $out .= "});";
      if ($param['url'] != null) {
         $out .= "var _load$name = function() {
            $.ajax({
               url: '{$param['url']}',
               beforeSend: function() {
                  var _loader = $('<div id=\'loadingslide\'><div class=\'loadingindicator\'>" . __s('Loading...') . "</div></div>');
                  $('#$name .contents').html(_loader);
               }
            })
            .always( function() {
               $('#loadingslide').remove();
            })
            .done(function(res) {
               $('#$name .contents').html(res);
            });
         };\n";
      }
      $out .= "</script>";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }

   /**
    * Create fixed modal window
    * After display it using $name.dialog("open");
    *
    * @since 0.84
    *
    * @param string $name    name of the js object
    * @param array  $options Possible options:
    *          - width       (default 800)
    *          - height      (default 400)
    *          - modal       is a modal window? (default true)
    *          - container   specify a html element to render (default empty to html.body)
    *          - title       window title (default empty)
    *          - display     display or get string? (default true)
    *
    * @return void|string (see $options['display'])
    */
   static function createFixedModalWindow($name, $options = []) {

      $param = ['width'     => 800,
                     'height'    => 400,
                     'modal'     => true,
                     'container' => '',
                     'title'     => '',
                     'display'   => true];

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }

      $out  =  "<script type='text/javascript'>\n";
      $out .= "$(function() {";
      $out .= "var $name=";
      if (!empty($param['container'])) {
         $out .= Html::jsGetElementbyID(Html::cleanId($param['container']));
      } else {
         $out .= "$('<div></div>')";
      }
      $out .= ".dialog({\n
         width:".$param['width'].",\n
         autoOpen: false,\n
         height:".$param['height'].",\n
         modal: ".($param['modal']?'true':'false').",\n
         title: \"".addslashes($param['title'])."\"\n
         });\n});";
      $out .= "</script>";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }

   }


   /**
    * Create modal window in Iframe
    * After display it using Html::jsGetElementbyID($domid).dialog("open");
    *
    * @since 0.85
    *
    * @param string $domid   DOM ID of the js object
    * @param string $url     URL to display in modal
    * @param array  $options Possible options:
    *          - width          (default 800)
    *          - height         (default 400)
    *          - modal          is a modal window? (default true)
    *          - title          window title (default empty)
    *          - display        display or get string? (default true)
    *          - reloadonclose  reload main page on close? (default false)
    *
    * @return void|string (see $options['display'])
    */
   static function createIframeModalWindow($domid, $url, $options = []) {

      $param = ['width'         => 1050,
                     'height'        => 500,
                     'modal'         => true,
                     'title'         => '',
                     'display'       => true,
                     'reloadonclose' => false];

      if (count($options)) {
         foreach ($options as $key => $val) {
            if (isset($param[$key])) {
               $param[$key] = $val;
            }
         }
      }
      $url .= (strstr($url, '?') ?'&' :  '?').'_in_modal=1';

      $out  = "<div id=\"$domid\">";
      $out .= "<iframe id='Iframe$domid' class='iframe hidden'></iframe></div>";

      $out .= "<script type='text/javascript'>
         $(function() {
            $('#$domid').dialog({
               modal: true,
               autoOpen: false,
               height: ".$param['height'].",
               width: ".$param['width'].",
               draggable: true,
               resizeable: true,
               open: function(ev, ui){
               $('#Iframe$domid').attr('src','$url').removeClass('hidden');},";
      if ($param['reloadonclose']) {
         $out .= "close: function(ev, ui) { window.location.reload() },";
      }

      $out.= "title: \"".addslashes($param['title'])."\"});
         });
            </script>";

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }


   /**
    *  Create Ajax Tabs apply to 'tabspanel' div. Content is displayed in 'tabcontent'
    *
    * @param string  $tabdiv_id        ID of the div containing the tabs (default 'tabspanel')
    * @param string  $tabdivcontent_id ID of the div containing the content loaded by tabs (default 'tabcontent')
    * @param array   $tabs             Tabs to create : tabs is array('key' => array('title'=> 'x',
    *                                  tabs is array('key' => array('title'=> 'x',
    *                                                                   url    => 'url_toload',
    *                                                                   params => 'url_params')...
    * @param string  $type             itemtype for active tab
    * @param integer $ID               ID of element for active tab (default 0)
    * @param string  $orientation      orientation of tabs (default vertical may also be horizontal)
    * @param array   $options          Display options
    *
    * @return void
    */
   static function createTabs(
      $tabdiv_id = 'tabspanel',
      $tabdivcontent_id = 'tabcontent',
      $tabs = [],
      $type = '',
      $ID = 0,
      $orientation = 'vertical',
      $options = []
   ) {
      global $CFG_GLPI;

      // TODO need to clean params !!
      $active_tabs = Session::getActiveTab($type);

      $mainclass = '';
      if (isset($options['main_class'])) {
         $mainclass = " {$options['main_class']}";
      }

      $rand = mt_rand();
      if (count($tabs) > 0) {
         echo "<div id='tabs$rand' class='center$mainclass $orientation'>";
         if (CommonGLPI::isLayoutWithMain()
             && !CommonGLPI::isLayoutExcludedPage()) {
            $orientation = 'horizontal';
         }
         echo "<ul>";
         $current = 0;
         $selected_tab = 0;
         foreach ($tabs as $key => $val) {
            if ($key == $active_tabs) {
               $selected_tab = $current;
            }
            echo "<li><a title=\"".
                 str_replace(["<sup class='tab_nb'>", '</sup>'], '', $val['title'])."\" ";
            echo " href='".$val['url'].(isset($val['params'])?'?'.$val['params']:'')."'>";
            // extract sup information
            // $title = '';
            // $limit = 16;
            // No title strip for horizontal menu
            $title = $val['title'];
            echo $title."</a></li>";
            $current ++;
         }
         echo "</ul>";
         echo "</div>";
         $js = "
         $(function(){
         forceReload$rand = false;
         $('#tabs$rand').tabs({
            active: $selected_tab,
            // Loading indicator
            beforeLoad: function (event, ui) {
               if ($(ui.panel).html()
                   && !forceReload$rand) {
                  event.preventDefault();
               } else {
                  forceReload$rand = false;
                  var _loader = $('<div id=\'loadingtabs\'><div class=\'loadingindicator\'>" . addslashes(__('Loading...')) . "</div></div>');
                  ui.panel.html(_loader);

                  ui.jqXHR.always(function() {
                     $('#loadingtabs').remove();
                  });

                  ui.jqXHR.fail(function(e) {
                     console.log(e);
                     if (e.statusText != 'abort') {
                        ui.panel.html(
                           '<div class=\'error\'><h3>" .
                           addslashes(__('An error occured loading contents!'))  . "</h3><p>" .
                           addslashes(__('Please check GLPI logs or contact your administrator.'))  .
                           "<br/>" . addslashes(__('or')) . " <a href=\'#\' onclick=\'return reloadTab()\'>" . addslashes(__('try to reload'))  . "</a></p></div>'
                        );
                     }
                  });
               }

               var newIndex = ui.tab.parent().children().index(ui.tab);
               $.get('".$CFG_GLPI['root_doc']."/ajax/updatecurrenttab.php',
                  { itemtype: '$type', id: '$ID', tab: newIndex });
            },
            ajaxOptions: {type: 'POST'}
         });";

         if ($orientation=='vertical') {
            $js .=  "$('#tabs$rand').tabs().addClass( 'ui-tabs-vertical ui-helper-clearfix' );";
         }

         if (CommonGLPI::isLayoutWithMain()
             && !CommonGLPI::isLayoutExcludedPage()) {
            $js .=  "$('#tabs$rand').scrollabletabs();";
         } else {
            $js .=  "$('#tabs$rand').removeClass( 'ui-corner-top' ).addClass( 'ui-corner-left' );";
         }
         $js .= '});';

         $js .=  "// force reload global function
            function reloadTab(add) {
               forceReload$rand = true;
               var current_index = $('#tabs$rand').tabs('option','active');
               // Save tab
               var currenthref = $('#tabs$rand ul>li a').eq(current_index).attr('href');
               $('#tabs$rand ul>li a').eq(current_index).attr('href',currenthref+'&'+add);
               $('#tabs$rand').tabs( 'load' , current_index);
               // Restore tab
               $('#tabs$rand ul>li a').eq(current_index).attr('href',currenthref);
            };";

         echo Html::scriptBlock($js);
      }
   }


   /**
    * Javascript code for update an item when another item changed
    *
    * @param string  $toobserve    id (or array of id) of the select to observe
    * @param string  $toupdate     id of the item to update
    * @param string  $url          Url to get datas to update the item
    * @param array   $parameters   of parameters to send to ajax URL
    * @param array   $events       of the observed events (default 'change')
    * @param integer $minsize      minimum size of data to update content (default -1)
    * @param integer $buffertime   minimum time to wait before reload (default -1)
    * @param array   $forceloadfor of content which must force update content
    * @param boolean $display      display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function updateItemOnEvent($toobserve, $toupdate, $url, $parameters = [],
                                     $events = ["change"], $minsize = -1, $buffertime = -1,
                                     $forceloadfor = [], $display = true) {

      $output  = "<script type='text/javascript'>";
      $output .= "$(function() {";
      $output .= self::updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters, $events,
                                               $minsize, $buffertime, $forceloadfor, false);
      $output .=  "});</script>";
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Javascript code for update an item when a select item changed
    *
    * @param string  $toobserve  id of the select to observe
    * @param string  $toupdate   id of the item to update
    * @param string  $url        Url to get datas to update the item
    * @param array   $parameters of parameters to send to ajax URL
    * @param boolean $display    display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function updateItemOnSelectEvent($toobserve, $toupdate, $url, $parameters = [],
                                           $display = true) {

      return self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters, ["change"],
                                     -1, -1, [], $display);
   }


   /**
    * Javascript code for update an item when a Input text item changed
    *
    * @param string  $toobserve    id of the Input text to observe
    * @param string  $toupdate     id of the item to update
    * @param string  $url          Url to get datas to update the item
    * @param array   $parameters   of parameters to send to ajax URL
    * @param integer $minsize      minimum size of data to update content (default -1)
    * @param integer $buffertime   minimum time to wait before reload (default -1)
    * @param array   $forceloadfor of content which must force update content
    * @param boolean $display      display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function updateItemOnInputTextEvent($toobserve, $toupdate, $url, $parameters = [],
                                              $minsize = -1, $buffertime = -1, $forceloadfor = [],
                                              $display = true) {

      if (count($forceloadfor) == 0) {
         $forceloadfor = ['*'];
      }
      // Need to define min size for text search
      if ($minsize < 0) {
         $minsize = 0;
      }
      if ($buffertime < 0) {
         $buffertime = 0;
      }
      return self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters,
                                     ["dblclick", "keyup"], $minsize, $buffertime,
                                     $forceloadfor, $display);
   }


   /**
    * Javascript code for update an item when another item changed (Javascript code only)
    *
    * @param string  $toobserve    id (or array of id) of the select to observe
    * @param string  $toupdate     id of the item to update
    * @param string  $url          Url to get datas to update the item
    * @param array   $parameters   of parameters to send to ajax URL
    * @param array   $events       of the observed events (default 'change')
    * @param integer $minsize      minimum size of data to update content (default -1)
    * @param integer $buffertime   minimum time to wait before reload (default -1)
    * @param array   $forceloadfor of content which must force update content
    * @param boolean $display      display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters = [],
                                           $events = ["change"], $minsize = -1, $buffertime = -1,
                                           $forceloadfor = [], $display = true) {

      if (is_array($toobserve)) {
         $zones = $toobserve;
      } else {
         $zones = [$toobserve];
      }
      $output = '';
      foreach ($zones as $zone) {
         foreach ($events as $event) {
            if ($buffertime > 0) {
               $output .= "var last$zone$event = 0;";
            }
            $output .= Html::jsGetElementbyID(Html::cleanId($zone)).".on(
               '$event',
               function(event) {";
            // TODO manage buffer time !!?
            // if ($buffertime > 0) {
            //    $output.= "var elapsed = new Date().getTime() - last$zone$event;
            //          last$zone$event = new Date().getTime();
            //          if (elapsed < $buffertime) {
            //             return;
            //          }";
            // }

            $condition = '';
            if ($minsize >= 0) {
               $condition = Html::jsGetElementbyID(Html::cleanId($zone)).".val().length >= $minsize ";
            }
            if (count($forceloadfor)) {
               foreach ($forceloadfor as $value) {
                  if (!empty($condition)) {
                     $condition .= " || ";
                  }
                  $condition .= Html::jsGetElementbyID(Html::cleanId($zone)).".val() == '$value'";
               }
            }
            if (!empty($condition)) {
               $output .= "if ($condition) {";
            }
            $output .= self::updateItemJsCode($toupdate, $url, $parameters, $toobserve, false);
            if (!empty($condition)) {
               $output .= "}";
            }
            $output .=  "}";
            $output .=");\n";
         }
      }
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Javascript code for update an item (Javascript code only)
    *
    * @param array   $options Options :
    *  - toupdate : array / Update a specific item on select change on dropdown
    *               (need value_fieldname, to_update,
    *                url (@see Ajax::updateItemOnSelectEvent for information)
    *                and may have moreparams)
    * @param boolean $display display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function commonDropdownUpdateItem($options, $display = true) {

      $field     = '';

      $output    = '';
      // Old scheme
      if (isset($options["update_item"])
          && (is_array($options["update_item"]) || (strlen($options["update_item"]) > 0))) {
         $field     = "update_item";
      }
      // New scheme
      if (isset($options["toupdate"])
          && (is_array($options["toupdate"]) || (strlen($options["toupdate"]) > 0))) {
         $field     = "toupdate";
      }

      if (!empty($field)) {
         $datas = $options[$field];
         if (is_array($datas) && count($datas)) {
            // Put it in array
            if (isset($datas['to_update'])) {
               $datas = [$datas];
            }
            foreach ($datas as $data) {
               $paramsupdate = [];
               if (isset($data['value_fieldname'])) {
                  $paramsupdate = [$data['value_fieldname'] => '__VALUE__'];
               }

               if (isset($data["moreparams"])
                   && is_array($data["moreparams"])
                   && count($data["moreparams"])) {

                  foreach ($data["moreparams"] as $key => $val) {
                     $paramsupdate[$key] = $val;
                  }
               }

               $output .= self::updateItemOnSelectEvent("dropdown_".$options["name"].$options["rand"],
                                                        $data['to_update'], $data['url'],
                                                        $paramsupdate, $display);
            }
         }
      }
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Javascript code for update an item (Javascript code only)
    *
    * @param string       $toupdate   id of the item to update
    * @param string       $url        Url to get datas to update the item
    * @param array        $parameters of parameters to send to ajax URL
    * @param string|array $toobserve  id of another item used to get value in case of __VALUE__ used or array    of id to get value in case of __VALUE#__ used (default '')
    *                               or
    *                      array    of id to get value in case of __VALUE#__ used (default '')
    * @param boolean      $display    display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function updateItemJsCode($toupdate, $url, $parameters = [], $toobserve = "",
                                    $display = true) {

      $out = Html::jsGetElementbyID($toupdate).".load('$url'\n";
      if (count($parameters)) {
         $out .= ",{";
         $first = true;
         foreach ($parameters as $key => $val) {
            if ($first) {
               $first = false;
            } else {
               $out .= ",";
            }

            $out .= $key.":";
            $regs = [];
            if (!is_array($val) && preg_match('/^__VALUE(\d+)__$/', $val, $regs)) {
               $out .=  Html::jsGetElementbyID(Html::cleanId($toobserve[$regs[1]])).".val()";

            } else if (!is_array($val) && $val==="__VALUE__") {
               $out .=  Html::jsGetElementbyID(Html::cleanId($toobserve)).".val()";

            } else {
               $out .=  json_encode($val);
            }
         }
         $out .= "}\n";

      }
      $out.= ")\n";
      if ($display) {
         echo $out;
      } else {
         return $out;
      }
   }

   /**
    * Javascript code for update an item
    *
    * @param string  $toupdate   id of the item to update
    * @param string  $url        Url to get datas to update the item
    * @param array   $parameters of parameters to send to ajax URL
    * @param string  $toobserve  id of another item used to get value in case of __VALUE__ used
    *                               (default '')
    * @param boolean $display    display or get string (default true)
    *
    * @return void|string (see $display)
    */
   static function updateItem($toupdate, $url, $parameters = [], $toobserve = "", $display = true) {

      $output  = "<script type='text/javascript'>";
      $output .= "$(function() {";
      $output .= self::updateItemJsCode($toupdate, $url, $parameters, $toobserve, false);
      $output .= "});</script>";
      if ($display) {
         echo $output;
      } else {
         return $output;
      }
   }

}
