<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 


// English Dictionary 

//Login

$lang["login"][0] = "Impossible to log in, make sure your browser accepts cookies";
$lang["login"][1] = "Log in again";
$lang["login"][2] = "Your password is no longer valid, if you've just changed it, please log in with your new password";
$lang["login"][3] = "Not Logged in";
$lang["login"][4] = "Bad Password";
$lang["login"][5] = "Access Denied";


// Global
$lang["buttons"][0]	= "Search";
$lang["buttons"][1]	= "List";
$lang["buttons"][2]	= "Post";
$lang["buttons"][3]	= "Assign";
$lang["buttons"][4]	= "Install";
$lang["buttons"][5]	= "Uninstall";
$lang["buttons"][6]	= "Delete";
$lang["buttons"][7]	= "Update";
$lang["buttons"][8]	= "Add";
$lang["buttons"][9]	= "Connect";
$lang["buttons"][10]	= "Disconnect";
$lang["buttons"][11]	= "Next";
$lang["buttons"][12]	= "Previous";
$lang["buttons"][13]	= "Back";
$lang["buttons"][14]	= "Change";
$lang["buttons"][15]    = "Schedule";

$lang["choice"][0]	= "No";
$lang["choice"][1]	= "Yes";

$lang["search"][0]	= "Search by";
$lang["search"][1]	= "where this field";
$lang["search"][2]	= "contains";
$lang["search"][3]	= "is the exact sentence";
$lang["search"][4]	= "sorted by";
$lang["search"][5]	= "View by location";
$lang["search"][6]	= "sorted by";

// Central
$lang["central"][0] 	= "Welcome ";
$lang["central"][1] 	= "this is the Command Center.";
$lang["central"][2] 	= "Last";
$lang["central"][3] 	= "Events";
$lang["central"][4] 	= "No Event.";

//repports


$lang["event"][0]	= "Item (ID)";
$lang["event"][1]	= "Date";
$lang["event"][2]	= "Service";
$lang["event"][3]	= "Level";
$lang["event"][4]	= "Message";

// Pager
$lang["pager"][1]	= "to";
$lang["pager"][2]	= "from";

// Direct Connections
$lang["connect"][0]	= "Direct Connections";
$lang["connect"][1]	= "not connected...";
$lang["connect"][2]	= "Connect";
$lang["connect"][3]	= "Disconnect";
$lang["connect"][4]	= "Make a direct connection";
$lang["connect"][5]	= "to a computer which";
$lang["connect"][6]	= "Name";
$lang["connect"][7]	= "ID";
$lang["connect"][8]	= "contains";
$lang["connect"][9]	= "Please choose a computer from the resultlist";

//header menu
$lang["Menu"][0]	="Computers";
$lang["Menu"][1]	="Networks";
$lang["Menu"][2]	="Printers";
$lang["Menu"][3]	="Monitors";
$lang["Menu"][4]	="Software";
$lang["Menu"][5]	="Display";
$lang["Menu"][6]	="Reports";
$lang["Menu"][7]	="Groups";
$lang["Menu"][8]	="Accounts";
$lang["Menu"][9]	="Mail accounts";
$lang["Menu"][10]	="Setup";
$lang["Menu"][11]	="Preferences";
$lang["Menu"][12]	="Data";

//Data

$lang["backup"][0]	    ="Dump SQL";
$lang["backup"][1]      ="Dump XML";
$lang["backup"][2]      ="Do a backup of the database";
$lang["backup"][3]      ="The file has been generated";
$lang["backup"][5]      ="Open the file";
$lang["backup"][6]      ="Structure for table";
$lang["backup"][7]      ="Data for table";
$lang["backup"][8]      ="successfully restored!";
$lang["backup"][9]      ="successfully deleted!";
$lang["backup"][10]     ="File";
$lang["backup"][11]     ="Size";
$lang["backup"][12]     ="Date";
$lang["backup"][13]     ="View/Download";
$lang["backup"][14]     ="Restore";
$lang["backup"][15]     ="Are you sure you want to save the database";
$lang["backup"][16]     ="Are you sure you want to overwrite the database with the file";
$lang["backup"][17]     ="Are you sure you want to delete this file";
$lang["backup"][18]     ="Are you sure you want to save the database";
$lang["backup"][19]     ="now";
$lang["backup"][20]     ="Del";
// Tracking
$lang["tracking"][0]	= "Search Tracking";
$lang["tracking"][1]	= "Show all active Jobs";
$lang["tracking"][2]	= "Show only Jobs assigned to you";
$lang["tracking"][3]	= "Show only Jobs not assigned yet";
$lang["tracking"][4]	= "Show only old Jobs";
$lang["tracking"][5]	= "Where the description contains";
$lang["tracking"][6]    = "You are not allowed to (re-)assign this job";

