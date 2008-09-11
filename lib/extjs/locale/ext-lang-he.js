/**
 * Hebrew Translations
 * By spartacus (from forums) 06-12-2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">...טוען</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
  Ext.grid.GridPanel.prototype.ddText = "שורות נבחרות {0}";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "סגור לשונית";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "הערך בשדה זה שגוי";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "...טוען";
}

Date.monthNames = [
  "ינואר",
  "פברואר",
  "מרץ",
  "אפריל",
  "מאי",
  "יוני",
  "יולי",
  "אוגוסט",
  "ספטמבר",
  "אוקטובר",
  "נובמבר",
  "דצמבר"
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
  "א",
  "ב",
  "ג",
  "ד",
  "ה",
  "ו",
  "ש"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "אישור",
    cancel : "ביטול",
    yes    : "כן",
    no     : "לא"
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
    todayText         : "היום",
    minText           : ".תאריך זה חל קודם לתאריך ההתחלתי שנקבע",
    maxText           : ".תאריך זה חל לאחר התאריך הסופי שנקבע",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : '(Control+Right) החודש הבא',
    prevText          : '(Control+Left) החודש הקודם',
    monthYearText     : '(לבחירת שנה Control+Up/Down) בחר חודש',
    todayTip          : "מקש רווח) {0})",
    format            : "d/m/Y",
    okText            : "&#160;אישור&#160;",
    cancelText        : "ביטול",
    startDay          : 0
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "עמוד",
    afterPageText  : "{0} מתוך",
    firstText      : "עמוד ראשון",
    prevText       : "עמוד קודם",
    nextText       : "עמוד הבא",
    lastText       : "עמוד אחרון",
    refreshText    : "רענן",
    displayMsg     : "מציג {0} - {1} מתוך {2}",
    emptyMsg       : 'אין מידע להצגה'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "{0} האורך המינימאלי לשדה זה הוא",
    maxLengthText : "{0} האורך המירבי לשדה זה הוא",
    blankText     : "שדה זה הכרחי",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "{0} הערך המינימאלי לשדה זה הוא",
    maxText : "{0} הערך המירבי לשדה זה הוא",
    nanText : "הוא לא מספר {0}"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "מנוטרל",
    disabledDatesText : "מנוטרל",
    minText           : "{0} התאריך בשדה זה חייב להיות לאחר",
    maxText           : "{0} התאריך בשדה זה חייב להיות לפני",
    invalidText       : "{1} הוא לא תאריך תקני - חייב להיות בפורמט {0}",
    format            : "m/d/y",
    altFormats        : "m/d/Y|m-d-y|m-d-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d"
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "...טוען",
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : '"user@domain.com" שדה זה צריך להיות כתובת דואר אלקטרוני בפורמט',
    urlText      : '"http:/'+'/www.domain.com" שדה זה צריך להיות כתובת אינטרנט בפורמט',
    alphaText    : '_שדה זה יכול להכיל רק אותיות ו',
    alphanumText : '_שדה זה יכול להכיל רק אותיות, מספרים ו'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : ':אנא הקלד את כתובת האינטרנט עבור הקישור',
    buttonTips : {
      bold : {
        title: '(Ctrl+B) מודגש',
        text: '.הדגש את הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: '(Ctrl+I) נטוי',
        text: '.הטה את הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: '(Ctrl+U) קו תחתי',
        text: '.הוסף קן תחתי עבור הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'הגדל טקסט',
        text: '.הגדל גופן עבור הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'הקטן טקסט',
        text: '.הקטן גופן עבור הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'צבע רקע לטקסט',
        text: '.שנה את צבע הרקע עבור הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'צבע גופן',
        text: '.שנה את צבע הגופן עבור הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'ישור לשמאל',
        text: '.ישר שמאלה את הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'ישור למרכז',
        text: '.ישר למרכז את הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'ישור לימין',
        text: '.ישר ימינה את הטקסט הנבחר',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'רשימת נקודות',
        text: '.התחל רשימת נקודות',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'רשימה ממוספרת',
        text: '.התחל רשימה ממוספרת',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'קישור',
        text: '.הפוך את הטקסט הנבחר לקישור',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'עריכת קוד מקור',
        text: '.הצג קוד מקור',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "מיין בסדר עולה",
    sortDescText : "מיין בסדר יורד",
    lockText     : "נעל עמודה",
    unlockText   : "שחרר עמודה",
    columnsText  : "עמודות"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(ריק)',
    groupByText    : 'הצג בקבוצות לפי שדה זה',
    showGroupsText : 'הצג בקבוצות'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "שם",
    valueText  : "ערך",
    dateFormat : "m/j/Y"
  });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : ".משוך לשינוי גודל",
    collapsibleSplitTip : ".משוך לשינוי גודל. לחיצה כפולה להסתרה"
  });
}
