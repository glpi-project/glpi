/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * Hebrew Translations
 * By spartacus (from forums) 06-12-2007
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">...����</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.Grid){
  Ext.grid.Grid.prototype.ddText = "����� ������ {0}";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "���� ������";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "���� ���� �� ����";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "...����";
}

Date.monthNames = [
  "�����",
  "������",
  "���",
  "�����",
  "���",
  "����",
  "����",
  "������",
  "������",
  "�������",
  "������",
  "�����"
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
  "�",
  "�",
  "�",
  "�",
  "�",
  "�",
  "�"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "�����",
    cancel : "�����",
    yes    : "��",
    no     : "��"
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
    todayText         : "����",
    minText           : ".����� �� �� ���� ������ ������� �����",
    maxText           : ".����� �� �� ���� ������ ����� �����",
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : '(Control+Right) ����� ���',
    prevText          : '(Control+Left) ����� �����',
    monthYearText     : '(������ ��� Control+Up/Down) ��� ����',
    todayTip          : "��� ����) {0})",
    format            : "d/m/Y",
    okText            : "&#160;�����&#160;",
    cancelText        : "�����",
    startDay          : 0
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "����",
    afterPageText  : "{0} ����",
    firstText      : "���� �����",
    prevText       : "���� ����",
    nextText       : "���� ���",
    lastText       : "���� �����",
    refreshText    : "����",
    displayMsg     : "���� {0} - {1} ���� {2}",
    emptyMsg       : '��� ���� �����'
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "{0} ����� ��������� ���� �� ���",
    maxLengthText : "{0} ����� ������ ���� �� ���",
    blankText     : "��� �� �����",
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "{0} ���� ��������� ���� �� ���",
    maxText : "{0} ���� ������ ���� �� ���",
    nanText : "��� �� ���� {0}"
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "������",
    disabledDatesText : "������",
    minText           : "{0} ������ ���� �� ���� ����� ����",
    maxText           : "{0} ������ ���� �� ���� ����� ����",
    invalidText       : "{1} ��� �� ����� ���� - ���� ����� ������ {0}",
    format            : "m/d/y",
    altFormats        : "m/d/Y|m-d-y|m-d-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d"
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "...����",
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : '"user@domain.com" ��� �� ���� ����� ����� ���� �������� ������',
    urlText      : '"http:/'+'/www.domain.com" ��� �� ���� ����� ����� ������� ������',
    alphaText    : '_��� �� ���� ����� �� ������ �',
    alphanumText : '_��� �� ���� ����� �� ������, ������ �'
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : ':��� ���� �� ����� �������� ���� ������',
    buttonTips : {
      bold : {
        title: '(Ctrl+B) �����',
        text: '.���� �� ����� �����',
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: '(Ctrl+I) ����',
        text: '.��� �� ����� �����',
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: '(Ctrl+U) �� ����',
        text: '.���� �� ���� ���� ����� �����',
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: '���� ����',
        text: '.���� ���� ���� ����� �����',
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: '���� ����',
        text: '.���� ���� ���� ����� �����',
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: '��� ��� �����',
        text: '.��� �� ��� ���� ���� ����� �����',
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: '��� ����',
        text: '.��� �� ��� ����� ���� ����� �����',
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: '���� �����',
        text: '.��� ����� �� ����� �����',
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: '���� �����',
        text: '.��� ����� �� ����� �����',
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: '���� �����',
        text: '.��� ����� �� ����� �����',
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: '����� ������',
        text: '.���� ����� ������',
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: '����� �������',
        text: '.���� ����� �������',
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: '�����',
        text: '.���� �� ����� ����� ������',
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: '����� ��� ����',
        text: '.��� ��� ����',
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "���� ���� ����",
    sortDescText : "���� ���� ����",
    lockText     : "��� �����",
    unlockText   : "���� �����",
    columnsText  : "������"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(���)',
    groupByText    : '��� ������� ��� ��� ��',
    showGroupsText : '��� �������'
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "��",
    valueText  : "���",
    dateFormat : "m/j/Y"
  });
}

if(Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : ".���� ������ ����",
    collapsibleSplitTip : ".���� ������ ����. ����� ����� ������"
  });
}
