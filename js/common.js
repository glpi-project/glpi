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

/* eslint no-var: 0 */
/* eslint prefer-arrow-callback: 0 */
/* eslint prefer-template: 0 */

/* global bootstrap */
/* global L */
/* global fuzzy */
/* global glpi_html_dialog */
/* global glpi_toast_info, glpi_toast_warning, glpi_toast_error */
/* global _ */

var timeoutglobalvar;

// Store configuration of tinymce editors
// This is needed if an editor need to be destroyed and recreated as tinymce
// api does not provide any method to get the current configuration
var tinymce_editor_configs = {};

// Store select2 configurations
// This is needed if a select2 need to be destroyed and recreated as select2
// api does not provide any method to get the current configuration
var select2_configs = {};

/**
 * modifier la propriete display d'un element
 *
 * @param objet
 * @param statut
**/
function setdisplay(objet, statut) {

    var e = objet;
    if (e.style.display != statut) {
        e.style.display = statut;
    }
    return true;
}


/**
 * @param id
**/
function cleandisplay(id) {

    var e = document.getElementById(id);
    if (e) {
        setdisplay(e,'block');
    }
}


/**
 * @param id
**/
function cleanhide(id) {

    var e = document.getElementById(id);
    if (e) {
        setdisplay(e,'none');
    }
}


/**
 * @param Type
 * @param Id
**/
function fillidfield(Type, Id) {
    window.opener.document.forms.helpdeskform.elements.items_id.value = Id;
    window.opener.document.forms.helpdeskform.elements.itemtype.value = Type;
    window.close();
}

/**
 * marks all checkboxes inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container_id    DOM element
**/
function markCheckboxes(container_id) {

    var checkboxes = document.getElementById(container_id).getElementsByTagName('input');
    for (var j = 0; j < checkboxes.length; j++) {
        var checkbox = checkboxes[j];
        if (checkbox && checkbox.type == 'checkbox') {
            if (checkbox.disabled === false ) {
                checkbox.checked = true;
            }
        }
    }
    return true;
}


/**
 * marks all checkboxes inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container_id    DOM element
**/
function unMarkCheckboxes(container_id) {

    var checkboxes = document.getElementById(container_id).getElementsByTagName('input');
    for (var j = 0; j < checkboxes.length; j++) {
        var checkbox = checkboxes[j];
        if (checkbox && checkbox.type == 'checkbox') {
            checkbox.checked = false;
        }
    }
    return true;
}


/**
 * display "other" text input field in case of selecting "other" option
 *
 * @since 0.84
 *
 * @param    select_object     DOM select object
 * @param    other_option_name the name of both the option and the text input field
**/
function displayOtherSelectOptions(select_object, other_option_name) {
    if (select_object.options[select_object.selectedIndex].value == other_option_name) {
        document.getElementById(other_option_name).style.display = "inline";
    } else {
        document.getElementById(other_option_name).style.display = "none";
    }
    return true;
}





/**
 * Check all checkboxes inside the given element as the same state as a reference one (toggle this one before)
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param {HTMLElement} reference
 * @param {string} container_id
**/
function checkAsCheckboxes(reference, container_id, checkboxes_selector = 'input[type="checkbox"]') {
    reference = typeof(reference) === 'string' ? document.getElementById(reference) : reference;
    $('#' + CSS.escape(container_id) + ' ' + checkboxes_selector + ':enabled')
        .prop('checked', $(reference).is(':checked'));

    return true;
}

/**
 * Permit to use Shift key on a group of checkboxes
 * Usage: $form.find('input[type="checkbox"]').shiftSelectable();
 */
$.fn.shiftSelectable = function() {
    var lastChecked;
    var $boxes = this;

    // prevent html selection
    document.onkeydown = function(e) {
        var keyPressed = e.keyCode;
        if (keyPressed == 16) { // shift key
            $('html').addClass('user-select-none');
            document.onkeyup = function() {
                $('html').removeClass('user-select-none');
            };
        }
    };

    $($boxes).parent().click(function(evt) {
        var selected_checkbox = $(this).children('input[type=checkbox]');

        if (!lastChecked) {
            lastChecked = selected_checkbox;
            return;
        }

        if (evt.shiftKey) {
            var start = $boxes.index(selected_checkbox);
            var end = $boxes.index(lastChecked);
            $boxes.slice(Math.min(start, end), Math.max(start, end))
                .prop('checked', $(lastChecked).is(':checked'))
                .trigger('change');
        }

        lastChecked = selected_checkbox;
    });
};


/**
 * safe function to hide an element with a specified id
 *
 * @param id               id of the dive
 * @param img_name         name attribut of the img item
 * @param img_src_close    url of the close img
 * @param img_src_open     url of the open img
**/
function showHideDiv(id, img_name = '', img_src_close = '', img_src_open = '') {
    var _elt = $('#' + id);

    if (img_name !== '') {
        var _awesome = img_src_close.match(/^fa-/);
        var _deco;
        var _img;
        if (!_awesome) {
            _img = $('img[name=' + CSS.escape(img_name) + ']');
            if (_elt.is(':visible')) {
                _img.attr('src', img_src_close);
            } else {
                _img.attr('src', img_src_open);
            }
        } else {
            _deco = $('#' + CSS.escape(img_name));
            if (_elt.is(':visible')) {
                _deco
                    .removeClass(img_src_open)
                    .addClass(img_src_close);
            } else {
                _deco
                    .removeClass(img_src_close)
                    .addClass(img_src_open);
            }
        }
    }

    if (_elt.is(':visible')) {
        _elt.hide();
    } else {
        _elt.show();
    }
}


/**
 * safe function to hide an element with a specified id
 *
 * @param id
 * @param img_name
 * @param img_src_yes
 * @param img_src_no
**/
function toogle(id, img_name, img_src_yes, img_src_no) {

    if (document.getElementById) { // DOM3 = IE5, NS6
        if (document.getElementById(id).value == '0') {
            document.getElementById(id).value = '1';
            if (img_name !== '') {
                document[img_name].src=img_src_yes;
            }
        } else {
            document.getElementById(id).value = '0';
            if (img_name !== '') {
                document[img_name].src=img_src_no;
            }
        }
    }
}


/**
 * @since 0.84
 *
 * @param tbl
 * @param img_name
 * @param img_src_close
 * @param img_src_open
 */
function toggleTableDisplay(tbl, img_name, img_src_close, img_src_open) {

    var tblRows = document.getElementById(tbl).rows;
    for (var i=0; i < tblRows.length; i++) {
        if (tblRows[i].className.indexOf("headerRow") == -1) {
            if (tblRows[i].style.display == 'none') {
                tblRows[i].style.display = "table-row";
                if (img_name !== '') {
                    document[img_name].src = img_src_open;
                }

            } else {
                tblRows[i].style.display = "none";
                if (img_name !== '') {
                    document[img_name].src = img_src_close;
                }
            }
        }
    }
    if (document.getElementById(tbl+'2')) {
        toggleTableDisplay(tbl+'2','');
    }
    if (document.getElementById(tbl+'3')) {
        toggleTableDisplay(tbl+'3','');
    }
    if (document.getElementById(tbl+'4')) {
        toggleTableDisplay(tbl+'4','');
    }
    if (document.getElementById(tbl+'5')) {
        toggleTableDisplay(tbl+'5','');
    }
}


