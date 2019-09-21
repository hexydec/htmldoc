# HTMLDoc: PHP HTML Document Parser and Minifier

A tokeniser based HTML and CSS document parser and minifier, written in PHP.

![Licence](https://img.shields.io/badge/Licence-MIT-lightgrey.svg)
![Project Status](https://img.shields.io/badge/Project%20Status-Beta-yellow.svg)
[![Build Status](https://api.travis-ci.org/hexydec/htmldoc.svg?branch=master)](https://travis-ci.org/hexydec/htmldoc)

**This project is currently beta code, it is recommended to test your integration thoroughly before deploy this code into production**

## Description

An HTML and CSS parser, primarily designed for minifying HTML documents, it also enables the document structure to be queried allowing attribute values and text node values can be extracted.

Both parsers are designed around a tokeniser to make the document processing more reliable and (hopefully) faster than regex based minifiers, which are a bit blunt and can be problematic if they match patterns in the wrong places.

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

## Documentation

- [How it works](docs/how-it-works.md)
- [How to use and examples](docs/how-to-use.md)
- [API Reference](docs/api/readme.md)
- [Mitigating Side Effects of Minification](docs/mitigating-side-effects.md)
- [About Document Recycling](docs/recycling.md)
- [Object Performance](docs/performance.md)

## Contributing

If you find an issue with HTMLDoc, please create an issue in the tracker.

If you wish to fix an issues yourself, please fork the code, fix the issue, then create a pull request, and I will evaluate your submission.

## Licence

The MIT License (MIT). Please see [License File](LICENCE) for more information.
