/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
/**
 * Latvian Translations
 * By salix 17 April 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Notiek ielāde...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} iezīmētu rindu";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Aizver šo zīmni";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Vērtība šajā laukā nav pareiza";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Ielādē...";
}

Date.monthNames = [
   "Janvāris",
   "Februāris",
   "Marts",
   "Aprīlis",
   "Maijs",
   "Jūnijs",
   "Jūlijs",
   "Augusts",
   "Septembris",
   "Oktobris",
   "Novembris",
   "Decembris"
];

Date.dayNames = [
   "Svētdiena",
   "Pirmdiena",
   "Otrdiena",
   "Trešdiena",
   "Ceturtdiena",
   "Piektdiena",
   "Sestdiena"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "Labi",
      cancel : "Atcelt",
      yes    : "Jā",
      no     : "Nē"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d.m.Y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Šodiena",
      minText           : "Norādītais datums ir mazāks par minimālo datumu",
      maxText           : "Norādītais datums ir lielāks par maksimālo datumu",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Nākamais mēnesis (Control+pa labi)',
      prevText          : 'Iepriekšējais mēnesis (Control+pa kreisi)',
      monthYearText     : 'Mēneša izvēle (Control+uz augšu/uz leju lai pārslēgtu gadus)',
      todayTip          : "{0} (Tukšumzīme)",
      format            : "d.m.Y",
      startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Lapa",
      afterPageText  : "no {0}",
      firstText      : "Pirmā lapa",
      prevText       : "iepriekšējā lapa",
      nextText       : "Nākamā lapa",
      lastText       : "Pēdējā lapa",
      refreshText    : "Atsvaidzināt",
      displayMsg     : "Rāda no {0} līdz {1} ierakstiem, kopā {2}",
      emptyMsg       : 'Nav datu, ko parādīt'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Minimālais garums šim laukam ir {0}",
      maxLengthText : "Maksimālais garums šim laukam ir {0}",
      blankText     : "Šis ir obligāts lauks",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Minimālais garums šim laukam ir  {0}",
      maxText : "Maksimālais garums šim laukam ir  {0}",
      nanText : "{0} nav pareizs skaitlis"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Atspējots",
      disabledDatesText : "Atspējots",
      minText           : "Datumam šajā laukā jābūt lielākam kā {0}",
      maxText           : "Datumam šajā laukā jābūt mazākam kā {0}",
      invalidText       : "{0} nav pareizs datums - tam jābūt šādā formātā: {1}",
      format            : "d.m.Y",
      startDay          : 1
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Ielādē...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Šajā laukā jāieraksta e-pasta adrese formātā "lietotās@domēns.lv"',
      urlText      : 'Šajā laukā jāieraksta URL formātā "http:/'+'/www.domēns.lv"',
      alphaText    : 'Šis lauks drīkst saturēt tikai burtus un _ zīmi',
      alphanumText : 'Šis lauks drīkst saturēt tikai burtus, ciparus un _ zīmi'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Kārtot pieaugošā secībā",
      sortDescText : "Kārtot dilstošā secībā",
      lockText     : "Noslēgt kolonnu",
      unlockText   : "Atslēgt kolonnu",
      columnsText  : "Kolonnas"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Nosaukums",
      valueText  : "Vērtība",
      dateFormat : "j.m.Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Velc, lai mainītu izmēru.",
      collapsibleSplitTip : "Velc, lai mainītu izmēru. Dubultklikšķis noslēpj apgabalu."
   });
}
