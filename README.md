![GLPI Logo](https://raw.githubusercontent.com/glpi-project/glpi/master/pics/logos/logo-GLPI-250-black.png)

![GLPI CI](https://github.com/glpi-project/glpi/workflows/GLPI%20CI/badge.svg?branch=9.5%2Fbugfixes)
[![Github All Releases](https://img.shields.io/github/downloads/glpi-project/glpi/total.svg)](#download)
[![Twitter Follow](https://img.shields.io/twitter/follow/GLPI_PROJECT.svg?style=social&label=Follow)](https://twitter.com/GLPI_PROJECT)


## About GLPI

GLPI stands for **Gestionnaire Libre de Parc Informatique** is a Free Asset and IT Management Software package, that provides ITIL Service Desk features, licenses tracking and software auditing.

GLPI features:
* Inventory of computers, peripherals, network printers and any associated components through an interface, with inventory tools such as: [FusionInventory](http://fusioninventory.org/) or [OCS Inventory](https://www.ocsinventory-ng.org/)
* Data Center Infrastructure Management (DCIM)
* Item lifecycle management
* Licenses management (ITIL compliant)
* Management of warranty and financial information (purchase order, warranty and extension, damping)
* Management of contracts, contacts, documents related to inventory items
* Incidents, requests, problems and changes management
* Knowledge base and Frequently-Asked Questions (FAQ)
* Asset reservation

Moreover, GLPI supports many [plugins](http://plugins.glpi-project.org) that provide additional features.

## Demonstration

Check GLPI features by asking a free personnal demonstration on **[glpi-network.cloud](https://www.glpi-network.cloud)**

## License

![license](https://img.shields.io/github/license/glpi-project/glpi.svg)

It is distributed under the GNU GENERAL PUBLIC LICENSE Version 2 - please consult the file called [COPYING](https://raw.githubusercontent.com/glpi-project/glpi/master/COPYING.txt) for more details.

## Some screenshots

**Tickets Timeline**

![Tickets Timeline](pics/screenshots/timeline.png)

**DCIM drag&drop**

![DCIM drag&drop](pics/screenshots/dcim_racks_draganddrop.gif)

**Components**

![Components](pics/screenshots/components.png)

## Prerequisites

* A web server (Apache, Nginx, IIS, etc.)
* MariaDB >= 10.0 or MySQL >= 5.6
* PHP 7.2 or higher
* Mandatory PHP extensions:
    - ctype
    - curl
    - gd (picture generation)
    - iconv
    - intl
    - json
    - mbstring
    - mysqli
    - session
    - simplexml
    - zlib

* Recommended PHP extensions (to enable optional features)
    - exif (security enhancement on images validation)
    - imap (mail collector and users authentication)
    - ldap (users authentication)
    - openssl (encrypted communication)
    - sodium (performances enhancement on sensitive data encryption/decryption)
    - zip and bz2 (installation of zip and bz2 packages from marketplace)

 * Supported browsers:
    - Edge
    - Firefox (including 2 latests ESR version)
    - Chrome

Please, consider using browsers on editor's supported version


## Download

See :
* [releases](https://github.com/glpi-project/glpi/releases) for tarball packages.
* [Remi's RPM repository](http://rpms.remirepo.net/) for RPM packages (Fedora, RHEL, CentOS)


## Documentation

Here is a [pdf version](https://forge.glpi-project.org/attachments/download/1901/glpidoc-0.85-en-partial.pdf).
We are working on a [markdown version](https://github.com/glpi-project/doc)

* [Installation](https://readthedocs.org/projects/glpi-install/)
* [Update](https://glpi-install.readthedocs.io/en/latest/update.html)


## Additional resources

* [Official website](http://glpi-project.org)
* [Demo](https://www.glpi-network.cloud)
* [Translations on transifex service](https://www.transifex.com/glpi/public/)
* [Issues](https://github.com/glpi-project/glpi/issues)
* [Suggestions](http://suggest.glpi-project.org)
* [Forum](http://forum.glpi-project.org)
* IRC : irc://irc.freenode.org/glpi
* [Development documentation](http://glpi-developer-documentation.readthedocs.io/en/master/)
* [Plugin directory](http://plugins.glpi-project.org)
* [Plugin development documentation](http://glpi-developer-documentation.readthedocs.io/en/master/plugins/index.html)


## Support
GLPI is a living software. Improvements are continuously made, new functionalities are being developed, and issues are being fixed.

To ease support and development, we need your help when encountering issues.
There is a GLPI version typical lifecycle:
 * A new major version (9.3) is released.
 * Minor versions (9.3.x), fixing bugs or issues, are published after several weeks.
   Please consider updating to the latest realeased minor version if you encounter some bugs or performance issues.
 * Several months after major version realesed, a new major version (9.4) is released
   Previous major versions become unsupported, please update to the new major version.
   Obviously, we provide support for the migration tools too!
