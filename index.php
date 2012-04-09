<?php

require 'inc/giki.php';

header('Content-Type: text/html; charset=' . $config['encoding']);

if(!is_dir($config['git_dir'])) {
	Git::initialize();
}

if(!isset($_GET['p']) || $_GET['p'] == '') {
	header('Location: ?p=' . urlencode($config['main_page']), 302); 
	exit;
}

$page = $_GET['p'];


if(!isset($_GET['rev']))
	$rev = 'HEAD';
else
	$rev = $_GET['rev'];

$page_uri = Markup::URI($page);

if($page_uri != $page) {
	header('Location: ?p=' . $page_uri, 302); 
	exit;
}

if(!preg_match('/^[' . $config['allowed_page_characters'] . ']*$/', $page))
	error('Invalid page: ' . $page);


$commit = false;
$content = false;
$parent = false;
$child = false;
$title = false;

$args = array('page' => &$page, 'page_uri' => &$page_uri, 'title' => &$title, 'commit' => &$commit, 'parent' => &$parent, 'child' => &$child, 'content' => &$content, 'menu' => true);

if(preg_match('/^Special:(.*)$/', $page, $m)) {
	$args['menu'] = false;
	
	$special = $m[1];
	
	switch($special) {
		case 'AllPages':
			$pages = explode("\n", trim(Git::exec('ls-files')));
			foreach($pages as &$file) {
				$file = preg_replace('/\.md$/', '', $file);
				$file = array($file, Git::title($file));
			}
			
			$args['allpages'] = $pages;
			
			break;
		default:
			$content = 'Invalid special page.';
	}
	
} elseif(!Git::exists($page)) {
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

$title = Git::title($page);

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
		$title = 'Editing “' . $title . '”';
		$args['edit'] = true;
	}
} else {
	if(!$title = Markup::title($content))
		$title = Git::title($page);
	$content = Markup::parse($content);
}

echo render('view.html', $args);

