# load()

Loads the inputted HTML as a document.

```php
$doc = new \hexydec\html\htmldoc();
$doc->load($html, $charset = null);
```

## Arguments

| Parameter	| Type		| Description 									|
|-----------|-----------|-----------------------------------------------|
| `$html`	| String	| The HTML to be parsed into the object			|
| `$charset`| String	| The charset of the document, or `null` to auto-detect |

## Auto-detecting the Charset

If `$charset` is set to null, the program will attempt to auto-detect the charset by looking for:

`<meta http-equiv="Content-Type" content="text/html; charset=xxx" />`

Where the charset will be extracted from, otherwise the charset will be detected using `mb_detect_encoding`.

## Returns

A boolean indicating whether the HTML was parsed successfully.
