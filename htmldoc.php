<?php
namespace hexydec\html;

require(__DIR__.'/tokens/collection.php');
require(__DIR__.'/tokens/doctype.php');
require(__DIR__.'/tokens/tag.php');
require(__DIR__.'/tokens/text.php');
require(__DIR__.'/tokens/comment.php');
require(__DIR__.'/tokens/cdata.php');
require(__DIR__.'/tokens/style.php');
require(__DIR__.'/tokens/script.php');

class htmldoc {

	protected $config = Array(
		'tokens' => Array(
			'doctype' => '<!DOCTYPE',
			'comment' => '<!--[\d\D]*?-->',
			'cdata' => '<!\[CDATA\[[\d\D]*?\]\]>',
			'tagopenstart' => '<[^ >\/]++',
			'tagselfclose' => '\/>',
			'tagopenend' => '>',
			'tagclose' => '<\/[^ >]++>',
			'textnode' => '(?<=>)[^<]++(?=<)',
			'attributevalue' => '=\s*+["\']?[^"\']*+["\']?',
			'attribute' => '[^<>"=\s]++',
			'whitespace' => '\s++'
		),
		'elements' => Array(
			'inline' => Array(
				'b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span'
			),
			'singleton' => Array(
				'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
			),
			'unnestable' => Array(
				'head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
			),
			'pre' => Array('textarea', 'pre', 'code'), // which elements not to strip whitespace from
			'custom' => Array('script', 'style'), // which elements have their own plugins
		),
		'attributes' => Array(
			'boolean' => Array(
				'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch'
			),
			'default' => Array( // default attributes that can be removed
				'style' => Array(
					'type' => 'text/css'
				),
				'script' => Array(
					'type' => 'text/javascript',
					'language' => true
				),
				'form' => Array(
					'method' => 'get'
				),
				'input' => Array(
					'type' => 'text'
				)
			),
			'empty' => Array('id', 'class', 'style', 'title'), // attributes to remove if empty
			'urls' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
		),
		'minify' => Array(
			'css' => 'hexydec\\minify\\cssmin::minify', // minify CSS
			'js' => false, // minify javascript
			'lowercase' => true, // lowercase tag and attribute names
			'whitespace' => true, // strip whitespace from text nodes
			'comments' => Array( // remove comments
				'ie' => true
			),
			'urls' => Array( // update internal URL's to be shorter
				'absolute' => true, // process absolute URLs to make them relative to the current document
				'host' => true, // remove the host for own domain
				'scheme' => true // remove the scheme from URLs that have the same scheme as the current document
			),
			'attributes' => Array( // remove values from boolean attributes
				'default' => true,
				'empty' => true, // remove these attributes if empty
				'option' => true, // remove value attribute from option where the text node has the same value
				'style' => true, // minify the style tag
				'class' => true, // sort classes
				'sort' => true, // sort attributes for better gzip
				'boolean' => true, // minify boolean attributes
			),
			'singleton' => true, // minify singleton element by removing slash
			'quotes' => true, // minify attribute quotes
		),
		'output' => Array(
			'charset' => 'utf-8',
			'quotestyle' => 'double', // double, single, minimal
			'singletonclose' => ' />'
		)
	);
	protected $document = false;
	protected $attributes = Array();

	public function __construct(Array $config = Array()) {
		$this->config = array_replace_recursive($this->config, $config);
	}

	public function open(String $url) {
		if (($html = file_get_contents($url)) !== false) {
			return $this->load($html);
		}
		return false;
	}

	public function load(String $html) {
		// $this->attributes = Array();
		$this->document = new collection($this->config);
		if ($this->document->load($html)) {
			return true;
		}
		return false;
	}

	public function minify(Array $config = Array()) {

		// merge config
		$config = array_replace_recursive($this->config['minify'], $config);

		// set minify output parameters
		if ($config['singleton']) {
			$this->config['output']['singletonclose'] = '>';
		}
		if ($config['quotes']) {
			$this->config['output']['quotestyle'] = 'minimal';
		}

		// sort attributes
		// if ($config['attributes']['sort']) {
		// 	arsort($this->attributes, SORT_NUMERIC);
		// 	$config['attributes']['sort'] = \array_keys($this->attributes);
		// }
		$this->document->minify($config, $this->document);
	}

	public function save(string $file = null) {
		$html = $this->document->compile($this->config['output']);
		if (!$file) {
			return $html;
		} elseif (file_put_contents($file, $html) === false) {
			trigger_error('File could not be written', E_USER_WARNING);
		} else {
			return true;
		}
		return false;
	}
}
