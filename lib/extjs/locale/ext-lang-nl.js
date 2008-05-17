/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/*
 * List compiled by mystix on the extjs.com forums.
 * Thank you Mystix!
 *
 * Dutch Translations
 * by Ido Sebastiaan Bas van Oostveen (12 Oct 2007)
 */

/* Ext Core translations */
Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Bezig met laden...</div>';

/* Ext single string translations */
if(Ext.View){
    Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
    Ext.grid.Grid.prototype.ddText = "{0} geselecteerde rij(en)";
}

if(Ext.TabPanelItem){
    Ext.TabPanelItem.prototype.closeText = "Sluit dit tabblad";
}

if(Ext.form.Field){
    Ext.form.Field.prototype.invalidText = "De waarde in dit veld is onjuist";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Bezig met laden...";
}

/* Javascript month and days translations */
Date.monthNames = [
   "Januari",
   "Februari",
   "Maart",
   "April",
   "Mei",
   "Juni",
   "Juli",
   "Augustus",
   "September",
   "Oktober",
   "November",
   "December"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  Jan : 0,
  Feb : 1,
  Maa : 2,
  Apr : 3,
  Mei : 4,
  Jun : 5,
  Jul : 6,
  Aug : 7,
  Sep : 8,
  Okt : 9,
  Nov : 10,
  Dec : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
   "Zondag",
   "Maandag",
   "Dinsdag",
   "Woensdag",
   "Donderdag",
   "Vrijdag",
   "Zaterdag"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
    Ext.MessageBox.buttonText = {
       ok     : "OK",
       cancel : "Annuleren",
       yes    : "Ja",
       no     : "Nee"
    };
}

if(Ext.util.Format){
    Ext.util.Format.date = function(v, format){
       if(!v) return "";
       if(!(v instanceof Date)) v = new Date(Date.parse(v));
       return v.dateFormat(format || "d-m-y");
    };
}

if(Ext.DatePicker){
    Ext.apply(Ext.DatePicker.prototype, {
       todayText         : "Vandaag",
       minText           : "Deze datum is eerder dan de minimum datum",
       maxText           : "Deze datum is later dan de maximum datum",
       disabledDaysText  : "",
       disabledDatesText : "",
       monthNames	 : Date.monthNames,
       dayNames		 : Date.dayNames,
       nextText          : 'Volgende Maand (Control+Rechts)',
       prevText          : 'Vorige Maand (Control+Links)',
       monthYearText     : 'Kies een maand (Control+Omhoog/Beneden volgend/vorige jaar)',
       todayTip          : "{0} (Spatie)",
       format            : "d-m-y",
       okText		 : "&#160;OK&#160;",
       cancelText	 : "Annuleren",
       startDay          : 1
    });
}

if(Ext.PagingToolbar){
    Ext.apply(Ext.PagingToolbar.prototype, {
       beforePageText : "Pagina",
       afterPageText  : "van {0}",
       firstText      : "Eerste Pagina",
       prevText       : "Vorige Pagina",
       nextText       : "Volgende Pagina",
       lastText       : "Laatste Pagina",
       refreshText    : "Ververs",
       displayMsg     : "Getoond {0} - {1} van {2}",
       emptyMsg       : 'Geen gegeven om weer te geven'
    });
}

if(Ext.form.TextField){
    Ext.apply(Ext.form.TextField.prototype, {
       minLengthText : "De minimale lengte voor dit veld is {0}",
       maxLengthText : "De maximale lengte voor dit veld is {0}",
       blankText     : "Dit veld is verplicht",
       regexText     : "",
       emptyText     : null
    });
}

if(Ext.form.NumberField){
    Ext.apply(Ext.form.NumberField.prototype, {
       minText : "De minimale waarde voor dit veld is {0}",
       maxText : "De maximale waarde voor dit veld is {0}",
       nanText : "{0} is geen geldig getal"
    });
}

if(Ext.form.DateField){
    Ext.apply(Ext.form.DateField.prototype, {
       disabledDaysText  : "Uitgeschakeld",
       disabledDatesText : "Uitgeschakeld",
       minText           : "De datum in dit veld moet na {0} liggen",
       maxText           : "De datum in dit veld moet voor {0} liggen",
       invalidText       : "{0} is geen geldige datum - formaat voor datum is {1}",
       format            : "d-m-y",
       altFormats        : "d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d"
    });
}