$lang["joblist"][0]	= "Status";
$lang["joblist"][1]	= "Date";
$lang["joblist"][2]	= "Priority";
$lang["joblist"][3]	= "Author";
$lang["joblist"][4]	= "Assigned";
$lang["joblist"][5]	= "Computer";
$lang["joblist"][6]	= "Description";
$lang["joblist"][7]	= "New Job for this item...";
$lang["joblist"][8]	= "No Jobs found.";
$lang["joblist"][9]	= "NEW";
$lang["joblist"][10]	= "OLD";
$lang["joblist"][11]	= "Opened";
$lang["joblist"][12]	= "Closed";
$lang["joblist"][13]	= "More&nbsp;Info";
$lang["joblist"][14]	= "Mark&nbsp;Old";
$lang["joblist"][15]	= "Assign";
$lang["joblist"][16]	= "No Tracking available.";
$lang["joblist"][17]	= "Very High";
$lang["joblist"][18]	= "High";
$lang["joblist"][19]	= "Normal";
$lang["joblist"][20]	= "Low";
$lang["joblist"][21]	= "Very Low";
$lang["job"][0]		= "Job Number";
$lang["job"][1]		= "Job still open...";
$lang["job"][2]		= "This job was open for";
$lang["job"][3]		= "Job is done, mark als old.";
$lang["job"][4]		= "Assign Job";
$lang["job"][5]		= "Assigned to";
$lang["job"][6]		= "Assign";
$lang["job"][7]		= "Followups";
$lang["job"][8]		= "No Followups for this job.";
$lang["job"][9]		= "Post new Followup for this job";
$lang["job"][10]	= "No Followups for this job.";
$lang["job"][11]	= "Describe the Problem/Action";
$lang["job"][12]	= "No Followups for this job.";
$lang["job"][13]	= "Add a new Job";
$lang["job"][14]	= "New Job";
$lang["job"][15]	= "Historical";
$lang["job"][16]	= "found";
$lang["job"][17]    = "intervention";

// Computers

$lang["computers"][0]	= "Add Computer...";
$lang["computers"][7]	= "Name";
$lang["computers"][8]	= "Type";
$lang["computers"][9]	= "OS";
$lang["computers"][10]	= "Location";
$lang["computers"][11]	= "Last modified";
$lang["computers"][12]	= "New Computer from template";
$lang["computers"][13]	= "Computer ID";
$lang["computers"][14]	= "Added on";
$lang["computers"][15]	= "Contact#";
$lang["computers"][16]	= "Contact person";
$lang["computers"][17]	= "Serial# 1";
$lang["computers"][18]	= "Serial# 2";
$lang["computers"][19]	= "Comments";
$lang["computers"][20]	= "OS Version";
$lang["computers"][21]	= "CPU";
$lang["computers"][22]	= "MHZ";
$lang["computers"][23]	= "RAM Type";
$lang["computers"][24]	= "RAM (MB)";
$lang["computers"][25]	= "HD (GB)";
$lang["computers"][26]	= "NIC Type";
$lang["computers"][27]	= "Flags";
$lang["computers"][28]	= "Server";
$lang["computers"][29]	= ""; 					// reserved
$lang["computers"][30]	= ""; 					// reserved
$lang["computers"][31]	= "ID";
$lang["computers"][32]	= "No Computers found.";
$lang["computers"][33]	= "Soundcard"; 					
$lang["computers"][34]	= "Graphicscard"; 					
$lang["computers"][35]	= "Mainboard"; 					
$lang["computers"][36]	= "HD-Type";
$lang["computers"][37]	= "No monitor connected.";
$lang["computers"][38]	= "No printer connected.";
$lang["computers"][39]	= "Printers";
$lang["computers"][40]	= "Monitors";
$lang["computers"][41]  = "Date purchase ";
$lang["computers"][42]	= "Warranty date expiration";
$lang["computers"][43]	= "Maintenance";
$lang["computers"][44]  = "Computers";
$lang["computers"][45] = "Select a template";

