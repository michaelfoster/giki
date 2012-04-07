<?php

require 'inc/lib/Markdown/markdown.php';
require 'inc/lib/SmartyPants/smartypants.php';

class Markup {
	public static function parse($text) {
		return SmartyPants(Markdown(htmlspecialchars($text)));
	}
	public static function diff($text) {
		$text = str_replace("\r", '', $text);
		
		$lines = explode("\n", $text);
		$text = '';
		
		foreach($lines as $line) {
			$class = array('line');
			
			if($line == '')
				continue;
			
			if(preg_match('/^\+/', $line))
				$class[] = 'add';
			elseif(preg_match('/^-/', $line))
				$class[] = 'del';
			elseif(preg_match('/^@@ /', $line))
				$class[] = 'gc';
			elseif(preg_match('/^\\\ /', $line))
				$class[] = 'eof';
			elseif(preg_match('/^ /', $line))
				$class[] = 'unchanged';
			elseif(preg_match('/^(diff \-\-git|index )/', $line))
				$class[] = 'head';
			
			$text .= '<div class="' . implode(' ', $class) . '">' . htmlspecialchars($line) . '</div>';
		}
		
		return $text;
	}
}

