/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * Translation by Lucian Lature 04-24-2007
 * Romanian Translations
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Încărcare...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} rând(uri) selectate";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Închide acest tab";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Valoarea acestui câmp este invalidă";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Încărcare...";
}

Date.monthNames = [
   "Ianuarie",
   "Februarie",
   "Martie",
   "Aprilie",
   "Mai",
   "Iunie",
   "Iulie",
   "August",
   "Septembrie",
   "Octombrie",
   "Noiembrie",
   "Decembrie"
];

Date.dayNames = [
   "Duminică",
   "Luni",
   "Marţi",
   "Miercuri",
   "Joi",
   "Vineri",
   "Sâmbătă"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Renunţă",
      yes    : "Da",
      no     : "Nu"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d-m-Y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Astăzi",
      minText           : "Această zi este înaintea datei de început",
      maxText           : "Această zi este după ultimul termen",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Următoarea lună (Control+Right)',
      prevText          : 'Luna anterioară (Control+Left)',
      monthYearText     : 'Alege o lună (Control+Up/Down pentru a parcurge anii)',
      todayTip          : "{0} (Spacebar)",
      format            : "d-m-y"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Pagina",
      afterPageText  : "din {0}",
      firstText      : "Prima pagină",
      prevText       : "Pagina precedentă",
      nextText       : "Următoarea pagină",
      lastText       : "Ultima pagină",
      refreshText    : "Reîmprospătare",
      displayMsg     : "Afişează {0} - {1} din {2}",
      emptyMsg       : 'Nu sunt date de afişat'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Lungimea minimă pentru acest câmp este de {0}",
      maxLengthText : "Lungimea maximă pentru acest câmp este {0}",
      blankText     : "Acest câmp este obligatoriu",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Valoarea minimă permisă a acestui câmp este {0}",
      maxText : "Valaorea maximă permisă a acestui câmp este {0}",
      nanText : "{0} nu este un număr valid"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Inactiv",
      disabledDatesText : "Inactiv",
      minText           : "Data acestui câmp trebuie să fie după {0}",
      maxText           : "Data acestui câmp trebuie sa fie înainte de {0}",
      invalidText       : "{0} nu este o dată validă - trebuie să fie în formatul {1}",
      format            : "d-m-y"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Încărcare...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Acest câmp trebuie să conţină o adresă de e-mail în formatul "user@domain.com"',
      urlText      : 'Acest câmp trebuie să conţină o adresă URL în formatul "http:/'+'/www.domain.com"',
      alphaText    : 'Acest câmp trebuie să conţină doar litere şi _',
      alphanumText : 'Acest câmp trebuie să conţină doar litere, cifre şi _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Sortare ascendentă",
      sortDescText : "Sortare descendentă",
      lockText     : "Blochează coloana",
      unlockText   : "Deblochează coloana",
      columnsText  : "Coloane"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Nume",
      valueText  : "Valoare",
      dateFormat : "m/j/Y"
   });
}

if(Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Trage pentru redimensionare.",
      collapsibleSplitTip : "Trage pentru redimensionare. Dublu-click pentru ascundere."
   });
}