// Networking

$lang["networking"][0]	= "Name"; 
$lang["networking"][1]	= "Location"; 
$lang["networking"][2]	= "Type"; 
$lang["networking"][3]	= "Contact"; 
$lang["networking"][4]	= "Contact#"; 
$lang["networking"][5]	= "RAM (MB)"; 
$lang["networking"][6]	= "Serial# 1"; 
$lang["networking"][7]	= "Serial# 2"; 
$lang["networking"][8]	= "Comments"; 
$lang["networking"][9]	= "Last modified"; 
$lang["networking"][10]	= ""; 
$lang["networking"][11]	= "Add Netdevice..."; 
$lang["networking"][12]	= "Networking device"; 
$lang["networking"][13]	= "networking ports found"; 
$lang["networking"][14]	= "IP"; 
$lang["networking"][15]	= "MAC"; 
$lang["networking"][16]	= "Interface"; 
$lang["networking"][17]	= "Connected to"; 
$lang["networking"][18]	= ""; 
$lang["networking"][19]	= "Add networking port..."; 
$lang["networking"][20]	= "Port Manager"; 
$lang["networking"][21]	= "Logical Number"; 
$lang["networking"][22]	= "Interface Address"; 
$lang["networking"][23]	= "Interface MAC"; 
$lang["networking"][24]	= "Connection"; 
$lang["networking"][25]	= "on"; 
$lang["networking"][26]	= "Not connected."; 
$lang["networking"][27]	= "Connecting port"; 
$lang["networking"][28]	= "(Step 1)"; 
$lang["networking"][29]	= "to a computer that"; 
$lang["networking"][30]	= "contains"; 
$lang["networking"][31]	= "Or choose a network device"; 
$lang["networking"][32]	= "(Step 2)"; 
$lang["networking"][33]	= "Choose a computer from the results"; 
$lang["networking"][34]	= "Looks like a lonely device to me. No ports found."; 
$lang["networking"][35]	= "(last step)"; 
$lang["networking"][36]	= "Listing all ports on device"; 
$lang["networking"][37]	= "Networking port found"; 
$lang["networking"][38]	= "No devices found."; 
$lang["networking"][39]	= "Date purchase";
$lang["networking"][40]	= "Guaranteed expiration";
$lang["networking"][41]	= "Maintenance";
$lang["networking"][42] = "Identifier";
$lang["networking"][43] = "Networking";
// Printers
$lang["printers"][0]	= "Add Printer...";
$lang["printers"][1]	= "View by location";
$lang["printers"][2]	= "sorted by";
$lang["printers"][3]	= "Add printer";
$lang["printers"][4]	= "Printer";
$lang["printers"][5]	= "Name";
$lang["printers"][6]	= "Location";
$lang["printers"][7]	= "Contact#";
$lang["printers"][8]	= "Contact Person";
$lang["printers"][9]	= "Type";
$lang["printers"][10]	= "Serial# 1";
$lang["printers"][11]	= "Serial# 2";
$lang["printers"][12]	= "Comments";
$lang["printers"][13]	= "Location";
$lang["printers"][14]	= "Serial Interface";
$lang["printers"][15]	= "Parallel Interface";
$lang["printers"][16]	= "Last modified";
$lang["printers"][17]	= "No printers found.";
$lang["printers"][18]	= "Flags";
$lang["printers"][19]	= "ID";
$lang["printers"][20]   = "Date purchase ";
$lang["printers"][21]	= "Guaranteed expiration";
$lang["printers"][22]	= "Maintenance";
$lang["printers"][23]   = "RAM";
$lang["printers"][24]   = "Identifier";
$lang["printers"][25]   = "Printers";


