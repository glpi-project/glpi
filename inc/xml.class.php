<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/// XML class
class XML {

   /// Array of SQL requests to export
   public $SqlString    = "";
   /// 1 there is a problem !!!
   public $IsError      = 0;
   /// If there is an error, this string explains it
   public $ErrorString  = "NO errors;)";
   /// Which format do you want your XML ?
   public $Type         = 1;
   ///path where the file will be saved.
   public $FilePath;

   // HERE I explain $Type

   /* For Example :
      1 (default) each value are in a tag called data
      <dataxml>
      <row>
      <data>value field1 row1</data>
      <data>value field2 row1</data>
      <data>value field3 row1</data>
      </row>
      <row>
      <data>value field1 row2</data>
      <data>value field2 row2</data>
      <data>value field3 row2</data>
      </row>
      </dataxml>

      2 each value is in a tag called dataN , where N is a position of field
      <dataxml>
      <row>
      <data1>value field1 row1</data1>
      <data2>value field2 row1</data2>
      <data3>value field3 row1</data3>
      </row>
      <row>
      <data1>value field1 row2</data1>
      <data2>value field2 row2</data2>
      <data3>value field3 row2</data3>
      </row>
      </dataxml>

      3 each value is in a tag called with the name of field
      <dataxml>
      <row>
      <fieldname1>value field1 row1</fieldname1>
      <fieldname2>value field2 row1</fieldname2>
      <fieldname3>value field3 row1</fieldname3>
      </row>
      <row>
      <fieldname1>value field1 row2</fieldname1>
      <fieldname2>value field2 row2</fieldname2>
      <fieldname3>value field3 row2</fieldname3>
      </row>
      </dataxml>

      4 each value is in a tag with an attributes called fieldname with the name of field
      <dataxml>
      <row>
      <data fieldname="fieldname1">value field1 row1</data>
      <data fieldname="fieldname2">value field1 row1</data>
      <data fieldname="fieldname3">value field1 row1</data>
      </row>
      <row>
      <data fieldname="fieldname1">value field1 row2</data>
      <data fieldname="fieldname2">value field1 row2</data>
      <data fieldname="fieldname3">value field1 row2</data>
      </row>
      </dataxml>
    */

   /**
    * Do XML export
   **/
   function DoXML() {
      global $DB;

      $fp = fopen($this->FilePath, 'wb');
      fputs($fp, "<?xml version=\"1.0\"?>\n");
      fputs($fp, "<dataxml>\n");

      foreach ($this->SqlString as $strqry) {
         if ($strqry == "") {
            $this->IsError     = 1;
            $this->ErrorString = "Error the query can't be a null string";
            return -1;
         }
         $result = $DB->rawQuery($strqry);

         if ($result == false) {
            $this->IsError     = 1;
            $this->ErrorString = "Error in SQL Query: ".$strqry;
            return -1;
         }
         // OK... let's create XML;)
         fputs($fp, "   <fields>\n");
         $i = 0;
         $FieldsVector = [];
         while ($i < $result->columnCount()) {
            $name = $DB->fieldName($result, $i);
            fputs($fp, "      <field>".$name."</field>\n");
            $FieldsVector[] = $name;
            $i++;
         }

         fputs($fp, "   </fields>\n");
         // And NOW the Data ...
         fputs($fp, "   <rows>\n");
         while ($row = $DB->fetchRow($result)) {
            fputs($fp, "      <row>\n");
            for ($j=0; $j<$i; $j++) {
               $FieldName  = "";   // Name of TAG
               $Attributes = "";
               switch ($this->Type) {
                  case 1 :
                     $FieldName = "data";
                     break;

                  case 2 :
                     $FieldName = "data".$j;
                     break;

                  case 3 :
                     $FieldName = $FieldsVector[$j];
                     break;

                  case 4 :
                     $FieldName = "data";
                     $Attributes = " fieldname=\"".$FieldsVector[$j]."\"";
               }
               fputs($fp, "         <".$FieldName.$Attributes.">".
                     Toolbox::encodeInUtf8(htmlspecialchars($row[$j]))."</".$FieldName.">\n");
            }
            fputs($fp, "      </row>\n");
         }
         fputs($fp, "   </rows>\n");

         $DB->freeResult($result);
      }
      fputs($fp, "</dataxml>");
      //OK free ...;)
      fclose($fp);

   } // End  Function : DoXML

} // Fine Class XML

