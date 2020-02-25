# attr()

Retrieves the value of the requested attribute from the first item in an HTMLdoc collection.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	echo $doc->attr($key);
}
```

## Arguments

### `$key`

The name of the attribute to retrieve.

## Example

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load('<div data-text="Hello World">Hi</div><a href="https://github.com/hexydec">My Github</a>')) {

	echo $doc->attr('data-text'); // Hello World

	echo $doc->find('a')->attr('href'); // https://github.com/hexydec
}
```

## Returns

A string containing the attribute value, or `null` if there are no elements in the collection or the attribute requested does not exist.
