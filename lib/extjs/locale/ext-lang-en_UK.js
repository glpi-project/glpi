/**
 * List compiled by mystix on the extjs.com forums.
 * Thank you Mystix!
 *
 * English (UK) Translations
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Loading...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} selected row(s)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Close this tab";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "The value in this field is invalid";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Loading...";
}

Date.monthNames = [
   "January",
   "February",
   "March",
   "April",
   "May",
   "June",
   "July",
   "August",
   "September",
   "October",
   "November",
   "December"
];

Date.dayNames = [
   "Sunday",
   "Monday",
   "Tuesday",
   "Wednesday",
   "Thursday",
   "Friday",
   "Saturday"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Cancel",
      yes    : "Yes",
      no     : "No"
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
      todayText         : "Today",
      minText           : "This date is before the minimum date",
      maxText           : "This date is after the maximum date",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Next Month (Control+Right)',
      prevText          : 'Previous Month (Control+Left)',
      monthYearText     : 'Choose a month (Control+Up/Down to move years)',
      todayTip          : "{0} (Spacebar)",
      format            : "d/m/y"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Page",
      afterPageText  : "of {0}",
      firstText      : "First Page",
      prevText       : "Previous Page",
      nextText       : "Next Page",
      lastText       : "Last Page",
      refreshText    : "Refresh",
      displayMsg     : "Displaying {0} - {1} of {2}",
      emptyMsg       : 'No data to display'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "The minimum length for this field is {0}",
      maxLengthText : "The maximum length for this field is {0}",
      blankText     : "This field is required",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "The minimum value for this field is {0}",
      maxText : "The maximum value for this field is {0}",
      nanText : "{0} is not a valid number"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Disabled",
      disabledDatesText : "Disabled",
      minText           : "The date in this field must be after {0}",
      maxText           : "The date in this field must be before {0}",
      invalidText       : "{0} is not a valid date - it must be in the format {1}",
      format            : "d/m/y",
      altFormats        : "d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Loading...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'This field should be an e-mail address in the format "user@domain.com"',
      urlText      : 'This field should be a URL in the format "http:/'+'/www.domain.com"',
      alphaText    : 'This field should only contain letters and _',
      alphanumText : 'This field should only contain letters, numbers and _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Sort Ascending",
      sortDescText : "Sort Descending",
      lockText     : "Lock Column",
      unlockText   : "Unlock Column",
      columnsText  : "Columns"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Name",
      valueText  : "Value",
      dateFormat : "j/m/Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Drag to resize.",
      collapsibleSplitTip : "Drag to resize. Double click to hide."
   });
}

