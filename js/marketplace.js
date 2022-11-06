/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/* global displayAjaxMessageAfterRedirect, marketplace_total_plugin */

var current_page = 1;
var ajax_url;
var ajax_done = false;

$(document).ready(function() {
    ajax_url = CFG_GLPI.root_doc+"/ajax/marketplace.php";

    // plugin actions (install, enable, etc)
    $(document).on('click', '.marketplace .modify_plugin', function() {
        var button     = $(this);
        var buttons    = button.closest('.buttons');
        var li         = button.closest('li.plugin');
        var icon       = button.children('i');
        var installed  = button.closest('.marketplace').hasClass('installed');
        var action     = button.data('action');
        var plugin_key = li.data('key');

        icon
            .removeClass()
            .addClass('fas fa-spinner fa-spin');

        if (action === 'download_plugin'
          || action === 'update_plugin') {
            followDownloadProgress(button);
        }

        ajax_done = false;
        $.post(ajax_url, {
            'action': action,
            'key': plugin_key
        }).done(function(html) {
            ajax_done = true;

            if (html.indexOf("cleaned") !== -1 && installed) {
                li.remove();
            } else {
                html = html.replace('cleaned', '');
                buttons.html(html);
                displayAjaxMessageAfterRedirect();
                addTooltips();
            }
        });
    });

    // sort control
    $(document).on('select2:select', '.marketplace .sort-control', function() {
        filterPluginList();
    });

    // pagination
    $(document).on('click', '.marketplace .pagination li', function() {
        var li   = $(this);
        var page = li.data('page');

        if (li.hasClass('nav-disabled')
          || li.hasClass('current')
          || isNaN(page)) {
            return;
        }

        refreshPlugins(page);
    });

    // filter by tag
    $(document).on('click', '.marketplace .plugins-tags .tag', function() {
        $(".marketplace:visible .plugins-tags .tag").removeClass('active');
        $(this).addClass('active');
        filterPluginList();
    });

    // filter plugin list when something typed in search input
    var chrono;
    $(document).on('input', '.marketplace .filter-list', function() {
        clearTimeout(chrono);
        chrono = setTimeout(function() {
            filterPluginList();
        }, 500);
    });

    // force refresh of plugin list
    $(document).on('click', '.marketplace .refresh-plugin-list', function() {
        refreshPlugins(current_page, true);
    });
});

// filter current plugin list based on tag selection or input filtering
var filterPluginList = function(page, force) {
    page  = page || 1;
    force = force || false;

    var marketplace  = $('.marketplace:visible');
    var pagination   = marketplace.find('ul.pagination');
    var plugins_list = marketplace.find('ul.plugins');
    var dom_tag      = marketplace.find('.plugins-tags .tag.active');
    var tag_key      = dom_tag.length ? dom_tag.data('tag') : "";
    var filter_str   = marketplace.find('.filter-list').val();
    var sort         = 'sort-alpha-desc';

    if (marketplace.find(".sort-control").length > 0) {
        sort = marketplace.find(".sort-control").select2('data')[0].element.value;
    }

    plugins_list
        .append("<div class='loading-plugins'><i class='fas fa-spinner fa-pulse'></i></div>");
    pagination.find('li.current').removeClass('current');

    var jqxhr = $.get(ajax_url, {
        'action': 'refresh_plugin_list',
        'tab':    marketplace.data('tab'),
        'tag':    tag_key,
        'filter': filter_str,
        'force':  force ? 1 : 0,
        'page':   page,
        'sort':   sort,
    }).done(function(html) {
        plugins_list.html(html);

        if (marketplace.data('tab') === 'installed') {
            return; // 'installed' tab is not paginated
        }

        var nb_plugins = jqxhr.getResponseHeader('X-GLPI-Marketplace-Total');
        $.get(ajax_url, {
            'action': 'getPagination',
            'page':  page,
            'total': nb_plugins,
        }).done(function(html) {
            pagination.html(html);
        });
    });

    return jqxhr;
};

// refresh current list of plugins base on page
var refreshPlugins = function(page, force) {
    force = force || false;
    var icon = $('.marketplace:visible .refresh-plugin-list');

    icon
        .removeClass('fa-sync-alt')
        .addClass('fa-spinner fa-spin');

    $.when(filterPluginList(page, force)).then(function() {
        icon
            .removeClass('fa-spinner fa-spin')
            .addClass('fa-sync-alt');
        current_page = page;

        addTooltips();
    });
};

// apply qtip on all actions buttons (not already done)
var addTooltips = function() {
    $(".qtip").remove();
    $(".marketplace:visible").find("[data-action][title], .add_tooltip").qtip({
        position: {
            viewport: $(window),
            my: "center left",
            at: "center right",
            adjust: {
                x: 2,
                method: "flip"
            }
        },
        style: {
            classes: 'qtip-dark'
        },
        show: {
            solo: true, // hide all other tooltips
        },
        hide: {
            event: 'click mouseleave'
        }
    });
};


/**
 * Perform a long polling download tracking, by asking server for progression
 * and reflect it into the dom
 *
 * @param button dom element containing clicked button (should be download action)
 */
var followDownloadProgress = function(button) {
    var buttons    = button.closest('.buttons');
    var li         = button.closest('li.plugin');
    var plugin_key = li.data('key');

    var progress = $('<progress max="100" value="0"></progress>');
    buttons.html(progress);

    // we call a non-blocking loop function to send ajax request with a small delay
    function loop () {
        setTimeout(function() {
            $.get(ajax_url, {
                'action': 'get_dl_progress',
                'key': plugin_key
            }).done(function(progress_value) {
                progress.attr('value', progress_value);
                if (progress_value < 100) {
                    loop();
                } else if (!ajax_done) {
                    // set an animated icon when decompressing
                    buttons.html('<i class="fas fa-cog fa-spin"></i>');

                    // display messages from backend
                    displayAjaxMessageAfterRedirect();
                }
            });
        }, 300);
    }

    loop();
};
