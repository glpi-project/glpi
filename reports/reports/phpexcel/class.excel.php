<?PHP

/*
 * Copyleft 2002 Johann Hanne
 *
 * This is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307 USA
 */

/*
 * This is the Spreadsheet::WriteExcel Perl package ported to PHP
 * Spreadsheet::WriteExcel was written by John McNamara, jmcnamara@cpan.org
 */
 
require_once "Excel/class.writeexcel_workbook.inc.php";
require_once "Excel/class.writeexcel_worksheet.inc.php";
require_once "Excel/functions.writeexcel_utility.inc.php";

class Excel_Workbook extends writeexcel_workbook {
    /**
    * The constructor. It just creates a Workbook
    *
    * @param string $filename The optional filename for the Workbook.
    * @return Spreadsheet_Excel_Writer_Workbook The Workbook created
    */
    
    function Excel_Workbook($filename = '') {
        $this->_filename = $filename;
        $this->writeexcel_workbook($filename);
    }
						    
    /**
    * Send HTTP headers for the Excel file.
    *
    * @param string $filename The filename to use for HTTP headers
    * @access public
    */
    function send($filename) { // attachment
		global $pref_lang;
		$this->_tmpfilename = $filename;
/*		if ($pref_lang == "ru") {
		    header ('<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">');
		} else {
		    header ('<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">');
		}
*/      header("Content-type: application/x-msexcel");
        header("Content-Disposition: inline; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
    }
}
?>
