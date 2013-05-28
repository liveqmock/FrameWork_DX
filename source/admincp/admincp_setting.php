<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: admincp_setting.php 23419 2011-07-14 03:49:57Z liulanbo $
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

cpheader();

$setting = array();
$query = DB::query("SELECT * FROM ".DB::table('common_setting'));
while($row = DB::fetch($query)) {
	$setting[$row['skey']] = $row['svalue'];
}

$extbutton = '';
$operation = $operation ? $operation : 'basic';



if(!submitcheck('settingsubmit')) {

	
shownav('global', 'setting_'.$operation);
	
	showsubmenu('setting_'.$operation);
	showformheader('setting&edit=yes');
	showhiddenfields(array('operation' => $operation));

	if($operation == 'basic') {

		showtableheader('');
		showsetting('setting_basic_bbname', 'settingnew[bbname]', $setting['bbname'], 'text');
		showsetting('setting_basic_sitename', 'settingnew[sitename]', $setting['sitename'], 'text');
		showsetting('setting_basic_siteurl', 'settingnew[siteurl]', $setting['siteurl'], 'text');
		showsetting('setting_basic_adminemail', 'settingnew[adminemail]', $setting['adminemail'], 'text');
		showtablefooter();

		showtableheader('setting_basic_bbclosed');
		showsetting('setting_basic_bbclosed', 'settingnew[bbclosed]', $setting['bbclosed'], 'radio', 0, 1);
		showsetting('setting_basic_closedreason', 'settingnew[closedreason]', $setting['closedreason'], 'textarea');
		showsetting('setting_basic_bbclosed_activation', 'settingnew[closedallowactivation]', $setting['closedallowactivation'], 'radio');
		showtagfooter('tbody');

	} elseif($operation == 'datetime') {

		$checktimeformat = array($setting['timeformat'] == 'H:i' ? 24 : 12 => 'checked');

		$setting['userdateformat'] = dateformat($setting['userdateformat']);
		$setting['dateformat'] = dateformat($setting['dateformat']);

		showtableheader();
		showtitle('setting_datetime_format');
		showsetting('setting_datetime_dateformat', 'settingnew[dateformat]', $setting['dateformat'], 'text');
		showsetting('setting_datetime_timeformat', '', '', '<input class="radio" type="radio" name="settingnew[timeformat]" value="24" '.$checktimeformat[24].'> 24 '.$lang['hour'].' <input class="radio" type="radio" name="settingnew[timeformat]" value="12" '.$checktimeformat[12].'> 12 '.$lang['hour'].'');

		$timezone_lang = cplang('setting_datetime_timezone');
		$timezone_select = "<select name='settingnew[timeoffset]'>";
		foreach($timezone_lang AS $key => $val) {
			$timezone_select .= "<option value='$key' ".($setting['timeoffset'] == $key ? 'selected="selected"' : '').">".cutstr($val, 34, '..')."</option>";
		}
		$timezone_select .= "</select>";
		showsetting('setting_datetime_timeoffset', '', '', $timezone_select);

	}  elseif($operation == 'memory') {

		showtips('setting_memory_tips');
		showtableheader('setting_memory_status', 'fixpadding');
		showsubtitle(array('setting_memory_state_interface', 'setting_memory_state_extension', 'setting_memory_state_config', 'setting_memory_clear', ''));

		$do_clear_ok = $do == 'clear' ? cplang('setting_memory_do_clear') : '';
		$do_clear_link = '<a href="'.ADMINSCRIPT.'?action=setting&operation=memory&do=clear">'.cplang('setting_memory_clear').'</a>'.$do_clear_ok;

		$ea = array('eAccelerator',
			$discuz->mem->extension['eaccelerator'] ? cplang('setting_memory_php_enable') : cplang('setting_memory_php_disable'),
			$discuz->mem->config['eaccelerator'] ? cplang('open') : cplang('closed'),
			$discuz->mem->type == 'eaccelerator' ? $do_clear_link : '--'
			);
		$apc = array('APC',
			$discuz->mem->extension['apc'] ? cplang('setting_memory_php_enable') : cplang('setting_memory_php_disable'),
			$discuz->mem->config['apc'] ? cplang('open') : cplang('closed'),
			$discuz->mem->type == 'apc' ? $do_clear_link : '--'
			);
		$memcache = array('memcache',
			$discuz->mem->extension['memcache'] ? cplang('setting_memory_php_enable') : cplang('setting_memory_php_disable'),
			$discuz->mem->config['memcache']['server'] ? cplang('open') : cplang('closed'),
			$discuz->mem->type == 'memcache' ? $do_clear_link : '--'
			);
		$xcache = array('Xcache',
			$discuz->mem->extension['xcache'] ? cplang('setting_memory_php_enable') : cplang('setting_memory_php_disable'),
			$discuz->mem->config['xcache'] ? cplang('open') : cplang('closed'),
			$discuz->mem->type == 'xcache' ? $do_clear_link : '--'
			);

		showtablerow('', array('width="100"', 'width="120"', 'width="120"'), $memcache);
		showtablerow('', '', $ea);
		showtablerow('', '', $apc);
		showtablerow('', '', $xcache);
		showtablefooter();

		if(!isset($setting['memory'])) {
			DB::insert('common_setting', array('skey' => 'memory', 'svalue' =>''), false, true);
			$setting['memory'] = '';
		}

		if($do == 'clear') {
			$discuz->mem->clear();
		}

		$setting['memory'] = unserialize($setting['memory']);
		showtableheader('setting_memory_function', 'fixpadding');
		showsubtitle(array('setting_memory_func', 'setting_memory_func_enable', 'setting_memory_func_ttl', ''));

		$func_array = array('forumindex', 'diyblock', 'diyblockoutput');

		foreach ($func_array as $skey) {
			showtablerow('', array('width="100"', 'width="120"', 'width="120"'), array(
					cplang('setting_memory_func_'.$skey),
					'<input type="checkbox" class="checkbox" name="settingnew[memory]['.$skey.'][enable]" '.($setting['memory'][$skey]['enable'] ? 'checked' : '').' value="1">',
					'<input type="text" class="txt" name="settingnew[memory]['.$skey.'][ttl]" value="'.$setting['memory'][$skey]['ttl'].'">',
					''
					));
		}

	}
	showsubmit('settingsubmit', 'submit', '', $extbutton.(!empty($from) ? '<input type="hidden" name="from" value="'.$from.'">' : ''));
	showtablefooter();
	showformfooter();

} else {

	$settingnew = $_G['gp_settingnew'];



	isset($settingnew['regname']) && empty($settingnew['regname']) && $settingnew['regname'] = 'register';
	isset($settingnew['reglinkname']) && empty($settingnew['reglinkname']) && $settingnew['reglinkname'] = cplang('reglinkname_default');
	$nohtmlarray = array('bbname', 'regname', 'reglinkname', 'icp', 'sitemessage');
	foreach($nohtmlarray as $k) {
		if(isset($settingnew[$k])) {
			$settingnew[$k] = dhtmlspecialchars($settingnew[$k]);
		}
	}




	if(isset($settingnew['sitemessage'])) {
		$settingnew['sitemessage'] = addslashes(serialize($settingnew['sitemessage']));
	}

	
	if(!empty($settingnew['memory'])) {
		foreach($settingnew['memory'] as $k => $v) {
			$settingnew['memory'][$k] = array(
				'enable' => !empty($settingnew['memory'][$k]['enable']) ? 1 : 0,
				'ttl' => min(3600 * 24, max(30, intval($settingnew['memory'][$k]['ttl'])))
				);
		}
		$settingnew['memory'] = addslashes(serialize($settingnew['memory']));
	}

	if(isset($settingnew['timeformat'])) {
		$settingnew['timeformat'] = $settingnew['timeformat'] == '24' ? 'H:i' : 'h:i A';
	}

	if(isset($settingnew['dateformat'])) {
		$settingnew['dateformat'] = dateformat($settingnew['dateformat'], 'format');
	}


	$updatecache = FALSE;
	$settings = array();
	foreach($settingnew as $key => $val) {
		if($setting[$key] != $val) {
			$$key = $val;
			$updatecache = TRUE;
			if(in_array($key, array('newbiespan',  'timeoffset', 'statscachelife', 'pvfrequence', 'oltimespan', 'seccodestatus',
				'maxprice', 'rssttl', 'maxonlines', 'loadctrl', 'floodctrl', 'regctrl', 'regfloodctrl',
				'jscachelife', 'maxmodworksmonths', 'maxonlinelist'))) {
				$val = (float)$val;
			}


			$settings[] = "('$key', '$val')";
		}
	}

	if($settings) {
		DB::query("REPLACE INTO ".DB::table('common_setting')." (`skey`, `svalue`) VALUES ".implode(',', $settings));
	}
	if($updatecache) {

		updatecache('setting');		
				
	}

	cpmsg('setting_update_succeed', 'action=setting&operation='.$operation.(!empty($_G['gp_anchor']) ? '&anchor='.$_G['gp_anchor'] : '').(!empty($from) ? '&from='.$from : ''), 'succeed');
}