/**
 * @since 0.84
 *
 * @param target
 * @param fields
**/
function submitGetLink(target, fields) {

    var myForm    = document.createElement("form");
    myForm.method = "post";
    myForm.action = target;
    for (var name in fields) {
        var myInput = document.createElement("input");
        myInput.setAttribute("name", name);
        myInput.setAttribute("value", fields[name]);
        myForm.appendChild(myInput);
    }
    document.body.appendChild(myForm);
    myForm.submit();
    document.body.removeChild(myForm);
}


/**
 * @since 0.85
 *
 * @param id
**/
function selectAll(id) {
    var element = $('#'+CSS.escape(id));
    var selected = [];
    element.find('option').each(function(i,e){
        selected[selected.length]=$(e).attr('value');
    });
    element.val(selected);
    element.trigger('change');
}

/**
 * @since 0.85
 *
 * @param id
**/
function deselectAll(id) {
    $('#' + CSS.escape(id)).val('').trigger('change');
}


/**
 * Set all the checkbox that refere to the criterion
 *
 * @since 0.85
 *
 * @param criterion jquery criterion
 * @param reference the new reference object, boolean, id ... (default toggle)
 *
**/
function massiveUpdateCheckbox(criterion, reference) {
    var value = null;
    if (typeof(reference) == 'boolean') {
        value = reference;
    } else if (typeof(reference) == 'string') {
        value = $('#' + CSS.escape(reference)).prop('checked');
    } else if (typeof(reference) == 'object') {
        value = $(reference).prop('checked');
    }
    if (typeof(value) == 'undefined') {
        return false;
    }
    $(criterion).each(function() {
        if (typeof(reference) == 'undefined') {
            value = !$(this).prop('checked');
        }
        $(this).prop('checked', value);
    });
    return true;
}

/**
 * Timeline for itiobjects
 */
var filter_timeline = function() {
    $(document).on("click", '.filter_timeline li a', function(event) {
        event.preventDefault();
        var _this = $(this);
        //hide all elements in timeline
        $('.h_item').addClass('h_hidden');

        //reset all elements
        if (_this.data('type') == 'reset') {
            $('.filter_timeline li a').removeClass('h_active');
            $('.h_item').removeClass('h_hidden');
            return;
        }

        //activate clicked element
        _this.toggleClass('h_active');

        //find active classname
        var active_classnames = [];
        $('.filter_timeline .h_active').each(function() {
            active_classnames.push(".h_content."+$(this).data('type'));
        });

        $(active_classnames.join(', ')).each(function(){
            $(this).parent().removeClass('h_hidden');
        });

        //show all items when no active filter
        if (active_classnames.length === 0) {
            $('.h_item').removeClass('h_hidden');
        }
    });
};


var read_more = function() {
    $(document).on("click", ".long_text .read_more a, .long_text .read_more .read_more_button", function() {
        $(this).parents('.long_text').removeClass('long_text');
        $(this).parent('.read_more').remove();
        return false;
    });
};

// Responsive header
if ($(window).width() <= 700) {
    var didScroll;
    var lastScrollTop = 0;
    var delta = 5;
    var navbarHeight = $('header').outerHeight();

    $(window).scroll(function() {
        didScroll = true;
    });

    setInterval(function() {
        if (didScroll) {
            scollHeaderResponsive();
            didScroll = false;
        }
    }, 250);

    var scollHeaderResponsive = function() {
        var st = $(this).scrollTop();

        // Make sure they scroll more than delta
        if (Math.abs(lastScrollTop - st) <= delta) {
            return;
        }

        if (st > lastScrollTop && st > navbarHeight) {
            // Scroll Down
            $('#header').removeClass('nav-down').addClass('nav-up');
        } else {
            // Scroll Up
            if (st + $(window).height() < $(document).height()) {
                $('#header').removeClass('nav-up').addClass('nav-down');
            }
        }
        lastScrollTop = st;
    };
}

var switchFoldMenu = function() {
    $.ajax({
        url: CFG_GLPI.root_doc + '/ajax/switchfoldmenu.php',
        type: 'POST',
        datatype: "json",
        success: function(data) {
            if (data.success === true) {
                $('body').toggleClass('navbar-collapsed');

                var collapsed = $('body').hasClass('navbar-collapsed');

                $('#navbar-menu li.dropdown').toggleClass('dropend');
                $('#navbar-menu .dropdown-menu.animate__animated').toggleClass('animate__animated');

                if (collapsed) {
                    $('#navbar-menu .dropdown-menu, #navbar-menu .nav-link').removeClass('show');
                } else {
                    if ($("#navbar-menu .nav-link.show").length == 0)  {
                        $('#navbar-menu .nav-link.active + .dropdown-menu').addClass('show');
                    }
                }
            }
        }
    });
};

$(function() {
    $("body").delegate('td','mouseover mouseleave', function(e) {
        var col = $(this).closest('tr').children().index($(this));
        var tr = $(this).closest('tr');
        if (!$(this).closest('tr').hasClass('noHover')) {
            if (e.type == 'mouseover') {
                tr.addClass("rowHover");
                // If rowspan
                if (tr.has('td[rowspan]').length === 0) {

                    tr.prevAll('tr:has(td[rowspan]):first').find('td[rowspan]').addClass("rowHover");
                }

                $(this).closest('table').find('tr:not(.noHover) th:nth-child('+(col+1)+')').addClass("headHover");
            } else {
                tr.removeClass("rowHover");
                // remove rowspan
                tr.removeClass("rowHover").prevAll('tr:has(td[rowspan]):first').find('td[rowspan]').removeClass("rowHover");
                $(this).closest('table').find('tr:not(.noHover) th:nth-child('+(col+1)+')').removeClass("headHover");
            }
        }
    });

    $('.reduce-menu').on('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        switchFoldMenu();
    });

    // ctrl+enter in form textareas (without tinymce)
    $(document).on('keydown', '#page form textarea', function(event) {
        if (event.ctrlKey
          && event.keyCode == 13) {
            submitparentForm($(this));
        }
    });

    // toggle debug panel
    $(document).on('click', '.see_debug', function() {
        $('body > .debug-panel').toggle();
    });
});

/**
 * Trigger submit event for a parent form of passed input dom element
 *
 * @param  Object input the dom or jquery object of input
 * @return bool
 */
var submitparentForm = function(input) {
    // find parent form
    var form = $(input).closest('form');

    // find submit button(s)
    var submit = form.find('[type=submit]').filter('[name=add], [name=update]');

    // trigger if only one submit button
    if (submit.length == 1) {
        return (submit.trigger('click') !== false);
    }

    return false;
};

