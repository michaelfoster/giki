<?php

require 'inc/lib/Markdown/markdown.php';
require 'inc/lib/SmartyPants/smartypants.php';
require 'inc/lib/HTMLPurifier/HTMLPurifier.includes.php';
require 'inc/lib/FineDiff/finediff.php';

class Markup {
	public static function parse($text) {
		global $config;		
		
		$text = str_replace("\r", '', $text);
		$text = preg_replace("/([^\s])\n([^\s])/s", "$1 \n$2", $text);
		
		$html = SmartyPants(Markdown($text));
		
		$purifierConfig = HTMLPurifier_Config::createDefault();
		
		$purifierConfig->set('Core.Encoding', $config['encoding']);
		$purifierConfig->set('Core.EscapeInvalidTags', true);
		$purifierConfig->set('HTML.Doctype', 'HTML 4.01 Strict');
		$purifierConfig->set('HTML.Nofollow', true);
		$purifierConfig->set('Attr.EnableID', true);
		$purifierConfig->set('Attr.AllowedRel', array('nofollow'));
		$purifierConfig->set('AutoFormat.Linkify', true);
		
		$purifier = new HTMLPurifier($purifierConfig);
		
		$html = $purifier->purify($html);
		
		$html = str_replace('        ', "\t", $html);
		
		return $html;
	}
	public static function title($text) {
		$text = str_replace("\r", '', $text);
		
		if(preg_match("/^(#\s*(.*)|(.*)\n(-*|=*)\n)/", $text, $m))
			return SmartyPants($m[2] ? $m[2] : $m[3]);
		return false;
	}
	public static function diff($text) {
		$text = str_replace("\r", '', $text);
		$diff_lines = explode("\n", $text);
		
		$lines = array();
		$classes = array();
		
		$plus = array();
		$minus = array();
		$sections = array();
		
		foreach($diff_lines as &$line) {
			$class = array('line');
			
			if($line == '')
				continue;
			
			if(preg_match('/^\+/', $line)) {
				$class[] = 'add';
				
				if(!empty($minus))
					$plus[] = &$line;
			} elseif(preg_match('/^-/', $line)) {
				$class[] = 'del';
				
				$minus[] = &$line;
			} else {
				if(!empty($minus)) {
					$sections[] = array($minus, $plus);
					$minus = array();
					$plus = array();
				}
				
				if(preg_match('/^@@ /', $line))
					$class[] = 'gc';
				elseif(preg_match('/^\\\ /', $line))
					$class[] = 'eof';
				elseif(preg_match('/^ /', $line))
					$class[] = 'unchanged';
				elseif(preg_match('/^(diff \-\-git|index )/', $line))
					continue;
			}
			
			$lines[] = &$line;
			$classes[] = $class;
		}
		
		foreach($sections as &$section) {
			for($i = 0; $i < count($section[1]); $i++) {
				$input = substr($section[0][$i], 1);
				$output = substr($section[1][$i], 1);
				
				$opcodes = FineDiff::getDiffOpcodes($input, $output);
				
				$input_orig = $input;
				$output_orig = $output;
				
				$input = '';
				$output = '';
				
				$opcodes_len = strlen($opcodes);
				$offset = $opcodes_offset = 0;
				while($opcodes_offset <  $opcodes_len) {
					$opcode = substr($opcodes, $opcodes_offset, 1);
					$opcodes_offset++;
					$n = intval(substr($opcodes, $opcodes_offset));
					if($n)
						$opcodes_offset += strlen(strval($n));
					else
						$n = 1;
					
					if($opcode === 'c') { 
						// copy n characters
						$input .= htmlspecialchars(substr($input_orig, $offset, $n));
						$output .= htmlspecialchars(substr($input_orig, $offset, $n));
						$offset += $n;
					} else if($opcode === 'd') {
						// delete n characters
						$input .= '<span>' . htmlspecialchars(substr($input_orig, $offset, $n)) . '</span>';
						$offset += $n;
					} else {
						// insert n characters from opcodes
						$output .= '<span>' . htmlspecialchars(substr($opcodes, $opcodes_offset + 1, $n)) . '</span>';
						$opcodes_offset += 1 + $n;
					}
				}
				
				$section[0][$i] = substr($section[0][$i], 0, 1) . $input;
				$section[1][$i] = substr($section[1][$i], 0, 1) . $output;
			}
		}
		
		$html = '';
		for($i = 0; $i < count($lines); $i++) {
			$html .= '<div class="' . implode(' ', $classes[$i]) . '">' . $lines[$i] . '</div>';
		}
		
		return $html;
	}
	public static function URI($page) {
		return $page;
	}
	public static function URL($url) {
		if(strlen($url) <= 1)
			return false;
		
		if($url[0] == ':') {
			$page = substr($url, 1);
			$url = '?p=' . $page;
			
			$page = preg_replace('/#.*$/', '', $page);
			
			if(Git::exists($page))
				return array($url, 'internal');
			else
				return array($url, 'internal notfound');
		}
			
		
		return array($url, 'external');
	}
}

