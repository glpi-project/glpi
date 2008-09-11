/**
 *
 * Norwegian translation (Bokmål: no-NB)
 * By Tore Kjørsvik 21-January-2008
 *  
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Laster...</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
  Ext.grid.GridPanel.prototype.ddText = "{0} markert(e) rad(er)";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "Lukk denne fanen";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "Verdien i dette feltet er ugyldig";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "Laster...";
}

Date.monthNames = [
  "Januar",
  "Februar",
  "Mars",
  "April",
  "Mai",
  "Juni",
  "Juli",
  "August",
  "September",
  "Oktober",
  "November",
  "Desember"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  Jan : 0,
  Feb : 1,
  Mar : 2,
  Apr : 3,
  Mai : 4,
  Jun : 5,
  Jul : 6,
  Aug : 7,
  Sep : 8,
  Okt : 9,
  Nov : 10,
  Des : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
  "Søndag",
  "Mandag",
  "Tirsdag",
  "Onsdag",
  "Torsdag",
  "Fredag",
  "Lørdag"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "OK",
    cancel : "Avbryt",
    yes    : "Ja",
    no     : "Nei"
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
    todayText         : "I dag",
    minText           : "Denne datoen er før tidligste tillatte dato",
    maxText           : "Denne datoen er etter seneste tillatte dato",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames	      : Date.monthNames,
    dayNames		      : Date.dayNames,
    nextText          : 'Neste måned (Control+Pil Høyre)',
    prevText          : 'Forrige måned (Control+Pil Venstre)',
    monthYearText     : 'Velg en måned (Control+Pil Opp/Ned for å skifte år)',
    todayTip          : "{0} (Mellomrom)",
    format            : "d.m.y",
    okText            : "&#160;OK&#160;",
    cancelText        : "Avbryt",
    startDay          : 1
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "Side",
    afterPageText  : "av {0}",
    firstText      : "Første side",
    prevText       : "Forrige side",
    nextText       : "Neste side",
    lastText       : "Siste side",
    refreshText    : "Oppdater",
    displayMsg     : "Viser {0} - {1} av {2}",
    emptyMsg       : 'Ingen data å vise'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "Den minste lengden for dette feltet er {0}",
    maxLengthText : "Den største lengden for dette feltet er {0}",
    blankText     : "Dette feltet er påkrevd",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "Den minste verdien for dette feltet er {0}",
    maxText : "Den største verdien for dette feltet er {0}",
    nanText : "{0} er ikke et gyldig nummer"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "Deaktivert",
    disabledDatesText : "Deaktivert",
    minText           : "Datoen i dette feltet må være etter {0}",
    maxText           : "Datoen i dette feltet må være før {0}",
    invalidText       : "{0} er ikke en gyldig dato - den må være på formatet {1}",
    format            : "d.m.y",
    altFormats        : "d.m.Y|d/m/y|d/m/Y|d-m-y|d-m-Y|d.m|d/m|d-m|dm|dmy|dmY|Y-m-d|d"
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "Laster...",
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Dette feltet skal være en epost adresse på formatet "bruker@domene.no"',
      urlText      : 'Dette feltet skal være en link (URL) på formatet "http:/'+'/www.domene.no"',
      alphaText    : 'Dette feltet skal kun inneholde bokstaver og _',
      alphanumText : 'Dette feltet skal kun inneholde bokstaver, tall og _'
   });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Vennligst skriv inn URL for lenken:',
    buttonTips : {
      bold : {
        title: 'Fet (Ctrl+B)',
        text: 'Gjør den valgte teksten fet.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Kursiv (Ctrl+I)',
        text: 'Gjør den valgte teksten kursiv.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Understrek (Ctrl+U)',
        text: 'Understrek den valgte teksten.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Forstørr tekst',
        text: 'Gjør fontstørrelse større.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Forminsk tekst',
        text: 'Gjør fontstørrelse mindre.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Tekst markeringsfarge',
        text: 'Endre bakgrunnsfarge til den valgte teksten.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Font farge',
        text: 'Endre farge på den valgte teksten.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Venstrejuster tekst',
        text: 'Venstrejuster teksten.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Sentrer tekst',
        text: 'Sentrer teksten.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Høyrejuster tekst',
        text: 'Høyrejuster teksten.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Punktliste',
        text: 'Start en punktliste.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Nummerert liste',
        text: 'Start en nummerert liste.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Lenke',
        text: 'Gjør den valgte teksten til en lenke.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Rediger kilde',
        text: 'Bytt til kilderedigeringsvisning.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "Sorter stigende",
    sortDescText : "Sorter synkende",
    lockText     : "Lås kolonne",
    unlockText   : "Lås opp kolonne",
    columnsText  : "Kolonner"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Ingen)',
    groupByText    : 'Grupper etter dette feltet',
    showGroupsText : 'Vis i grupper'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "Navn",
    valueText  : "Verdi",
    dateFormat : "d.m.Y"
  });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "Dra for å endre størrelse.",
    collapsibleSplitTip : "Dra for å endre størrelse. Dobbelklikk for å skjule."
  });
}