/**
* Determines if data from drop is an image.
*
* @param      {Blob}   file    The file
* @return     {boolean}  True if image, False otherwise.
*/
var isImage = function(file) {
    var validimagetypes = ["image/gif", "image/jpeg","image/jpg", "image/png"];

    if ($.inArray(file.type, validimagetypes) < 0) {
        return false;
    } else {
        return true;
    }
};

/**
 * Return a png url reprensenting an extension
 *
 * @param  {String} ext the extension
 * @return {string}   an image html tag
 */
var getExtIcon = function(ext) {
    var url = CFG_GLPI.root_doc+'/pics/icones/'+ext+'-dist.png';
    if (!urlExists(url)) {
        url = CFG_GLPI.root_doc+'/pics/icones/defaut-dist.png';
    }

    return '<img src="' + _.escape(url) + '" title="' + _.escape(ext) + '">';
};

/**
 * Check for existence of an url
 *
 * @param  {String} url
 * @return {Bool}
 */
var urlExists = function(url) {
    var exist = false;

    $.ajax({
        'type':    'HEAD',
        'url':     url,
        'async':   false,
        'success': function() {
            exist = true;
        }
    });

    return exist;
};

/**
 * Format a size to the last possible unit (o, Kio, Mio, etc)
 *
 * @param  {integer} size
 * @return {string}  The formated size
 */
var getSize = function (size) {
    var bytes = [
        _x('size', 'B'),
        _x('size', 'KiB'),
        _x('size', 'MiB'),
        _x('size', 'GiB'),
        _x('size', 'TiB'),
        _x('size', 'PiB'),
        _x('size', 'EiB'),
        _x('size', 'ZiB'),
        _x('size', 'YiB'),
    ];
    var lastval = '';
    bytes.some(function(val) {
        if (size > 1024) {
            size = size / 1024;
        } else {
            lastval = val;
            return true;
        }
    });

    return Math.round(size * 100, 2) / 100 + lastval;
};

/**
 * Convert a integer index into an excel like alpha index (A, B, ..., AA, AB, ...)
 * @since  9.3
 * @param  integer index    the numeric index
 * @return string           excel like string index
 */
var getBijectiveIndex = function(index) {
    var bij_str = "";
    while (parseInt(index) > 0) {
        index--;
        bij_str = String.fromCharCode("A".charCodeAt(0) + ( index % 26)) + bij_str;
        index /= 26;
    }
    return bij_str;
};

/**
 * Stop propagation and navigation default for the specified event
 */
var stopEvent = function(event) {
    event.preventDefault();
    event.stopPropagation();
};

$(() => {
    /**
     * Back to top implementation
     */
    if ($('#backtotop').length) {
        var scrollTrigger = 100, // px
            backToTop = function () {
                var scrollTop = $(window).scrollTop();
                if (scrollTop > scrollTrigger) {
                    $('#backtotop').addClass('d-md-block');
                } else {
                    $('#backtotop').removeClass('d-md-block');
                }
            };
        backToTop();
        $(window).on('scroll', function () {
            backToTop();
        });
        $('#backtotop').on('click', function (e) {
            e.preventDefault();
            $('html,body').animate({
                scrollTop: 0
            }, 700);
        });
    }
});

/**
 * Returns element height, including margins
*/
function _eltRealSize(_elt) {
    var _s = 0;
    _s += _elt.outerHeight();
    _s += parseFloat(_elt.css('margin-top').replace('px', ''));
    _s += parseFloat(_elt.css('margin-bottom').replace('px', ''));
    _s += parseFloat(_elt.css('padding-top').replace('px', ''));
    _s += parseFloat(_elt.css('padding-bottom').replace('px', ''));
    return _s;
}

var initMap = function(parent_elt, map_id, height, initial_view = {position: [0, 0], zoom: 1}) {
    // default parameters
    map_id = (typeof map_id !== 'undefined') ? map_id : 'map';
    height = (typeof height !== 'undefined') ? height : '200px';

    if (height == 'full') {
        var viewport_height = $(window).height();
        var map_position    = $(parent_elt).offset()['top'] + $(parent_elt).outerHeight();
        var newHeight = Math.floor(
            viewport_height
         - map_position
         - 2 // small margin to display the border at the bottom
        );
        var minHeight = 300;
        if (newHeight < minHeight) {
            newHeight = minHeight;
        }
        height = newHeight + 'px';
    }

    //add map, set a default arbitrary location
    parent_elt.append($('<div id="'+_.escape(map_id)+'" style="height: ' + _.escape(height) + '"></div>'));
    var map = L.map(map_id, {fullscreenControl: true, minZoom: 2}).setView(initial_view.position, initial_view.zoom);

    //setup tiles and © messages
    L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href=\'https://osm.org/copyright\'>OpenStreetMap</a> contributors',
    }).addTo(map);
    return map;
};

var showMapForLocation = function(elt) {
    var _id = $(elt).data('fid');
    var _items_id = $('#' + CSS.escape(_id)).val();

    if (_items_id == 0) {
        return;
    }

    glpi_html_dialog({
        title: __("Display on map"),
        body: "<div id='location_map_dialog'/>",
        dialogclass: "modal-xl",
        show: function() {
            //add map, set a default arbitrary location
            var map_elt = initMap($('#location_map_dialog'), 'location_map', '500px');
            map_elt.spin(true);

            $.ajax({
                dataType: 'json',
                method: 'POST',
                url: CFG_GLPI.root_doc + '/ajax/getMapPoint.php',
                data: {
                    itemtype: 'Location',
                    items_id: $('#' + CSS.escape(_id)).val()
                }
            }).done(function(data) {
                if (data.success === false) {
                    glpi_html_dialog({
                        body: data.message
                    });
                } else {
                    var _markers = [];
                    var _marker = L.marker([data.lat, data.lng]);
                    _markers.push(_marker);

                    var _group = L.featureGroup(_markers).addTo(map_elt);
                    map_elt.fitBounds(
                        _group.getBounds(), {
                            padding: [50, 50],
                            maxZoom: 10
                        }
                    );
                }
            }).always(function() {
            //hide spinner
                map_elt.spin(false);
            });
        }
    });
};

var query = {};
function markMatch (text, term) {
    // Find where the match is
    var match = text.toUpperCase().indexOf(term.toUpperCase());

    var _result = $('<span></span>');

    // If there is no match, move on
    if (match < 0) {
        _result.append(_.escape(text));
        return _result.html();
    }

    // Put in whatever text is before the match
    _result.html(_.escape(text.substring(0, match)));

    // Mark the match
    var _match = $('<span class=\'select2-rendered__match\'></span>');
    _match.html(_.escape(text.substring(match, match + term.length)));

    // Append the matching text
    _result.append(_match);

    // Put in whatever is after the match
    _result.append(_.escape(text.substring(match + term.length)));

    return _result.html();
}

/**
 * Function that renders select2 results.
 */
