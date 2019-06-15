<?php
namespace hexydec\html;

require(__DIR__.'/tokens/ast.php');
require(__DIR__.'/tokens/doctype.php');
require(__DIR__.'/tokens/tag.php');
require(__DIR__.'/tokens/text.php');
require(__DIR__.'/tokens/comment.php');
require(__DIR__.'/tokens/cdata.php');

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
				'b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var'
			),
			'singleton' => Array(
				'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
			),
			'unnestable' => Array(
				'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
			),
			'preserve' => Array('script', 'style', 'textarea', 'pre', 'code'), // which elements not to strip whitespace from
			'booleanattributes' => Array(
				'allowfullscreen',
				'allowpaymentrequest',
				'async',
				'autofocus',
				'autoplay',
				'checked',
				'contenteditable',
				'controls',
				'default',
				'defer',
				'disabled',
				'formnovalidate',
				'hidden',
				'indeterminate',
				'ismap',
				'itemscope',
				'loop',
				'multiple',
				'muted',
				'nomodule',
				'novalidate',
				'open',
				'readonly',
				'required',
				'reversed',
				'scoped',
				'selected',
				'typemustmatch'
			)
		),
		'minify' => Array(
			'cssmin' => '\\hexydec\\minify\\cssmin::minify', // minify CSS
			'jsmin' => false, // minify javascript
			'lowercase' => true, // lowercase tag and attribute names
			'whitespace' => true, // strip whitespace from text nodes
			'comments' => Array( // remove comments
				'ie' => true
			),
			'urls' => Array( // update internal URL's to be shorter
				'attributes' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
				'absolute' => true, // process absolute URLs to make them relative to the current document
				'scheme' => true // remove the scheme from URLs that have the same scheme as the current document
			),
			'attributes' => Array( // remove values from boolean attributes
				'option' => true, // remove value attribute from option where the text node has the same value
				'type' => true, // remove the type attribute from script and style tags
				'method' => true, // remove method from form tags where it is set to GET
				'style' => true, // minify the style tag
				'removequotes' => true, // remove quotes from attributes where possible
				'sort' => true, // sort attributes for better gzip
				'boolean' => true // minify boolean attributes
			),
			'singleton' => true, // minify singleton element by removing slash
			'quotes' => true, // minify attribute quotes
		),
		'output' => Array(
			'charset' => 'utf-8',
			'quotestyle' => 'double', // double, single, minimal
			'singletonclose' => ' />',

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
		$ast = new ast($this->config);
		if (($this->document = $ast->load($html))) {
			// print_r($this->document);
			// exit();
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
		foreach ($this->document AS $item) {
			$item->minify($config);
		}
	}

	public function save(string $file = null) {
		$html = $this->compile($this->document);
		if (!$file) {
			return $html;
		} elseif (file_put_contents($file, $html) === false) {
			trigger_error('File could not be written', E_USER_WARNING);
		} else {
			return true;
		}
		return false;
	}

	protected function compile(Array $ast) {
		$output = $this->config['output'];
		$singleton = $this->config['elements']['singleton'];
		$html = '';
		foreach ($ast AS $item) {
			$html .= $item->compile();
		}
		return $html;
	}
}
