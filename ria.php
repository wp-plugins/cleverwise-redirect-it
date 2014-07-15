<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Verify admin panel is loaded, if not fail
////////////////////////////////////////////////////////////////////////////
if (!is_admin()) {
	die();
}

////////////////////////////////////////////////////////////////////////////
//	Menu call
////////////////////////////////////////////////////////////////////////////
add_action('admin_menu', 'cw_redirect_it_aside_mn');

////////////////////////////////////////////////////////////////////////////
//	Load admin menu option
////////////////////////////////////////////////////////////////////////////
function cw_redirect_it_aside_mn() {

	//	If user is logged in and has admin permissions show menu
	if (is_user_logged_in()) {
		add_menu_page('Redirect It','Redirect It','manage_options','cw-redirect-it','cw_redirect_it_aside','','31');
	}
}

////////////////////////////////////////////////////////////////////////////
//	Load admin functions
////////////////////////////////////////////////////////////////////////////
function cw_redirect_it_aside() {
Global $wpdb,$ri_wp_option,$ri_wp_option_updates_txt,$cw_redirect_it_tbl,$cwfa_ri;

	////////////////////////////////////////////////////////////////////////////
	//	Load options for plugin
	////////////////////////////////////////////////////////////////////////////
	$ri_wp_option_array=get_option($ri_wp_option);
	$ri_wp_option_array=unserialize($ri_wp_option_array);
	$redirect_filename=$ri_wp_option_array['redirect_filename'];
	$redirect_abspath=$ri_wp_option_array['redirect_abspath'];
	$redirect_site_url=$ri_wp_option_array['redirect_site_url'];
	$redirect_url_type=$ri_wp_option_array['redirect_url_type'];
	$redirect_no_match_url=$ri_wp_option_array['redirect_no_match_url'];
	$redirect_records_ppg=$ri_wp_option_array['redirect_records_ppg'];

	$ri_wp_option_updates_array=get_option($ri_wp_option_updates_txt);
	$ri_wp_option_updates_array=unserialize($ri_wp_option_updates_array);
	$redirect_file_update=$ri_wp_option_updates_array['redirect_file_update'];
	$redirect_db_update=$ri_wp_option_updates_array['redirect_db_update'];

	////////////////////////////////////////////////////////////////////////////
	//	Set action value
	////////////////////////////////////////////////////////////////////////////
	if (isset($_REQUEST['cw_action'])) {
		$cw_action=$_REQUEST['cw_action'];
	} else {
		$cw_action='main';
	}

	////////////////////////////////////////////////////////////////////////////
	//	Previous page link
	////////////////////////////////////////////////////////////////////////////
	$pplink='<a href="javascript:history.go(-1);">Return to previous page...</a>';

	////////////////////////////////////////////////////////////////////////////
	//	Define Variables
	////////////////////////////////////////////////////////////////////////////
	$cw_redirect_it_action='';
	$cw_redirect_it_html='';
	$redirect_file_status='Up-to-date';
	$redirect_types=array('o'=>'Off','s'=>'On (Standard)');
	$redirect_url_types=array('s'=>'Standard','a'=>'Advanced');

	////////////////////////////////////////////////////////////////////////////
	//	Redirect File Build
	////////////////////////////////////////////////////////////////////////////
	if ($cw_action == 'redirectbuild') {

		// 	Is writeable?
		$writeable='y';

		//	Is redirect file directory writeable?
		if (!is_writeable($redirect_abspath)) {
			$writeable='n';
		}
		$redirect_abspath .=$redirect_filename;

		//	If redirect file directory isn't writeable is file?
		if ($writeable == 'n') { 
			if (is_writeable($redirect_abspath)) {
				$writeable='y';
			}
		}

		if ($writeable == 'n') {
			$cw_redirect_it_html='<p style="font-weight: bold; color: #ff0000; font-size: 14px;">FAILURE! Redirect file is NOT writeable!</p><p>This needs to be corrected before redirect file may be generated!  Often this is caused by an incorrect permission setting on the web server.  Please check your configuration.</p>';
		} else {

			//	Grab redirects from database
			$redirectlist='';
			$myrows=$wpdb->get_results("SELECT ri_link_name,ri_link_code,ri_link_url,ri_link_aliases FROM $cw_redirect_it_tbl where ri_link_type='s'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$ri_link_name=$cwfa_ri->cwf_fmt_sth(strtolower($myrow->ri_link_name));
					$ri_link_code=$cwfa_ri->cwf_san_alls(stripslashes($myrow->ri_link_code));
					$ri_link_url=$cwfa_ri->cwf_san_url(stripslashes($myrow->ri_link_url));
					$ri_link_aliases=$cwfa_ri->cwf_fmt_sth(strtolower($myrow->ri_link_aliases));

					$ri_link_ids=array();
					if ($ri_link_aliases) {
						$ri_link_ids=explode('|',$ri_link_aliases);
						array_pop($ri_link_ids);
					} 
					array_push($ri_link_ids,"$ri_link_name");

					foreach ($ri_link_ids as $ri_link_id) {
						$redirectlist .='\''.$ri_link_id.'\''.'=>'.'\''.$ri_link_url.'\',';
					}
				}
			}

			//	Create redirect file
			$redirectpage='';
$redirectpage .=<<<EOM
<?php
/*
Wordpress Plugin Cleverwise Redirect It auto generated file
Do NOT edit directly as changes will be overwritten by plugin
Layout: 010114
*/

\$redirects=array($redirectlist);

\$l='skip';
\$rourl='$redirect_no_match_url';

if (isset(\$_REQUEST['l'])) {
	\$l=\$_REQUEST['l'];
	\$l=preg_replace('/\\//','',\$l);
}

if (array_key_exists(\$l,\$redirects)) {
	\$rourl=\$redirects[\$l];
}

header("Location: \$rourl");
?>
EOM;

			//	Write redirect file
			$filehandler=fopen($redirect_abspath, 'w');
			fwrite($filehandler,$redirectpage);
			fclose($filehandler);

			$cw_redirect_it_html='<p>Success! Redirect file has been created!</p>';

			//	Log file update
			$ri_wp_option_updates_array['redirect_db_update']=$redirect_db_update;
			$ri_wp_option_updates_array['redirect_file_update']=time();
			$redirect_file_update=$ri_wp_option_updates_array['redirect_file_update'];

			$ri_wp_option_updates_array=serialize($ri_wp_option_updates_array);
			$ri_wp_option_updates_chk=get_option($ri_wp_option_updates_txt);
			if (!$ri_wp_option_updates_chk) {
				add_option($ri_wp_option_updates_txt,$ri_wp_option_updates_array);
			} else {
				update_option($ri_wp_option_updates_txt,$ri_wp_option_updates_array);
			}
		}

		$cw_redirect_it_action='Building Redirect File';
		$cw_redirect_it_html .='<a href="?page=cw-redirect-it">Continue</a>';

	////////////////////////////////////////////////////////////////////////////
	//	Redirect Add & Edit
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'redirectadd' || $cw_action == 'redirectedit') {
		$ri_link_id='0';
		$ri_link_addts='';
		$ri_link_edits='';
		$ri_link_name='';
		$ri_link_code='';
		$ri_link_type='s';
		$ri_link_url='';
		$ri_link_notes='';
		$ri_link_aliases='';
		$ri_link_aliases_links='';

		$cw_redirect_it_action_btn='Add';
		if ($cw_action == 'redirectedit') {
			$cw_redirect_it_action_btn='Edit';

			$ri_link_id=$cwfa_ri->cwf_san_int($_REQUEST['ri_link_id']);

			$myrows=$wpdb->get_results("SELECT ri_link_addts,ri_link_edits,ri_link_name,ri_link_code,ri_link_type,ri_link_url,ri_link_notes,ri_link_aliases FROM $cw_redirect_it_tbl where ri_link_id='$ri_link_id'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$ri_link_addts=$cwfa_ri->cwf_san_int($myrow->ri_link_addts);
					$ri_link_edits=$cwfa_ri->cwf_san_int($myrow->ri_link_edits);
					$ri_link_name=$cwfa_ri->cwf_san_ans($myrow->ri_link_name);
					$ri_link_code=$cwfa_ri->cwf_san_alls(stripslashes($myrow->ri_link_code));
					$ri_link_type=$cwfa_ri->cwf_san_an($myrow->ri_link_type);
					$ri_link_url=$cwfa_ri->cwf_san_url(stripslashes($myrow->ri_link_url));
					$ri_link_notes=$cwfa_ri->cwf_san_alls(stripslashes($myrow->ri_link_notes));
					$ri_link_aliases=$cwfa_ri->cwf_san_ansrp(stripslashes($myrow->ri_link_aliases));
				}
			}
		}

		$cw_redirect_it_action=$cw_redirect_it_action_btn.'ing Redirect';
		$cw_action .='sv';

		$redirect_type_list='';
		foreach ($redirect_types as $redirect_type_id => $redirect_type_name) {
			$redirect_type_list .='<input type="radio" name="ri_link_type" value="'.$redirect_type_id.'"';
			if ($redirect_type_id == $ri_link_type) {
				$redirect_type_list .=' checked';
			}
			$redirect_type_list .='>'.$redirect_type_name.'&nbsp;&nbsp;&nbsp;';
		}

		if ($ri_link_aliases) {
			$ri_link_aliases=preg_replace('/\|/',"\n",$ri_link_aliases);
		}

		if ($cw_action == 'redirecteditsv') {

			//	Build redirect link
			$disp_redirect_name=$cwfa_ri->cwf_fmt_sth(strtolower($ri_link_name));
			$disp_redirect_url=$redirect_site_url;
			if ($redirect_url_type == 'a') {
				$disp_redirect_url .=$disp_redirect_name;
			} else {
				$disp_redirect_url .=$redirect_filename.'?l='.$disp_redirect_name;
			}

			//	Dates
			$ri_link_addts=$cwfa_ri->cwf_dt_fmt($ri_link_addts);
			if ($ri_link_edits == '0') {
				$ri_link_edits='Never';
			} else {
				$ri_link_edits=$cwfa_ri->cwf_dt_fmt($ri_link_edits);
			}

$cw_redirect_it_html .=<<<EOM
<p>$pplink</p>
<hr style="width: 400px; border: 1px dotted #000000;" align="left">Profile: $ri_link_name<br>Added: $ri_link_addts || Last Edited: $ri_link_edits<hr style="width: 400px; border: 1px dotted #000000;" align="left">
<p>Redirect URL: <div style="margin: -12px 0px 5px 20px; font-size: 11px;">If redirect does NOT work rebuild redirect file!</div><a href="$disp_redirect_url" target="_blank">$disp_redirect_url</a></p>
<p><form method="get" style="margin: 0px; padding: 0px;"><textarea name="disp_redirect_url" style="width: 400px; height: 100px;">$disp_redirect_url</textarea></form></p>
<hr style="width: 400px; border: 1px dotted #000000;" align="left">Edit Redirect Record<hr style="width: 400px; border: 1px dotted #000000;" align="left">
EOM;
			if ($ri_link_aliases) {
				$ri_link_aliases_links=explode("\n",$ri_link_aliases);
				array_pop($ri_link_aliases_links);
				$ri_alias_cnt='0';
				foreach ($ri_link_aliases_links as $ri_link_aliases_link) {
					$ri_link_aliases_link=$cwfa_ri->cwf_fmt_sth($ri_link_aliases_link);
					if ($redirect_url_type == 'a') {
						$ri_link_aliases_link=$redirect_site_url.$ri_link_aliases_link;
					} else {
						$ri_link_aliases_link=$redirect_site_url.$redirect_filename.'?l='.$ri_link_aliases_link;
					}
					//$ri_link_aliases_link='<a href="'.$ri_link_aliases_link.'" target="_blank">'.$ri_link_aliases_link.'</a>';
					$ri_link_aliases_links[$ri_alias_cnt]=$ri_link_aliases_link;
					$ri_alias_cnt++;
				}
				if ($ri_link_aliases_links) {
					$ri_link_aliases_links=implode("\n",$ri_link_aliases_links);
				}
			}
		}

