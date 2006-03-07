// ** I18N

// Calendar EN language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Zondag",
 "Maandag",
 "Dinsdag",
 "Woensdag",
 "Donderdag",
 "Vrijdag",
 "Zaterdag",
 "Zondag");

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
("Zo",
 "Ma",
 "Di",
 "Wo",
 "Do",
 "Vr",
 "Za",
 "Zo");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

// full month names
Calendar._MN = new Array
("Januari",
 "Februari",
 "Maart",
 "April",
 "Mei",
 "Juni",
 "Juli",
 "Augustus",
 "September",
 "Oktober",
 "November",
 "December");

// short month names
Calendar._SMN = new Array
("Jan",
 "Feb",
 "Mar",
 "Apr",
 "May",
 "Jun",
 "Jul",
 "Aug",
 "Sep",
 "Oct",
 "Nov",
 "Dec");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "Over de kalendar";

Calendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"Ga voor de meest recente versie naar: http://www.dynarch.com/projects/calendar/\n" +
"Verspreid onder GNU LGPL.  See http://gnu.org/licenses/lgpl.html voor details." +
"\n\n" +
"Datum selectie:\n" +
"- Gebruik de \xab \xbb knoppen om het jaar te selecteren\n" +
"- Gebruik de " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " knoppen om de maand te selecteren\n" +
"- Houd de muis ingedrukt op de boven genoemde knoppen voor een snellere selectie.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Tijd selectie:\n" +
"- Klik op een willekeurig onderdeel van het tijd gedeelte om het te verhogen\n" +
"- of Shift-klik om het te verlagen\n" +
"- of klik en sleep voor een snellere selectie.";

Calendar._TT["PREV_YEAR"] = "Vorig jaar (ingedrukt voor menu)";
Calendar._TT["PREV_MONTH"] = "Vorige maand (ingedrukt voor menu)";
Calendar._TT["GO_TODAY"] = "Naar vandaag";
Calendar._TT["NEXT_MONTH"] = "Volgende maand (ingedrukt voor menu)";
Calendar._TT["NEXT_YEAR"] = "Volgend jaar (ingedrukt voor menu)";
Calendar._TT["SEL_DATE"] = "Selecteer datum";
Calendar._TT["DRAG_TO_MOVE"] = "Sleep om te verplaatsen";
Calendar._TT["PART_TODAY"] = " (vandaag)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Toon %s eerst";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Sluiten";
Calendar._TT["TODAY"] = "Vandaag";
Calendar._TT["TIME_PART"] = "(Shift-)Klik of sleep om waarde te veranderen";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%d-%m-%Y";
Calendar._TT["TT_DATE_FORMAT"] = "%a, %e %b";

Calendar._TT["WK"] = "wk";
Calendar._TT["TIME"] = "Tijd:";