var templateResult = function(result) {
    var _elt = $('<span></span>');
    _elt.attr('title', result.title);

    if (typeof query.term !== 'undefined' && typeof result.rendered_text !== 'undefined') {
        _elt.html(result.rendered_text); // rendered_text is expected to be a safe HTML string
    } else {
        if (!result.text) {
            return null;
        }

        var text = result.text;
        if (!result.id) {
            // If result has no id, then it is used as an optgroup and is not used for matches
            _elt.html(_.escape(text));
            return _elt;
        }

        var _term = query.term || '';
        var markup = markMatch(text, _term);

        if (result.level) {
            var a='';
            var i=result.level;
            while (i>1) {
                a = a+'&nbsp;&nbsp;&nbsp;';
                i=i-1;
            }
            _elt.html(a+'&raquo;'+markup);
        } else {
            _elt.html(markup);
        }
    }

    return _elt;
};

// delay function who reinit timer on each call
var typewatch = (function(){
    var timer = 0;
    return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
    };
})();

/**
 * Function that renders select2 selections.
 */
var templateSelection = function (selection) {
    var text = '';
    if (!("element" in selection)) {
        text = selection.text;
    } else if (Object.prototype.hasOwnProperty.call(selection, 'selection_text')) {
        // Data generated by ajax containing 'selection_text'
        text = selection.selection_text;
    } else if (selection.element.parentElement.nodeName == 'OPTGROUP') {
        // Data generated with optgroups
        text = selection.element.parentElement.getAttribute('label') + ' - ' + selection.text;
    } else {
        // Default text
        text = selection.text;
    }
    var _elt = $('<span></span>');
    _elt.html(_.escape(text));
    return _elt;
};

var templateItilStatus = function(option) {
    if (option === false) {
        // Option is false when element does not match searched terms
        return null;
    }
    var status = option.id || 0;

    var classes = "";
    switch (parseInt(status)) {
        case 1 :
            classes = 'new ti ti-circle-filled';
            break;
        case 2 :
            classes = 'assigned ti ti-circle';
            break;
        case 3 :
            classes = 'planned ti ti-calendar';
            break;
        case 4 :
            classes = 'waiting ti ti-circle-filled';
            break;
        case 5 :
            classes = 'solved ti ti-circle';
            break;
        case 6 :
            classes = 'closed ti ti-circle-filled';
            break;
        case 7:
            classes = 'accepted ti ti-circle-check-filled';
            break;
        case 8 :
            classes = 'observe ti ti-eye';
            break;
        case 9 :
            classes = 'eval ti ti-circle';
            break;
        case 10 :
            classes = 'approval ti ti-help-circle';
            break;
        case 11 :
            classes = 'test ti ti-help-circle';
            break;
        case 12 :
            classes = 'qualif ti ti-circle';
            break;
        case 13 :
            classes = 'refused ti ti-circle-x';
            break;
        case 14 :
            classes = 'canceled ti ti-ban';
            break;
    }

    return $(`<span><i class="itilstatus ${classes}"></i> ${_.escape(option.text)}</span>`);
};

var templateValidation = function(option) {
    if (option === false) {
        // Option is false when element does not match searched terms
        return null;
    }

    var status = option.id || 0;

    var classes = "";
    switch (parseInt(status)) {
        case 2 : // WAITING
            classes = 'waiting ti ti-clock';
            break;
        case 3 : // ACCEPTED
            classes = 'accepted ti ti-circle-check-filled';
            break;
        case 4 : // REFUSED
            classes = 'refused ti ti-circle-x';
            break;
    }

    return $(`<span><i class="validationstatus ${classes}"></i> ${_.escape(option.text)}</span>`);
};

var templateItilPriority = function(option) {
    if (option === false) {
        // Option is false when element does not match searched terms
        return null;
    }

    var priority = option.id || 0;
    var priority_color = CFG_GLPI['priority_'+priority] || "";
    var color_badge = "";

    if (priority_color.length > 0) {
        color_badge += `<i class='ti ti-circle-filled' style='color: ${_.escape(priority_color)}'></i>`;
    }

    return $(`<span>${color_badge}&nbsp;${_.escape(option.text)}</span>`);
};

/**
 * Returns given text without is diacritical marks.
 *
 * @param {string} text
 *
 * @return {string}
 */
var getTextWithoutDiacriticalMarks = function (text) {
    // Normalizing to NFD Unicode normal form decomposes combined graphemes
    // into the combination of simple ones. The "è" becomes "e + ̀`".
    text = text.normalize('NFD');

    // The U+0300 -> U+036F range corresponds to diacritical chars.
    // They are removed to keep only chars without their diacritical mark.
    return text.replace(/[\u0300-\u036f]/g, '');
};

/**
 * Escape markup in text to prevent XSS.
 *
 * @param {string} text
 *
 * @return {string}
 */
var escapeMarkupText = function (text) {
    // TODO in GLPI 11.1: console.warn('`escapeMarkupText()` is deprecated, use `_.escape()` instead.');

    if (typeof(text) !== 'string') {
        return text;
    }

    return _.escape(text);
};

/**
 * Updates an accessible progress bar title and foreground width.
 * @since 9.5.0
 * @param progressid ID of the progress bar
 * @return void
 */
function updateProgress(progressid) {
    var progress = $("#"+CSS.escape(progressid)).first();
    $("div[data-progressid='"+CSS.escape(progressid)+"']").each(function(i, item) {
        var j_item = $(item);
        var fg = j_item.find(".progress-fg").first();
        var calcWidth = (progress.attr('value') / progress.attr('max')) * 100;
        fg.width(calcWidth+'%');
        if (j_item.data('append-percent') === 1) {
            var new_title = (j_item.prop('title').replace(new RegExp("\\d*%$"), progress.attr('value')+'%')).trim();
            progress.prop('title', new_title);
            j_item.prop('title', new_title);
        }
    });
}

// fullscreen api
function GoInFullscreen(element) {
    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) {
        element.msRequestFullscreen();
    }
}

function GoOutFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    }
}

function getUuidV4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function setHasUnsavedChanges(has_unsaved_changes) {
    window.glpiUnsavedFormChanges = has_unsaved_changes;
    document.dispatchEvent(new CustomEvent("glpiFormChangeEvent", {
        has_unsaved_changes: has_unsaved_changes
    }));
}

function hasUnsavedChanges() {
    return window.glpiUnsavedFormChanges;
}

