/*
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 LICENSE
 This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 ----------------------------------------------------------------------
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
 
Contents:

- WHAT IS IT?
- REQUIREMENTS
- FRESH INSTALLATION
- CONFIGURATION
- TESTING


*************************************************************************************************************
--- WHAT IS IT?
*************************************************************************************************************

GLPI is the Information Resource-Manager with an additional Administration-
Interface. You can use it to build up a database with an inventory for your 
company (computer, software, printers...). It has enhanced functions to make the daily life for the 
administrators easier, like a job-tracking-system with mail-notification and 
methods to build a database with basic information about your network-topology.

The principal functionalities of the application are : 

1) the precise inventory of all the technical resources. All their characteristics will be stored in a database. 

2) management and the history of the maintenance actions and the bound procedures. 
This application is dynamic and is directly connected to the users who can post requests to the technicians.
An interface thus authorizes the latter with if required preventing the service of maintenance 
and indexing a problem encountered with one of the technical resources to which they have access.


*************************************************************************************************************
--- REQUIREMENTS (only tested with this setup, try it on your own)
*************************************************************************************************************

1) Apache 1.3.>6 with PHP 4 (PHP3 doesn't work anymore)
2) MySQL 3.22 and +


*************************************************************************************************************
--- UPDATE FROM 0.3
*************************************************************************************************************

I) Save your configuration :

First of all you must do 2 things :

Save your databse using the GLPI interface. Get the dump file in the backups/dump/ directory 
and make a backup of it.

Backup the file config.php which is in the glpi/config/ directory 

Now, if the update is going wrong or that you do not appreciate the v0.4, 
it is easy to return to your preceding version.

Nevertheless, if this first step proceeded badly,  thanks for forwarding to us as fast as possible 
the procedure used and the error message which you obtain (on the mailing lists, or the forum of 
the site of the project, or on the bugtrack). 

II) Get and install the files


1) Delete your previous GLPI directory

2) Download the v0.4 tarball on the download ("Télécharger" in french) section of the website 
of the GLPI project (http://glpi.indepnet.org).


3) Uncompress the GLPI tarball where the previous one was.

4) Change acces rights to the following directory (add write access) :

-[your_http_root/]glpi/backups/dump
-[your_http_root/]glpi/glpi/config/

in order that PHP can write in them.

III)Launch the update :

1) Use your favorite browser to get the address http://yourserver/glpi/

2) Select your favorite langagea and click on « ok ».

3) Click on  « update ».

4) Verify that firsts checks succeed. If not follow the comments and retry.

If all checks are ok click on « continue ».

5) Configure the access to your Mysql server. You can found these informations in the backup of your config.php.
Mysql server : mysql hostname
Mysql user : mysql username
Mysql pass : mysql password of the mysql username

Then, click on « continue »

6) Select the database you want to update.
You can find hos name in the backup of your config.php file ($dbdefault variable).
Click on « continue »

7) You must confirm the update of the choosen database. Click on « continue » if it is right.

8) The update begin. The time of the update depend of your database size, so it must be quite long.

ATTENTION : The update do not keep your previous configurations whoch are store in the config.php file 
(general configuration, external auth, mailing, etc)

You must to use the post-install configuration forms in the "configuration" section of GLPI to setup them again.

All these configurations are only accessible by a new type of user: the « super-admin ».
The update automaticaly convert the « admin » user to « super-admin ».


Then, several cases are possible depending of your previous version


CASE 1: You had « admin » users or at least 1 « admin » user  was not connected to the application 
from external sources (ldap, IMAP) (in this case the password is not stored in the data base). 

All the « admin » users having a non-empty password become « super-admin ». You must to see a message.

Your database is up to date. You can now configure GLPI using the « use GLPI » button. Then, login as an old admin user.



CAS 2: You do not have « admin » user or all your admins have an empty password (because they login 
using an external source).

Then, you must to create a new « super-admin » user (do not enter a name already used by an other user)

If this step succeed, your database is up to date and you can use GLPI using the « use GLPI » button.
Then, login as the new  « super-admin » user you create.

When you finish to configure GLPI, you can delete this user.
Please, keep attention on the fact that you must always have a « super-admin » user 
in order to connect to GLPI when the external authentification sources are down.



*************************************************************************************************************
--- FRESH INSTALLATION
*************************************************************************************************************

The installation of GLPI is quite easy. Since the v0.4, there is no more file you should edit by hand.

The procedure is as follows:

1. Get the tarball of GLPI on our server. Unpack it on your computer. 
You obtain a directory called glpi containing the whole files of GLPI.

2. Copy this directory onto your server.

3. Using your browser, get the root of GLPI. You can now configure GLPI using an graphic interface.
After this step, you can use GLPI and begin to work.


------ Detailled procedure

--- Requirements

You should have a space on a web server with :
-  an access to the web server in order to install the files (FTP, SSH, etc) ;
-  PHP4 or later with the support of the sessions ;
-  an access to a MySQL database.


Before the installation, you must have a MySQL database available. If you are not the administrator 
of the server, it is necessary to ask for the activation of a MySQL base to the administrator. 

You must know the data of your MySQL connection (provided by the administrator): 
-  the host address of the MySQL server ;
-  your MySQL login ;
-  your MySQL password ;
-  the name of the database

--- Getting GLPI

GLPI is available in the website :
-  http://glpi.indepnet.org in the "téléchargement" section.

Choose the version you want to install. Unpack the tarball in your personnal computer.
Upload the obtained directory into your web server.

If your are the administrator of the server unpack the tarball in the root apache directory 
(/var/www or /var/www/html).

--- Install the files

Install the whole files of GLPI in your web space, where you want that GLPI is accessible to the public.

Now, it is necessary to modify permissions to some directories in order that PHP can write in:
/backups/dump and /glpi/config

--- Begin of the installation


From now, all is held online. To begin the installation you must to use your browser to get the root of GLPI:
default is  http://yourserver/glpi/


During the first connection, a step by step installation starts. 
The interface is user friendly, you must just enter the required informations. 

--- Preliminary steps:

A- Choose your favorite language.

Just choose your favorite langage and click to « OK »

B- Install or Update.

You want to do a fresh install, so click on  « Install ».

C- Compatibility checks to use GLPI

This step verifies that all requirements are ojk for the installtion of GLPI.
If something is wrong, you cannot continur the installtion. An error message will appear that explain you 
what to do to correct the problem.

If all is ok, you can click on « continue ».

--- Installation steps

Step 1 : Configuration of the access to the database server.

You must enter in a form all the informations needed to connect to MySQL.

« Mysql server » is the hostname where is your database server. For example: localhost or mysql.domain.tld

« Mysql user » is the username you use to connect to the server.

« Mysql pass » is the password of the username
This field can be empty if your user have no password (No comment will be done here on the security 
of such a user).

Then, you must to click on « continue ».

Two cases now:

-  Your parameters are rights. So, you access to the next step.
-  Your parameters are wrongs. So, an error message is displayed, you must to click on back to modify
your parameters and retry.

Step 2 : Choose or create the database.

The access to the database is ok. You mustr to create or choose the database that GLPI will use.

Two possibilities:

-  You want to use an existing database to store the GLPI tables:

Select this database and click on continue to initialize this database.

-  You want to create a new database to store the GLPI tables:

For this case, you must to have the rights to create a new database on the server.

Select « create a new database ». Enter the name of the database that you want to create in the text field.
Click on continue in order to initialize the database.

Step 3 : Temporary step and explanations :

This stage informs you that the database is initialized with the default values. 
Some informations are given to you on these values. Read this information attentively and click on « continue ». 

Step 4 : This stage indicates that the installation of GLPI is now finished, a summary is displayed.
Read these informations attentively and click on "use GLPI" to perform your first connection 
with the application. 

--- End of the installation

In case of error (of the kind: you forgot your own access to GLPI...), to start again this procedure of 
installation, it is necessary to use your software ftp (for example) and to erase the following file: 
-  glpi/config/config_db.php

Use your browser to get the root of GLPI: http://yourserver/glpi/ (by default) to start again 
the procedure of configuration then (actually, it is the absence of file "config_db.php" which causes 
the launching of this procedure). 
