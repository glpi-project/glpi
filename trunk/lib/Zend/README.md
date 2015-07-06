### Welcome to the *Zend Framework 2.3* Release!

Master:
[![Build Status](https://secure.travis-ci.org/zendframework/zf2.png?branch=master)](http://travis-ci.org/zendframework/zf2)
[![Coverage Status](https://coveralls.io/repos/zendframework/zf2/badge.png?branch=master)](https://coveralls.io/r/zendframework/zf2)
Develop:
[![Build Status](https://secure.travis-ci.org/zendframework/zf2.png?branch=develop)](http://travis-ci.org/zendframework/zf2)
[![Coverage Status](https://coveralls.io/repos/zendframework/zf2/badge.png?branch=develop)](https://coveralls.io/r/zendframework/zf2)

## RELEASE INFORMATION

*Zend Framework 2.3.0*

This is the third minor (feature) release for the version 2 series.

12 Mar 2014

### UPDATES IN 2.3.0

This release ups the minimum required PHP version from 5.3.3 to **5.3.23**.
Making this change affords the following:

- 5.3.9 and up have a fix that allows a class to implement multiple interfaces
  that define the same method, so long as the signatures are compatible. Prior
  to that version, doing so raised a fatal error. This change is necessary in
  order to solve a problem with separated interface usage in the framework.

- 5.3.23 contains a [PHP bug #62672](https://bugs.php.net/bug.php?id=52861).
  Adopting this version or greater will allow us to (eventually) remove polyfill
  support that works around the symptoms of that issue.

As always, the Zend Framework project strongly recommends using the latest
version of PHP available to ensure you have the latest security fixes.

Additional updates that may affect existing applications include:

- [#5825](https://github.com/zendframework/zf2/pull/5825) adds a new
  `Zend\I18n\Translator` loader, `PhpMemoryArray`. This loader allows you to
  seed translation strings directly, instead of requiring a file that returns
  a PHP array; it's primary use case would be for seeing translations returned
  from a caching system.

- [#5901](https://github.com/zendframework/zf2/pull/5901) adds a new interface,
  `Zend\Authentication\AuthenticationServiceInterface`. You can not type-hint
  against this instead of `Zend\Authentication\AuthenticationService`.

- [#4455](https://github.com/zendframework/zf2/pull/4455) adds two new plugin
  managers to the default services: `LogProcessorManager` and
  `LogWriterManager`. These use the config keys `log_processors` and
  `log_writers`, respectively, and the module methods `getLogProcessorConfig()`
  and `getLogWriterConfig()`. The `LoggerAbstractServiceFactory` was updated to
  use these services when creating a `Logger` instance, and the `Logger` was
  updated to allow passing in these managers via the `processor_plugin_manager`
  and `writer_plugin_manager` configuration keys. This change will allow
  defining and configuring custom log processors and writers to wire with the
  logger abstract factory.

- [#5885](https://github.com/zendframework/zf2/pull/5885) adds the ability to
  specify the Locale via a route match parameter, and have it apply to the
  composed translator in the router, if any.

- [#5882](https://github.com/zendframework/zf2/pull/5882) adds the ability to
  set the formatter used by a `Zend\Log\Writer\Db` instance via the
  configuration options passed to the factory.

- [#5803](https://github.com/zendframework/zf2/pull/5803) adds a new flag to
  `Zend\Navigation` containers' `hasPages()` method, `$onlyVisible`. If set to
  `true`, only pages that are visibile based on ACLs will be considered.

- [#5759](https://github.com/zendframework/zf2/pull/5759) adds a new method to
  the `FlashMessenger` view helper, `renderCurrent()`, which will render
  messages registered with the `FlashMessenger` during the current request (vs.
  a previous request).

- [#5865](https://github.com/zendframework/zf2/pull/5865) removes the dependency
  on `Zend\Stdlib` in `Zend\Dom` by implementing PHP error handling directly in
  the component. This makes the component more easily portable.

- [#5840](https://github.com/zendframework/zf2/pull/5840) removes the class
  `Zend\Http\Client\Cookies`, as it was not used inside the framework itself,
  and could not be used with the HTTP client regardless.

- [#5436](https://github.com/zendframework/zf2/pull/5436) brings consistency
  to the `Zend\Filter` component, ensuring that exceptions are never thrown,
  and that values that a given filter cannot manipulate are returned unfiltered.

- [#5666](https://github.com/zendframework/zf2/pull/5666) removes the ability
  to translate form validation _messages_. This should never have been enabled
  in the first place, as validation message translation should be based on the
  message key, not the message string itself. In most cases, this should not
  affect existing applications, as most messages are dynamic and, as such, would
  result in the translator being unable to lookup a matching translation key.

- [#5664](https://github.com/zendframework/zf2/pull/5664) removes the ability
  to translate validator message _keys_. This should never have been enabled
  in the first place, as the keys are themselves to be used by the translator
  to find translation strings; they were not intended to be translated
  themselves.

- [#5649](https://github.com/zendframework/zf2/pull/5649) adds a new method
  to `Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase`,
  `assertResponseReasonPhrase()`. This allows developers to write assertions
  directly against the HTTP reason phrase they may have set in the response
  object.

- [#5406](https://github.com/zendframework/zf2/pull/5406) and
  [#5689](https://github.com/zendframework/zf2/pull/5689) make the i18n
  component optional for the MVC. 5406 does this by introducing a new interface,
  `Zend\I18n\Translator\TranslatorInterface` and a `DummyTranslator`
  implementation, and altering the MvcTranslator service factory to instantiate
  a `DummyTranslator` to inject into the `Zend\Mvc\I18n\Translator` instance.
  5689 updates the MVC translator factory to look for one of the following:

  - A defined `Zend\I18n\Translator\TranslatorInterface` service; if found,
    this will be injected into a `Zend\Mvc\I18n\Translator` instance.
  - Defined `translator` configuration; if so, this will be passed to the
    `Zend\I18n\Translator\Translator::factory` method, and the result injected
    in a `Zend\Mvc\I18n\Translator` instance.
  - If no configuration is found, a `DummyTranslator` will be created and injected
    into a `Zend\Mvc\I18n\Translator` instance.

- [#5469](https://github.com/zendframework/zf2/pull/5469) adds a new abstract
  controller, `Zend\Mvc\Controller\AbstractConsoleController`, for simplifying
  the creation of console controllers.

- [#5364](https://github.com/zendframework/zf2/pull/5364) adds "naming
  strategies" to hydrators, allowing transformation of the data keys
  when either hydrating or extracting data sets. This is implemented via a new
  interface, `Zend\Stdlib\Hydrator\NamingStrategy\NamingStrategyInterface`,
  which is now composed by default into the `AbstractHydrator`.

- [#5587](https://github.com/zendframework/zf2/pull/5587) changes the default
  cost for `Zend\Crypt\Password\Bcrypt` to 10, to keep it consistent with PHP's
  own default, as well as potentially mitigate DoS vectors (due to high
  computation cost).

- [#5356](https://github.com/zendframework/zf2/pull/5356) deprecates
  `Zend\Dom\Css2Path::transform` in favor of the new
  `Zend\Dom\Document\Query::cssToXpath`. Additionally, it properly cleans up the
  relations between documents, queries, and nodelists, providing a workflow
  similar to performing XPath queries in PHP:

  ```php
  use Zend\Dom\Document;
  $document = new Document($content);
  $nodeList = Document\Query::execute($expression, $document, Document\Query::TYPE_CSS);
  foreach ($nodeList as $node) {
      // ...
  }
  ```

  or, more succinctly:

  ```php
  use Zend\Dom\Document;
  foreach (
    Document\Query::execute($expression, new Document($content), Document\Query::TYPE_CSS)
    as $node
  ) {
      // ...
  }
  ```

  This API is intended to replace `Zend\Dom\Query`; however, `Zend\Dom\Query`
  remains in order to retain backwards compatibility.

- [#5043](https://github.com/zendframework/zf2/pull/5043) introduced changes in
  how DocBlock tag instances are returned via the `Zend\Code\Reflection`
  component. These instances are rarely created manually; however, if you are
  doing so, please note the following API changes:
  - `Zend\Code\Generator\DocBlock\Tag\AuthorTag`: removed `set/getDatatype()` and
    `set/getParamName()`
  - `Zend\Code\Generator\DocBlock\Tag\AuthorTag`: `__construct` changed from
    `($options = array())` to `($authorName = null, $authorEmail = null)`
  - `Zend\Code\Generator\DocBlock\Tag\LicenseTag`: `__construct` changed from
    `($options = array())` to `($url = null, $licenseName = null)`
  - `Zend\Code\Generator\DocBlock\Tag\ReturnTag`: `__construct` changed from
    `($options = array())` to `($types = array(), $description = null)`
  - `Zend\Code\Generator\DocBlock\Tag\ParamTag`: `__construct` changed from
    `($options = array())` to `($variableName = null, $types = array(),
    $description = null)`
  - Using `DocBlockGenerator::fromReflection()` and afterwards `getTags()` is now
    returning the new `Tag` classes (`ReturnTag`, `AuthorTag`, `ParamTag`, ...)
    where applicable and otherwise `GenericTag`. The deprecated class `Tag` will
    not be returned anymore.
- [#5101](https://github.com/zendframework/zf2/pull/5101) introduces a behavior
  change in the FormLabel view helper: it now escapes the label content by
  default. If you wish to disable escaping, you need to either pass the label
  option `disable_html_escape` to the form element, or call the
  `setEscapeHtmlHelper(false)` method on the `formLabel()` view helper.
- [#4962](https://github.com/zendframework/zf2/pull/4962) adds a service alias
  from "ControllerManager" to "ControllerLoader", and updates code to reference

Please see [CHANGELOG.md](CHANGELOG.md).

### SYSTEM REQUIREMENTS

Zend Framework 2 requires PHP 5.3.23 or later; we recommend using the
latest PHP version whenever possible.

### INSTALLATION

Please see [INSTALL.md](INSTALL.md).

### CONTRIBUTING

If you wish to contribute to Zend Framework, please read both the
[CONTRIBUTING.md](CONTRIBUTING.md) and [README-GIT.md](README-GIT.md) file.

### QUESTIONS AND FEEDBACK

Online documentation can be found at http://framework.zend.com/manual.
Questions that are not addressed in the manual should be directed to the
appropriate mailing list:

http://framework.zend.com/archives/subscribe/

If you find code in this release behaving in an unexpected manner or
contrary to its documented behavior, please create an issue in our GitHub
issue tracker:

https://github.com/zendframework/zf2/issues

If you would like to be notified of new releases, you can subscribe to
the fw-announce mailing list by sending a blank message to
<fw-announce-subscribe@lists.zend.com>.

## Reporting Potential Security Issues

If you have encountered a potential security vulnerability in Zend Framework, please report it to us at [zf-security@zend.com](mailto:zf-security@zend.com). We will work with you to verify the vulnerability and patch it.

When reporting issues, please provide the following information:

- Component(s) affected
- A description indicating how to reproduce the issue
- A summary of the security vulnerability and impact

We request that you contact us via the email address above and give the project contributors a chance to resolve the vulnerability and issue a new release prior to any public exposure; this helps protect Zend Framework users and provides them with a chance to upgrade and/or update in order to protect their applications.

For sensitive email communications, please use [our PGP key](http://framework.zend.com/zf-security-pgp-key.asc).

### LICENSE

The files in this archive are released under the Zend Framework license.
You can find a copy of this license in [LICENSE.txt](LICENSE.txt).

### ACKNOWLEDGEMENTS

The Zend Framework team would like to thank all the [contributors](https://github.com/zendframework/zf2/contributors) to the Zend
Framework project, our corporate sponsor, and you, the Zend Framework user.
Please visit us sometime soon at http://framework.zend.com.
