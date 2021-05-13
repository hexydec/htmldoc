<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class script implements token {

	/**
	 * @var array The style configuration
	 */
	protected $config = [];

	/**
	 * @var string A string containing javascript
	 */
	protected $content = '';

	/**
	 * Constructs the script object
	 *
	 * @param htmldoc $root The parent htmldoc object
	 */
	public function __construct(htmldoc $root) {
		$this->config = $root->config['custom']['script'];
	}

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @param array $config An array of configuration options
	 * @return void
	 */
	public function parse(tokenise $tokens) : void {
		$pattern = '/[\\S\\s]*(?=<\\/script>)/iU';
		if (($token = $tokens->next($pattern)) !== null && $token[0]) {
			$this->content = $token[0];
		}
	}

	/**
	 * Minifies the internal representation of the script using an external minifier
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {
		if (!isset($minify['script']) || $minify['script'] !== false) {
			$config = $this->config;
			$content = \trim($this->content);

			// minify?
			if ($content && $config['minifier']) {

				// cache the output?
				if (empty($config['cache'])) {
					$file = null;
				} else {
					$file = sprintf($config['cache'], md5($content));
					if (\file_exists($file) && ($output = \file_get_contents($file)) !== false) {
						$this->content = $output;
						return;
					}
				}

				// minify the CSS
				if (($content = \call_user_func($config['minifier'], $content, $minify['script'])) !== false) {

					// cache the minified code
					if ($file) {
						\file_put_contents($file, $content);
					}
					$this->content = $content;
				}
			}
		}
	}

	/**
	 * Compile the scripts as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		return $options['xml'] ? '<![CDATA['.$this->content.']]>' : $this->content;
	}
}