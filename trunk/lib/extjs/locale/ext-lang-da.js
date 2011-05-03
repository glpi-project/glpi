/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
/*
 * Danish translation
 * By JohnF
 * 04-09-2007, 05:28 AM
 * 
 * Extended and modified by Karl Krukow, 
 * December, 2007.
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Henter...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} markerede rækker";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Luk denne fane";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Værdien i dette felt er ugyldig";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Henter...";
}

Date.monthNames = [
   "januar",
   "februar",
   "marts",
   "april",
   "maj",
   "juni",
   "juli",
   "august",
   "september",
   "oktober",
   "november",
   "december"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  Jan : 0,
  Feb : 1,
  Mar : 2,
  Apr : 3,
  May : 4,
  Jun : 5,
  Jul : 6,
  Aug : 7,
  Sep : 8,
  Oct : 9,
  Nov : 10,
  Dec : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
   "søndag",
   "mandag",
   "tirsdag",
   "onsdag",
   "torsdag",
   "fredag",
   "lørdag"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Fortryd",
      yes    : "Ja",
      no     : "Nej"
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
      todayText         : "I dag",
      minText           : "Denne dato er før den tidligst tilladte",
      maxText           : "Denne dato er senere end den senest tilladte",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames        : Date.monthNames,
      dayNames          : Date.dayNames,      
      nextText          : 'Næste måned (Ctrl + højre piltast)',
      prevText          : 'Forrige måned (Ctrl + venstre piltast)',
      monthYearText     : 'Vælg en måned (Ctrl + op/ned pil for at ændre årstal)',
      todayTip          : "{0} (mellemrum)",
      format            : "d/m/y",
      okText            : "&#160;OK&#160;",
      cancelText        : "Cancel",
      startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Side",
      afterPageText  : "af {0}",
      firstText      : "Første side",
      prevText       : "Forrige side",
      nextText       : "Næste side",
      lastText       : "Sidste side",
      refreshText    : "Opfrisk",
      displayMsg     : "Viser {0} - {1} af {2}",
      emptyMsg       : 'Der er ingen data at vise'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Minimum længden for dette felt er {0}",
      maxLengthText : "Maksimum længden for dette felt er {0}",
      blankText     : "Dette felt skal udfyldes",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Mindste-værdien for dette felt er {0}",
      maxText : "Maksimum-værdien for dette felt er {0}",
      nanText : "{0} er ikke et tilladt nummer"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Inaktiveret",
      disabledDatesText : "Inaktiveret",
      minText           : "Datoen i dette felt skal være efter {0}",
      maxText           : "Datoen i dette felt skal være før {0}",
      invalidText       : "{0} er ikke en tilladt dato - datoer skal angives i formatet {1}",
      format            : "d/m/y",
      altFormats        : "d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d",
      startDay          : 1
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Henter...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Dette felt skal være en email adresse i formatet "xxx@yyy.zzz"',
      urlText      : 'Dette felt skal være en URL i formatet "http:/'+'/xxx.yyy"',
      alphaText    : 'Dette felt kan kun indeholde bogstaver og "_" (understregning)',
      alphanumText : 'Dette felt kan kun indeholde bogstaver, tal og "_" (understregning)'
   });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Indtast URL:',
    buttonTips : {
      bold : {
        title: 'Fed (Ctrl+B)', //Can I change this to Ctrl+F?
        text: 'Formater det markerede tekst med fed.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Kursiv (Ctrl+I)',//Ctrl+K
        text: 'Formater det markerede tekst med kursiv.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Understreg (Ctrl+U)',
        text: 'Understreg det markerede tekst.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Forstør tekst',
        text: 'Forøg fontstørrelsen.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Formindsk tekst',
        text: 'Formindsk fontstørrelsen.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Farve for tekstfremhævelse',
        text: 'Skift baggrundsfarve for det markerede tekst.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Skriftfarve',
        text: 'Skift skriftfarve for det markerede tekst.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Juster venstre',
        text: 'Venstrestil tekst.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Centreret',
        text: 'Centrer tekst.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Juster højre',
        text: 'Højrestil tekst.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Punktopstilling',
        text: 'Påbegynd punktopstilling.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Nummereret opstilling',
        text: 'Påbegynd nummereret opstilling.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Hyperlink',
        text: 'Lav det markerede test til et hyperlink.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Kildetekstredigering',
        text: 'Skift til redigering af kildetekst.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Sortér stigende",
      sortDescText : "Sortér faldende",
      lockText     : "Lås kolonne",
      unlockText   : "Fjern lås fra kolonne",
      columnsText  : "Kolonner"
   });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Ingen)',
    groupByText    : 'Gruppér efter dette felt',
    showGroupsText : 'Vis i grupper' //should this be sort in groups?
  });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Navn",
      valueText  : "Værdi",
      dateFormat : "j/m/Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Træk for at ændre størrelsen.",
      collapsibleSplitTip : "Træk for at ændre størrelsen. Dobbelt-klik for at skjule."
   });
}
