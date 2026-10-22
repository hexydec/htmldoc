# \__construct()

Called when a new htmldoc object is created.

```php
$doc = new \hexydec\html\htmldoc($config);
```
## Arguments

### `$config`

A optional array of configuration options that will be merged recursively with the default configuration. The available options and their defaults are:

#### `elements`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `inline`		| HTML elements that are considered inline, whitespace around them and at the edges of their content is preserved | `['a', 'abbr', 'acronym', 'b', 'bdi', 'bdo', 'big', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'font', 'i', 'ins', 'kbd', 'label', 'map', 'mark', 'noscript', 'output', 'picture', 'q', 'ruby', 's', 'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'time', 'tt', 'u', 'var', 'wbr']` |
| `inlineblock`	| HTML elements that display as inline blocks, whitespace around them is preserved but whitespace at the edges of their content is not | `['audio', 'button', 'canvas', 'embed', 'iframe', 'img', 'input', 'math', 'meter', 'object', 'progress', 'select', 'svg', 'textarea', 'video']` |
| `singleton`	| HTML elements that are singletons					| `['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']` |
| `closeoptional`	| HTML elements that don't have to be closed, keyed by tag name, with the opening tags that close them implicitly as the value | <code>[<br>&nbsp; &nbsp; 'html' => [],<br>&nbsp; &nbsp; 'head' => ['body'],<br>&nbsp; &nbsp; 'li' => ['li'],<br>&nbsp; &nbsp; 'dt' => ['dt', 'dd'],<br>&nbsp; &nbsp; ...<br>]</code> See `src/config.php` for the full list |

#### `attributes`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `boolean`		| HTML attributes that are boolean values, any value is removed when minified | `['allowfullscreen', 'async', 'autofocus', 'autoplay', 'checked', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'inert', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'playsinline', 'readonly', 'required', 'reversed', 'selected', 'typemustmatch', 'shadowrootclonable', 'shadowrootdelegatesfocus', 'shadowrootserializable']` |
| `booleanvalues`	| Boolean HTML attributes that also take keyword values, the value is only removed when empty or the same as the attribute name | `['hidden']` |
| `default`		| Default attributes that can be removed			| <code>[<br>&nbsp; &nbsp; 'style' => ['type' => 'text/css'],<br>&nbsp; &nbsp; 'script' => ['type' => 'text/javascript', 'language' => true],<br>&nbsp; &nbsp; 'form' => ['method' => 'get'],<br>&nbsp; &nbsp; 'input' => ['type' => 'text']<br>]</code> |
| `empty`		| Attributes to remove if empty						| `['id', 'class', 'style', 'action', 'dir', 'onfocus', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup', 'target']` |
| `urls`		| Attributes that contain urls						| `['href', 'src', 'action', 'poster']` |
| `urlskip`		| Skips compressing URLs if tag => attribute => value matches | <code>[<br>&nbsp; &nbsp; 'link' => [<br>&nbsp; &nbsp; &nbsp; &nbsp; 'rel' => ['stylesheet', 'icon', 'shortcut icon', 'apple-touch-icon-precomposed', 'apple-touch-icon', 'preload', 'prefetch', 'author', 'help']<br>&nbsp; &nbsp; ]<br>]</code> |

#### `custom`

This option enables you to specify custom handlers for specific tags. To use a custom handler, create a new index inside `custom` with a key of the custom tag name. The value should be an array specifying any configuration options.

The only required key is `class` which should specify a fully qualified class name to handle the tag (The class must implement \hexydec\html\token, which includes a public `?tag $parent` property).

By default there are built in handlers for `style` and `script` tags, CSS is minified using [hexydec\\css\\cssdoc](https://github.com/hexydec/cssdoc), and Javascript is minified with [hexydec\\css\\jslite](https://github.com/hexydec/jslite)  (if you install the modules using composer).

The `style` and `script` handlers have the following options:

| option		| Description																		|
|---------------|-----------------------------------------------------------------------------------|
| `class`		| The name of the handler class (Should implement \hexydec\html\token) 				|
| `cache`		| A string specifying a file pattern to cache minified code to, use %s for the generated file name	|
| `minifier`	| A callback for minifying the custom code											|

The `minifier` callback has the following pattern:

```php
function (string $code, array $minify) : ?string {
	// manipulate the code, return null if minification failed
	return $code;
}
```

The minifiers are implemented as callbacks to enable you to use your own minifiers, whist still using the built-in tag handler class.

## Returns

A new HTMLdoc object.
