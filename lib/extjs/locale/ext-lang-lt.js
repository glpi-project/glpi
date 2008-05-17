/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * Lithuanian Translations (UTF-8)
 * By Vladas Saulis, October 18, 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Kraunasi...</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
  Ext.grid.Grid.prototype.ddText = "{0} pažymėta";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "Uždaryti šią užsklandą";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "Šio lauko reikšmė neteisinga";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "Kraunasi...";
}

Date.monthNames = [
  "Saulis",
  "Vasaris",
  "Kovas",
  "Balandis",
  "Gegužė",
  "Birželis",
  "Liepa",
  "Rugpjūtis",
  "Rugsėjis",
  "Spalis",
  "Lapkritis",
  "Gruodis"
];

Date.getShortMonthName = function(month) {
  return [
    "Sau",
    "Vas",
    "Kov",
    "Bal",
    "Geg",
    "Bir",
    "Lie",
    "Rgp",
    "Rgs",
    "Spa",
    "Lap",
    "Grd"
    ];
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
  "Pirmadienis",
  "Antradienis",
  "Trečiadienis",
  "Ketvirtadienis",
  "Penktadienis",
  "Šeštadienis",
  "Sekmadienis"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "Gerai",
    cancel : "Atsisakyti",
    yes    : "Taip",
    no     : "Ne"
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
    todayText         : "Šiandien",
    minText           : "Ši data yra mažesnė už leistiną",
    maxText           : "Ši data yra didesnė už leistiną",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : 'Next Month (Control+Right)',
    prevText          : 'Previous Month (Control+Left)',
    monthYearText     : 'Choose a month (Control+Up/Down perėjimui tarp metų)',
    todayTip          : "{0} (Spacebar)",
    format            : "y-m-d",
    okText            : "&#160;Gerai&#160;",
    cancelText        : "Atsisaktyi",
    startDay          : 1
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "Puslapis",
    afterPageText  : "iš {0}",
    firstText      : "Pirmas puslapis",
    prevText       : "Ankstesnis pusl.",
    nextText       : "Kitas puslapis",
    lastText       : "Pakutinis pusl.",
    refreshText    : "Atnaujinti",
    displayMsg     : "Rodomi įrašai {0} - {1} iš {2}",
    emptyMsg       : 'Nėra duomenų'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "Minimalus šio lauko ilgis yra {0}",
    maxLengthText : "Maksimalus šio lauko ilgis yra {0}",
    blankText     : "Šis laukas yra reikalingas",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "Minimalus šio lauko ilgis yra {0}",
    maxText : "Maksimalus šio lauko ilgis yra {0}",
    nanText : "{0} yra neleistina reikšmė"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "Neprieinama",
    disabledDatesText : "Neprieinama",
    minText           : "Šiame lauke data turi būti didesnė už {0}",
    maxText           : "Šiame lauke data turi būti mažesnėė už {0}",
    invalidText       : "{0} yra neteisinga data - ji turi būti įvesta formatu {1}",
    format            : "y-m-d",
    altFormats        : "y-m-d|y/m/d|Y-m-d|m/d|m-d|md|ymd|Ymd|d|Y-m-d"
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "Kraunasi...",
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : 'Šiame lauke turi būti el.pašto adresas formatu "user@domain.com"',
    urlText      : 'Šiame lauke turi būti nuoroda (URL) formatu "http:/'+'/www.domain.com"',
    alphaText    : 'Šiame lauke gali būti tik raidės ir ženklas "_"',
    alphanumText : 'Šiame lauke gali būti tik raidės, skaičiai ir ženklas "_"'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Įveskite URL šiai nuorodai:',
    buttonTips : {
      bold : {
        title: 'Bold (Ctrl+B)',
        text: 'Teksto paryškinimas.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Italic (Ctrl+I)',
        text: 'Kursyvinis tekstas.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Underline (Ctrl+U)',
        text: 'Teksto pabraukimas.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Padidinti šriftą',
        text: 'Padidinti šrifto dydį.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Sumažinti šriftą',
        text: 'Sumažinti šrifto dydį.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Nuspalvinti teksto foną',
        text: 'Pakeisti teksto fono spalvą.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Teksto spalva',
        text: 'Pakeisti pažymėto teksto spalvą.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Išlyginti kairen',
        text: 'Išlyginti tekstą į kairę.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Centruoti tekstą',
        text: 'Centruoti tektą redaktoriaus lange.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Išlyginti dešinėn',
        text: 'Išlyginti tekstą į dešinę.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Paprastas sąrašas',
        text: 'Pradėti neorganizuotą sąrašą.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Numeruotas sąrašas',
        text: 'Pradėti numeruotą sąrašą.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Nuoroda',
        text: 'Padaryti pažymėta tekstą nuoroda.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Išeities tekstas',
        text: 'Persijungti į išeities teksto koregavimo režimą.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "Rūšiuoti didėjančia tvarka",
    sortDescText : "Rūšiuoti mažėjančia tvarka",
    lockText     : "Užfiksuoti stulpelį",
    unlockText   : "Atlaisvinti stulpelį",
    columnsText  : "Stulpeliai"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Nėra)',
    groupByText    : 'Grupuoti pagal šį lauką',
    showGroupsText : 'Rodyti grupėse'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "Pavadinimas",
    valueText  : "Reikšmė",
    dateFormat : "Y-m-d"
  });
}

if(Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "Patraukite juostelę.",
    collapsibleSplitTip : "Patraukite juostelę arba Paspauskite dvigubai kad paslėpti."
  });
}
