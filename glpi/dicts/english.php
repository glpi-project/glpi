<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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
 Translator: Simon DEPIETS 2df@tuxfamily.org
 ----------------------------------------------------------------------
*/



// English Dictionary

$lang["common"][0] = "Without name";

//Login

$lang["login"][0] = "Impossible to log in, make sure your browser accepts cookies";
$lang["login"][1] = "Log in again";
$lang["login"][2] = "Your password is no longer valid, if you've just changed it, please log in with your new password";
$lang["login"][3] = "Not Logged in";
$lang["login"][4] = "Bad Password";
$lang["login"][5] = "Access Denied";


// Global
$lang["buttons"][0] 	= "Search"; 
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
$lang["buttons"][16] 	= "Blank"; 
$lang["buttons"][17] 	= "Delete"; 
$lang["buttons"][18] 	= "Check All"; 
$lang["buttons"][19] 	= "UnCheck All"; 

$lang["choice"][0] 	= "Yes"; 
$lang["choice"][1] 	= "No"; 

$lang["search"][0]	= "Search by";
$lang["search"][1]	= "where this field";
$lang["search"][2]	= "contains";
$lang["search"][3]	= "is the exact sentence";
$lang["search"][4]	= "sorted by";
$lang["search"][5]	= "View by location";
$lang["search"][6]	= "sorted by";
$lang["search"][7]	= "Global search";
$lang["search"][8] 	= "Begin date"; 
$lang["search"][9] 	= "End date"; 

// Central
$lang["central"][0] 	= "Welcome ";
$lang["central"][1] 	= "this is the central console.";
$lang["central"][2] 	= "Last";
$lang["central"][3] 	= "Events";
$lang["central"][4] 	= "No Event.";
$lang["central"][5]	= "Home";
$lang["central"][6]	= "Logout";
$lang["central"][7] 	= "Help"; 
//repports


$lang["event"][0]	= "Item (ID)";
$lang["event"][1]	= "Date";
$lang["event"][2]	= "Service";
$lang["event"][3]	= "Level";
$lang["event"][4]	= "Message";

// Pager
$lang["pager"][1]	= "to";
$lang["pager"][2]	= "from";
$lang["pager"][3] 	= "in"; 

// Direct Connections
$lang["connect"][0]	= "Direct Connections";
$lang["connect"][1]	= "not connected...";
$lang["connect"][2]	= "Connect";
$lang["connect"][3]	= "Disconnect";
$lang["connect"][4]	= "Make a direct connection";
$lang["connect"][5] 	= "To a computer which"; 
$lang["connect"][6]	= "Name";
$lang["connect"][7]	= "ID";
$lang["connect"][8]	= "contains";
$lang["connect"][9]	= "Please choose a computer from the resultlist";
$lang["connect"][10] 	= "Please choose a printer from the resultlist"; 
$lang["connect"][11] 	= "Please choose a peripheral from the resultlist"; 
$lang["connect"][12] 	= "Please choose a monitor from the resultlist"; 
$lang["connect"][13] 	= "To a printer which"; 
$lang["connect"][14] 	= "To a peripheral which"; 
$lang["connect"][15] 	= "To a monitor which"; 
$lang["connect"][16] 	= "No item found"; 
$lang["connect"][17]	= "Automatic update of the unknown netpoint";
$lang["connect"][18]	= "Attention !! The netpoints of the two items are differents.";

//header menu
$lang["Menu"][0]	="Computers";
$lang["Menu"][1]	="Networks";
$lang["Menu"][2]	="Printers";
$lang["Menu"][3]	="Monitors";
$lang["Menu"][4]	="Software";
$lang["Menu"][5]	="Tracking";
$lang["Menu"][6]	="Reports";
$lang["Menu"][7]	="Groups";
$lang["Menu"][8]	="Accounts";
$lang["Menu"][9]	="Mail accounts";
$lang["Menu"][10]	="Setup";
$lang["Menu"][11]	="Setting";
$lang["Menu"][12]	="Data";
$lang["Menu"][13]	="Stats";
$lang["Menu"][14]	="Users";
$lang["Menu"][15]	="Administration";
$lang["Menu"][16]	="Peripherals";

//Data

