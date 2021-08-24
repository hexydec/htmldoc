# remove()

Removes all top level nodes, or if $selector is specified, the nodes matched by the selector.

## Arguments

### `$selector`

A CSS selector to refine the nodes to delete or null to delete top level nodes.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$doc->remove('div.test');
}
```

## Returns

The current htmldoc object with the requested nodes deleted.
