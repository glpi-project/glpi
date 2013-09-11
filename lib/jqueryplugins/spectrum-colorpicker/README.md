# Spectrum
## The No Hassle Colorpicker

See the demo and docs: http://bgrins.github.io/spectrum.

I wanted a colorpicker that didn't require images, and that had an API that made sense to me as a developer who has worked with color in a number of applications.  I had tried a number of existing plugins, but decided to try and make a smaller, simpler one.

I started using canvas, then switched to CSS gradients, since it turned out to be easier to manage, and provided better cross browser support.

### Basic Usage

Head over to the [docs](http://bgrins.github.io/spectrum) for more information.  There is a visual demo of the different options hosted at: http://bgrins.github.io/spectrum.

    <script src='spectrum.js'></script>
    <link rel='stylesheet' href='spectrum.css' />

    <input id='colorpicker' />

    <script>
    $("#colorpicker").spectrum({
        color: "#f00"
    });
    </script>

### jQuery Plugin Repository

Spectrum is registered as a jQuery plugin on the jQuery plugin repository here: http://plugins.jquery.com/spectrum/

### Continuous Integration

[![Build Status](https://secure.travis-ci.org/bgrins/spectrum.png?branch=master)](http://travis-ci.org/bgrins/spectrum)

Visit https://travis-ci.org/bgrins/spectrum to view the status of the automated tests.

### Building Spectrum Locally

If you'd like to download and use the plugin, head over to http://bgrins.github.io/spectrum/ and click the 'Download Zip' button.

If you'd like to run the development version, spectrum uses Grunt to automate the testing, linting, and building.  Head over to http://gruntjs.com/getting-started for more information.  First, clone the repository, then run:

    npm install -g grunt-cli
    npm install
    grunt build

### Internationalization

If you are able to translate the text in the UI to another language, please do!  You can do so by either [filing a pull request](https://github.com/bgrins/spectrum/pulls) or [opening an issue]( https://github.com/bgrins/spectrum/issues) with the translation.  The existing languages are listed at: https://github.com/bgrins/spectrum/tree/master/i18n.