$lang["backup"][0] 	= "Dump SQL"; 
$lang["backup"][1] 	= "Dump XMLa"; 
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
$lang["backup"][14]     ="Restored";
$lang["backup"][15]     ="Are you sure you want to save the database";
$lang["backup"][16]     ="Are you sure you want to overwrite the database with the file";
$lang["backup"][17]     ="Are you sure you want to delete this file";
$lang["backup"][18]     ="Are you sure you want to save the database";
$lang["backup"][19] 	= "Now"; 
$lang["backup"][20]     ="Del";
// Tracking
$lang["tracking"][0]	= "Follow up of interventions";
$lang["tracking"][1]	= "Show all active interventions";
$lang["tracking"][2]	= "Show only intervention assigned to you";
$lang["tracking"][3]	= "Show only interventions not assigned yet";
$lang["tracking"][4]	= "Show only old interventions";
$lang["tracking"][5]	= "Where the description contains";
$lang["tracking"][6]    = "You are not allowed to (re-)assign this intervention";
$lang["tracking"][7] 	= "Show..."; 
$lang["tracking"][8] 	= "No description, please retry"; 
$lang["tracking"][9] 	= "New intervention post, next..."; 
$lang["tracking"][10] 	= "Impossible to add the intervention, check the data base"; 
$lang["tracking"][11] 	= "Intervention(s) required by this user"; 

$lang["joblist"][0]	= "Status";
$lang["joblist"][1]	= "Date";
$lang["joblist"][2] 	= "Priority"; 
$lang["joblist"][3]	= "Author";
$lang["joblist"][4]	= "Assigned";
$lang["joblist"][5]	= "Computer";
$lang["joblist"][6]	= "Description";
$lang["joblist"][7]	= "New Job for this item...";
$lang["joblist"][8]	= "No Job found.";
$lang["joblist"][9]	= "NEW";
$lang["joblist"][10]	= "OLD";
$lang["joblist"][11]	= "Opened on";
$lang["joblist"][12]	= "Closed on";
$lang["joblist"][13]	= "Information";
$lang["joblist"][14]	= "Close";
$lang["joblist"][15]	= "Assign";
$lang["joblist"][16]	= "No intervention in progress.";
$lang["joblist"][17]	= "Very High";
$lang["joblist"][18]	= "High";
$lang["joblist"][19]	= "Normal";
$lang["joblist"][20]	= "Low";
$lang["joblist"][21]	= "Very Low";
$lang["joblist"][22] 	= "No old intervention found"; 
$lang["joblist"][23] 	= "Add an old intervention to the history"; 
$lang["job"][0]		= "Intervention Number";
$lang["job"][1]		= "Intervention still open...";
$lang["job"][2]		= "This job was open for";
$lang["job"][3]		= "Intervention is done, mark as old.";
$lang["job"][4]		= "Assign intervention";
$lang["job"][5]		= "Assigned to";
$lang["job"][6]		= "Assign";
$lang["job"][7]		= "Followups";
$lang["job"][8]		= "No Followups for this intervention.";
$lang["job"][9]		= "Post new Followup for this intervention";
$lang["job"][10]	= "No Followups for this intervention.";
$lang["job"][11]	= "Describe the Problem/Action";
$lang["job"][12]	= "No Followups for this intervention.";
$lang["job"][13]	= "Add a new intervention";
$lang["job"][14]	= "New intervention";
$lang["job"][15]	= "History";
$lang["job"][16]	= "found";
$lang["job"][17]    = "tracking";
$lang["job"][18]        = "old";
$lang["job"][19] 	= "Email Followups"; 
$lang["job"][20] 	= "Real time of the intervention"; 
$lang["job"][21] 	= "Hour(s)"; 
$lang["job"][22] 	= "Minute(s)"; 
$lang["job"][23] 	= "Restore this intervention"; 

// Computers

