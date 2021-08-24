# children()

Refine the current document by its child tags.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html, $charset)) {
	$children = $doc->children();
}
```

## Example

```php
$found = $doc->find('div.foo');
$found = $doc->find('a.foo[href^=/foo]');
$found = $doc->find('div.foo[data-attr*=foo]:first-child');
$found = $doc->find('table.list th');
$found = $doc->find('ul.list > li');
$found = $doc->find('form a.button, form label.button');
```
## Returns

An HTMLdoc object containing the child nodes, or an empty HTMLdoc collection if no children were found.
