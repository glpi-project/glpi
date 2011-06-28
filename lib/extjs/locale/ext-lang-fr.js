/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
﻿/*
 * France (France) translation
 * By Thylia
 * 09-11-2007, 02:22 PM
 * updated by disizben (22 Sep 2008)
 * updated by Thylia (20 Apr 2010)
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">En cours de chargement...</div>';

if(Ext.DataView){
   Ext.DataView.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} ligne{1} sélectionnée{1}";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "En cours de chargement...";
}

Date.shortMonthNames = [
   "Janv",
   "Févr",
   "Mars",
   "Avr",
   "Mai",
   "Juin",
   "Juil",
   "Août",
   "Sept",
   "Oct",
   "Nov",
   "Déc"
];

Date.getShortMonthName = function(month) {
  return Date.shortMonthNames[month];
};

Date.monthNames = [
   "Janvier",
   "Février",
   "Mars",
   "Avril",
   "Mai",
   "Juin",
   "Juillet",
   "Août",
   "Septembre",
   "Octobre",
   "Novembre",
   "Décembre"
];

Date.monthNumbers = {
  "Janvier" : 0,
  "Février" : 1,
  "Mars" : 2,
  "Avril" : 3,
  "Mai" : 4,
  "Juin" : 5,
  "Juillet" : 6,
  "Août" : 7,
  "Septembre" : 8,
  "Octobre" : 9,
  "Novembre" : 10,
  "Décembre" : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[Ext.util.Format.capitalize(name)];
};

Date.dayNames = [
   "Dimanche",
   "Lundi",
   "Mardi",
   "Mercredi",
   "Jeudi",
   "Vendredi",
   "Samedi"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

Date.parseCodes.S.s = "(?:er)";

Ext.override(Date, {
    getSuffix : function() {
        return (this.getDate() == 1) ? "er" : "";
    }
});

if(Ext.MessageBox){
    Ext.MessageBox.buttonText = {
        ok     : "OK",
        cancel : "Annuler",
        yes    : "Oui",
        no     : "Non"
    };
}

if(Ext.util.Format){
    Ext.util.Format.date = function(v, format){
        if(!v) return "";
        if(!Ext.isDate(v)) v = new Date(Date.parse(v));
        return v.dateFormat(format || "d/m/Y");
    };
    Ext.util.Format.plural = function(v, s, p) {
        return v + ' ' + (v <= 1 ? s : (p ? p : s + 's'));
    };
}

if(Ext.DatePicker){
    Ext.apply(Ext.DatePicker.prototype, {
        todayText         : "Aujourd'hui",
        minText           : "Cette date est antérieure à la date minimum",
        maxText           : "Cette date est postérieure à la date maximum",
        disabledDaysText  : "",
        disabledDatesText : "",
        monthNames        : Date.monthNames,
        dayNames          : Date.dayNames,
        nextText          : 'Mois suivant (CTRL+Flèche droite)',
        prevText          : "Mois précédent (CTRL+Flèche gauche)",
        monthYearText     : "Choisissez un mois (CTRL+Flèche haut ou bas pour changer d'année.)",
        todayTip          : "{0} (Barre d'espace)",
        okText            : "&#160;OK&#160;",
        cancelText        : "Annuler",
        format            : "d/m/y",
        startDay          : 1
    });
}

if(Ext.PagingToolbar){
    Ext.apply(Ext.PagingToolbar.prototype, {
        beforePageText : "Page",
        afterPageText  : "sur {0}",
        firstText      : "Première page",
        prevText       : "Page précédente",
        nextText       : "Page suivante",
        lastText       : "Dernière page",
        refreshText    : "Actualiser la page",
        displayMsg     : "Page courante {0} - {1} sur {2}",
        emptyMsg       : 'Aucune donnée à afficher'
    });
}

if(Ext.form.BasicForm){
    Ext.form.BasicForm.prototype.waitTitle = "Veuillez patienter...";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "La valeur de ce champ est invalide";
}

if(Ext.form.TextField){
    Ext.apply(Ext.form.TextField.prototype, {
        minLengthText : "La longueur minimum de ce champ est de {0} caractère(s)",
        maxLengthText : "La longueur maximum de ce champ est de {0} caractère(s)",
        blankText     : "Ce champ est obligatoire",
        regexText     : "",
        emptyText     : null
    });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      decimalSeparator : ",",
      decimalPrecision : 2,
      minText : "La valeur minimum de ce champ doit être de {0}",
      maxText : "La valeur maximum de ce champ doit être de {0}",
      nanText : "{0} n'est pas un nombre valide"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Désactivé",
      disabledDatesText : "Désactivé",
      minText           : "La date de ce champ ne peut être antérieure au {0}",
      maxText           : "La date de ce champ ne peut être postérieure au {0}",
      invalidText       : "{0} n'est pas une date valide - elle doit être au format suivant: {1}",
      format            : "d/m/y",
      altFormats        : "d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d",
      startDay          : 1
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "En cours de chargement...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Ce champ doit contenir une adresse email au format: "usager@example.com"',
      urlText      : 'Ce champ doit contenir une URL au format suivant: "http:/'+'/www.example.com"',
      alphaText    : 'Ce champ ne peut contenir que des lettres et le caractère souligné (_)',
      alphanumText : 'Ce champ ne peut contenir que des caractères alphanumériques ainsi que le caractère souligné (_)'
   });
}

if(Ext.form.HtmlEditor){
   Ext.apply(Ext.form.HtmlEditor.prototype, {
      createLinkText : "Veuillez entrer l'URL pour ce lien:",
          buttonTips : {
              bold : {
                  title: 'Gras (Ctrl+B)',
                  text: 'Met le texte sélectionné en gras.',
                  cls: 'x-html-editor-tip'
              },
              italic : {
                  title: 'Italique (Ctrl+I)',
                  text: 'Met le texte sélectionné en italique.',
                  cls: 'x-html-editor-tip'
              },
              underline : {
                  title: 'Souligné (Ctrl+U)',
                  text: 'Souligne le texte sélectionné.',
                  cls: 'x-html-editor-tip'
              },
              increasefontsize : {
                  title: 'Agrandir la police',
                  text: 'Augmente la taille de la police.',
                  cls: 'x-html-editor-tip'
              },
              decreasefontsize : {
                  title: 'Réduire la police',
                  text: 'Réduit la taille de la police.',
                  cls: 'x-html-editor-tip'
              },
              backcolor : {
                  title: 'Couleur de surbrillance',
                  text: 'Modifie la couleur de fond du texte sélectionné.',
                  cls: 'x-html-editor-tip'
              },
              forecolor : {
                  title: 'Couleur de police',
                  text: 'Modifie la couleur du texte sélectionné.',
                  cls: 'x-html-editor-tip'
              },
              justifyleft : {
                  title: 'Aligner à gauche',
                  text: 'Aligne le texte à gauche.',
                  cls: 'x-html-editor-tip'
              },
              justifycenter : {
                  title: 'Centrer',
                  text: 'Centre le texte.',
                  cls: 'x-html-editor-tip'
              },
              justifyright : {
                  title: 'Aligner à droite',
                  text: 'Aligner le texte à droite.',
                  cls: 'x-html-editor-tip'
              },
              insertunorderedlist : {
                  title: 'Liste à puce',
                  text: 'Démarre une liste à puce.',
                  cls: 'x-html-editor-tip'
              },
              insertorderedlist : {
                  title: 'Liste numérotée',
                  text: 'Démarre une liste numérotée.',
                  cls: 'x-html-editor-tip'
              },
              createlink : {
                  title: 'Lien hypertexte',
                  text: 'Transforme en lien hypertexte.',
                  cls: 'x-html-editor-tip'
              },
              sourceedit : {
                  title: 'Code source',
                  text: 'Basculer en mode édition du code source.',
                  cls: 'x-html-editor-tip'
              }
        }
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Tri croissant",
      sortDescText : "Tri décroissant",
      columnsText  : "Colonnes"
   });
}

if(Ext.grid.GroupingView){
   Ext.apply(Ext.grid.GroupingView.prototype, {
      emptyGroupText : '(Aucun)',
      groupByText    : 'Grouper par ce champ',
      showGroupsText : 'Afficher par groupes'
   });
}

if(Ext.grid.PropertyColumnModel){
    Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
        nameText   : "Propriété",
        valueText  : "Valeur",
        dateFormat : "d/m/Y",
        trueText   : "vrai",
        falseText  : "faux"
    });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Cliquer et glisser pour redimensionner le panneau.",
      collapsibleSplitTip : "Cliquer et glisser pour redimensionner le panneau. Double-cliquer pour le cacher."
   });
}

if(Ext.form.TimeField){
   Ext.apply(Ext.form.TimeField.prototype, {
      minText     : "L'heure de ce champ ne peut être antérieure à {0}",
      maxText     : "L'heure de ce champ ne peut être postérieure à {0}",
      invalidText : "{0} n'est pas une heure valide",
      format      : "H:i",
      altFormats  : "g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|h a|g a|g A|gi|hi|Hi|gia|hia|g|H"
   });
}

if(Ext.form.CheckboxGroup){
  Ext.apply(Ext.form.CheckboxGroup.prototype, {
    blankText : "Vous devez sélectionner au moins un élément dans ce groupe"
  });
}

if(Ext.form.RadioGroup){
  Ext.apply(Ext.form.RadioGroup.prototype, {
    blankText : "Vous devez sélectionner au moins un élément dans ce groupe"
  });
}
