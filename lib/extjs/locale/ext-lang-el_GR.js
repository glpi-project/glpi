/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2009, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * Greek translation
 * By thesilentman (utf8 encoding)
 * 27 Apr 2008
 *
 * Changes since previous (second) Version:
 * + added Date.shortMonthNames 
 * + added Date.getShortMonthName 
 * + added Date.monthNumbers
 * + added Ext.grid.GroupingView
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Μεταφόρτωση δεδομένων...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} Επιλεγμένες σειρές";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Κλείστε το tab";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Το περιεχόμενο του πεδίου δεν είναι αποδεκτό";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Μεταφόρτωση δεδομένων...";
}

Date.monthNames = [
   "Ιανουάριος",
   "Φεβρουάριος",
   "Μάρτιος",
   "Απρίλιος",
   "Μάιος",
   "Ιούνιος",
   "Ιούλιος",
   "Αύγουστος",
   "Σεπτέμβριος",
   "Οκτώβριος",
   "Νοέμβριος",
   "Δεκέμβριος"
];

Date.shortMonthNames = [
   "Ιαν",
   "Φεβ",
   "Μάρ",
   "Απρ",
   "Μάι",
   "Ιού",
   "Ιού",
   "Αύγ",
   "Σεπ",
   "Οκτ",
   "Νοέ",
   "Δεκ"
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
   "Κυριακή",
   "Δευτέρα",
   "Τρίτη",
   "Τετάρτη",
   "Πέμπτη",
   "Παρασκευή",
   "Σάββατο"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Άκυρο",
      yes    : "Ναι",
      no     : "Όχι"
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
      todayText         : "Σήμερα",
      minText           : "Η Ημερομηνία είναι προγενέστερη από την παλαιότερη αποδεκτή",
      maxText           : "Η Ημερομηνία είναι μεταγενέστερη από την νεότερη αποδεκτή",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames  : Date.monthNames,
      dayNames    : Date.dayNames,
      nextText          : 'Επόμενος Μήνας (Control+Δεξί Βέλος)',
      prevText          : 'Προηγούμενος Μήνας (Control + Αριστερό Βέλος)',
      monthYearText     : 'Επιλογή Μηνός (Control + Επάνω/Κάτω Βέλος για μεταβολή ετών)',
      todayTip          : "{0} (ΠΛήκτρο Διαστήματος)",
      format            : "d/m/y"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Σελίδα",
      afterPageText  : "από {0}",
      firstText      : "Πρώτη Σελίδα",
      prevText       : "Προηγούμενη Σελίδα",
      nextText       : "Επόμενη Σελίδα",
      lastText       : "Τελευταία Σελίδα",
      refreshText    : "Ανανέωση",
      displayMsg     : "Εμφάνιση {0} - {1} από {2}",
      emptyMsg       : 'Δεν υπάρχουν δεδομένα'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Το μικρότερο αποδεκτό μήκος για το πεδίο είναι {0}",
      maxLengthText : "Το μεγαλύτερο αποδεκτό μήκος για το πεδίο είναι {0}",
      blankText     : "Το πεδίο είναι υποχρεωτικό",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Η μικρότερη τιμή του πεδίου είναι {0}",
      maxText : "Η μεγαλύτερη τιμή του πεδίου είναι {0}",
      nanText : "{0} δεν είναι αποδεκτός αριθμός"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Ανενεργό",
      disabledDatesText : "Ανενεργό",
      minText           : "Η ημερομηνία αυτού του πεδίου πρέπει να είναι μετά την {0}",
      maxText           : "Η ημερομηνία αυτού του πεδίου πρέπει να είναι πριν την {0}",
      invalidText       : "{0} δεν είναι έγκυρη ημερομηνία - πρέπει να είναι στη μορφή {1}",
      format            : "d/m/y"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Μεταφόρτωση δεδομένων...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Το πεδίο δέχεται μόνο διευθύνσεις Email σε μορφή "user@domain.com"',
      urlText      : 'Το πεδίο δέχεται μόνο URL σε μορφή "http:/'+'/www.domain.com"',
      alphaText    : 'Το πεδίο δέχεται μόνο χαρακτήρες και _',
      alphanumText : 'Το πεδίο δέχεται μόνο χαρακτήρες, αριθμούς και _'
   });
}

