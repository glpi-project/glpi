/*
 * Italian translation
 * By eric_void
 * 04-10-2007, 11:25 AM
 * Updated by Federico Grilli 21/12/2007 
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Caricamento in corso...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} righe selezionate";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Chiudi pannello";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Valore non valido";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Caricamento in corso...";
}

Date.monthNames = [
   "Gennaio",
   "Febbraio",
   "Marzo",
   "Aprile",
   "Maggio",
   "Giugno",
   "Luglio",
   "Agosto",
   "Settembre",
   "Ottobre",
   "Novembre",
   "Dicembre"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  Gen : 0,
  Feb : 1,
  Mar : 2,
  Apr : 3,
  Mag : 4,
  Giu : 5,
  Lug : 6,
  Ago : 7,
  Set : 8,
  Ott : 9,
  Nov : 10,
  Dic : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
   "Domenica",
   "Luned\u00EC",
   "Marted\u00EC",
   "Mercoled\u00EC",
   "Gioved\u00EC",
   "Venerd\u00EC",
   "Sabato"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Annulla",
      yes    : "S\u00EC",
      no     : "No"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d/m/Y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Oggi",
      minText           : "Data precedente alla data minima",
      maxText           : "Data successiva alla data massima",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames		: Date.monthNames,
      dayNames			: Date.dayNames,
      nextText          : 'Mese successivo (Ctrl+Destra)',
      prevText          : 'Mese precedente (Ctrl+Sinistra)',
      monthYearText     : 'Scegli un mese (Ctrl+Su/Giu per cambiare anno)',
      todayTip          : "{0} (Barra spaziatrice)",
      format            : "d/m/y",
      cancelText		: "Annulla",
	  okText            : "&#160;OK&#160;",
	  startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Pagina",
      afterPageText  : "di {0}",
      firstText      : "Prima pagina",
      prevText       : "Pagina precedente",
      nextText       : "Pagina successiva",
      lastText       : "Ultima pagina",
      refreshText    : "Aggiorna",
      displayMsg     : "Record {0} - {1} di {2}",
      emptyMsg       : 'Nessun dato da mostrare'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "La lunghezza minima \u00E8 {0}",
      maxLengthText : "La lunghezza massima \u00E8 {0}",
      blankText     : "Campo obbligatorio",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Il valore minimo \u00E8 {0}",
      maxText : "Il valore massimo \u00E8 {0}",
      nanText : "{0} non \u00E8 un valore numerico corretto"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Disabilitato",
      disabledDatesText : "Disabilitato",
      minText           : "La data deve essere successiva al {0}",
      maxText           : "La data deve essere precedente al {0}",
      invalidText       : "{0} non \u00E8 una data valida. Deve essere nel formato {1}",
      format            : "d/m/y",
      altFormats        : "d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Caricamento in corso...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Il campo deve essere un indirizzo e-mail nel formato "user@domain.com"',
      urlText      : 'Il campo deve essere un indirizzo web nel formato "http:/'+'/www.domain.com"',
      alphaText    : 'Il campo deve contenere solo lettere e _',
      alphanumText : 'Il campo deve contenere solo lettere, numeri e _'
   });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Inserire un URL per il link:',
    buttonTips : {
      bold : {
        title: 'Grassetto (Ctrl+B)',
        text: 'Rende il testo selezionato in grassetto.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Corsivo (Ctrl+I)',
        text: 'Rende il testo selezionato in corsivo.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Sottolinea (Ctrl+U)',
        text: 'Sottolinea il testo selezionato.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Ingrandisci testo',
        text: 'Aumenta la dimensione del carattere.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Rimpicciolisci testo',
        text: 'Diminuisce la dimensione del carattere.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Colore evidenziatore testo',
        text: 'Modifica il colore di sfondo del testo selezionato.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Colore carattere',
        text: 'Modifica il colore del testo selezionato.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Allinea a sinistra',
        text: 'Allinea il testo a sinistra.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Centra',
        text: 'Centra il testo.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Allinea a destra',
        text: 'Allinea il testo a destra.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Elenco puntato',
        text: 'Elenco puntato.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Elenco numerato',
        text: 'Elenco numerato.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Collegamento',
        text: 'Trasforma il testo selezionato in un collegamanto.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Sorgente',
        text: 'Passa alla modalit\u00E0 editing del sorgente.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Ordinamento crescente",
      sortDescText : "Ordinamento decrescente",
      lockText     : "Blocca colonna",
      unlockText   : "Sblocca colonna",
      columnsText  : "Colonne"
   });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Nessun dato)',
    groupByText    : 'Raggruppa per questo campo',
    showGroupsText : 'Mostra nei gruppi'
  });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Nome",
      valueText  : "Valore",
      dateFormat : "j/m/Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Trascina per cambiare dimensioni.",
      collapsibleSplitTip : "Trascina per cambiare dimensioni. Doppio click per nascondere."
   });
}

