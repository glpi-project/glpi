/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
/**
 * Greek (Old Version) Translations by Vagelis
 * 03-June-2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Öüñôùóç...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} åðéëåãìÝíç(åò) ãñáììÞ(Ýò)";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Êëåßóôå áõôÞ ôçí êáñôÝëá";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Ç ôéìÞ óôï ðåäßï äåí åßíáé Ýãêõñç";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Öüñôùóç...";
}

Date.monthNames = [
   "ÉáíïõÜñéïò",
   "ÖåâñïõÜñéïò",
   "ÌÜñôéïò",
   "Áðñßëéïò",
   "ÌÜéïò",
   "Éïýíéïò",
   "Éïýëéïò",
   "Áýãïõóôïò",
   "ÓåðôÝìâñéïò",
   "Ïêôþâñéïò",
   "ÍïÝìâñéïò",
   "ÄåêÝìâñéïò"
];

Date.dayNames = [
   "ÊõñéáêÞ",
   "ÄåõôÝñá",
   "Ôñßôç",
   "ÔåôÜñôç",
   "ÐÝìðôç",
   "ÐáñáóêåõÞ",
   "ÓÜââáôï"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "ÅíôÜîåé",
      cancel : "Áêýñùóç",
      yes    : "Íáé",
      no     : "¼÷é"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "ì/ç/Å");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "ÓÞìåñá",
      minText           : "Ç çìåñïìçíßá áõôÞ åßíáé ðñéí ôçí ìéêñüôåñç çìåñïìçíßá",
      maxText           : "Ç çìåñïìçíßá áõôÞ åßíáé ìåôÜ ôçí ìåãáëýôåñç çìåñïìçíßá",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Åðüìåíïò ÌÞíáò (Control+Right)',
      prevText          : 'Ðñïçãïýìåíïò ÌÞíáò (Control+Left)',
      monthYearText     : 'ÅðéëÝîôå ÌÞíá (Control+Up/Down ãéá ìåôáêßíçóç óôá Ýôç)',
      todayTip          : "{0} (Spacebar)",
      format            : "ì/ç/Å"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Óåëßäá",
      afterPageText  : "áðü {0}",
      firstText      : "Ðñþôç óåëßäá",
      prevText       : "Ðñïçãïýìåíç óåëßäá",
      nextText       : "Åðüìåíç óåëßäá",
      lastText       : "Ôåëåõôáßá óåëßäá",
      refreshText    : "ÁíáíÝùóç",
      displayMsg     : "ÅìöÜíéóç {0} - {1} áðü {2}",
      emptyMsg       : 'Äåí âñÝèçêáí åããñáöÝò ãéá åìöÜíéóç'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Ôï åëÜ÷éóôï ìÝãåèïò ãéá áõôü ôï ðåäßï åßíáé {0}",
      maxLengthText : "Ôï ìÝãéóôï ìÝãåèïò ãéá áõôü ôï ðåäßï åßíáé {0}",
      blankText     : "Ôï ðåäßï áõôü åßíáé õðï÷ñåùôïêü",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Ç åëÜ÷éóôç ôéìÞ ãéá áõôü ôï ðåäßï åßíáé {0}",
      maxText : "Ç ìÝãéóôç ôéìÞ ãéá áõôü ôï ðåäßï åßíáé {0}",
      nanText : "{0} äåí åßíáé Ýãêõñïò áñéèìüò"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "ÁðåíåñãïðïéçìÝíï",
      disabledDatesText : "ÁðåíåñãïðïéçìÝíï",
      minText           : "Ç çìåñïìçíßá ó' áõôü ôï ðåäßï ðñÝðåé íá åßíáé ìåôÜ áðü {0}",
      maxText           : "Ç çìåñïìçíßá ó' áõôü ôï ðåäßï ðñÝðåé íá åßíáé ðñéí áðü {0}",
      invalidText       : "{0} äåí åßíáé Ýãêõñç çìåñïìçíßá - ðñÝðåé íá åßíáé ôçò ìïñöÞò {1}",
      format            : "ì/ç/Å"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Öüñôùóç...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Áõôü ôï ðåäßï ðñÝðåé íá åßíáé e-mail address ôçò ìïñöÞò "user@example.com"',
      urlText      : 'Áõôü ôï ðåäßï ðñÝðåé íá åßíáé ìéá äéåýèõíóç URL ôçò ìïñöÞò "http:/'+'/www.example.com"',
      alphaText    : 'Áõôü ôï ðåäßï ðñÝðåé íá ðåñéÝ÷åé ãñÜììáôá êáé _',
      alphanumText : 'Áõôü ôï ðåäßï ðñÝðåé íá ðåñéÝ÷åé ãñÜììáôá, áñéèìïýò êáé _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Áýîïõóá Ôáîéíüìçóç",
      sortDescText : "Öèßíïõóá Ôáîéíüìçóç",
      lockText     : "Êëåßäùìá óôÞëçò",
      unlockText   : "Îåêëåßäùìá óôÞëçò",
      columnsText  : "ÓôÞëåò"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "¼íïìá",
      valueText  : "ÔéìÞ",
      dateFormat : "ì/ç/Å"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Óýñåôå ãéá áëëáãÞ ìåãÝèïõò.",
      collapsibleSplitTip : "Óýñåôå ãéá áëëáãÞ ìåãÝèïõò. Double click ãéá áðüêñõøç."
   });
}
