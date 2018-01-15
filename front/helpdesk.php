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


include ('../inc/includes.php');

echo "<!DOCTYPE html>";
echo "<html lang=\"{$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]}\">";
?>

<head>
    <meta charset="utf-8" />
    <title>Helpdesk</title>
<style type="text/css">
body {
   font-size : 12px;
   background-color : #FFFFFF;
   color : #000000;
   font-family: Verdana,Arial,Helvetica,sans-serif;
}

#contenuform {
   width: 600px;
   margin: 0 auto;
}

#contenuform legend {
   font-weight: bold;
}

#contenuform fieldset {
   background-color: #eeeeee;
   border: 2px solid #898989;
   -moz-border-radius: 8px;
   padding-bottom:10px;
   width: 600px;
}

#contenuform label {
   display: block;
}

#contenuform textarea {
   width:100%;
}

</style>
</head>
<body>

<div id="contenuform">

<form method="post" name="helpdeskform" action="tracking.injector.php">
   <input type="hidden" name="_type" value="Helpdesk" />
   <input type="hidden" name="_auto_import" value="1" />
      <h2 align='center'><?php echo __("Helpdesk reporting form"); ?></h2>

   <fieldset>
      <legend><?php echo __("The issue must be solved");?></legend>

      <label for="urgency">
         <?php echo __("Select an urgency level"); ?>
      </label>
      <select name="urgency" id="urgency">
         <option value="5"><?php echo _x('urgency', 'Very high');?></option>
         <option value="4"><?php echo _x('urgency', 'High');?></option>
         <option value="3" selected="selected"><?php echo _x('urgency', 'Medium');?></option>
         <option value="2"><?php echo _x('urgency', 'Low');?></option>
         <option value="1"><?php echo _x('urgency', 'Very low');?></option>
      </select>
   </fieldset>

   <fieldset>
      <legend><?php echo __("Describe your issue"); ?></legend>
      <label for="name">
         <?php echo __("Title"); ?>
      </label>
      <input type='text' name="name" id="name" size='60' /><br />
      <label for="content">
         <?php echo __("Content"); ?>
      </label>
      <textarea name="content" id="content" cols="60" rows="20"></textarea>
   </fieldset>

<p align='center'><input type="submit" name= 'add' value="<?php echo _sx("button", "post") ?>" /></p>

<?php
// Close form for CSRF
Html::closeForm();
?>
</div>
</body>
</html>