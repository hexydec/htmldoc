# HTMLdoc: About Recycling Documents

HTMLdoc has been designed to represent the input document as closely as possible, but if you recycle a document, you will notice differences between the input and output. This section is here to explain what those differences will be.

```php
// recycle the document
$doc = new \hexydec\html\htmldoc();
if ($doc->load($html)) {
	echo $doc->save();
}
```

## HTML Tag Names

Whilst tag names are parsed with the inputted case, the case of the matched closing tag may be different, and this difference will not be reflected in the output:

```html
<!-- input -->
<DIV><div>
<Div></div>
<div></DIV>
<DiV></DIV>

<!-- output -->
<DIV><DIV>
<Div></Div>
<div></div>
<DiV></DiV>
```

## HTML Attributes

The whitespace and delimiting of attributes is not recorded when HTML documents or snippets are parsed into the HTMLdoc object, therefor if you recycle a document, the resulting HTML will have its attribute structure normalised:

```html

<!-- input with whitespace and delimiting all over the shop -->
<a
	href="https://github.com/hexydec"
	class = 'test'
	title=test
	>
		Test
</a>

<!-- Resulting output even with no document modifications -->
<a href="https://github.com/hexydec" class="test" title="test">
		Test
</a>
```

The above assumes the default output setting of `'quotestyle' => 'double'`. Note that whitespace within Textnodes will be preserved.

## HTML Encoding

HTML Attribute values and Textnodes are stored internally with any HTML encoding removed. On output the contents of the attribute or text node is then encoded, this will result in any special characters that were not encoded correctly on input being encoded on output.

Any HTML entities (Except `<`, `>`, `=`, `'`, `"`) that were encoded on input, will now no longer be encoded and use the native character (Note that for HTML attributes, only `&`, `<` and the quote character used to contain the attribute will be encoded).

*This is valid for documents that are outputted with the default UTF-8 encoding. For other encodings, especially single-byte encodings, characters that do not have a representation in the output encoding will be represented by its HTML entity.*