if(Ext.form.HtmlEditor){
   Ext.apply(Ext.form.HtmlEditor.prototype, {
        createLinkText : 'Δώστε τη διεύθυνση (URL) για το σύνδεσμο (link):',
        buttonTips : {
            bold : {
               title: 'Έντονα (Ctrl+B)',
               text: 'Κάνετε το προεπιλεγμένο κείμενο έντονο.',
               cls: 'x-html-editor-tip'
            },
            italic : {
               title: 'Πλάγια (Ctrl+I)',
               text: 'Κάνετε το προεπιλεγμένο κείμενο πλάγιο.',
               cls: 'x-html-editor-tip'
            },
            underline : {
               title: 'Υπογράμμιση (Ctrl+U)',
               text: 'Υπογραμμίζετε το προεπιλεγμένο κείμενο.',
               cls: 'x-html-editor-tip'
           },
           increasefontsize : {
               title: 'Μεγέθυνση κειμένου',
               text: 'Μεγαλώνετε τη γραμματοσειρά.',
               cls: 'x-html-editor-tip'
           },
           decreasefontsize : {
               title: 'Σμίκρυνση κειμένου',
               text: 'Μικραίνετε τη γραμματοσειρά.',
               cls: 'x-html-editor-tip'
           },
           backcolor : {
               title: 'Χρώμα Φόντου Κειμένου',
               text: 'Αλλάζετε το χρώμα στο φόντο του προεπιλεγμένου κειμένου.',
               cls: 'x-html-editor-tip'
           },
           forecolor : {
               title: 'Χρώμα Γραμματοσειράς',
               text: 'Αλλάζετε το χρώμα στη γραμματοσειρά του προεπιλεγμένου κειμένου.',               
               cls: 'x-html-editor-tip'
           },
           justifyleft : {
               title: 'Αριστερή Στοίχιση Κειμένου',
               text: 'Στοιχίζετε το κείμενο στα αριστερά.',
               cls: 'x-html-editor-tip'
           },
           justifycenter : {
               title: 'Κεντράρισμα Κειμένου',
               text: 'Στοιχίζετε το κείμενο στο κέντρο.',
               cls: 'x-html-editor-tip'
           },
           justifyright : {
               title: 'Δεξιά Στοίχιση Κειμένου',
               text: 'Στοιχίζετε το κείμενο στα δεξιά.',
               cls: 'x-html-editor-tip'
           },
           insertunorderedlist : {
               title: 'Εισαγωγή Λίστας Κουκίδων',
               text: 'Ξεκινήστε μια λίστα με κουκίδες.',
               cls: 'x-html-editor-tip'
           },
           insertorderedlist : {
               title: 'Εισαγωγή Λίστας Αρίθμησης',
               text: 'Ξεκινήστε μια λίστα με αρίθμηση.',
               cls: 'x-html-editor-tip'
           },
           createlink : {
               title: 'Hyperlink',
               text: 'Μετατρέπετε το προεπιλεγμένο κείμενο σε Link.',
               cls: 'x-html-editor-tip'
           },
           sourceedit : {
               title: 'Επεξεργασία Κώδικα',
               text: 'Μεταβαίνετε στη λειτουργία επεξεργασίας κώδικα.',
               cls: 'x-html-editor-tip'
           }
        }
   });
}


if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Αύξουσα ταξινόμηση",
      sortDescText : "Φθίνουσα ταξινόμηση",
      lockText     : "Κλείδωμα στήλης",
      unlockText   : "Ξεκλείδωμα στήλης",
      columnsText  : "Στήλες"
   });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
  emptyGroupText : '(Καμμία)',
  groupByText    : 'Ομαδοποίηση βάσει αυτού του πεδίου',
  showGroupsText : 'Να εμφανίζεται στις ομάδες'
  });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Όνομα",
      valueText  : "Περιεχόμενο",
      dateFormat : "m/d/Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Σύρετε για αλλαγή μεγέθους.",
      collapsibleSplitTip : "Σύρετε για αλλαγή μεγέθους. Διπλό κλικ για απόκρυψη."
   });
}

