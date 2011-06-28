/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
﻿/**
 * List compiled by Tewodros Wondimu on the extjs.com forums Oct 18, 2010.
 *
 * Amharic Translations
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">እያስገባ ነዉ...</div>';

if(Ext.DataView){
  Ext.DataView.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
  Ext.grid.GridPanel.prototype.ddText = "{0} ምርጥ ረድፍ {1}";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "እያስገባ ነዉ...";
}

Date.shortMonthNames = [
   "መስከ",
   "ጥቅም",
   "ህዳር",
   "ታህሳ",
   "ጥር",
   "የካቲ",
   "መጋቢ",
   "ሚያዚ",
   "ግንቦ",
   "ሰኔ",
   "ሐምሌ",
   "ነሃሴ",
   "ጷግሜ"
];


Date.monthNames = [
  "መስከረም",
  "ጥቅምት",
  "ህዳር",
  "ታህሳስ",
  "ጥር",
  "የካቲት",
  "መጋቢት",
  "ሚያዚያ",
  "ግንቦት",
  "ሰኔ",
  "ሐምሌ",
  "ነሃሴ",
  "ጷግሜ"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  "መስከረም" : 0,
  "ጥቅምት" : 1,
  "ህዳር" : 2,
  "ታህሳስ" : 3,
  "ጥር" : 4,
  "የካቲት" : 5,
  "መጋቢት" : 6,
  "ሚያዚያ" : 7,
  "ግንቦት" : 8,
  "ሰኔ" : 9,
  "ሐምሌ" : 10,  
  "ነሃሴ" : 11,
  "ጷግሜ" : 12
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
  "እሁድ",
  "ሰኞ",
  "ማክሰኞ",
  "ዓሮብ",
  "ሐሙስ",
  "አርብ",
  "ቅዳሜ"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

Date.parseCodes.S.s = "(?:st|nd|rd|th)";

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "ይሁን",
    cancel : "ሰርዝ",
    yes    : "አዎ",
    no     : "የለም / አይ"
  };
}

if(Ext.util.Format){
  Ext.util.Format.date = function(v, format){
    if(!v) return "";
    if(!(v instanceof Date)) v = new Date(Date.parse(v));
    return v.dateFormat(format || "ወ/ቀ/አ");
  };
}

if(Ext.DatePicker){
  Ext.apply(Ext.DatePicker.prototype, {
    todayText         : "ዛሬ",
    minText           : "ይሄ ቀን  ከመጨረሻ ትንሹ ቀን በፊት ነዉ",
    maxText           : "ይሄ ቀን ከመጨረሻ ትልቁ ቀን በፊት ነዉ",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : 'የሚቀጥለዉ ወር(መቆጣጣሪያ  ቁምፍ+ቀኝ)',
    prevText          : 'ያለፈዉ ወር(መቆጣጣሪያ  ቁምፍ+ግራ)',
    monthYearText     : 'ወር ምረጥ (አመት ለመለወጥ መቆጣጣሪያ  ቁምፍ+ወደላይ/ወደታች)',
    todayTip          : "{0} (የቦታ ቁልፍ)",
    format            : "d/m/Y",
    okText            : "ይሁን",
    cancelText        : "ሰርዝ",
    startDay          : 0
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "ገልጽ",
    afterPageText  : "የ {0}",
    firstText      : "የመጀመሪያዉ ገልጽ",
    prevText       : "ያለፈዉ ገልጽ",
    nextText       : "የሚቀጥለዉ ገልጽ",
    lastText       : "የመጨረሻዉ ገልጽ",
    refreshText    : "ከልስ",
    displayMsg     : "{0} - {1} ሲያሳይ ከ {2}",
    emptyMsg       : 'የሚታይ ዳታ የለም'
  });
}

if(Ext.form.BasicForm){
    Ext.form.BasicForm.prototype.waitTitle = "እባክዎን ይጠብቁ..."
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "የዚህ መስክ እሴት የተሳሳተ ነዉ";
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "የዚህ መስክ የመጨረሻ ትንሽ ርዝመት {0} ነዉ",
    maxLengthText : "የዚህ መስክ የመጨረሻ ትልቅ ርዝመት {0} ነዉ",
    blankText     : "ይሄ መስክ አስፈላጊ ነዉ",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    decimalSeparator : ".",
    decimalPrecision : 2,
    minText : "የዚህ መስክ የመጨረሻ ትንሽ እሴት {0} ነዉ",
    maxText : "የዚህ መስክ የመጨረሻ ትልቅ እሴት {0} ነዉ",
    nanText : "{0} የተሳሳተ ቁጥር ነዉ"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "ቦዝኗል / ስራ አቁሟል",
    disabledDatesText : "ቦዝኗል / ስራ አቁሟል",
    minText           : "እዚ እሴት ዉስጥ ያለዉ ቀን ከ{0} በሑአላ መሆን አለበት",
    maxText           : "እዚ እሴት ዉስጥ ያለዉ ቀን ከ{0} በፊት መሆን አለበት",
    invalidText       : "{0} የተሳሳተ ቀን ነዉ - በዚህ ቅርፀት መሆን አለበት {1}",
    format            : "d/m/y",
    altFormats        : "d/m/Y|d/m/y|d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d",
    startDay          : 0
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "እያስገባ ነዉ...",
    valueNotFoundText : "አልተወሰነም"
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : 'ይሄ እሴት ኢሜይል መሆን አለበት - በዚህ ቅርፀት መሆን አለበት "user@example.com"',
    urlText      : 'ይሄ እሴት  ዩአርኤን መሆን አለበት - በዚህ ቅርፀት መሆን አለበት "http:/'+'/www.example.com"',
    alphaText    : 'ይሄ እሴት መያዝ ያለበት ቃላትና _ ብቻ ነዉ',
    alphanumText : 'ይሄ እሴት መያዝ ያለበት ቃላት፤ ቁጥርና _ ብቻ ነዉ'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'እባኮትን ለዚ ዩአርኤን አገናኝ አስገባ:',
    buttonTips : {
      bold : {
        title: 'ደማቅ (መቆጣጣሪያ  ቁምፍ+B)',
        text: 'የተመረጠዉን ጽሁፍ አድምቅ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      italic : {
        title: 'ሰያፍ (መቆጣጣሪያ  ቁምፍ+I)',
        text: 'የተመረጠዉን ጽሁፍ ሰያፍ አድርግ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      underline : {
        title: 'መስመረግርጌ (መቆጣጣሪያ  ቁምፍ+U)',
        text: 'የተመረጠዉን ጽሁፍ ከግርጌ አስምር::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      increasefontsize : {
        title: 'ጽሁፉን አሳድግ',
        text: 'ቅርጸ ቁምፊ አሳድገግ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      decreasefontsize : {
        title: 'ጽሁፉን አሳንስ',
        text: 'ቅርጸ ቁምፊ አሳንስ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      backcolor : {
        title: 'ጽሁፍ ምረጥ ቀለም',
        text: 'የተመረጠዉን ጽሁፍ ዳራ ቀለም ለዉጥ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      forecolor : {
        title: 'ቅርጸ ቁምፊ ቀለም',
        text: 'የተመረጠዉን ጽሁፍ ቀለም ለዉጥ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      justifyleft : {
        title: 'ጽሁፍ ግራ አሰልፍ',
        text: 'ጽሁፉን ወደግራ አሰልፍ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      justifycenter : {
        title: 'ጽሁፍ አማክል',
        text: 'አርታኢዉ ላይ ጽሁፉን አማክል::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      justifyright : {
        title: 'ጽሁፍ ቀኝ አሰልፍ',
        text: 'ጽሁፉን ወደቀኝ አሰልፍ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      insertunorderedlist : {
        title: 'ነጥበ ምልክት ዝርዝር',
        text: 'ነጥበ ምልክት ዝርዝር ጀምር::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      insertorderedlist : {
        title: 'ቁጥራዊ ዝርዝር',
        text: 'ቁጥራዊ ዝርዝር ጀምር::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      createlink : {
        title: 'ገፅ አገናኝ',
        text: 'የተመረጠዉን ጽሁፍ ገፅ አገናኝ አድርግ::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      },
      sourceedit : {
        title: 'ምንጭ አርትእ',
        text: 'ወደ ምንጭ አርትእ ሁነታ ቀይር::',
        cls: 'ኤክስ-ኤችቲኤምኤል-አርታኢ-ጠቃሚ ምክር'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "ሽቅብ ደርድር",
    sortDescText : "ቁልቁል ደርድር",
    columnsText  : "አምዶች"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(ማንም)',
    groupByText    : 'በዚ መስክ ቦድን',
    showGroupsText : 'በቡድን አሳይ'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "ስም",
    valueText  : "እሴት ",
    dateFormat : "m/j/Y",
    trueText: "እሙን",
    falseText: "ሐሰት"
  });
}

if(Ext.grid.BooleanColumn){
   Ext.apply(Ext.grid.BooleanColumn.prototype, {
      trueText  : "እሙን",
      falseText : "ሐሰት",
      undefinedText: '&#160;'
   });
}

if(Ext.grid.NumberColumn){
    Ext.apply(Ext.grid.NumberColumn.prototype, {
        format : '0,000.00'
    });
}

if(Ext.grid.DateColumn){
    Ext.apply(Ext.grid.DateColumn.prototype, {
        format : 'm/d/Y'
    });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "መጠን ለመቀይር ጎትት::",
    collapsibleSplitTip : "መጠን ለመቀይር ጎትት:: ለመደበቅ ሁለቴ ጠቅ አድርግ::"
  });
}

if(Ext.form.TimeField){
  Ext.apply(Ext.form.TimeField.prototype, {
    minText : "እዚህ መስክ ዉስጥ ያለዉ ሰዓት ከ{0} እኩል ወይም በኁላ መሆን አለበት",
    maxText : "እዚህ መስክ ዉስጥ ያለዉ ሰዓት ከ{0} እኩል ወይም በፊት መሆን አለበት",
    invalidText : "{0} የተሳሳተ ሰዓት ነዉ",
    format : "g:i A",
    altFormats : "g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|ha|gA|h a|g a|g A|gi|hi|gia|hia|g|H"
  });
}

if(Ext.form.CheckboxGroup){
  Ext.apply(Ext.form.CheckboxGroup.prototype, {
    blankText : "እዚህ ቡድን ዉስጥ ቢያንስ አንድ ዓይነት መምረጥ አለብህ"
  });
}

if(Ext.form.RadioGroup){
  Ext.apply(Ext.form.RadioGroup.prototype, {
    blankText : "እዚህ ቡድን ዉስጥ ቢያንስ አንድ ዓይነት መምረጥ አለብህ"
  });
}