function dateformat($string, $operation = 'formalise') {
	$string = htmlspecialchars(trim($string));
	$replace = $operation == 'formalise' ? array(array('n', 'j', 'y', 'Y'), array('mm', 'dd', 'yy', 'yyyy')) : array(array('mm', 'dd', 'yyyy', 'yy'), array('n', 'j', 'Y', 'y'));
	return str_replace($replace[0], $replace[1], $string);
}

function insertconfig($s, $find, $replace) {
	if(preg_match($find, $s)) {
		$s = preg_replace($find, $replace, $s);
	} else {
		$s .= "\r\n".$replace;
	}
	return $s;
}


function showlist($first, $seconds, $thirds, $subtype) {
	echo '<tbody id="'.$subtype.'_detail" style="display:none"><tr><td colspan="2"><table width="100%">';
	foreach ($first as $id => $gsecond) {
		showdetial($gsecond, $subtype, 'group', '', 1);
		if(!empty($seconds[$id])) {
			foreach ($seconds[$id] as $second) {
				showdetial($second, $subtype);
				if(!empty($thirds[$second['id']])) {
					foreach ($thirds[$second['id']] as $third) {
						showdetial($third, $subtype);
					}
				}
			}
		}
		showdetial($gsecond, $subtype, '', 'last');
	}
	echo '</table></td></tr></tbody>';
}

