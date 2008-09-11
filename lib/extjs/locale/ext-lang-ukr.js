/*
 * Ukrainian translation
 * By zlatko (utf-8 encoding)
 * 3 October 2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Триває завантаження...</div>';

if(Ext.View){
   Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
   Ext.grid.GridPanel.prototype.ddText = "{0} вибраних стрічок";
}

if(Ext.TabPanelItem){
   Ext.TabPanelItem.prototype.closeText = "Закрити цю вкладку";
}

if(Ext.form.Field){
   Ext.form.Field.prototype.invalidText = "Значення у цьому полі невірне";
}

if(Ext.LoadMask){
   Ext.LoadMask.prototype.msg = "Завантаження...";
}

Date.monthNames = [
   "Січень",
   "Лютий",
   "Березень",
   "Квітень",
   "Травень",
   "Червень",
   "Липень",
   "Серпень",
   "Вересень",
   "Жовтень",
   "Листопад",
   "Грудень"
];

Date.dayNames = [
   "Неділя",
   "Понеділок",
   "Вівторок",
   "Середа",
   "Четвер",
   "Пятниця",
   "Субота"
];

if(Ext.MessageBox){
   Ext.MessageBox.buttonText = {
      ok     : "OK",
      cancel : "Відміна",
      yes    : "Так",
      no     : "Ні"
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
      todayText         : "Сьогодні",
      minText           : "Ця дата менше мінімальної дати",
      maxText           : "Ця дата більше максимальної дати",
      disabledDaysText  : "",
      disabledDatesText : "",
      monthNames        : Date.monthNames,
      dayNames	        : Date.dayNames,
      nextText          : 'Наступний місяць (Control+Вправо)',
      prevText          : 'Попередній місяць (Control+Вліво)',
      monthYearText     : 'Вибір місяця (Control+Вверх/Вниз для вибору року)',
      todayTip          : "{0} (Пробіл)",
      format            : "d.m.y",
      okText            : "&#160;OK&#160;",
      cancelText        : "Відміна",
      startDay          : 1
   });
}

if(Ext.PagingToolbar){
   Ext.apply(Ext.PagingToolbar.prototype, {
      beforePageText : "Сторінка",
      afterPageText  : "з {0}",
      firstText      : "Перша сторінка",
      prevText       : "Попередня сторінка",
      nextText       : "Наступна сторінка",
      lastText       : "Остання сторінка",
      refreshText    : "Обновити",
      displayMsg     : "Відображаються записи з {0} по {1}, всього {2}",
      emptyMsg       : 'Дані для відображення відсутні'
   });
}

if(Ext.form.TextField){
   Ext.apply(Ext.form.TextField.prototype, {
      minLengthText : "Мінімальна довжина цього поля {0}",
      maxLengthText : "Максимальна довжина цього поля {0}",
      blankText     : "Це поле є обов’язковим для заповнення",
      regexText     : "",
      emptyText     : null
   });
}

if(Ext.form.NumberField){
   Ext.apply(Ext.form.NumberField.prototype, {
      minText : "Значення цього поля не може бути менше {0}",
      maxText : "Значення цього поля не може бути більше {0}",
      nanText : "{0} не являється числом"
   });
}

if(Ext.form.DateField){
   Ext.apply(Ext.form.DateField.prototype, {
      disabledDaysText  : "Не доступно",
      disabledDatesText : "Не доступно",
      minText           : "Дата в цьому полі повинна бути більше {0}",
      maxText           : "Дата в цьому полі повинна бути менше {0}",
      invalidText       : "{0} неправильна дата - дата повинна бути вказана у  форматі {1}",
      format            : "d.m.y"
   });
}

if(Ext.form.ComboBox){
   Ext.apply(Ext.form.ComboBox.prototype, {
      loadingText       : "Завантаження...",
      valueNotFoundText : undefined
   });
}

if(Ext.form.VTypes){
   Ext.apply(Ext.form.VTypes, {
      emailText    : 'Це поле повинно містити адресу електронної почти у форматі "user@domain.com"',
      urlText      : 'Це поле повинно містити URL у форматі "http:/'+'/www.domain.com"',
      alphaText    : 'Це поле повинно містити виключно латинські літери та символ підкреслення "_"',
      alphanumText : 'Це поле повинно містити виключно латинські літери, цифри та символ підкреслення "_"'
   });
}

if(Ext.form.HtmlEditor){
   Ext.apply(Ext.form.HtmlEditor.prototype, {
     createLinkText : 'Будь-ласка введіть адресу:',
     buttonTips : {
            bold : {
               title: 'Напівжирний (Ctrl+B)',
               text: 'Застосування напівжирного до виділеного тексту.',
               cls: 'x-html-editor-tip'
            },
            italic : {
               title: 'Курсив (Ctrl+I)',
               text: ' Застосування курсиву до виділеного тексту.',
               cls: 'x-html-editor-tip'
            },
            underline : {
               title: 'Підкреслений (Ctrl+U)',
               text: ' Застосування підкреслення до виділенного тексту.',
               cls: 'x-html-editor-tip'
           },
           increasefontsize : {
               title: 'Збільшити розмір',
               text: 'Збільшення розміру шрифта.',
               cls: 'x-html-editor-tip'
           },
           decreasefontsize : {
               title: 'Зменшити розмір',
               text: 'Зменшення розміру шрифта.',
               cls: 'x-html-editor-tip'
           },
           backcolor : {
               title: 'Заливка',
               text: 'Зміна кольору фону для виділеного тексту або абзацу.',
               cls: 'x-html-editor-tip'
           },
           forecolor : {
               title: 'Колір тексту',
               text: 'Зміна кольору тексту.',
               cls: 'x-html-editor-tip'
           },
           justifyleft : {
               title: 'Вирівняти текст по лівій границі',
               text: 'Вирівнювання тексту по лівій границі.',
               cls: 'x-html-editor-tip'
           },
           justifycenter : {
               title: 'Вирівняти текст по центру',
               text: 'Вирівнювання тексту по центру.',
               cls: 'x-html-editor-tip'
           },
           justifyright : {
               title: 'Вирівняти текст по правій границі',
               text: 'Вирівнювання тексту по правій границі.',
               cls: 'x-html-editor-tip'
           },
           insertunorderedlist : {
               title: 'Маркери',
               text: 'Почати маркований список.',
               cls: 'x-html-editor-tip'
           },
           insertorderedlist : {
               title: 'Нумерація',
               text: 'Почати нумернований список.',
               cls: 'x-html-editor-tip'
           },
           createlink : {
               title: 'Вставити гіперпосилання',
               text: 'Створення посилання із виділеного тексту.',
               cls: 'x-html-editor-tip'
           },
           sourceedit : {
               title: 'Вихідний код',
               text: 'Переключитись на вихідний код.',
               cls: 'x-html-editor-tip'
           }
        }
   });
}

if(Ext.grid.GridView){
   Ext.apply(Ext.grid.GridView.prototype, {
      sortAscText  : "Сортувати по зростанню",
      sortDescText : "Сортувати по спаданню",
      lockText     : "Закріпити колонку",
      unlockText   : "Зняти закріплення колонки",
      columnsText  : "Колонки"
   });
}

if(Ext.grid.PropertyColumnModel){
   Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
      nameText   : "Назва",
      valueText  : "Значення",
      dateFormat : "j.m.Y"
   });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
   Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
      splitTip            : "Тягніть для зміни розміру.",
      collapsibleSplitTip : "Тягніть для зміни розміру. Подвійний клік сховає панель."
   });
}

