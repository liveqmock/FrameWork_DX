<?php

/*
 * 妈网统计平台，前端入口
 * 20120524 * 
 */
define('APPTYPEID', 0);
define('CURSCRIPT', 'index.php');
define('BASESCRIPT', 'index.php'); //为应用入口，改变则全站改变
define('TPLDIR', 'template/tongji');
require './source/class/class_core.php';
$discuz = & discuz_core::instance();
error_reporting(E_ALL);
error_reporting(0);
//操作入口控制
$modarray = array('index', 'top', 'export', 'compare', 'member');
$mod = !in_array($discuz->var['mod'], $modarray) ? 'home' : $discuz->var['mod'];
$oparray = array(
    'index' => array('period', 'forum', 'topic', 'module', 'channel', 'index'),
    'top' => array('forum', 'topic', 'module', 'channel'),
    'export' => array('forum', 'topic', 'module', 'channel'),
    'compare' => array('forum', 'topic', 'module', 'channel'),
    'member' => array('logging', 'logout'),
);
$op = !in_array($discuz->var['op'], $oparray[$mod]) ? $oparray[$mod][0] : $discuz->var['op'];

$hover[$mod] = '_hover';
$discuz->init();
define('CURMODULE', $mod);
$navtitle = str_replace('{bbname}', $_G['setting']['sitename'], $_G[brand][subject]);
include_once template("main/index");

?>