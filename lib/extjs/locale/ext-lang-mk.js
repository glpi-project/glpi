/*
 * Macedonia translation
 * By PetarD petar.dimitrijevic@vorteksed.com.mk (utf8 encoding)
 * 23 April 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Вчитувам...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} избрани редици";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Затвори tab";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Вредноста во ова поле е невалидна";
}

if(Ext.LoadMask){
    Ext.LoadMask.prototype.msg = "Вчитувам...";
}

Date.monthNames = [
   "Јануари",
   "Февруари",
   "Март",
   "Април",
   "Мај",
   "Јуни",
   "Јули",
   "Август",
   "Септември",
   "Октомври",
   "Ноември",
   "Декември"
];

Date.dayNames = [
   "Недела",
   "Понеделник",
   "Вторник",
   "Среда",
   "Четврток",
   "Петок",
   "Сабота"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "Потврди",
      cancel : "Поништи",
      yes    : "Да",
      no     : "Не"
   };
}

if(Ext.util.Format){
   Ext.util.Format.date = function(v, format){
      if(!v) return "";
      if(!(v instanceof Date)) v = new Date(Date.parse(v));
      return v.dateFormat(format || "d.m.y");
   };
}

if(Ext.DatePicker){
   Ext.apply(Ext.DatePicker.prototype, {
      todayText         : "Денеска",
      minText           : "Овој датум е пред најмалиот датум",
      maxText           : "Овој датум е пред најголемиот датум",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames	: Date.monthNames,
      dayNames		: Date.dayNames,
      nextText          : 'Следен месец (Control+Стрелка десно)',
      prevText          : 'Претходен месец (Control+Стрелка лево)',
      monthYearText     : 'Изберете месец (Control+Стрелка горе/Стрелка десно за менување година)',
      todayTip          : "{0} (Spacebar)",
      format            : "d.m.y"
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Страница",
      afterPageText  : "од {0}",
      firstText      : "Прва Страница",
      prevText       : "Претходна Страница",
      nextText       : "Следна Страница",
      lastText       : "Последна Страница",
      refreshText    : "Освежи",
      displayMsg     : "Прикажувам {0} - {1} од {2}",
      emptyMsg       : 'Нема податоци за приказ'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Минималната должина за ова поле е {0}",
      maxLengthText : "Максималната должина за ова поле е {0}",
      blankText     : "Податоците во ова поле се потребни",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Минималната вредност за ова поле е {0}",
      maxText : "Максималната вредност за ова поле е {0}",
      nanText : "{0} не е валиден број"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Неактивно",
      disabledDatesText : "Неактивно",
      minText           : "Датумот во ова поле мора да биде пред {0}",
      maxText           : "Датумот во ова поле мора да биде по {0}",
      invalidText       : "{0} не е валиден датум - мора да биде во формат {1}",
      format            : "d.m.y"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Вчитувам...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Ова поле треба да биде e-mail адреса во формат "user@example.com"',
      urlText      : 'Ова поле треба да биде URL во формат "http:/'+'/www.example.com"',
      alphaText    : 'Ова поле треба да содржи само букви и _',
      alphanumText : 'Ова поле треба да содржи само букви, бројки и _'
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Сортирај Растечки",
      sortDescText : "Сортирај Опаѓачки",
      lockText     : "Заклучи Колона",
      unlockText   : "Отклучи колона",
      columnsText  : "Колони"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Име",
      valueText  : "Вредност",
      dateFormat : "m.d.Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Повлечете за менување на големината.",
      collapsibleSplitTip : "Повлечете за менување на големината. Дупли клик за криење."
   });
}