$lang["computers"][0]	= "Add Computer...";
$lang["computers"][1]   = "Id";
$lang["computers"][6]	= "Serial";
$lang["computers"][7]	= "Name";
$lang["computers"][8]	= "Type";
$lang["computers"][9]	= "OS";
$lang["computers"][10]	= "Location";
$lang["computers"][11]	= "Last modified";
$lang["computers"][12]	= "New Computer from template";
$lang["computers"][13]	= "Computer ID";
$lang["computers"][14]	= "Inserted";
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
$lang["computers"][26]	= "Adapter";
$lang["computers"][27]	= "Flags";
$lang["computers"][28]	= "Server";
$lang["computers"][29]	= ""; 					// reserved
$lang["computers"][30]	= ""; 					// reserved
$lang["computers"][31]	= "ID";
$lang["computers"][32]	= "No Computer found.";
$lang["computers"][33]	= "Soundcard";
$lang["computers"][34]	= "Graphics card";
$lang["computers"][35]	= "Motherboard";
$lang["computers"][36]	= "HD-Type";
$lang["computers"][37]	= "No connected monitor.";
$lang["computers"][38]	= "No connected printer";
$lang["computers"][39]	= "Printers";
$lang["computers"][40]	= "Monitors";
$lang["computers"][41]  = "Date of purchase ";
$lang["computers"][42]	= "Warranty expiration date";
$lang["computers"][43]	= "Maintenance";
$lang["computers"][44]  = "Computers";
$lang["computers"][45] = "Select a template";
$lang["computers"][46] 	= "Peripherals"; 
$lang["computers"][47] 	= "No connected peripheral"; 

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
$lang["networking"][9]	= "Last modification";
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
$lang["networking"][29]	= "to a computer whose";
$lang["networking"][30]	= "contains"; 
$lang["networking"][31]	= "Or choose a network device"; 
$lang["networking"][32]	= "(Step 2)"; 
$lang["networking"][33]	= "Choose a computer from the resultlist";
$lang["networking"][34]	= "Device not connected : no port found.";
$lang["networking"][35]	= "(last step)";
$lang["networking"][36]	= "List of all ports on device";
$lang["networking"][37]	= "Networking port found"; 
$lang["networking"][38]	= "No computer found.";
$lang["networking"][39]	= "Date of purchase";
$lang["networking"][40]	= "Warranty expiration date";
$lang["networking"][41]	= "Maintenance";
$lang["networking"][42] = "Identifier";
$lang["networking"][43] = "Networking";
$lang["networking"][44] = "The port";
$lang["networking"][45] = "is now connected on port";
$lang["networking"][46] 	= "Add several ports..."; 
$lang["networking"][47] 	= "From"; 
$lang["networking"][48] 	= "To"; 
$lang["networking"][49] 	= "Firmware"; 
$lang["networking"][50] 	= "ID"; 
$lang["networking"][51] 	= "Network point"; 
$lang["networking"][52] 	= "Netpoint "; 





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
$lang["printers"][16]	= "Last modification";
$lang["printers"][17]	= "No printer found.";
$lang["printers"][18]	= "Flags";
$lang["printers"][19]	= "ID";
$lang["printers"][20]   = "Date of purchase ";
$lang["printers"][21]	= "Warranty expiration date";
$lang["printers"][22]	= "Maintenance";
$lang["printers"][23]   = "RAM";
$lang["printers"][24]   = "Identifier";
$lang["printers"][25]   = "Printers";
$lang["printers"][26] 	= "Number"; 
$lang["printers"][27] 	= "USB Port"; 


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
$lang["monitors"][16]	= "Last modification";
$lang["monitors"][17]	= "No monitor found.";
$lang["monitors"][18]	= "Flags";
$lang["monitors"][19]	= "Sub-D";
$lang["monitors"][20]	= "BNC";
$lang["monitors"][21]	= "Size";
$lang["monitors"][22]	= "Connected to";
$lang["monitors"][23]	= "ID";
$lang["monitors"][24]   = "Date of purchase ";
$lang["monitors"][25]	= "Warranty expiration date";
$lang["monitors"][26]	= "Maintenance";
$lang["monitors"][27]   = "Identify";
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
$lang["software"][14]	= "No license for this software.";
$lang["software"][15]	= "Add licenses to software"; 
$lang["software"][16]	= "Serial Number (free for free)";
$lang["software"][17]	= "Installed Software"; 
$lang["software"][18]	= "License already installed."; 
$lang["software"][19]	= "Installed";
$lang["software"][20]	= "Remaining";
$lang["software"][21]	= "Total";
$lang["software"][22]	= "No Software found.";
$lang["software"][23]   = "Software";
$lang["software"][24] 	= "End date"; 
$lang["software"][25] 	= "Expire on"; 
$lang["software"][26] 	= "Never expired"; 
$lang["software"][27] 	= "Expired licence"; 



