<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_core.php 24580 2011-09-27 05:38:22Z zhengqingpeng $
 */
if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

define('DISCUZ_CORE_FUNCTION', true);

function system_error($message, $show = true, $save = true, $halt = true) {
    require_once libfile('class/error');
    discuz_error::system_error($message, $show, $save, $halt);
}

function updatesession($force = false) {

    global $_G;
    static $updated = false;

    if (!$updated) {
	if ($_G['uid']) {
	    if ($_G['cookie']['ulastactivity']) {
		$ulastactivity = authcode($_G['cookie']['ulastactivity'], 'DECODE');
	    } else {
		$ulastactivity = getuserprofile('lastactivity');
		dsetcookie('ulastactivity', authcode($ulastactivity, 'ENCODE'), 31536000);
	    }
	}
	$discuz = & discuz_core::instance();
	$oltimespan = $_G['setting']['oltimespan'];
	$lastolupdate = $discuz->session->var['lastolupdate'];
	if ($_G['uid'] && $oltimespan && TIMESTAMP - ($lastolupdate ? $lastolupdate : $ulastactivity) > $oltimespan * 60) {
	    $discuz->session->set('lastolupdate', TIMESTAMP);
	}
	foreach ($discuz->session->var as $k => $v) {
	    if (isset($_G['member'][$k]) && $k != 'lastactivity') {
		$discuz->session->set($k, $_G['member'][$k]);
	    }
	}

	foreach ($_G['action'] as $k => $v) {
	    $discuz->session->set($k, $v);
	}

	$discuz->session->update();
	$updated = true;

	if ($_G['uid'] && TIMESTAMP - $ulastactivity > 21600) {
	    dsetcookie('ulastactivity', authcode(TIMESTAMP, 'ENCODE'), 31536000);
	    DB::update('common_member_status', array('lastip' => $_G['clientip'], 'lastactivity' => TIMESTAMP, 'lastvisit' => TIMESTAMP), "uid='$_G[uid]'", 1);
	}
    }
    return $updated;
}

function dmicrotime() {
    return array_sum(explode(' ', microtime()));
}

function setglobal($key, $value, $group = null) {
    global $_G;
    $k = explode('/', $group === null ? $key : $group . '/' . $key);
    switch (count($k)) {
	case 1: $_G[$k[0]] = $value;
	    break;
	case 2: $_G[$k[0]][$k[1]] = $value;
	    break;
	case 3: $_G[$k[0]][$k[1]][$k[2]] = $value;
	    break;
	case 4: $_G[$k[0]][$k[1]][$k[2]][$k[3]] = $value;
	    break;
	case 5: $_G[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] = $value;
	    break;
    }
    return true;
}

function getglobal($key, $group = null) {
    global $_G;
    $k = explode('/', $group === null ? $key : $group . '/' . $key);
    switch (count($k)) {
	case 1: return isset($_G[$k[0]]) ? $_G[$k[0]] : null;
	    break;
	case 2: return isset($_G[$k[0]][$k[1]]) ? $_G[$k[0]][$k[1]] : null;
	    break;
	case 3: return isset($_G[$k[0]][$k[1]][$k[2]]) ? $_G[$k[0]][$k[1]][$k[2]] : null;
	    break;
	case 4: return isset($_G[$k[0]][$k[1]][$k[2]][$k[3]]) ? $_G[$k[0]][$k[1]][$k[2]][$k[3]] : null;
	    break;
	case 5: return isset($_G[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]]) ? $_G[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] : null;
	    break;
    }
    return null;
}

function getgpc($k, $type = 'GP') {
    $type = strtoupper($type);
    switch ($type) {
	case 'G': $var = &$_GET;
	    break;
	case 'P': $var = &$_POST;
	    break;
	case 'C': $var = &$_COOKIE;
	    break;
	default:
	    if (isset($_GET[$k])) {
		$var = &$_GET;
	    } else {
		$var = &$_POST;
	    }
	    break;
    }

    return isset($var[$k]) ? $var[$k] : NULL;
}

function getuserbyuid($uid) {
    static $users = array();
    if (empty($users[$uid])) {
	$users[$uid] = DB::fetch_first("SELECT * FROM " . DB::table('common_member') . " WHERE uid='$uid'");
    }
    return $users[$uid];
}


function getuserprofile($field) {
	global $_G;
	if(isset($_G['member'][$field])) {
		return $_G['member'][$field];
	}
	static $tablefields = array(
		'status'	=> array('regip','lastip','lastvisit','lastactivity'),
	);
	$profiletable = '';
	foreach($tablefields as $table => $fields) {
		if(in_array($field, $fields)) {
			$profiletable = $table;
			break;
		}
	}
	if($profiletable) {
		$data = array();
		if($_G['uid']) {
			$data = DB::fetch_first("SELECT ".implode(', ', $tablefields[$profiletable])." FROM ".DB::table('common_member_'.$profiletable)." WHERE uid='$_G[uid]'");
		}
		if(!$data) {
			foreach($tablefields[$profiletable] as $k) {
				$data[$k] = '';
			}
		}
		$_G['member'] = array_merge(is_array($_G['member']) ? $_G['member'] : array(), $data);
		return $_G['member'][$field];
	}
}

function daddslashes($string, $force = 1) {
    if (is_array($string)) {
	$keys = array_keys($string);
	foreach ($keys as $key) {
	    $val = $string[$key];
	    unset($string[$key]);
	    $string[addslashes($key)] = daddslashes($val, $force);
	}
    } else {
	$string = addslashes($string);
    }
    return $string;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4;
    $key = md5($key != '' ? $key : getglobal('authkey'));
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
	$rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
	$j = ($j + $box[$i] + $rndkey[$i]) % 256;
	$tmp = $box[$i];
	$box[$i] = $box[$j];
	$box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
	$a = ($a + 1) % 256;
	$j = ($j + $box[$a]) % 256;
	$tmp = $box[$a];
	$box[$a] = $box[$j];
	$box[$j] = $tmp;
	$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
	if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
	    return substr($result, 26);
	} else {
	    return '';
	}
    } else {
	return $keyc . str_replace('=', '', base64_encode($result));
    }
}

function dfsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
    require_once libfile('function/filesock');
    return _dfsockopen($url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block);
}

function dhtmlspecialchars($string) {
    if (is_array($string)) {
	foreach ($string as $key => $val) {
	    $string[$key] = dhtmlspecialchars($val);
	}
    } else {
	$string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
	if (strpos($string, '&amp;#') !== false) {
	    $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
	}
    }
    return $string;
}

function dexit($message = '') {
    echo $message;
    output();
    exit();
}

