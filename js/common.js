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

/* global bootstrap */
/* global L */
/* global glpi_html_dialog */

var timeoutglobalvar;

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
function checkAsCheckboxes(reference, container_id) {
    reference = typeof(reference) === 'string' ? document.getElementById(reference) : reference;
    $('#' + container_id + ' input[type="checkbox"]:enabled')
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
            evt.preventDefault();
            var start = $boxes.index(selected_checkbox);
            var end = $boxes.index(lastChecked);
            $boxes.slice(Math.min(start, end), Math.max(start, end) + 1)
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
function showHideDiv(id, img_name, img_src_close, img_src_open) {
    var _elt = $('#' + id);

    if (img_name !== '') {
        var _awesome = img_src_close.match(/^fa-/);
        var _deco;
        var _img;
        if (!_awesome) {
            _img = $('img[name=' + img_name + ']');
            if (_elt.is(':visible')) {
                _img.attr('src', img_src_close);
            } else {
                _img.attr('src', img_src_open);
            }
        } else {
            _deco = $('#'+img_name);
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
    var element =$('#'+id);var selected = [];
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
    $('#'+id).val('').trigger('change');
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
        value = $('#' + reference).prop('checked');
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
    if ($('html').hasClass('loginpage')) {
        return;
    }

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

    return '<img src="'+url+'" title="'+ext+'">';
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
    var bytes   = ['o', 'Kio', 'Mio', 'Gio', 'Tio'];
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

var initMap = function(parent_elt, map_id, height, initial_view = {position: [43.6112422, 3.8767337], zoom: 6}) {
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
    parent_elt.append($('<div id="'+map_id+'" style="height: ' + height + '"></div>'));
    var map = L.map(map_id, {fullscreenControl: true}).setView(initial_view.position, initial_view.zoom);

    //setup tiles and © messages
    L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href=\'https://osm.org/copyright\'>OpenStreetMap</a> contributors'
    }).addTo(map);
    return map;
};

var showMapForLocation = function(elt) {
    var _id = $(elt).data('fid');
    var _items_id = $('#' + _id).val();

    if (_items_id == 0) {
        return;
    }

    glpi_html_dialog({
        title: __("Display on map"),
        body: "<div id='location_map_dialog'/>",
        dialogclass: "modal-lg",
        show: function() {
            //add map, set a default arbitrary location
            var map_elt = initMap($('#location_map_dialog'), 'location_map');
            map_elt.spin(true);

            $.ajax({
                dataType: 'json',
                method: 'POST',
                url: CFG_GLPI.root_doc + '/ajax/getMapPoint.php',
                data: {
                    itemtype: 'Location',
                    items_id: $('#' + _id).val()
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
        _result.append(escapeMarkupText(text));
        return _result.html();
    }

    // Put in whatever text is before the match
    _result.html(escapeMarkupText(text.substring(0, match)));

    // Mark the match
    var _match = $('<span class=\'select2-rendered__match\'></span>');
    _match.html(escapeMarkupText(text.substring(match, match + term.length)));

    // Append the matching text
    _result.append(_match);

    // Put in whatever is after the match
    _result.append(escapeMarkupText(text.substring(match + term.length)));

    return _result.html();
}

/**
 * Function that renders select2 results.
 */
var templateResult = function(result) {
    var _elt = $('<span></span>');
    _elt.attr('title', result.title);

    if (typeof query.term !== 'undefined' && typeof result.rendered_text !== 'undefined') {
        _elt.html(result.rendered_text);
    } else {
        if (!result.text) {
            return null;
        }

        var text = result.text;
        if (!result.id) {
            // If result has no id, then it is used as an optgroup and is not used for matches
            _elt.html(escapeMarkupText(text));
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
    _elt.html(escapeMarkupText(text));
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
            classes = 'new fas fa-circle';
            break;
        case 2 :
            classes = 'assigned far fa-circle';
            break;
        case 3 :
            classes = 'planned far fa-calendar';
            break;
        case 4 :
            classes = 'waiting fas fa-circle';
            break;
        case 5 :
            classes = 'solved far fa-circle';
            break;
        case 6 :
            classes = 'closed fas fa-circle';
            break;
        case 7:
            classes = 'accepted fas fa-check-circle';
            break;
        case 8 :
            classes = 'observe fas fa-eye';
            break;
        case 9 :
            classes = 'eval far fa-circle';
            break;
        case 10 :
            classes = 'approval fas fa-question-circle';
            break;
        case 11 :
            classes = 'test fas fa-question-circle';
            break;
        case 12 :
            classes = 'qualif far fa-circle';
            break;
        case 13 :
            classes = 'refused far fa-times-circle';
            break;
        case 14 :
            classes = 'canceled fas fa-ban';
            break;
    }

    return $(`<span><i class="itilstatus ${classes}"></i> ${option.text}</span>`);
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
            classes = 'waiting far fa-clock';
            break;
        case 3 : // ACCEPTED
            classes = 'accepted fas fa-check';
            break;
        case 4 : // REFUSED
            classes = 'refused fas fa-times';
            break;
    }

    return $(`<span><i class="validationstatus ${classes}"></i> ${option.text}</span>`);
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
        color_badge += `<i class='fas fa-circle' style='color: ${priority_color}'></i>`;
    }

    return $(`<span>${color_badge}&nbsp;${option.text}</span>`);
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
    if (text.indexOf('>') !== -1 || text.indexOf('<') !== -1) {
        // escape text, if it contains chevrons (can already be escaped prior to this point :/)
        text = jQuery.fn.select2.defaults.defaults.escapeMarkup(text);
    }
    return text;
};

/**
 * Updates an accessible progress bar title and foreground width.
 * @since 9.5.0
 * @param progressid ID of the progress bar
 * @return void
 */
function updateProgress(progressid) {
    var progress = $("progress#progress"+progressid).first();
    $("div[data-progressid='"+progressid+"']").each(function(i, item) {
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

/**
 * Get RGB object from an hexadecimal color code
 *
 * @param {*} hex
 * @returns {Object} {r, g, b}
 */
function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

/**
 * Get luminance for a color
 * https://www.w3.org/TR/2008/REC-WCAG20-20081211/#relativeluminancedef
 *
 * @param {Array} rgb [r, g, b] array
 * @returns {Number}
 */
function luminance(rgb) {
    var a = rgb.map(function (v) {
        v /= 255;
        return v <= 0.03928
            ? v / 12.92
            : Math.pow( (v + 0.055) / 1.055, 2.4 );
    });
    return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
}

/**
 * Get contrast ratio between two colors
 * https://www.w3.org/TR/2008/REC-WCAG20-20081211/#contrast-ratiodef
 *
 * @param {Array} rgb1 [r, g, b] array
 * @param {Array} rgb2 [r, g, b] array
 * @returns {Number}
 */
function contrast(rgb1, rgb2) {
    return (luminance(rgb1) + 0.05) / (luminance(rgb2) + 0.05);
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

/** Track input changes and warn the user of unsaved changes if they try to navigate away */
window.glpiUnsavedFormChanges = false;
$(document).ready(function() {
    // Forms must have the data-track-changes attribute set to true.
    // Form fields may have their data-track-changes attribute set to empty (false) to override the tracking on that input.
    $(document).on('input', 'form[data-track-changes="true"] input:not([data-track-changes=""]),' +
      'form[data-track-changes="true"] textarea:not([data-track-changes="false"])', function() {
        window.glpiUnsavedFormChanges = true;
    });
    $(document).on('change', 'form[data-track-changes="true"] select:not([data-track-changes=""])', function() {
        window.glpiUnsavedFormChanges = true;
    });
    $(window).on('beforeunload', function(e) {
        if (window.glpiUnsavedFormChanges) {
            e.preventDefault();
            // All supported browsers will show a localized message
            return '';
        }
    });

    $(document).on('submit', 'form', function() {
        window.glpiUnsavedFormChanges = false;
    });
});

function onTinyMCEChange(e) {
    var editor = $(e.target)[0];
    if ($(editor.targetElm).data('trackChanges') !== false) {
        if ($(editor.formElement).data('trackChanges') === true) {
            window.glpiUnsavedFormChanges = true;
        }
    }
}

function relativeDate(str) {
    var s = ( +new Date() - Date.parse(str) ) / 1e3,
        m = s / 60,
        h = m / 60,
        d = h / 24,
        y = d / 365.242199,
        tmp;

    return (tmp = Math.round(s)) === 1 ? __('just now')
        : m < 1.01 ? '%s seconds ago'.replace('%s', tmp)
            : (tmp = Math.round(m)) === 1 ? __('a minute ago')
                : h < 1.01 ? '%s minutes ago'.replace('%s', tmp)
                    : (tmp = Math.round(h)) === 1 ? __('an hour ago')
                        : d < 1.01 ? '%s hours ago'.replace('%s', tmp)
                            : (tmp = Math.round(d)) === 1 ? __('yesterday')
                                : y < 1.01 ? '%s days ago'.replace('%s', tmp)
                                    : (tmp = Math.round(y)) === 1 ? __('a year ago')
                                        : '%s years ago'.replace('%s', tmp);
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
            const zone_obj = $(`#${cleaned_zone_id}`);

            zone_obj.on(event, () => {
                const conditional = (min_size >= 0 || force_load_for.length > 0);
                const min_size_condition = (min_size >= 0 && zone_obj.val().length() >= min_size);
                const force_load_condition = (force_load_for.length > 0 && force_load_for.includes(zone_obj.val()));

                const doLoad = () => {
                    // Resolve params to another array to avoid overriding dynamic params like "__VALUE__"
                    let resolved_params = {};
                    $.each(params, (k, v) => {
                        if (typeof v === "string") {
                            const reqs = v.match(/^__VALUE(\d+)__$/);
                            if (reqs !== null) {
                                resolved_params[k] = $('#'+dropdown_ids[reqs[0]]).val();
                            } else if (v === '__VALUE__') {
                                resolved_params[k] = $('#'+dropdown_ids[0]).val();
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
                details += '</details>';
            }
            details += `<details><summary>${e.innerText}</summary><pre>`;
            in_details = true;
        } else {
            if (in_details) {
                details += e.innerText;
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
    return `${prefix}${id}${more_entropy ? `.${Math.trunc(Math.random() * 100000000)}`:""}`;
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
        submitter = submitter.find('button[name="add"]:first, button[name="update"]:first');
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

$(document.body).on('submit', 'form[data-submit-once]', (e) => {
    const form = $(e.target).closest('form');
    if (form.attr('data-submitted') === 'true') {
        e.preventDefault();
        return false;
    } else {
        blockFormSubmit(form, e);
    }
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