function showdetial(&$forum, $varname, $type = '', $last = '', $toggle = false) {
	global $_G;

	if($last == '') {
		$tab1 = '&nbsp;&nbsp;';
		$tab2 = '&nbsp;&nbsp;&nbsp;&nbsp;';
		if($type == 'group') {
			echo '<tr class="hover"><td colspan="2"'.($type == 'group' ? ' onclick="toggle_group(\'group_'.$varname.$forum['id'].'\', $(\'a_group_'.$varname.$forum['id'].'\'))"' : '').'>'.($type == 'group' ? '<a href="javascript:;" id="a_group_'.$varname.$forum['id'].'">'.($toggle ? '[+]' : '[-]').'</a>' : '').'&nbsp;&nbsp;'.$forum['name'].'</td></tr><tbody id="group_'.$varname.$forum['id'].'"'.($toggle ? ' style="display:none;"' : '').'>';
		}
			echo '<tr class="header"><td colspan="2">'.$tab1.$forum['name'].'</td></tr>';
			showtablerow('', array('width="12%"', ''), array(
					$tab2.cplang('setting_seo_seotitle'),
					'<input type="text" id="t_'.$forum['id'].'_'.$varname.'" onfocus="getcodetext(this, \''.$varname.'\');" name="seo'.$varname.'['.$forum[id].'][seotitle]" value="'.htmlspecialchars($forum['seotitle']).'" class="txt" style="width:280px;" />',
				)
			);
			showtablerow('', array('width="12%"', ''), array(
					$tab2.cplang('setting_seo_seokeywords'),
					'<input type="text" id="k_'.$forum['id'].'_'.$varname.'" onfocus="getcodetext(this, \''.$varname.'\');" name="seo'.$varname.'['.$forum[id].'][keywords]" value="'.htmlspecialchars($forum['keywords']).'" class="txt" style="width:280px;" />',
				)
			);
			showtablerow('', array('width="12%"', ''), array(
					$tab2.cplang('setting_seo_seodescription'),
					'<input type="text" id="d_'.$forum['id'].'_'.$varname.'" onfocus="getcodetext(this, \''.$varname.'\');" name="seo'.$varname.'['.$forum[id].'][description]" value="'.htmlspecialchars($forum['description']).'" class="txt" style="width:280px;" />',
				)
			);
	} else {
		if($last == 'lastboard') {
			$return = '</tbody>';
		} elseif($last == 'lastchildboard' && $type) {
			$return = '<script type="text/JavaScript">$(\'cb_'.$type.'\').className = \'lastchildboard\';</script>';
		} elseif($last == 'last') {
			$return = '</tbody>';
		}
	}
	echo  $return = isset($return) ? $return : '';
}
?>