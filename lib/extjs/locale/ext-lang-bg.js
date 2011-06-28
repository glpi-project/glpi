/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
/**
 * Bulgarian Translation
 *
 * By Георги Костадинов, Калгари, Канада
 * 10 October 2007
 * By Nedko Penev 
 * 26 October 2007
 *
 * (utf-8 encoding)
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Зареждане...</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
  Ext.grid.GridPanel.prototype.ddText = "{0} избрани колони";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "Затвори таб";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "Невалидна стойност на полето";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "Зареждане...";
}

Date.monthNames = [
  "Януари",
  "Февруари",
  "Март",
  "Април",
  "Май",
  "Юни",
  "Юли",
  "Август",
  "Септември",
  "Октомври",
  "Ноември",
  "Декември"
];

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

Date.dayNames = [
  "Неделя",
  "Понеделник",
  "Вторник",
  "Сряда",
  "Четвъртък",
  "Петък",
  "Събота"
];

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "OK",
    cancel : "Отмени",
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
    todayText         : "Днес",
    minText           : "Тази дата е преди минималната",
    maxText           : "Тази дата е след максималната",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : 'Следващ месец (Control+Right)',
    prevText          : 'Предишен месец (Control+Left)',
    monthYearText     : 'Избери месец (Control+Up/Down за преместване по години)',
    todayTip          : "{0} (Spacebar)",
    format            : "d.m.y",
    okText            : "&#160;OK&#160;",
    cancelText        : "Отмени",
    startDay          : 1
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "Страница",
    afterPageText  : "от {0}",
    firstText      : "Първа страница",
    prevText       : "Предишна страница",
    nextText       : "Следваща страница",
    lastText       : "Последна страница",
    refreshText    : "Презареди",
    displayMsg     : "Показвайки {0} - {1} от {2}",
    emptyMsg       : 'Няма данни за показване'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "Минималната дължина на това поле е {0}",
    maxLengthText : "Максималната дължина на това поле е {0}",
    blankText     : "Това поле е задължително",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "Минималната стойност за това поле е {0}",
    maxText : "Максималната стойност за това поле е {0}",
    nanText : "{0} не е валидно число"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "Недостъпен",
    disabledDatesText : "Недостъпен",
    minText           : "Датата в това поле трябва да е след {0}",
    maxText           : "Датата в това поле трябва да е преди {0}",
    invalidText       : "{0} не е валидна дата - трябва да бъде във формат {1}",
    format            : "d.m.y",
    altFormats        : "d.m.y|d/m/Y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d",
    startDay          : 1
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "Зареждане...",
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : 'Това поле трябва да бъде емейл във формат "user@example.com"',
    urlText      : 'Това поле трябва да бъде URL във формат "http:/'+'/www.example.com"',
    alphaText    : 'Това поле трябва да съдържа само букви и _',
    alphanumText : 'Това поле трябва да съдържа само букви, цифри и _'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Моля, въведете URL за връзката:',
    buttonTips : {
      bold : {
        title: 'Bold (Ctrl+B)',
        text: 'Удебелява избрания текст.',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Italic (Ctrl+I)',
        text: 'Прави избрания текст курсив.',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Underline (Ctrl+U)',
        text: 'Подчертава избрания текст.',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Уголеми текста',
        text: 'Уголемява размера на шрифта.',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Намали текста',
        text: 'Намалява размера на шрифта.',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Цвят на маркирания текст',
        text: 'Променя фоновия цвят на избрания текст.',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Цвят на шрифта',
        text: 'Променя цвета на избрания текст.',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Ляво подравняване',
        text: 'Подравнява текста на ляво.',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Центриране',
        text: 'Центрира текста.',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Дясно подравняване',
        text: 'Подравнява текста на дясно.',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Неномериран списък',
        text: 'Започва неномериран списък.',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Номериран списък',
        text: 'Започва номериран списък.',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Хипервръзка',
        text: 'Превръща избрания текст в хипервръзка.',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Редактиране на кода',
        text: 'Преминаване в режим на редактиране на кода.',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "Подреди в нарастващ ред",
    sortDescText : "Подреди в намаляващ ред",
    lockText     : "Заключи колона",
    unlockText   : "Отключи колона",
    columnsText  : "Колони"
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "Име",
    valueText  : "Стойност",
    dateFormat : "d.m.Y"
  });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "Влачете с мишката за да промените размера.",
    collapsibleSplitTip : "Влачете с мишката за да промените размера. Чукнете два пъти за да скриете."
  });
}
