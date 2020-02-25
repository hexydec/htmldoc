# HTMLdoc: Mitigating Side Effects of Minification

Whilst all transformations performed on the input HTML will produce valid HTML, some side effects may be present as a result of the transformed code.

## CSS Side Effects

### Absolute Values in Attribute Selectors

Selectors that reference an absolute value may no longer work after minification of an HTMLdoc document if the attribute in question is transformed into a shorter form. Consider the following HTML:

```html
<a href="https://github.com/hexydec">Go to my GitHub Page</a>
```

You may for example place an image next to all external links using the following CSS:

```css
a[href^=https://] {
	/* CSS Properties */
}
```

After minification, the HTML may be transformed to this:

```html
<a href="//github.com/hexydec">Go to my GitHub Page</a>
```

At which point the CSS will no longer work. The solution is to use something like:

```css
a[href*=//] {
	/* CSS Properties */
}
```

Depending on your specific case you may need to change any CSS that uses match values in a CSS attribute selector.

### Testing attributes that may have been removed

Depending on the attribute you are selecting, if it is empty, the HTMLdoc minification operation may remove the attribute altogether. Consider the following HTML:

```html
<input type="text" name="test" value="" />
```

You may wish to apply different styles depending on whether the `value` attribute is empty or target it by its `type` attribute:

```css
input[type=text] {
	/* CSS Properties */
}
input:not([value='']) {
	/* CSS Properties */
}
```

After minification, the HTML may be transformed to this:

```html
<input name="test">
```

At which point the CSS will no longer work. The solution is to use something like:

```css
input[type=text], input:not([type]) {
	/* CSS Properties */
}
input[value]:not([value='']) {
	/* CSS Properties */
}
```

## Javascript Side-Effects

### Reading Attributes

If your Javascript code expects an attribute to exist, you may need to update your code to take into account that the attribute might not exist at all. Consider the following HTML:

```html
<input type="text" name="test" value="" />
```

If you wanted to test whether the `value` attribute is empty:

```javascript
const obj = document.querySelector("input[type=text]");

if (obj instanceof HTMLElement) {
	const value = obj.getAttribute("value");

	if (value !== "") {
		// do something
	}
}
```

After minification, the HTML may be transformed to this:

```html
<input name="test">
```

There are two issues here, firstly is that the `obj` will be `null`, as the `type` attribute has been removed, so the first `if` will fail. Secondly because the `value` attribute also now doesn't exist, `value` will also be `null`.

The solution here is much like the CSS solution:

```javascript
const obj = document.querySelector("input:not([type]),input[type=text]");

if (obj instanceof HTMLElement) {
	const value = obj.getAttribute("value");

	if (value !== "" && value !== null) { // attribute value will be null if it doesn't exist
		// do something
	}
}
```

## Other Side-Effects

There may be other side-effects associated with the transformations performed by HTMLdoc, this page will be updated with more examples where appropriate.

If you discover a side-effect which you think needs documenting, please [create an issue in the tracker](https://github.com/hexydec/htmldoc/issues).