// Peripherals
$lang["peripherals"][0]	= "Add peripheral...";
$lang["peripherals"][3]	= "Add peripheral...";
$lang["peripherals"][4]	= "Peripheral";
$lang["peripherals"][5]	= "Name";
$lang["peripherals"][6]	= "Location";
$lang["peripherals"][7]	= "Contact #";
$lang["peripherals"][8]	= "Contact person";
$lang["peripherals"][9]	= "Type";
$lang["peripherals"][10]	= "Serial# 1";
$lang["peripherals"][11]	= "Serial#  2";
$lang["peripherals"][12]	= "Comment";
$lang["peripherals"][13]	= "Location";
$lang["peripherals"][14]	= "";
$lang["peripherals"][15]	= "";
$lang["peripherals"][16]	= "Last modification";
$lang["peripherals"][17]	= "No peripheral found";
$lang["peripherals"][18]	= "Brand";
$lang["peripherals"][19]	= "";
$lang["peripherals"][20]	= "";
$lang["peripherals"][21]	= "Size";
$lang["peripherals"][22]	= "Connected to";
$lang["peripherals"][23]	= "ID";
$lang["peripherals"][24]   = "Date of purchase ";
$lang["peripherals"][25]	= "Warranty expiration date";
$lang["peripherals"][26]	= "Maintenance";
$lang["peripherals"][27]   = "identifiant";
$lang["peripherals"][28]	= "Peripherals";



// Reports
$lang["reports"][0]	= "Select a report you want to generate";
$lang["reports"][1]     = "Printers under maintenance contract";
$lang["reports"][2]     = "Monitors under maintenance contract";
$lang["reports"][3]     = "Network devices under maintenance contract";
$lang["reports"][4]     = "List of the hardware under maintenance contract";
$lang["reports"][5]     = "Computers under maintenance contract";
$lang["reports"][6]	="Computers";
$lang["reports"][7]	="Printers";
$lang["reports"][8]	="Net devices";
$lang["reports"][9]	="Monitors";
$lang["reports"][10] 	= "Select type to be displayed"; 
$lang["reports"][11] 	= "Hardwares under maintenance contract"; 
$lang["reports"][12] 	= "Hardware Type"; 
$lang["reports"][13] 	= "Buy date(s)"; 
$lang["reports"][14] 	= "Sort option"; 
$lang["reports"][15] 	= "Show report"; 
$lang["reports"][16] 	= "All"; 
$lang["reports"][17] 	= "Buy date"; 
$lang["reports"][18] 	= "Serial number"; 
$lang["reports"][19] 	= "Contact name"; 
$lang["reports"][20] 	= "Location"; 
$lang["reports"][21] 	= "Warranty expiration date"; 
$lang["reports"][22] 	= "Date type"; 
$lang["reports"][23] 	= "Date(s)"; 
$lang["reports"][24] 	= "Restrict to computers"; 
$lang["reports"][25]    ="Tracking report";
$lang["reports"][26]    ="Default report";
$lang["reports"][27]    ="Maintenance";
$lang["reports"][28] 	= "By year"; 
$lang["reports"][29] 	= "Peripherals"; 

// LDAP
$lang["ldap"][0]	= "Search";
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
$lang["setup"][2]	= "Add a GLPI user";
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
$lang["setup"][53]      = "Date purchase ";
$lang["setup"][54]	= "Guaranteed expiration";
$lang["setup"][55]	= "Maintenance";
$lang["setup"][56]	= "Settings";
$lang["setup"][57]      = "User Name";
$lang["setup"][58]      = "Right of intervention assignement";
$lang["setup"][59]      = "Setup the rights of intervention assignement";
$lang["setup"][60]      = "No";
$lang["setup"][61]      = "Yes";
$lang["setup"][62] 	= "Select the category to configure"; 
$lang["setup"][63] 	= "Attention you are about to remove a heading used for one or more items"; 
$lang["setup"][64] 	= "If you confirm the deletion the items using this heading will see allotting fields NULL"; 
$lang["setup"][65] 	= "You can also replace all the events of this heading by another:"; 
$lang["setup"][66] 	= "No user found"; 
$lang["setup"][67] 	= "External authentifications"; 
$lang["setup"][68] 	= "Email Followup"; 
$lang["setup"][69] 	= "Peripheral types"; 
$lang["setup"][70] 	= "General setup"; 
$lang["setup"][71] 	= "Firmware"; 
$lang["setup"][72] 	= "Select a dropdown"; 
$lang["setup"][73] 	= "Network point"; 

