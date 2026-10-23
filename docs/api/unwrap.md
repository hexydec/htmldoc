# unwrap()

Removes the parent of each tag in the collection, leaving the parent's children in its place. Like jQuery's `unwrap()`, all of the parent's children are kept, not just the selected tags.

## Arguments

### `$selector`

A CSS selector the parent must match to be removed, or null to remove any parent.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load('<div class="wrapper"><p>Hello</p></div><section><p>World</p></section>')) {
	$doc->find('p')->unwrap('.wrapper');
	echo $doc->html(); // <p>Hello</p><section><p>World</p></section>
}
```

## Returns

The current htmldoc object. The tags in the collection are moved rather than copied, so they can still be edited afterwards.
