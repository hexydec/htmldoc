# wrap()

Wraps each tag in the collection in a copy of the first tag in the specified HTML. Like jQuery's `wrap()`, where the wrapper contains other tags, each tag is placed inside the wrapper's innermost first tag.

## Arguments

### `$html`

A string of HTML, or an htmldoc object, containing the wrapper. Only the first tag is used, any text or other tags around it are ignored.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load('<p>Hello</p><p>World</p>')) {
	$doc->find('p')->wrap('<div class="wrapper"></div>');
	echo $doc->html(); // <div class="wrapper"><p>Hello</p></div><div class="wrapper"><p>World</p></div>
}
```

## Returns

The current htmldoc object. The tags in the collection are moved into the wrapper rather than copied, so they can still be edited afterwards.