$lang["setup"][100] 	= "General setup"; 
$lang["setup"][101]="Installation path";
$lang["setup"][102] 	= "Log Level"; 
$lang["setup"][103]="1- Critical (login error only)";
$lang["setup"][104]="2- Severe (Not used) ";
$lang["setup"][105]="3- Important (succed logins) ";
$lang["setup"][106]="4- Notices (Add, delete, tracking)";
$lang["setup"][107]="5- Complete (All) ";
$lang["setup"][108]="Number of log events to be printed";
$lang["setup"][109]="How long logs are keep in database (in days, 0 for infinite)";
$lang["setup"][110]="Show the interventions at the login ";
$lang["setup"][111]="Number of elements to be printed by page";
$lang["setup"][112]="Maximum number of characters for each elements of the list ";

$lang["setup"][150]="External sources of authentification";
$lang["setup"][151]="If you do not wish to use LDAP as source of connection leave the empty fields";
$lang["setup"][152]="LDAP configuration";
$lang["setup"][153]="LDAP Host";
$lang["setup"][154]="Basedn";
$lang["setup"][155]="rootdn (for non anonymous binds)";
$lang["setup"][156]="Pass (for non-anonymous binds)";
$lang["setup"][157]="The LDAP extension of your parser PHP is not installed";
$lang["setup"][158]="Impossible to use LDAP as external source of connection";
$lang["setup"][159] 	= "Connection filter"; 

$lang["setup"][160]="If you do not wish to use IMAP/POP as source of connection leave the empty fields";
$lang["setup"][161]="More information for syntax of Auth Server on <a target=\"_blank\" href=\"http://www.php.net/manual/en/function.imap-open.php\">php.net</a>. The parameter which interests you is the first (mailbox).";
$lang["setup"][162]="IMAP/POP configuration";
$lang["setup"][163]="IMAP/POP Auth Server";
$lang["setup"][164]="IMAP/POP Host Name (users email will be login@thishost)";
$lang["setup"][165]="Your parser PHP was compiled without the IMAP functions";
$lang["setup"][166]="Impossible to use IMAP/POP as external source of connection";



// Helpdesk

$lang["help"][0]	= "Welcome";
$lang["help"][1]	= "Please describe your problem";
$lang["help"][2]	= "The problem must be solved";
$lang["help"][3] 	= "Yesterday"; 
$lang["help"][4]	= "Very quickly";
$lang["help"][5]	= "In the course of the day";
$lang["help"][6] 	= "Next week"; 
$lang["help"][7] 	= "When time is free"; 
$lang["help"][8]	= "Inform me about the taken pursuant actions ";
$lang["help"][9]	= "No";
$lang["help"][10]	= "Yes";
$lang["help"][11]	= "My E-Mail";
$lang["help"][12]	= "My Machine-ID:";
$lang["help"][13]	= "The Problem";
$lang["help"][14]	= "Submit Message";
$lang["help"][15]	= "No Description, please try again.";
$lang["help"][16]	= "If you want be notified about the taken pursuant actions, you must enter your e-mail!";
$lang["help"][17]	= "Please enter your Machine-ID!";
$lang["help"][18]	= "Your message has been sent successfully, your request is in hand.";
$lang["help"][19]	= "Thank you for using our automatic Helpdesk-System.";
$lang["help"][20]	= "The description of your problem could not be added to our database.";
$lang["help"][21]	= "Please contact your local system administrator.";
$lang["help"][22] 	= "Search the ID of your computer"; 
$lang["help"][23] 	= "Enter the first letters (user name, computer or serial)"; 

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
  $lang["calendarM"][11] = "December" ;

  // Première lettre des jours de la semaine
  $lang["calendarD"][0] = "S" ;
  $lang["calendarD"][1] = "M" ;
  $lang["calendarD"][2] = "T" ;
  $lang["calendarD"][3] = "W" ;
  $lang["calendarD"][4] = "T" ;
  $lang["calendarD"][5] = "F" ;
  $lang["calendarD"][6] = "S" ;