$cw_redirect_it_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="$cw_action">
<input type="hidden" name="ri_link_id" value="$ri_link_id">
<p>Name:<div style="margin: -12px 0px 5px 20px; font-size: 11px;">Becomes part of URL, upper case converted to lower case, and spaces converted to hyphens.  IMPORTANT: If you change the name you'll need to add the old one as an alias to still reach destination URL.</div><input type="text" name="ri_link_name" value="$ri_link_name" style="width: 300px;"></p>
<p>Destination URL:<div style="margin: -12px 0px 5px 20px; font-size: 11px;">Paste FULL destination URL including http:// or https://</div><textarea name="ri_link_url" style="width: 400px; height: 100px;">$ri_link_url</textarea></p>
<p>Type: $redirect_type_list</p>
<p>Notes:<div style="margin: -12px 0px 5px 20px; font-size: 11px;">500 characters max; not included in redirect file</div><textarea name="ri_link_notes" style="width: 400px; height: 100px;">$ri_link_notes</textarea></p>
<p>Aliases:<div style="margin: -12px 0px 5px 20px; font-size: 11px;">You may enter additional names for this link.  All aliases will use the above destination URL.  Enter one alias per line.  Spaces will be converted to hyphens and all characters will be converted to lower case.</div><textarea name="ri_link_aliases" style="width: 400px; height: 100px;">$ri_link_aliases</textarea></p>
EOM;

