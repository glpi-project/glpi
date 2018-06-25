![GLPI Logo](https://raw.githubusercontent.com/glpi-project/glpi/master/pics/logos/logo-GLPI-250-black.png)

## How to install?

Installation procedure is entirely automated; there is an [installation documentation](http://glpi-install.readthedocs.io/) you should rely on.

When you're using the source code, there are extra steps; to get all third party libraries installed.

First, [download and install composer](https://getcomposer.org), a PHP dependency management tool. Once done, got the the GLPI directory and just run:

```bash
$ composer install --no-dev
```

The `--no-dev` flag will prevent development dependencies (such as [atoum, the unit test tool](https://atoum.org)) to be installed. Of course, if you plan to develop on this instance, you must have them installed.

Second, you have to install JS third party libraries; this will be done using [bower](https://bower.io), this will require (npm)[https://www.npmjs.com/] to be installed on your system. As example, to get bower installed locally for your current user, you can got to the `glpi/public` directory and run:

```bash
$ npm install bower
```

Once it's finished, install JS dependencies:

```bash
$ ./node_modules/bower/bin/bower install
```