$lang["mailing"][0]="-----------------------";
$lang["mailing"][1]="--------------------------------------";
$lang["mailing"][2]="Author : ";
$lang["mailing"][3]="Contents : ";
$lang["mailing"][4]="Intervention(s) already done";
$lang["mailing"][5]="Description of the intervention";
$lang["mailing"][6]="Beginning's date : ";
$lang["mailing"][7]="Computer concerned : ";
$lang["mailing"][8]="Attributed to : ";
$lang["mailing"][9]="New intervention's demand";
$lang["mailing"][10]="New intervention's follow up";
$lang["mailing"][11]="Intervention finished the ";
$lang["mailing"][12]="Attribution of the intervention";
$lang["mailing"][13]="Error in the automatic e-mail generation";

$lang["stats"][0] = "Select stats to be displayed";
$lang["stats"][1] = "Global";
$lang["stats"][2] = "Per technician";
$lang["stats"][3] = "Per location";
$lang["stats"][4] = "Per user";
$lang["stats"][5] = "Number of interventions";
$lang["stats"][6] = "Average resolution delay";
$lang["stats"][7] = "Max resoltution delay";
$lang["stats"][8] = "This Mounth";
$lang["stats"][9] = "This year";
$lang["stats"][10] = "Total";
$lang["stats"][11] = "Number off resolved interventions";
$lang["stats"][12] = "Global statistics";
$lang["stats"][13] = "Number of attribued interventions";
$lang["stats"][14] = "Number of resolver interventions";
$lang["stats"][15] = "Average resolution delay";
$lang["stats"][16] = "Technician name";
$lang["stats"][17] = "Technician's stats";
$lang["stats"][18] = "User's stats";
$lang["stats"][19] = "Location stats";
$lang["stats"][20] = "Username";
$lang["stats"][21] = "Location";
$lang["stats"][22] = "Numbre of asked interventions";
$lang["stats"][23] = "No stats are available";
$lang["stats"][25] 	= "Average real time of intervention"; 
$lang["stats"][26] 	= "Types"; 
$lang["stats"][27] 	= "Total real time of intervention"; 
$lang["stats"][28] 	= "Maximum time to resolve an intervention"; 
$lang["stats"][29] 	= "Minimum delay to take into account the intervention"; 
$lang["stats"][30] 	= "Average delay to take into account the intervention"; 
$lang["stats"][31] 	= "Day(s)"; 
$lang["stats"][32] 	= "Hour(s)"; 
$lang["stats"][33] 	= "Min(s)"; 
$lang["stats"][34] 	= "Sec(s)"; 

