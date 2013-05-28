<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_members.php 24683 2011-10-08 04:15:03Z svn_project_zhangjie $
 */
if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

@set_time_limit(600);
if ($operation != 'export') {
    cpheader();
}
//error_reporting(E_ALL);
require_once libfile('function/delete');

$_G['setting']['memberperpage'] = 20;
$page = max(1, $_G['page']);
$start_limit = ($page - 1) * $_G['setting']['memberperpage'];

if ($operation == 'search') {
    shownav('user', '用户管理');
    showsubmenu('-用户管理');
    $page = intval($_G['gp_page'] ? $_G['gp_page'] : 1);
    $perpage = 20;
    $start_limit = ($page - 1) * $_G['setting']['memberperpage'];
    $conditions = '';
    if ($_G['gp_username']) {
	$conditions .=" AND m.username  LIKE '%" . $_G['gp_username'] . "%'";
    }

    $memberstr = '';
    $query = DB::query("SELECT m.uid,m.adminid,m.regdate,m.username,m.groupid  FROM " . DB::table('common_member') . " m  WHERE 1 $conditions ORDER BY uid  DESC LIMIT " . (($page - 1) * $perpage) . ",{$perpage}");
    while ($member = DB::fetch($query)) {

	$memberstr .= showtablerow('', '', array(
	    "<input class=\"checkbox\" type=\"checkbox\" name=\"uidarray[]\" value=\"$member[uid]\"  /> $member[uid]",
	    $member[username],
	    date('Y-m-d', $member[regdate]),
	    "<a href='admin.php?action=members&operation=edit&uid=$member[uid]'>编辑</a>"
		), TRUE);
    }
    showformheader('members');
    showtableheader();
    showtablerow('', '', array(
	' 用户名：<label><input type="text" name="username" value="' . $_G['gp_username'] . '" /> </label>' .
	' <input type="submit" value="查询" name="getuser" class="btn" />',
    ));
    showtablefooter();

    showtableheader();
    showsubtitle(array('', '用户名', '加入日期', '操作'));
    echo $memberstr;
    $ucdata = DB::fetch_first("SELECT count(uid) ucount FROM " . DB::table('common_member') . " m  WHERE 1 $conditions");
    $membercount = $ucdata['ucount'];
    showhiddenfields(array('page' => $page));

    $multi = multi($membercount, $perpage, $page, ADMINSCRIPT . "?action=members");
    showsubmit('', 'submit', '', '', $multi);
    showtablefooter();

    showtableheader();
    showtablerow('', '', array(
	'<input name="chkall" id="chkall" type="checkbox" class="checkbox"  onclick="checkAll(\'prefix\', this.form, \'uidarray\', \'chkall\')" /> <label for="chkall">' . cplang('select_all') . '</label>' .
	' <input type="submit" value="删除" name="userdel" id="submit_userdel" class="btn"  title="请谨慎，删除后无法恢复，建议选择关闭"/>',
    ));

    showtablefooter();
    showformfooter();
} elseif ($operation == 'edit') {
    if (!submitcheck('updatesubmit', 1)) {
	shownav('user', 'nav_members_add');
	showsubmenu('members_edit');
	showformheader('members&operation=edit');
	$uid = intval($_G['gp_uid']);
	$member = DB::fetch_first("SELECT * FROM " . DB::table('common_member') . " WHERE uid='$uid'");
	showtableheader();
	showhiddenfields(array('uid' => $member[uid]));
	showsetting('username', 'newusername', $member['username'], 'text');
	showsetting('password', 'newpassword', '', 'text');
	showsetting('email', 'newemail', $member['email'], 'text');
	showsubmit('updatesubmit');
	showtablefooter();
	showformfooter();
    } else {
	$newusername = trim($_G['gp_newusername']);
	$newpassword = trim($_G['gp_newpassword']);
	$newemail = trim($_G['gp_newemail']);
	$uid=intval($_G['gp_uid']);
	if (!$newusername || !isset($_G['gp_confirmed']) && !$newpassword || !isset($_G['gp_confirmed']) && !$newemail) {
	    cpmsg('members_add_invalid', '', 'error');
	}

	$data = array(
	    'username' => $newusername,
	    'password' => md5($newpassword),
	    'email' => $newemail,
	);	
	DB::update('common_member', $data,array('uid'=>$uid));
	cpmsg('成员更新成功', '', 'succeed', array('username' => $newusername, 'uid' => $uid));
    }
} elseif ($operation == 'add') {
    if (!submitcheck('addsubmit', 1)) {
	shownav('user', 'nav_members_add');
	showsubmenu('members_add');
	showformheader('members&operation=add');
	showtableheader();
	showsetting('username', 'newusername', '', 'text');
	showsetting('password', 'newpassword', '', 'text');
	showsetting('email', 'newemail', '', 'text');
	showsubmit('addsubmit');
	showtablefooter();
	showformfooter();
    } else {

	$newusername = trim($_G['gp_newusername']);
	$newpassword = trim($_G['gp_newpassword']);
	$newemail = trim($_G['gp_newemail']);

	if (!$newusername || !isset($_G['gp_confirmed']) && !$newpassword || !isset($_G['gp_confirmed']) && !$newemail) {
	    cpmsg('members_add_invalid', '', 'error');
	}

	if (DB::result_first("SELECT count(*) FROM " . DB::table('common_member') . " WHERE username='$newusername'")) {
	    cpmsg('members_add_username_duplicate', '', 'error');
	}

	$data = array(
	    'uid' => $uid,
	    'username' => $newusername,
	    'password' => md5($newpassword),
	    'email' => $newemail,
	    'adminid' => 1,
	    'groupid' => 1,
	    'regdate' => $_G['timestamp'],
	    'credits' => 0,
	);

	DB::insert('common_member', $data);
	$uid = DB::insert_id();
	DB::insert('common_member_status', array('uid' => $uid, 'regip' => 'Manual Acting', 'lastvisit' => $_G['timestamp'], 'lastactivity' => $_G['timestamp']));

	if ($_G['gp_emailnotify']) {
	    if (!function_exists('sendmail')) {
		include libfile('function/mail');
	    }
	    $add_member_subject = lang('email', 'add_member_subject');
	    $add_member_message = lang('email', 'add_member_message', array(
		'newusername' => $newusername,
		'bbname' => $_G['setting']['bbname'],
		'adminusername' => $_G['member']['username'],
		'siteurl' => $_G['siteurl'],
		'newpassword' => $newpassword,
		    ));
	    sendmail("$newusername <$newemail>", $add_member_subject, $add_member_message);
	}
	$newusername = dstripslashes($newusername);
	cpmsg('members_add_succeed', '', 'succeed', array('username' => $newusername, 'uid' => $uid));
    }
}
?>