# find()

Find elements within the document using a CSS selector.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html, $charset)) {
	$found = $doc->find($selector);
}
```

## Arguments

### `$selector`

A CSS selector defining the nodes to find within the document. The following selectors can be used:

| Selector				| Example			|
|-----------------------|-------------------|
| Any element			| `*`				|
| Tag					| `div`				|
| ID					| `#foo`			|
| Class					| `.foo`			|
| Attribute				| `[href]`			|
| Attribute equals		| `[href=/foo/bar/]`|
| Attribute begins with	| `[href^=/foo]`	|
| Attribute contains	| `[href*=foo]`		|
| Attribute ends with	| `[href$=bar/]`	|
| First Child			| `:first-child`	|
| Last Child			| `:last-child`		|
| Not selector			| `not(selector)`	|
| Child selector		| `>`				|

## Example

Selectors can be put together in combinations, and multiple selectors can be used:

```php
$found = $doc->find('div.foo');
$found = $doc->find('a.foo[href^=/foo]');
$found = $doc->find('div.foo[data-attr*=foo]:first-child');
$found = $doc->find('table.list th');
$found = $doc->find('ul.list > li');
$found = $doc->find('form a.button, form label.button');
$found = $doc->find('script:not([src])'); // not() can only negate single level selectors
```
## Returns

An HTMLdoc object containing the matched nodes, or an empty HTMLdoc collection if no matches were found.
