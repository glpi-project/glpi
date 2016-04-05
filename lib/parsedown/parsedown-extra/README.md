> You might also like [Caret](http://caret.io?ref=parsedown) - our Markdown editor for the Desktop.

## Parsedown Extra

[![Build Status](https://img.shields.io/travis/erusev/parsedown-extra/master.svg?style=flat-square)](https://travis-ci.org/erusev/parsedown-extra)

An extension of [Parsedown](http://parsedown.org) that adds support for [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/).

[See Demo](http://parsedown.org/extra/)

### Installation

Include both `Parsedown.php` and `ParsedownExtra.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown-extra).

### Example

``` php
$Extra = new ParsedownExtra();

echo $Extra->text('# Header {.sth}'); # prints: <h1 class="sth">Header</h1>
```

### Questions

**Who uses Parsedown Extra?**

[October CMS](http://octobercms.com/), [Bolt CMS](http://bolt.cm/), [Kirby CMS](http://getkirby.com/), [Grav CMS](http://getgrav.org/), [Statamic CMS](http://www.statamic.com/) and [more](https://www.versioneye.com/php/erusev:parsedown-extra/references).

**How can I help?**

Use it, star it, share it and in case you feel generous, [donate some money](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=528P3NZQMP8N2).
