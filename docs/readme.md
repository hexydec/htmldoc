# HTMLDoc API

This document describes how to configure and use the HTMLDoc object.

## Methods

### \__construct()

Called when a new htmldoc object is created.

```php
$doc = new \hexydec\html\htmldoc($config);
```

#### $config

The options set into the object are setup for general use, but can be configured with the following options:

##### `elements`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `inline`		| HTML elements that are considered inline			| `Array('b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span')` |
| `singleton`	| HTML elements that are singletons					| `Array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr')` |
| `closeoptional`	| HTML elements that don't have to be closed		| `Array('head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup')` |
| `pre`			| HTML elements that contain pre-formatted content	| `Array('textarea', 'pre', 'code')` |
| `plugins`		| HTML elements that have a custom handler class	| `Array('script', 'style')` |

##### `attributes`

| option		| Description										| Defaults						|
|---------------|---------------------------------------------------|-------------------------------|
| `boolean`		| HTML attributes that are boolean values			| `Array('allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch')` |
| `default`		| Default attributes that can be removed			| <code>Array(<br>&nbsp; &nbsp; 'style' => Array('type' => 'text/css'),<br>&nbsp; &nbsp; 'script' => Array('type' => 'text/javascript', 'language' => true),<br>&nbsp; &nbsp; 'form' => Array('method' => 'get'),<br>&nbsp; &nbsp; 'input' => Array('type' => 'text')<br>)</code> |
| `empty`		| Attributes to remove if empty						| `Array('id', 'class', 'style', 'title')` |
| `urls`		| Attributes that contain urls						| `Array('href', 'src', 'action', 'poster')` |

### open()

Open an HTML file from a URL.

*Note the charset of the document is determined by the `charset` directive of the `Content-Type` header. If the header is not present, the charset will be detected using the method described in the `load()` method.*

```php
$doc = new \hexydec/html\htmldoc();
$doc->open($url, $context = null, &$error = null);
```

| Parameter	| Type		| Description 									|
|-----------|-----------|-----------------------------------------------|
| `$url`	| String 	| The URL of the HTML document to be opened		|
| `$context`| Resource 	| A stream context resource created with stream_context_create()	|
| `$error`	| String	| A reference to a description of any error that is generated.	|

### load()

Loads the inputted HTML as a document.

```php
$doc = new \hexydec/html\htmldoc();
$doc->load($html, $charset = null);
```

| Parameter	| Type		| Description 									|
|-----------|-----------|-----------------------------------------------|
| `$html`	| String	| The HTML to be parsed into the object			|
| `$charset`| String	| The charset of the document, or `null` to auto-detect |

### find()

Find elements within the document using a CSS selector.

```php
$doc = new \hexydec/html\htmldoc();
if ($doc->load($html, $charset)) {
	$found = $doc->find($selector);
}
```
#### $selector

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
| Child selector		| `>`				|

Selectors can be put together in combinations, and multiple selectors can be used:

```php
$found = $doc->find('div.foo');
$found = $doc->find('a.foo[href^=/foo]');
$found = $doc->find('div.foo[data-attr*=foo]:first-child');
$found = $doc->find('table.list th');
$found = $doc->find('ul.list > li');
$found = $doc->find('form a.button, form label.button');
```
#### Returns

An HTMKDoc object containing the matched nodes.

### eq()

Builds a new HTMLDoc collection containing only the node at the index requested.

```php
$doc = new \hexydec/html\htmldoc();
if ($doc->load($html, $charset)) {
	$found = $doc->find($selector)->eq($index);
}
```

#### $index

An integer indicating the zero based index of the element to return. A minus value will return that many items from the end of the collection.

#### Returns

An HTMLDoc collection containing the element at the index requested, or an empty HTMLDoc collection if the index is out of range.

### first()

Returns a new HTMLDoc collection containing the first element in the collection.

### last()

Returns a new HTMLDoc collection containing the last element in the collection.

### get()

Extracts an array of tag objects from an HTMLDoc collection.

### minify()

Minifies the HTML document with the inputted or default options.

```php
$doc = new \hexydec/html\htmldoc();
$doc->load($html);
$doc->minify($options);
```

The optional `$options` array contains a list of configuration parameters to configure the minifier output, the options are as follows and are recursively merged with the default config:

