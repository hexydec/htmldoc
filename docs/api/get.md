# get()

Retrieves the `tag` object at the specified index, or all children of type `tag`.

## Arguments

### `$index`

The index of the child tag to retrieve.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load('<div data-text="Hello World">Hi</div><a href="https://github.com/hexydec">My Github</a>')) {
	$all = $doc->get();
	$first = $doc->get(0);
	$last = $doc->get(1);
	$first = $doc->get(-1);
}
```

## Returns

A tag object if index is specified, or an array of tag objects, or null if the specified index doesn't exist or the object is empty
