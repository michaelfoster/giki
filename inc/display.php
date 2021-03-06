<?php

require 'inc/lib/Twig/Autoloader.php';

Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array(
	'autoescape' => false
        // 'cache' => 'templates/cache',
));

function render($template, $arg) {
	global $twig, $config;
	
	$arg['config'] = $config;
	
	$html = $twig->render($template, $arg);
	
	return $html;
}

function error($message) {
	global $config;
	
	die(render('error.html', array('error' => $message)));
}

