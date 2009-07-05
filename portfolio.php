<?php
/*
Plugin Name: AndrewHeiss.com Portfolio
Version: 0.1
Plugin URI: http://www.andrewheiss.com
Description: Displays my custom portfolio
Author: Andrew Heiss
Author URI: http://www.andrewheiss.com/
*/

// error_reporting(E_ALL);
$portfolio = new Portfolio();
add_action( 'admin_menu', array(&$portfolio, 'addAdminMenu') );
// require('lib/markdown.php');
// require('lib/smartypants.php');

/**
* WP Portfolio
* TODO: Make messages look nicer
* TODO: Front end
* TODO: Use a lightbox/thick box to display portfolio entries
*/
class Portfolio
{
	var $portfolio_table;
	var $portfolio_type_table;
	
	function __construct()
	{
		add_option("portfolio_table", "ah_portfolio");
		add_option("portfolio_types_table", "ah_portfolio_types");
		$this->portfolio_table = get_option('portfolio_table');
		$this->portfolio_types_table = get_option('portfolio_types_table');
		
		// if (!function_exists("Markdown")) {
		// 	require (dirname(__FILE__).'/lib/markdown.php');
		// }
		// if (!function_exists("SmartyPants")) {
		// 	require (dirname(__FILE__).'/lib/smartypants.php');
		// }
	}
	
	function niceHTML($text) {
		$html = SmartyPants(Markdown($text));
		return $html;
	}
	
	function addAdminMenu() {
		add_menu_page('Portfolio Settings', 'Portfolio', 8, __FILE__, array(&$this, 'portfolioOptions'));
		add_submenu_page(__FILE__, 'Portfolio settings', 'Settings', 8, __FILE__, array(&$this, 'portfolioOptions'));
		add_submenu_page(__FILE__, 'Manage Portfolio Entries', 'Edit Portfolio Entries', 8, 'portfolio-edit', array(&$this, 'manageEntries'));
		add_submenu_page(__FILE__, 'Manage Project Types', 'Manage Project Types', 8, 'portfolio-types-edit', array(&$this, 'manageTypes'));	
	}
	
	function portfolioOptions() {
		echo '<div class="wrap">';
		echo "<div id=\"icon-options-general\" class=\"icon32\"><br/></div>";
		echo "<h2>Coming Soon Page</h2>";
		echo '<p>Here is where the "options" would go if I *actually* _had_ options.</p>';
		echo "<p>Check <a href=\"http://codex.wordpress.org/Creating_Options_Pages\" title=\"Creating Options Pages &laquo; WordPress Codex\">here</a> for more information.</p>";
		echo '</div>';
	}
	
	// FUTURE: Simplify manageTypes() and manageEntries() - too much repetition--could be put in one method
	
	function manageTypes() {
		// Delete types in bulk
		if ($_POST['action'] == 'delete' && count($_POST['type_check']) > 0) {
			if ($this->deleteItems('type')) {
				$this->showListTypes("Portfolio types successfully deleted");
				return;
			}
		}

		// If an individual type is specified, edit it (and/or validate and process it)
		// Otherwise, just show the full list of types
		if (empty($_GET['type'])) {
			$this->showListTypes();
		} else {
			$typeID = intval($_GET['type']);
			
			if (isset($_POST['submit_check'])) { // If the form was submitted...
				 if ($form_errors = $this->validateType()) {
					 $this->editIndividualType($typeID, $form_errors);
				 } else {
					 $this->processTypes();
				 }
			} else {
				$this->editIndividualType($typeID); // If not, just edit the type
			}
		}
	}
	
	function manageEntries() {

		// Delete entries in bulk
		if ($_POST['action'] == 'delete' && count($_POST['entry_check']) > 0) {
			if ($this->deleteItems('entry')) {
				$this->showList("Portfolio entries successfully deleted");
				return;
			}
		}

		// If an individual entry is specified, edit it (and/or validate and process it)
		// Otherwise, just show the full list
		if (empty($_GET['entry'])) {
			$this->showList();
		} else {
			$itemID = intval($_GET['entry']);
			
			if (isset($_POST['submit_check'])) { // If the form was submitted...
				 if ($form_errors = $this->validateForm()) {
					 $this->editIndividual($itemID, $form_errors);
				 } else {
					 $this->processForm();
				 }
			} else {
				$this->editIndividual($itemID); // If not, just edit the entry
			}
		}
		
	}
	
