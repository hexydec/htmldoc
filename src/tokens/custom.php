<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class custom implements token {

	/**
	 * @var array The custom configuration
	 */
	protected array $config = [];

	/**
	 * @var string The name of the tag
	 */
	protected string $tagName;

	/**
	 * @var string A string containing javascript
	 */
	protected string $content = '';

	/**
	 * Constructs the script object
	 *
	 * @param htmldoc $root The parent htmldoc object
	 * @param string $tagName A string specifying the parent tag name
	 */
	public function __construct(htmldoc $root, ?string $tagName = null) {
		$this->tagName = $tagName = \mb_strtolower($tagName ?? '');
		$this->config = $root->config['custom'][$tagName];
	}

	/**
	 * Parses the next HTML component from a tokenise object
	 *
	 * @param tokenise $tokens A tokenise object
	 * @return void
	 */
	public function parse(tokenise $tokens) : void {
		$tag = $this->tagName;

		// account for opening comment tag in <script>
		$pattern = '/'.($tag === 'script' ? '(?:\\s*+<!--)?' : '').'((?U:[^<]|<(?!\\/'.$tag.'>))*+)/i'; // capture anything up to the closing tag
		if (($token = $tokens->next($pattern)) !== null && $token[1]) {
			$this->content = $token[1];
		}
	}

	/**
	 * Minifies the internal representation of the script using an external minifier
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {
		$tag = $this->tagName;
		if (!isset($minify[$tag]) || $minify[$tag] !== false) {
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

				// minify the custom code
				if (($content = \call_user_func($config['minifier'], $content, $minify[$tag], $tag)) !== false) {

					// cache the minified code
					if ($file) {
						$dir = \dirname($file);
						if (!\is_dir($dir)) {
							\mkdir($dir, 0755);
						}
						\file_put_contents($file, $content);
					}
					$this->content = $content;
				}
			}
		}
	}

	/**
	 * Compile the custom tag content as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		return $options['xml'] ? '<![CDATA['.$this->content.']]>' : $this->content;
	}
}
