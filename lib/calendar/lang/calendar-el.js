// ** I18N

// Calendar EL language
// Author:
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Κυριακή",
 "Δευτέρα",
 "Τρίτη",
 "Τετάρτη",
 "Πέμπτη",
 "Παρασκευή",
 "Σάββατο",
 "Κυριακή");

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
("Κυ",
 "Δε",
 "Tρ",
 "Τε",
 "Πε",
 "Πα",
 "Σα",
 "Κυ");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 0;

// full month names
Calendar._MN = new Array
("Ιανουάριος",
 "Φεβρουάριος",
 "Μάρτιος",
 "Απρίλιος",
 "Μάϊος",
 "Ιούνιος",
 "Ιούλιος",
 "Αύγουστος",
 "Σεπτέμβριος",
 "Οκτώβριος",
 "Νοέμβριος",
 "Δεκέμβριος");

// short month names
Calendar._SMN = new Array
("Ιαν",
 "Φεβ",
 "Μαρ",
 "Απρ",
 "Μαι",
 "Ιουν",
 "Ιουλ",
 "Αυγ",
 "Σεπ",
 "Οκτ",
 "Νοε",
 "Δεκ");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "Για το ημερολόγιο";

Calendar._TT["ABOUT"] =
"Επιλογέας ημερομηνίας/ώρας σε DHTML\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"Για τελευταία έκδοση: http://www.dynarch.com/projects/calendar/\n" +
"Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details." +
"\n\n" +
"Επιλογή ημερομηνίας:\n" +
"- Χρησιμοποιείστε τα κουμπιά \xab, \xbb για επιλογή έτους\n" +
"- Χρησιμοποιείστε τα κουμπιά " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " για επιλογή μήνα\n" +
"- Κρατήστε κουμπί ποντικού πατημένο στα παραπάνω κουμπιά για πιο γρήγορη επιλογή.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Επιλογή ώρας:\n" +
"- Κάντε κλικ σε ένα από τα μέρη της ώρας για αύξηση\n" +
"- ή Shift-κλικ για μείωση\n" +
"- ή κλικ και μετακίνηση για πιο γρήγορη επιλογή.";

Calendar._TT["PREV_YEAR"] = "Προηγ. έτος (κρατήστε για το μενού)";
Calendar._TT["PREV_MONTH"] = "Προηγ. μήνας (κρατήστε για το μενού)";
Calendar._TT["GO_TODAY"] = "Σήμερα";
Calendar._TT["NEXT_MONTH"] = "Επόμενος μήνας (κρατήστε για το μενού)";
Calendar._TT["NEXT_YEAR"] = "Επόμενο έτος (κρατήστε για το μενού)";
Calendar._TT["SEL_DATE"] = "Επιλέξτε ημερομηνία";
Calendar._TT["DRAG_TO_MOVE"] = "Σύρτε για να μετακινήσετε";
Calendar._TT["PART_TODAY"] = " (σήμερα)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Αφετηρία από %s";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Κλείσιμο";
Calendar._TT["TODAY"] = "Σήμερα";
Calendar._TT["TIME_PART"] = "(Shift-)κλικ ή μετακίνηση για αλλαγή";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "dd-mm-y";
Calendar._TT["TT_DATE_FORMAT"] = "D, d M";

Calendar._TT["WK"] = "εβδ";
Calendar._TT["TIME"] = "Ώρα:";
