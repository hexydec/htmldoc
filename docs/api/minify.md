# minify()

Minifies the HTML document with the inputted or default options.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$doc->minify($options);
}
```

## Arguments

### `$options`

An optional array contains a list of configuration parameters to configure the minifier output, the options are as follows and are recursively merged with the default config:

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Type</th>
			<th>Options</th>
			<th>Description</th>
			<th>Default</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><code>lowercase</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Lowercase tag and attribute names</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>whitespace</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Strip whitespace from text nodes (Preserves whitespace between inline items defined in <code>htmldoc::$config['elements']['inline']</code>)</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td rowspan="3"><code>comments</code></td>
			<td rowspan="3">Array</td>
			<td colspan="2">Options for removing comments</td>
			<td><code>Array()</code></td>
		</tr>
		<tr>
			<td><code>comments</code></td>
			<td>Whether to remove comments</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>ie</code></td>
			<td>Whether to preserve Internet Explorer specific comments</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td rowspan="5"><code>urls</code></td>
			<td rowspan="5">Array</td>
			<td colspan="2">Minify internal URL's</td>
			<td><code>Array()</code></td>
		</tr>
		<tr>
			<td><code>scheme</code></td>
			<td>Remove the scheme from URLs that have the same scheme as the current document</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>host</code></td>
			<td>Remove the host for own domain</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>relative</code></td>
			<td>Process absolute URLs to make them relative to the current document</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>parent</code></td>
			<td>Process relative URLs to use parent folders (<code>../</code>) where it is shorter</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td rowspan="9"><code>attributes</code></td>
			<td rowspan="9">Array</td>
			<td colspan="2">Minify attributes</td>
			<td><code>Array()</code></td>
		</tr>
			<tr>
				<td><code>trim</code></td>
				<td>Trim whitespace from around attribute values</td>
				<td><code>true</code></td>
			</tr>
		<tr>
			<td><code>default</code></td>
			<td>Remove default attributes as defined in <code>htmldoc::$config['attributes']['default']</code></td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>empty</code></td>
			<td>Remove attributes with empty values, the attributes processed are defined in <code>htmldoc::$config['attributes']['empty']</code></td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>option</code></td>
			<td>Remove the <code>value</code> attribute from <code>option</code> tags where the text node has the same value</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>style</code></td>
			<td>Remove whitespace and last semi-colon from the <code>style</code> attribute</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>class</code></td>
			<td>Remove unnecessary whitespace from class attributes</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>sort</code></td>
			<td>Sort attributes for better gzip compression</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>boolean</code></td>
			<td>Minify boolean attributes to render only the attribute name and not the value. Boolean attributes are defined in <code>htmldoc::$config['attributes']['boolean']</code></td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>singleton</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Removes spaces and slash in singleton attributes, e.g. <code>&lt;br /&gt;</code> becomes <code>&lt;br&gt;</code></td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>quotes</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Removes quotes from attribute values where possible</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>close</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Removes closing tags for elements defined in <code>htmldoc::$config['elements']['closeoptional']</code> where possible</td>
			<td><code>true</code></td>
		</tr>
		<tr>
			<td><code>email</code></td>
			<td>Boolean</td>
			<td></td>
			<td>Sets the minification presets to email safe options</td>
			<td><code>false</code></td>
		</tr>
	</tbody>
</table>

## Returns

`void`