// Monitors
$lang["monitors"][0]	= "Add Monitor...";
$lang["monitors"][3]	= "Add monitor";
$lang["monitors"][4]	= "Monitor";
$lang["monitors"][5]	= "Name";
$lang["monitors"][6]	= "Location";
$lang["monitors"][7]	= "Contact#";
$lang["monitors"][8]	= "Contact Person";
$lang["monitors"][9]	= "Type";
$lang["monitors"][10]	= "Serial# 1";
$lang["monitors"][11]	= "Serial# 2";
$lang["monitors"][12]	= "Comments";
$lang["monitors"][13]	= "Location";
$lang["monitors"][14]	= "Microphone";
$lang["monitors"][15]	= "Speakers";
$lang["monitors"][16]	= "Last modified";
$lang["monitors"][17]	= "No monitors found.";
$lang["monitors"][18]	= "Flags";
$lang["monitors"][19]	= "Sub-D";
$lang["monitors"][20]	= "BNC";
$lang["monitors"][21]	= "Size";
$lang["monitors"][22]	= "Connected to";
$lang["monitors"][23]	= "ID";
$lang["monitors"][24]   = "Date purchase ";
$lang["monitors"][25]	= "Guaranteed expiration";
$lang["monitors"][26]	= "Maintenance";
$lang["monitors"][27]   = "Identifier";
$lang["monitors"][28]	= "Monitors";


// Software
$lang["software"][0]	= "Add Software..."; 
$lang["software"][1]	= "ID"; 
$lang["software"][2]	= "Name"; 
$lang["software"][3]	= "Platform"; 
$lang["software"][4]	= "Location"; 
$lang["software"][5]	= "Version"; 
$lang["software"][6]	= "Comments"; 
$lang["software"][7]	= "Search Software"; 
$lang["software"][8]	= "where"; 
$lang["software"][9]	= "contains"; 
$lang["software"][10]	= "Software"; 
$lang["software"][11]	= "Licenses"; 
$lang["software"][12]	= "Add license..."; 
$lang["software"][13]	= "licenses found"; 
$lang["software"][14]	= "No licenses for this software."; 
$lang["software"][15]	= "Add licenses to software"; 
$lang["software"][16]	= "Serial Number (free for free)"; 
$lang["software"][17]	= "Installed Software"; 
$lang["software"][18]	= "License already installed."; 
$lang["software"][19]	= "Installed"; 
$lang["software"][20]	= "Remaining"; 
$lang["software"][21]	= "Total"; 
$lang["software"][22]	= "No Software found.";
$lang["software"][23]   = "Software";


// Reports
$lang["reports"][0]	= "Select a report to generate";
$lang["reports"][1] = "Printers on the maintain contract";
$lang["reports"][2] = "Monitors on the maintain contract";
$lang["reports"][3] = "Network devices on the maintain contract";
$lang["reports"][4] = "List of the hardware on the maintain contract";
$lang["reports"][5] = "Computers on the maintain contract";

// LDAP
$lang["ldap"][0]	= "Searching for";
$lang["ldap"][1]	= "Show";
$lang["ldap"][2]	= "and";
$lang["ldap"][3]	= "where the attribute";
$lang["ldap"][4]	= "Add new entry...";
$lang["ldap"][5]	= "required";
$lang["ldap"][6]	= "allowed";
$lang["ldap"][7]	= "LDAP";


