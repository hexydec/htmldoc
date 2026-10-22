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
	 * @var bool Whether the source content was wrapped in a CDATA section
	 */
	protected bool $cdata = false;

	/**
	 * @var tag|null The parent tag object
	 */
	public ?tag $parent = null;

	/**
	 * Constructs the script object
	 *
	 * @param htmldoc $root The parent htmldoc object
	 * @param string $tagName A string specifying the parent tag name
	 * @param tag $parent The parent tag object
	 */
	public function __construct(htmldoc $root, ?string $tagName = null, ?tag $parent = null) {
		$this->parent = $parent;
		$this->tagName = $tagName = \mb_strtolower($tagName ?? '');
		$this->config = $root->lookup('custom', $tagName);
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

			// unwrap content that is a single CDATA section, so the minifier receives only the code
			$trimmed = \trim($token[1]);
			if (\str_starts_with($trimmed, '<![CDATA[') && \str_ends_with($trimmed, ']]>') && !\str_contains($inner = \substr($trimmed, 9, -3), ']]>')) {
				$this->content = $inner;
				$this->cdata = true;
			} else {
				$this->content = $token[1];
			}
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

		// drop the source's CDATA wrapper, output re-adds one in XML where the content needs it
		if ($minify['cdata']) {
			$this->cdata = false;
		}

		// minify the code
		if (!isset($minify[$tag]) || $minify[$tag] !== false) {
			$config = $this->config;
			$content = \trim($this->content);

			// minify?
			if ($content && $config['minifier']) {

				// the cache file is keyed by the options as well as the code, as different options give different output
				$file = empty($config['cache']) ? null : \sprintf($config['cache'], \md5(\serialize($minify[$tag]).$content));

				// use the cached output
				if ($file !== null && \file_exists($file) && ($output = \file_get_contents($file)) !== false) {
					$this->content = $output;

				// minify the custom code
				} elseif (($content = \call_user_func($config['minifier'], $content, $minify[$tag], $tag)) !== false) {

					// cache the minified code
					if ($file) {
						$dir = \dirname($file);
						if (!\is_dir($dir)) {
							\mkdir($dir, 0755, true);
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
		$content = $this->content;

		// XML and foreign content such as SVG are parsed as markup, so need a wrapper where the content cannot be written as text
		$wrap = $this->cdata;
		if (!$wrap && (\strpbrk($content, '<&') !== false || \str_contains($content, ']]>'))) {
			$wrap = $options['xml'];
			$parent = $this->parent;
			while (!$wrap && $parent !== null) {
				$wrap = \in_array($parent->tagNameLower, ['svg', 'math'], true);
				$parent = $parent->parent;
			}
		}

		// wrap in CDATA, splitting any ]]> so it cannot end the section early
		if ($wrap) {
			$content = '<![CDATA['.\str_replace(']]>', ']]]]><![CDATA[>', $content).']]>';
		}
		return $content;
	}
}
