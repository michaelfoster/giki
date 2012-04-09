<?php

/*
   DO NOT EDIT!
*/

$config = array();

// where the git binary is located
$config['git'] = '/usr/bin/git';

// where to store our repository
$config['data_dir'] = 'data';
$config['git_dir'] = $config['data_dir'] . '/.git';
$config['extension'] = '.md';

// who to commit as
$config['author'] = "{$_SERVER['REMOTE_ADDR']} <giki@localhost>";

// php date() args
$config['date'] = 'H:i, j F Y';

// temporary file for error messages
$config['git_error'] = $config['data_dir'] . '/giki.tmp';

// wiki title
$config['title'] = 'Giki Example';

// wiki default page
$config['main_page'] = 'Main_Page';

// allowed characters in article/page names
$config['allowed_page_characters'] = 'a-zA-Z0-9_\\-.\/()';

// page encoding
$config['encoding'] = 'utf-8';