/** Track input changes and warn the user of unsaved changes if they try to navigate away */
setHasUnsavedChanges(false);
$(document).ready(function() {
    // Forms must have the data-track-changes attribute set to true.
    // Form fields may have their data-track-changes attribute set to empty (false) to override the tracking on that input.
    $(document).on('input', 'form[data-track-changes="true"] input:not([data-track-changes=""]),' +
      'form[data-track-changes="true"] textarea:not([data-track-changes="false"])', function() {
        setHasUnsavedChanges(true);
    });
    $(document).on('change', 'form[data-track-changes="true"] select:not([data-track-changes=""])', function() {
        setHasUnsavedChanges(true);
    });
    $(window).on('beforeunload', function(e) {
        if (hasUnsavedChanges()) {
            e.preventDefault();
            // All supported browsers will show a localized message
            return '';
        }
    });

    $(document).on('submit', 'form', (e) => {
        // if the submitter has a data-block-on-unsaved attribute, do not clear the unsaved changes flag
        if (e.originalEvent && $(e.originalEvent.submitter).attr('data-block-on-unsaved') === 'true') {
            return;
        }
        setHasUnsavedChanges(false);
    });
});

function onTinyMCEChange(e) {
    var editor = $(e.target)[0];
    if ($(editor.targetElm).data('trackChanges') !== false) {
        if ($(editor.formElement).data('trackChanges') === true) {
            setHasUnsavedChanges(true);
        }
    }
}

function relativeDate(str) {
    var today = new Date(),
        strdate = new Date(str);
    today.setHours(0, 0, 0, 0);
    strdate.setHours(0, 0, 0, 0);

    var s = ( +new Date() - Date.parse(str) ) / 1e3,
        m = s / 60,
        h = m / 60,
        d = ( today - strdate ) / 864e5,
        w = d / 7,
        mo = d / 30.44,
        y = d / 365.24,
        tmp;

    return (tmp = Math.round(s)) === 1 ? __('just now')
        : m < 1.01 ? __('%s seconds ago').replace('%s', tmp)
            : (tmp = Math.round(m)) === 1 ? __('a minute ago')
                : h < 1.01 ? __('%s minutes ago').replace('%s', tmp)
                    : (tmp = Math.round(h)) === 1 ? __('an hour ago')
                        : d < 1.01 ? __('%s hours ago').replace('%s', tmp)
                            : (tmp = Math.round(d)) === 1 ? __('yesterday')
                                : w < 1.01 ? __('%s days ago').replace('%s', tmp)
                                    : (tmp = Math.floor(w)) === 1 ? __('a week ago')
                                        : mo < 1.01 ? __('%s weeks ago').replace('%s', tmp)
                                            : (tmp = Math.floor(mo)) === 1 ? __('a month ago')
                                                : y < 1 ? __('%s months ago').replace('%s', tmp)
                                                    : (tmp = Math.floor(y)) === 1 ? __('a year ago')
                                                        : __('%s years ago').replace('%s', tmp);
}

/**
 * Special case as both "English" and "English (US)" use the same locale for
 * flatpickr but should have different first day of week.
 * We must manually set firstDayOfWeek for "English"
 *
 * @param {String} language
 * @param {String} region
 * @returns
 */
function getFlatPickerLocale(language, region) {
    if (language == "en" && region == "GB") {
        return {
            firstDayOfWeek: 1 // No need to specify locale code, default is english
        };
    } else {
        return language;
    }
}

/**
 *
 * @param {string|Array<string>} dropdown_ids
 * @param {string} target
 * @param {string} url
 * @param {{}} params
 * @param {Array<string>} events
 * @param {number} min_size
 * @param {number} buffer_time
 * @param {Array<string>} force_load_for
 */
function updateItemOnEvent(dropdown_ids, target, url, params = {}, events = ['change'],
   min_size = -1, buffer_time = -1, force_load_for = []) { // eslint-disable-line

    if (!Array.isArray(dropdown_ids)) {
        dropdown_ids = [dropdown_ids];
    }
    const zones = dropdown_ids;
    $(zones).each((i, zone) => {
        $(events).each((i2, event) => {
            //TODO Manage buffer time

            const cleaned_zone_id = zone.replace('[', '_').replace(']', '_');
            const zone_obj = $(`#${CSS.escape(cleaned_zone_id)}`);

            zone_obj.on(event, () => {
                const conditional = (min_size >= 0 || force_load_for.length > 0);
                const min_size_condition = (min_size >= 0 && zone_obj.val().length() >= min_size);
                const force_load_condition = (force_load_for.length > 0 && force_load_for.includes(zone_obj.val()));

                const doLoad = () => {
                    // Resolve params to another array to avoid overriding dynamic params like "__VALUE__"
                    const resolved_params = {};
                    $.each(params, (k, v) => {
                        if (typeof v === "string") {
                            const reqs = v.match(/^__VALUE(\d+)__$/);
                            if (reqs !== null) {
                                resolved_params[k] = $('#'+CSS.escape(dropdown_ids[reqs[0]])).val();
                            } else if (v === '__VALUE__') {
                                resolved_params[k] = $('#'+CSS.escape(dropdown_ids[0])).val();
                            } else {
                                resolved_params[k] = v;
                            }
                        } else {
                            resolved_params[k] = v;
                        }
                    });
                    $(target).load(url, resolved_params);
                };
                if (conditional && (min_size_condition || force_load_condition)) {
                    doLoad();
                } else if (!conditional) {
                    doLoad();
                }
            });
        });
    });
}

function updateItemOnSelectEvent(dropdown_ids, target, url, params = {}) {
    updateItemOnEvent(dropdown_ids, target, url, params, ['change'], -1, -1, []);
}

/**
 * Initialize tooltips on given container.
 *
 * @param {Node} [container=document]
 *
 * @returns {void}
 */
function initTooltips(container) {
    if (container === undefined) {
        container = document;
    }

    const tooltipNodes = container.querySelectorAll('[data-bs-toggle="tooltip"]:not([data-bs-original-title])');
    tooltipNodes.forEach(
        function(tooltipNode) {
            const options = {
                delay: {show: 50, hide: 50},
                html: tooltipNode.hasAttribute("data-bs-html") ? tooltipNode.getAttribute("data-bs-html") === "true" : false,
                placement: tooltipNode.hasAttribute("data-bs-placement") ? tooltipNode.getAttribute('data-bs-placement') : 'auto',
                trigger : tooltipNode.hasAttribute("data-bs-trigger") ? tooltipNode.getAttribute("data-bs-trigger") : "hover",
            };
            return new bootstrap.Tooltip(tooltipNode, options);
        }
    );

    const popoverNodes = container.querySelectorAll('[data-bs-toggle="popover"]:not([data-bs-original-title])');
    popoverNodes.forEach(
        function(popoverNode) {
            const options = {
                delay: {show: 50, hide: 50},
                html: popoverNode.hasAttribute("data-bs-html") ? popoverNode.getAttribute("data-bs-html") === "true" : false,
                placement: popoverNode.hasAttribute("data-bs-placement") ? popoverNode.getAttribute('data-bs-placement') : 'auto',
                trigger : popoverNode.hasAttribute("data-bs-trigger") ? popoverNode.getAttribute("data-bs-trigger") : "hover",
                sanitize : popoverNode.hasAttribute("data-bs-sanitize") ? popoverNode.getAttribute("data-bs-sanitize") === "true" : true,
            };
            return new bootstrap.Popover(popoverNode, options);
        }
    );
}

