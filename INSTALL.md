![GLPI Logo](https://raw.githubusercontent.com/glpi-project/glpi/master/pics/logos/logo-GLPI-250-black.png)

## How to install?

Installation procedure is entirely automated; there is an [installation documentation](https://readthedocs.org/projects/glpi-install/) you should rely on.

When you are using the source code, there are extra steps, to get all third party libraries installed.

First, [download and install composer](https://getcomposer.org/), a PHP dependency management tool.
Second, [download and install npm](https://www.npmjs.com/), a JS dependency management tool.
Once done, go to the GLPI directory and just run:

```bash
$ bin/console dependencies install
```
