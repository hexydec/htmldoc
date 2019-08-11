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

## Returns

A boolean indicating whether the HTML was parsed successfully.
