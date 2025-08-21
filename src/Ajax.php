<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;

use function Safe\json_encode;
use function Safe\preg_match;

/**
 * Ajax Class
 **/
class Ajax
{
    /**
     * Create modal window
     * After display it using data-bs-toggle and data-bs-target attributes
     *
     * @since 0.84
     *
     * @param string   $name    name of the js object
     * @param string   $url     URL to display in modal
     * @param array    $options Possible options:
     *     - width      (default 800)
     *     - height     (default 400)
     *     - modal      is a modal window? (default true)
     *     - container  specify a html element to render (default empty to html.body)
     *     - title      window title (default empty)
     *     - display    display or get string? (default true)
     *
     * @return void|string (see $options['display'])
     */
    public static function createModalWindow($name, $url, $options = [])
    {
        if (preg_match('/[^\w]+/', $name) === 1) {
            throw new InvalidArgumentException('Modal name is expected to be a valid javascript variable identifier.');
        }

        $param = [
            'width'           => 800,
            'height'          => 400,
            'modal'           => true,
            'modal_class'     => "modal-lg",
            'container'       => '',
            'title'           => '',
            'extraparams'     => [],
            'display'         => true,
            'js_modal_fields' => '',
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                if (isset($param[$key])) {
                    $param[$key] = $val;
                }
            }
        }

        $html = TemplateRenderer::getInstance()->render(
            'components/modal.html.twig',
            [
                'title'       => $param['title'],
                'modal_class' => $param['modal_class'],
            ]
        );

        $js = "var {$name};";
        $js .= "$(function() {";
        if (!empty($param['container'])) {
            $js .= "   var el = $('#" . jsescape(Html::cleanId($param['container'])) . "');";
            $js .= "   el.addClass('modal');";
        } else {
            $js .= "   var el = $('<div class=\"modal\"></div>');";
            $js .= "   $('body').append(el);";
        }
        $js .= "   el.html('" . jsescape($html) . "');";
        $js .= "   {$name} = new bootstrap.Modal(el.get(0), {show: false});";
        $js .= "   el.on(";
        $js .= "      'show.bs.modal',";
        $js .= "      function(evt) {";
        $js .= "         var fields = ";
        if (is_array($param['extraparams']) && count($param['extraparams'])) {
            $js .= json_encode($param['extraparams'], JSON_FORCE_OBJECT);
        } else {
            $js .= '{}';
        }
        $js .= ";";
        if (!empty($param['js_modal_fields'])) {
            $js .= $param['js_modal_fields'] . "";
        }
        $js .= "         el.find('.modal-body').load('" . jsescape($url) . "', fields);";
        $js .= "      }";
        $js .= "   );";
        $js .= "});";

        $out = Html::scriptBlock($js);

