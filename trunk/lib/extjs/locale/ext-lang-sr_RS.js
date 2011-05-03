/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
﻿/*
 * Serbian Cyrillic Translation
 * by Čolovic Vladan (cyrillic, utf8 encoding)
 * sr_RS (ex: sr_CS, sr_YU)
 * 12 May 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Учитавам...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} изабраних редова";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Затвори ову »картицу«";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Унешена вредност није правилна";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Учитавам...";
}

Date.monthNames = [
   "Јануар",
   "Фебруар",
   "Март",
   "Април",
   "Мај",
   "Јун",
   "Јул",
   "Август",
   "Септембар",
   "Октобар",
   "Новембар",
   "Децембар"
];

Date.dayNames = [
   "Недеља",
   "Понедељак",
   "Уторак",
   "Среда",
   "Четвртак",
   "Петак",
   "Субота"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "У реду",
      cancel : "Одустани",
      yes    : "Да",
      no     : "Не"
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
      todayText         : "Данас",
      minText           : "Датум је испред најмањег дозвољеног датума",
      maxText           : "Датум је након највећег дозвољеног датума",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Следећи месец (Control+Десно)',
      prevText          : 'Претходни месец (Control+Лево)',
      monthYearText     : 'Изаберите месец (Control+Горе/Доле за избор године)',
      todayTip          : "{0} (Размакница)",
      format            : "d.m.y",
      startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Страна",
      afterPageText  : "од {0}",
      firstText      : "Прва страна",
      prevText       : "Претходна страна",
      nextText       : "Следећа страна",
      lastText       : "Последња страна",
      refreshText    : "Освежи",
      displayMsg     : "Приказана {0} - {1} од {2}",
      emptyMsg       : 'Немам шта приказати'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Минимална дужина овог поља је {0}",
      maxLengthText : "Максимална дужина овог поља је {0}",
      blankText     : "Поље је обавезно",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Минимална вредност у пољу је {0}",
      maxText : "Максимална вредност у пољу је {0}",
      nanText : "{0} није правилан број"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Пасивно",
      disabledDatesText : "Пасивно",
      minText           : "Датум у овом пољу мора бити након {0}",
      maxText           : "Датум у овом пољу мора бити пре {0}",
      invalidText       : "{0} није правилан датум - захтевани облик је {1}",
      format            : "d.m.y",
      startDay          : 1
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Учитавам...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Ово поље прихвата e-mail адресу искључиво у облику "korisnik@domen.com"',
      urlText      : 'Ово поље прихвата URL адресу искључиво у облику "http:/'+'/www.domen.com"',
      alphaText    : 'Ово поље може садржати искључиво слова и знак _',
      alphanumText : 'Ово поље може садржати само слова, бројеве и знак _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Растући редослед",
      sortDescText : "Опадајући редослед",
      lockText     : "Закључај колону",
      unlockText   : "Откључај колону",
      columnsText  : "Колоне"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Назив",
      valueText  : "Вредност",
      dateFormat : "d.m.Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Повући за измену величине.",
      collapsibleSplitTip : "Повући за измену величине. Двоструки клик за сакривање."
   });
}