	function deleteItems($type) {
		if ($type == "entry") {
			$items = implode(",", $_POST['entry_check']); 
			$table = $this->portfolio_table;
			$column = "id_project";
		} elseif ($type == "type") {
			$items = implode(",", $_POST['type_check']); 
			$table = $this->portfolio_types_table;
			$column = "id_type";
		}
		
		$query = "DELETE FROM $table WHERE $column IN ($items)";
		
		$result = mysql_query($query);
		if ($result) {
			return true;
		}
	}
	
	function editTypes() {
		echo "Here's where you can manage the types of projects";
	}
	
	function processTypes()
	{
		$title = wp_filter_nohtml_kses($_POST['type_title']);
		$description = wp_filter_kses($_POST['type_description']); // FIXME: This strips out <p>s, but shouldn't
		
		$id = intval($_POST['id_type']);
		
		$query = "INSERT INTO $this->portfolio_types_table
		(id_type, type_title, type_description)
		VALUES ('$id', '$title', '$description')
		ON DUPLICATE KEY
		UPDATE type_title = '$title', type_description = '$description'";
		
		$updateType = mysql_query($query);
		$result_id = mysql_insert_id();
		
		$title = stripslashes($title);
		
		if ($result_id == $id) {
			$message = "$title successfully updated";
			$this->editIndividualType($id, "", $message);
		} else {
			$message = "$title successfully added";
			$this->showListTypes($message);
		}
	}
	
	function processForm() {
		$title = wp_filter_nohtml_kses($_POST['project_title']);
		$description = wp_filter_kses($_POST['project_description']); // FIXME: This strips out <p>s, but shouldn't
		$image = wp_filter_kses($_POST['project_image']);
		$link = wp_filter_nohtml_kses($_POST['project_link']);
		$date = strftime("%Y-%m-%d %H:%M:%S", strtotime(wp_filter_nohtml_kses($_POST['project_date'])));
		$type = wp_filter_nohtml_kses($_POST['project_type']);
		$visible = (isset($_POST['project_visible'])) ? "1" : "0" ;
		
		// FUTURE: Use Markdown instead
		$id = intval($_POST['id_project']);
		
		$query = "INSERT INTO $this->portfolio_table 
		(id_project, fk_type, project_title, project_description, project_image, project_link, project_date, project_visible) 
		VALUES ('$id', '$type', '$title', '$description', '$image', '$link', '$date', '$visible') 
		ON DUPLICATE KEY
		UPDATE fk_type = '$type', project_title = '$title', project_description = '$description', project_image = '$image', project_link = '$link', project_date = '$date', project_visible = '$visible'";
		
		$updateEntry = mysql_query($query);
		$result_id = mysql_insert_id();
		
		$title = stripslashes($title);
		
		if ($result_id == $id) {
			$message = "$title successfully updated";
			$this->editIndividual($id, "", $message);
		} else {
			$message = "$title successfully added";
			$this->showList($message);
		}
	}
	
	function validateForm() {
		if (empty($_POST['project_title'])) {
			$errors[] = "Please type a project title.";
		}
		
		return $errors;
	}
	
	function validateType() {
		if (empty($_POST['type_title'])) {
			$errors[] = "Please type a type title.";
		}
		
		return $errors;
	}
	
	
	function showListTypes($message = "") {		
		$query = "SELECT a.*, COUNT(v.id_project) AS projectCount
		 FROM $this->portfolio_types_table AS a
		 LEFT JOIN $this->portfolio_table AS v ON ( v.fk_type = a.id_type )
		 GROUP BY a.id_type";
		$fullList = mysql_query($query);
	?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br/></div>
			<h2>Edit Project Types
			<a class="button add-new-h2" href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;type=new">Add New</a>	
			</h2>

			<?php
				if (!empty($message)) {
					echo "<p>$message</p>";
				}
			?>
			<form id="posts-filter" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="action">
						<option selected="selected" value="-1">Bulk Actions</option>
						<option value="delete">Delete</option>
					</select>
					<input id="doaction" class="button-secondary action" type="submit" name="doaction" value="Apply"/>
				</div>
				<br class="clear"/>
			</div>

			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th id="cb" class="manage-column column-cb check-column"><input type="checkbox"/></th>
						<th class="manage-column column-title">Title</th>
						<th class="manage-column">Number of portfolio entries</th>
					</tr>
				</thead>
				<tbody>	
			<?php
				$i = 0;
				while ($entry = mysql_fetch_array($fullList)) { 
					$alternate = ($i % 2 == 0) ? "alternate" : "";
					echo "<tr id=\"" . $entry['id_type'] . "\" class=\"$alternate\">";
					echo "<th class=\"check-column\"><input type=\"checkbox\" value=\"" . $entry['id_type'] . "\" name=\"type_check[]\"/></th>";
					echo "<td><a class=\"row-title\" href=\"$_SERVER[REQUEST_URI]&amp;type=" . $entry['id_type'] . "\">" . $entry['type_title'] . "</a></td>";
					
					echo "<td class=\"manage-column\">" . $entry['projectCount'] . "</td>";
					echo "</tr>";
					$i++;
				}?>
				</tbody>
			</table>
			</form>
		<?php
	}
	

