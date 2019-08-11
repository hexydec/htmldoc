# save()

Compile the document into an HTML string and save to the specified location, or return as a string.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$doc->save($file, $options);
}
```

## Arguments

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
			<td colspan="2">An array of output options, the input is merged with `htmldoc::$config['output']`. <em>Note that for most scenarios, specifying this argument is not required</em></td>
			<td><code>Array()</code</td>
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
			<td>A string defining how singleton tags will be closed. if <code>false</code> the renderer will follow the value defined in <code>tag::$singleton</code>, which is set by the parser. Note that using the <code>minify()</code> method using the option <code>'singleton' => true</code> will change the default setting to <code>&gt;</code></td>
			<td><code>false</code></td>
		</tr>
		<tr>
			<td><code>closetags</code></td>
			<td>A boolean specifying whether to force elements to render a closing tag. If <code>false</code>, the renderer will follow the value defined in <code>tag::$close</code> (Which will be set according to whether the tag had no closing tag when the document was parsed, or may be set to false if the document has been minified with <code>minify()</code>)</td>
			<td><code>false</code></td>
		</tr>
	</tbody>
</table>

## Returns

Returns the HTML document as a string if `$file` is null, or `true` if the file was successfully saved to the specified file. On error the method will return `false`.