        if ($param['display']) {
            echo $out;
        } else {
            return $out;
        }
    }


    /**
     * Create modal window in Iframe
     * After display it using data-bs-toggle and data-bs-target attributes
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
    public static function createIframeModalWindow($domid, $url, $options = [])
    {

        $param = [
            'width'         => 1050,
            'height'        => 500,
            'modal'         => true,
            'title'         => '',
            'display'       => true,
            'dialog_class'  => 'modal-lg',
            'autoopen'      => false,
            'reloadonclose' => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                if (isset($param[$key])) {
                    $param[$key] = $val;
                }
            }
        }
        $url .= (strstr($url, '?') ? '&' : '?') . '_in_modal=1';

        if (isset($options['extradata'])) {
            $url .= (strstr($url, '?') ? '&' : '?') . Toolbox::append_params($options['extradata'], '&');
        }

        $rand = mt_rand();

        $domid  = Html::sanitizeDomId($domid);

        $html = '
            <div id="' . htmlescape($domid) . '" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog ' . htmlescape($param['dialog_class']) . '">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            <h3>' . htmlescape($param['title']) . '</h3>
                        </div>
                        <div class="modal-body">
                            <iframe id="iframe' . htmlescape($domid) . '" class="iframe hidden"
                                width="100%" height="400" frameborder="0">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        ';

        $domid = jsescape($domid);

        $js = '
            $(function() {
                myModalEl' . $rand . ' = document.getElementById("' . $domid . '");
                myModal' . $rand . '   = new bootstrap.Modal(myModalEl' . $rand . ');

                // move modal to body
                $(myModalEl' . $rand . ').appendTo($("body"));

                myModalEl' . $rand . '.addEventListener("show.bs.modal", function () {
                    $("#iframe' . $domid . '").attr("src", "' . jsescape($url) . '").removeClass("hidden");
                });
        ';
        if ($param['reloadonclose']) {
            $js .= '
                myModalEl' . $rand . '.addEventListener("hide.bs.modal", function () {
                    window.location.reload()
                });
            ';
        }
        if ($param['autoopen']) {
            $js .= '
                myModal' . $rand . '.show();
            ';
        }
        $js .= '
                document.getElementById("iframe' . $domid . '").onload = function() {
                    var h = ' . ((int) $param['height']) . ';
                    var w = ' . ((int) $param['width']) . ';

                    $("#iframe' . $domid . '").height(h);

                    if (w >= 700) {
                        $("#' . $domid . ' .modal-dialog").addClass("modal-xl");
                    } else if (w >= 500) {
                        $("#' . $domid . ' .modal-dialog").addClass("modal-lg");
                    } else if (w <= 300) {
                        $("#' . $domid . ' .modal-dialog").addClass("modal-sm");
                    }

                    // reajust height to content
                    myModal' . $rand . '.handleUpdate()
                };
            });
        ';

        $out = Html::scriptBlock($js) . trim($html);

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
    public static function createTabs(
        $tabdiv_id = 'tabspanel',
        $tabdivcontent_id = 'tabcontent',
        $tabs = [],
        $type = '',
        $ID = 0,
        $orientation = 'vertical',
        $options = []
    ) {
        if (count($tabs) === 0) {
            return;
        }

        $active_tab = Session::getActiveTab($type);

        // Compute tabs ids.
        $active_id = null;
        foreach ($tabs as $key => $val) {
            $id = sprintf('tab-%s-%s', str_replace('$', '_', $key), mt_rand());

            $tabs[$key]['id'] = $id;

            if ($key == $active_tab || $active_id === null) {
                $active_id = $id;
            }
        }
        $active_id = str_replace('\\', '_', $active_id);

        // Display tabs
        if (count($tabs) > 0) {
            if (count($tabs) == 1) {
                $orientation = "horizontal";
            }

            $flex_container = "flex-column flex-md-row";
            $flex_tab       = "flex-row flex-md-column d-none d-md-block";
            $border         = "border-start-0";
            $navitemml      = "ms-0";
            $navlinkp       = "pe-1";
            $nav_width      = "style='min-width: 200px'";
            if ($orientation == "horizontal") {
                $flex_container = "flex-column";
                $flex_tab       = "flex-row d-none d-md-flex";
                $border         = "";
                $navitemml      = "";
                $navlinkp       = "";
                $nav_width      = "";
            }

            if (($options['in_modal'] ?? false)) {
                $border = "border-0";
            }

            echo "<div class='d-flex card-tabs $flex_container $orientation'>";
            echo "<ul class='nav nav-tabs $flex_tab' id='" . htmlescape($tabdiv_id) . "' $nav_width role='tablist'>";
            $html_tabs = "";
            $html_sele = "";
            $i = 0;

            // Hide tabs if only one single tab on item creation form
            $display_class = "";
            if (
                is_a($type, CommonDBTM::class, true)
                && count($tabs) == 1
            ) {
                $display_class = "d-none";
            }

            foreach ($tabs as $tab_key => $val) {
                $target = str_replace('\\', '_', $val['id']);
                $tab_content_url = $val['url'] . (isset($val['params']) ? '?' . $val['params'] : '');
                $selected = $active_id == $target ? 'selected' : '';
                $title = $val['title'];
                $title_clean = strip_tags($title);

                // Compute direct link that user can reach in a new tab using
                // middle mouse click.
                // TODO: ctrl+click should have the same behavior but it seems
                // to be caught by the tabs events handler and does not trigger
                // a new browser tab.
                $direct_link_url = $_SERVER['REQUEST_URI'];
                if (count($_GET)) {
                    $direct_link_url .= count($_GET) ? '&' : '?';
                } elseif (!str_contains($direct_link_url, "?")) {
                    $direct_link_url .= "?";
                }
                $direct_link_url .= "forcetab=$tab_key";

                if ($tab_key !== -1) {
                    $html_tabs .= "
                        <li class='nav-item $navitemml'>
                            <a
                                class='nav-link justify-content-between $navlinkp $display_class'
                                data-bs-toggle='tab'
                                title='" . $title_clean . "'
                                data-glpi-ajax-content='" . htmlescape($tab_content_url) . "'
                                href='" . htmlescape($direct_link_url) . "'
                                data-bs-target='#" . htmlescape($target) . "'
                            >{$title}</a>
                        </li>
                    ";
                    $html_sele .= "<option value='$i' {$selected}>{$title}</option>";
                } else {
                    // All tabs
                    $html_tabs .= <<<HTML
                        <li class='nav-item $navitemml'>
                            <a class='nav-link justify-content-between $navlinkp $display_class' data-bs-toggle='tab'
                                title='" . $title_clean . "' href='#' data-show-all-tabs="true">{$title}</a>
                        </li>
HTML;
                    $html_sele .= "<option value='$i' {$selected}>{$title}</option>";
                }
                $i++;
            }
            echo $html_tabs;
            echo "</ul>";
            echo "<select class='form-select border-2 rounded-0 rounded-top d-md-none mb-2' id='" . htmlescape($tabdiv_id) . "-select'>$html_sele</select>";

            echo "<div class='tab-content p-2 flex-grow-1 card $border' style='min-height: 150px'>";
            foreach ($tabs as $val) {
                $id = str_replace('\\', '_', $val['id']);
                echo "<div data-glpi-tab-content class='tab-pane fade' role='tabpanel' id='" . htmlescape($id) . "'></div>";
            }
            echo  "</div>"; // .tab-content
            echo "</div>"; // .container-fluid

            $type = jsescape($type);
            $ID = jsescape($ID);
            $withtemplate = (int) ($_GET['withtemplate'] ?? 0);
            $tabdiv_id = jsescape($tabdiv_id);
            $active_id = jsescape($active_id);
            $js = <<<JS
         var url_hash = window.location.hash;
         var loadTabContents = function (tablink, force_reload = false, update_session_tab = true) {
            var url = tablink.data('glpi-ajax-content');
            var base_url = CFG_GLPI.url_base;
            if (base_url === '') {
                // If base URL is not configured, fallback to current URL domain + GLPI base dir.
                base_url = window.location.origin + '/' + CFG_GLPI.root_doc;
            }
            const href_url_params = new URL(url, base_url).searchParams;
            var target = tablink.attr('data-bs-target');

            const updateCurrentTab = () => {
                $.get(
                  CFG_GLPI.root_doc + '/ajax/updatecurrenttab.php',
                  {
                     itemtype: '$type',
                     id: '$ID',
                     tab_key: href_url_params.get('_glpi_tab'),
                     withtemplate: $withtemplate
                  }
               );
            }
            if ($(target).html() && !force_reload) {
                updateCurrentTab();
                return;
            }
            $(target).html(`<div class="d-flex justify-content-center"><span class="spinner-border spinner-border position-absolute m-5" role="status" aria-hidden="true"></span></div>`);

            $.get(url, function(data) {
               $(target).html(data);

               $(target).closest('main').trigger('glpi.tab.loaded');

               if (update_session_tab) {
                   updateCurrentTab();
               }
            }).done(function() {
                // try to restore the scroll on a specific anchor
                if (url_hash.length > 0) {
                    // as we load content by ajax, when full page was ready, the anchor was not present
                    // se we recall it to force the scroll.
                    window.location.href = url_hash;

                    // animate item with a flash
                    $(url_hash).addClass('animate__animated animate__shakeX animate__slower');

                    // unset hash (to avoid scrolling when changing tabs)
                    url_hash   = '';
                }
            }).fail(function(data) {
               $(target).html(data.responseText);
            });
         };

         var reloadTab = function (add) {
            var active_link = $('main #tabspanel .nav-item .nav-link.active');

            // Update target AJAX endpoint URL and load tab contents
            var current_url = active_link.data('glpi-ajax-content');
            active_link.data('glpi-ajax-content', current_url + '&' + add);
            loadTabContents(active_link, true);

            // Restore URL
            active_link.data('glpi-ajax-content', current_url);
         };

         var loadAllTabs = () => {
             const tabs = $('#$tabdiv_id a[data-bs-toggle=\"tab\"]');
             tabs.each((index, tab) => {
                loadTabContents($(tab));
             });
         }

         $(function() {
            // Keep track of the first load which will be the tab stored in the
            // session.
            // In this case, it is useless to send a request to the
            // updatecurrenttab endpoint as we already are on this tab
            let first_load = true;

            $('a[data-bs-toggle=\"tab\"]').on('shown.bs.tab', function(e) {
               e.preventDefault();
               if ($(this).attr('data-show-all-tabs') === 'true') {
                  loadAllTabs();
                  // show all tabs by adding active and show classes to all tabs
                  $('#$tabdiv_id').parent().find('.tab-pane').addClass('active show').removeClass('fade');
               } else {
                  // Remove active and show classes from all tabs except the one that is clicked
                  let clicked_tab = $(this).attr('data-bs-target');
                  $('#$tabdiv_id').parent().find('.tab-pane:not(' + clicked_tab + ')').removeClass('active show');
                  loadTabContents($(this), false, !first_load);
               }
            });

            // load initial tab
            $('a[data-bs-target=\"#{$active_id}\"]').tab('show');
            first_load = false;

            // select events in responsive mode
            $('#$tabdiv_id-select').on('change', function (e) {
               $('#$tabdiv_id li a').eq($(this).val()).tab('show');
            });
         });
JS;

            echo Html::scriptBlock($js);
        }
    }


    /**
     * Javascript code for update an item when another item changed
     *
     * @param string|array $toobserve    id (or array of id) of the select to observe
     * @param string       $toupdate     id of the item to update
     * @param string       $url          Url to get datas to update the item
     * @param array        $parameters   of parameters to send to ajax URL
     * @param array        $events       of the observed events (default 'change')
     * @param integer      $minsize      minimum size of data to update content (default -1)
     * @param integer      $buffertime   minimum time to wait before reload (default -1)
     * @param array        $forceloadfor of content which must force update content
     * @param boolean      $display      display or get string (default true)
     *
     * @return void|string (see $display)
     */
    public static function updateItemOnEvent(
        $toobserve,
        $toupdate,
        $url,
        $parameters = [],
        $events = ["change"],
        $minsize = -1,
        $buffertime = -1,
        $forceloadfor = [],
        $display = true
    ) {

        $js = "$(function() {";
        $js .= self::updateItemOnEventJsCode(
            $toobserve,
            $toupdate,
            $url,
            $parameters,
            $events,
            $minsize,
            $buffertime,
            $forceloadfor,
            false
        );
        $js .=  "});";

        $output = Html::scriptBlock($js);

        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }


    /**
     * Javascript code for update an item when a select item changed
     *
     * @param string|array $toobserve  id of the select to observe
     * @param string       $toupdate   id of the item to update
     * @param string       $url        Url to get datas to update the item
     * @param array        $parameters of parameters to send to ajax URL
     * @param boolean      $display    display or get string (default true)
     *
     * @return void|string (see $display)
     */
    public static function updateItemOnSelectEvent(
        $toobserve,
        $toupdate,
        $url,
        $parameters = [],
        $display = true
    ) {

        return self::updateItemOnEvent(
            $toobserve,
            $toupdate,
            $url,
            $parameters,
            ["change"],
            -1,
            -1,
            [],
            $display
        );
    }


    /**
     * Javascript code for update an item when another item changed (Javascript code only)
     *
     * @param string|array $toobserve    id (or array of id) of the select to observe
     * @param string       $toupdate     id of the item to update
     * @param string       $url          Url to get datas to update the item
     * @param array        $parameters   of parameters to send to ajax URL
     * @param array        $events       of the observed events (default 'change')
     * @param integer      $minsize      minimum size of data to update content (default -1)
     * @param integer      $buffertime   minimum time to wait before reload (default -1)
     * @param array        $forceloadfor of content which must force update content
     * @param boolean      $display      display or get string (default true)
     *
     * @return void|string (see $display)
     */
    public static function updateItemOnEventJsCode(
        $toobserve,
        $toupdate,
        $url,
        $parameters = [],
        $events = ["change"],
        $minsize = -1,
        $buffertime = -1,
        $forceloadfor = [],
        $display = true
    ) {
        if ($buffertime !== -1) {
            trigger_error('$buffertime parameter has no effect anymore.', E_USER_WARNING);
        }

        if (is_array($toobserve)) {
            $zones = $toobserve;
        } else {
            $zones = [$toobserve];
        }
        $output = '';
        foreach ($zones as $zone) {
            foreach ($events as $event) {
                if (preg_match('/[^\w]+/', $event) === 1) {
                    throw new InvalidArgumentException('Event name is expected to contain only alphanumeric chars.');
                }

                $zone_id = jsescape(Html::cleanId($zone));

                $output .= "$('#$zone_id').on('$event', function(event) {";
                $condition = '';
                if ($minsize >= 0) {
                    $condition = "$('#$zone_id').val().length >= " . ((int) $minsize);
                }
                if (count($forceloadfor)) {
                    foreach ($forceloadfor as $value) {
                        if (!empty($condition)) {
                            $condition .= " || ";
                        }
                        $condition .= "$('#$zone_id').val() == '" . jsescape($value) . "'";
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
                $output .= ");";
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
    public static function commonDropdownUpdateItem($options, $display = true)
    {

        $field     = '';

        $output    = '';
        // Old scheme
        if (
            isset($options["update_item"])
            && (is_array($options["update_item"]) || (strlen($options["update_item"]) > 0))
        ) {
            $field     = "update_item";
        }
        // New scheme
        if (
            isset($options["toupdate"])
            && (is_array($options["toupdate"]) || (strlen($options["toupdate"]) > 0))
        ) {
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

                    if (
                        isset($data["moreparams"])
                        && is_array($data["moreparams"])
                        && count($data["moreparams"])
                    ) {
                        foreach ($data["moreparams"] as $key => $val) {
                            $paramsupdate[$key] = $val;
                        }
                    }

                    $output .= self::updateItemOnSelectEvent(
                        "dropdown_" . $options["name"] . $options["rand"],
                        $data['to_update'],
                        $data['url'],
                        $paramsupdate,
                        $display
                    );
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
    public static function updateItemJsCode(
        $toupdate,
        $url,
        $parameters = [],
        $toobserve = "",
        $display = true
    ) {

        $out = sprintf('$("#%s").load("%s"', jsescape($toupdate), jsescape($url));
        if (count($parameters)) {
            $out .= ",{";
            $first = true;
            foreach ($parameters as $key => $val) {
                // prevent xss attacks
                if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*$/', $key)) {
                    continue;
                }

                if ($first) {
                    $first = false;
                } else {
                    $out .= ",";
                }

                $out .= $key . ":";
                $regs = [];
                if (is_string($val) && preg_match('/^__VALUE(\d+)__$/', $val, $regs)) {
                    $out .= sprintf('$("#%s").val()', jsescape(Html::cleanId($toobserve[$regs[1]])));
                } elseif (is_string($val) && $val === "__VALUE__") {
                    $out .= sprintf('$("#%s").val()', jsescape(Html::cleanId($toobserve)));
                } else {
                    $out .=  json_encode($val);
                }
            }
            $out .= "}";
        }
        $out .= ")";
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
    public static function updateItem($toupdate, $url, $parameters = [], $toobserve = "", $display = true)
    {

        $js = "$(function() {";
        $js .= self::updateItemJsCode($toupdate, $url, $parameters, $toobserve, false);
        $js .= "});";

        $output = Html::scriptBlock($js);

        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }
}
