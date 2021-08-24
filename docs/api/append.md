# append()

Append HTML or another document to each top level node in the current document.

## Arguments

### `$html`

A string of HTML, or an `htmldoc` object.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$nodes = $doc->find('body');
	$nodes->append('<footer class="footer">This is a footer</footer>');
}
```

## Returns

The current htmldoc object with the nodes appended.