/*
 * Sends the CSRF token in ajax POST requests headers.
 */
$(document).ajaxSend(
    function(event, xhr, settings) {
        if (settings.type !== 'POST') {
            return;
        }

        xhr.setRequestHeader('X-Glpi-Csrf-Token', getAjaxCsrfToken());
    }
);

/**
 * Returns CSRF token that can be used for AJAX requests.
 *
 * @returns {string|null}
 */
function getAjaxCsrfToken() {
    const meta  = document.querySelector('meta[property="glpi:csrf_token"]');
    return meta !== null ? meta.getAttribute('content') : null;
}

// init tooltips
$(
    function() {
        // Init uninitialized tooltips everytime an ajax query is completed.
        $(document).ajaxComplete(
            function() {
                initTooltips();
            }
        );

        // init tooltips after a little time on dom load
        setTimeout(function() {
            initTooltips();
        }, 50);
    }
);

// case insentive :contains selector -> ":icontains"
jQuery.expr.filters.icontains = function(elem, i, m) {
    return (elem.innerText || elem.textContent || "").toLowerCase().indexOf(m[3].toLowerCase()) > -1;
};

function tableToDetails(table) {
    let in_details = false;
    const section_els = $(table).find('.section-header, .section-content');
    let details = '';

    section_els.each((i, e) => {
        if (e.classList.contains('section-header')) {
            if (in_details) {
                details += '</pre></details>';
            }
            details += `<details><summary>${_.escape(e.innerText)}</summary><pre>`;
            in_details = true;
        } else {
            if (in_details) {
                details += _.escape(e.innerText);
            }
        }
    });

    if (in_details) {
        details += '</pre></details>';
    }
    return details;
}

function flashIconButton(button, button_classes, icon_classes, duration) {
    const btn = $(button);
    const ico = btn.find('i').eq(0);
    const original_btn_classes = btn.attr('class');
    const original_ico_classes = ico.attr('class');
    btn.removeClass();
    ico.removeClass();
    btn.addClass(button_classes);
    ico.addClass(icon_classes);
    window.setTimeout(() => {
        btn.removeClass();
        ico.removeClass();
        btn.addClass(original_btn_classes);
        ico.addClass(original_ico_classes);
    }, duration);
}

/**
 * uniqid() function, providing similar result as PHP does.
 *
 * @param {string} prefix
 * @param {boolean} random
 * @returns {string}
 *
 * @see https://stackoverflow.com/a/48593447
 */
function uniqid(prefix = "", more_entropy = false) {
    const sec = Date.now() * 1000 + Math.random() * 1000;
    const id = sec.toString(16).replace(/\./g, "").padEnd(14, "0");
    const suffix = more_entropy
        ? '.' + Math.floor(Math.random() * 100000000).toString().padStart(8, '0')
        : '';
    return `${prefix}${id}${suffix}`;
}

/**
 * Prevent submitting the form again
 *
 * A spinner is shown in the triggering submit button and all others are disabled.
 * @param form The form to block submits
 * @param {SubmitEvent} e The submit event
 */
function blockFormSubmit(form, e) {
    var submitter = null;

    if (e.originalEvent && e.originalEvent.submitter) {
        submitter = $(e.originalEvent.submitter);
    }

    // if submitter is not a button, find the first submit button with add or update as the name
    if (submitter === null || !submitter.is('button')) {
        submitter = form.find('button[name="add"]:first, button[name="update"]:first');
        // If no submit button was found, use the first submit button
        if (submitter.length === 0) {
            submitter = form.find('button[type="submit"]:first');
        }
    }

    if (submitter.length > 0 && submitter.is('button')) {
        submitter.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`);
    }

    // Prevent clicking any submit buttons in form
    form.find('button[type="submit"]').click((e) => {e.preventDefault();});
    // Mark the form as submitted
    form.attr('data-submitted', 'true');
}

window.validateFormWithBootstrap = function (event) {
    const form = $(event.target).closest('form');
    const valid = form[0].checkValidity();

    if (form.hasClass('needs-validation')) {
        if (!valid) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.addClass('was-validated');
    }

    return valid;
};

$(() => {
    $(document.body).on('submit', 'form[data-submit-once]', (e) => {
        const form = $(e.target).closest('form');
        if (form.attr('data-submitted') === 'true') {
            e.preventDefault();
            return false;
        } else {
            let submitter = null;
            if (e.originalEvent && e.originalEvent.submitter) {
                submitter = $(e.originalEvent.submitter);
            }
            if ((submitter === null || submitter.attr('formnovalidate') === undefined) && !window.validateFormWithBootstrap(e)) {
                return false;
            }
            if (submitter !== null && submitter.is('button') && submitter.attr('data-block-on-unsaved') === 'true' && hasUnsavedChanges()) {
                // This submit may be cancelled by the unsaved changes warning so we cannot permanently block it
                // We fall back to a timed block
                const block = function(e) {
                    e.preventDefault();
                };
                submitter.on('click', block);
                submitter.data('original_html', submitter.html());
                submitter.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`);
                window.setTimeout(() => {
                    submitter.off('click', block);
                    submitter.html(submitter.data('original_html'));
                }, 100);
                return;
            }
            blockFormSubmit(form, e);
        }
    });

    // Clear focus on content-editable-tinymce items when clicking outside of their content
    $(document).on('click focus', 'body', function(e) {
        if (
            // Event must be outside of our simulate-focus item
            $(e.target).closest('.simulate-focus').length == 0
            // Special case when target is part of tinymce toolbar/aux, must NOT drop focus in this case
            && $(e.target).closest('.tox-toolbar__overflow').length == 0
            && $(e.target).closest('.tox-tinymce-aux').length == 0
        ) {
            $('.content-editable-tinymce').removeClass('simulate-focus');
        }
    });

    // General "copy to clipboard" handler.
    // TODO: refactorate existing code to use this unique handler.
    $(document).on('click', '[data-glpi-clipboard-text]', function() {
        const text = $(this).data('glpi-clipboard-text');
        if (navigator.clipboard === undefined) {
            // The clipboard is not available in non secure environements.
            // See: https://developer.mozilla.org/en-US/docs/Web/API/Clipboard
            // See: https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts
            // This rarely happens in production but we can still add a specific
            // error message to identify this issue in our support and/or help
            // system administrator fix it themselves.
            glpi_toast_error(__("Unable to copy to clipboard (insecure context)."));
        } else {
            navigator.clipboard.writeText(text);
            glpi_toast_info(__("Copied to clipboard"));
        }
    });
});

/**
 * Strip html tags from a string
 *
 * @param {String} html_string
 * @returns {String}
 */
function strip_tags(html_string) {
    var dom = new DOMParser().parseFromString(html_string, 'text/html');
    return dom.body.textContent;
}

