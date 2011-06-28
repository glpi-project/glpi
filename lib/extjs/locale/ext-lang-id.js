/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
/**
 * Pedoman translasi:
 * http://id.wikisource.org/wiki/Panduan_Pembakuan_Istilah,_Pelaksanaan_Instruksi_Presiden_Nomor_2_Tahun_2001_Tentang_Penggunaan_Komputer_Dengan_Aplikasi_Komputer_Berbahasa_Indonesia
 * Original source: http://vlsm.org/etc/baku-0.txt
 * by Farid GS
 * farid [at] pulen.net
 * 10:13 04 Desember 2007
 * Indonesian Translations
 */

Ext.UpdateManager.defaults.indicatorText = '<div class="loading-indicator">Pemuatan...</div>';

if(Ext.View){
  Ext.View.prototype.emptyText = "";
}

if(Ext.grid.GridPanel){
  Ext.grid.GridPanel.prototype.ddText = "{0} selected row(s)";
  Ext.grid.GridPanel.prototype.ddText = "{0} baris terpilih";
}

if(Ext.TabPanelItem){
  Ext.TabPanelItem.prototype.closeText = "Close this tab";
  Ext.TabPanelItem.prototype.closeText = "Tutup tab ini";
}

if(Ext.form.Field){
  Ext.form.Field.prototype.invalidText = "The value in this field is invalid";
  Ext.form.Field.prototype.invalidText = "Isian belum benar";
}

if(Ext.LoadMask){
  Ext.LoadMask.prototype.msg = "Loading...";
  Ext.LoadMask.prototype.msg = "Pemuatan...";
}

Date.monthNames = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember"
];

Date.getShortMonthName = function(month) {
  return Date.monthNames[month].substring(0, 3);
};

Date.monthNumbers = {
  Jan : 0,
  Feb : 1,
  Mar : 2,
  Apr : 3,
  Mei : 4,
  Jun : 5,
  Jul : 6,
  Agu : 7,
  Sep : 8,
  Okt : 9,
  Nov : 10,
  Des : 11
};

Date.getMonthNumber = function(name) {
  return Date.monthNumbers[name.substring(0, 1).toUpperCase() + name.substring(1, 3).toLowerCase()];
};

Date.dayNames = [
  "Minggu",
  "Senin",
  "Selasa",
  "Rabu",
  "Kamis",
  "Jumat",
  "Sabtu"
];

Date.getShortDayName = function(day) {
  return Date.dayNames[day].substring(0, 3);
};

if(Ext.MessageBox){
  Ext.MessageBox.buttonText = {
    ok     : "OK",
    cancel : "Batal",
    yes    : "Ya",
    no     : "Tidak"
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
    todayText         : "Hari ini",
    minText           : "Tanggal ini sebelum batas tanggal minimal", 
    maxText           : "Tanggal ini setelah batas tanggal maksimal", 
    disabledDaysText  : "",
    disabledDatesText : "",
    monthNames        : Date.monthNames,
    dayNames          : Date.dayNames,
    nextText          : 'Bulan Berikut (Kontrol+Kanan)', 
    prevText          : 'Bulan Sebelum (Kontrol+Kiri)', 
    monthYearText     : 'Pilih bulan (Kontrol+Atas/Bawah untuk pindah tahun)', 
    todayTip          : "{0} (Spacebar)",
    format            : "d/m/y",
    okText            : "&#160;OK&#160;",
    cancelText        : "Batal",
    startDay          : 1
  });
}

if(Ext.PagingToolbar){
  Ext.apply(Ext.PagingToolbar.prototype, {
    beforePageText : "Hal",
    afterPageText  : "dari {0}",
    firstText      : "Hal. Pertama",
    prevText       : "Hal. Sebelum",
    nextText       : "Hal. Berikut",
    lastText       : "Hal. Akhir",
    refreshText    : "Segarkan", 
    displayMsg     : "Menampilkan {0} - {1} dari {2}",
    emptyMsg       : 'Data tidak ditemukan' 
  });
}

if(Ext.form.TextField){
  Ext.apply(Ext.form.TextField.prototype, {
    minLengthText : "Panjang minimal untuk field ini adalah {0}",  
    maxLengthText : "Panjang maksimal untuk field ini adalah {0}", 
    blankText     : "Field ini wajib diisi", 
    regexText     : "",
    emptyText     : null
  });
}

if(Ext.form.NumberField){
  Ext.apply(Ext.form.NumberField.prototype, {
    minText : "Nilai minimal untuk field ini adalah {0}",  
    maxText : "Nilai maksimal untuk field ini adalah {0}",  
    nanText : "{0} bukan angka" 
  });
}

