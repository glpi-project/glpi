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
 *
 * Hungarian Translations (utf-8 encoded)
 * by Amon <amon@theba.hu> (27 Apr 2008)
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Betöltés...</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
  Ext.grid.GridPanel.prototype.ddText = "{0} kivÃ¡lasztott sor";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "Fül bezárása";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "HibÃ¡s Ã©rtÃ©k!";
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
  'Jan' : 0,
  'Feb' : 1,
  'MÃ¡r' : 2,
  'Ãpr' : 3,
  'MÃ¡j' : 4,
  'JÃºn' : 5,
  'JÃºl' : 6,
  'Aug' : 7,
  'Sze' : 8,
  'Okt' : 9,
  'Nov' : 10,
  'Dec' : 11
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
    return v.dateFormat(format || "Y m d");
  };
}

if(Ext.DatePicker){
  Ext.apply(Ext.DatePicker.prototype, {
    todayText         : "Mai nap",
    minText           : "A dátum korábbi a megengedettnél",
    maxText           : "A dÃ¡tum kÃ©sÅ‘bbi a megengedettnÃ©l",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : 'KÃ¶v. hÃ³nap (CTRL+Jobbra)',
    prevText          : 'ElÅ‘zÅ‘ hÃ³nap (CTRL+Balra)',
    monthYearText     : 'VÃ¡lassz hÃ³napot (Ã‰vvÃ¡lasztÃ¡s: CTRL+Fel/Le)',
    todayTip          : "{0} (Szóköz)",
    format            : "y-m-d",
    okText            : "&#160;OK&#160;",
    cancelText        : "Mégsem",
    startDay          : 0
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
    refreshText    : "FrissÃ­tÃ©s",
    displayMsg     : "{0} - {1} sorok láthatók a {2}-ból/ből",
    emptyMsg       : 'Nincs megjeleníthető adat'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "A mező tartalma legalább {0} hosszú kell legyen",
    maxLengthText : "A mezÅ‘ tartalma legfeljebb {0} hosszÃº lehet",
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
    invalidText       : "{0} nem megfelelÅ‘ dÃ¡tum - a helyes formÃ¡tum: {1}",
    format            : "Y m d",
    altFormats        : "Y-m-d|y-m-d|y/m/d|m/d|m-d|md|ymd|Ymd|d"
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
    emailText    : 'A mezÅ‘ email cÃ­met tartalmazhat, melynek formÃ¡tuma "felhasznÃ¡lÃ³@szolgÃ¡ltatÃ³.hu"',
    urlText      : 'A mezÅ‘ webcÃ­met tartalmazhat, melynek formÃ¡tuma "http:/'+'/www.weboldal.hu"',
    alphaText    : 'A mező csak betűket és aláhúzást (_) tartalmazhat',
    alphanumText : 'A mező csak betűket, számokat és aláhúzást (_) tartalmazhat'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Add meg a webcÃ­met:',
    buttonTips : {
      bold : {
        title: 'Félkövér (Ctrl+B)',
        text: 'FÃ©lkÃ¶vÃ©rrÃ© teszi a kijelÃ¶lt szÃ¶veget.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Dőlt (Ctrl+I)',
        text: 'DÅ‘ltÃ© teszi a kijelÃ¶lt szÃ¶veget.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Aláhúzás (Ctrl+U)',
        text: 'AlÃ¡hÃºzza a kijelÃ¶lt szÃ¶veget.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'SzÃ¶veg nagyÃ­tÃ¡s',
        text: 'NÃ¶veli a szÃ¶vegmÃ©retet.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'SzÃ¶veg kicsinyÃ­tÃ©s',
        text: 'CsÃ¶kkenti a szÃ¶vegmÃ©retet.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Háttérszín',
        text: 'A kijelÃ¶lt szÃ¶veg hÃ¡ttÃ©rszÃ­nÃ©t mÃ³dosÃ­tja.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'SzÃ¶vegszÃ­n',
        text: 'A kijelÃ¶lt szÃ¶veg szÃ­nÃ©t mÃ³dosÃ­tja.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Balra zÃ¡rt',
        text: 'Balra zÃ¡rja a szÃ¶veget.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'KÃ¶zÃ©pre zÃ¡rt',
        text: 'KÃ¶zÃ©pre zÃ¡rja a szÃ¶veget.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Jobbra zÃ¡rt',
        text: 'Jobbra zÃ¡rja a szÃ¶veget.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Felsorolás',
        text: 'FelsorolÃ¡st kezd.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'SzÃ¡mozÃ¡s',
        text: 'SzÃ¡mozott listÃ¡t kezd.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Hiperlink',
        text: 'A kijelÃ¶lt szÃ¶veget linkkÃ© teszi.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'ForrÃ¡s nÃ©zet',
        text: 'ForrÃ¡s nÃ©zetbe kapcsol.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "Növekvő rendezés",
    sortDescText : "Csökkenő rendezés",
    lockText     : "Oszlop zÃ¡rolÃ¡s",
    unlockText   : "Oszlop feloldÃ¡s",
    columnsText  : "Oszlopok"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Nincs)',
    groupByText    : 'Oszlop szerint csoportosÃ­tÃ¡s',
    showGroupsText : 'Csoportos nÃ©zet'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "Név",
    valueText  : "Érték",
    dateFormat : "Y m j"
  });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "Átméretezés húzásra.",
    collapsibleSplitTip : "Átméretezés húzásra. Eltüntetés duplaklikk."
  });
}
