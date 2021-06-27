# open()

Open an HTML file from a URL.

*Note the charset of the document is determined by the `charset` directive of the `Content-Type` header. If the header is not present, the charset will be detected using the method described in the [`load()` method](load.md).*

```php
$doc = new \hexydec\html\htmldoc();
$doc->open($url, $context = null, &$error = null);
```

## Arguments

| Parameter	| Type		| Description 									|
|-----------|-----------|-----------------------------------------------|
| `$url`	| String 	| The URL of the HTML document to be opened		|
| `$context`| Resource 	| A stream context resource created with `stream_context_create()`	|
| `$error`	| String	| A reference to a description of any error that is generated.	|

## Returns

A string containing the HTML that was loaded, or `false` when the requested file could not be loaded.
