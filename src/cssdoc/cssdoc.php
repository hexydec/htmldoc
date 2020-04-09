<?php
declare(strict_types = 1);
namespace hexydec\css;

class cssdoc {

	/**
	 * @var array $tokens Regexp components keyed by their corresponding codename for tokenising HTML
	 */
	protected static $tokens = [
	   'whitespace' => '\s++',
	   'comment' => '\\/\\*[\d\D]*?\\*\\/',
	   'quotes' => '(?<!\\\\)("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')',
	   'join' => '[>+~]',
	   'comparison' => '[\^*$<>]?=', // comparison operators for media queries or attribute selectors
	   'curlyopen' => '{',
	   'curlyclose' => '}',
	   'squareopen' => '\[',
	   'squareclose' => '\]',
	   'bracketopen' => '\(',
	   'bracketclose' => '\)',
	   'comma' => ',',
	   'colon' => ':',
	   'semicolon' => ';',
	   'directive' => '@[a-z-]++',
	   'string' => '!?[^\[\]{}\(\):;,>+~\^$!" \n\r\t]++'
	];

	/**
	 * @var array $config Object configuration array
	 */
	protected $config = [
		'removesemicolon' => true,
		'removezerounits' => true,
		'removeleadingzero' => true,
		'convertquotes' => true,
		'removequotes' => true,
		'shortenhex' => true,
		'email' => false,
		'maxline' => false,
		'lowerproperties' => true,
		'lowervalues' => true,
		'output' => 'minify'
	];

	protected $output = [
		'output' => 'minify',
		'prefix' => ''
	];

	protected $document;

	/**
	 * Constructs the object
	 *
	 * @param array $config An array of configuration parameters that is recursively merged with the default config
	 */
	public function __construct(array $config = []) {
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * Calculates the length property
	 *
	 * @param string $var The name of the property to retrieve, currently 'length' and output
	 * @return mixed The number of children in the object for length, the output config, or null if the parameter doesn't exist
	 */
	public function __get(string $var) {
		if ($var == 'length') {
			return count($this->children);
		} elseif ($var == 'output') {
			return $this->output;
		}
		return null;
	}

	/**
	 * Retrieves the children of the document as an array
	 *
	 * @return array An array of child nodes
	 */
	public function toArray() : array {
		return $this->children;
	}

	/**
	 * Open an HTML file from a URL
	 *
	 * @param string $url The address of the HTML file to retrieve
	 * @param resource $context An optional array of context parameters
	 * @param string &$error A reference to any user error that is generated
	 * @return mixed The loaded HTML, or false on error
	 */
	public function open(string $url, resource $context = null, string &$error = null) {

		// open a handle to the stream
		if (($handle = @fopen($url, 'rb', false, $context)) === false) {
			$error = 'Could not open file "'.$url.'"';

		// retrieve the stream contents
		} elseif (($html = stream_get_contents($handle)) === false) {
			$error = 'Could not read file "'.$url.'"';

		// success
		} else {

			// find charset in headers
			$charset = null;
			$meta = stream_get_meta_data($handle);
			if (!empty($meta['wrapper_data'])) {
				foreach ($meta['wrapper_data'] AS $item) {
					if (mb_stripos($item, 'Content-Type:') === 0 && ($charset = mb_stristr($item, 'charset=')) !== false) {
						$charset = mb_substr($charset, 8);
						break;
					}
				}
			}

			// load html
			if ($this->load($html, $charset, $error)) {
				return $html;
			}
		}
		return false;
	}

	/**
	 * Parse an HTML string into the object
	 *
	 * @param string $html A string containing valid HTML
	 * @param string $charset The charset of the document
	 * @param string &$error A reference to any user error that is generated
	 * @return bool Whether the input HTML was parsed
	 */
	public function load(string $css, string $charset = null, &$error = null) : bool {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromCss($css)) !== null) {
			$css = mb_convert_encoding($css, mb_internal_encoding(), $charset);
		}

		// reset the document
		$this->children = [];

		// tokenise the input HTML
		if (($tokens = \hexydec\html\tokenise::tokenise($css, self::$tokens)) === false) {
			$error = 'Could not tokenise input';

		// parse the document
		} elseif (!$this->parse($tokens)) {
			$error = 'Input is not invalid';

		// success
		} else {
			// var_dump($tokens);
			return true;
		}
		return false;
	}

	/**
	 * Reads the charset defined in the Content-Type meta tag, or detects the charset from the HTML content
	 *
	 * @param string $html A string containing valid HTML
	 * @return string The defined or detected charset or null if the charset is not defined
	 */
	protected function getCharsetFromCss(string $css) : ?string {
		if (mb_strpos($css, '@charset') === 0 && ($end = mb_strpos($css, '";')) !== false) {
			return mb_substr($css, 10, $end);
		} elseif (($charset = mb_detect_encoding($css)) !== false) {
			return $charset;
		}
		return null;
	}

	/**
	 * Parses an array of tokens into an CSS document
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @return bool Whether the parser was able to capture any objects
	 */
	protected function parse(array &$tokens) : bool {
		$this->document = new document($this);
		return $this->document->parse($tokens);
	}

	/**
	 * Minifies the internal representation of the comment
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify = []) : void {
		$minify = array_merge($this->config, $minify);

		// set email options
		if ($minify['email']) {
			$minify['maxline'] = 800;
			$minify['shortenhex'] = false;
		}
		$this->document->minify($minify);
	}

	/**
	 * Compile the property to a string
	 *
	 * @param array $options An array of compilation options
	 * @return void
	 */
	public function compile(array $options = []) : string {
		$options = array_merge($this->output, $options);
		return $this->document->compile($options);
	}
}
