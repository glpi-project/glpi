/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * List compiled by mystix on the extjs.com forums.
 * Thank you Mystix!
 */
 
 /*  Slovak Translation by Michal Thomka
  *  14 April 2007
  */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Nahrávam...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} oznaèených riadkov";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Zavrie túto záloku";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Hodnota v tomto poli je nesprávna";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Nahrávam...";
}

Date.monthNames = [
   "Január",
   "Február",
   "Marec",
   "Apríl",
   "Máj",
   "Jún",
   "Júl",
   "August",
   "September",
   "Október",
   "November",
   "December"
];

Date.dayNames = [
   "NedeŸa",
   "Pondelok",
   "Utorok",
   "Streda",
   "tvrtok",
   "Piatok",
   "Sobota"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Zrui",
      yes    : "Áno",
      no     : "Nie"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "m/d/R");
   };
}


if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Dnes",
      minText           : "Tento dátum je mení ako minimálny moný dátum",
      maxText           : "Tento dátum je väèí ako maximálny moný dátum",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames        : Date.monthNames,
      dayNames          : Date.dayNames,
      nextText          : 'Ïalí Mesiac (Control+Doprava)',
      prevText          : 'Predch. Mesiac (Control+DoŸava)',
      monthYearText     : 'Vyberte Mesiac (Control+Hore/Dole pre posun rokov)',
      todayTip          : "{0} (Medzerník)",
      format            : "m/d/r"
   });
}


if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Strana",
      afterPageText  : "z {0}",
      firstText      : "Prvá Strana",
      prevText       : "Predch. Strana",
      nextText       : "Ïalia Strana",
      lastText       : "Posledná strana",
      refreshText    : "Obnovi",
      displayMsg     : "Zobrazujem {0} - {1} z {2}",
      emptyMsg       : 'iadne dáta'
   });
}


if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Minimálna dåka pre toto pole je {0}",
      maxLengthText : "Maximálna dåka pre toto pole je {0}",
      blankText     : "Toto pole je povinné",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Minimálna hodnota pre toto pole je {0}",
      maxText : "Maximálna hodnota pre toto pole je {0}",
      nanText : "{0} je nesprávne èíslo"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Zablokované",
      disabledDatesText : "Zablokované",
      minText           : "Dátum v tomto poli musí by a po {0}",
      maxText           : "Dátum v tomto poli musí by pred {0}",
      invalidText       : "{0} nie je správny dátum - musí by vo formáte {1}",
      format            : "m/d/r"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Nahrávam...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Toto pole musí by e-mailová adresa vo formáte "user@domain.com"',
      urlText      : 'Toto pole musí by URL vo formáte "http:/'+'/www.domain.com"',
      alphaText    : 'Toto poŸe moe obsahova iba písmená a znak _',
      alphanumText : 'Toto poŸe moe obsahova iba písmená,èísla a znak _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Zoradi vzostupne",
      sortDescText : "Zoradi zostupne",
      lockText     : "Zamknú ståpec",
      unlockText   : "Odomknú stŸpec",
      columnsText  : "Ståpce"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Názov",
      valueText  : "Hodnota",
      dateFormat : "m/j/Y"
   });
}

if(Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Potiahnite pre zmenu rozmeru",
      collapsibleSplitTip : "Potiahnite pre zmenu rozmeru. Dvojklikom schováte."
   });
}
