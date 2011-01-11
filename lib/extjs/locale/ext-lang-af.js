/*!
 * Ext JS Library 3.3.0
 * Copyright(c) 2006-2010 Ext JS, Inc.
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/*
 * List compiled by mystix on the extjs.com forums.
 * Thank you Mystix!
 *
 * Afrikaans Translations
 * by Thys Meintjes (20 July 2007)
 */

/* Ext Core translations */
Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Besig om te laai...</div>';

/* Ext single string translations */
if(Ext.View){
    Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
    Ext.grid.GridPanel.prototype.ddText = "{0} geselekteerde ry(e)";
}

if(Ext.TabPanelItem){
    Ext.TabPanelItem.prototype.closeText = "Maak die oortjie toe";
}

if(Ext.form.Field){
    Ext.form.Field.prototype.invalidText = "Die waarde in hierdie veld is foutief";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Besig om te laai...";
}

/* Javascript month and days translations */
Date.monthNames = [
   "Januarie",
   "Februarie",
   "Maart",
   "April",
   "Mei",
   "Junie",
   "Julie",
   "Augustus",
   "September",
   "Oktober",
   "November",
   "Desember"
];

Date.dayNames = [
   "Sondag",
   "Maandag",
   "Dinsdag",
   "Woensdag",
   "Donderdag",
   "Vrydag",
   "Saterdag"
];

/* Ext components translations */
if(Ext.MessageBox){
    Ext.MessageBox.buttonText = {
       ok     : "OK",
       cancel : "Kanselleer",
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
       todayText         : "Vandag",
       minText           : "Hierdie datum is vroër as die minimum datum",
       maxText           : "Hierdie dataum is later as die maximum datum",
       disabledDaysText  : "",
       disabledDatesText : "",
       monthNames	 : Date.monthNames,
       dayNames		 : Date.dayNames,
       nextText          : 'Volgende Maand (Beheer+Regs)',
       prevText          : 'Vorige Maand (Beheer+Links)',
       monthYearText     : "Kies 'n maand (Beheer+Op/Af volgende/vorige jaar)",
       todayTip          : "{0} (Spasie)",
       format            : "d-m-y",
       startDay          : 0
    });
}

if(Ext.PagingToolbar){
    Ext.apply(Ext.PagingToolbar.prototype, {
       beforePageText : "Bladsy",
       afterPageText  : "van {0}",
       firstText      : "Eerste Bladsy",
       prevText       : "Vorige Bladsy",
       nextText       : "Volgende Bladsy",
       lastText       : "Laatste Bladsy",
       refreshText    : "Verfris",
       displayMsg     : "Wys {0} - {1} van {2}",
       emptyMsg       : 'Geen data om te wys nie'
    });
}

if(Ext.form.TextField){
    Ext.apply(Ext.form.TextField.prototype, {
       minLengthText : "Die minimum lengte van die veld is {0}",
       maxLengthText : "Die maximum lengte van die veld is {0}",
       blankText     : "Die veld is verpligtend",
       regexText     : "",
       emptyText     : null
    });
}

if(Ext.form.NumberField){
    Ext.apply(Ext.form.NumberField.prototype, {
       minText : "Die minimum waarde vir die veld is {0}",
       maxText : "Die maximum waarde vir die veld is {0}",
       nanText : "{0} is nie 'n geldige waarde nie"
    });
}

if(Ext.form.DateField){
    Ext.apply(Ext.form.DateField.prototype, {
       disabledDaysText  : "Afgeskakel",
       disabledDatesText : "Afgeskakel",
       minText           : "Die datum in hierdie veld moet na {0} wees",
       maxText           : "Die datum in hierdie veld moet voor {0} wees",
       invalidText       : "{0} is nie 'n geldige datum nie - datumformaat is {1}",
       format            : "d/m/y",
       altFormats        : "d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d"
    });
}

if(Ext.form.ComboBox){
    Ext.apply(Ext.form.ComboBox.prototype, {
       loadingText       : "Besig om te laai...",
       valueNotFoundText : undefined
    });
}

if(Ext.form.VTypes){
    Ext.apply(Ext.form.VTypes, {
       emailText    : "Hierdie veld moet 'n e-pos adres wees met die formaat 'gebruiker@domein.za'",
       urlText      : "Hierdie veld moet 'n URL wees me die formaat 'http:/'+'/www.domein.za'",
       alphaText    : 'Die veld mag alleenlik letters en _ bevat',
       alphanumText : 'Die veld mag alleenlik letters, syfers en _ bevat'
    });
}

if(Ext.grid.GridView){
    Ext.apply(Ext.grid.GridView.prototype, {
       sortAscText  : "Sorteer Oplopend",
       sortDescText : "Sorteer Aflopend",
       lockText     : "Vries Kolom",
       unlockText   : "Ontvries Kolom",
       columnsText  : "Kolomme"
    });
}

if(Ext.grid.PropertyColumnModel){
    Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
       nameText   : "Naam",
       valueText  : "Waarde",
       dateFormat : "Y-m-j"
    });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
    Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
       splitTip            : "Trek om grootte aan te pas.",
       collapsibleSplitTip : "Trek om grootte aan te pas. Dubbel-klik om weg te steek."
    });
}