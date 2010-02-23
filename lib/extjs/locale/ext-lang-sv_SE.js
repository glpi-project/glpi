/**
 * Swedish translation (utf8-encoding)
 * By Erik Andersson, Monator Technologies
 * 24 April 2007
 * Changed by Cariad, 29 July 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Laddar...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} markerade rad(er)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Stäng denna flik";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Värdet i detta fält är inte tillåtet";
}

if(Ext.LoadMask){
   Ext.LoadMask.prototype.msg = "Laddar...";
}

Date.monthNames = [
   "januari",
   "februari",
   "mars",
   "april",
   "maj",
   "juni",
   "juli",
   "augusti",
   "september",
   "oktober",
   "november",
   "december"
];

Date.dayNames = [
   "söndag",
   "måndag",
   "tisdag",
   "onsdag",
   "torsdag",
   "fredag",
   "lördag"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Avbryt",
      yes    : "Ja",
      no     : "Nej"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "Y-m-d");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Idag",
      minText           : "Detta datum inträffar före det tidigast tillåtna",
      maxText           : "Detta datum inträffar efter det senast tillåtna",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Nästa månad (Ctrl + högerpil)',
      prevText          : 'Föregående månad (Ctrl + vänsterpil)',
      monthYearText     : 'Välj en månad (Ctrl + uppåtpil/neråtpil för att ändra årtal)',
      todayTip          : "{0} (mellanslag)",
      format            : "Y-m-d",
      startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Sida",
      afterPageText  : "av {0}",
      firstText      : "Första sidan",
      prevText       : "Föregående sida",
      nextText       : "Nästa sida",
      lastText       : "Sista sidan",
      refreshText    : "Uppdatera",
      displayMsg     : "Visar {0} - {1} av {2}",
      emptyMsg       : 'Det finns ingen data att visa'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Minsta tillåtna längd för detta fält är {0}",
      maxLengthText : "Största tillåtna längd för detta fält är {0}",
      blankText     : "Detta fält är obligatoriskt",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Minsta tillåtna värde för detta fält är {0}",
      maxText : "Största tillåtna värde för detta fält är {0}",
      nanText : "{0} är inte ett tillåtet nummer"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Inaktiverad",
      disabledDatesText : "Inaktiverad",
      minText           : "Datumet i detta fält måste inträffa efter {0}",
      maxText           : "Datumet i detta fält måste inträffa före {0}",
      invalidText       : "{0} är inte ett tillåtet datum - datum ska anges i formatet {1}",
      format            : "Y-m-d"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Laddar...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Detta fält ska innehålla en e-post adress i formatet "användare@domän.se"',
      urlText      : 'Detta fält ska innehålla en länk (URL) i formatet "http:/'+'/www.domän.se"',
      alphaText    : 'Detta fält får bara innehålla bokstäver och "_"',
      alphanumText : 'Detta fält får bara innehålla bokstäver, nummer och "_"'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Sortera stigande",
      sortDescText : "Sortera fallande",
      lockText     : "Lås kolumn",
      unlockText   : "Lås upp kolumn",
      columnsText  : "Kolumner"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Namn",
      valueText  : "Värde",
      dateFormat : "Y-m-d"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Dra för att ändra storleken.",
      collapsibleSplitTip : "Dra för att ändra storleken. Dubbelklicka för att gömma."
   });
}
