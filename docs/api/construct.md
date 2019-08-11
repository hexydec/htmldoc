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
| `inline`		| HTML elements that are considered inline			| `Array('b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span')` |
| `singleton`	| HTML elements that are singletons					| `Array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr')` |
| `closeoptional`	| HTML elements that don't have to be closed		| `Array('head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup')` |
| `pre`			| HTML elements that contain pre-formatted content	| `Array('textarea', 'pre', 'code')` |
| `plugins`		| HTML elements that have a custom handler class	| `Array('script', 'style')` |

#### `attributes`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `boolean`		| HTML attributes that are boolean values			| `Array('allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch')` |
| `default`		| Default attributes that can be removed			| <code>Array(<br>&nbsp; &nbsp; 'style' => Array('type' => 'text/css'),<br>&nbsp; &nbsp; 'script' => Array('type' => 'text/javascript', 'language' => true),<br>&nbsp; &nbsp; 'form' => Array('method' => 'get'),<br>&nbsp; &nbsp; 'input' => Array('type' => 'text')<br>)</code> |
| `empty`		| Attributes to remove if empty						| `Array('id', 'class', 'style', 'title')` |
| `urls`		| Attributes that contain urls						| `Array('href', 'src', 'action', 'poster')` |

## Returns

A new HTMLDoc object.
