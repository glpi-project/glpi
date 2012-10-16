### Welcome to the *Zend Framework 2.0.0* Release!

Master: [![Build Status](https://secure.travis-ci.org/zendframework/zf2.png?branch=master)](http://travis-ci.org/zendframework/zf2)

## RELEASE INFORMATION

*Zend Framework 2.0.0*

This is the first stable release of the new version 2 release branch.

04 September 2012

### NEW IN ZEND FRAMEWORK 2

New and/or refactored components include:

- EventManager - provides subject/observer, pubsub, aspect-oriented programming,
  signal slots, and event systems for your applications.
- ServiceManager - provides an Inversion of Control (IoC) container for managing
  object life-cycles and dependencies via a configurable and programmable
  interface.
- DI - provides a Dependency Injection Container, another form of IoC.
- MVC - a newly written MVC layer for ZF2, using controllers as services, and
  wiring everything together using events.
- ModuleManager - a solution for providing plug-and-play functionality for
  either MVC applications or any application requiring pluggable 3rd party code.
  Within ZF2, the ModuleManager provides the MVC with services, and assists in
  wiring events.
- Loader - provides new options for autoloading, including standard, performant
  PSR-0 loading and class-map-based autoloading.
- Code - provides support for code reflection, static code scanning, and
  annotation parsing.
- Config - more performant and more flexible configuration parsing and creation.
- Escaper - a new component for providing context-specific escaping solutions
  for HTML, HTML attributes, CSS, JavaScript, and combinations of contexts as
  well.
- HTTP - rewritten to provide better header, request, and response abstraction.
- I18n - a brand new internationalization and localization layer built on top of
  PHP's ext/intl extension.
- InputFilter - a new component for providing normalization and validation of
  sets of data.
- Form - rewritten from the ground up to properly separate validation, domain
  modeling, and presentation concerns. Allows binding objects to forms, defining
  forms and input filters via annotations, and more.
- Log - rewritten to be more flexible and provide better capabilities
  surrounding message formats and filtering.
- Mail - rewritten to separate concerns more cleanly between messages and
  transports.
- Session - rewritten to make testing easier, as well as to make it more
  configurable for end users.
- Uri - rewritten to provide a cleaner, more object oriented interface.

Many components have been ported from Zend Framework 1, and operate in
practically identical manners to their 1.X equivalent. Others, such as the
service components, have been moved to their own repositories to ensure that as
APIs change, they do not need to wait on the framework to release new versions.

Welcome to a new generation of Zend Framework!

### SYSTEM REQUIREMENTS

Zend Framework 2 requires PHP 5.3.3 or later; we recommend using the
latest PHP version whenever possible.

### INSTALLATION

Please see INSTALL.md.

### CONTRIBUTING

If you wish to contribute to Zend Framework 2.0, please read both the
README-DEV.md and README-GIT.md file.

### QUESTIONS AND FEEDBACK

Online documentation can be found at http://framework.zend.com/manual.
Questions that are not addressed in the manual should be directed to the
appropriate mailing list:

http://framework.zend.com/wiki/display/ZFDEV/Mailing+Lists

If you find code in this release behaving in an unexpected manner or
contrary to its documented behavior, please create an issue in the Zend
Framework issue tracker at:

http://framework.zend.com/issues/browse/ZF2

If you would like to be notified of new releases, you can subscribe to
the fw-announce mailing list by sending a blank message to
<fw-announce-subscribe@lists.zend.com>.

### LICENSE

The files in this archive are released under the Zend Framework license.
You can find a copy of this license in LICENSE.txt.

### ACKNOWLEDGEMENTS

The Zend Framework team would like to thank all the [contributors](https://github.com/zendframework/zf2/contributors) to the Zend
Framework project, our corporate sponsor, and you, the Zend Framework user.
Please visit us sometime soon at http://framework.zend.com.
