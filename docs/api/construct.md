# \__construct()

Called when a new htmldoc object is created.

```php
$doc = new \hexydec\html\htmldoc($config);
```
## Arguments

### $config

The options set into the object are setup for general use, but can be configured with the following options:

#### `elements`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `inline`		| HTML elements that are considered inline			| `['b', 'u', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span']` |
| `singleton`	| HTML elements that are singletons					| `['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr']` |
| `closeoptional`	| HTML elements that don't have to be closed	| `['head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup']` |
| `pre`			| HTML elements that contain pre-formatted content	| `['textarea', 'pre', 'code']` |
| `plugins`		| HTML elements that have a custom handler class	| `['script', 'style']` 		|

#### `attributes`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `boolean`		| HTML attributes that are boolean values			| `['allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch']` |
| `default`		| Default attributes that can be removed			| <code>[<br>&nbsp; &nbsp; 'style' => ['type' => 'text/css'],<br>&nbsp; &nbsp; 'script' => ['type' => 'text/javascript', 'language' => true],<br>&nbsp; &nbsp; 'form' => ['method' => 'get'],<br>&nbsp; &nbsp; 'input' => ['type' => 'text']<br>]</code> |
| `empty`		| Attributes to remove if empty						| `['id', 'class', 'style', 'title']` |
| `urls`		| Attributes that contain urls						| `['href', 'src', 'action', 'poster']` |
| `urlskip`		| Skips compressing URLs if tag => attribute => value matches | <code>[<br>&nbsp; &nbsp; 'link' => [<br>&nbsp; &nbsp; &nbsp; &nbsp; 'rel' => ['stylesheet', 'icon', 'shortcut icon', 'apple-touch-icon-precomposed', 'apple-touch-icon', 'preload', 'prefetch', 'author', 'help']<br>&nbsp; &nbsp; ]<br>]</code> |

## Returns

A new HTMLdoc object.
