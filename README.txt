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