$(document.body).on('shown.bs.tab', 'a[data-bs-toggle="tab"]', (e) => {
    const new_tab = $(e.target);
    // Main tab is the first in the list (check parent li)
    const is_main_tab = new_tab.parent().index() === 0;
    const nav_header = new_tab.closest('.card-tabs').parent().find('.navigationheader');
    if (nav_header.length > 0) {
        const is_recursive_toggle = nav_header.find('span.is_recursive-toggle');
        if (is_recursive_toggle.length > 0) {
            const checkbox = is_recursive_toggle.find('input');
            const disabled_state = checkbox.prop('disabled');
            // if data-disabled-initial is not set, set it to the current disabled state
            if (checkbox.attr('data-disabled-initial') === undefined) {
                checkbox.attr('data-disabled-initial', disabled_state || false);
            }
            const original_disabled_state = checkbox.attr('data-disabled-initial') === 'true';
            // disable input element inside the toggle
            checkbox.prop('disabled', is_main_tab ? original_disabled_state : true);
        }
    }
});

/**
 * Converts a disclosable password field to a normal text field
 * @param {string} item The ID of the field to be shown
 */
function showDisclosablePasswordField(item) {
    $("#" + CSS.escape(item)).prop("type", "text");
}

/**
 * Converts a normal text field to a password field
 * @param {string} item The ID of the field to be hidden
 */
function hideDisclosablePasswordField(item) {
    $("#" + CSS.escape(item)).prop("type", "password");
}

/**
 * Copies the password from a disclosable password field to the clipboard
 * @param {string} item The ID of the field to be copied
 */
function copyDisclosablePasswordFieldToClipboard(item) {
    const is_password_input = $("#" + CSS.escape(item)).prop("type") === "password";
    if (is_password_input) {
        showDisclosablePasswordField(item);
    }
    $("#" + CSS.escape(item)).select();
    try {
        document.execCommand("copy");
    } catch {
        alert("Copy to clipboard failed'");
    }
    if (is_password_input) {
        hideDisclosablePasswordField(item);
    }
}

/**
 * Convert an HTML table with static content to a basic sortable table
 * @param element_id The ID of the table to be converted
 */
function initSortableTable(element_id) {
    const element = $(`#${CSS.escape(element_id)}`);
    const sort_table = (column_index) => {
        const current_sort = element.data('sort');
        element.data('sort', column_index);
        const current_order = element.data('order');
        const new_order = current_sort === column_index && current_order === 'up' ? 'down' : 'up';
        element.data('order', new_order);
        const sortable_header = element.find('thead').first();
        const col = sortable_header.find('th').eq(column_index);
        // Remove all sort icon classes
        sortable_header.find('th i[class*="ti ti-caret"]').removeClass('ti-caret-down-filled ti-caret-up-filled');

        const sort_icon = col.find('i');
        if (sort_icon.length === 0) {
            // Add sort icon
            col.eq(0).append(`<i class="ti ti-caret-${new_order}-filled"></i>`);
        } else {
            sort_icon.addClass(new_order === 'up' ? 'ti-caret-up-filled' : 'ti-caret-down-filled');
        }

        const rows = element.find('tbody tr');
        const sorted_rows = rows.sort((a, b) => {
            const a_cell = $(a).find('td').eq(column_index);
            const b_cell = $(b).find('td').eq(column_index);
            let a_value = a_cell.text();
            let b_value = b_cell.text();

            if (a_cell.attr('data-value-unit') !== undefined) {
                a_value = a_value.replace(a_cell.attr('data-value-unit'), '').trim();
            }
            if (b_cell.attr('data-value-unit') !== undefined) {
                b_value = b_value.replace(b_cell.attr('data-value-unit'), '').trim();
            }
            // if the values are numberic, cast them to numbers to sort them correctly
            if (!isNaN(a_value) && !isNaN(b_value)) {
                a_value = Number(a_value);
                b_value = Number(b_value);
            }

            if (a_value === b_value) {
                return 0;
            }
            if (new_order === 'up') {
                return a_value < b_value ? -1 : 1;
            }
            return a_value > b_value ? -1 : 1;
        });
        element.find('tbody').html(sorted_rows);
    };

    // Make all th in thead appear clickable and bold
    element.find('thead th').attr('role', 'button');
    element.find('thead th').addClass('fw-bold');

    element.find('thead th').each((index, header) => {
        $(header).on('click', () => {
            sort_table(index);
        });
    });
}

/**
 * Wait for an element to be available in the DOM
 * @param {string} selector The selector to wait for
 */
