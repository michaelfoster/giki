<?php

require 'inc/config.php';
require 'inc/display.php';
require 'inc/markup.php';

class Git {
	public static function path($page, $data_dir = true, $create = false) {
		global $config;
		
		$path = $page . $config['extension'];
		
		if(preg_match('/\.\.[\/\\\]/', $page))
			error('Invalid page title: ' . $page);
		
		if($create) {
			if(dirname($path) != '.') {
				if(!is_dir($config['data_dir'] . '/' . dirname($path)))
					if(!@mkdir($config['data_dir'] . '/' . dirname($path), 0775, true))
						error('Could not create directory: ' . dirname($path));
			}
		}
		
		if($data_dir)
			return $config['data_dir'] . '/' . $path;
		else
			return $path;
	}
	
	public static function write($page, $body, $commit_message = false) {
		global $config;
		
		if($commit_message === '' || $commit_message === false) {
			$commit_message = 'no commit message';
		}
		
		file_put_contents(Git::path($page, true, true), $body);
		Git::exec('add ' . escapeshellarg(Git::path($page, false)));
		Git::exec('commit --allow-empty --no-verify --message=' . escapeshellarg($commit_message) . ' --author=' . escapeshellarg($config['author']));
		Git::exec('gc');
	}
	
	public static function exists($page) {
		return file_exists(Git::path($page));
	}
	
	public static function title($page) {
		$page = str_replace('_', ' ', $page);
		
		return $page;
	}
	
	public static function show($page, $revision = 'HEAD') {
		return Git::exec('show ' . escapeshellarg($revision) . ':' . escapeshellarg(Git::path($page, false)));
	}
	
	public static function diff($page, $revision = 'HEAD') {
		return Git::exec('diff ' . escapeshellarg($revision) . '^ -- ' . escapeshellarg(Git::path($page, false)));
	}
	
	public static function commit($page, $revision = 'HEAD', $n = 1, $die_on_error = true) {
		$log = trim(Git::exec('log -' . (int)$n . ' --pretty="%h %at %aN %s" ' . escapeshellarg($revision) . ' -- ' . escapeshellarg(Git::path($page, false)), $die_on_error));
		$commits = array();
		
		$lines = explode("\n", $log);
		
		if(!preg_match_all('/^([a-f0-9]{7}) (\d+) (\S+) (.*)$/m', $log, $c)) {
			if(!$die_on_error)
				return false;
			
			error('Invalid `git log` response: ' . $log);
		}
		
		for($i = 0; $i < count($c[0]); $i++) {
			$commits[] = array(
				'hash' => $c[1][$i],
				'time' => (int)$c[2][$i],
				'author' => $c[3][$i],
				'subject' => $c[4][$i]
			);
		}
		
		if($n == 1)
			return $commits[0];
		
		return $commits;
	}
	
	public static function parent($page, $revision) {
		return Git::commit($page, $revision . '^', 1, false);
	}
	
	public static function child($page, $revision) {
		$child = Git::exec('log --format="%h" --children -n1 ' . escapeshellarg($revision) . '..HEAD -- ' . escapeshellarg(Git::path($page, false)));
		
		if(!$child)
			return false;
		
		return trim($child);
	}
	
	public static function exec($args, $die_on_error = true) {
		global $config;
		
		$command = "{$config['git']} --git-dir={$config['git_dir']} --work-tree={$config['data_dir']} $args 2> " . escapeshellarg($config['git_error']);
		$output = shell_exec($command);
		if(is_null($output) && $error = file_get_contents($config['git_error'])) {
			if($die_on_error) {
				@unlink($config['git_error']);
				error($error);
			}
		}
		
		@unlink($config['git_error']);
		
		return $output;
	}
	
	public static function initialize() {
		global $config;
		
		if(!is_dir($config['data_dir']))
			@mkdir($config['data_dir']);
		
		Git::exec('init');
		Git::write($config['default_page'], '## Hello, world!', 'init');
	}
}

