jQuery Timepicker Addon
=======================

About
-----
- Author: [Trent Richardson](http://trentrichardson.com)
- Documentation: [http://trentrichardson.com/examples/timepicker/](http://trentrichardson.com/examples/timepicker/)
- Twitter: [@practicalweb](http://twitter.com/practicalweb)

Use
---
I recommend getting the eBook [Handling Time](https://sellfy.com/p/8gxZ) as it has a lot of example code to get started.  The quick and dirty:

- To use this plugin you must include jQuery (1.6+) and jQuery UI with datepicker (and optionally slider).
- Include timepicker-addon script and css located in the `dist` directory or from a CDN:
  * [http://cdnjs.com/libraries/jquery-ui-timepicker-addon](http://cdnjs.com/libraries/jquery-ui-timepicker-addon)
  * [http://www.jsdelivr.com/#!jquery.ui.timepicker.addon](http://www.jsdelivr.com/#!jquery.ui.timepicker.addon)
- now use timepicker with `$('#selector').datetimepicker()` or `$('#selector').timepicker()`.

There is also a [Bower](http://bower.io/) package named `jqueryui-timepicker-addon`.  Beware there are other similar package names that point to forks which may not be current.

Rails with Bower
-----------------
If you happen to use Rails with the [bower](gem "bower-rails", "~> 0.8.3") gem, here it is to use it easily :
#### Bowerfile 
```asset "jqueryui-timepicker-addon", "1.5.6"```

#### application.js
```javascript
//= require jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon
//= require jqueryui-timepicker-addon/dist/i18n/jquery-ui-timepicker-fr
```

#### application.css.scss
```scss
@import "jqueryui-timepicker-addon/dist/jquery-ui-timepicker-addon.css";
```

Contributing Code - Please Read!
--------------------------------
- All code contributions and bug reports are much appreciated.
- Please be sure to apply your fixes to the "dev" branch.
- Also note tabs are appreciated over spaces.
- Please read the [CONTRIBUTING.md][contributingmd] for more on using Grunt to produce builds.

[contributingmd]: CONTRIBUTING.md
