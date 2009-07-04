<?php
/*
Plugin Name: AndrewHeiss.com Portfolio
Version: 0.1
Plugin URI: http://www.andrewheiss.com
Description: Displays my custom portfolio
Author: Andrew Heiss
Author URI: http://www.andrewheiss.com/
*/

$portfolio = new Portfolio();
add_action( 'admin_menu', array(&$portfolio, 'addAdminMenu') );


/**
* WP Portfolio
*/
class Portfolio
{
	var $portfolio_table;
	// TODO: Include functions to display the portfolio
	// TODO: Use a lightbox/thick box to display portfolio entries
	
	function __construct()
	{
		add_option("portfolio_table", "ah_portfolio");
		$this->portfolio_table = get_option('portfolio_table');
	}
	
	function addAdminMenu() {
		
		add_menu_page('Portfolio Settings', 'Portfolio', 8, __FILE__, array(&$this, 'portfolioOptions'));
		add_submenu_page(__FILE__, 'Portfolio settings', 'Settings', 8, __FILE__, array(&$this, 'portfolioOptions'));
		add_submenu_page(__FILE__, 'Edit Portfolio Entries', 'Edit Portfolio Entries', 8, 'portfolio-edit', array(&$this, 'editEntries'));	
	}
	
	function portfolioOptions() {
		echo '<div class="wrap">';
		echo "<div id=\"icon-options-general\" class=\"icon32\"><br/></div>";
		echo "<h2>Coming Soon Page</h2>";
		echo '<p>Here is where the options would go if I actually had options.</p>';
		echo "<p>Check <a href=\"http://codex.wordpress.org/Creating_Options_Pages\" title=\"Creating Options Pages &laquo; WordPress Codex\">here</a> for more information.</p>";
		echo '</div>';
	}
	
	function editEntries() {

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
	
	function processForm() {
		$title = wp_filter_nohtml_kses($_POST['project_title']);
		$image = wp_filter_kses($_POST['project_image']);
		$date = wp_filter_nohtml_kses($_POST['project_date']);
		$type = wp_filter_nohtml_kses($_POST['project_type']);
		$link = wp_filter_nohtml_kses($_POST['project_link']);
		$description = wp_filter_kses($_POST['project_description']); // FIXME: This strips out <p>s, but shouldn't
		// FUTURE: Use Markdown instead
		$id = intval($_POST['id_portfolio']);
		
		$query = "INSERT INTO $this->portfolio_table (id_portfolio, project_date, project_description, project_image, project_link, project_title, project_type) VALUES ('$id', '$date', '$description', '$image', '$link', '$title', '$type') ON DUPLICATE KEY UPDATE project_date = '$date', project_description = '$description', project_image = '$image', project_link = '$link', project_title = '$title', project_type = '$type'";
		
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

	function showList($message = "") {

		$query = "SELECT * FROM $this->portfolio_table";
		$fullList = mysql_query($query);
		
		$page = $_GET['page']; // TODO: Sanitize this?
		
		?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2>Edit Portfolio Entries
		<a class="button add-new-h2" href="?page=<?php echo $page; ?>&amp;entry=new">Add New</a>	
		</h2>
		
		<?php
			if (!empty($message)) {
				echo "<p>$message</p>";
			}
		?>
		
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th class="manage-column column-cb check-column">&nbsp;</th>
					<th class="manage-column column-title">Title</th>
					<th class="manage-column">Type</th>
					<th class="manage-column column-date">Date</th>
				</tr>
			</thead>
			<tbody>	
		<?php
			$i = 0;
			while ($entry = mysql_fetch_array($fullList)) { // TODO: Make the checkboxes do something, like delete
				$alternate = ($i % 2 == 0) ? "alternate" : "";
				echo "<tr id=\"" . $entry['id_portfolio'] . "\" class=\"$alternate\">";
				echo "<th class=\"check-column\"><input type=\"checkbox\" value=\"" . $entry['id_portfolio'] . "\" name=\"post[]\"/></th>";
				echo "<td><a class=\"row-title\" href=\"?page=$page&amp;entry=" . $entry['id_portfolio'] . "\">" . $entry['project_title'] . "</a></td>";
				echo "<td>" . $entry['project_type'] . "</td>";
				echo "<td>" . strftime("%Y/%m/%d", strtotime($entry['project_date'])) . "</td>";
				echo "</tr>";
				$i++;
			}?>
			</tbody>
		</table>
	<?php
	} // End of editEntries()


	function editIndividual($entryID = 0, $errors = "", $message = "") {
		$already_filled = false;
		
		if ($entryID == 0) {
			# Add a new entry
			$buttonTitle = "Add entry";
			$pageTitle = "Add New Portfolio Entry";
		} else {
			$query = "SELECT * FROM $this->portfolio_table WHERE id_portfolio = $entryID";
			$result = mysql_query($query);

			$buttonTitle = "Save changes";

			if (mysql_num_rows($result)) {
				$already_filled = true;
				$date = trim(mysql_result($result, 0, "project_date"));
				$description = trim(mysql_result($result, 0, "project_description"));
				$image = trim(mysql_result($result, 0, "project_image"));
				$link = trim(mysql_result($result, 0, "project_link"));
				$title = trim(mysql_result($result, 0, "project_title"));
				$type = trim(mysql_result($result, 0, "project_type")); // TODO: Put project types in separate table, add plugin page
				$id = trim(mysql_result($result, 0, "id_portfolio")); // TODO: Rename to id_project, use $project throughout
				
				$pageTitle = "Edit $title";
			} else {
				$this->showList(); // Invalid entry ID
			}
		}
		
		if (!empty($errors)) {
			$title = $_POST['project_title'];
			$image = $_POST['project_image'];
			$date = $_POST['project_date'];
			$type = $_POST['project_type'];
			$link = $_POST['project_link'];
			$description = $_POST['project_description'];
			$id = $_POST['id_portfolio'];
		} 
		
		// TODO: Add ability to delete
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
		<form action="<?php echo $_SERVER ['REQUEST_URI']; ?>" method="post" accept-charset="utf-8">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Project Title</th>
					<td><input type="text" name="project_title" value="<?php echo $title; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Type</th>
					<td><input type="text" name="project_type" value="<?php echo $type; ?>" /></td>
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
						<input type="hidden" name="id_portfolio" value="<?php echo $id; ?>" />
						<input type="hidden" name="submit_check" value="1" />
						<input class="button-primary" type="submit" value="<?php echo $buttonTitle; ?>" />
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
