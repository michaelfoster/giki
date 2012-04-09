<?php

require 'inc/giki.php';

header('Content-Type: text/html; charset=' . $config['encoding']);

if(!is_dir($config['git_dir'])) {
	Git::initialize();
}

if(!isset($_GET['p']) || $_GET['p'] == '') {
	header('Location: ?p=' . urlencode($config['default_page']), 302); 
	exit;
}

$page = $_GET['p'];

if(!isset($_GET['rev']))
	$rev = 'HEAD';
else
	$rev = $_GET['rev'];

if(!Git::exists($page)) {
	$commit = false;
	$content = false;
	$parent = false;
	$child = false;
	
	// force edit
	$_GET['edit'] = true;
	unset($_GET['raw']);
	unset($_GET['history']);
	unset($_GET['diff']);
} else {
	$commit = Git::commit($page, $rev);
	$content = Git::show($page, $rev);
	$parent = Git::parent($page, $commit['hash']);
	$child = Git::child($page, $commit['hash']);
}

$page_uri = Markup::URI($page);
$title = Git::title($page);

$args = array('page' => $page, 'page_uri' => $page_uri, 'title' => &$title, 'commit' => $commit, 'parent' => $parent, 'child' => $child, 'content' => &$content);

if(isset($_GET['commit']))
	$args['show_commit'] = true;

if(isset($_GET['raw'])) {
	header('Content-Type: text/plain');
	die($content);
} elseif(isset($_GET['history'])) {
	$args['history'] =  Git::commit($page, $commit['hash'], 150);
} elseif(isset($_GET['diff'])) {
	if($parent)
		$args['diff'] = Markup::diff(Git::diff($page, $commit['hash']));
	else
		$content = Markup::parse($content);
	
	$args['show_commit'] = true;
} elseif(isset($_GET['edit'])) {
	if(isset($_POST['content'], $_GET['base'], $_POST['message'])) {
		if($commit['hash'] != $_GET['base']) {
			// TODO
			error('updated before submit');
		} else {
			Git::write($page, $_POST['content'], $_POST['message']);
			header('Location: ?p=' . $page_uri, 302); 
			exit;
		}
	} else {
		$args['edit'] = true;
	}
} else {
	if(!$title = Markup::title($content))
		$title = Git::title($page);
	$content = Markup::parse($content);
}

echo render('view.html', $args);

