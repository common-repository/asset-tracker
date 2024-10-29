<?php
/*
	Copyright 2010  Telogis  (email : asset.tracker@telogis.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin Name: Asset Tracker
Plugin URI: http://www.telogis.com/
Description: This plugin generates a series of images (assets), based on the page's content.
Version: 1.0.5
Author: Telogis
Author URI: http://www.telogis.com/
License: GPL2
*/

class asset_tracker {
	function __construct() {
		$this->config = array(
			/*
				!! IMPORTANT !!
				The following settings are automatically overridden by values from the database on page load.
				Any changes made here will not take any effect.
				!! IMPORTANT !!
			*/
			// 'max_size' is the amount of asset slots to fill. Each asset can be set to take up any amount of slots.
			'max_size' => 5,
			// 'before' is displayed directly BEFORE the assets are shown on the screen
			'before' => '<div id="asset-tracker"><div class="wrap">',
			// 'after' is displayed directly AFTER the assets are shown on the screen
			'after' => '</div></div>',
			// 'display' is where the plugin will echo its HTML output. If set to 0 it will not automatically display.
			'display' => '0',
			// 'noshow' is a comma-delimited list of page id's to exclude or include (depending on 'noshow_type') from showing assets on.
			'noshow' => '',
			// 'noshow_type' defines whether to exclude or include the page id's in 'noshow'. Include = 1, exclude = 0.
			'noshow_type' => '0',
			// 'cvar_slot' is the slot ID to store the Google Analytics custom variable in
			'cvar_slot' => '0',
			// 'ganew' specifies whether to use the new asynchronous Google Analytics code. 1 = new, 0 = old.
			'ganew' => '0',
			
			/*
				Settings past here are not saved in the database, changes you make will take effect instantly.
			*/
			// 'cvar_title' is the name of the Google Analytics custom variable
			'cvar_title' => 'Asset',
			// 'item_html' contains the HTML of each item.
			'item_html' => array(
				// shown before the URL
				'<a href="',
				// shown after the URL, and before the image
				'" onclick="',
				// google analytics onclick event
				'"><img src="',
				// the end of the target URL, and the beginning of the alt tag
				'" alt="',
				// holds the image and link closing tags
				'" /></a>'
			),
			// 'display_options' holds a list of available hooks.
			'display_options' => array(
				'Disable' => 'Disable',
				'Beginning of Footer' => 'get_footer',
				'Beginning of Sidebar' => 'get_sidebar'
			)
		);
		
		// Installation
		register_activation_hook(__FILE__, array($this, 'install'));
		
		// Load settings from database into array
		$this->load_settings();
		
		// Administration
		add_action('admin_menu', array($this, 'backend_menu'));
		
		// Display the assets
		if (in_array($this->config['display'], $this->config['display_options']) && $this->config['display'] != 'Disabled') {
			add_action($this->config['display'], array($this, 'show'), 10);
		}
	}
	
	private function get_url($trailing_slash = true) {
		$u = ($_SERVER["HTTPS"]=="on"?'https://':'http://').$_SERVER["SERVER_NAME"].($_SERVER["SERVER_PORT"]!="80"?":".$_SERVER["SERVER_PORT"]:"").$_SERVER["REQUEST_URI"];
		return (!$trailing_slash&&substr($u,-1)=='/')?substr($u,0,-1):$u;
	}
	
	private function get_rel_url($trailing_slash = true) {
		return (!$trailing_slash&&substr($_SERVER["REQUEST_URI"],-1)=='/')?substr($_SERVER["REQUEST_URI"], 0, -1):$_SERVER["REQUEST_URI"];
	}
	
