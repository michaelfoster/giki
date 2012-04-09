<?php

require 'inc/lib/Markdown/markdown.php';
require 'inc/lib/SmartyPants/smartypants.php';
require 'inc/lib/HTMLPurifier/HTMLPurifier.includes.php';

class Markup {
	public static function parse($text) {
		global $config;		
		
		$html = SmartyPants(Markdown($text));
		
		
		$purifierConfig = HTMLPurifier_Config::createDefault();

		// configuration goes here:
		$purifierConfig->set('Core.Encoding', 'UTF-8');
		$purifierConfig->set('Core.EscapeInvalidTags', true);
		$purifierConfig->set('HTML.Doctype', 'HTML 4.01 Strict');
		$purifierConfig->set('HTML.Nofollow', true);
		$purifierConfig->set('Attr.AllowedRel', array('nofollow'));
		$purifierConfig->set('AutoFormat.Linkify', true);
		
		$purifier = new HTMLPurifier($purifierConfig);
		
		return $purifier->purify($html);
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
				continue;
			
			$text .= '<div class="' . implode(' ', $class) . '">' . htmlspecialchars($line) . '</div>';
		}
		
		return $text;
	}
	public static function URL($url) {
		if(strlen($url) <= 1)
			return false;
		
		if($url[0] == ':') {
			$page = substr($url, 1);
			$url = '?p=' . $page;
			
			if(Git::exists($page))
				return array($url, 'internal');
			else
				return array($url, 'internal notfound');
		}
			
		
		return array($url, 'external');
	}
}

