<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_misc.php 21860 2011-04-14 06:45:25Z monkey $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();


if($operation == 'cron') {
	if(empty($_G['gp_edit']) && empty($_G['gp_run'])) {

		if(!submitcheck('cronssubmit')) {

			shownav('tools', 'misc_cron');
			showsubmenu('nav_misc_cron');
			showtips('misc_cron_tips');
			showformheader('misc&operation=cron');
			showtableheader('', 'fixpadding');
			showsubtitle(array('', 'name', 'available', 'type', 'time', 'misc_cron_last_run', 'misc_cron_next_run', ''));

			$query = DB::query("SELECT * FROM ".DB::table('common_cron')." ORDER BY type DESC");
			while($cron = DB::fetch($query)) {
				$disabled = $cron['weekday'] == -1 && $cron['day'] == -1 && $cron['hour'] == -1 && $cron['minute'] == '' ? 'disabled' : '';

				if($cron['day'] > 0 && $cron['day'] < 32) {
					$cron['time'] = cplang('misc_cron_permonth').$cron['day'].cplang('misc_cron_day');
				} elseif($cron['weekday'] >= 0 && $cron['weekday'] < 7) {
					$cron['time'] = cplang('misc_cron_perweek').cplang('misc_cron_week_day_'.$cron['weekday']);
				} elseif($cron['hour'] >= 0 && $cron['hour'] < 24) {
					$cron['time'] = cplang('misc_cron_perday');
				} else {
					$cron['time'] = cplang('misc_cron_perhour');
				}

				$cron['time'] .= $cron['hour'] >= 0 && $cron['hour'] < 24 ? sprintf('%02d', $cron[hour]).cplang('misc_cron_hour') : '';

				if(!in_array($cron['minute'], array(-1, ''))) {
					foreach($cron['minute'] = explode("\t", $cron['minute']) as $k => $v) {
						$cron['minute'][$k] = sprintf('%02d', $v);
					}
					$cron['minute'] = implode(',', $cron['minute']);
					$cron['time'] .= $cron['minute'].cplang('misc_cron_minute');
				} else {
					$cron['time'] .= '00'.cplang('misc_cron_minute');
				}

				$cron['lastrun'] = $cron['lastrun'] ? date('Y-m-d H:i:s',$cron['lastrun']) : '<b>N/A</b>';
				$cron['nextcolor'] = $cron['nextrun'] && $cron['nextrun'] + $_G['setting']['timeoffset'] * 3600 < TIMESTAMP ? 'style="color: #ff0000"' : '';
				$cron['nextrun'] = $cron['nextrun'] ? date('Y-m-d H:i:s',$cron['nextrun']) : '<b>N/A</b>';

				showtablerow('', array('class="td25"', 'class="crons"', 'class="td25"', 'class="td25"', 'class="td23"', 'class="td23"', 'class="td23"', 'class="td25"'), array(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$cron[cronid]\" ".($cron['type'] == 'system' ? 'disabled' : '').">",
					"<input type=\"text\" class=\"txt\" name=\"namenew[$cron[cronid]]\" size=\"20\" value=\"$cron[name]\"><br /><b>$cron[filename]</b>",
					"<input class=\"checkbox\" type=\"checkbox\" name=\"availablenew[$cron[cronid]]\" value=\"1\" ".($cron['available'] ? 'checked' : '')." $disabled>",
					cplang($cron['type'] == 'system' ? 'inbuilt' : 'custom'),
					$cron[time],
					$cron[lastrun],
					$cron[nextrun],
					"<a href=\"".ADMINSCRIPT."?action=misc&operation=cron&edit=$cron[cronid]\" class=\"act\">$lang[edit]</a><br />".
					($cron['available'] ? " <a href=\"".ADMINSCRIPT."?action=misc&operation=cron&run=$cron[cronid]\" class=\"act\">$lang[misc_cron_run]</a>" : " <a href=\"###\" class=\"act\" disabled>$lang[misc_cron_run]</a>")
				));
			}

			showtablerow('', array('','colspan="10"'), array(
				cplang('add_new'),
				'<input type="text" class="txt" name="newname" value="" size="20" />'
			));
			showsubmit('cronssubmit', 'submit', 'del');
			showtablefooter();
			showformfooter();

		} else {

			if($ids = dimplode($_G['gp_delete'])) {
				DB::delete('common_cron', "cronid IN ($ids) AND type='user'");
			}

			if(is_array($_G['gp_namenew'])) {
				foreach($_G['gp_namenew'] as $id => $name) {
					$newcron = array(
						'name' => dhtmlspecialchars($_G['gp_namenew'][$id]),
						'available' => $_G['gp_availablenew'][$id]
					);
					if(empty($_G['gp_availablenew'][$id])) {
						$newcron['nextrun'] = '0';
					}
					DB::update('common_cron', $newcron, "cronid='$id'");
				}
			}

			if($newname = trim($_G['gp_newname'])) {
				DB::insert('common_cron', array(
					'name' => dhtmlspecialchars($newname),
					'type' => 'user',
					'available' => '0',
					'weekday' => '-1',
					'day' => '-1',
					'hour' => '-1',
					'minute' => '',
					'nextrun' => $_G['timestamp'],
				));
			}

			$query = DB::query("SELECT cronid, filename FROM ".DB::table('common_cron'));
			while($cron = DB::fetch($query)) {
				if(!file_exists(DISCUZ_ROOT.'./source/include/cron/'.$cron['filename'])) {
					DB::update('common_cron', array(
						'available' => '0',
						'nextrun' => '0',
					), "cronid='$cron[cronid]'");
				}
			}

			updatecache('setting');
			cpmsg('crons_succeed', 'action=misc&operation=cron', 'succeed');

		}

	} else {

		$cronid = empty($_G['gp_run']) ? $_G['gp_edit'] : $_G['gp_run'];
		$cron = DB::fetch_first("SELECT * FROM ".DB::table('common_cron')." WHERE cronid='$cronid'");
		if(!$cron) {
			cpmsg('cron_not_found', '', 'error');
		}
		$cron['filename'] = str_replace(array('..', '/', '\\'), array('', '', ''), $cron['filename']);
		$cronminute = str_replace("\t", ',', $cron['minute']);
		$cron['minute'] = explode("\t", $cron['minute']);

		if(!empty($_G['gp_edit'])) {

			if(!submitcheck('editsubmit')) {

				shownav('tools', 'misc_cron');
				showsubmenu($lang['misc_cron_edit'].' - '.$cron['name']);
				showtips('misc_cron_edit_tips');

				$weekdayselect = $dayselect = $hourselect = '';

				for($i = 0; $i <= 6; $i++) {
					$weekdayselect .= "<option value=\"$i\" ".($cron['weekday'] == $i ? 'selected' : '').">".$lang['misc_cron_week_day_'.$i]."</option>";
				}

				for($i = 1; $i <= 31; $i++) {
					$dayselect .= "<option value=\"$i\" ".($cron['day'] == $i ? 'selected' : '').">$i $lang[misc_cron_day]</option>";
				}

				for($i = 0; $i <= 23; $i++) {
					$hourselect .= "<option value=\"$i\" ".($cron['hour'] == $i ? 'selected' : '').">$i $lang[misc_cron_hour]</option>";
				}

				shownav('tools', 'misc_cron');
				showformheader("misc&operation=cron&edit=$cronid");
				showtableheader();
				showsetting('misc_cron_edit_weekday', '', '', "<select name=\"weekdaynew\"><option value=\"-1\">*</option>$weekdayselect</select>");
				showsetting('misc_cron_edit_day', '', '', "<select name=\"daynew\"><option value=\"-1\">*</option>$dayselect</select>");
				showsetting('misc_cron_edit_hour', '', '', "<select name=\"hournew\"><option value=\"-1\">*</option>$hourselect</select>");
				showsetting('misc_cron_edit_minute', 'minutenew', $cronminute, 'text');
				showsetting('misc_cron_edit_filename', 'filenamenew', $cron['filename'], 'text');
				showsubmit('editsubmit');
				showtablefooter();
				showformfooter();

			} else {

				$daynew = $_G['gp_weekdaynew'] != -1 ? -1 : $_G['gp_daynew'];
				if(strpos($_G['gp_minutenew'], ',') !== FALSE) {
					$minutenew = explode(',', $_G['gp_minutenew']);
					foreach($minutenew as $key => $val) {
						$minutenew[$key] = $val = intval($val);
						if($val < 0 || $var > 59) {
							unset($minutenew[$key]);
						}
					}
					$minutenew = array_slice(array_unique($minutenew), 0, 12);
					$minutenew = implode("\t", $minutenew);
				} else {
					$minutenew = intval($_G['gp_minutenew']);
					$minutenew = $minutenew >= 0 && $minutenew < 60 ? $minutenew : '';
				}

				if(preg_match("/[\\\\\/\:\*\?\"\<\>\|]+/", $_G['gp_filenamenew'])) {
					cpmsg('crons_filename_illegal', '', 'error');
				} elseif(!is_readable(DISCUZ_ROOT.($cronfile = "./source/include/cron/{$_G['gp_filenamenew']}"))) {
					cpmsg('crons_filename_invalid', '', 'error', array('cronfile' => $cronfile));
				} elseif($_G['gp_weekdaynew'] == -1 && $daynew == -1 && $_G['gp_hournew'] == -1 && $minutenew === '') {
					cpmsg('crons_time_invalid', '', 'error');
				}

				DB::update('common_cron', array(
					'weekday' => $_G['gp_weekdaynew'],
					'day' => $daynew,
					'hour' => $_G['gp_hournew'],
					'minute' => $minutenew,
					'filename' => trim($_G['gp_filenamenew']),
				), "cronid='$cronid'");

				updatecache('crons');
				require_once libfile('class/cron');
				discuz_cron::run($cronid);

				cpmsg('crons_succeed', 'action=misc&operation=cron', 'succeed');

			}

		} else {

			if(!file_exists(DISCUZ_ROOT.($cronfile = "./source/include/cron/$cron[filename]"))) {
				cpmsg('crons_run_invalid', '', 'error', array('cronfile' => $cronfile));
			} else {
				require_once libfile('class/cron');
				discuz_cron::run($cron['cronid']);
				cpmsg('crons_run_succeed', 'action=misc&operation=cron', 'succeed');
			}

		}

	}

}
elseif($operation == 'custommenu') {

	if(!$do) {

		if(!submitcheck('optionsubmit')) {
			$mpp = 10;
			$startlimit = ($page - 1) * $mpp;
			$num = DB::result_first("SELECT count(*) FROM ".DB::table('common_admincp_cmenu')." WHERE uid='$_G[uid]' AND sort='1'");
			$multipage = multi($num, $mpp, $page, ADMINSCRIPT.'?action=misc&operation=custommenu');
			$optionlist = $ajaxoptionlist = '';
			$query = DB::query("SELECT id, title, displayorder, url FROM ".DB::table('common_admincp_cmenu')." WHERE uid='$_G[uid]' AND sort='1' ORDER BY displayorder, dateline DESC, clicks DESC LIMIT $startlimit, $mpp");
			while($custom = DB::fetch($query)) {
				$custom['url'] = rawurldecode($custom['url']);
				$optionlist .= showtablerow('', array('class="td25"', 'class="td28"', '', 'class="td26"'), array(
					"<input type=\"checkbox\" class=\"checkbox\" name=\"delete[]\" value=\"$custom[id]\">",
					"<input type=\"text\" class=\"txt\" size=\"3\" name=\"displayordernew[$custom[id]]\" value=\"$custom[displayorder]\">",
					"<input type=\"text\" class=\"txt\" size=\"25\" name=\"titlenew[$custom[id]]\" value=\"".cplang($custom['title'])."\"><input type=\"hidden\" name=\"langnew[$custom[id]]\" value=\"$custom[title]\">",
					"<input type=\"text\" class=\"txt\" size=\"40\" name=\"urlnew[$custom[id]]\" value=\"$custom[url]\">"
				), TRUE);
				$ajaxoptionlist .= '<li><a href="'.$custom['url'].'" target="'.(substr(rawurldecode($custom['url']), 0, 17) == ADMINSCRIPT.'?action=' ? 'main' : '_blank').'">'.cplang($custom['title']).'</a></li>';
			}

			echo <<<EOT
<script type="text/JavaScript">
	var rowtypedata = [
		[
			[1,'', 'td25'],
			[1,'<input type="text" class="txt" name="newdisplayorder[]" size="3">', 'td28'],
			[1,'<input type="text" class="txt" name="newtitle[]" size="25">'],
			[1,'<input type="text" class="txt" name="newurl[]" size="40">', 'td26']
		]
	];
</script>
EOT;
			shownav('tools', 'nav_custommenu');
			showsubmenu('nav_custommenu');
			showformheader('misc&operation=custommenu');
			showtableheader();
			showsubtitle(array('', 'display_order', 'name', 'URL'));
			echo $optionlist;
			echo '<tr><td></td><td colspan="3"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['custommenu_add'].'</a></div></td></tr>';
			showsubmit('optionsubmit', 'submit', 'del', '', $multipage);
			showtablefooter();
			showformfooter();

		} else {

			if($ids = dimplode($_G['gp_delete'])) {
				DB::query("DELETE FROM ".DB::table('common_admincp_cmenu')." WHERE id IN ($ids) AND uid='$_G[uid]'");
			}

			if(is_array($_G['gp_titlenew'])) {
				foreach($_G['gp_titlenew'] as $id => $title) {
					$_G['gp_urlnew'][$id] = rawurlencode($_G['gp_urlnew'][$id]);
					$title = dhtmlspecialchars($_G['gp_langnew'][$id] && lang($_G['gp_langnew'][$id], false) ? $_G['gp_langnew'][$id] : $title);
					$ordernew = intval($_G['gp_displayordernew'][$id]);
					DB::query("UPDATE ".DB::table('common_admincp_cmenu')." SET title='$title', displayorder='$ordernew', url='".dhtmlspecialchars($_G['gp_urlnew'][$id])."' WHERE id='$id'");
				}
			}

			if(is_array($_G['gp_newtitle'])) {
				foreach($_G['gp_newtitle'] as $k => $v) {
					$_G['gp_urlnew'][$k] = rawurlencode($_G['gp_urlnew'][$k]);
					DB::query("INSERT INTO ".DB::table('common_admincp_cmenu')." (title, displayorder, url, sort, uid) VALUES ('".dhtmlspecialchars($v)."', '".intval($_G['gp_newdisplayorder'][$k])."', '".dhtmlspecialchars($_G['gp_newurl'][$k])."', '1', '$_G[uid]')");
				}
			}

			updatemenu('index');
			cpmsg('custommenu_edit_succeed', 'action=misc&operation=custommenu', 'succeed');

		}

	} elseif($do == 'add') {

		if($_G['gp_title'] && $_G['gp_url']) {
			admincustom($_G['gp_title'], dhtmlspecialchars($_G['gp_url']), 1);
			updatemenu('index');
			cpmsg('custommenu_add_succeed', rawurldecode($_G['gp_url']), 'succeed', array('title' => cplang($_G['gp_title'])));
		} else {
			cpmsg('parameters_error', '', 'error');
		}
	}

}

?>