function waitForElement(selector) {
    return new Promise(resolve => {
        if (document.querySelector(selector)) {
            return resolve(document.querySelector(selector));
        }

        const observer = new MutationObserver(() => {
            if (document.querySelector(selector)) {
                resolve(document.querySelector(selector));
                observer.disconnect();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
}

/**
 * Get UUID using crypto.randomUUID() if possible
 * Else fallback to uniqid()
 */
function getUUID() {
    // crypto functions are only available when using secure context
    if (typeof crypto === "undefined" || typeof crypto.randomUUID === "undefined") {
        // Fallback to another method that is always available but collisions
        // are not totally impossible.
        return uniqid();
    }

    return crypto.randomUUID();
}

// Init the AJAX controller
/* global GlpiCommonAjaxController */
if (typeof GlpiCommonAjaxController == "function") {
    new GlpiCommonAjaxController();
}

function setupAjaxDropdown(config) {
    // Field ID is used as a selector, so we need to escape special characters
    // to avoid issues with jQuery.
    const field_id = CSS.escape(config.field_id);

    const select2_el = $('#' + field_id).select2({
        containerCssClass: config.container_css_class,
        width: config.width,
        multiple: config.multiple,
        placeholder: config.placeholder,
        allowClear: config.allowclear,
        minimumInputLength: 0,
        quietMillis: 100,
        dropdownAutoWidth: true,
        dropdownParent: $('#' + field_id).closest('div.modal, div.dropdown-menu, body'),
        minimumResultsForSearch: config.ajax_limit_count,
        ajax: {
            url: config.url,
            dataType: 'json',
            type: 'POST',
            data: function (params) {
                query = params;
                var data = $.extend({}, config.params, {
                    searchText: params.term,
                });

                if (config.parent_id_field !== '') {
                    data.parent_id = document.getElementById(config.parent_id_field).value;
                }

                data.page_limit = config.dropdown_max; // page size
                data.page = params.page || 1; // page number

                /** convert data false and true values to int **/
                Object.keys(data).forEach(function(key) {
                    if (data[key] === false) {
                        data[key] = 0;
                    } else if (data[key] === true) {
                        data[key] = 1;
                    }
                });

                return data;
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                var more = (data.count >= config.dropdown_max);

                return {
                    results: data.results,
                    pagination: {
                        more: more
                    }
                };
            }
        },
        templateResult: config.templateResult,
        templateSelection: config.templateSelection
    })
        .bind('setValue', function (e, value) {
            $.ajax(config.url, {
                data: $.extend({}, config.params, {
                    _one_id: value,
                }),
                dataType: 'json',
                type: 'POST',
            }).done(function (data) {

                var iterate_options = function (options, value) {
                    var to_return = false;
                    $.each(options, function (index, option) {
                        if (Object.prototype.hasOwnProperty.call(option, 'id') && option.id == value) {
                            to_return = option;
                            return false; // act as break;
                        }

                        if (Object.prototype.hasOwnProperty.call(option, 'children')) {
                            to_return = iterate_options(option.children, value);
                        }
                    });

                    return to_return;
                };

                var option = iterate_options(data.results, value);
                if (option !== false) {
                    var newOption = new Option(option.text, option.id, true, true);
                    $('#' + field_id).append(newOption).trigger('change');
                }
            });
        });

    if (config.on_change !== '') {
        // eslint-disable-next-line no-eval
        $('#' + field_id).on('change', function () { eval(config.on_change); });
    }

    $('label[for=' + field_id + ']').on('click', function () { $('#' + field_id).select2('open'); });
    $('#' + field_id).on('select2:open', function (e) {
        const search_input = document.querySelector(`.select2-search__field[aria-controls='select2-${CSS.escape(e.target.id)}-results']`);
        if (search_input) {
            search_input.focus();
        }
    });

    return select2_el;
}

function setupAdaptDropdown(config)
{
    // Field ID is used as a selector, so we need to escape special characters
    // to avoid issues with jQuery.
    const field_id = CSS.escape(config.field_id);

    const options = {
        width: config.width,
        dropdownAutoWidth: true,
        dropdownCssClass: config.dropdown_css_class,
        dropdownParent: $('#' + field_id).closest('div.modal, div.dropdown-menu, body'),
        quietMillis: 100,
        minimumResultsForSearch: config.ajax_limit_count,
        matcher: function (params, data) {
            // store last search in the global var
            query = params;

            // If there are no search terms, return all of the data
            if ($.trim(params.term) === '') {
                return data;
            }

            const pre_marker = '#-#-#-#-#';
            const post_marker = '#+#+#+#+#';

            const renderResults = function (text) {
                return _.escape(text)
                    .replaceAll(pre_marker, '<span class="select2-rendered__match">')
                    .replaceAll(post_marker, '</span>');
            };

            var searched_term = getTextWithoutDiacriticalMarks(params.term);
            var data_text = typeof (data.text) === 'string'
                ? getTextWithoutDiacriticalMarks(data.text)
                : '';
            var select2_fuzzy_opts = {
                pre: pre_marker,
                post: post_marker,
            };

            // Skip if there is no 'children' property
            if (typeof data.children === 'undefined') {
                var match = fuzzy.match(searched_term, data_text, select2_fuzzy_opts);
                if (match == null) {
                    return false;
                }
                data.rendered_text = renderResults(match.rendered);
                data.score = match.score;
                return data;
            }

            // `data.children` contains the actual options that we are matching against
            // also check in `data.text` (optgroup title)
            var filteredChildren = [];

            $.each(data.children, function (idx, child) {
                var child_text = typeof (child.text) === 'string'
                    ? getTextWithoutDiacriticalMarks(child.text)
                    : '';

                var match_child = fuzzy.match(searched_term, child_text, select2_fuzzy_opts);
                var match_text = fuzzy.match(searched_term, data_text, select2_fuzzy_opts);
                if (match_child !== null || match_text !== null) {
                    if (match_text !== null) {
                        data.score = match_text.score;
                        data.rendered_text = renderResults(match_text.rendered);
                    }

                    if (match_child !== null) {
                        child.score = match_child.score;
                        child.rendered_text = renderResults(match_child.rendered);
                    }
                    filteredChildren.push(child);
                }
            });

            // If we matched any of the group's children, then set the matched children on the group
            // and return the group object
            if (filteredChildren.length) {
                var modifiedData = $.extend({}, data, true);
                modifiedData.children = filteredChildren;

                // You can return modified objects from here
                // This includes matching the `children` how you want in nested data sets
                return modifiedData;
            }

            // Return `null` if the term should not be displayed
            return null;
        },
        templateResult: config.templateresult,
        templateSelection: config.templateselection,
    };
    if (config.placeholder !== undefined && config.placeholder !== '') {
        options.placeholder = config.placeholder;
    }
    const select2_el = $('#' + field_id).select2(options);

    select2_el.bind('setValue', (e, value) => {
        $('#' + field_id).val(value).trigger('change');
    });
    $('label[for=' + field_id + ']').on('click', function () {
        $('#' + field_id).select2('open');
    });
    $('#' + field_id).on('select2:open', function (e) {
        const search_input = document.querySelector(`.select2-search__field[aria-controls='select2-${CSS.escape(e.target.id)}-results']`);
        if (search_input) {
            search_input.focus();
        }
    });

    return select2_el;
}

window.displaySessionMessages = () => {
    $.ajax({
        method: 'GET',
        url: (CFG_GLPI.root_doc + "/ajax/displayMessageAfterRedirect.php"),
        data: {
            'get_raw': true
        }
    }).then((messages) => {
        $.each(messages, (level, level_messages) => {
            $.each(level_messages, (index, message) => {
                switch (parseInt(level)) {
                    case 1:
                        glpi_toast_error(message);
                        break;
                    case 2:
                        glpi_toast_warning(message);
                        break;
                    default:
                        glpi_toast_info(message);
                }
            });
        });
    });
};

// Add/remove a special data attribute to bootstrap's modals when they are
// displayed/hidden.
// This is needed for e2e testing as bootstrap have some compatibility issues
// with cypress.
// See https://github.com/cypress-io/cypress/issues/25202.
document.addEventListener('shown.bs.modal', (e) => {
    const modal = e.target.closest('.modal');
    if (modal) {
        modal.setAttribute('data-cy-shown', 'true');
    }
});
document.addEventListener('hidden.bs.modal', (e) => {
    const modal = e.target.closest('.modal');
    if (modal) {
        modal.setAttribute('data-cy-shown', 'false');
    }
});

// Tinymce on click loading
$(document).on('click', 'div[data-glpi-tinymce-init-on-demand-render]', function() {
    const div = $(this);
    const textarea_id = div.attr('data-glpi-tinymce-init-on-demand-render');
    div.removeAttr('data-glpi-tinymce-init-on-demand-render');
    const textarea = $("#" + textarea_id);

    const loadingOverlay = $(`
        <div class="glpi-form-editor-loading-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75">
            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                <span class="visually-hidden">${__('Loading...')}</span>
            </div>
        </div>
    `);

    textarea.show();
    div.css('position', 'relative').append(loadingOverlay);
    tinyMCE.init(tinymce_editor_configs[textarea_id]).then((editors) => {
        editors[0].focus();
        div.remove();
    });
});

// Prevent Bootstrap dialog from blocking focusin
// See: https://www.tiny.cloud/docs/tinymce/latest/bootstrap-cloud/#usingtinymceinabootstrapdialog
document.addEventListener('focusin', (e) => {
    if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
        e.stopImmediatePropagation();
    }
});

