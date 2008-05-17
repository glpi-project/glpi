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
/*
 * Hungarian translation
 * By amon  <amon@theba.hu> (utf-8 encoded)
 * 09 February 2008
 */
 
Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Betöltés...</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
  Ext.grid.Grid.prototype.ddText = "{0} kiválasztott sor";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "Fül bezárása";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "A mezőben lévő adat nem megfelelő";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "Betöltés...";
}

Date.monthNames = [
  "Január",
  "Február",
  "Március",
  "Április",
  "Május",
  "Június",
  "Július",
  "Augusztus",
  "Szeptember",
  "Október",
  "November",
  "December"
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
  "Vasárnap",
  "Hétfő",
  "Kedd",
  "Szerda",
  "Csütörtök",
  "Péntek",
  "Szombat"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "OK",
    cancel : "Mégsem",
    yes    : "Igen",
    no     : "Nem"
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
    todayText         : "Mai nap",
    minText           : "A dátum korábbi a megengedettnél",
    maxText           : "A dárum későbbi a megengedettnél",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : 'Köv. hónap (Ctrl+Jobbra)',
    prevText          : 'Előző hónap (Ctrl+Balra)',
    monthYearText     : 'Válassz hónapot (Évválasztás: Ctrl+Fel/Le)',
    todayTip          : "{0} (Szóköz)",
    format            : "Y-m-d",
    okText            : "&#160;OK&#160;",
    cancelText        : "Mégsem",
    startDay          : 1
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "Oldal",
    afterPageText  : "a {0}-ból/ből",
    firstText      : "Első oldal",
    prevText       : "Előző oldal",
    nextText       : "Következő oldal",
    lastText       : "Utolsó oldal",
    refreshText    : "Frissít",
    displayMsg     : "{0} - {1} sorok láthatók a {2}-ból/ből",
    emptyMsg       : 'Nincs megjeleníthető adat'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "A mező tartalma legalább {0} hosszú kell legyen",
    maxLengthText : "A mező tartalma nem lehet hosszabb {0}-nál/nél",
    blankText     : "Kötelezően kitöltendő mező",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "A mező tartalma nem lehet kissebb, mint {0}",
    maxText : "A mező tartalma nem lehet nagyobb, mint {0}",
    nanText : "{0} nem szám"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "Nem választható",
    disabledDatesText : "Nem választható",
    minText           : "A dátum nem lehet korábbi, mint {0}",
    maxText           : "A dátum nem lehet későbbi, mint {0}",
    invalidText       : "{0} nem megfelelő dátum - a megfelelő formátum {1}",
    format            : "y-m-d",
    altFormats        : "y m d|y. m. d.|m d|m-d|md|ymd|Ymd|d|Y-m-d"
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "Betöltés...",
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : 'A mezőbe e-mail címet kell írni ebben a formátumban: "felhasználó@szerver.hu"',
    urlText      : 'A mezőbe webcímet kell írni ebben a formátumban: "http:/'+'/www.weboldal.hu"',
    alphaText    : 'A mező csak betűket és aláhúzást (_) tartalmazhat',
    alphanumText : 'A mező csak betűket, számokat és aláhúzást (_) tartalmazhat'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Kérlek add meg a webcímet:',
    buttonTips : {
      bold : {
        title: 'Félkövér (Ctrl+B)',
        text: 'Félkövérré teszi a szöveget.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Dőlt (Ctrl+I)',
        text: 'Dőltté teszi a szöveget.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Aláhúzás (Ctrl+U)',
        text: 'Aláhúzza a szöveget.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Betűméret növlés',
        text: 'Növeli a szöveg betűméretét.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Betűméret csökkentés',
        text: 'Csökkenti a szöveg betűméretét.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Háttérszín',
        text: 'A kijelölt szöveg háttérszínét változtatja meg.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Betűszín',
        text: 'A kijelölt szöveg betűszínét változtatja meg.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Balra igazít',
        text: 'A szöveget balra igazítja.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Középre igazít',
        text: 'A szöveget középre igazítja.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Jobbra igazít',
        text: 'A szöveget jobbra igazítja.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Felsorolás',
        text: 'Felsorolást nyit.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Számozott lista',
        text: 'Számozott listát nyit.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Hiperlink',
        text: 'Hiperlinkké teszi a kijelölt szöveget.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Forráskód',
        text: 'Forráskód üzemmódba vált.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "Növekvő rendezés",
    sortDescText : "Csökkenő rendezés",
    lockText     : "Oszlop zárolása",
    unlockText   : "Oszlop felengedése",
    columnsText  : "Oszlopok"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(nincs)',
    groupByText    : 'Mező szerint csoportosít',
    showGroupsText : 'Csoportosított megjelenítés'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "Név",
    valueText  : "Érték",
    dateFormat : "Y j m"
  });
}

if(Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "Átméretezés húzásra.",
    collapsibleSplitTip : "Átméretezés húzásra. Eltüntetés duplaklikk."
  });
}