function dheader($string, $replace = true, $http_response_code = 0) {
    $islocation = substr(strtolower(trim($string)), 0, 8) == 'location';
    if (defined('IN_MOBILE') && strpos($string, 'mobile') === false && $islocation) {
	if (strpos($string, '?') === false) {
	    $string = $string . '?mobile=yes';
	} else {
	    if (strpos($string, '#') === false) {
		$string = $string . '&mobile=yes';
	    } else {
		$str_arr = explode('#', $string);
		$str_arr[0] = $str_arr[0] . '&mobile=yes';
		$string = implode('#', $str_arr);
	    }
	}
    }
    $string = str_replace(array("\r", "\n"), array('', ''), $string);
    if (empty($http_response_code) || PHP_VERSION < '4.3') {
	@header($string, $replace);
    } else {
	@header($string, $replace, $http_response_code);
    }
    if ($islocation) {
	exit();
    }
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {

    global $_G;

    $config = $_G['config']['cookie'];

    $_G['cookie'][$var] = $value;
    $var = ($prefix ? $config['cookiepre'] : '') . $var;
    $_COOKIE[$var] = $value;

    if ($value == '' || $life < 0) {
	$value = '';
	$life = -1;
    }

    if (defined('IN_MOBILE')) {
	$httponly = false;
    }

    $life = $life > 0 ? getglobal('timestamp') + $life : ($life < 0 ? getglobal('timestamp') - 31536000 : 0);
    $path = $httponly && PHP_VERSION < '5.2.0' ? $config['cookiepath'] . '; HttpOnly' : $config['cookiepath'];

    $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
    if (PHP_VERSION < '5.2.0') {
	setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure);
    } else {
	setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure, $httponly);
    }
}

function getcookie($key) {
    global $_G;
    return isset($_G['cookie'][$key]) ? $_G['cookie'][$key] : '';
}

function fileext($filename) {
    return addslashes(trim(substr(strrchr($filename, '.'), 1, 10)));
}

function formhash($specialadd = '') {
    global $_G;
    $hashadd = defined('IN_ADMINCP') ? 'Only For Discuz! Admin Control Panel' : '';
    return substr(md5(substr($_G['timestamp'], 0, -7) . $_G['username'] . $_G['uid'] . $_G['authkey'] . $hashadd . $specialadd), 8, 8);
}

function checkrobot($useragent = '') {
    static $kw_spiders = array('bot', 'crawl', 'spider', 'slurp', 'sohu-search', 'lycos', 'robozilla');
    static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

    $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
    if (strpos($useragent, 'http://') === false && dstrpos($useragent, $kw_browsers))
	return false;
    if (dstrpos($useragent, $kw_spiders))
	return true;
    return false;
}

function checkmobile() {
    global $_G;
    $mobile = array();
    static $mobilebrowser_list = array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
 'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
 'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
 'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
 'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
 'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
 'benq', 'haier', '^lct', '320x320', '240x320', '176x220');
    $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (($v = dstrpos($useragent, $mobilebrowser_list, true))) {
	$_G['mobile'] = $v;
	return true;
    }
    $brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
    if (dstrpos($useragent, $brower))
	return false;

    $_G['mobile'] = 'unknown';
    if ($_GET['mobile'] === 'yes') {
	return true;
    } else {
	return false;
    }
}

function dstrpos($string, &$arr, $returnvalue = false) {
    if (empty($string))
	return false;
    foreach ((array) $arr as $v) {
	if (strpos($string, $v) !== false) {
	    $return = $returnvalue ? $v : true;
	    return $return;
	}
    }
    return false;
}

function isemail($email) {
    return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

function quescrypt($questionid, $answer) {
    return $questionid > 0 && $answer != '' ? substr(md5($answer . md5($questionid)), 16, 8) : '';
}

function random($length, $numeric = 0) {
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
	$hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}

function strexists($string, $find) {
    return !(strpos($string, $find) === FALSE);
}

function lang($file, $langvar = null, $vars = array(), $default = null) {
    global $_G;
    list($path, $file) = explode('/', $file);
    if (!$file) {
	$file = $path;
	$path = '';
    }

    if ($path != 'plugin') {
	$key = $path == '' ? $file : $path . '_' . $file;
	if (!isset($_G['lang'][$key])) {
	    include DISCUZ_ROOT . './source/language/' . ($path == '' ? '' : $path . '/') . 'lang_' . $file . '.php';
	    $_G['lang'][$key] = $lang;
	}
	if (defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
	    include DISCUZ_ROOT . './source/language/mobile/lang_template.php';
	    $_G['lang'][$key] = array_merge($_G['lang'][$key], $lang);
	}
	$returnvalue = &$_G['lang'];
    } else {
	if (empty($_G['config']['plugindeveloper'])) {
	    loadcache('pluginlanguage_script');
	} elseif (!isset($_G['cache']['pluginlanguage_script'][$file]) && preg_match("/^[a-z]+[a-z0-9_]*$/i", $file)) {
	    if (@include(DISCUZ_ROOT . './data/plugindata/' . $file . '.lang.php')) {
		$_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
	    } else {
		loadcache('pluginlanguage_script');
	    }
	}
	$returnvalue = & $_G['cache']['pluginlanguage_script'];
	$key = &$file;
    }
    $return = $langvar !== null ? (isset($returnvalue[$key][$langvar]) ? $returnvalue[$key][$langvar] : null) : $returnvalue[$key];
    $return = $return === null ? ($default !== null ? $default : $langvar) : $return;
    $searchs = $replaces = array();
    if ($vars && is_array($vars)) {
	foreach ($vars as $k => $v) {
	    $searchs[] = '{' . $k . '}';
	    $replaces[] = $v;
	}
    }
    if (is_string($return) && strpos($return, '{_G/') !== false) {
	preg_match_all('/\{_G\/(.+?)\}/', $return, $gvar);
	foreach ($gvar[0] as $k => $v) {
	    $searchs[] = $v;
	    $replaces[] = getglobal($gvar[1][$k]);
	}
    }
    $return = str_replace($searchs, $replaces, $return);
    return $return;
}


function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $cachefile, $tpldir, $file) {
    static $tplrefresh, $timestamp;
    if ($tplrefresh === null) {
	$tplrefresh = getglobal('config/output/tplrefresh');
	$timestamp = getglobal('timestamp');
    }

    if (empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($timestamp % $tplrefresh))) {
	if (empty($timecompare) || @filemtime(DISCUZ_ROOT . $subtpl) > $timecompare) {
	    require_once DISCUZ_ROOT . '/source/class/class_template.php';
	    $template = new template();
	    $template->parse_template($maintpl, $templateid, $tpldir, $file, $cachefile);
	    return TRUE;
	}
    }
    return FALSE;
}

function template($file, $templateid = 0, $tpldir = '', $gettplfile = 0) {
    global $_G;
    $tpldir = defined('TPLDIR') ? TPLDIR : '';  
    $templateid = $templateid ? $templateid : (defined('TEMPLATEID') ? TEMPLATEID : '');
    $file .=!empty($_G['inajax']) && ($file == 'common/header' || $file == 'common/footer') ? '_ajax' : '';
    $tplfile = ($tpldir ? $tpldir . '/' : './template/') . $file . '.htm';
    $filebak = $file;
    $file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_' . $_G['basescript'] . '_' . CURMODULE;
    $cachefile = './data/template/' . str_replace('/', '_', $file) . '.tpl.php';
    //重置为默认模板
    if ($templateid != 1 && !file_exists(DISCUZ_ROOT . $tplfile)) {
	$tplfile = './template/default/' . $filebak . '.htm';
    }
    if ($gettplfile) {
	return $tplfile;
    }
    checktplrefresh($tplfile, $tplfile, @filemtime(DISCUZ_ROOT . $cachefile), $templateid, $cachefile, $tpldir, $file);
    return DISCUZ_ROOT . $cachefile;
}
function modauthkey($id) {
    global $_G;
    return md5($_G['username'] . $_G['uid'] . $_G['authkey'] . substr(TIMESTAMP, 0, -7) . $id);
}