// Setup
$lang["setup"][0]	= "Dropdowns"; 
$lang["setup"][1]	= "Templates"; 
$lang["setup"][2]	= "glpi Users"; 
$lang["setup"][3]	= "Locations"; 
$lang["setup"][4]	= "Computer Types"; 
$lang["setup"][5]	= "Operating Systems"; 
$lang["setup"][6]	= "RAM Types"; 
$lang["setup"][7]	= "CPUs"; 
$lang["setup"][8]	= "NICs"; 
$lang["setup"][9]	= "Network Interfaces"; 
$lang["setup"][10]	= "Inventory"; 
$lang["setup"][11]	= "Change password for"; 
$lang["setup"][12]	= "User"; 
$lang["setup"][13]	= "Realname"; 
$lang["setup"][14]	= "E-Mail"; 
$lang["setup"][15]	= "Phone#"; 
$lang["setup"][16]	= "Location"; 
$lang["setup"][17]	= "Account Type"; 
$lang["setup"][18]	= "Login"; 
$lang["setup"][19]	= "Password"; 
$lang["setup"][20]	= "Type"; 
$lang["setup"][21]	= "Don't forget to set a password later!"; 
$lang["setup"][22]	= "Add a template..."; 
$lang["setup"][23]	= "Template name"; 
$lang["setup"][24]	= "Name"; 
$lang["setup"][25]	= "Location"; 
$lang["setup"][26]	= "Contact#"; 
$lang["setup"][27]	= "Contact Person"; 
$lang["setup"][28]	= "Serial# 1"; 
$lang["setup"][29]	= "Serial# 2"; 
$lang["setup"][30]	= "Comments"; 
$lang["setup"][31]	= "Type"; 
$lang["setup"][32]	= "OS"; 
$lang["setup"][33]	= "OS Version"; 
$lang["setup"][34]	= "CPU"; 
$lang["setup"][35]	= "MHZ"; 
$lang["setup"][36]	= "RAM Type"; 
$lang["setup"][37]	= "RAM (MB)"; 
$lang["setup"][38]	= "HD (GB)"; 
$lang["setup"][39]	= "NIC Type"; 
$lang["setup"][40]	= "Display new jobs first?"; 
$lang["setup"][41]	= "Select Language"; 
$lang["setup"][42]	= "Networking Types"; 
$lang["setup"][43]	= "Printer Types"; 
$lang["setup"][44]	= "Monitor Types";
$lang["setup"][45]	= "Mainboards";
$lang["setup"][46]	= "Graphics Cards";
$lang["setup"][47]	= "Soundcards";
$lang["setup"][48]	= "Harddisk Types";
$lang["setup"][49]	= "Mainboard";
$lang["setup"][50]	= "Graphics Card";
$lang["setup"][51]	= "Soundcard";
$lang["setup"][52]	= "Harddisk Type";
$lang["setup"][53]  = "Date purchase ";
$lang["setup"][54]	= "Guaranteed expiration";
$lang["setup"][55]	= "Maintenance";
$lang["setup"][56]	= "Setup's";
$lang["setup"][57]  = "User Name";
$lang["setup"][58]  = "Allowed to assign a job";
$lang["setup"][59]  = "Setup who can assign a job";
$lang["setup"][60]  = "No";
$lang["setup"][61]  = "Yes";

// Helpdesk

$lang["help"][0]	= "Welcome";
$lang["help"][1]	= "Post-Only restrictions apply, please file your problem";
$lang["help"][2]	= "The problem should be solved";
$lang["help"][3]	= "yesterday";
$lang["help"][4]	= "in no time";
$lang["help"][5]	= "now";
$lang["help"][6]	= "next week";
$lang["help"][7]	= "if you have time";
$lang["help"][8]	= "Inform me about updates";
$lang["help"][9]	= "No";
$lang["help"][10]	= "Yes";
$lang["help"][11]	= "My E-Mail-Address";
$lang["help"][12]	= "My Machine-ID:";
$lang["help"][13]	= "The Problem";
$lang["help"][14]	= "Submit Problem";
$lang["help"][15]	= "No Description, please try again.";
$lang["help"][16]	= "You wanted to be notified about updates, but didn't give us your e-mail-address!";
$lang["help"][17]	= "Please give us your Machine-ID!";
$lang["help"][18]	= "Your job has been posted successfully, we are working on it.";
$lang["help"][19]	= "Thank you for using our automatic Helpdesk-System.";
$lang["help"][20]	= "Your job couldn't been added to our database.";
$lang["help"][21]	= "Please contact your local system administrator.";

  // Mois
  $lang["calendarM"][0] = "January" ;
  $lang["calendarM"][1] = "February" ;
  $lang["calendarM"][2] = "March" ;
  $lang["calendarM"][3] = "April" ;
  $lang["calendarM"][4] = "May" ;
  $lang["calendarM"][5] = "June" ;
  $lang["calendarM"][6] = "July" ;
  $lang["calendarM"][7] = "August" ;
  $lang["calendarM"][8] = "September" ;
  $lang["calendarM"][9] = "October" ;
  $lang["calendarM"][10] = "November" ;
  $lang["calendarM"][11] = "Décember" ;

  // Première lettre des jours de la semaine
  $lang["calendarD"][0] = "S" ;
  $lang["calendarD"][1] = "M" ;
  $lang["calendarD"][2] = "T" ;
  $lang["calendarD"][3] = "W" ;
  $lang["calendarD"][4] = "T" ;
  $lang["calendarD"][5] = "F" ;
  $lang["calendarD"][6] = "S" ;

?>