if(Ext.form.DateField){
  Ext.apply(Ext.form.DateField.prototype, {
    disabledDaysText  : "Disfungsi",  
    disabledDatesText : "Disfungsi",  
    minText           : "Tanggal dalam field ini harus setelah {0}", 
    maxText           : "Tanggal dalam field ini harus sebelum {0}", 
    invalidText       : "{0} tanggal salah - Harus dalam format {1}", 
    format            : "d/m/y", 
    //altFormats        : "m/d/Y|m-d-y|m-d-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d"
    altFormats        : "d/m/Y|d-m-y|d-m-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d",
    startDay          : 1
  });
}

if(Ext.form.ComboBox){
  Ext.apply(Ext.form.ComboBox.prototype, {
    loadingText       : "Pemuatan...",  
    valueNotFoundText : undefined
  });
}

if(Ext.form.VTypes){
  Ext.apply(Ext.form.VTypes, {
    emailText    : 'Field ini harus dalam format email seperti "user@example.com"', 
    urlText      : 'Field ini harus dalam format URL seperti "http:/'+'/www.example.com"', 
    alphaText    : 'Field ini harus terdiri dari huruf dan _', 
    alphanumText : 'Field ini haris terdiri dari huruf, angka dan _'  
  });
}

if(Ext.form.HtmlEditor){
  Ext.apply(Ext.form.HtmlEditor.prototype, {
    createLinkText : 'Silakan masukkan URL untuk tautan:', 
    buttonTips : {
      bold : {
        title: 'Tebal (Ctrl+B)', 
        text: 'Buat tebal teks terpilih', 
        cls: 'x-html-editor-tip'
      },
      italic : {
        title: 'Miring (CTRL+I)', 
        text: 'Buat miring teks terpilih', 
        cls: 'x-html-editor-tip'
      },
      underline : {
        title: 'Garisbawah (CTRl+U)', 
        text: 'Garisbawahi teks terpilih', 
        cls: 'x-html-editor-tip'
      },
      increasefontsize : {
        title: 'Perbesar teks', 
        text: 'Perbesar ukuran fonta', 
        cls: 'x-html-editor-tip'
      },
      decreasefontsize : {
        title: 'Perkecil teks',  
        text: 'Perkecil ukuran fonta', 
        cls: 'x-html-editor-tip'
      },
      backcolor : {
        title: 'Sorot Warna Teks',  
        text: 'Ubah warna latar teks terpilih', 
        cls: 'x-html-editor-tip'
      },
      forecolor : {
        title: 'Warna Fonta', 
        text: 'Ubah warna teks terpilih',  
        cls: 'x-html-editor-tip'
      },
      justifyleft : {
        title: 'Rata Kiri', 
        text: 'Ratakan teks ke kiri', 
        cls: 'x-html-editor-tip'
      },
      justifycenter : {
        title: 'Rata Tengah', 
        text: 'Ratakan teks ke tengah editor', 
        cls: 'x-html-editor-tip'
      },
      justifyright : {
        title: 'Rata Kanan', 
        text: 'Ratakan teks ke kanan', 
        cls: 'x-html-editor-tip'
      },
      insertunorderedlist : {
        title: 'Daftar Bulet', 
        text: 'Membuat daftar berbasis bulet', 
        cls: 'x-html-editor-tip'
      },
      insertorderedlist : {
        title: 'Daftar Angka', 
        text: 'Membuat daftar berbasis angka', 
        cls: 'x-html-editor-tip'
      },
      createlink : {
        title: 'Hipertaut', 
        text: 'Buat teks terpilih sebagai Hipertaut', 
        cls: 'x-html-editor-tip'
      },
      sourceedit : {
        title: 'Edit Kode Sumber', 
        text: 'Pindah dalam mode kode sumber', 
        cls: 'x-html-editor-tip'
      }
    }
  });
}

if(Ext.grid.GridView){
  Ext.apply(Ext.grid.GridView.prototype, {
    sortAscText  : "Urut Naik", 
    sortDescText : "Urut Turun", 
    lockText     : "Kancing Kolom", 
    unlockText   : "Lepas Kunci Kolom", 
    columnsText  : "Kolom"
  });
}

if(Ext.grid.GroupingView){
  Ext.apply(Ext.grid.GroupingView.prototype, {
    emptyGroupText : '(Kosong)', 
    groupByText    : 'Kelompokkan Berdasar Field Ini', 
    showGroupsText : 'Tampil Dalam Kelompok' 
  });
}

if(Ext.grid.PropertyColumnModel){
  Ext.apply(Ext.grid.PropertyColumnModel.prototype, {
    nameText   : "Nama", 
    valueText  : "Nilai", 
    dateFormat : "d/m/Y" 
  });
}

if(Ext.layout.BorderLayout && Ext.layout.BorderLayout.SplitRegion){
  Ext.apply(Ext.layout.BorderLayout.SplitRegion.prototype, {
    splitTip            : "Seret untuk ubah ukuran.", 
    collapsibleSplitTip : "Seret untuk ubah ukuran, Dobel klik untuk sembunyikan." 
  });
}
