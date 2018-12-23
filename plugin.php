<?php

class KokenImageProtect extends KokenPlugin {

	function __construct()
	{
		$this->register_hook('before_closing_head', 'render_into_head');
		$this->register_hook('before_closing_body', 'render_into_foot');
	}

	function after_install()
	{
		return $this->confirm_setup();
	}

	private function get_robots_path()
	{
		if (isset($_SERVER['PHP_SELF']) && isset($_SERVER['SCRIPT_FILENAME']))
		{
			$php_self = str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['PHP_SELF']);
			$doc_root = preg_replace('~' . $php_self . '$~i', '', $_SERVER['SCRIPT_FILENAME']);
		}
		else
		{
			$doc_root = $_SERVER['DOCUMENT_ROOT'];
		}

		return $doc_root . '/robots.txt';
	}

	function after_uninstall()
	{
		$robots = $this->get_robots_path();

		if (file_exists($robots))
		{
			$content = trim(preg_replace('/# BEGIN KOKEN.*# END KOKEN/s', '', file_get_contents($robots)));

			if (empty($content))
			{
				unlink($robots);
			}
			else
			{
				file_put_contents($robots, $content);
			}
		}


	}
	function confirm_setup() {

		$file = $this->get_robots_path();

		if (file_exists($file))
		{
			$robots = preg_replace('/# BEGIN KOKEN.*# END KOKEN/s', '', file_get_contents($file));
		}
		else
		{
			$robots = '';
		}

		if ($this->data->robots !== 'allow')
		{
			$base = trim(preg_replace('/\/api\.php(.*)?$/', '', $_SERVER['SCRIPT_NAME']), '/');

			if (!empty($base) && substr($base, 0, 1) !== '/')
			{
				$base = "/$base";
			}

			if ($this->data->robots !== 'block')
			{
				$presets = array(
					'tiny', 'small', 'medium', 'medium_large', 'large', 'xlarge', 'huge'
				);

				$whitelist = array_slice($presets, 0, array_search($this->data->robots, $presets));

				$allows = array();
				foreach($whitelist as $preset)
				{
					$allows[] = "Allow: $base/storage/cache/images/*,$preset.*";
				}
				$allows = "\n" . join("\n", $allows);
			}
			else
			{
				$allows = '';
			}

			$robots .= "\n\n";
			$robots .= <<<OUT
# BEGIN KOKEN
User-agent: Twitterbot
Disallow:

User-agent: *{$allows}
Disallow: {$base}/storage/originals/
Disallow: {$base}/storage/cache/images/
# END KOKEN
OUT;
		}

		file_put_contents($file, trim($robots));
		return true;

	}

	function render_into_head()
	{
		if ($this->data->pinterest) {
			echo <<<OUT
<meta name="pinterest" content="nopin" description="{$this->data->pinterest_msg}" />
OUT;
		}

		echo <<<OUT
<link rel="stylesheet" href="{$this->get_path()}/plugin.css" type="text/css" />
OUT;

		if ($this->data->appearance === 'custom') {
			echo <<<OUT
<style type="text/css">
.koken_image_protect_context_menu { color: {$this->data->textcolor}; background: {$this->data->bgcolor};}
.koken_image_protect_context_menu:after { border-bottom: 8px solid {$this->data->bgcolor}; }
</style>
OUT;
		}

	}

	function render_into_foot()
	{
		$copyright = htmlspecialchars($this->data->copyright, ENT_QUOTES);
		$custom_message = htmlspecialchars($this->data->custom_message, ENT_QUOTES);

		echo <<<OUT
<script src="{$this->get_path()}/plugin.js"></script>
<script type="text/javascript">ImageProtect({
	size: '{$this->data->overlayMinSize}',
	states: {
		menu: '{$this->data->menu}',
		overlay: '{$this->data->overlay}',
		visibility: '{$this->data->public}'
	},
	menu: {
		copyright: {
			message: '{$copyright}',
			prepend: '{$this->data->copyright_prepend_symbol}'
		},
		message: '{$custom_message}',
		size: {$this->data->message_size}
	},
	theme: '{$this->data->appearance}',
	shadow: '{$this->data->shadow}',
	radius: '{$this->data->radius}',
	pinterest: '{$this->data->pinterest}'
});</script>
OUT;

	}

}

?>