# HTMLdoc: How to Use

HTMLdoc has been designed to be as simple to use as possible, but with enough configuration options to control the output more closely.

## Configuring HTMLdoc

The default configuration has been setup to give the best options for most needs. If you do need to change the configuration, the configuration options can be passed to the object upon creation:

```php
$config = [
	'elements' => [
		'pre' => [
			'span' // treat spans as pre formattted
		]
	]
];
$doc = new \hexydec\html\htmldoc($config);
```

To see all of the configuration options, see the [API documentation for the `__construct()` method](api/construct.md).

## Loading HTML

HTML can be loaded in two ways, either from a string, or from a stream:

### From a String

```php
$html = '<div>Hello World</div>'; // can be a snippet
$charset = mb_internal_encoding(); // UTF-8?

$doc = new \hexydec\html\htmldoc();
if ($doc->load($html, $charset)) {
	// do something
}
```

### From a Stream

```php
$url = 'https://github.com/hexydec'; // of course you want to parse this page
$context = stream_context_create([
	'http' => [
		'user_agent' => 'My HTML Bot 1.0 (Mozilla Compatible)',
		'timeout' => 10
	]
]);

$doc = new \hexydec\html\htmldoc();
if ($doc->open($url, $context, $error)) {
	// do something
} else {
	trigger_error('Could not parse HTML: '.$error, E_USER_WARNING);
}
```

For more information, see the API documentation for the [`load()` method](api/load.md) and the [`open()` method](api/open.md).

## Finding Elements and Extracting Information

You can use standard CSS selectors to query an HTMLdoc object after you have loaded some HTML:

```php
$url = 'https://github.com/hexydec';

$doc = new \hexydec\html\htmldoc();
if ($doc->open($url, $context, $error)) {

	// make a new HTMLdoc containg just this node
	$name = $doc->find(".vcard-fullname");

	// echo my name
	echo $name->text();

	// extract the HTML
	echo $name->html();

	// get the value of an attribute
	echo $name->attr("itemprop"); // = "name"
} else {
	trigger_error('Could not parse HTML: '.$error, E_USER_WARNING);
}
```

For more information, [see the API documentation](api/readme.md).

## Minifying Documents

When minifying documents, HTMLdoc updates the internal representation of the document and some of the output settings. When the document is saved, the generated code will then be smaller.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$doc->minify(); // just run the minify method
	echo $doc->save();
}
```

The `minify()` method can also accept an array of minification options to change what is minified and what is not, this can be useful for example for minification of HTML for emails.

To see all the available options [API documentation for the `minify()` method](api/minify.md).

## Outputting Documents

HTML can be rendered in the following ways from your HTMLdoc object:

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {

	// output as a string
	echo $doc->html();

	// output as a string with charset conversion
	echo $doc->save(null, 'iso-8859-1');

	// save to a file, optionally convert the charset
	$file = __DIR__.'/output/file.html';
	if ($doc->save($file, 'iso-8859-1')) {
		// do something when it is saved
	}

}
```
For a full description of the methods above and to see all the available options [see the API documentation](api/readme.md).