function loaducenter() {

}
/*
 * 重写uc_user_login ,摒弃ucenter
 * 20120424
 */
function uc_user_login($username, $password, $isuid = 0, $checkques = 0, $questionid = '', $answer = '') {
    if ($isuid == 1) {
	$user = get_user_by_uid($username);
    } elseif ($isuid == 2) {
	$user = get_user_by_email($username);
    } else {
	$user = get_user_by_username($username);
    }
    $passwordmd5 = preg_match('/^\w{32}$/', $password) ? $password : md5($password);

    if (empty($user)) {
	$status = -1;
    } elseif ($user['password'] != $passwordmd5) {
	$status = -2;
    } elseif ($checkques && $user['secques'] != '' && $user['secques'] != quescrypt($questionid, $answer)) {
	$status = -3;
    } else {
	$status = $user['uid'];
    }
    return array($status, $user['username'], $password, $user['email'], 0);
}

function get_user_by_uid($uid) {
    $arr = DB::fetch_first("SELECT * FROM " . DB::table('common_member') . " WHERE uid='$uid'");
    return $arr;
}

function get_user_by_username($username) {
    $arr = DB::fetch_first("SELECT * FROM " . DB::table('common_member') . " WHERE  username='$username'");
    return $arr;
}

function get_user_by_email($email) {
    $arr = DB::fetch_first("SELECT * FROM " . DB::table('common_member') . " WHERE  email='$email'");
    return $arr;
}

function loadcache($cachenames, $force = false) {
    global $_G;
    static $loadedcache = array();
    $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
    $caches = array();
    foreach ($cachenames as $k) {
	if (!isset($loadedcache[$k]) || $force) {
	    $caches[] = $k;
	    $loadedcache[$k] = true;
	}
    }

    if (!empty($caches)) {
	$cachedata = cachedata($caches);
	foreach ($cachedata as $cname => $data) {
	    if ($cname == 'setting') {
		$_G['setting'] = $data;
	    } elseif (strpos($cname, 'usergroup_' . $_G['groupid']) !== false) {
		$_G['cache'][$cname] = $_G['group'] = $data;
	    } elseif ($cname == 'style_default') {
		$_G['cache'][$cname] = $_G['style'] = $data;
	    } elseif ($cname == 'grouplevels') {
		$_G['grouplevels'] = $data;
	    } else {
		$_G['cache'][$cname] = $data;
	    }
	}
    }
    return true;
}

function cachedata($cachenames) {
    global $_G;
    static $isfilecache, $allowmem;

    if (!isset($isfilecache)) {
	$isfilecache = getglobal('config/cache/type') == 'file';
	$allowmem = memory('check');
    }

    $data = array();
    $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
    if ($allowmem) {
	$newarray = array();
	foreach ($cachenames as $name) {
	    $data[$name] = memory('get', $name);
	    if ($data[$name] === null) {
		$data[$name] = null;
		$newarray[] = $name;
	    }
	}
	if (empty($newarray)) {
	    return $data;
	} else {
	    $cachenames = $newarray;
	}
    }

    if ($isfilecache) {
	$lostcaches = array();
	foreach ($cachenames as $cachename) {
	    if (!@include_once(DISCUZ_ROOT . './data/cache/cache_' . $cachename . '.php')) {
		$lostcaches[] = $cachename;
	    }
	}
	if (!$lostcaches) {
	    return $data;
	}
	$cachenames = $lostcaches;
	unset($lostcaches);
    }
    $query = DB::query("SELECT * FROM " . DB::table('common_syscache') . " WHERE cname IN ('" . implode("','", $cachenames) . "')");
    while ($syscache = DB::fetch($query)) {
	$data[$syscache['cname']] = $syscache['ctype'] ? unserialize($syscache['data']) : $syscache['data'];
	$allowmem && (memory('set', $syscache['cname'], $data[$syscache['cname']]));
	if ($isfilecache) {
	    $cachedata = '$data[\'' . $syscache['cname'] . '\'] = ' . var_export($data[$syscache['cname']], true) . ";\n\n";
	    if ($fp = @fopen(DISCUZ_ROOT . './data/cache/cache_' . $syscache['cname'] . '.php', 'wb')) {
		fwrite($fp, "<?php\n//Discuz! cache file, DO NOT modify me!\n//Identify: " . md5($syscache['cname'] . $cachedata . $_G['config']['security']['authkey']) . "\n\n$cachedata?>");
		fclose($fp);
	    }
	}
    }

    foreach ($cachenames as $name) {
	if ($data[$name] === null) {
	    $data[$name] = null;
	    $allowmem && (memory('set', $name, array()));
	}
    }

    return $data;
}

function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
    global $_G;
    $format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
    static $dformat, $tformat, $dtformat, $offset, $lang;
    if ($dformat === null) {
	$dformat = getglobal('setting/dateformat');
	$tformat = getglobal('setting/timeformat');
	$dtformat = $dformat . ' ' . $tformat;
	$offset = getglobal('member/timeoffset');
	$lang = lang('core', 'date');
    }
    $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
    $timestamp += $timeoffset * 3600;
    $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
    if ($format == 'u') {
	$todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
	$s = gmdate(!$uformat ? str_replace(":i", ":i:s", $dtformat) : $uformat, $timestamp);
	$time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
	if ($timestamp >= $todaytimestamp) {
	    if ($time > 3600) {
		return '<span title="' . $s . '">' . intval($time / 3600) . '&nbsp;' . $lang['hour'] . $lang['before'] . '</span>';
	    } elseif ($time > 1800) {
		return '<span title="' . $s . '">' . $lang['half'] . $lang['hour'] . $lang['before'] . '</span>';
	    } elseif ($time > 60) {
		return '<span title="' . $s . '">' . intval($time / 60) . '&nbsp;' . $lang['min'] . $lang['before'] . '</span>';
	    } elseif ($time > 0) {
		return '<span title="' . $s . '">' . $time . '&nbsp;' . $lang['sec'] . $lang['before'] . '</span>';
	    } elseif ($time == 0) {
		return '<span title="' . $s . '">' . $lang['now'] . '</span>';
	    } else {
		return $s;
	    }
	} elseif (($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
	    if ($days == 0) {
		return '<span title="' . $s . '">' . $lang['yday'] . '&nbsp;' . gmdate($tformat, $timestamp) . '</span>';
	    } elseif ($days == 1) {
		return '<span title="' . $s . '">' . $lang['byday'] . '&nbsp;' . gmdate($tformat, $timestamp) . '</span>';
	    } else {
		return '<span title="' . $s . '">' . ($days + 1) . '&nbsp;' . $lang['day'] . $lang['before'] . '</span>';
	    }
	} else {
	    return $s;
	}
    } else {
	return gmdate($format, $timestamp);
    }
}

function dmktime($date) {
    if (strpos($date, '-')) {
	$time = explode('-', $date);
	return mktime(0, 0, 0, $time[1], $time[2], $time[0]);
    }
    return 0;
}

