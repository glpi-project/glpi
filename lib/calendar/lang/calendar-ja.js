// ** I18N

// Calendar Japanese language
// Author: Tadashi Jokagi <elf2000@users.sourceforge.net>
// Encoding: UTF-8
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("日曜日",
 "月曜日",
 "火曜日",
 "水曜日",
 "木曜日",
 "金曜日",
 "土曜日",
 "日曜日");

// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   Calendar._SDN_len = N; // short day name length
//   Calendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
Calendar._SDN = new Array
("日",
 "月",
 "火",
 "水",
 "木",
 "金",
 "土",
 "日");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 0;

// full month names
Calendar._MN = new Array
("1 月",
 "2 月",
 "3 月",
 "4 月",
 "5 月",
 "6 月",
 "7 月",
 "8 月",
 "9 月",
 "10 月",
 "11 月",
 "12 月");

// short month names
Calendar._SMN = new Array
("1",
 "2",
 "3",
 "4",
 "5",
 "6",
 "7",
 "8",
 "9",
 "10",
 "11",
 "12");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "カレンダーについて";

Calendar._TT["ABOUT"] =
"DHTML での日付/時間選択\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"最新のバージョンはこちらに訪問してください: http://www.dynarch.com/projects/calendar/\n" +
"GNU LGPL の元で配布されます。詳細は http://gnu.org/licenses/lgpl.html を参照してください。" +
"\n\n" +
"日にちの選択:\n" +
"- \xab, \xbb ボタンは「年」の選択に使います。\n" +
"- " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " ボタンは「月」の選択に使います。\n" +
"- より早い選択には上のボタンをマウスボタンで維持します。";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"時間選択:\n" +
"- 時間の部分のどれかをクリックすると、それが増加します。\n" +
"- もしくは、Shift とクリックでそれが減少します。\n" +
"- もしくはクリックとドラッグですばやく選択します。";

Calendar._TT["PREV_YEAR"] = "昨年 (持続でメニュー)";
Calendar._TT["PREV_MONTH"] = "先月 (持続でメニュー)";
Calendar._TT["GO_TODAY"] = "今日に移動";
Calendar._TT["NEXT_MONTH"] = "来月 (持続でメニュー)";
Calendar._TT["NEXT_YEAR"] = "来年 (持続でメニュー)";
Calendar._TT["SEL_DATE"] = "日付の選択";
Calendar._TT["DRAG_TO_MOVE"] = "ドラッグで移動";
Calendar._TT["PART_TODAY"] = " (今日)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "%s を初めに表示";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "閉じる";
Calendar._TT["TODAY"] = "今日";
Calendar._TT["TIME_PART"] = "(Shift と)クリックかドラッグで値を変更";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "%b/%e(%a)";

Calendar._TT["WK"] = "週";
Calendar._TT["TIME"] = "時間:";
