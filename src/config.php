<?php
declare(strict_types = 1);
namespace hexydec\html;

class config {

	/**
	 * @var array $config Object configuration array
	 */
	protected $config = [];

	/**
	 * Constructs the object
	 *
	 * @param array $config An array of configuration parameters that is recursively merged with the default config
	 */
	public function __construct(array $config = []) {
		$minify = function (string $code, array $minify, string $tag) {
			switch ($tag) {
				case 'style':
					$obj = new \hexydec\css\cssdoc();
					break;
				case  'script':
					$obj = new \hexydec\jslite\jslite();
					break;
				default:
					return false;
			}
			if ($obj->load($code)) {
				$obj->minify($minify);
				return $obj->compile();
			}
			return false;
		};
		$default = [
			'elements' => [
				'inline' => [
					'b', 'u', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span'
				],
				'singleton' => [

					// html singletons
					'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr',

					// SVG singletons
					'animate', 'animateMotion', 'animateTransform', 'circle', 'ellipse', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence', 'hatchpath', 'image', 'line', 'mpath', 'path', 'polygon', 'polyline', 'rect', 'set', 'stop', 'use', 'view'
				],
				'closeoptional' => [
					'html', 'head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup'
				]
			],
			'attributes' => [
				'boolean' => [
					'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch'
				],
				'default' => [ // default attributes that can be removed
					'style' => [
						'type' => 'text/css',
						'media' => 'all'
					],
					'script' => [
						'type' => 'text/javascript',
						'language' => true
					],
					'form' => [
						'method' => 'get',
						'autocomplete' => 'on',
						'enctype' => 'application/x-www-form-urlencoded',
						'target' => '_self'
					],
					'input' => [
						'type' => 'text'
					],
					'button' => [
						'type' => 'submit',
						'formenctype' => 'application/x-www-form-urlencoded',
						'formtarget' => '_self'
					],
					'select' => [
						'size' => '0'
					]
				],
				'empty' => ['id', 'class', 'style', 'title', 'action', 'lang', 'dir', 'onfocus', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup', 'target'], // attributes to remove if empty
				'urls' => ['href', 'src', 'action', 'poster'], // attributes to minify URLs in
				'urlskip' => [
					'link' => [
						'rel' => ['stylesheet', 'icon', 'shortcut icon', 'apple-touch-icon-precomposed', 'apple-touch-icon', 'preload', 'prefetch', 'author', 'help']
					]
				]
			],
			'custom' => [ // specify custom handlers

				// default to CSSdoc if available
				'style' => [
					'class' => '\\hexydec\\html\\custom', // the class that will parse the tag, must implement \hexydec\html\token
					'cache' => null, // a file path pattern containing %s to replace the generated file key, e.g. dirname(__DIR__).'/cache/%s.css'
					'minifier' => \class_exists('\\hexydec\\css\\cssdoc') ? $minify : null
				],

				// default to JSLite if available
				'script' => [
					'class' => '\\hexydec\\html\\custom',
					'cache' => null, // a file path pattern containing %s to replace the generated file key, e.g. dirname(__DIR__).'/cache/%s.js'
					'minifier' => \class_exists('\\hexydec\\jslite\\jslite') ? $minify : null
				]
			],
			'minify' => [
				'lowercase' => true, // lowercase tag and attribute names
				'whitespace' => true, // strip whitespace from text nodes
				'comments' => [
					'remove' => true, // remove comments
					'ie' => false // preserve IE comments
				],
				'urls' => [ // update internal URL's to be shorter
					'scheme' => true, // remove the scheme from URLs that have the same scheme as the current document
					'host' => true, // remove the host for own domain
					'relative' => true, // process absolute URLs to make them relative to the current document
					'parent' => true // process relative URLs to use relative parent links where it is shorter
				],
				'elements' => [ // apply specific minifier options to certain tag trees
					'textarea' => ['whitespace' => false],
					'pre' => ['whitespace' => false],
					'code' => ['whitespace' => false],
					'svg' => [
						'lowercase' => false,
						'attributes' => [
							'default' => false,
							'empty' => false,
							'option' => false,
							'boolean' => false
						],
						'singleton' => false,
						'close' => false
					]
				],
				'attributes' => [ // minify attributes
					'trim' => true, // trim whitespace from around attribute values
					'default' => true, // remove default attributes
					'empty' => true, // remove these attributes if empty
					'option' => true, // remove value attribute from option where the text node has the same value
					'style' => true, // minify the style tag
					'class' => true, // sort classes
					'sort' => true, // sort attributes for better gzip
					'boolean' => true // minify boolean attributes
				],
				'singleton' => true, // minify singleton element by removing slash
				'quotes' => true, // sets the output option 'quotestyle' to 'minimal'
				'close' => true, // don't write close tags where possible
				'safe' => false, // sets the minification presets to CSS safe options
				'email' => false, // sets the minification presets to email safe options
				'style' => [], // specify CSS minifier options
				'script' => [], // specify CSS javascript options
				'cache' => null // for style and scipt tags, the folder to cache the minified code in (Assuming you are using the default callback)
			],
			'output' => [
				'charset' => null, // set the output charset
				'quotestyle' => 'double', // double, single, minimal
				'singletonclose' => null, // string to close singleton tags, or false to leave as is
				'closetags' => false, // whether to force tags to have a closing tag (true) or follow tag::close
				'xml' => false, // sets the output presets to produce XML valid code
				'elements' => [ // output options for particular tags elements
					'svg' => [
						'xml' => true,
						'quotestyle' => 'double', // double, single, minimal
						'singletonclose' => '/>', // string to close singleton tags, or false to leave as is
						'closetags' => true, // whether to force tags to have a closing tag (true) or follow tag::close
					]
				]
			]
		];
		$this->config = $config ? \array_replace_recursive($default, $config) : $default;
	}
}