function save_syscache($cachename, $data) {
    static $isfilecache, $allowmem;
    if (!isset($isfilecache)) {
	$isfilecache = getglobal('config/cache/type') == 'file';
	$allowmem = memory('check');
    }

    if (is_array($data)) {
	$ctype = 1;
	$data = addslashes(serialize($data));
    } else {
	$ctype = 0;
    }

    DB::query("REPLACE INTO " . DB::table('common_syscache') . " (cname, ctype, dateline, data) VALUES ('$cachename', '$ctype', '" . TIMESTAMP . "', '$data')");

    $allowmem && memory('rm', $cachename);
    $isfilecache && @unlink(DISCUZ_ROOT . './data/cache/cache_' . $cachename . '.php');
}

function dimplode($array) {
    if (!empty($array)) {
	return "'" . implode("','", is_array($array) ? $array : array($array)) . "'";
    } else {
	return 0;
    }
}

function libfile($libname, $folder = '') {
    $libpath = DISCUZ_ROOT . '/source/' . $folder;
    if (strstr($libname, '/')) {
	list($pre, $name) = explode('/', $libname);
	return realpath("{$libpath}/{$pre}/{$pre}_{$name}.php");
    } else {
	return realpath("{$libpath}/{$libname}.php");
    }
}

function dstrlen($str) {
    if (strtolower(CHARSET) != 'utf-8') {
	return strlen($str);
    }
    $count = 0;
    for ($i = 0; $i < strlen($str); $i++) {
	$value = ord($str[$i]);
	if ($value > 127) {
	    $count++;
	    if ($value >= 192 && $value <= 223)
		$i++;
	    elseif ($value >= 224 && $value <= 239)
		$i = $i + 2;
	    elseif ($value >= 240 && $value <= 247)
		$i = $i + 3;
	}
	$count++;
    }
    return $count;
}

function cutstr($string, $length, $dot = ' ...') {
    if (strlen($string) <= $length) {
	return $string;
    }

    $pre = chr(1);
    $end = chr(1);
    $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), $string);

    $strcut = '';
    if (strtolower(CHARSET) == 'utf-8') {

	$n = $tn = $noc = 0;
	while ($n < strlen($string)) {

	    $t = ord($string[$n]);
	    if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
		$tn = 1;
		$n++;
		$noc++;
	    } elseif (194 <= $t && $t <= 223) {
		$tn = 2;
		$n += 2;
		$noc += 2;
	    } elseif (224 <= $t && $t <= 239) {
		$tn = 3;
		$n += 3;
		$noc += 2;
	    } elseif (240 <= $t && $t <= 247) {
		$tn = 4;
		$n += 4;
		$noc += 2;
	    } elseif (248 <= $t && $t <= 251) {
		$tn = 5;
		$n += 5;
		$noc += 2;
	    } elseif ($t == 252 || $t == 253) {
		$tn = 6;
		$n += 6;
		$noc += 2;
	    } else {
		$n++;
	    }

	    if ($noc >= $length) {
		break;
	    }
	}
	if ($noc > $length) {
	    $n -= $tn;
	}

	$strcut = substr($string, 0, $n);
    } else {
	for ($i = 0; $i < $length; $i++) {
	    $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
	}
    }

    $strcut = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

    $pos = strrpos($strcut, chr(1));
    if ($pos !== false) {
	$strcut = substr($strcut, 0, $pos);
    }
    return $strcut . $dot;
}

function dstripslashes($string) {
    if (empty($string))
	return $string;
    if (is_array($string)) {
	foreach ($string as $key => $val) {
	    $string[$key] = dstripslashes($val);
	}
    } else {
	$string = stripslashes($string);
    }
    return $string;
}

function aidencode($aid, $type = 0, $tid = 0) {
    global $_G;
    $s = !$type ? $aid . '|' . substr(md5($aid . md5($_G['config']['security']['authkey']) . TIMESTAMP . $_G['uid']), 0, 8) . '|' . TIMESTAMP . '|' . $_G['uid'] . '|' . $tid : $aid . '|' . md5($aid . md5($_G['config']['security']['authkey']) . TIMESTAMP) . '|' . TIMESTAMP;
    return rawurlencode(base64_encode($s));
}


