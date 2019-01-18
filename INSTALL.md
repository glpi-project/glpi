![GLPI Logo](https://raw.githubusercontent.com/glpi-project/glpi/master/pics/logos/logo-GLPI-250-black.png)

## How to install?

Installation procedure is entirely automated; there is an [installation documentation](https://readthedocs.org/projects/glpi-install/) you should rely on.

When you are using the source code, there are extra steps, to get all third party libraries installed.

First, [download and install composer](https://getcomposer.org/), a PHP dependency management tool. Once done, go to the GLPI directory and just run:

```bash
$ composer install --no-dev
```

The `--no-dev` flag will prevent development dependencies (such as [atoum, the unit test tool](https://atoum.org)) to be installed. Of course, if you plan to develop on this instance, you must have them installed.

Second, [download and install npm](https://www.npmjs.com/), a JS dependency management tool.
Once done, go to the GLPI directory and run following command to retrieve dependencies from npm repository:

```bash
$ npm install
```

Then, you run the following command to build dependencies into files used by GLPI:

```bash
$ npm run-script build
```

You can use `npm run-script build-dev` if you want to build dependencies for a development use.