if ($ri_link_aliases_links) {
$cw_redirect_it_html .=<<<EOM
<p><div name="aliaslinks" id="aliaslinks"><p>Aliases Links: <a href="javascript:void(0);" onclick="document.getElementById('aliaslinks').style.display='none';document.getElementById('saliaslinks').style.display='';">Show/Open</a></p></div><div name="saliaslinks" id="saliaslinks" style="display: none;"><p>Aliases Links: <a href="javascript:void(0);" onclick="document.getElementById('aliaslinks').style.display='';document.getElementById('saliaslinks').style.display='none';">Hide/Close</a><div style="margin: -12px 0px 5px 20px; font-size: 11px;">Below are saved link aliases in ready-to-go link format.</div><textarea name="ri_link_aliases_links" style="width: 400px; height: 100px;">$ri_link_aliases_links</textarea></p></div></p>
EOM;
}

$cw_redirect_it_html .=<<<EOM
<p><input type="submit" value="$cw_redirect_it_action_btn" class="button">
</form>
EOM;

		if ($cw_action == 'redirecteditsv') {
$cw_redirect_it_html .=<<<EOM
<div id="redirect_del_link" name="redirect_del_link" style="border-top: 1px solid #d6d6cf; margin-top: 20px; padding: 5px; width: 390px;"><a href="javascript:void(0);" onclick="document.getElementById('redirect_del_controls').style.display='';document.getElementById('redirect_del_link').style.display='none';">Show deletion controls</a></div>
<div name="redirect_del_controls" id="redirect_del_controls" style="display: none; width: 390px; margin-top: 20px; border: 1px solid #d6d6cf; padding: 5px;">
<a href="javascript:void(0);" onclick="document.getElementById('redirect_del_controls').style.display='none';document.getElementById('redirect_del_link').style.display='';">Hide deletion controls</a>
<form method="post">
<input type="hidden" name="cw_action" value="redirectdel"><input type="hidden" name="ri_link_id" value="$ri_link_id"><input type="hidden" name="ri_link_name" value="$ri_link_name">
<p><input type="checkbox" name="ri_confirm_1" value="1"> Check to delete $ri_link_name</p>
<p><input type="checkbox" name="ri_confirm_2" value="1"> Check to confirm deletion of $ri_link_name</p>
<p><span style="color: #ff0000; font-weight: bold;">Deletion is final! There is no undoing this action!</span></p>
<p style="text-align: right;"><input type="submit" value="Delete" class="button"></p>
</div>
EOM;
		}

	////////////////////////////////////////////////////////////////////////////
	//	Redirect Add & Edit Save
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'redirectaddsv' || $cw_action == 'redirecteditsv') {
		$ri_link_id=$cwfa_ri->cwf_san_int($_REQUEST['ri_link_id']);
		$ri_link_addts=time();
		$ri_link_edits=time();
		$ri_link_name=$cwfa_ri->cwf_san_ans($_REQUEST['ri_link_name']);
		$ri_link_code=$cwfa_ri->cwf_san_alls($_REQUEST['ri_link_code']);
		$ri_link_type=$cwfa_ri->cwf_san_an($_REQUEST['ri_link_type']);
		$ri_link_url=$cwfa_ri->cwf_san_url($_REQUEST['ri_link_url']);
		$ri_link_notes=$cwfa_ri->cwf_san_alls($_REQUEST['ri_link_notes']);
		$ri_link_aliases=$cwfa_ri->cwf_san_ansrp($_REQUEST['ri_link_aliases']);

		$error='';

		//	Redirect name
		if (!$ri_link_name) {
			$error .='<li>No redirect name provided</li>';
		} else {
			//	Verify unique name
			$ri_link_id_chk='0';
			$myrows=$wpdb->get_results("SELECT ri_link_id FROM $cw_redirect_it_tbl where ri_link_name='$ri_link_name'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$ri_link_id_chk=$cwfa_ri->cwf_san_int($myrow->ri_link_id);
				}
			}
			if ($ri_link_id_chk > '0' and $ri_link_id_chk != $ri_link_id) {
				$error .='<li>Redirect name already in use as profile name</li>';
			}

			if ($ri_link_aliases) {
				//	Check aliases
				$ri_link_aliases=explode("\n",$ri_link_aliases);
				$ri_link_aliases=array_unique($ri_link_aliases);

				isset($ri_link_aliases_exist);
				$ri_link_loop_cnt='0';
				foreach ($ri_link_aliases as $ri_link_aliases_chk) {
					$ri_link_id_chk='0';
					$ri_link_aliases_chk=$cwfa_ri->cwf_fmt_striptrim($ri_link_aliases_chk);
					$ri_link_aliases_chk=$cwfa_ri->cwf_san_ans($ri_link_aliases_chk);
					if (!$ri_link_aliases_chk) {
						unset($ri_link_aliases[$ri_link_loop_cnt]);
					} else {
						$ri_link_aliases[$ri_link_loop_cnt]=$ri_link_aliases_chk;
						$myrows=$wpdb->get_results("SELECT ri_link_id FROM $cw_redirect_it_tbl where ri_link_name like '$ri_link_aliases_chk' or ri_link_aliases like '%$ri_link_aliases_chk|%'");
						if ($myrows) {
							foreach ($myrows as $myrow) {
								$ri_link_id_chk=$cwfa_ri->cwf_san_int($myrow->ri_link_id);
							}
						}
						if ($ri_link_id_chk > '0' and $ri_link_id_chk != $ri_link_id) {
							$ri_link_aliases_exist .=$ri_link_aliases_chk."\n";
						}
					}
					$ri_link_loop_cnt++;
				}

				if ($ri_link_aliases_exist) {
					$ri_link_aliases_exist=trim($ri_link_aliases_exist);
					$ri_link_aliases_exist=preg_replace('/\n/',', ',$ri_link_aliases_exist);
					$error .='<li>Redirect alias(es) already in use include: '.$ri_link_aliases_exist.'</li>';
				}
				unset($ri_link_aliases_exist);
			}
		}

		//	Redirect URL
		if (!$ri_link_url) {
			$error .='<li>No redirect URL provided</li>';
		}

		$cw_redirect_it_action='Error';
		if ($error) {
			$cw_redirect_it_html='Please fix the following:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
		} else {
			$cw_redirect_it_action='Success';

			if ($ri_link_aliases) {
				$ri_link_aliases=array_unique($ri_link_aliases);
				$ri_link_aliases=implode('|',$ri_link_aliases);
				$ri_link_aliases .='|';
			}

			$data=array();
			$data['ri_link_name']=$ri_link_name;
			$data['ri_link_code']=$ri_link_code;
			$data['ri_link_type']=$ri_link_type;
			$data['ri_link_url']=$ri_link_url;
			$data['ri_link_notes']=$ri_link_notes;
			$data['ri_link_aliases']=$ri_link_aliases;
			
			if ($cw_action == 'redirecteditsv') {
				$data['ri_link_edits']=$ri_link_edits;
				$where=array();
				$where['ri_link_id']=$ri_link_id;
				$wpdb->update($cw_redirect_it_tbl,$data,$where);
			} else {
				$data['ri_link_addts']=$ri_link_addts;
				$wpdb->insert($cw_redirect_it_tbl,$data);
				$ri_link_id=$wpdb->insert_id;
			}

			$cw_redirect_it_html='<p>'.$ri_link_name.' has been successfully saved!</p>';
			$cw_redirect_it_html .='<p> Now what? <a href="?page=cw-redirect-it&cw_action=redirectedit&ri_link_id='.$ri_link_id.'">View Redirect Record</a> or <a href="?page=cw-redirect-it">Main Panel</a></p>';

			//	Log database update
			$ri_wp_option_updates_array['redirect_db_update']=time();
			$ri_wp_option_updates_array['redirect_file_update']=$redirect_file_update;
			$redirect_db_update=$ri_wp_option_updates_array['redirect_db_update'];

			$ri_wp_option_updates_array=serialize($ri_wp_option_updates_array);
			$ri_wp_option_updates_chk=get_option($ri_wp_option_updates_txt);
			if (!$ri_wp_option_updates_chk) {
				add_option($ri_wp_option_updates_txt,$ri_wp_option_updates_array);
			} else {
				update_option($ri_wp_option_updates_txt,$ri_wp_option_updates_array);
			}
		}

	////////////////////////////////////////////////////////////////////////////
	//	Redirect Search
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'redirectsearch') {
		$search_results='';
		$pgprevnxt='';
		$pgnavlist='';
		$statusword='';
		$settings_ppg=$redirect_records_ppg;

		//	Load search box
		$sbox=trim($_REQUEST['sbox']);
		$sbox=stripslashes($sbox);
		if (!$sbox) {
			$sbox='%';
		}
		$sboxlink=urlencode($sbox);
		$sbox=addslashes($sbox);

		$sstatus=$_REQUEST['sstatus'];
		if ($sstatus == 'all') {
			$ri_wheresql='';
			$statusword='all statuses';
		} else {
			$ri_wheresql="ri_link_type='$sstatus' and";
			$statusword=$redirect_types[$sstatus].' status';
		} 
		$ri_wheresql .='(ri_link_name like "%'.$sbox.'%" or ri_link_url like "%'.$sbox.'%" or ri_link_notes like "%'.$sbox.'%" or ri_link_aliases like "%'.$sbox.'%")';
		$ri_wherelink="sbox=$sboxlink&sstatus=$sstatus";
		$ri_form='<input type="hidden" name="sbox" value="'.$sbox.'"><input type="hidden" name="sstatus" value="'.$sstatus.'">';

		//	Matching record count
		$search_cnt='0';
		$myrows=$wpdb->get_results("SELECT count(ri_link_id) as ri_link_id_cnt FROM $cw_redirect_it_tbl where $ri_wheresql");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$search_cnt=$myrow->ri_link_id_cnt;
			}
		}

		//	Max page count
		$tpgs=$search_cnt/$settings_ppg;
		if (substr_count($tpgs,'.') > '0') {
			list($tpgs,$tpgsdiscard)=explode('.',$tpgs);
			$tpgs++;
		}

		//	Load page count
		if (isset($_REQUEST['spg'])) {
			$spg=$cwfa_ri->cwf_san_int($_REQUEST['spg']);
			if (!$spg) {
				$spg='1';
			}
		} else {
			$spg='1';
		}

		//	Page count can't exceed max pages
		if ($spg > $tpgs) {
			$spg=$tpgs;
		}
		$cw_page_txt='Page: '.$spg.' of '.$tpgs;

		//	Get records
		$snum=($spg-1)*$settings_ppg;
		if ($snum < '0') {
			$snum='0';
		}
		$enum=$snum;
		$myrows=$wpdb->get_results("SELECT ri_link_id,ri_link_name FROM $cw_redirect_it_tbl where $ri_wheresql order by ri_link_name limit $snum,$settings_ppg");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$ri_link_id=$myrow->ri_link_id;
				$ri_link_name=$myrow->ri_link_name;
				$enum++;
				$enum=$cwfa_ri->cwf_fmt_tho($enum);
				$search_results .='<li>'.$enum.') <a href="?page=cw-redirect-it&cw_action=redirectedit&ri_link_id='.$ri_link_id.'">'.$ri_link_name.'</a></li>';
				$enum=$cwfa_ri->cwf_san_int($enum);
			}
		}

		//	Show search text
		if ($search_results) {
			$snum++;
			$snum=$cwfa_ri->cwf_fmt_tho($snum);
			$enum=$cwfa_ri->cwf_fmt_tho($enum);
			$search_cnt=$cwfa_ri->cwf_fmt_tho($search_cnt);

			$search_results="<p>Displaying $snum to $enum out of $search_cnt</p><ul>$search_results</ul>";

			$snum=$cwfa_ri->cwf_san_int($snum);
			$enum=$cwfa_ri->cwf_san_int($enum);
			$search_cnt=$cwfa_ri->cwf_san_int($search_cnt);
		} else {
			$search_results='<li>Sorry, no matching records...  <a href="javascript:history.go(-1);">Continue</a></li>';
		}

		//	Build Page List
		if ($search_results) {
			$tpgsloop=$spg-4;
			$tpgsmax=$spg+3;

			if ($tpgsloop < '1') {
				$tpgsloop='0';
				$tpgsmax='7';
			}
			if ($spg > ($tpgs-6)) {
				$tpgsloop=$tpgs-7;
				$tpgsmax=$tpgs;
			}
			if ($tpgs < '9') {
				$tpgsloop='0';
				$tpgsmax=$tpgs;
			}
		
			while ($tpgsloop < $tpgsmax) {
				$tpgsloop++;
				if ($pgnavlist) {
					$pgnavlist .=' | ';
				}
				if ($tpgsloop == $spg) {
					$pgnavlist .=$tpgsloop;
				} else {
					$pgnavlist .='<a href="?page=cw-redirect-it&cw_action=redirectsearch&'.$ri_wherelink.'&spg='.$tpgsloop.'">'.$tpgsloop.'</a>';
				}
			}
			if ($pgnavlist) {
				if ($spg != '1') {
					$spgpx=$spg-1;
					$pgprevnxt='<a href="?page=cw-redirect-it&cw_action=redirectsearch&'.$ri_wherelink.'&spg='.$spgpx.'">Previous Page</a>';
				}
				if ($spg != $tpgs) {
					$spgpx=$spg+1;
					if ($pgprevnxt) {
						$pgprevnxt .=' | ';
					}
					$pgprevnxt .='<a href="?page=cw-redirect-it&cw_action=redirectsearch&'.$ri_wherelink.'&spg='.$spgpx.'">Next Page</a>';
				}
				if ($pgprevnxt) {
					$pgprevnxt=' .:. '.$pgprevnxt;
				}

				//	Show page list if more than one page
				if ($tpgs > '1') {
					$pgnavlist="<p>$cw_page_txt$pgprevnxt</p><p>Pages: $pgnavlist</p>";
				} else {
					$pgnavlist="<p>$cw_page_txt$pgprevnxt</p>";
				}

				if ($tpgs > '8') {
					$pgnavlist .='<p><form method="post" style="margin: 0px; 0px;"><input type="hidden" name="cw_action" value="redirectsearch">'.$fs_form.'Jump To Page: <input type="text" name="spg" style="width: 40px;"> of '.$tpgs.' <input type="submit" value="Go" class="button"></form></p>';
				}
			} else {
				$pgnavlist='&nbsp;';
			}
		}

		$cw_redirect_it_action='Searching Redirects';
