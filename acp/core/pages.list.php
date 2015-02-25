<?php

//prohibit unauthorized access
require("core/access.php");

$dbh = new PDO("sqlite:".CONTENT_DB);

unset($result);
/* $_SESSION[filter_string] was defined in inc.pages.php */
$sql = "SELECT page_id, page_language, page_linkname, page_title, page_meta_description, page_sort, page_lastedit, page_lastedit_from, page_status, page_template, page_modul, page_authorized_users, page_permalink, page_redirect, page_redirect_code
		FROM fc_pages
		$_SESSION[filter_string]
		ORDER BY page_language ASC, page_sort ASC, page_linkname ASC";

$sth = $dbh->prepare($sql);
$sth->execute();
$result = $sth->fetchAll(PDO::FETCH_ASSOC);

$x=0;
foreach($result as $p) {
	$this_page_id = 'p'.$p['page_id'];
	$count_comments = $dbh->query("Select Count(*) FROM fc_comments WHERE comment_parent LIKE '$this_page_id' ")->fetch();
	$result[$x]['cnt_comments'] = $count_comments[0];
	$x++;
}

	
$dbh = null;
   
$cnt_result = count($result);

$result = fc_array_multisort($result, 'page_language', SORT_ASC, 'page_sort', SORT_ASC, SORT_NATURAL);

echo '<div class="row">';
echo '<div class="col-md-3">';
echo '<fieldset>';
echo '<legend>Filter</legend>';
echo $kw_form;
echo '<p style="padding:0;">' . $btn_remove_keyword . '</p>';
echo '</fieldset>';
echo '</div>';
echo '<div class="col-md-9">';
echo '<fieldset>';
echo '<legend>'.$lang['f_page_status'].'/'.$lang['f_page_language'].'</legend>';
echo $status_btn_group . ' ' . $lang_btn_group;
echo '</fieldset>';
echo '</div>';
echo '</div>';


echo '<div class="row">';
echo '<div class="col-lg-6">';

/**
 * list all pages where page_sort != empty
 */

echo '<fieldset>';
echo '<legend>' . $lang['legend_structured_pages'] . '</legend>';
echo '<div class="pages-list-container">';



