# text()

Retrieves the combined value of all the text nodes in the collection. The formatting of the text will not be altered from how it is in the HTML, although spaces will be added between text from different elements.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	echo $doc->text();
}
```

## Returns

A string containing the combined contents of the text nodes in an HTMLdoc collection. 
