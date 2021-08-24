# prepend()

Prepend HTML or another document to each top level node in the current document.

## Arguments

### `$html`

A string of HTML, or an `htmldoc` object.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$nodes = $doc->find('body');
	$nodes->prepend('<header class="header">This is a header</header>');
}
```

## Returns

The current htmldoc object with the nodes appended.