$lang["install"][0] 	= "Install or update GLPI"; 
$lang["install"][1]	= " Choose \"Install\" for a new complete installation of GLPI ( the imported data by GLPI will be the default datas).";
$lang["install"][2]	= "Choose \"Update\" for an update of GLPI from a former version";
$lang["install"][3] 	= "Install"; 
$lang["install"][4] 	= "Update"; 
$lang["install"][5]	= "Checking of the compatibility of your environment with the execution of GLPI";
$lang["install"][6] 	= "Test done"; 
$lang["install"][7] 	= "Results"; 
$lang["install"][8] 	= "Test PHP Parser"; 
$lang["install"][9] 	= "You must to install PHP4. You must download it here :"; 
$lang["install"][10]	= "You use 4.0.0 or 4.0.1 PHP version - We advise you to update your PHP"; 
$lang["install"][11] 	= "PHP version is 4.x - Perfect !"; 
$lang["install"][12] 	= "Sessions Test"; 
$lang["install"][13]	= "Your PHP parser is not compiled with the support of the sessions ! "; 
$lang["install"][14] 	= "Support of the sessions is available  - Perfect !"; 
$lang["install"][15]	= "Check that the support of the sessions is well activated in your php.ini "; 
$lang["install"][16]	= "Check writing mode for dump files"; 
$lang["install"][17]	= "The file could not be created."; 
$lang["install"][18]	= "Check that PHP has a right of writing for the directory: 'backups/dump/'. If you are under a Microsoft Windows environment, check if it is only in read right mode."; 
$lang["install"][19]	= "The file was created but can not be deleted."; 
$lang["install"][20] 	= "File was deleted - Perfect !"; 
$lang["install"][21]	= "Check writing mode for temporary files";
$lang["install"][22]	= "Check that PHP has a right of writing for the directory: 'reports/reports/convexcel/tmp/'. If you are under a Microsoft Windows environment, check if it is only in read right mode."; 
$lang["install"][23] 	= "Check writing mode for setting diles"; 
$lang["install"][24]	= "Check that PHP has a right of writing for the directory: 'glpi/config/'. If you are under a Microsoft Windows environment, check if it is only in read right mode."; 
$lang["install"][25] 	= "Continue ?"; 
$lang["install"][26] 	= "Continue"; 
$lang["install"][27] 	= "Retry"; 
$lang["install"][28] 	= "Database connection setup"; 
$lang["install"][29] 	= "Database connection parameters"; 
$lang["install"][30]	= "Mysql server";
$lang["install"][31]	= "Mysql user"; 
$lang["install"][32]	= "Mysql pass"; 
$lang["install"][33] 	= "Back"; 
$lang["install"][34] 	= "Test of the connection at the database"; 
$lang["install"][35] 	= "Impossible to connect at the databse"; 
$lang["install"][36] 	= "The server answered"; 
$lang["install"][37] 	= "Server or/and user field is empty"; 
$lang["install"][38] 	= "Please select a database:"; 
$lang["install"][39] 	= "Create a new database:"; 
$lang["install"][40] 	= "Please select the database to update:"; 
$lang["install"][41] 	= "Impossible to use the database:"; 
$lang["install"][42] 	= ""; 
$lang["install"][43] 	= "OK - database was initialized"; 
$lang["install"][44] 	= "Default values have been entered, delete them if you want"; 
$lang["install"][45] 	= "Do not delete the 'helpdesk' user"; 
$lang["install"][46] 	= "At the first connection, you can use the login &quot;glpi&quot; and the password &quot;glpi&quot; in order to access the application and have the administrator rights"; 
$lang["install"][47] 	= "Impossible to write the database setup file"; 
$lang["install"][48] 	= "Error in creating database !"; 
$lang["install"][49]	= "You did not select a database !"; 
$lang["install"][50] 	= ""; 
$lang["install"][51] 	= "GLPI setup :"; 
$lang["install"][52]	= "The preselected values are the default values, it is recommended to leave these values "; 
$lang["install"][53] 	= "Settings were indeed recorded"; 
$lang["install"][54]	= "Click on 'Continuer' to finish the installation"; 
$lang["install"][55]	= "The install is finished"; 
$lang["install"][56]	= "It is now recommended to apply a chmod+0 to the files install.php and update.php"; 
$lang["install"][57]	= "Default logins / passwords are:"; 
$lang["install"][58]	= "glpi/glpi for the  administrator account"; 
$lang["install"][59]	= "tech/tech for the technician account"; 
$lang["install"][60]	= "normal for the normal account"; 
$lang["install"][61]	= "post-only/post-only for the postonly account"; 
$lang["install"][62]	= "You can delete or modify these accounts as well as the first entries in the database."; 
$lang["install"][63]	= "Attention DO NOT DELETE the HELPDESK user."; 
$lang["install"][64] 	= "Use GLPI"; 
$lang["install"][70] 	= "Impossible to create the database connection file, please verify the rights on the files"; 

$lang["update"][90] 	= "Error during the database update"; 
$lang["update"][91] 	= "Attention !"; 
$lang["update"][92] 	= "You will update the GLPI database named :"; 
$lang["update"][93] 	= "Database connection succed"; 
$lang["update"][94] 	= "Update succed, your databse is up to date"; 
$lang["update"][95] 	= "Connection to databse failed, verify the connection parameters included in config_db.php file"; 
$lang["update"][96] 	= "Now, all administrators have &quot;super-admin&quot; rights. This new user type was also added."; 
$lang["update"][97]	= "The updated database do not contains any &quot;super-admin&quot; user";
$lang["update"][98]	= "You must to create one in order to be able to configure the application (in particular accesses to the external data sources )";
$lang["update"][99]	= "You will be able to remove this user once that you will have configured the application ";
$lang["update"][100] 	= "Enter the login"; 
$lang["update"][101] 	= "Enter the password"; 
$lang["update"][102] 	= "Re-enter the password"; 
$lang["update"][103]	= "The login or the password is empty, or you entered 2 different passwords";
$lang["update"][104]	= "Well recorded user you can connect to the application ";
$lang["update"][105]	= "Impossible to reach the update in this way!! ";
$lang["update"][106]	= "Go back to GLPI";

?>