	function showList($message = "") {
		$query = "SELECT * FROM $this->portfolio_table LEFT JOIN $this->portfolio_types_table ON $this->portfolio_types_table.id_type = $this->portfolio_table.fk_type";
		$fullList = mysql_query($query);
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2>Edit Portfolio Entries
		<a class="button add-new-h2" href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;entry=new">Add New</a>	
		</h2>
		
		<?php
			if (!empty($message)) {
				echo "<p>$message</p>";
			}
		?>
		<form id="posts-filter" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action">
					<option selected="selected" value="-1">Bulk Actions</option>
					<option value="delete">Delete</option>
				</select>
				<input id="doaction" class="button-secondary action" type="submit" name="doaction" value="Apply"/>
			</div>
			<br class="clear"/>
		</div>
		
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th id="cb" class="manage-column column-cb check-column"><input type="checkbox"/></th>
					<th class="manage-column column-title">Title</th>
					<th class="manage-column">Type</th>
					<th class="manage-column">Visible</th>
					<th class="manage-column column-date">Date</th>
				</tr>
			</thead>
			<tbody>	
		<?php
			$i = 0;
			while ($entry = mysql_fetch_array($fullList)) { 
				$alternate = ($i % 2 == 0) ? "alternate" : "";
				echo "<tr id=\"" . $entry['id_project'] . "\" class=\"$alternate\">";
				echo "<th class=\"check-column\"><input type=\"checkbox\" value=\"" . $entry['id_project'] . "\" name=\"entry_check[]\"/></th>";
				echo "<td><a class=\"row-title\" href=\"$_SERVER[REQUEST_URI]&amp;entry=" . $entry['id_project'] . "\">" . $entry['project_title'] . "</a></td>";
				echo "<td>" . $entry['type_title'] . "</td>";
				$visible = ($entry['project_visible'] == 1) ? "Yes" : "No";
				echo "<td>$visible</td>";
				echo "<td>" . strftime("%Y/%m/%d", strtotime($entry['project_date'])) . "</td>";
				echo "</tr>";
				$i++;
			}?>
			</tbody>
		</table>
		</form>
	<?php
	} // End of editEntries()


	function editIndividualType($typeID = 0, $errors = "", $message = "") {
		$already_filled = false;
		// TODO: Add order column for types
		// TODO: Order of type and entry lists
		if ($typeID == 0) {
			# Add a new entry
			$buttonTitle = "Add type";
			$pageTitle = "Add New Project Type";
		} else {
			$query = "SELECT * FROM $this->portfolio_types_table WHERE id_type = $typeID";
			$result = mysql_query($query);

			$buttonTitle = "Save changes";

			if (mysql_num_rows($result)) {
				$already_filled = true;
				
				$id = trim(mysql_result($result, 0, "id_type"));
				$title = trim(mysql_result($result, 0, "type_title"));
				$description = trim(mysql_result($result, 0, "type_description"));
				
				$pageTitle = "Edit $title";
			} else {
				$this->showListTypes(); // Invalid entry ID
			}
		}
		
		if (!empty($errors)) {
			$id = $_POST['id_type'];
			$title = $_POST['type_title'];
			$description = $_POST['type_description'];
		} ?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php echo $pageTitle; ?></h2>
		<p>Here's where I can edit the portfolio entry types.</p>
		<?php
		if (!empty($errors)) {
			foreach ($errors as $value) {
				echo "<p>".$value."</p>";
			}
		}
		if (!empty($message)) {
			echo "<p>$message</p>";
		}
		?>
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" accept-charset="utf-8">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Type Title</th>
					<td><input type="text" name="type_title" value="<?php echo $title; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Description</th>
					<td><textarea name="type_description" rows="8" cols="40"><?php echo $description; ?></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						<input type="hidden" name="id_type" value="<?php echo $id; ?>" />
						<input type="hidden" name="submit_check" value="1" />
						<input class="button-primary" type="submit" value="<?php echo $buttonTitle; ?>" />
						<a class="button" href="<?php echo preg_replace("/&type=(\d+|new)/", "", $_SERVER['REQUEST_URI']); ?>">Cancel</a>
						<?php
							if ($already_filled == true) {
								?><input class="button" type="submit" name="delete_type" value="Delete" /><?php // TODO: Make single deleting work too
							}
						?>
					</td>
				</tr>
			</table>
			<p></p>
		</form>
	</div>
<?php	}

