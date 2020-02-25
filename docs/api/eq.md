# eq()

Builds a new HTMLdoc collection containing only the node at the index requested.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html, $charset)) {
	$found = $doc->find($selector)->eq($index);
}
```

## Arguments

### `$index`

An integer indicating the zero based index of the element to return. A minus value will return that many items from the end of the collection.

## Returns

An HTMLdoc collection containing the element at the index requested, or an empty HTMLdoc collection if the index is out of range.
