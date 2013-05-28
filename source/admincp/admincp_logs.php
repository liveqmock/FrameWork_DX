<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_logs.php 20450 2011-02-24 03:24:55Z congyushuai $
 */
if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}
cpheader();

$lpp = empty($_G['gp_lpp']) ? 20 : $_G['gp_lpp'];
$checklpp = array();
$checklpp[$lpp] = 'selected="selected"';

$operation = in_array($operation, array('cp', 'error', 'v')) ? $operation : 'cp';

$logdir = DISCUZ_ROOT . './data/log/';
$logfiles = get_log_files($logdir, $operation . 'log');
$logs = array();
$lastkey = count($logfiles) - 1;
$lastlog = $logfiles[$lastkey];
krsort($logfiles);
if ($logfiles) {
    if (!isset($_G['gp_day']) || strexists($_G['gp_day'], '_')) {
	list($_G['gp_day'], $_G['gp_num']) = explode('_', $_G['gp_day']);
	$logs = file(($_G['gp_day'] ? $logdir . $_G['gp_day'] . '_' . $operation . 'log' . ($_G['gp_num'] ? '_' . $_G['gp_num'] : '') . '.php' : $logdir . $lastlog));
    } else {
	$logs = file($logdir . $operation . 'log_' . $_G['gp_day'] . '.php');
    }
}

$start = ($page - 1) * $lpp;
$logs = array_reverse($logs);

if (empty($_G['gp_keyword'])) {
    $num = count($logs);
    $multipage = multi($num, $lpp, $page, ADMINSCRIPT . "?action=logs&operation=$operation&lpp=$lpp" . (!empty($_G['gp_day']) ? '&day=' . $_GET['day'] : ''), 0, 3);
    $logs = array_slice($logs, $start, $lpp);
} else {
    foreach ($logs as $key => $value) {
	if (strpos($value, $_G['gp_keyword']) === FALSE) {
	    unset($logs[$key]);
	}
    }
    $multipage = '';
}

$usergroup = array();

shownav('tools', 'nav_logs', 'nav_logs_' . $operation);
if ($logfiles) {
    $sel = '<select class="right" style="margin-right:20px;" onchange="location.href=\'' . ADMINSCRIPT . '?action=logs&operation=' . $operation . '&keyword=' . $_G['gp_keyword'] . '&day=\'+this.value">';
    foreach ($logfiles as $logfile) {
	list($date, $logtype, $num) = explode('_', $logfile);
	if (is_numeric($date)) {
	    $num = intval($num);
	    $sel .= '<option value="' . $date . '_' . $num . '"' . ($date . '_' . $num == $_G['gp_day'] . '_' . $_G['gp_num'] ? ' selected="selected"' : '') . '>' . ($num ? '&nbsp;&nbsp;' . $date . ' ' . cplang('logs_archive') . ' ' . $num : $date) . '</option>';
	} else {
	    list($logtype) = explode('.', $logtype);
	    $sel .= '<option value="' . $logtype . '"' . ($logtype == $_G['gp_day'] ? ' selected="selected"' : '') . '>' . $logtype . '</option>';
	}
    }
    $sel .= '</select>';
} else {
    $sel = '';
}
showsubmenu('nav_logs', array(
    array(array('menu' => 'nav_logs_system', 'submenu' => array(
		array('nav_logs_cp', 'logs&operation=cp'),
		array('nav_logs_error', 'logs&operation=error'),
	)), '', in_array($operation, array('cp', 'error'))),    
	), $sel);

