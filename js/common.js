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
var timeoutglobalvar;

if (typeof(String.prototype.normalize) !== 'function') {
   $.ajax({
      type: "GET",
      url: CFG_GLPI.root_doc + "/lib/unorm/unorm.js",
      dataType: "script",
      cache: true
   });
}

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
 * masquer le menu actif par timeout
 *
 * @param idMenu
**/
function afterView(idMenu) {

   setdisplay(idMenu,'none');
}


/**
 * @param id
 * @param idMenu
**/
function menuAff(id, idMenu) {

   var m      = document.getElementById(idMenu);
   var item   = m.getElementsByTagName('li');
   var ssmenu = null;
   for (var i=0; i<item.length; i++) {
      if (item[i].id == id) {
         ssmenu = item[i];
      }
   }
   m = m.getElementsByTagName('ul');

   if (ssmenu) {
      var smenu = ssmenu.getElementsByTagName('ul');
      if (smenu) {
         //masquer tous les menus ouverts
         for (i=0; i<m.length; i++) {
            setdisplay(m[i],'none');
         }
         setdisplay(smenu[0],'block');
         clearTimeout(timeoutglobalvar);
         ssmenu.onmouseout = function() {
            timeoutglobalvar = setTimeout(function() {
               afterView(smenu[0]);
            },300);
         };
      }
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
 * @param    reference_id    DOM element
 * @param    container_id    DOM element
**/
function checkAsCheckboxes(reference_id, container_id) {
   $('#' + container_id + ' input[type="checkbox"]:enabled')
      .prop('checked', $('#' + reference_id).is(':checked'));

   return true;
}

/**
 * Permit to use Shift key on a group of checkboxes
 * Usage: $form.find('input[type="checkbox"]').shiftSelectable();
 */
$.fn.shiftSelectable = function() {
   var lastChecked,
       $boxes = this;

   // prevent html selection
   document.onkeydown = function(e) {
      var keyPressed = e.keyCode;
      if (keyPressed == 16) { // shift key
         $('html').addClass('unselectable');
         document.onkeyup = function() {
            $('html').removeClass('unselectable');
         };
      }
   };

   $($boxes.selector).parent().click(function(evt) {
      if ($boxes.length <= 0) {
         $boxes = $($boxes.selector);
      }
      var selected_checkbox = $(this).children('input[type=checkbox]');

      if (!lastChecked) {
         lastChecked = selected_checkbox;
         return;
      }

      if (evt.shiftKey) {
         evt.preventDefault();
         var start = $boxes.index(selected_checkbox),
             end = $boxes.index(lastChecked);
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
      var _deco,
          _img;
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
   $(document).on("click", ".long_text .read_more a", function() {
      $(this).parents('.long_text').removeClass('long_text');
      $(this).parent('.read_more').remove();
      return false;
   });
};


var split_button_fct_called = false;
var split_button = function() {
   if (split_button_fct_called) {
      return true;
   }
   split_button_fct_called = true;

   // unfold status list
   $(document).on("click", '.x-button-drop', function() {
      $(this).parents(".x-split-button").toggleClass('open');
   });

   $(document).on("click", '.x-split-button', function(event) {
      event.stopPropagation();
   });

   //click on an element of status list
   $(document).on("click", '.x-button-drop-menu li', function(event) {
      var chosen_li = $(this);
      if (event.target.children.length) {
         var xBtnDrop = chosen_li.parent().siblings(".x-button-drop");
         //clean old status class
         xBtnDrop.attr('class','x-button x-button-drop');

         //find status
         var cstatus = chosen_li.data('status');

         //add status to dropdown button
         xBtnDrop.addClass(cstatus);

         //fold status list
         chosen_li.parents(".x-split-button").removeClass('open');
      }
   });

   //fold status list on click on document
   $(document).on("click", function() {
      if ($('.x-split-button').hasClass('open')) {
         $('.x-split-button').removeClass('open');
      }
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

var langSwitch = function(elt) {
   var _url = elt.attr('href').replace(/front\/preference.+/, 'ajax/switchlang.php');
   $.ajax({
      url: _url,
      type: 'GET',
      success: function(html) {
         $('#language_link')
            .html(html);
         $('#debugajax').remove();
      }
   });
};

$(function() {
   if ($('html').hasClass('loginpage')) {
      return;
   }
   $('#menu.fullmenu li').on('mouseover', function() {
      var _id = $(this).data('id');
      menuAff('menu' + _id, 'menu');
   });

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

   // prevent jquery ui dialog to keep focus
   $.ui.dialog.prototype._focusTabbable = function() {};

   //Hack for Jquery Ui Date picker
   var _gotoToday = jQuery.datepicker._gotoToday;
   jQuery.datepicker._gotoToday = function(a) {
      var target = jQuery(a);
      var inst = this._getInst(target[0]);
      _gotoToday.call(this, a);
      jQuery.datepicker._selectDate(a, jQuery.datepicker._formatDate(inst,inst.selectedDay, inst.selectedMonth, inst.selectedYear));
   };

   //quick lang switch
   $('#language_link > a').on('click', function(event) {
      event.preventDefault();
      langSwitch($(this));
   });

   // ctrl+enter in form textareas (without tinymce)
   $(document).on('keydown', '#page form textarea', function(event) {
      if (event.ctrlKey
          && event.keyCode == 13) {
         submitparentForm($(this));
      }
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
   var submit = form.find('input[type=submit]').filter('[name=add], [name=update]');

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
            $('#backtotop').show('slow');
            $('#see_debug').addClass('wbttop');
         } else {
            $('#backtotop').hide();
            $('#see_debug').removeClass('wbttop');
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

var initMap = function(parent_elt, map_id, height) {
   // default parameters
   map_id = (typeof map_id !== 'undefined') ? map_id : 'map';
   height = (typeof height !== 'undefined') ? height : '200px';

   if (height == 'full') {
      //full height map
      var wheight = $(window).height();
      var _oSize = 0;

      $('#header_top, #c_menu, #c_ssmenu2, #footer, .search_page').each(function(){
         _oSize += _eltRealSize($(this));
      });
      _oSize += parseFloat($('#page').css('padding-top').replace('px', ''));
      _oSize += parseFloat($('#page').css('padding-bottom').replace('px', ''));
      _oSize += parseFloat($('#page').css('margin-top').replace('px', ''));
      _oSize += parseFloat($('#page').css('margin-bottom').replace('px', ''));

      var newHeight = Math.floor(wheight - _oSize);
      var minHeight = 300;
      if ( newHeight < minHeight ) {
         newHeight = minHeight;
      }
      height = newHeight + 'px';
   }

   //add map, set a default arbitrary location
   parent_elt.append($('<div id="'+map_id+'" style="height: ' + height + '"></div>'));
   var map = L.map(map_id, {fullscreenControl: true}).setView([43.6112422, 3.8767337], 6);

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

   _dialog = $('<div id="location_map_dialog"/>');
   _dialog.appendTo('body').dialog({
      close: function() {
         $(this).dialog('destroy').remove();
      }
   });

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
         _dialog.dialog('close');
         $('<div>' + data.message + '</div>').dialog({
            close: function() {
               $(this).dialog('destroy').remove();
            }
         });
      } else {
         var _markers = [];
         _marker = L.marker([data.lat, data.lng]);
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
};

var query = {};
function markMatch (text, term) {
   // Find where the match is
   var match = text.toUpperCase().indexOf(term.toUpperCase());

   var _result = $('<span></span>');

   // If there is no match, move on
   if (match < 0) {
      _result.append(text);
      return _result.html();
   }

   // Put in whatever text is before the match
   _result.text(text.substring(0, match));

   // Mark the match
   var _match = $('<span class=\'select2-rendered__match\'></span>');
   _match.text(text.substring(match, match + term.length));

   // Append the matching text
   _result.append(_match);

   // Put in whatever is after the match
   _result.append(text.substring(match + term.length));

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
      if (text.indexOf('>') !== -1 || text.indexOf('<') !== -1) {
         // escape text, if it contains chevrons (can already be escaped prior to this point :/)
         text = jQuery.fn.select2.defaults.defaults.escapeMarkup(result.text);
      };

      if (!result.id) {
         return text;
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
   // Data generated by ajax containing 'selection_text'
   if (selection.hasOwnProperty('selection_text')) {
      return selection.selection_text;
   }
   // Data generated with optgroups
   if (selection.element.parentElement.nodeName == 'OPTGROUP') {
      return selection.element.parentElement.getAttribute('label') + ' - ' + selection.text;
   }
   // Default text
   return selection.text;
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
}