function output() {

    global $_G;


    if (defined('DISCUZ_OUTPUTED')) {
	return;
    } else {
	define('DISCUZ_OUTPUTED', 1);
    }

    if (!empty($_G['blockupdate'])) {
	block_updatecache($_G['blockupdate']['bid']);
    }

    if (defined('IN_MOBILE')) {
	mobileoutput();
    }
    $havedomain = implode('', $_G['setting']['domain']['app']);
    if ($_G['setting']['rewritestatus'] || !empty($havedomain)) {
	$content = ob_get_contents();
	$content = output_replace($content);


	ob_end_clean();
	$_G['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();

	echo $content;
    }
    if ($_G['setting']['ftp']['connid']) {
	@ftp_close($_G['setting']['ftp']['connid']);
    }
    $_G['setting']['ftp'] = array();

    if (defined('CACHE_FILE') && CACHE_FILE && !defined('CACHE_FORBIDDEN') && !defined('IN_MOBILE')) {
	if (diskfreespace(DISCUZ_ROOT . './' . $_G['setting']['cachethreaddir']) > 1000000) {
	    if ($fp = @fopen(CACHE_FILE, 'w')) {
		flock($fp, LOCK_EX);
		fwrite($fp, empty($content) ? ob_get_contents() : $content);
	    }
	    @fclose($fp);
	    chmod(CACHE_FILE, 0777);
	}
    }

    if (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include(libfile('function/debug'))) {
	function_exists('debugmessage') && debugmessage();
    }
}

function output_replace($content) {
    global $_G;
    if (defined('IN_MODCP') || defined('IN_ADMINCP'))
	return $content;
    if (!empty($_G['setting']['output']['str']['search'])) {
	if (empty($_G['setting']['domain']['app']['default'])) {
	    $_G['setting']['output']['str']['replace'] = str_replace('{CURHOST}', $_G['siteurl'], $_G['setting']['output']['str']['replace']);
	}
	$content = str_replace($_G['setting']['output']['str']['search'], $_G['setting']['output']['str']['replace'], $content);
    }
    if (!empty($_G['setting']['output']['preg']['search'])) {
	if (empty($_G['setting']['domain']['app']['default'])) {
	    $_G['setting']['output']['preg']['search'] = str_replace('\{CURHOST\}', preg_quote($_G['siteurl'], '/'), $_G['setting']['output']['preg']['search']);
	    $_G['setting']['output']['preg']['replace'] = str_replace('{CURHOST}', $_G['siteurl'], $_G['setting']['output']['preg']['replace']);
	}

	$content = preg_replace($_G['setting']['output']['preg']['search'], $_G['setting']['output']['preg']['replace'], $content);
    }

    return $content;
}

function output_ajax() {
    global $_G;
    $s = ob_get_contents();
    ob_end_clean();
    $s = preg_replace("/([\\x01-\\x08\\x0b-\\x0c\\x0e-\\x1f])+/", ' ', $s);
    $s = str_replace(array(chr(0), ']]>'), array(' ', ']]&gt;'), $s);
    if (defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include(libfile('function/debug'))) {
	function_exists('debugmessage') && $s .= debugmessage(1);
    }
    $havedomain = implode('', $_G['setting']['domain']['app']);
    if ($_G['setting']['rewritestatus'] || !empty($havedomain)) {
	$s = output_replace($s);
    }
    return $s;
}

function runhooks() {
    if (!defined('HOOKTYPE')) {
	define('HOOKTYPE', !defined('IN_MOBILE') ? 'hookscript' : 'hookscriptmobile');
    }
    if (defined('CURMODULE')) {
	global $_G;
	if ($_G['setting']['plugins'][HOOKTYPE . '_common']) {
	    hookscript('common', 'global', 'funcs', array(), 'common');
	}
	hookscript(CURMODULE, $_G['basescript']);
    }
}

function hookscript($script, $hscript, $type = 'funcs', $param = array(), $func = '') {
    global $_G;
    static $pluginclasses;
    if ($hscript == 'home') {
	if ($script != 'spacecp') {
	    $script = 'space_' . (!empty($_G['gp_do']) ? $_G['gp_do'] : (!empty($_GET['do']) ? $_GET['do'] : ''));
	} else {
	    $script .=!empty($_G['gp_ac']) ? '_' . $_G['gp_ac'] : (!empty($_GET['ac']) ? '_' . $_GET['ac'] : '');
	}
    }
    if (!isset($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
	return;
    }
    if (!isset($_G['cache']['plugin'])) {
	loadcache('plugin');
    }
    foreach ((array) $_G['setting'][HOOKTYPE][$hscript][$script]['module'] as $identifier => $include) {
	$hooksadminid[$identifier] = !$_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] || ($_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] && $_G['adminid'] > 0 && $_G['setting']['hookscript'][$hscript][$script]['adminid'][$identifier] >= $_G['adminid']);
	if ($hooksadminid[$identifier]) {
	    @include_once DISCUZ_ROOT . './source/plugin/' . $include . '.class.php';
	}
    }
    if (@is_array($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
	$_G['inhookscript'] = true;
	$funcs = !$func ? $_G['setting'][HOOKTYPE][$hscript][$script][$type] : array($func => $_G['setting'][HOOKTYPE][$hscript][$script][$type][$func]);
	foreach ($funcs as $hookkey => $hookfuncs) {
	    foreach ($hookfuncs as $hookfunc) {
		if ($hooksadminid[$hookfunc[0]]) {
		    $classkey = (HOOKTYPE != 'hookscriptmobile' ? '' : 'mobile') . 'plugin_' . ($hookfunc[0] . ($hscript != 'global' ? '_' . $hscript : ''));
		    if (!class_exists($classkey)) {
			continue;
		    }
		    if (!isset($pluginclasses[$classkey])) {
			$pluginclasses[$classkey] = new $classkey;
		    }
		    if (!method_exists($pluginclasses[$classkey], $hookfunc[1])) {
			continue;
		    }
		    $return = $pluginclasses[$classkey]->$hookfunc[1]($param);

		    if (is_array($return)) {
			if (!isset($_G['setting']['pluginhooks'][$hookkey]) || is_array($_G['setting']['pluginhooks'][$hookkey])) {
			    foreach ($return as $k => $v) {
				$_G['setting']['pluginhooks'][$hookkey][$k] .= $v;
			    }
			}
		    } else {
			if (!is_array($_G['setting']['pluginhooks'][$hookkey])) {
			    $_G['setting']['pluginhooks'][$hookkey] .= $return;
			} else {
			    foreach ($_G['setting']['pluginhooks'][$hookkey] as $k => $v) {
				$_G['setting']['pluginhooks'][$hookkey][$k] .= $return;
			    }
			}
		    }
		}
	    }
	}
    }
    $_G['inhookscript'] = false;
}

function hookscriptoutput($tplfile) {
    global $_G;
    if (!empty($_G['hookscriptoutput'])) {
	return;
    }
    if (!empty($_G['gp_mobiledata'])) {
	require_once libfile('class/mobiledata');
	$mobiledata = new mobiledata();
	if ($mobiledata->validator()) {
	    $mobiledata->outputvariables();
	}
    }
    hookscript('global', 'global');
    if (defined('CURMODULE')) {
	$param = array('template' => $tplfile, 'message' => $_G['hookscriptmessage'], 'values' => $_G['hookscriptvalues']);
	hookscript(CURMODULE, $_G['basescript'], 'outputfuncs', $param);
    }
    $_G['hookscriptoutput'] = true;
}

function pluginmodule($pluginid, $type) {
    global $_G;
    if (!isset($_G['cache']['plugin'])) {
	loadcache('plugin');
    }
    list($identifier, $module) = explode(':', $pluginid);
    if (!is_array($_G['setting']['plugins'][$type]) || !array_key_exists($pluginid, $_G['setting']['plugins'][$type])) {
	showmessage('plugin_nonexistence');
    }
    if (!empty($_G['setting']['plugins'][$type][$pluginid]['url'])) {
	dheader('location: ' . $_G['setting']['plugins'][$type][$pluginid]['url']);
    }
    $directory = $_G['setting']['plugins'][$type][$pluginid]['directory'];
    if (empty($identifier) || !preg_match("/^[a-z]+[a-z0-9_]*\/$/i", $directory) || !preg_match("/^[a-z0-9_\-]+$/i", $module)) {
	showmessage('undefined_action');
    }
    if (@!file_exists(DISCUZ_ROOT . ($modfile = './source/plugin/' . $directory . $module . '.inc.php'))) {
	showmessage('plugin_module_nonexistence', '', array('mod' => $modfile));
    }
    return DISCUZ_ROOT . $modfile;
}




function debug($var = null, $vardump = false) {
    echo '<pre>';
    if ($var === null) {
	print_r($GLOBALS);
    } else {
	if ($vardump) {
	    var_dump($var);
	} else {
	    print_r($var);
	}
    }
    exit();
}

function debuginfo() {
    global $_G;
    if (getglobal('setting/debug')) {
	$db = & DB::object();
	$_G['debuginfo'] = array(
	    'time' => number_format((dmicrotime() - $_G['starttime']), 6),
	    'queries' => $db->querynum,
	    'memory' => ucwords($_G['memory'])
	);
	if ($db->slaveid) {
	    $_G['debuginfo']['queries'] = 'Total ' . $db->querynum . ', Slave ' . $db->slavequery;
	}
	return TRUE;
    } else {
	return FALSE;
    }
}

function getfocus_rand($module) {
    global $_G;

    if (empty($_G['setting']['focus']) || !array_key_exists($module, $_G['setting']['focus'])) {
	return null;
    }
    do {
	$focusid = $_G['setting']['focus'][$module][array_rand($_G['setting']['focus'][$module])];
	if (!empty($_G['cookie']['nofocus_' . $focusid])) {
	    unset($_G['setting']['focus'][$module][$focusid]);
	    $continue = 1;
	} else {
	    $continue = 0;
	}
    } while (!empty($_G['setting']['focus'][$module]) && $continue);
    if (!$_G['setting']['focus'][$module]) {
	return null;
    }
    loadcache('focus');
    if (empty($_G['cache']['focus']['data']) || !is_array($_G['cache']['focus']['data'])) {
	return null;
    }
    return $focusid;
}

function check_seccode($value, $idhash) {
    global $_G;
    if (!$_G['setting']['seccodestatus']) {
	return true;
    }
    if (!isset($_G['cookie']['seccode' . $idhash])) {
	return false;
    }
    list($checkvalue, $checktime, $checkidhash, $checkformhash) = explode("\t", authcode($_G['cookie']['seccode' . $idhash], 'DECODE', $_G['config']['security']['authkey']));
    return $checkvalue == strtoupper($value) && TIMESTAMP - 180 > $checktime && $checkidhash == $idhash && FORMHASH == $checkformhash;
}

function check_secqaa($value, $idhash) {
    global $_G;
    if (!$_G['setting']['secqaa']) {
	return true;
    }
    if (!isset($_G['cookie']['secqaa' . $idhash])) {
	return false;
    }
    loadcache('secqaa');
    list($checkvalue, $checktime, $checkidhash, $checkformhash) = explode("\t", authcode($_G['cookie']['secqaa' . $idhash], 'DECODE', $_G['config']['security']['authkey']));
    return $checkvalue == md5($value) && TIMESTAMP - 180 > $checktime && $checkidhash == $idhash && FORMHASH == $checkformhash;
}

function showmessage($message, $url_forward = '', $values = array(), $extraparam = array(), $custom = 0) {
    require_once libfile('function/message');
    return dshowmessage($message, $url_forward, $values, $extraparam, $custom);
}

function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {
    if (!getgpc($var)) {
	return FALSE;
    } else {
	global $_G;
	if (!empty($_G['gp_mobiledata'])) {
	    require_once libfile('class/mobiledata');
	    $mobiledata = new mobiledata();
	    if ($mobiledata->validator()) {
		return TRUE;
	    }
	}
	if ($allowget || ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_G['gp_formhash']) && $_G['gp_formhash'] == formhash() && empty($_SERVER['HTTP_X_FLASH_VERSION']) && (empty($_SERVER['HTTP_REFERER']) ||
		preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
	    if (checkperm('seccode')) {
		if ($secqaacheck && !check_secqaa($_G['gp_secanswer'], $_G['gp_sechash'])) {
		    showmessage('submit_secqaa_invalid');
		}
		if ($seccodecheck && !check_seccode($_G['gp_seccodeverify'], $_G['gp_sechash'])) {
		    showmessage('submit_seccode_invalid');
		}
	    }
	    return TRUE;
	} else {
	    showmessage('submit_invalid');
	}
    }
}

function multi($num, $perpage, $curpage, $mpurl, $maxpages = 0, $page = 10, $autogoto = FALSE, $simple = FALSE) {
    global $_G;
    $ajaxtarget = !empty($_G['gp_ajaxtarget']) ? " ajaxtarget=\"" . htmlspecialchars($_G['gp_ajaxtarget']) . "\" " : '';

    $a_name = '';
    if (strpos($mpurl, '#') !== FALSE) {
	$a_strs = explode('#', $mpurl);
	$mpurl = $a_strs[0];
	$a_name = '#' . $a_strs[1];
    }

    if (defined('IN_ADMINCP')) {
	$shownum = $showkbd = TRUE;
	$lang['prev'] = '&lsaquo;&lsaquo;';
	$lang['next'] = '&rsaquo;&rsaquo;';
    } else {
	$shownum = $showkbd = FALSE;
	if (defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
	    $lang['prev'] = lang('core', 'prevpage');
	    $lang['next'] = lang('core', 'nextpage');
	} else {
	    $lang['prev'] = '&nbsp;&nbsp;';
	    $lang['next'] = lang('core', 'nextpage');
	}
    }
    if (defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
	$dot = '..';
	$page = intval($page) < 10 && intval($page) > 0 ? $page : 4;
    } else {
	$dot = '...';
    }
    $multipage = '';
    $mpurl .= strpos($mpurl, '?') !== FALSE ? '&amp;' : '?';

    $realpages = 1;
    $_G['page_next'] = 0;
    $page -= strlen($curpage) - 1;
    if ($page <= 0) {
	$page = 1;
    }
    if ($num > $perpage) {

	$offset = floor($page * 0.5);

	$realpages = @ceil($num / $perpage);
	$pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;

	if ($page > $pages) {
	    $from = 1;
	    $to = $pages;
	} else {
	    $from = $curpage - $offset;
	    $to = $from + $page - 1;
	    if ($from < 1) {
		$to = $curpage + 1 - $from;
		$from = 1;
		if ($to - $from < $page) {
		    $to = $page;
		}
	    } elseif ($to > $pages) {
		$from = $pages - $page + 1;
		$to = $pages;
	    }
	}
	$_G['page_next'] = $to;
	$multipage = ($curpage - $offset > 1 && $pages > $page ? '<a href="' . $mpurl . 'page=1' . $a_name . '" class="first"' . $ajaxtarget . '>1 ' . $dot . '</a>' : '') .
		($curpage > 1 && !$simple ? '<a href="' . $mpurl . 'page=' . ($curpage - 1) . $a_name . '" class="prev"' . $ajaxtarget . '>' . $lang['prev'] . '</a>' : '');
	for ($i = $from; $i <= $to; $i++) {
	    $multipage .= $i == $curpage ? '<strong>' . $i . '</strong>' :
		    '<a href="' . $mpurl . 'page=' . $i . ($ajaxtarget && $i == $pages && $autogoto ? '#' : $a_name) . '"' . $ajaxtarget . '>' . $i . '</a>';
	}
	$multipage .= ($to < $pages ? '<a href="' . $mpurl . 'page=' . $pages . $a_name . '" class="last"' . $ajaxtarget . '>' . $dot . ' ' . $realpages . '</a>' : '') .
		($curpage < $pages && !$simple ? '<a href="' . $mpurl . 'page=' . ($curpage + 1) . $a_name . '" class="nxt"' . $ajaxtarget . '>' . $lang['next'] . '</a>' : '') .
		($showkbd && !$simple && $pages > $page && !$ajaxtarget ? '<kbd><input type="text" name="custompage" size="3" onkeydown="if(event.keyCode==13) {window.location=\'' . $mpurl . 'page=\'+this.value; doane(event);}" /></kbd>' : '');

	$multipage = $multipage ? '<div class="pg">' . ($shownum && !$simple ? '<em>&nbsp;' . $num . '&nbsp;</em>' : '') . $multipage . '</div>' : '';
    }
    $maxpage = $realpages;
    return $multipage;
}

function simplepage($num, $perpage, $curpage, $mpurl) {
    $return = '';
    $lang['next'] = lang('core', 'nextpage');
    $lang['prev'] = lang('core', 'prevpage');
    $next = $num == $perpage ? '<a href="' . $mpurl . '&amp;page=' . ($curpage + 1) . '" class="nxt">' . $lang['next'] . '</a>' : '';
    $prev = $curpage > 1 ? '<span class="pgb"><a href="' . $mpurl . '&amp;page=' . ($curpage - 1) . '">' . $lang['prev'] . '</a></span>' : '';
    if ($next || $prev) {
	$return = '<div class="pg">' . $prev . $next . '</div>';
    }
    return $return;
}

function runlog($file, $message, $halt = 0) {
    global $_G;

    $nowurl = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : ($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
    $log = dgmdate($_G['timestamp'], 'Y-m-d H:i:s') . "\t" . $_G['clientip'] . "\t$_G[uid]\t{$nowurl}\t" . str_replace(array("\r", "\n"), array(' ', ' '), trim($message)) . "\n";
    writelog($file, $log);
    if ($halt) {
	exit();
    }
}

function stripsearchkey($string) {
    $string = trim($string);
    $string = str_replace('*', '%', addcslashes($string, '%_'));
    $string = str_replace('_', '\_', $string);
    return $string;
}

function dmkdir($dir, $mode = 0777, $makeindex = TRUE) {
    if (!is_dir($dir)) {
	dmkdir(dirname($dir));
	@mkdir($dir, $mode);
	if (!empty($makeindex)) {
	    @touch($dir . '/index.html');
	    @chmod($dir . '/index.html', 0777);
	}
    }
    return true;
}

function dreferer($default = '') {
    global $_G;

    $default = empty($default) ? $GLOBALS['_t_curapp'] : '';
    $_G['referer'] = !empty($_G['gp_referer']) ? $_G['gp_referer'] : $_SERVER['HTTP_REFERER'];
    $_G['referer'] = substr($_G['referer'], -1) == '?' ? substr($_G['referer'], 0, -1) : $_G['referer'];

    if (strpos($_G['referer'], 'member.php?mod=logging')) {
	$_G['referer'] = $default;
    }
    $_G['referer'] = htmlspecialchars($_G['referer'], ENT_QUOTES);
    $_G['referer'] = str_replace('&amp;', '&', $_G['referer']);
    $reurl = parse_url($_G['referer']);
    if (!empty($reurl['host']) && !in_array($reurl['host'], array($_SERVER['HTTP_HOST'], 'www.' . $_SERVER['HTTP_HOST'])) && !in_array($_SERVER['HTTP_HOST'], array($reurl['host'], 'www.' . $reurl['host']))) {
	if (!in_array($reurl['host'], $_G['setting']['domain']['app']) && !isset($_G['setting']['domain']['list'][$reurl['host']])) {
	    $domainroot = substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.') + 1);
	    if (empty($_G['setting']['domain']['root']) || (is_array($_G['setting']['domain']['root']) && !in_array($domainroot, $_G['setting']['domain']['root']))) {
		$_G['referer'] = $_G['setting']['domain']['defaultindex'] ? $_G['setting']['domain']['defaultindex'] : 'index.php';
	    }
	}
    } elseif (empty($reurl['host'])) {
	$_G['referer'] = $_G['siteurl'] . './' . $_G['referer'];
    }
    return strip_tags($_G['referer']);
}

function ftpcmd($cmd, $arg1 = '') {
    static $ftp;
    $ftpon = getglobal('setting/ftp/on');
    if (!$ftpon) {
	return $cmd == 'error' ? -101 : 0;
    } elseif ($ftp == null) {
	require_once libfile('class/ftp');
	$ftp = & discuz_ftp::instance();
    }
    if (!$ftp->enabled) {
	return $ftp->error();
    } elseif ($ftp->enabled && !$ftp->connectid) {
	$ftp->connect();
    }
    switch ($cmd) {
	case 'upload' : return $ftp->upload(getglobal('setting/attachdir') . '/' . $arg1, $arg1);
	    break;
	case 'delete' : return $ftp->ftp_delete($arg1);
	    break;
	case 'close' : return $ftp->ftp_close();
	    break;
	case 'error' : return $ftp->error();
	    break;
	case 'object' : return $ftp;
	    break;
	default : return false;
    }
}

function diconv($str, $in_charset, $out_charset = CHARSET, $ForceTable = FALSE) {
    global $_G;

    $in_charset = strtoupper($in_charset);
    $out_charset = strtoupper($out_charset);

    if (empty($str) || $in_charset == $out_charset) {
	return $str;
    }

    $out = '';

    if (!$ForceTable) {
	if (function_exists('iconv')) {
	    $out = iconv($in_charset, $out_charset . '//IGNORE', $str);
	} elseif (function_exists('mb_convert_encoding')) {
	    $out = mb_convert_encoding($str, $out_charset, $in_charset);
	}
    }

    if ($out == '') {
	require_once libfile('class/chinese');
	$chinese = new Chinese($in_charset, $out_charset, true);
	$out = $chinese->Convert($str);
    }

    return $out;
}

function renum($array) {
    $newnums = $nums = array();
    foreach ($array as $id => $num) {
	$newnums[$num][] = $id;
	$nums[$num] = $num;
    }
    return array($nums, $newnums);
}


function sizecount($size) {
    if ($size >= 1073741824) {
	$size = round($size / 1073741824 * 100) / 100 . ' GB';
    } elseif ($size >= 1048576) {
	$size = round($size / 1048576 * 100) / 100 . ' MB';
    } elseif ($size >= 1024) {
	$size = round($size / 1024 * 100) / 100 . ' KB';
    } else {
	$size = $size . ' Bytes';
    }
    return $size;
}

function swapclass($class1, $class2 = '') {
    static $swapc = null;
    $swapc = isset($swapc) && $swapc != $class1 ? $class1 : $class2;
    return $swapc;
}

function writelog($file, $log) {
    global $_G;
    $yearmonth = dgmdate(TIMESTAMP, 'Ym', $_G['setting']['timeoffset']);
    $logdir = DISCUZ_ROOT . './data/log/';
    $logfile = $logdir . $yearmonth . '_' . $file . '.php';
    if (@filesize($logfile) > 2048000) {
	$dir = opendir($logdir);
	$length = strlen($file);
	$maxid = $id = 0;
	while ($entry = readdir($dir)) {
	    if (strpos($entry, $yearmonth . '_' . $file) !== false) {
		$id = intval(substr($entry, $length + 8, -4));
		$id > $maxid && $maxid = $id;
	    }
	}
	closedir($dir);

	$logfilebak = $logdir . $yearmonth . '_' . $file . '_' . ($maxid + 1) . '.php';
	@rename($logfile, $logfilebak);
    }
    if ($fp = @fopen($logfile, 'a')) {
	@flock($fp, 2);
	$log = is_array($log) ? $log : array($log);
	foreach ($log as $tmp) {
	    fwrite($fp, "<?PHP exit;?>\t" . str_replace(array('<?', '?>'), '', $tmp) . "\n");
	}
	fclose($fp);
    }
}

function getstatus($status, $position) {
    $t = $status & pow(2, $position - 1) ? 1 : 0;
    return $t;
}

function setstatus($position, $value, $baseon = null) {
    $t = pow(2, $position - 1);
    if ($value) {
	$t = $baseon | $t;
    } elseif ($baseon !== null) {
	$t = $baseon & ~$t;
    } else {
	$t = ~$t;
    }
    return $t & 0xFFFF;
}

function space_key($uid, $appid = 0) {
    global $_G;

    $siteuniqueid = DB::result_first("SELECT svalue FROM " . DB::table('common_setting') . " WHERE skey='siteuniqueid'");
    return substr(md5($siteuniqueid . '|' . $uid . (empty($appid) ? '' : '|' . $appid)), 8, 16);
}


function memory($cmd, $key = '', $value = '', $ttl = 0) {
    $discuz = & discuz_core::instance();
    if ($cmd == 'check') {
	return $discuz->mem->enable ? $discuz->mem->type : '';
    } elseif ($discuz->mem->enable && in_array($cmd, array('set', 'get', 'rm'))) {
	switch ($cmd) {
	    case 'set': return $discuz->mem->set($key, $value, $ttl);
		break;
	    case 'get': return $discuz->mem->get($key);
		break;
	    case 'rm': return $discuz->mem->rm($key);
		break;
	}
    }
    return null;
}

function ipaccess($ip, $accesslist) {
    return preg_match("/^(" . str_replace(array("\r\n", ' '), array('|', ''), preg_quote($accesslist, '/')) . ")/", $ip);
}

function ipbanned($onlineip) {
    global $_G;

    if ($_G['setting']['ipaccess'] && !ipaccess($onlineip, $_G['setting']['ipaccess'])) {
	return TRUE;
    }

    loadcache('ipbanned');
    if (empty($_G['cache']['ipbanned'])) {
	return FALSE;
    } else {
	if ($_G['cache']['ipbanned']['expiration'] < TIMESTAMP) {
	    require_once libfile('function/cache');
	    updatecache('ipbanned');
	}
	return preg_match("/^(" . $_G['cache']['ipbanned']['regexp'] . ")$/", $onlineip);
    }
}

function getcount($tablename, $condition) {
    if (empty($condition)) {
	$where = '1';
    } elseif (is_array($condition)) {
	$where = DB::implode_field_value($condition, ' AND ');
    } else {
	$where = $condition;
    }
    $ret = intval(DB::result_first("SELECT COUNT(*) AS num FROM " . DB::table($tablename) . " WHERE $where"));
    return $ret;
}

function sysmessage($message) {
    require libfile('function/sysmessage');
    show_system_message($message);
}

if (!function_exists('file_put_contents')) {
    if (!defined('FILE_APPEND'))
	define('FILE_APPEND', 8);

    function file_put_contents($filename, $data, $flag = 0) {
	$return = false;
	if ($fp = @fopen($filename, $flag != FILE_APPEND ? 'w' : 'a')) {
	    if ($flag == LOCK_EX)
		@flock($fp, LOCK_EX);
	    $return = fwrite($fp, is_array($data) ? implode('', $data) : $data);
	    fclose($fp);
	}
	return $return;
    }

}

function checkperm($perm) {
    global $_G;
    return (empty($_G['group'][$perm]) ? '' : $_G['group'][$perm]);
}

function periodscheck($periods, $showmessage = 1) {
    global $_G;

    if (!$_G['group']['disableperiodctrl'] && $_G['setting'][$periods]) {
	$now = dgmdate(TIMESTAMP, 'G.i');
	foreach (explode("\r\n", str_replace(':', '.', $_G['setting'][$periods])) as $period) {
	    list($periodbegin, $periodend) = explode('-', $period);
	    if (($periodbegin > $periodend && ($now >= $periodbegin || $now < $periodend)) || ($periodbegin < $periodend && $now >= $periodbegin && $now < $periodend)) {
		$banperiods = str_replace("\r\n", ', ', $_G['setting'][$periods]);
		if ($showmessage) {
		    showmessage('period_nopermission', NULL, array('banperiods' => $banperiods), array('login' => 1));
		} else {
		    return TRUE;
		}
	    }
	}
    }
    return FALSE;
}


function getexpiration() {
    global $_G;
    $date = getdate($_G['timestamp']);
    return mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']) + 86400;
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val{strlen($val) - 1});
    switch ($last) {
	case 'g': $val *= 1024;
	case 'm': $val *= 1024;
	case 'k': $val *= 1024;
    }
    return $val;
}

function get_url_list($message) {
    $return = array();

    (strpos($message, '[/img]') || strpos($message, '[/flash]')) && $message = preg_replace("/\[img[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]|\[flash[^\]]*\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/is", '', $message);
    if (preg_match_all("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto):\/\/|www\.)([a-z0-9\/\-_+=.~!%@?#%&;:$\\()|]+\s*)/i", $message, $urllist)) {
	foreach ($urllist[0] as $key => $val) {
	    $val = trim($val);
	    $return[0][$key] = $val;
	    if (!preg_match('/^http:\/\//is', $val))
		$val = 'http://' . $val;
	    $tmp = parse_url($val);
	    $return[1][$key] = $tmp['host'];
	    if ($tmp['port']) {
		$return[1][$key] .= ":$tmp[port]";
	    }
	}
    }

    return $return;
}

function iswhitelist($host) {
    global $_G;
    static $iswhitelist = array();

    if (isset($iswhitelist[$host])) {
	return $iswhitelist[$host];
    }
    $hostlen = strlen($host);
    $iswhitelist[$host] = false;
    if (is_array($_G['cache']['domainwhitelist']))
	foreach ($_G['cache']['domainwhitelist'] as $val) {
	    $domainlen = strlen($val);
	    if ($domainlen > $hostlen) {
		continue;
	    }
	    if (substr($host, -$domainlen) == $val) {
		$iswhitelist[$host] = true;
		break;
	    }
	}
    if ($iswhitelist[$host] == false) {
	$iswhitelist[$host] = $host == $_SERVER['HTTP_HOST'];
    }
    return $iswhitelist[$host];
}

if (!function_exists('http_build_query')) {

    function http_build_query($data, $numeric_prefix = '', $arg_separator = '', $prefix = '') {
	$render = array();
	if (empty($arg_separator)) {
	    $arg_separator = ini_get('arg_separator.output');
	    empty($arg_separator) && $arg_separator = '&';
	}
	foreach ((array) $data as $key => $val) {
	    if (is_array($val) || is_object($val)) {
		$_key = empty($prefix) ? "{$key}[%s]" : sprintf($prefix, $key) . "[%s]";
		$_render = http_build_query($val, '', $arg_separator, $_key);
		if (!empty($_render)) {
		    $render[] = $_render;
		}
	    } else {
		if (is_numeric($key) && empty($prefix)) {
		    $render[] = urlencode("{$numeric_prefix}{$key}") . "=" . urlencode($val);
		} else {
		    if (!empty($prefix)) {
			$_key = sprintf($prefix, $key);
			$render[] = urlencode($_key) . "=" . urlencode($val);
		    } else {
			$render[] = urlencode($key) . "=" . urlencode($val);
		    }
		}
	    }
	}
	$render = implode($arg_separator, $render);
	if (empty($render)) {
	    $render = '';
	}
	return $render;
    }

}

function strreplace_strip_split($searchs, $replaces, $str) {
    $searchspace = array('((\s*\-\s*)+)', '((\s*\,\s*)+)', '((\s*\|\s*)+)', '((\s*\t\s*)+)', '((\s*_\s*)+)');
    $replacespace = array('-', ',', '|', ' ', '_');
    return trim(preg_replace($searchspace, $replacespace, str_replace($searchs, $replaces, $str)), ' ,-|_');
}
?>