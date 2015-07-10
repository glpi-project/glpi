<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

   <title>Helpdesk</title>
 <!-- Sample Helpdesk Frontend -->
<style type="text/css">
<!--
/*  ... ici sont d&eacute;finis les formats ... */

body {
   font-size : 12px;
   background-color : #FFFFFF;
   color : #000000;
   font-family: Verdana,Arial,Helvetica,sans-serif;
}

#contenuform {
   height: 100%;
   position: relative;
   left: 35%;
   width: 600px;
   margin-left: -150px;
}

#contenuform legend {
   font-weight: bold;
}

#contenuform fieldset {
   background-color: #eeeeee;
   border: 2px solid #FFC65D;
   -moz-border-radius: 8px;
   padding-bottom:10px;
   width: 600px;
}

#contenuform textarea {
   width:550px;
}

-->
</style>
</head>
<body>
    <!-- Edit the next line to fit your installation -->

<div id="contenuform">

<form method="post" name="helpdeskform" action="tracking.injector.php">
   <input type="hidden" name="_type" value="Helpdesk" />
   <input type="hidden" name="_auto_import" value="1" />
      <h2 align='center'>Formulaire de signalement au support technique</h2>

   <fieldset>
      <legend> Le probl&egrave;me doit &ecirc;tre r&eacute;solu</legend>

      S&eacute;lectionnez un niveau d'urgence :
      <select name="urgency">
         <option value="5">Tr&egrave;s haute</option>
         <option value="4">Haute</option>
         <option value="3" selected='selected'>Moyenne</option>
         <option value="2">Basse</option>
         <option value="1">Tr&egrave;s Basse</option>
      </select>
   </fieldset>

   <fieldset>
      <legend>D&eacute;crivez votre probl&egrave;me</legend>
      Titre : <input type='text' name="name" size='60' /><br />
      <textarea name="content" cols="60" rows="20"></textarea>
   </fieldset>

<p align='center'><input type="submit" name= 'add' value="Envoyer" /></p>

<?php
// Close form for CSRF
include ('../inc/includes.php');
Html::closeForm();

?>
</div>
</body>
</html>