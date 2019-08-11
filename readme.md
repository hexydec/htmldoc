# HTMLDoc: PHP HTML Document Parser and Minifier

A tokeniser based HTML and CSS document parser and minifier, written in PHP.

![Licence](https://img.shields.io/badge/Licence-MIT-lightgrey.svg)
![Project Status](https://img.shields.io/badge/Project%20Status-Beta-yellow.svg)

**This project is currently beta code, it is recommended to test your integration thoroughly before deploy this code into production**

## Description

An HTML and CSS parser, primarily designed for minifying HTML documents, although the plan is to also allow the document structure to be queried so attribute values and text node values can be extracted.

Both parsers are designed around a tokeniser to make the document processing more reliable and (hopefully) faster than regex based minifiers, which are a bit blunt and can be problematic if they match patterns in the wrong places.

Also because documents are read into a structured format, performing operations on specific parts of it is much easier and more reliable, and will in the future enable documents to be queried and data extracted.

## Usage

To minify an HTML document:

```php
use hexydec\html\htmldoc;

$doc = new htmldoc();

// load from a variable
$doc->load($html);

// load from a URL
$doc->open($url);

// minify the document
$doc->minify();

// compile back to HTML
echo $doc->save();
```

## Documentation

- [How it works](how-it-works.md)
- [How to use and examples](how-to-use.md)
- [API Reference](api/readme.md)
- [About Document Recycling](recycling.md)
- [Object Performance](performance.md)

## Licence

The MIT License (MIT). Please see [License File](LICENCE) for more information.