showformheader("logs&operation=$operation");
showtableheader('', 'fixpadding" style="table-layout: fixed');
$filters = '';
if ($operation == 'cp') {

    showtablerow('class="header"', array('class="td23"', 'class="td23"', 'class="td24"', 'class="td24"', ''), array(
	cplang('operator'),
	cplang('ip'),
	cplang('time'),
	cplang('action'),
	cplang('other')
    ));

    echo <<<EOD
<script type="text/javascript">
function togglecplog(k) {
	var cplogobj = $('cplog_'+k);
	if(cplogobj.style.display == 'none') {
		cplogobj.style.display = '';
	} else {
		cplogobj.style.display = 'none';
	}
}
</script>
EOD;

    foreach ($logs as $k => $logrow) {
	$log = explode("\t", $logrow);
	if (empty($log[1])) {
	    continue;
	}
//	$log[1] = dgmdate($log[1], 'y-n-j H:i');
	$log[1] = date('y-n-j H:i:s',$log[1]);
	$log[2] = dstripslashes($log[2]);
	$log[2] = $log[2]?$log[2]:'异常';
	$log[3] = $usergroup[$log[3]];
	$log[5] = rtrim($log[5]);
	showtablerow('', '', array($log[2], $log[4], $log[1], $log[5], '<a href="javascript:;" onclick="togglecplog(' . $k . ')">' . cutstr($log[6], 200) . '</a>'));
	echo '<tbody id="cplog_' . $k . '" style="display:none;">';
	echo '<tr><td colspan="6">' . $log[6] . '</td></tr>';
	echo '</tbody>';
    }
} elseif ($operation == 'error') {

    showtablerow('class="header"', array('class="td23"', 'class=""'), array(
	cplang('time'),
	cplang('message'),
    ));
    foreach ($logs as $logrow) {
	$log = explode("\t", $logrow);
	if (empty($log[1])) {
	    continue;
	}

	showtablerow('', array('class="bold"'), array(
	    dgmdate($log[1], 'Y-m-d H:i:s'),
	    $log[2] . '<br>' . $log[4] . '<br>' . $log[5]
	));
    }
}

function get_log_files($logdir = '', $action = 'action') {
    $dir = opendir($logdir);
    $files = array();
    while ($entry = readdir($dir)) {
	$files[] = $entry;
    }
    closedir($dir);

    if ($files) {
	sort($files);
	$logfile = $action;
	$logfiles = array();
	$ym = '';
	foreach ($files as $file) {
	    if (strpos($file, $logfile) !== FALSE) {
		if (substr($file, 0, 6) != $ym) {
		    $ym = substr($file, 0, 6);
		}
		$logfiles[$ym][] = $file;
	    }
	}
	if ($logfiles) {
	    $lfs = array();
	    foreach ($logfiles as $ym => $lf) {
		$lastlogfile = $lf[0];
		unset($lf[0]);
		$lf[] = $lastlogfile;
		$lfs = array_merge($lfs, $lf);
	    }
	    return $lfs;
	}
	return array();
    }
    return array();
}

if ($_G['gp_keyword']) {
    $filters = '';
}
showtablefooter();
showtableheader('', 'fixpadding');
if ($operation != 'credit') {
    if (!empty($_GET['day'])) {
	showhiddenfields(array('day' => $_GET['day']));
    }
    showsubmit($operation == 'invite' ? 'invitesubmit' : '', 'submit', 'del', $filters, $multipage . (empty($_G['gp_keyword']) ? cplang('logs_lpp') . ':<select onchange="if(this.options[this.selectedIndex].value != \'\') {window.location=\'' . ADMINSCRIPT . '?action=logs&operation=' . $operation . '&lpp=\'+this.options[this.selectedIndex].value }"><option value="20" ' . $checklpp[20] . '> 20 </option><option value="40" ' . $checklpp[40] . '> 40 </option><option value="80" ' . $checklpp[80] . '> 80 </option></select>' : '') . '&nbsp;<input type="text" class="txt" name="keyword" value="' . $_G['gp_keyword'] . '" />' . ($_G['gp_day'] ? '<input type="hidden" class="btn" value="' . $_G['gp_day'] . '" />' : '') . '<input type="submit" class="btn" value="' . $lang['search'] . '" />');
}
showtablefooter();
showformfooter();
?>