$cw_redirect_it_html .=<<<EOM
<p>Results for: <b>$sbox</b> with $statusword</p>
$search_results
$pgnavlist
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Redirect Delete
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'redirectdel') {
		$ri_link_id=$cwfa_ri->cwf_san_int($_REQUEST['ri_link_id']);
		if (isset($_REQUEST['ri_confirm_1'])) {
			$ri_confirm_1=$cwfa_ri->cwf_san_int($_REQUEST['ri_confirm_1']);
		} else {
			$ri_confirm_1='0';
		}
		if (isset($_REQUEST['ri_confirm_2'])) {
			$ri_confirm_2=$cwfa_ri->cwf_san_int($_REQUEST['ri_confirm_2']);
		} else {
			$ri_confirm_2='0';
		}
		$ri_link_name=urldecode($_REQUEST['ri_link_name']);

		$cw_redirect_it_action='Deleting Redirect';

		if (!$ri_link_id) {
			$ri_confirm_1='0';
		}

		if ($ri_confirm_1 == '1' and $ri_confirm_2 == '1') {
			$where=array();
			$where['ri_link_id']=$ri_link_id;
			$wpdb->delete($cw_redirect_it_tbl,$where);
			$cw_redirect_it_html=$ri_link_name.' redirect has been removed! Don\'t forget to build redirect file to capture this deletion! <a href="?page=cw-redirect-it">Continue...</a>';

			//	Log database update
			$ri_wp_option_updates_array['redirect_db_update']=time();
			$ri_wp_option_updates_array['redirect_file_update']=$redirect_file_update;
			$redirect_db_update=$ri_wp_option_updates_array['redirect_db_update'];

			$ri_wp_option_updates_array=serialize($ri_wp_option_updates_array);
			$ri_wp_option_updates_chk=get_option($ri_wp_option_updates_txt);
			if (!$ri_wp_option_updates_chk) {
				add_option($ri_wp_option_updates_txt,$ri_wp_option_updates_array);
			} else {
				update_option($ri_wp_option_updates_txt,$ri_wp_option_updates_array);
			}
		} else {
			$cw_redirect_it_html='<span style="color: #ff0000;">Error! You must check both confirmation boxes!</span><br><br>'.$pplink;
		}

	////////////////////////////////////////////////////////////////////////////
	//	Settings
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settings' or $cw_action == 'settingsv') {
		$cw_redirect_it_action='View';

		if ($cw_action == 'settingsv') {
			$cw_redirect_it_action='Sav';
			$error='';

			$ri_wp_option_array=array();

			$redirect_filename=$cwfa_ri->cwf_san_filename($_REQUEST['redirect_filename']);
			if (!$redirect_filename) {
				$error .='<li>No Redirect File Name</li>';
			} else {
				$ri_wp_option_array['redirect_filename']=$redirect_filename;
			}

			$redirect_abspath=$cwfa_ri->cwf_san_abspath($_REQUEST['redirect_abspath']);
			if (!$redirect_abspath) {
				$error .='<li>Invalid Absolute Path To Redirect File</li>';
			} else {
				if (file_exists($redirect_abspath)) {
					$redirect_abspath=$cwfa_ri->cwf_trailing_slash_on($redirect_abspath);
					$ri_wp_option_array['redirect_abspath']=$redirect_abspath;
				} else {
					$error .='<li>Invalid Absolute Path To Redirect File</li>';
				}
			}

			$redirect_site_url=$cwfa_ri->cwf_san_url($_REQUEST['redirect_site_url']);
			if (!$redirect_site_url) {
				$error .='<li>No URL To Redirect File</li>';
			} else {
				$redirect_site_url=preg_replace('/http:\/\//','',$redirect_site_url);
				$redirect_site_url=preg_replace('/\/+/','/',$redirect_site_url);
				$redirect_site_url='http://'.$redirect_site_url;
				$redirect_site_url=$cwfa_ri->cwf_trailing_slash_on($redirect_site_url);
				$ri_wp_option_array['redirect_site_url']=$redirect_site_url;
			}

			$redirect_url_type=$cwfa_ri->cwf_san_an($_REQUEST['redirect_url_type']);
			if ($redirect_url_type != 'a') {
				$redirect_url_type='s';
			}
			$ri_wp_option_array['redirect_url_type']=$redirect_url_type;

			$redirect_no_match_url=$cwfa_ri->cwf_san_url($_REQUEST['redirect_no_match_url']);
			if (!$redirect_no_match_url) {
				$redirect_no_match_url=site_url();
			}
			$ri_wp_option_array['redirect_no_match_url']=$redirect_no_match_url;

			$redirect_records_ppg=$cwfa_ri->cwf_san_int($_REQUEST['redirect_records_ppg']);
			if (!$redirect_records_ppg or $redirect_records_ppg > '300' or $redirect_records_ppg < '10') {
				$redirect_records_ppg='50';
			}
			$ri_wp_option_array['redirect_records_ppg']=$redirect_records_ppg;

			if ($error) {
				$cw_redirect_it_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
			} else {
				$ri_wp_option_array=serialize($ri_wp_option_array);
				$ri_wp_option_chk=get_option($ri_wp_option);

				if (!$ri_wp_option_chk) {
					add_option($ri_wp_option,$ri_wp_option_array);
				} else {
					update_option($ri_wp_option,$ri_wp_option_array);
				}

				//	Writeable directory
				if (!is_writeable($redirect_abspath) and !file_exists("$redirect_abspath$redirect_filename")) {
					$cw_redirect_it_html='<div style="font-weight: bold; margin-bottom: 20px;">WARNING! WARNING! WARNING! Absolute path is NOT writable.<br>';
					$cw_redirect_it_html .='This is better security, however please manually create <u>'.$redirect_filename.'</u>, change permissions to 666, in <u>'.$redirect_abspath.'</u></div>';
				}

				$cw_redirect_it_html .='Settings have been saved! <a href="?page=cw-redirect-it">Continue to Main Menu</a>';
			}

		} else {
			$cw_redirect_it_action='Edit';

			if (!$redirect_filename) {
				$redirect_filename='redirectit.php';
			}

			if (!$redirect_abspath) {
				$redirect_abspath=plugin_dir_path(__FILE__);
				$redirect_abspath_discard='';
				list($redirect_abspath,$redirect_abspath_discard)=explode('wp-content',$redirect_abspath);
				unset($redirect_abspath_discard);
			}

			if (!$redirect_url_type) {
				$redirect_url_type='s';
			}

			if (!$redirect_site_url) {
				$redirect_site_url=site_url();
			}

			foreach ($redirect_url_types as $redirect_url_type_id => $redirect_url_type_name) {
				$redirect_url_type_list .='<option value="'.$redirect_url_type_id.'"';
				if ($redirect_url_type == $redirect_url_type_id) {
					$redirect_url_type_list .=' selected';
				}
				$redirect_url_type_list .='>'.$redirect_url_type_name.'</option>';
			}

			if (!$redirect_no_match_url) {
				$redirect_no_match_url=site_url();
			}

			if (!$redirect_records_ppg) {
				$redirect_records_ppg='50';
			}

$cw_redirect_it_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="settingsv">
<p>Redirect File Name:<div style="margin-left: 20px;">Set the name of the file that will hold your redirects (path below).  It should end in .php. <a href="javascript:void(0);" onclick="document.getElementById('cwfp').style.display='';">View file permission notes!</a>
<div style="display: none;" id="cwfp" name="cwfp"><p>The filename you specify below must be writeable by your website/server.</p>
<p>Most setups: The easy way is having the whole directory/folder this file will be located in writeable (chmod 777 or in permission tab check all).  The secure way is only having this file writeable (777 or 666), however for first time setups you'll need to manually create a file first (use "touch" or simply upload a file with this name).</p>
<p>Some hosts like HostGator don't allow files and folders/directories to use 666 or 777.  If this is the case 755 works.</p>
<p>Confused? Then just finish this setup and try to create the redirect file.  If it works great! If not upload any file from your computer to your hosting and name it redirectit.php (or whatever you choose below) then change the permissions to 777 or 666.  Now try to create the redirect file.</p>
</div>
</div></p>
<p><input type="text" name="redirect_filename" value="$redirect_filename" style="width: 150px;"></p>
<p>Absolute Path To Redirect File:<div style="margin-left: 20px;">Set the absolute path for the directory/folder that will contain the redirect file from above.  The plugin makes the best guess and enters that below.  However unless you want the redirect file in its own folder/directory you should be able to skip this setting.  If you are receiving errors then change this to "../" (no quotes though).  That is dot dot forward slash</div></p>
<p><input type="text" name="redirect_abspath" value="$redirect_abspath" style="width: 400px;"></p>
<p>URL Type:<div style="margin-left: 20px;">More information in "Help Guide".  If you are unsure just leave as "Standard".</div></p>
<p><select name="redirect_url_type">$redirect_url_type_list</select></p>
<p>URL To Redirect File (excluding file name):<div style="margin-left: 20px;">This depends on the "URL Type" setting:<br><br>Standard: Set the URL to redirect file name, excluding the redirect file name itself.<br>Advanced: Customize subdirectory, but remember to setup your .htaccess (httpd.conf) for proper redirect</div></p>
<p><input type="text" name="redirect_site_url" value="$redirect_site_url" style="width: 400px;"></p>
<p>Redirect For No Match:<div style="margin-left: 20px;">If no redirect URL is found for requested link where should visitor be sent?</div></p>
<p><input type="text" name="redirect_no_match_url" value="$redirect_no_match_url" style="width: 400px;"></p>
<p>Redirects Per Page:<div style="margin-left: 20px;">When running searches how many redirect records should be displayed per page? 10-300</div></p>
<p><input type="text" name="redirect_records_ppg" value="$redirect_records_ppg" style="width: 50px;"></p>
<p><input type="submit" value="Save" class="button">
</form>
EOM;
		}
		$cw_redirect_it_action .='ing Settings';

	////////////////////////////////////////////////////////////////////////////
	//	Help Guide
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingshelp') {
		$cw_redirect_it_action='Help Guide';

$cw_redirect_it_html .=<<<EOM
<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Introduction:</div>
<p>This system allows you to easily manage link redirects. By using this plugin you may easily control external (or even internal) links with ease. After adding a destination link into the system you will be provided with a link at your domain name. This provides several advantages.</p>
<p>First if the destination link ever changes no problem. You simply change it in one location and all links to it are updated. Second you are building your domain brand since the outbound links use your domain. Third there is no way for a visitor to tell the link destination without clicking on it, which helps save affiliate commissions. Fourth it works well for emails as you can shorten outbound links with variables in them.</p>
<p>Steps:</p>
<ol>
<li>Setup the information in Settings.  If necessary setup rewrite rule. (See Advanced URL Type section)</li>
<li>Add some redirects.  Do keep in mind no two redirects may have the same name as this system uses the name as part of the link redirect URL.  (Why not use a number? We don't want people to be able to simply start trying your redirects by changing a number.  You might have some secret ones! ;-)</li>
<li>Run the Build Redirect File function.</li>
<li>View redirect entries/records to get outgoing links and start using them on your site, in your emails, and around the Internet.</li>
<li>Add and edit redirects as needed, but don't forget to run the Build Redirect File function.</li>
</ol>

<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Advanced URL Type:</div>
<p>This system supports the ability to setup an advanced URL for redirecting your destination URLs.  In the standard mode the redirect URL will contain the php redirect file name.  However you have the option of omitting that and instead using a directory/folder name.  For example instead of http://www.mydomain.tld/redirectit.php?l=LINKNAME you might want http://www.mydomain.tld/ro/LINKNAME which is cleaner.</p>

<p>In order to perform this action you must setup your .htaccess (or httpd.conf) file to properly translate the directory/folder LINKNAME into the necessary variable and pass it to the redirect script.  In the following section you will see the code that will work for many Wordpress setups, but keep in mind your environment could require different code.</p>

<p>You can even get more advanced and use multiple directories like http://www.mydomain.tld/redirects/out/LINKNAME or even subdomains like http://ro.mydomain.tld/LINKNAME as long as the subdomain can read the directory that will hold the redirect php file.  There are many different possible setups.  If you find this all confusing then just stick with the standard method.  You can always change it later with help and/or learning and even keep your old redirects working using the powerful .htaccess (httpd.conf).</p>

<p>Please note there are infinite possible setups and it is impossible to write a guide to give the correct .htaccess code to cover them all.  This section should be viewed as a general overview and NOT the ultimate answer for your .htaccess file.</p>

<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Advanced settings URL type sample .htaccess code:</div>
# Wordpress Redirect It Plugin<br>
RewriteRule ^ro/(.*)$ ./redirectit.php?l=$1 [L]
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	What Is New?
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingsnew') {
		$cw_redirect_it_action='What Is New?';

$cw_redirect_it_html .=<<<EOM
<p>The following lists the new changes from version-to-version.</p>
<p>Version: <b>1.4</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Link alias support</li>
</ul>
<p>Version: <b>1.3</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>UI changes</li>
<li>Fixed: Typos</li>
</ul>
<p>Version: <b>1.2</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Permissions check bug fix</li>
<li>Added additional notes to redirect file permissions (Settings screen)</li>
<li>Added footer links</li>
</ul>
<p>Version: <b>1.1</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Altered framework code to fit Wordpress Plugin Directory terms</li>
</ul>
<p>Version: <b>1.0</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Initial release of plugin</li>
</ul>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Main panel
	////////////////////////////////////////////////////////////////////////////
	} else {

		//	Count Redirects
		$cw_redirect_it_count='';
		$myrows=$wpdb->get_results("SELECT count(ri_link_id) as ri_link_cnt FROM $cw_redirect_it_tbl");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_redirect_it_count=$cwfa_ri->cwf_fmt_tho($myrow->ri_link_cnt);
			}
		}

		//	Build redirect type list
		$redirect_type_list='';
		foreach ($redirect_types as $redirect_type_id => $redirect_type_name) {
			$redirect_type_list .='<option value="'.$redirect_type_id.'">'.$redirect_type_name.'</option>';
		}