<table>
	<thead>
		<th>Parameter</th>
		<th>Type</th>
		<th>Options</th>
		<th>Description</th>
		<th>Default</th>
	</thead>
	<tbody>
		<tr>
			<td><code>lowercase</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Lowercase tag and attribute names</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>whitespace</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Strip whitespace from text nodes (Preserves whitespace between inline items defined in <code>htmldoc::$config['elements']['inline']</code>)</td>
			<td>true</td>
		</tr>
		<tr>
			<td rowspan="2"><code>comments</code></td>
			<td rowspan="2">Array</td>
			<td colspan="2">Remove comments, set to false to preserve comments</td>
			<td><code>Array()</code></td>
		</tr>
		<tr>
			<td><code>ie</code></td>
			<td>Whether to preserve Internet Explorer specific comments</td>
			<td>true</td>
		</tr>
		<tr>
			<td rowspan="4"><code>urls</code></td>
			<td rowspan="4">Array</td>
			<td colspan="2">Minify internal URL's</td>
			<td><code>Array()</code></td>
		</tr>
		<tr>
			<td><code>absolute</code></td>
			<td>Process absolute URLs to make them relative to the current document</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>host</code></td>
			<td>Remove the host for own domain</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>scheme</code></td>
			<td>Remove the scheme from URLs that have the same scheme as the current document</td>
			<td>true</td>
		</tr>
		<tr>
			<td rowspan="8"><code>attributes</code></td>
			<td rowspan="8">Array</td>
			<td colspan="2">Minify attributes</td>
			<td><code>Array()</code></td>
		</tr>
		<tr>
			<td><code>default</code></td>
			<td>Remove default attributes as defined in <code>htmldoc::$config['attributes']['default']</code></td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>empty</code></td>
			<td>Remove attributes with empty values, the attributes processed are defined in <code>htmldoc::$config['attributes']['empty']</code></td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>option</code></td>
			<td>Remove the <code>value</code> attribute from <code>option</code> tags where the text node has the same value</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>style</code></td>
			<td>Remove whitespace and last semi-colon from the <code>style</code> attribute</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>class</code></td>
			<td>Sort class names</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>sort</code></td>
			<td>Sort attributes</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>boolean</code></td>
			<td>Minify boolean attributes to render only the attribute name and not the value. Boolean attributes are defined in <code>htmldoc::$config['attributes']['boolean']</code></td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>singleton</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Removes spaces and slash in singleton attributes, e.g. <code>&lt;br /&gt;</code> becomes <code>&lt;br&gt;</code></td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>quotes</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Removes quotes from attribute values where possible</td>
			<td>true</td>
		</tr>
		<tr>
			<td><code>close</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Removes closing tags for elements defined in `htmldoc::$config['elements']['closeoptional']` where possible</td>
			<td>true</td>
		</tr>
	</tbody>
</table>

### save()

Compile the document into an HTML string and save to the specified location, or return as a string.

```php
$doc = new \hexydec/html\htmldoc();
$doc->load($html);
$doc->save($file, $options);
```

#### Arguments

<table>
	<thead>
		<th>Parameter</th>
		<th>Type</th>
		<th>Options</th>
		<th>Description</th>
		<th>Default</th>
	</thead>
	<tbody>
		<tr>
			<td><code>$file</code></td>
			<td>String</td>
			<td></td>
			<td>The location to save the HTML, or <code>null</code> to return the HTML as a string</td>
			<td>null</td>
		</tr>
		<tr>
			<td rowspan="5"><code>$options</code></td>
			<td rowspan="5">Array</td>
			<td colspan="2">An array of output options, the input is merged with `htmldoc::$config['output']`. *Note that for most scenarios, specifying this argument is not required*</td>
			<td><code>>Array()</code</td>
		</tr>
		<tr>
			<td><code>charset</code></td>
			<td>The charset the output should be converted to. The default <code>null</code> will prevent any charset conversion.</td>
			<td><code>null</code></td>
		</tr>
		<tr>
			<td><code>quotestyle</code></td>
			<td>Defines how to quote the attributes in the output, either <code>double</code>, <code>single</code>, or <code>minimal</code>. Note that using the <code>minify()</code> method using the option <code>'quotes' => true</code> will change the default setting to <code>minimal</code></td>
			<td><code>&quot;double&quot;</code></td>
		</tr>
		<tr>
			<td><code>singletonclose</code></td>
			<td>A string defining how singleton tags will be closed. Note that using the <code>minify()</code> method using the option <code>'singleton' => true</code> will change the default setting to <code>&gt;</code></td>
			<td><code>&quot; /&gt;&quot;</code></td>
		</tr>
		<tr>
			<td><code>closetags</code></td>
			<td>A boolean specifying whether to force elements to render a closing tag. If <code>false</code>, the renderer will follow the value defined in <code>tag::$close</code> (Which will be set according to whether the tag had no closing tag when the document was parsed, or may be set to false if the document has been minified with <code>minify()</code>)</td>
			<td><code>false</code></td>
		</tr>
	</tbody>
</table>

#### Return Value

Returns the HTML document as a string if `$file` is null, or `true` if the file was successfully saved to the specified file. On error the method will return `false`.
