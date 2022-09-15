# HTMLDoc: PHP HTML Document Parser and Minifier

A tokeniser based HTML document parser and minifier, written in PHP.

[![Licence](https://img.shields.io/badge/Licence-MIT-lightgrey.svg)](LICENCE)
![Status: Stable](https://img.shields.io/badge/Status-Stable-Green.svg)
[![Tests Status](https://github.com/hexydec/htmldoc/actions/workflows/tests.yml/badge.svg)](https://github.com/hexydec/htmldoc/actions/workflows/tests.yml)
[![Code Coverage](https://codecov.io/gh/hexydec/htmldoc/branch/master/graph/badge.svg)](https://app.codecov.io/gh/hexydec/htmldoc)

## Description

An HTML parser, primarily designed for minifying HTML documents, it also enables the document structure to be queried allowing attribute and textnode values to be extracted.

Both parsers are designed around a tokeniser to make the document processing more reliable than regex based minifiers, which are a bit blunt and can be problematic if they match patterns in the wrong places.

The software is also capable of processing and minifying SVG documents.

## Usage

To minify an HTML document:

```php
use hexydec\html\htmldoc;

$doc = new htmldoc();

// load from a variable
if ($doc->load($html) {

	// minify the document
	$doc->minify();

	// compile back to HTML
	echo $doc->save();
}
```

You can test out the minifier online at [https://hexydec.com/htmldoc/](https://hexydec.com/htmldoc/), or run the supplied `index.php` file after installation.

To extract data from an HTML document:

```php
use hexydec\html\htmldoc;

$doc = new htmldoc();

// load from a URL this time
if ($doc->open($url) {

	// extract text
	$text = $doc->find('.article__body')->text();

	// extract attribute
	$attr = $doc->find('.article__author-image')->attr('src');

	// extract HTML
	$html = $doc->find('.article__body')->html();
}

```

## Installation

The easiest way to get up and running is to use composer:

```
$ composer install hexydec/htmldoc
```

HTMLdoc requires [\hexydec\token\tokenise](https://github.com/hexydec/tokenise) to run, which you can install manually if not using composer. Optionally you can also install [CSSdoc](https://github.com/hexydec/cssdoc) and [JSlite](https://github.com/hexydec/jslite) to perform inline CSS and Javascript minification respectively.

All these dependencies will be installed through composer.

## Test Suite

You can run the test suite like this:

### Linux
```
$ vendor/bin/phpunit
```
### Windows
```
> vendor\bin\phpunit
```

## Documentation

- [How it works](docs/how-it-works.md)
- [How to use and examples](docs/how-to-use.md)
- [API Reference](docs/api/readme.md)
- [Mitigating Side Effects of Minification](docs/mitigating-side-effects.md)
- [About Document Recycling](docs/recycling.md)
- [Object Performance](docs/performance.md)

## Support

HTMLdoc supports PHP version 7.4+.

## Contributing

If you find an issue with HTMLdoc, please create an issue in the tracker.

If you wish to fix an issue yourself, please fork the code, fix the issue, then create a pull request, and I will evaluate your submission.

## Licence

The MIT License (MIT). Please see [License File](LICENCE) for more information.
