# html()

Compile the document into an HTML string and return as a string.

```php
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	$doc->html($options);
}
```

## Arguments

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
			<td rowspan="6"><code>$options</code></td>
			<td rowspan="6">Array</td>
			<td colspan="2">An array of output options, the input is merged with `htmldoc::$config['output']`. <em>Note that for most scenarios, specifying this argument is not required</em></td>
			<td><code>[]</code></td>
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
		<tr>
			<td><code>xml</code></td>
			<td>A boolean specifying whether to render XML compliant output. Setting this to <code>true</code> automatically sets <code>quotestyle = &quot;double&quot;</code>, <code>singletonclose = &quot;/&gt;&quot;</code>, and <code>closetag = true</code></td>
			<td><code>false</code></td>
		</tr>
		<tr>
			<td><code>elements</code></td>
			<td>An array specifying output options for specific tags</td>
			<td><code>[<br>
				&nbsp; 'svg' => [<br>
				&nbsp; &nbsp; 'xml' => true,<br>
				&nbsp; &nbsp; 'quotestyle' => 'double',<br>
				&nbsp; &nbsp; 'singletonclose' => '/>',<br>
				&nbsp; &nbsp; 'closetags' => true<br>
				&nbsp; ]</code></td>
		</tr>
	</tbody>
</table>

## Returns

Returns the rendered HTML as a string.
