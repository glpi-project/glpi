/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/*
 * France (Canadian) translation
 * By BernardChhun
 * 04-08-2007, 03:07 AM
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">En cours de chargement...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
   Ext.grid.Grid.prototype.ddText = "{0} ligne(s) sélectionné(s)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Fermer cette onglet";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "La valeur de ce champ est invalide";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "En cours de chargement...";
}

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

Date.dayNames = [
   "Dimanche",
   "Lundi",
   "Mardi",
   "Mercredi",
   "Jeudi",
   "Vendredi",
   "Samedi"
];

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
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d/m/Y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Aujourd'hui",
      minText           : "Cette date est plus petite que la date minimum",
      maxText           : "Cette date est plus grande que la date maximum",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Prochain mois (CTRL+Fléche droite)',
      prevText          : 'Mois précédent (CTRL+Fléche gauche)',
      monthYearText     : 'Choissisez un mois (CTRL+Fléche haut ou bas pour changer d\'année.)',
      todayTip          : "{0} (Barre d'espace)",
      format            : "d/m/y"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Page",
      afterPageText  : "de {0}",
      firstText      : "Première page",
      prevText       : "Page précédente",
      nextText       : "Prochaine page",
      lastText       : "Dernière page",
      refreshText    : "Recharger la page",
      displayMsg     : "Page courante {0} - {1} de {2}",
      emptyMsg       : 'Aucune donnée à afficher'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "La longueur minimum de ce champ est de {0} caractères",
      maxLengthText : "La longueur maximum de ce champ est de {0} caractères",
      blankText     : "Ce champ est obligatoire",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "La valeur minimum de ce champ doit être de {0}",
      maxText : "La valeur maximum de ce champ doit être de {0}",
      nanText : "{0} n'est pas un nombre valide"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Désactivé",
      disabledDatesText : "Désactivé",
      minText           : "La date de ce champ doit être avant le {0}",
      maxText           : "La date de ce champ doit être après le {0}",
      invalidText       : "{0} n'est pas une date valide - il doit être au format suivant: {1}",
      format            : "d/m/y"
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
      emailText    : 'Ce champ doit contenir un courriel et doit être sous ce format: "usager@domaine.com"',
      urlText      : 'Ce champ doit contenir une URL sous le format suivant: "http:/'+'/www.domaine.com"',
      alphaText    : 'Ce champ ne peut contenir que des lettres et le caractère souligné (_)',
      alphanumText : 'Ce champ ne peut contenir que des caractères alphanumériques ainsi que le caractère souligné (_)'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Tri ascendant",
      sortDescText : "Tri descendant",
      lockText     : "Verrouillé la colonne",
      unlockText   : "Déverrouillé la colonne",
      columnsText  : "Colonnes"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Propriété",
      valueText  : "Valeur",
      dateFormat : "d/m/Y"
   });
}

if(Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Cliquer et glisser pour redimensionner le panneau.",
      collapsibleSplitTip : "Cliquer et glisser pour redimensionner le panneau. Double-cliquer pour cacher le panneau."
   });
}
