<?php

/*
Plugin Name: Category Overload
Plugin URI: http://alexking.org/projects/wordpress
Description: This plugin will allow you to better manage a very large category list.
Version: 1.1b1
Author: Alex King
Author URI: http://alexking.org
*/ 

// Copyright (c) 2006-2007 Alex King. All rights reserved.
// http://alexking.org/projects/wordpress
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

function akca_request_handler() {
	if (isset($_GET['ak_action'])) {
		switch ($_GET['ak_action']) {
			case 'akca_blank':
				die('<html></html>');
				break;
			case 'akca_select_cat':
				ak_select_cat();
				break;
		}
	}
}
add_action('init', 'akca_request_handler');

function ak_cat_rows($output) {
	ob_start();
	if (isset($_GET['ak_cat_parent'])) {
		$parent = intval($_GET['ak_cat_parent']);
		$parent_cat = get_category($parent);
	}
	else {
		$parent = 0;
	}
	if (isset($_GET['ak_offset'])) {
		$offset = intval($_GET['ak_offset']);
	}
	else {
		$offset = 0;
	}
	global $wpdb, $class;

	$categories = $wpdb->get_results("
		SELECT SQL_CALC_FOUND_ROWS 
		t.term_id AS cat_ID, tt.count AS category_count,
		tt.description AS category_description, t.name AS cat_name,
		t.slug AS category_nicename, tt.parent AS category_parent
		FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
		WHERE tt.taxonomy = 'category' AND tt.parent = '$parent'
		ORDER BY t.name LIMIT $offset, 50
	");
	$cat_count = intval($wpdb->get_var('SELECT FOUND_ROWS()'));
	
	$tools = '';

	if ($cat_count > 50 || $parent != 0) {
		if ($offset + 50 < $cat_count) {
			$next = $offset + 50;
			$tools .= '<a href="categories.php?ak_offset='.$next.'&ak_cat_parent='.$parent.'" style="float: right;">Next &raquo;</a>';
		}
		if ($offset > 0) {
			$prev = $offset - 50; 
			if ($prev < 0) {
				$prev = 0;
			}
			$tools .= '<a href="categories.php?ak_offset='.$prev.'&ak_cat_parent='.$parent.'" style="float: left;">&laquo; Previous</a>';
		}
		if ($parent != 0) {
			$tools .= '<a href="categories.php?ak_cat_parent='.$parent_cat->category_parent.'">Up a Level</a>';
		}
		$tools = '<tr><td colspan="6" style="background: #DEEEFC; text-align: center;">'.$tools.'</td></tr>';
	}

	print($tools);

	if ($categories) {
		foreach ($categories as $category) {
			$category->cat_name = wp_specialchars($category->cat_name);
			$pad = str_repeat('&#8212; ', $level);
			if ( current_user_can('manage_categories') ) {
				$edit = "<a href='categories.php?action=edit&amp;cat_ID=$category->cat_ID' class='edit'>".__('Edit')."</a></td>";
				$default_cat_id = get_option('default_category');

				if ($category->cat_ID != $default_cat_id)
					$edit .= "<td><a href='" . wp_nonce_url("categories.php?action=delete&amp;cat_ID=$category->cat_ID", 'delete-category_' . $category->cat_ID ) . "' onclick=\"return deleteSomething( 'cat', $category->cat_ID, '" . sprintf(__("You are about to delete the category &quot;%s&quot;.  All of its posts will go to the default category.\\n&quot;OK&quot; to delete, &quot;Cancel&quot; to stop."), js_escape($category->cat_name))."' );\" class='delete'>".__('Delete')."</a>";
				else
					$edit .= "<td style='text-align:center'>".__("Default");
			}
			else
				$edit = '';

			$class = ('alternate' == $class) ? '' : 'alternate';
			echo "<tr id='cat-$category->cat_ID' class='$class'>
							<th scope='row'>$category->cat_ID</th>
							<td><a href='categories.php?ak_cat_parent=$category->cat_ID'>$category->cat_name</a></td>
							<td>$category->category_description</td>
							<td>$category->category_count</td>
							<td>$edit</td>
							</tr>";
		}
	} else {
		echo '<tr><td colspan="6" style="text-align: center;">No categories found.</td></tr>';
	}

	print($tools);
	$output = ob_get_contents();
	ob_end_clean();
	
	return $output;
}
add_action('cat_rows', 'ak_cat_rows');

function ak_cat_select_rows() {
	if (isset($_GET['ak_cat_parent'])) {
		$parent = intval($_GET['ak_cat_parent']);
	}
	else {
		$parent = 0;
	}
	if (isset($_GET['ak_offset'])) {
		$offset = intval($_GET['ak_offset']);
	}
	else {
		$offset = 0;
	}
	global $wpdb, $class;

	$categories = $wpdb->get_results("
		SELECT SQL_CALC_FOUND_ROWS 
		t.term_id AS cat_ID, tt.count AS category_count,
		tt.description AS category_description, t.name AS cat_name,
		t.slug AS category_nicename, tt.parent AS category_parent
		FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
		WHERE tt.taxonomy = 'category' AND tt.parent = '$parent'
		ORDER BY t.name LIMIT $offset, 50
	");
	$cat_count = $wpdb->get_var('SELECT FOUND_ROWS()');

	$tools = '';

	if ($cat_count > 50) {
		if ($offset + 50 < $cat_count) {
			$next = $offset + 50;
			$tools .= '<a href="options-general.php?ak_action=akca_select_cat&ak_cat_parent='.$parent.'&ak_offset='.$next.'" style="float: right;">Next &raquo;</a>';
		}
		if ($offset > 0) {
			$prev = $offset - 50; 
			if ($prev < 0) {
				$prev = 0;
			}
			$tools .= '<a href="options-general.php?ak_action=akca_select_cat&ak_cat_parent='.$parent.'&ak_offset='.$prev.'">&laquo; Previous</a>';
		}
		$tools = '<tr><td colspan="5" style="background: #DEEEFC;">'.$tools.'</td></tr>';
	}
	
	print($tools);

	if ($categories) {
		foreach ($categories as $category) {
			$category->cat_name = wp_specialchars($category->cat_name);
			$pad = str_repeat('&#8212; ', $level);

			$class = ('alternate' == $class) ? '' : 'alternate';
			echo "<tr id='cat-$category->cat_ID' class='$class'>
							<th scope='row'>$category->cat_ID</th>
							<td><a href='options-general.php?ak_action=akca_select_cat&ak_cat_parent=$category->cat_ID'>$category->cat_name</a></td>
							<td>$category->category_description</td>
							".'<td style="text-align: center"><a href="#" onclick="top.document.getElementById(\'ak_cat_parent_display\').innerHTML=\''.$category->cat_name.'\'; top.document.getElementById(\'category_parent\').value=\''.$category->cat_ID.'\'; top.document.getElementById(\'ak_cat_chooser\').style.display=\'none\'; ">Select</a></td>
							</tr>';
		}
	} else {
		echo '<tr><td colspan="4" style="text-align: center";>No categories found.</td></tr>';
	}
	
	print($tools);
}

function ak_edit_category_form($category) {
	if ($category->category_parent != 0) {
		$parent = get_category_to_edit($category->category_parent);
		$parent_name = $parent->cat_name;
	}
	else {
		$parent_name = 'None';
	}
	$parent_id = $category->category_parent;
    ?>

<div class="wrap">
 <h2><?php _e('Edit Category') ?></h2>
 <form name="editcat" action="categories.php" method="post">
	  <?php wp_nonce_field('update-category_' .  $category->cat_ID); ?>
	  <table class="editform" width="100%" cellspacing="2" cellpadding="5">
		<tr>
		  <th width="33%" scope="row"><?php _e('Category name:') ?></th>
		  <td width="67%"><input name="cat_name" type="text" value="<?php echo wp_specialchars($category->cat_name); ?>" size="40" /> <input type="hidden" name="action" value="editedcat" />
<input type="hidden" name="cat_ID" value="<?php echo $category->cat_ID ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Category slug:') ?></th>
			<td><input name="category_nicename" type="text" value="<?php echo wp_specialchars($category->category_nicename); ?>" size="40" /></td>
		</tr>
		<tr>
			<th scope="row" style="vertical-align: top; padding-top: 17px;"><?php _e('Category parent:') ?></th>
			<td>        
				<p>
					<strong id="ak_cat_parent_display"><?php print($parent_name); ?></strong> (<a href="#" onclick="document.getElementById('ak_cat_chooser').style.display='block'; document.getElementById('ak_cat_chooser_iframe').src='options-general.php?ak_action=akca_select_cat&ak_cat_parent=0'; return false;">Change</a>)
					<input type="hidden" id="category_parent" name="category_parent" value="<?php print($parent_id); ?>" />
				</p>
				<div id="ak_cat_chooser" style="display: none;">
					<a href="#" onclick="document.getElementById('ak_cat_chooser').style.display='none'; return false;" style="float: right;">Close</a>
					<h3><?php _e('Categories'); ?> </h3>
					<iframe id="ak_cat_chooser_iframe" src="options-general.php?ak_action=akca_blank" border="0" frameborder="0" borderwidth="0" style="border: 1px solid #B2B2B2; height: 250px; width: 100%;"></iframe>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Description:') ?></th>
			<td><textarea name="category_description" rows="5" cols="50" style="width: 97%;"><?php echo wp_specialchars($category->category_description, 1); ?></textarea></td>
		</tr>
		</table>
	  <p class="submit"><input type="submit" name="submit" value="<?php _e('Edit category') ?> &raquo;" /></p>
 </form>
 <p><a href="categories.php"><?php _e('&laquo; Return to category list'); ?></a></p>
</div>
    <?php
	include('admin-footer.php');
    die();
}
add_action('edit_category_form_pre', 'ak_edit_category_form');

function ak_add_category_form() {
	require_once ('admin-header.php');
	if ( current_user_can('manage_categories') ) : 
?>
<div class="wrap">
    <h2><?php _e('Add New Category') ?></h2>
    <form name="addcat" id="addcat" action="categories.php" method="post">
    <?php wp_nonce_field('add-category'); ?>
        <p><?php _e('Name:') ?><br />
        <input type="text" name="cat_name" value="" /></p>
        <p><?php _e('Category parent:') ?>
        <strong id="ak_cat_parent_display">None</strong> (<a href="#" onclick="document.getElementById('ak_cat_chooser').style.display='block'; document.getElementById('ak_cat_chooser_iframe').src='options-general.php?ak_action=akca_select_cat&ak_cat_parent=0'; return false;">Change</a>)
        <input type="hidden" id="category_parent" name="category_parent" value="0" />
		</p>
		<div id="ak_cat_chooser" style="display: none;">
			<a href="#" onclick="document.getElementById('ak_cat_chooser').style.display='none'; return false;" style="float: right;">Close</a>
			<h3><?php _e('Categories'); ?> </h3>
			<iframe id="ak_cat_chooser_iframe" src="options-general.php?ak_action=akca_blank" border="0" frameborder="0" borderwidth="0" style="border: 1px solid #B2B2B2; height: 250px; width: 100%;"></iframe>
		</div>
        <p><?php _e('Description: (optional)') ?> <br />
        <textarea name="category_description" rows="5" cols="50" style="width: 97%;"></textarea></p>
        <p class="submit"><input type="hidden" name="action" value="addcat" /><input type="submit" name="submit" value="<?php _e('Add Category &raquo;') ?>" /></p>
    </form>
</div>
<?php endif; 
	include('admin-footer.php');
	die();
}
add_action('add_category_form_pre', 'ak_add_category_form');

function ak_select_cat() {
	$cat_ID = (int) $_GET['ak_cat_parent'];
	$category = get_category($cat_ID);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_settings('blog_charset'); ?>" />
<title><?php bloginfo('name') ?> &rsaquo; <?php echo $title; ?> &#8212; WordPress</title>
<link rel="stylesheet" href="<?php echo get_settings('siteurl') ?>/wp-admin/wp-admin.css?version=<?php bloginfo('version'); ?>" type="text/css" />
<?php do_action('admin_head'); ?>
</head>
<body>
<?php
if ($cat_ID != 0) {
	print('<p style="background: #DEEEFC; padding: 5px; margin: 0;"><a href="options-general.php?ak_action=akca_select_cat&ak_cat_parent='.$category->category_parent.'">&laquo; Up a Level</a></p>');
}
?>
<table id="the-list-x" width="100%" cellpadding="3" cellspacing="3">
	<tr>
		<th scope="col"><?php _e('ID') ?></th>
        <th scope="col"><?php _e('Name') ?></th>
        <th scope="col"><?php _e('Description') ?></th>
        <th scope="col"><?php _e('Action') ?></th>
	</tr>
<?php
ak_cat_select_rows();
?>
</table>
</body>
</html>
<?php
	die();
}

?>