$cw_redirect_it_action='Main Panel';
$cw_redirect_it_html .=<<<EOM
<p>Redirects: $cw_redirect_it_count&nbsp;&nbsp;&nbsp;(<a href="?page=cw-redirect-it&cw_action=redirectadd">Add Redirect</a>)</p>
<div style="width: 400px; text-align: center;">
<form method="post" style="margin: 0px; padding: 0px;">
<input type="hidden" name="cw_action" value="redirectsearch">
Search: <input type="text" name="sbox" style="width: 150px;"> with <select name="sstatus"><option value="all"> All statuses</option>$redirect_type_list</select> <input type="submit" value="Go" class="button">
<div style="margin-top: 5px; width: 350px; font-size: 10px; font-style: italic;">Just hit "Go" to bring up all records</div>
</form>
</div>
EOM;
	}

	////////////////////////////////////////////////////////////////////////////
	//	Verify redirect file is up-to-date
	////////////////////////////////////////////////////////////////////////////
	if ($redirect_file_update < $redirect_db_update) {
		$redirect_file_status='<span style="color: #ff0000">Needs updating!</span>';
	}

	////////////////////////////////////////////////////////////////////////////
	//	Send to print out
	////////////////////////////////////////////////////////////////////////////
	cw_redirect_it_admin_browser($cw_redirect_it_html,$cw_redirect_it_action,$redirect_file_status);
}

