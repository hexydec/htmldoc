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
| `inline`		| HTML elements that are considered inline			| `['b', 'u', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span']` |
| `singleton`	| HTML elements that are singletons					| `['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr', 'animate', 'animateMotion', 'animateTransform', 'circle', 'ellipse', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence', 'hatchpath', 'image', 'line', 'mpath', 'path', 'polygon', 'polyline', 'rect', 'set', 'stop', 'use', 'view']` |
| `closeoptional`	| HTML elements that don't have to be closed	| `['head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup']` |

#### `attributes`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `boolean`		| HTML attributes that are boolean values			| `['allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch']` |
| `default`		| Default attributes that can be removed			| <code>[<br>&nbsp; &nbsp; 'style' => ['type' => 'text/css'],<br>&nbsp; &nbsp; 'script' => ['type' => 'text/javascript', 'language' => true],<br>&nbsp; &nbsp; 'form' => ['method' => 'get'],<br>&nbsp; &nbsp; 'input' => ['type' => 'text']<br>]</code> |
| `empty`		| Attributes to remove if empty						| `['id', 'class', 'style', 'title']` |
| `urls`		| Attributes that contain urls						| `['href', 'src', 'action', 'poster']` |
| `urlskip`		| Skips compressing URLs if tag => attribute => value matches | <code>[<br>&nbsp; &nbsp; 'link' => [<br>&nbsp; &nbsp; &nbsp; &nbsp; 'rel' => ['stylesheet', 'icon', 'shortcut icon', 'apple-touch-icon-precomposed', 'apple-touch-icon', 'preload', 'prefetch', 'author', 'help']<br>&nbsp; &nbsp; ]<br>]</code> |

#### `custom`

This option enables you to specify custom handlers for specific tags. To use a custom handler, create a new index inside `custom` with a key of the custom tag name. The value should be an array specifying any configuration options.

The only required key is `class` which should specify a fully qualified class name to handle the tag (The class must implement \hexydec\html\token).

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
