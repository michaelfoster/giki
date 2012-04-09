<?php

class HTMLPurifier_URIScheme_irc extends HTMLPurifier_URIScheme {
	public $default_port = 6667;
	public $browsable = false;
	public $hierarchical = true;

	public function doValidate(&$uri, $config, $context) {
		
		return true;
	}
}

