# Minify: PHP HTML and CSS Document Parser and Minifier

A tokeniser based HTML and CSS document parser and minifier, written in PHP.

![Licence](https://img.shields.io/badge/Licence-MIT-lightgrey.svg)
![Project Status](https://img.shields.io/badge/Project%20Status-Alpha-yellow.svg)

**This project is currently alpha code, it is currently not recommended to deploy this code into production**

## Description

An HTML and CSS parser, primarily designed for minifying HTML documents, although the plan is to also allow the document structure to be queried so attribute values and text node values can be extracted.

Both parsers are designed around a tokeniser to make the document processing more reliable and (hopefully) faster than regex based minifiers, which are a bit blunt and can be problematic if they match patterns in the wrong places.

Also because documents are read into a structured format, performing operations on specific parts of it is much easier and more reliable, and will in the future enable documents to be queried and data extracted.

## Usage

To minify an HTML document:

```php
use hexydec\minify\htmldoc;

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

To minify a CSS document:

```php
use hexydec\minify\cssmin;

echo cssmin::minify($css);
```