if(Ext.form.ComboBox){
    Ext.apply(Ext.form.ComboBox.prototype, {
       loadingText       : "Bezig met laden...",
       valueNotFoundText : undefined
    });
}

if(Ext.form.VTypes){
    Ext.apply(Ext.form.VTypes, {
       emailText    : 'Dit veld moet een e-mail adres zijn in het formaat "gebruiker@domein.nl"',
       urlText      : 'Dit veld moet een URL zijn in het formaat "http:/'+'/www.domein.nl"',
       alphaText    : 'Dit veld mag alleen letters en _ bevatten',
       alphanumText : 'Dit veld mag alleen letters, cijfers en _ bevatten'
    });
}

if(Ext.form.HtmlEditor){
   Ext.apply(Ext.form.HtmlEditor.prototype, {
	createLinkText : 'Vul hier het Internet adres voor de link in:',
	buttonTips : {
            bold : {
                title: 'Vet (Ctrl+B)',
                text: 'Maak de geselecteerde tekst vet gedrukt.',
                cls: 'x-html-editor-tip'
            },
            italic : {
                title: 'Cursief (Ctrl+I)',
                text: 'Maak de geselecteerde tekst cursief.',
                cls: 'x-html-editor-tip'
            },
            underline : {
                title: 'Onderstrepen (Ctrl+U)',
                text: 'Onderstreep de geselecteerde tekst.',
                cls: 'x-html-editor-tip'
            },
            increasefontsize : {
                title: 'Tekst Vergroten',
                text: 'Vergroot het lettertype.',
                cls: 'x-html-editor-tip'
            },
            decreasefontsize : {
                title: 'Tekst Verkleinen',
                text: 'Verklein het lettertype.',
                cls: 'x-html-editor-tip'
            },
            backcolor : {
                title: 'Tekst Achtergrond Kleur',
                text: 'Verander de achtergrond kleur van de geselecteerde tekst.',
                cls: 'x-html-editor-tip'
            },
            forecolor : {
                title: 'Lettertype Kleur',
                text: 'Verander de kleur van de geselecteerde tekst.',
                cls: 'x-html-editor-tip'
            },
            justifyleft : {
                title: 'Tekst Links Uitlijnen',
                text: 'Lijn de tekst links uit.',
                cls: 'x-html-editor-tip'
            },
            justifycenter : {
                title: 'Tekst Centreren',
                text: 'Centreer de tekst in de editor.',
                cls: 'x-html-editor-tip'
            },
            justifyright : {
                title: 'Tekst Richts Uitlijnen',
                text: 'Lijn de tekst rechts uit.',
                cls: 'x-html-editor-tip'
            },
            insertunorderedlist : {
                title: 'Punten Lijst',
                text: 'Begin een ongenummerde lijst.',
                cls: 'x-html-editor-tip'
            },
            insertorderedlist : {
                title: 'Genummerde Lijst',
                text: 'Begin een genummerde lijst.',
                cls: 'x-html-editor-tip'
            },
            createlink : {
                title: 'Hyperlink',
                text: 'Maak van de geselecteerde tekst een hyperlink.',
                cls: 'x-html-editor-tip'
            },
            sourceedit : {
                title: 'Bron Aanpassen',
                text: 'Schakel modus over naar bron aanpassen.',
                cls: 'x-html-editor-tip'
            }
        }
    });
}

if(Ext.grid.GridView){
    Ext.apply(Ext.grid.GridView.prototype, {
       sortAscText  : "Sorteer Oplopend",
       sortDescText : "Sorteer Aflopend",
       lockText     : "Kolom Vastzetten",
       unlockText   : "Kolom Vrijgeven",
       columnsText  : "Kolommen"
    });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Geen)',
    groupByText    : 'Dit veld groeperen',
    showGroupsText : 'Zien als groepen'
  });
}

if(Ext.grid.PropertyColumnModel){
    Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
       nameText   : "Naam",
       valueText  : "Waarde",
       dateFormat : "Y-m-j"
    });
}

if(Ext.layout.BorderLayout.SplitRegion){
    Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
       splitTip            : "Sleep om grootte aan te passen.",
       collapsibleSplitTip : "Sleep om grootte aan te passen. Dubbel klikken om te verbergen."
    });
}