for($i=0;$i<$cnt_result;$i++) {

	if($result[$i]['page_sort'] == "" || $result[$i]['page_sort'] == 'portal') {
		continue;
	}
	
	unset($show_redirect);

	$page_id = $result[$i]['page_id'];
	$page_sort = $result[$i]['page_sort'];
	$page_linkname = stripslashes($result[$i]['page_linkname']);
	$page_title = stripslashes($result[$i]['page_title']);
	$page_description = stripslashes($result[$i]['page_meta_description']);
	$page_status = $result[$i]['page_status'];
	$page_lastedit = $result[$i]['page_lastedit'];
	$page_lastedit_from = $result[$i]['page_lastedit_from'];
	$page_template = $result[$i]['page_template'];
	$page_authorized_users = $result[$i]['page_authorized_users'];
	$page_language = $result[$i]['page_language'];
	$page_permalink = $result[$i]['page_permalink'];
	$page_redirect = $result[$i]['page_redirect'];
	$page_redirect_code = $result[$i]['page_redirect_code'];
	$page_modul = $result[$i]['page_modul'];
	$page_cnt_comments = $result[$i]['cnt_comments'];
	
	if($page_template == "use_standard") {
		$show_template_name =  $lang['use_standard'];
	} else {
		$show_template_name = $page_template;
	}
	
	$points_of_page = substr_count($page_sort, '.');
	$indent = ($points_of_page-1)*10 . 'px';

	$pi = get_page_impression($page_id);
	
	if($page_status == "public") {
		$btn = 'ghost-btn-public';
		$item_class = 'page-list-item-public';
		$status_label = $lang['f_page_status_puplic'];
	} elseif($page_status == "ghost") {
		$btn = 'ghost-btn-ghost';
		$item_class = 'page-list-item-ghost';
		$status_label = $lang['f_page_status_ghost'];
	} elseif($page_status == "private") {
		$btn = 'ghost-btn-private';
		$item_class = 'page-list-item-private';
		$status_label = $lang['f_page_status_private'];
	} elseif($page_status == "draft") {
		$btn = 'ghost-btn-draft';
		$item_class = 'page-list-item-draft';
		$status_label = $lang['f_page_status_draft'];
	}
	
	$last_edit = date("d.m.Y H:i:s",$page_lastedit) . " ($page_lastedit_from)";
	
	/* check for display edit button */
	
	if($_SESSION['acp_editpages'] == "allowed"){
		$edit_button = "<a class='btn btn-sm btn-default' href='$_SERVER[PHP_SELF]?tn=pages&sub=edit&editpage=$page_id'><span class='glyphicon glyphicon-edit'></span> $lang[edit]</a>";
		$duplicate_button = "<a class='btn btn-sm btn-default' href='$_SERVER[PHP_SELF]?tn=pages&sub=edit&editpage=$page_id&duplicate=1'><span class='glyphicon glyphicon-duplicate'></span> $lang[duplicate]</a>";

	} else {
		$edit_button = '';
		$duplicate_button = '';
	}
	
	$arr_checked_admins = explode(",",$page_authorized_users);
	if(in_array("$_SESSION[user_nick]", $arr_checked_admins)) {
		$edit_button = "<a class='btn btn-sm btn-default' href='$_SERVER[PHP_SELF]?tn=pages&sub=edit&editpage=$page_id'><span class='glyphicon glyphicon-edit'></span> $lang[edit]</a>";
	}
	
	
	if($fc_mod_rewrite == "permalink") {
		$frontend_link = "../$page_permalink";
	} else {
		$frontend_link = "../index.php?p=$page_id";
	}
	
	$show_mod = '';
	if($page_modul != '') {
		$page_modul_title = substr($page_modul, 0,-4);
		$show_mod = ' <small><span class="glyphicon glyphicon-cog" title="'.$page_modul_title.'"></span></small>';
	}
	
	if($page_redirect != '') {
		if($_SESSION['checked_redirect'] != "checked") {
			continue;
		}
	}
	
	$page_comments_link = '<a class="fancybox-ajax btn btn-default btn-sm" href="/acp/core/ajax.comments.php?pid='.$page_id.'"><span class="glyphicon glyphicon-comment"></span></a>';
	
	
	
	$extra_info  = '<span class="label label-white"><span class="glyphicon glyphicon-time"></span> '.$last_edit.'</span> ';
	$extra_info .= '<span class="label label-white"><span class="glyphicon glyphicon-file"></span> '.$show_template_name.'</span> ';
	$extra_info .= '<span class="label label-white"><span class="glyphicon glyphicon-globe"></span> '.$page_language.'</span> ';
	$extra_info .= '<span class="label label-white"><span class="glyphicon glyphicon-comment"></span> '.$page_cnt_comments.'</span> ';	
	$extra_info2 = '';
	if($page_permalink != '') {
		$extra_info2 = '<span class="label label-white"><span class="glyphicon glyphicon-link"></span> '.$page_permalink.'</span> ';
	}
	if($page_redirect != '') {
		$extra_info2 .= '<span class="label label-white"><span class="text-primary glyphicon glyphicon-link"></span> '.$page_redirect.'</span> ';
	}
	$extra_info2 .= '<span class="label label-white"><span class="glyphicon glyphicon-stats"></span> ' . $pi.'</span> ';
	$extra_info2 .= '<span class="label label-white"><span class="glyphicon glyphicon-sort-by-attributes"></span> '.$page_sort.'</span>';
	
	echo '<div class="hiddenControls page-list-item '.$item_class.'" style="margin-left:'.$indent.';">';

	echo '<div class="label-page-status">'.$status_label.'</div>';
	echo '<h5><a class="ghost-btn '.$btn.'" href="'.$frontend_link.'" title="'.$frontend_link.'">'.$page_linkname.'</a> '.$page_title.' '.$show_mod.'</h5>';
	echo '<p class="extrainfo condensed" style="padding-left:10px;">'.$extra_info.'</p>';
	echo '<p class="extrainfo condensed" style="padding-left:10px;">'.$extra_info2.'</p>';
	echo '<div class="controls-container controls">';
	echo '<div class="controls-container-inner">';
	echo '<div class="btn-group">'.$edit_button.' '.$duplicate_button.' '.$page_comments_link.'</div>';
	echo '</div>';

	echo '</div>';

	echo '</div>';

}


echo '</div>';
echo '</fieldset>';

echo '</div>';
echo '<div class="col-lg-6">';

/**
 * list all pages where
 * page_sort == empty
 * or page_sort == portal
 */

echo '<fieldset>';
echo '<legend>' . $lang['legend_unstructured_pages'] . '</legend>';
echo '<div class="pages-list-container">';


