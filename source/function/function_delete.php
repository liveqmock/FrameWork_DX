<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_delete.php 22847 2011-05-26 00:41:18Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


function deletemember($uids, $delpost = true) {
	if(!$uids) {
		return;
	}
	$uids = dimplode($uids);
	$numdeleted = DB::result_first("SELECT COUNT(*) FROM ".DB::table('common_member')." WHERE uid IN ($uids)");
	foreach(array( 'common_member_status') as $table) {
		DB::delete($table, "uid IN ($uids)");
	}




	foreach(array('common_member') as $table) {
		DB::delete($table, "uid IN ($uids)");
	}

	return $numdeleted;
}




?>