////////////////////////////////////////////////////////////////////////////
//	Print out to browser (wp)
////////////////////////////////////////////////////////////////////////////
function cw_redirect_it_admin_browser($cw_redirect_files_html,$cw_redirect_it_action,$redirect_file_status) {
$cw_plugin_name='cleverwise-redirect-it';
print <<<EOM
<style type="text/css">
#cws-wrap {margin: 20px 20px 20px 0px;}
#cws-wrap a {text-decoration: none; color: #3991bb;}
#cws-wrap a:hover {text-decoration: underline; color: #ce570f;}
#cws-nav {width: 400px; padding: 0px; margin-top: 10px; background-color: #deeaef; -moz-border-radius: 5px; border-radius: 5px;}
#cws-resources {width: 400px; padding: 0px; margin: 40px 0px 20px 0px; background-color: #c6d6ad; -moz-border-radius: 5px; border-radius: 5px; font-size: 12px; color: #000000;}
#cws-resources a {text-decoration: none; color: #28394d;}
#cws-resources a:hover {text-decoration: none; background-color: #28394d; color: #ffffff;}
#cws-inner {padding: 5px;}
</style>
<div id="cws-wrap" name="cws-wrap">
<h2 style="padding: 0px; margin: 0px;">Cleverwise Redirect It Management</h2>
<div style="margin-top: 7px; width: 90%; font-size: 10px; line-height: 1;">Manage link redirects easily through this powerful plugin. By using this plugin you may easily control external (or even internal) links with ease. After adding a destination link into the system you will be provided with a link at your domain name.</div>
<div id="cws-nav" name="cws-nav"><div id="cws-inner" name="cws-inner"><a href="?page=cw-redirect-it">Main Panel</a> | <a href="?page=cw-redirect-it&cw_action=settings">Settings</a> | <a href="?page=cw-redirect-it&cw_action=settingshelp">Help Guide</a> | <a href="?page=cw-redirect-it&cw_action=settingsnew">What Is New?</a></div></div>
<p style="margin: 6px 0px -7px 0px;">* Redirect File Status: $redirect_file_status | <a href="?page=cw-redirect-it&cw_action=redirectbuild">Build Redirect File</a></p>
<p style="font-size: 13px; font-weight: bold;">Current: <span style="color: #ab5c23;">$cw_redirect_it_action</span></p>
<p>$cw_redirect_files_html</p>
<div id="cws-resources" name="cws-resources"><div id="cws-inner" name="cws-inner">Resources (open in new windows):<br>
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7VJ774KB9L9Z4" target="_blank">Donate - Thank You!</a> | <a href="http://wordpress.org/support/plugin/$cw_plugin_name" target="_blank">Get Support</a> | <a href="http://wordpress.org/support/view/plugin-reviews/$cw_plugin_name" target="_blank">Review Plugin</a> | <a href="http://www.cyberws.com/cleverwise-plugins/plugin-suggestion/" target="_blank">Suggest Plugin</a><br>
<a href="http://www.cyberws.com/cleverwise-plugins" target="_blank">Cleverwise Plugins</a> | <a href="http://www.cyberws.com/professional-technical-consulting/" target="_blank">Wordpress +PHP,Server Consulting</a></div></div>
</div>
EOM;
}

////////////////////////////////////////////////////////////////////////////
//	Activate
////////////////////////////////////////////////////////////////////////////
function cw_redirect_it_activate() {
	Global $wpdb,$ri_wp_option_version_txt,$ri_wp_option_version_num,$cw_redirect_it_tbl;
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');

	$ri_wp_option_db_version=get_option($ri_wp_option_version_txt);

//	Create category table
	$table_name=$cw_redirect_it_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS `$table_name` (
  `ri_link_id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `ri_link_addts` int(15) unsigned NOT NULL,
  `ri_link_edits` int(15) unsigned zerofill NOT NULL,
  `ri_link_name` varchar(150) NOT NULL,
  `ri_link_code` varchar(150) NOT NULL,
  `ri_link_type` char(1) NOT NULL,
  `ri_link_url` varchar(250) NOT NULL,
  `ri_link_notes` varchar(500) NOT NULL,
  PRIMARY KEY (`ri_link_id`),
  KEY `ri_link_name` (`ri_link_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
EOM;
	dbDelta($sql);
 
//	Insert version number
	if (!$ri_wp_option_db_version) {
		add_option($ri_wp_option_version_txt,$ri_wp_option_version_num);
	}
}
