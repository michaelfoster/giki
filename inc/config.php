<?php

$config = array();

$config['git'] = '/usr/bin/git';

$config['data_dir'] = 'data';
$config['git_dir'] = $config['data_dir'] . '/.git';

$config['git_error'] = '/tmp/gwiki.error';

$config['default_page'] = 'Main_Page';

$config['extension'] = '.md';

$config['author'] = "{$_SERVER['REMOTE_ADDR']} <giki@localhost>";

$config['date'] = 'H:i, j F Y';

$config['allowed_page_characters'] = 'a-fA-F0-9_\\-.\/';