	// installation
	public function install() {
		global $wpdb;
		
		// Create tables
		$the_query = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}asset_tracker_cache` (
		 `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		 `post_id` INT NOT NULL ,
		 `content` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
		) ENGINE = MYISAM ;";
		$wpdb->query($the_query);
		$the_query = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}asset_tracker_assets` (
		 `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		 `title` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
		 `imageurl` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
		 `size` TINYINT NOT NULL ,
		 `tags` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
		 `link` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  '#'
		) ENGINE = MYISAM ;";
		$wpdb->query($the_query);
		
		// Insert sample data if there is no data in the table already
		$na = $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}asset_tracker_assets`");
		if ($na == '0') {
			$url = get_option('siteurl') . "/wp-content/plugins/asset-tracker/";
			$the_query = "INSERT INTO `{$wpdb->prefix}asset_tracker_assets` (`title`,`imageurl`,`size`,`tags`,`link`) VALUES
			('Fleet Management Software', '".$url."Fleet Management Software.png', 1, 'telogis,fleet,management,software,information', 'http://www.telogis.com/solutions/fleet/'),
			('Fleet Route Planning', '".$url."Fleet Route Planning.png', 1, 'telogis,fleet,route,planning,blog', 'http://www.telogis.com/solutions/route/'),
			('GIS Platform', '".$url."GIS Platform.png', 1, 'telogis,gis,platform,article', 'http://www.telogis.com/solutions/geobase/'),
			('Green Fleet', '".$url."Green Fleet.png', 1, 'telogis,green,fleet,environment', 'http://www.telogis.com/benefits/green-fleet/'),
			('Work Order Management', '".$url."Mobile Field Service Workers.png', 1, 'telogis,mobile,field,service,workers', 'http://www.telogis.com/solutions/mobile/')";
			$wpdb->query($the_query);
		}
		
		// Save settings
		add_option('asset_tracker', array('max_size' => 5, 'before' => '<div id="asset-tracker"><div class="wrap">', 'after' => '</div></div>', 'display' => '0', 'noshow' => '', 'noshow_type' => '0', 'cvar_slot' => '0', 'ganew' => '0'));
	}
	
	// Load settings from database into array
	private function load_settings() {
		$dbvals = get_option('asset_tracker');
		if (is_array($dbvals)) {
			foreach($dbvals as $i => $v) {
				if (isset($this->config[$i])) {
					$this->config[$i] = $dbvals[$i];
				}
			}
		}
	}
	
	// Settings form
	private function settings_form($opt) {
		foreach ($opt as $i => $v) {
			$opt[$i] = htmlentities($opt[$i]);
		}
		$opt['field_cvar_slot_p'] = "";
		for ($i=0;$i<=5;$i++) {
			if ($opt['field_cvar_slot'] == $i) {
				$opt['field_cvar_slot_p'] .= "<option selected>$i</option>";
			} else {
				$opt['field_cvar_slot_p'] .= "<option>$i</option>";
			}
		}
		$opt['field_display_p'] = "";
		foreach ($this->config['display_options'] as $i => $v) {
			if ($opt['field_display'] == $v) {
				$opt['field_display_p'] .= "<option value=\"$v\" selected>$i</option>";
			} else {
				$opt['field_display_p'] .= "<option value=\"$v\">$i</option>";
			}
		}
		$opt['field_noshow_type'] = ($opt['field_noshow_type'] == 1)
			? '<option value="0">Exclude</option><option value="1" selected>Include Only</option>'
			: '<option value="0" selected>Exclude</option><option value="1">Include Only</option>';
		$opt['field_ganew'] = ($opt['field_ganew'] == 1)
			? '<option value="0">No</option><option value="1" selected>Yes</option>'
			: '<option value="0" selected>No</option><option value="1">Yes</option>';
		echo "<form name=\"frmAssetSettings\" id=\"frmAssetSettings\" method=\"post\" action=\"tools.php?page=asset-tracker-list&settings=1\">
			<div id=\"poststuff\" class=\"metabox-holder has-right-sidebar\">
				<div id=\"post_body\">
					
					<div id=\"at_max_size\" class=\"stuffbox\">
						<h3><label for=\"field_max_size\">Maximum Size</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_max_size\" id=\"field_max_size\" size=\"30\" tabindex=\"4\" value=\"" . $opt['field_max_size'] . "\" />
							<p>The amount of asset slots to fill. Each asset can be set to take up any amount of slots.</p>
						</div>
					</div>
					
					<div id=\"at_before\" class=\"stuffbox\">
						<h3><label for=\"field_before\">Before HTML</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_before\" id=\"field_before\" size=\"30\" tabindex=\"1\" value=\"" . $opt['field_before'] . "\" />
							<p>Displayed directly BEFORE the assets are shown on the screen.</p>
						</div>
					</div>
					
					<div id=\"at_after\" class=\"stuffbox\">
						<h3><label for=\"field_after\">After HTML</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_after\" id=\"field_after\" size=\"30\" tabindex=\"2\" value=\"" . $opt['field_after'] . "\" />
							<p>Displayed directly AFTER the assets are shown on the screen.</p>
						</div>
					</div>
					
					<div id=\"at_display\" class=\"stuffbox\">
						<h3><label for=\"field_display\">Display</label></h3>
						<div class=\"inside\">
							<select name=\"field_display\" id=\"field_display\" tabindex=\"3\">" . $opt['field_display_p'] . "</select>
							<p>Where will the plugin echo its HTML output? If set to \"Disable\" it will not automatically display, but you can display it manually in your template by calling the at_show_assets() function.</p>
						</div>
					</div>
					
					<div id=\"at_noshow\" class=\"stuffbox\">
						<h3><label for=\"field_noshow\">Invalid/Valid Pages</label></h3>
						<div class=\"inside\">
							<select name=\"field_noshow_type\" id=\"field_noshow_type\" tabindex=\"5\">" . $opt['field_noshow_type'] . "</select>
							<input type=\"text\" name=\"field_noshow\" id=\"field_noshow\" size=\"30\" tabindex=\"2\" value=\"" . $opt['field_noshow'] . "\" />
							<p>A list of pages to include or exclude from displaying assets on. Seperate each ID with a comma.</p>
						</div>
					</div>
					
					<div id=\"at_cvar_slot\" class=\"stuffbox\">
						<h3><label for=\"field_cvar_slot\">Google Custom Var Slot</label></h3>
						<div class=\"inside\">
							<select name=\"field_cvar_slot\" id=\"field_cvar_slot\" tabindex=\"4\">" . $opt['field_cvar_slot_p'] . "</select>
							<p>The slot ID to store the Google Analytics custom variable in. Set to 0 if you are not using Google Analytics.</p>
						</div>
					</div>
					
					<div id=\"at_ganew\" class=\"stuffbox\">
						<h3><label for=\"field_ganew\">Use Asynchronous Google Analytics Code</label></h3>
						<div class=\"inside\">
							<select name=\"field_ganew\" id=\"field_ganew\" tabindex=\"5\">" . $opt['field_ganew'] . "</select>
							<p>Use the new asynchronous Google Analytics code? (Ignore if not using Google Analytics)</p>
						</div>
					</div>
					
					<input type=\"hidden\" value=\"0kflmk3ef3wsf\" name=\"ht6jh7kte88j\" id=\"ht6jh7kte88j\" />
					<input name=\"save\" type=\"submit\" class=\"button-primary\" id=\"publish\" tab-index=\"6\" accesskey=\"p\" value=\"Update Settings\" />
					<br /><br />
				</div>
			</div>
		</form>";
	}
	
	// Asset form
	private function asset_form($action, $field_title, $field_link, $field_imageurl, $field_size, $field_tags) {
		$field_size_options = "";
		for ($i=1;$i<=$this->config['max_size'];$i++) {
			if ($field_size == $i) {
				$field_size_options .= "<option selected>$i</option>";
			} else {
				$field_size_options .= "<option>$i</option>";
			}
		}
		echo "<form name=\"frmEditAsset\" id=\"frmEditPageAsset\" method=\"post\" action=\"$action\">
			<div id=\"poststuff\" class=\"metabox-holder has-right-sidebar\">
				<div id=\"post_body\">
					<div id=\"at_title\" class=\"stuffbox\">
						<h3><label for=\"field_title\">Title</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_title\" id=\"field_title\" size=\"75\" tabindex=\"0\" value=\"$field_title\" />
							<p>This is put into the image's alt tag when it is displayed on a page.</p>
						</div>
					</div>
					
					<div id=\"at_link\" class=\"stuffbox\">
						<h3><label for=\"field_link\">Link</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_link\" id=\"field_link\" class=\"code\" size=\"30\" tabindex=\"1\" value=\"$field_link\" />
							<p>When a user clicks the image, they will be taken to this URL.</b><br /><br />Note: this image will not be shown on a page with this URL.</p>
						</div>
					</div>
					
					<div id=\"at_imageurl\" class=\"stuffbox\">
						<h3><label for=\"field_imageurl\">Image URL</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_imageurl\" id=\"field_imageurl\" class=\"code\" size=\"30\" tabindex=\"2\" value=\"$field_imageurl\" />
							<p>The url of the image to load into the slot.</p>
						</div>
					</div>
					
					<div id=\"at_size\" class=\"stuffbox\">
						<h3><label for=\"field_size\">Size</label></h3>
						<div class=\"inside\">
							<select name=\"field_size\" id=\"field_size\" tabindex=\"3\">
								$field_size_options
							</select>
							<p>The amount of slots that the image will take up.</p>
						</div>
					</div>
					
					<div id=\"at_tags\" class=\"stuffbox\">
						<h3><label for=\"field_tags\">Tags</label></h3>
						<div class=\"inside\">
							<input type=\"text\" name=\"field_tags\" id=\"field_tags\" size=\"30\" tabindex=\"4\" value=\"$field_tags\" />
							<p>These are used to show the most relavent images, based on the content in the page. Avoid using common words (and, or, a etc.).<br /><br />Seperate each one with a comma (the,quick,brown,fox).</p>
						</div>
					</div>
					
					<input type=\"hidden\" value=\"r312ogr23hr\" name=\"23fy894ifgh\" id=\"23fy894ifgh\" />
					<input name=\"save\" type=\"submit\" class=\"button-primary\" id=\"publish\" tab-index=\"5\" accesskey=\"p\" value=\"Update Asset\" />
					<br /><br />
				</div>
			</div>
		</form>";
	}
	
	// Administration page
	public function options() {
		global $wpdb;
		echo '<div class="wrap">';
		
		// edit asset
		if (isset($_GET['ed'])) {
			$ed_id = preg_replace("/\D/", "", $_GET['ed']);
			if (is_numeric($ed_id) && $ed_id != '') {
				if (isset($_POST['23fy894ifgh'])) {
					if ($_POST['23fy894ifgh'] == "r312ogr23hr") {
						$at_error_fields = "";
						if ($_POST['field_title'] == "") $at_error_fields .= ", Title";
						if ($_POST['field_link'] == "") $at_error_fields .= ", Link";
						if ($_POST['field_imageurl'] == "") $at_error_fields .= ", Image URL";
						if ($_POST['field_size'] == "") $at_error_fields .= ", Size";
						if ($_POST['field_tags'] == "") $at_error_fields .= ", Tags";
						if ($at_error_fields != "")
						{
							echo '<div id="icon-edit-pages" class="icon32"><br /></div><h2>Edit Asset</h2>';
							echo "<h3>Error!</h3><p>The following fields were not filled out: " . trim($at_error_fields, ", ") . "</p>";
							$this->asset_form("tools.php?page=asset-tracker-list&ed=$ed_id", $_POST['field_title'], $_POST['field_link'], $_POST['field_imageurl'], $_POST['field_size'], $_POST['field_tags']);
							return 1;
						}
						$field_title = mysql_real_escape_string(htmlentities($_POST['field_title']));
						$field_link = mysql_real_escape_string(htmlentities($_POST['field_link']));
						$field_imageurl = mysql_real_escape_string(htmlentities($_POST['field_imageurl']));
						$field_size = mysql_real_escape_string(htmlentities($_POST['field_size']));
						$field_tags = mysql_real_escape_string(str_replace(" ", ",", str_replace(",,", ",", htmlentities($_POST['field_tags']))));
						
						$wpdb->query("UPDATE {$wpdb->prefix}asset_tracker_assets SET 
						`title` = '$field_title',
						`link` = '$field_link',
						`imageurl` = '$field_imageurl',
						`size` = $field_size,
						`tags` = '$field_tags'
						WHERE `id`=$ed_id limit 1");
						
						$at_edited = true;
					}
				}
				else
				{
					echo '<div id="icon-edit-pages" class="icon32"><br /></div><h2>Edit Asset</h2>';
					$asset = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}asset_tracker_assets where `id`=$ed_id limit 1");
					$this->asset_form("tools.php?page=asset-tracker-list&ed=$ed_id", $asset->title, $asset->link, $asset->imageurl, $asset->size, $asset->tags);
					return 1;
				}
			}
		}
		
		// add asset
		if (isset($_GET['add'])) {
			if (isset($_POST['23fy894ifgh'])) {
				if ($_POST['23fy894ifgh'] == "r312ogr23hr") {
					$at_error_fields = "";
					if ($_POST['field_title'] == "") $at_error_fields .= ", Title";
					if ($_POST['field_link'] == "") $at_error_fields .= ", Link";
					if ($_POST['field_imageurl'] == "") $at_error_fields .= ", Image URL";
					if ($_POST['field_size'] == "") $at_error_fields .= ", Size";
					if ($_POST['field_tags'] == "") $at_error_fields .= ", Tags";
					if ($at_error_fields != "")
					{
						echo '<div id="icon-edit-pages" class="icon32"><br /></div><h2>Add Asset</h2>';
						echo "<h3>Error!</h3><p>The following fields were not filled out: " . trim($at_error_fields, ", ") . "</p>";
						showAssetTrackerForm("tools.php?page=asset-tracker-list&add=1", $_POST['field_title'], $_POST['field_link'], $_POST['field_imageurl'], $_POST['field_size'], $_POST['field_tags']);
						return 1;
					}
					$field_title = mysql_real_escape_string($_POST['field_title']);
					$field_link = mysql_real_escape_string($_POST['field_link']);
					$field_imageurl = mysql_real_escape_string($_POST['field_imageurl']);
					$field_size = mysql_real_escape_string($_POST['field_size']);
					$field_tags = mysql_real_escape_string(str_replace(" ", ",", str_replace(",,", ",", $_POST['field_tags'])));
					
					$wpdb->query("INSERT INTO {$wpdb->prefix}asset_tracker_assets  (`title`, `link`, `imageurl`, `size`, `tags`)
					values('$field_title', '$field_link', '$field_imageurl', $field_size, '$field_tags')");
					
					$at_added = true;
				}
			} else {
				echo '<div id="icon-edit-pages" class="icon32"><br /></div><h2>Add Asset</h2>';
				$this->asset_form("tools.php?page=asset-tracker-list&add=1", "", "", "", "", "");
				return 1;
			}
		}
		
		if (isset($_GET['settings'])) {
			echo '<div id="icon-tools" class="icon32"><br /></div><h2>Asset Tracker Settings</h2><p><a href="tools.php?page=asset-tracker-list">Back</a></p>';
			if (isset($_POST['ht6jh7kte88j'])) {
				if ($_POST['ht6jh7kte88j'] == '0kflmk3ef3wsf') {
					foreach(array('max_size', 'before', 'after', 'display', 'noshow', 'noshow_type', 'cvar_slot', 'ganew') as $i) {
						if (isset($_POST['field_'.$i])) {
							$the_result[$i] = stripslashes($_POST['field_'.$i]);
						}
					}
					
					update_option("asset_tracker", $the_result);
					$this->load_settings();
					echo "<h3>Settings saved!</h3><p>Asset Tracker settings saved. Note: some settings may require you to clear the plugin's cache before they will take effect.</p>";
				}
			}
			$this->settings_form(array(
				'field_max_size' => $this->config['max_size'],
				'field_before' => $this->config['before'],
				'field_after' => $this->config['after'],
				'field_display' => $this->config['display'],
				'field_noshow' => $this->config['noshow'],
				'field_noshow_type' => $this->config['noshow_type'],
				'field_cvar_slot' => $this->config['cvar_slot'],
				'field_ganew' => $this->config['ganew']
				));
			return 1;
		}
		
		// content header
		echo '<div id="icon-tools" class="icon32"><br /></div><h2>Asset Tracker</h2>';
		
		// delete asset
		if (isset($_GET['del'])) {
			$del_id = preg_replace("/\D/", "", $_GET['del']);
			if (is_numeric($del_id) && $del_id != '')
				if ($wpdb->query("DELETE FROM {$wpdb->prefix}asset_tracker_assets WHERE `id`=$del_id limit 1") > 0)
					echo "<h3>Asset Deleted!</h3><p>Asset #$del_id has been deleted.</p>";
		}
		
		// clear cache
		if (isset($_GET['cc'])) {
			if ($_GET['cc'] == 1) {
				$wpdb->query("DELETE FROM {$wpdb->prefix}asset_tracker_cache");
				echo "<h3>Cache Cleared!</h3><p>The asset tracker cache has been deleted.</p>";
			}
		}
		
		// edited message
		if (isset($at_edited))
			echo "<h3>Asset Edited!</h3><p>Asset #" . $_GET['ed'] . " has been edited.</p>";
		
		// added message
		if (isset($at_added))
			echo "<h3>Asset Added!</h3><p>Asset added.</p>";
		
		// show list
		$total_cached = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}asset_tracker_cache");
		echo "<p>There are currently $total_cached cached asset tracker sets. <a href=\"tools.php?page=asset-tracker-list&cc=1\" onclick=\"if ( confirm('  Are you sure you want to clear the cache and delete $total_cached items?\\n\\n  \\'Cancel\\' to stop, \\'OK\\' to delete.') ) { return true;}return false;\">Clear cache</a></p>";
		echo "<p><a href=\"tools.php?page=asset-tracker-list&add=1\">Add asset</a> | <a href=\"tools.php?page=asset-tracker-list&settings=1\">Settings</a></p><table class=\"widefat\" cellspacing=\"0\"><thead><tr><th>Title</th><th>Link</th><th>Image URL</th><th>Size</th><th>Tags</th></tr></thead>";
		$assets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}asset_tracker_assets");
		foreach ($assets as $asset) {
			echo "
			<tr>
				<td width=\"34%\">
				
				<strong><a class='row-title' href='tools.php?page=asset-tracker-list&ed=$asset->id' title='Edit Asset'>$asset->title</a></strong><br />
				
				<div class=\"row-actions\">
					<span class='edit'><a href='tools.php?page=asset-tracker-list&ed=$asset->id'>Edit</a> | </span>
					<span class='delete'>
						<a class='submitdelete' href='tools.php?page=asset-tracker-list&del=$asset->id' onclick=\"if ( confirm('  Are you sure you want to delete asset #$asset->id?\\n\\n  \\'Cancel\\' to stop, \\'OK\\' to delete.') ) { return true;}return false;\">
							Delete
						</a>
					</span>
				</div>
				
				</td>
				<td width=\"1%\"><p>$asset->link</p></td>
				<td width=\"34%\"><p>$asset->imageurl</p></td>
				<td width=\"1%\"><p>$asset->size</p></td>
				<td width=\"30%\"><p>$asset->tags</p></td>
			</tr>
			";
		}
		echo '</table></div>';
	}
	
	// Backend menu item
	public function backend_menu() {
		add_submenu_page('tools.php', 'Assets', 'Asset Tracker', 'administrator', 'asset-tracker-list', array($this, "options"));
	}
	
	// Show assets
	public function show() {
		global $post, $wpdb;
		
		$noshow_ids = explode(",", preg_replace("/,{2,}/", ",", str_replace(" ", ",", $this->config['noshow'])));
		$display = false;
		if (is_array($noshow_ids)) {
			if ($this->config['noshow_type'] == '1') { // include
				if (in_array($post->ID, $noshow_ids)) {
					$display = true;
				}
			} else if (!in_array($post->ID, $noshow_ids)) { // exclude
				$display = true;
			}
		}
		else {
			$display = true;
		}
		
		if ($post->ID > 0 && $display)
		{
			echo $this->config['before'];
			$act_output = $wpdb->get_var("SELECT `content` FROM {$wpdb->prefix}asset_tracker_cache WHERE `post_id` = $post->ID");
			if ($act_output == "")
			{
				$page_tags = str_replace("	", "", str_replace("\n", "", str_replace("\r", "", strip_tags($post->post_content))));
				$page_tags = strtolower(preg_replace("/[^A-Za-z0-9]/", ",", $page_tags));
				$page_tags = preg_replace("/,{2,}/", ",", $page_tags);
				
				$a_page_tag = explode(",", $page_tags);
				
				$act_output = "";
				$act_space = $this->config['max_size'];
				$total_acts = 0;
				
				for ($i=0;$i<$this->config['max_size'];$i++) {
					for ($a=0;$a<6;$a++) {
						$top_act[$i][$a] = 0;
					}
				}
				
				$assets = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}asset_tracker_assets where `link`!='" . $this->get_url() . "' and `link`!='" . $this->get_url(false) . "' and `link`!='" . $this->get_rel_url() . "' and `link`!='" . $this->get_rel_url(false) . "' order by rand()");
				
				foreach ($assets as $asset) {
					$act_score = 0;
					$a_at_tag = explode(",", strtolower($asset->tags));
					foreach($a_at_tag as $at_tag) {
						if (in_array($at_tag, $a_page_tag) == 0) {
							$act_score++;
						}
					}
					if ($act_space >= $asset->size && $act_score > 0) {
						for ($i=0;$i<$act_space;$i++) {
							if ($act_score > $top_act[$i][0]) {
								for ($z=($act_space-1);$z>0;$z--) {
									if ($i < $z) {
										$top_act[$z][0] = $top_act[$z-1][0];
										$top_act[$z][1] = $top_act[$z-1][1];
										$top_act[$z][2] = $top_act[$z-1][2];
										$top_act[$z][3] = $top_act[$z-1][3];
										$top_act[$z][4] = $top_act[$z-1][4];
										$top_act[$z][5] = $top_act[$z-1][5];
									}
								}
								
								$top_act[$i][0] = $act_score;
								$top_act[$i][1] = $asset->id;
								$top_act[$i][2] = $asset->title;
								$top_act[$i][3] = $asset->imageurl;
								$top_act[$i][4] = $asset->size;
								$top_act[$i][5] = $asset->link;
								$act_space -= $asset->size;
								
								$total_acts++;
								break;
							}
						}
					}
				}
				
				// Here is the javascript code which sends the pageview info to Google Analytics
				if ($this->config['cvar_slot'] != '0') {
					if ($this->config['ganew'] == '1') {
						$onclick = 'javascript:var _gaq=_gaq||[];_gaq.push([\'_setCustomVar\','.$at_config['cvar_slot'].',\''.$at_config['cvar_title'].'\',\'%s\',3]);_gaq.push([\'_trackPageview\']);';
					} else {
						$onclick = 'javascript:pageTracker._setCustomVar('.$this->config['cvar_slot'].', \''.$this->config['cvar_title'].'\',\'%s\',3); pageTracker._trackPageview();';
					}
				}
					
				$act_space = $this->config['max_size'];
				
				for ($i=0;$i<$act_space;$i++) {
					if ($top_act[$i][0] > 0 && $act_space >= $top_act[$i][4]) {
						$act_output .= $this->config['item_html'][0] . $top_act[$i][5] . $this->config['item_html'][1] . $wpdb->prepare($onclick, array($top_act[$i][2])) . $this->config['item_html'][2] . $top_act[$i][3] . $this->config['item_html'][3] . $top_act[$i][2] . $this->config['item_html'][4];
						$act_space -= intval($top_act[$i][4]);
					}
				}
				
				if ($act_space > 0) {
					$at_query = "SELECT * FROM {$wpdb->prefix}asset_tracker_assets where `size` <= $act_space and `link`!='" . $this->get_url() . "' and `link`!='" . $this->get_url(false) . "' and `link`!='" . $this->get_rel_url() . "' and `link`!='" . $this->get_rel_url(false) . "' order by rand()";
					$at_query = $wpdb->get_results($at_query);
					foreach ($assets as $asset) {
						if ($asset->size <= $act_space && $asset->id != $top_act[0][1] && $asset->id != $top_act[1][1] && $asset->id != $top_act[2][1]) {
							$act_output .= $this->config['item_html'][0] . $asset->link . $this->config['item_html'][1] . $wpdb->prepare($onclick, array($asset->title)) . $this->config['item_html'][2] . $asset->imageurl . $this->config['item_html'][3] . $asset->title . $this->config['item_html'][4];
							$act_space -= $asset->size;
							if (!$act_space) break;
						}
					}
				}
				$wpdb->insert("{$wpdb->prefix}asset_tracker_cache", array('post_id' => $post->ID, 'content' => $act_output), array('%d', '%s'));
			}
			echo $act_output;
			echo $this->config['after'];
		}
	}
}

$v_asset_tracker = new asset_tracker;
function at_show_assets() {
	global $v_asset_tracker;
	$v_asset_tracker->show();
}

?>