for($i=0;$i<$cnt_result;$i++) {

	if($result[$i]['page_sort'] != "" && $result[$i]['page_sort'] != 'portal') {
		continue;
	}
	
	unset($show_redirect);

	$page_id = $result[$i]['page_id'];
	$page_sort = $result[$i]['page_sort'];
	$page_linkname = stripslashes($result[$i]['page_linkname']);
	$page_title = stripslashes($result[$i]['page_title']);
	$page_status = $result[$i]['page_status'];
	$page_lastedit = $result[$i]['page_lastedit'];
	$page_lastedit_from = $result[$i]['page_lastedit_from'];
	$page_template = $result[$i]['page_template'];
	$page_authorized_users = $result[$i]['page_authorized_users'];
	$page_language = $result[$i]['page_language'];
	$page_permalink = $result[$i]['page_permalink'];
	$page_redirect = $result[$i]['page_redirect'];
	$page_cnt_comments = $result[$i]['cnt_comments'];
		
	if($page_template == "use_standard") {
		$show_template_name =  "$lang[use_standard]";
	} else {
		$show_template_name = "$page_template";
	}
	
	if($page_sort == 'portal') {
		$page_linkname = '<span class="glyphicon glyphicon-home"></span> ' . $page_linkname;
	}
	
	$hits_id = $page_id;	
	if($page_sort == "portal") {
		$hits_id = "portal_$page_language";
	}
	
	$pi = get_page_impression($hits_id);
	
	if($page_status == "public") {
		$btn = 'ghost-btn-public';
		$item_class = 'page-list-item-public';
		$status_label = $lang['f_page_status_puplic'];
	} elseif($page_status == "ghost") {
		$btn = 'ghost-btn-ghost';
		$item_class = 'page-list-item-ghost';
		$status_label = $lang['f_page_status_ghost'];
	} elseif($page_status == "private") {
		$btn = 'ghost-btn-private';
		$item_class = 'page-list-item-private';
		$status_label = $lang['f_page_status_private'];
	} elseif($page_status == "draft") {
		$btn = 'ghost-btn-draft';
		$item_class = 'page-list-item-draft';
		$status_label = $lang['f_page_status_draft'];
	}
	
	$last_edit = date("d.m.Y H:i:s",$page_lastedit) . " ($page_lastedit_from)";
	
	/* check for display edit button */
	
	if($_SESSION['acp_editpages'] == "allowed"){
		$edit_button = "<a class='btn btn-sm btn-default' href='$_SERVER[PHP_SELF]?tn=pages&sub=edit&editpage=$page_id'><span class='glyphicon glyphicon-edit'></span> $lang[edit]</a>";
		$duplicate_button = "<a class='btn btn-sm btn-default' href='$_SERVER[PHP_SELF]?tn=pages&sub=edit&editpage=$page_id&duplicate=1'><span class='glyphicon glyphicon-duplicate'></span> $lang[duplicate]</a>";
	} else {
		$edit_button = '';
		$duplicate_button = '';
	}
	
	$arr_checked_admins = explode(",",$page_authorized_users);
	if(in_array("$_SESSION[user_nick]", $arr_checked_admins)) {
		$edit_button = "<a class='btn btn-sm btn-default' href='$_SERVER[PHP_SELF]?tn=pages&sub=edit&editpage=$page_id'><span class='glyphicon glyphicon-edit'></span> $lang[edit]</a>";
	}
	
	if($fc_mod_rewrite == "permalink") {
		$frontend_link = "../$page_permalink";
	} else {
		$frontend_link = "../index.php?p=$page_id";
	}
	
	if($page_redirect != '') {
		if($_SESSION['checked_redirect'] != "checked") {
			continue;
		}
	}

	$page_comments_link = '<a class="fancybox-ajax btn btn-default btn-sm" href="/acp/core/ajax.comments.php?pid='.$page_id.'"><span class="glyphicon glyphicon-comment"></span></a>';


		
	$extra_info  = '<span class="label label-white"><span class="glyphicon glyphicon-time"></span> '.$last_edit.'</span> ';
	$extra_info .= '<span class="label label-white"><span class="glyphicon glyphicon-file"></span> '.$show_template_name.'</span> ';
	$extra_info .= '<span class="label label-white"><span class="glyphicon glyphicon-globe"></span> '.$page_language.'</span> ';
	$extra_info .= '<span class="label label-white"><span class="glyphicon glyphicon-comment"></span> '.$page_cnt_comments.'</span> ';	
	$extra_info2 = '';
	if($page_permalink != '') {
		$extra_info2 = '<span class="label label-white"><span class="glyphicon glyphicon-link"></span> '.$page_permalink.'</span> ';
	}
	if($page_redirect != '') {
		$extra_info2 .= '<span class="label label-white"><span class="text-primary glyphicon glyphicon-link"></span> '.$page_redirect.'</span> ';
	}
	$extra_info2 .= '<span class="label label-white"><span class="glyphicon glyphicon-stats"></span> '.$pi.'</span> ';
	$extra_info2 .= '<span class="label label-white"><span class="glyphicon glyphicon-sort-by-attributes"></span> '.$page_sort.'</span>';
	
	echo '<div class="hiddenControls page-list-item '.$item_class.'" style="margin-left:-10px;">';

	echo '<div class="label-page-status">'.$status_label.'</div>';
	echo '<h5><a class="ghost-btn '.$btn.'" href="'.$frontend_link.'" title="'.$frontend_link.'">'.$page_linkname.'</a> '.$page_title.'</h5>';
	echo '<p class="extrainfo condensed" style="padding-left:10px;">'.$extra_info. '</p>';
	echo '<p class="extrainfo condensed" style="padding-left:10px;">'.$extra_info2.'</p>';
	echo '<div class="controls-container controls">';
	echo '<div class="controls-container-inner">';
	echo '<div class="btn-group">'.$edit_button.' '.$duplicate_button.' '.$page_comments_link.'</div>';
	echo '</div>';

	echo '</div>';

	echo '</div>';
		

} // eol for $i

echo '</div>';
echo"</fieldset>";

echo '</div>';
echo '</div>';

?>
