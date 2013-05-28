<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_menu.php 21972 2011-04-19 02:51:52Z monkey $
 */

global $_G;
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$isfounder = isset($isfounder) ? $isfounder : isfounder();

$topmenu = $menu = array();

$topmenu = array (
	'index' => '',
	'global' => '',
	'user' => '',
	'tools' => '',
);

$menu['index'] = array(
	array('menu_home', 'index'),
	array('menu_custommenu_manage', 'misc_custommenu'),
);

$custommenu = get_custommenu();
$menu['index'] = array_merge($menu['index'], $custommenu);

$menu['global'] = array(
	array('menu_setting_basic', 'setting_basic'),
	array('menu_setting_datetime', 'setting_datetime'),
);

$menu['user'] = array(
	array('menu_members_edit', 'members_search'),
	array('menu_members_add', 'members_add'),
//	array('menu_admingroups', 'admingroup'),
//	array('menu_usergroups', 'usergroups'),
);

if(is_array($_G['setting']['verify'])) {
	foreach($_G['setting']['verify'] as $vid => $verify) {
		if($vid != 7 && $verify['available']) {
			$menu['user'][] = array($verify['title'], "verify_verify_$vid");
		}
	}
}


if(file_exists($menudir = DISCUZ_ROOT.'./source/admincp/menu')) {
	$adminextend = $adminextendnew = array();
	if(file_exists($adminextendfile = DISCUZ_ROOT.'./data/cache/cache_adminextend.php')) {
		@include $adminextendfile;
	}
	$menudirhandle = dir($menudir);
	while($entry = $menudirhandle->read()) {
		if(!in_array($entry, array('.', '..')) && preg_match("/^menu\_([\w\.]+)$/", $entry, $entryr) && substr($entry, -4) == '.php' && strlen($entry) < 30 && is_file($menudir.'/'.$entry)) {
			@include_once $menudir.'/'.$entry;
			$adminextendnew[] = $entryr[1];
		}
	}
	if($adminextend != $adminextendnew) {
		@unlink($adminextendfile);
		if($adminextendnew) {
			require_once libfile('function/cache');
			writetocache('adminextend', getcachevars(array('adminextend' => $adminextendnew)));
		}
		unset($_G['lang']['admincp']);
	}
}

if($isfounder) {
	$menu['plugin'] = array(
		array('menu_addons', 'addons'),
		array('menu_plugins', 'plugins'),
	);
}
loadcache('adminmenu');
if(is_array($_G['cache']['adminmenu'])) {
	foreach($_G['cache']['adminmenu'] as $row) {
		$menu['plugin'][] = array($row['name'], $row['action']);
	}
}
if(!$menu['plugin']) {
	unset($topmenu['plugin']);
}

$menu['tools'] = array(
	array('menu_tools_updatecaches', 'tools_updatecache'),
//	array('menu_tools_updatecounters', 'counter'),
	array('menu_logs', 'logs'),
	array('menu_misc_cron', 'misc_cron'),
);
if($isfounder && $x) {
	$topmenu['founder'] = '';

	$menu['founder'] = array(
		array('menu_founder_perm', 'founder_perm'),
		array('menu_setting_mail', 'setting_mail'),
		array('menu_setting_uc', 'setting_uc'),
		array('menu_db', 'db_export'),
		array('menu_postsplit', 'postsplit_manage'),
		array('menu_threadsplit', 'threadsplit_manage'),
	);

	$menu['uc'] = array();
}

if(!isfounder() && !isset($GLOBALS['admincp']->perms['all'])) {
	$menunew = $menu;
	foreach($menu as $topkey => $datas) {
		if($topkey == 'index') {
			continue;
		}
		$itemexists = 0;
		foreach($datas as $key => $data) {
			if(array_key_exists($data[1], $GLOBALS['admincp']->perms)) {
				$itemexists = 1;
			} else {
				unset($menunew[$topkey][$key]);
			}
		}
		if(!$itemexists) {
			unset($topmenu[$topkey]);
			unset($menunew[$topkey]);
		}
	}
	$menu = $menunew;
}

?>