	function editIndividual($entryID = 0, $errors = "", $message = "") {
		$already_filled = false;
		
		$types = "SELECT * FROM $this->portfolio_types_table";
		$getTypes = mysql_query($types);
		
		if ($entryID == 0) {
			# Add a new entry
			$buttonTitle = "Add entry";
			$pageTitle = "Add New Portfolio Entry";
		} else {
			$query = "SELECT * FROM $this->portfolio_table WHERE id_project = $entryID";
			$result = mysql_query($query);

			$buttonTitle = "Save changes";

			if (mysql_num_rows($result)) {
				$already_filled = true;
				
				$id = trim(mysql_result($result, 0, "id_project"));
				$title = trim(mysql_result($result, 0, "project_title"));
				$description = trim(mysql_result($result, 0, "project_description"));
				$image = trim(mysql_result($result, 0, "project_image"));
				$link = trim(mysql_result($result, 0, "project_link"));
				$date = strftime("%B %e, %Y %T", strtotime(trim(mysql_result($result, 0, "project_date"))));
				$visible = trim(mysql_result($result, 0, "project_visible")); 
				$type = trim(mysql_result($result, 0, "fk_type")); 
				
				$pageTitle = "Edit $title";
			} else {
				$this->showList(); // Invalid entry ID
			}
		}
		
		if (!empty($errors)) {
			$id = $_POST['id_project'];
			$title = $_POST['project_title'];
			$description = $_POST['project_description'];
			$image = $_POST['project_image'];
			$link = $_POST['project_link'];
			$date = $_POST['project_date'];
			$visible = $_POST['project_visible'];
			$type = $_POST['project_type'];
		} 
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php echo $pageTitle; ?></h2>
		<p>Here's where I can edit the portfolio entry.</p>
		<?php
		if (!empty($errors)) {
			foreach ($errors as $value) {
				echo "<p>".$value."</p>";
			}
		}
		if (!empty($message)) {
			echo "<p>$message</p>";
		}
		?>
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" accept-charset="utf-8">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Project Title</th>
					<td><input type="text" name="project_title" value="<?php echo $title; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Type</th>
					<td><select name="project_type" id="project_type">
						<option></option>
						<?php while ($getType = mysql_fetch_array($getTypes)) {
							$selected = ($type == $getType['id_type']) ? "selected=\"selected\"" : "";
							echo "<option $selected value=\"$getType[id_type]\">$getType[type_title]</option>";
						}?>	
					</select></td>
				</tr>
				<tr valign="top">
					<th scope="row">Visible on front page?</th>
					<?php $checked = ($visible == 1) ? " checked=\"checked\"": ""; ?>
					<td><input <?php echo $checked; ?>type="checkbox" name="project_visible" value="visible" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project URL</th>
					<td><input type="text" name="project_link" value="<?php echo $link; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Date</th>
					<td><input type="text" name="project_date" value="<?php echo $date; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Image</th>
					<td><input type="text" name="project_image" value="<?php echo $image; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Description</th>
					<td><textarea name="project_description" rows="8" cols="40"><?php echo $description; ?></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						<input type="hidden" name="id_project" value="<?php echo $id; ?>" />
						<input type="hidden" name="submit_check" value="1" />
						<input class="button-primary" type="submit" value="<?php echo $buttonTitle; ?>" />
						<a class="button" href="<?php echo preg_replace("/&entry=(\d+|new)/", "", $_SERVER['REQUEST_URI']); ?>">Cancel</a>
						<?php
							if ($already_filled == true) {
								?><input class="button" type="submit" name="delete_entry" value="Delete" /><?php
							}
						?>
					</td>
				</tr>
			</table>
			<p></p>
		</form>
	</div>
	<?php
	}
	
}
