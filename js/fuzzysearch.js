/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/* global hotkeys, fuzzy */

$(function() {
    var list = [];

    // prepare options for fuzzy lib
    var fuzzy_options = {
        pre: "<b>",
        post: "</b>",
        extract: function(el) {
            return el.title;
        }
    };

    // when the shortcut for fuzzy is called
    hotkeys('ctrl+alt+g, option+command+g', function() {
        trigger_fuzzy();
    });

    // when the button is clicked
    $(document).on('click', '.trigger-fuzzy', function() {
        trigger_fuzzy();
    });

    var fuzzy_started = false;
    var trigger_fuzzy = function() {
        // remove old fuzzy modal
        removeFuzzy();

        // retrieve html of fuzzy input
        $.get(CFG_GLPI.root_doc+'/ajax/fuzzysearch.php', {
            'action': 'getHtml'
        }, function(html) {
            // add modal to body and show it
            $(document.body).append(html);
            $('#fuzzysearch').modal('show');

            // retrieve current menu data
            $.getJSON(CFG_GLPI.root_doc+'/ajax/fuzzysearch.php', {
                'action': 'getList'
            }, function(data) {
                list = data;

                // start fuzzy after some time
                setTimeout(function() {
                    if ($("#fuzzysearch .results li").length == 0) {
                        startFuzzy();
                    }
                }, 100);
            });

            // focus input element
            $("#fuzzysearch input").trigger("focus");

            // don't bind key events twice
            if (fuzzy_started) {
                return;
            }
            fuzzy_started = true;

            // general key matches
            $(document).on('keyup', function(key) {
                switch (key.key) {
                    case "Escape":
                        removeFuzzy();
                        break;

                    case "ArrowUp":
                        selectPrev();
                        break;

                    case "ArrowDown":
                        selectNext();
                        break;

                    case "Enter":
                        // find url, if one selected, go for it, else try to find first element
                        var url = $("#fuzzysearch .results .active a").attr('href');
                        if (url == undefined) {
                            url = $("#fuzzysearch .results li:first a").attr('href');
                        }
                        if (url != undefined) {
                            document.location = url;
                        }
                        break;
                }
            });

            // when a key is pressed in fuzzy input, launch match
            $(document).on('keyup', "#fuzzysearch input", function(key) {
                if (key.key != "Escape"
                   && key.key != "ArrowUp"
                   && key.key != "ArrowDown"
                   && key.key != "Enter") {
                    startFuzzy();
                }
            });
        });
    };

    var startFuzzy = function() {

        // retrieve input
        var input_text = $("#fuzzysearch input").val();

        //clean old results
        $("#fuzzysearch .results").empty();

        // launch fuzzy search on this list
        var results = fuzzy.filter(input_text, list, fuzzy_options);

        // append new results
        results.map(function(el) {
            //console.log(el.string);
            $("#fuzzysearch .results")
                .append("<li class='list-group-item'><a href='"+CFG_GLPI.root_doc+el.original.url+"'>"+el.string+"</a></li>");
        });

        selectFirst();
    };

    /**
    * Clean generated Html
    */
    var removeFuzzy = function() {
        $("#fuzzysearch").remove();
    };

    /**
    * Select the first element in the results list
    */
    var selectFirst = function() {
        $("#fuzzysearch .results li:first()").addClass('active');
        scrollToSelected();
    };

    /**
    * Select the last element in the results list
    */
    var selectLast = function() {
        $("#fuzzysearch .results li:last()").addClass('active');
        scrollToSelected();
    };

    /**
    * Select the next element in the results list.
    * If no selected, select the first.
    */
    var selectNext = function() {
        if ($("#fuzzysearch .results .active").length == 0) {
            selectFirst();
        } else {
            $("#fuzzysearch .results .active:not(:last-child)")
                .removeClass('active')
                .next()
                .addClass("active");
            scrollToSelected();
        }
    };

    /**
    * Select the previous element in the results list.
    * If no selected, select the last.
    */
    var selectPrev = function() {
        if ($("#fuzzysearch .results .active").length == 0) {
            selectLast();
        } else {
            $("#fuzzysearch .results .active:not(:first-child)")
                .removeClass('active')
                .prev()
                .addClass("active");
            scrollToSelected();
        }
    };

    /**
    * Force scroll to the selected element in the results list
    */
    var scrollToSelected = function() {
        var results = $("#fuzzysearch .results");
        var selected = results.find('.active');

        if (selected.length) {
            results.scrollTop(results.scrollTop() + selected.position().top - results.height()/2 + selected.height()/2 - 25);
        }
    };
});
