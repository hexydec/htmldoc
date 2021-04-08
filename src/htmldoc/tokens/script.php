<?php
declare(strict_types = 1);
namespace hexydec\html;

class script implements token {

	/**
	 * @var htmldoc The parent htmldoc object
	 */
	protected $root;

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
		$this->root = $root;
	}

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @param array $config An array of configuration options
	 * @return void
	 */
	public function parse(tokenise $tokens) : void {
		// $pattern = '/\G(?:"(?:\\\\[^\\n\\r]|[^\\\\"\\n\\r])*+"|\'(?:\\\\[^\\n\\r]|[^\\\\\'\\n\\r])*+\'|`(?:\\\\.|[^\\\\`])*+`|[^"\'`]*)*(?=<\\/script>)/iU';
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
			$this->content = trim($this->content);
			if ($this->content) {
				$func = $this->root->getConfig('custom', 'script', 'config', 'minifier');
				if ($func) {
					$this->content = call_user_func($func, $this->content, $minify['script']);
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
