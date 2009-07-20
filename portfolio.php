<?php
/*
Plugin Name: AndrewHeiss.com Portfolio
Version: 1.0
Plugin URI: http://www.andrewheiss.com
Description: Displays my custom portfolio
Author: Andrew Heiss
Author URI: http://www.andrewheiss.com/
*/

// error_reporting(E_ALL);
$portfolioAdmin = new PortfolioAdmin();
$portfolioDisplay = new PortfolioDisplay();
add_action('admin_menu', array(&$portfolioAdmin, 'addAdminMenu'));
add_filter('ah_portfolio', array(&$portfolioDisplay, 'buildPortfolio'));

/**
* WP Portfolio
* TODO: Make messages look nicer
* TODO: Front end
* TODO: Use a lightbox/thick box to display portfolio entries
*/
class PortfolioAdmin {
	var $portfolio_table;
	var $portfolio_types_table;
	var $inherit_test;
	
	function __construct()
	{
		add_option("portfolio_table", "ah_portfolio");
		add_option("portfolio_types_table", "ah_portfolio_types");
		$this->portfolio_table = get_option('portfolio_table');
		$this->portfolio_types_table = get_option('portfolio_types_table');
		$this->inherit_test = "This is a test. This is only a test";
	}
	
	function niceHTML($text) {
		$html = SmartyPants(Markdown($text));
		return $html;
	}
	
	function addAdminMenu() {
		add_menu_page('Portfolio Settings', 'Portfolio', 7, __FILE__, array(&$this, 'portfolioOptions'));
		add_submenu_page(__FILE__, 'Portfolio settings', 'Settings', 7, __FILE__, array(&$this, 'portfolioOptions'));
		add_submenu_page(__FILE__, 'Manage Portfolio Entries', 'Edit Portfolio Entries', 7, 'portfolio-edit', array(&$this, 'manageEntries'));
		add_submenu_page(__FILE__, 'Manage Project Types', 'Manage Project Types', 7, 'portfolio-types-edit', array(&$this, 'manageTypes'));	
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
		if (($_POST['action'] == 'delete' && count($_POST['type_check']) > 0)) {
			if ($this->delete('type')) {
				$this->showListTypes("Portfolio types successfully deleted");
				return;
			}
		}
		
		// if (isset($_POST['delete_type']) | isset($_POST['delete_entry'])) { }

		// If an individual type is specified, edit it (and/or validate and process it)
		// Otherwise, just show the full list of types
		if (empty($_GET['type'])) {
			$this->showListTypes();
		} else {
			$typeID = intval($_GET['type']);
			
			if (isset($_POST['submit_check'])) { // If the form was submitted...
				$type = new Type($_POST);
				if ($form_errors = $type->validate()) {
					$this->editIndividualType($typeID, $form_errors);
				} else {
					$type->process();
				}
			} else {
				$this->editIndividualType($typeID); // If not, just edit the type
			}
		}
	}
	
	function manageEntries() {

		// Delete entries in bulk
		if ($_POST['action'] == 'delete' && count($_POST['entry_check']) > 0) {
			if ($this->delete('entry')) {
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
				$item = new Item($_POST);
				
				if ($form_errors = $item->validate()) {
					$this->editIndividual($itemID, $form_errors);
				} else {
					$item->process();
				}
			} else {
				$this->editIndividual($itemID); // If not, just edit the entry
			}
		}
		
	}
	
	function showListTypes($message = "") {		
		$query = "SELECT a.*, COUNT(v.id_project) AS projectCount
		 FROM $this->portfolio_types_table AS a
		 LEFT JOIN $this->portfolio_table AS v ON ( v.fk_type = a.id_type )
		 GROUP BY a.id_type ORDER BY a.type_order DESC, a.type_title ASC";
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
		$query = "SELECT * FROM $this->portfolio_table LEFT JOIN $this->portfolio_types_table ON $this->portfolio_types_table.id_type = $this->portfolio_table.fk_type ORDER BY project_date DESC";
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
				
				$type = new Type(mysql_fetch_array($result));
				
				$id = $type->id;
				$title = $type->title;
				$description = $type->description;
				$order = $type->order;
				
				$pageTitle = "Edit $title";
			} else {
				$this->showListTypes(); // Invalid entry ID
			}
		}
		
		if (!empty($errors)) {
			$id = $_POST['id_type'];
			$title = $_POST['type_title'];
			$description = stripslashes($_POST['type_description']);
			$order = $_POST['type_order'];
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
					<td><input type="text" name="type_title" size="45" value="<?php echo $title; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Type Description</th>
					<td><textarea name="type_description" rows="8" cols="40"><?php echo $description; ?></textarea></td>
				</tr>
				<tr valign="top">
					<th scope="row">Order (optional)</th>
					<td><input type="text" name="type_order" value="<?php echo $order; ?>" /></td>
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
				
				$item = new Item(mysql_fetch_array($result));
				
				$id = $item->id;
				$title = $item->title;
				$description = $item->description;
				$image_large = $item->image_large;
				$image_small = $item->image_small;
				$link = $item->link;
				$date = strftime("%B %e, %Y %T", strtotime($item->date));
				$visible = $item->visible;
				$type = $item->type;
				
				$pageTitle = "Edit $title";
			} else {
				$this->showList(); // Invalid entry ID
			}
		}
		
		if (!empty($errors)) {
			$id = $_POST['id_project'];
			$title = $_POST['project_title'];
			$description = $_POST['project_description'];
			$image_large = $_POST['project_image_large'];
			$image_small = $_POST['project_image_small'];
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
					<td><input type="text" name="project_title" size="45" value="<?php echo $title; ?>" /></td>
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
					<td><input type="text" name="project_link" size="45" value="<?php echo $link; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Project Date</th>
					<td><input type="text" name="project_date" value="<?php echo $date; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Large Project Image</th>
					<td><input type="text" name="project_image_large" size="45" value="<?php echo $image_large; ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Small Project Image</th>
					<td><input type="text" name="project_image_small" size="45" value="<?php echo $image_small; ?>" /></td>
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
	
	function delete($type) {
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
	
} // End of PortfolioAdmin{}


/**
* Portfolio Display
*/
class PortfolioDisplay extends PortfolioAdmin
{
	
	function __construct() {
		parent::__construct(); // Get the __construct variables from PortfolioAdmin{}
		
	}
	
	function buildPortfolio() {
		$query = "SELECT a.*, COUNT(v.id_project) AS projectCount
		 FROM $this->portfolio_types_table AS a
		 LEFT JOIN $this->portfolio_table AS v ON ( v.fk_type = a.id_type )
		 GROUP BY a.id_type ORDER BY a.type_order DESC, a.type_title ASC";
		
		$results = mysql_query($query);
		
		while ($type = mysql_fetch_array($results)) {
			if ($type['projectCount'] > 0) {
				$this->buildSection($type['type_title'], $type['type_description'], $type['id_type']);
			}
		}
		
	}
	
	function buildSection($title, $description, $id)
	{
		echo "<h3>$title</h3>";
		echo "<p>$description <a id=\"show$id\" href=\"#\">View full portfolio&nbsp;&raquo;</a></p>";
		
		$query1 = "SELECT * FROM $this->portfolio_table WHERE fk_type = $id AND project_visible = 1 ORDER BY project_date DESC";
		$getresults = mysql_query($query1);
		
		echo "<div class=\"portfolio-section item$id\">\n";
		
		while ($row = mysql_fetch_array($getresults)) {
			$item = new Item($row);
			$item->displayDetails();
		}

		echo "</div>";
		echo "<br class=\"clearfloat\" />\n";		
	}
	
} // End of PortfolioDisplay{}


/**
* Item
*/
class Item extends PortfolioAdmin {
	
	public $id, $title, $description, $image_large, $image_small, $link, $date, $visible, $type;
	
	function __construct($array) {
		parent::__construct();
		$this->id = $array['id_project'];
		$this->title = $array['project_title'];
		$this->description = $array['project_description'];
		$this->image_large = $array['project_image_large'];
		$this->image_small = $array['project_image_small'];
		$this->link = $array['project_link'];
		$this->date = $array['project_date'];
		$this->visible = $array['project_visible'];
		$this->type = (isset($array['fk_type'])) ? $array['fk_type'] : $array['project_type'];
	}
	
	public function displayDetails() {
		$thickbox_link = "#TB_inline?width=630&amp;height=400&amp;inlineId=projectDetails_" . $this->id;
		?>
		<div class="portfolio-item">
			<a href="<?php echo $thickbox_link; ?>" class="thickbox" title="<?php echo $this->title; ?>"><img src="<?php echo $this->image_small; ?>" alt="<?php echo $this->title; ?>" /></a>
			<h4><a href="<?php echo $thickbox_link; ?>" class="thickbox" title="<?php echo $this->title; ?>"><?php echo $this->title; ?></a></h4>

			<?php
			if (!empty($this->link)) { 
				$ext_link = "<a href=\"$this->link\" class=\"external\">";
				$ext_link_noclass = "<a href=\"$this->link\">";
				$ext_link_close = "</a>";
			} ?>
		
			<div id="projectDetails_<?php echo $this->id; ?>" class="modal-hidden-content">
				<div class="big-portfolio-picture"><?php echo $ext_link_noclass; ?><img src="<?php echo $this->image_large; ?>" alt="<?php echo $this->title; ?>" /><?php echo $ext_link_close; ?></div>
				<div class="portfolio-detail-header">
					<h4 class="project-title"><?php echo $ext_link . $this->title . $ext_link_close; ?></h4>
					<p class="project-date"><?php echo strftime("%B %Y", strtotime($this->date)); ?></p>
					<br class="clearfloat" />
				</div>
				<p><?php echo $this->description; ?></p>;
			</div>
		</div>
		<?php
	}
	
	public function validate() {
		if (empty($this->title)) {
			$errors[] = "Please type a project title.";
		}
		
		if (empty($this->description)) {
			$errors[] = "You need a description!";
		}
		
		return $errors;
	}
	
	public function process() {
		$id = intval($this->id);
		$title = wp_filter_nohtml_kses($this->title);
		// FUTURE: Use Markdown instead
		$description = wp_filter_kses($this->description); // FIXME: This strips out <p>s, but shouldn't
		$image_large = wp_filter_kses($this->image_large);
		$image_small = wp_filter_kses($this->image_small);
		$link = wp_filter_nohtml_kses($this->link);
		$date = strftime("%Y-%m-%d %H:%M:%S", strtotime(wp_filter_nohtml_kses($this->date)));
		$type = wp_filter_nohtml_kses($this->type);
		$visible = (isset($this->visible)) ? "1" : "0" ;
		
		$query = "INSERT INTO $this->portfolio_table 
		(id_project, fk_type, project_title, project_description, project_image_large, project_image_small, project_link, project_date, project_visible) 
		VALUES ('$id', '$type', '$title', '$description', '$image_large', '$image_small', '$link', '$date', '$visible') 
		ON DUPLICATE KEY
		UPDATE fk_type = '$type', project_title = '$title', project_description = '$description', project_image_large = '$image_large', project_image_small = '$image_small', project_link = '$link', project_date = '$date', project_visible = '$visible'";

		$updateEntry = mysql_query($query) or die (mysql_error());
		$result_id = mysql_insert_id();
		
		$title = stripslashes($title);
		
		if ($result_id == $id) {
			$message = "$title successfully updated";
			parent::editIndividual($id, "", $message);
		} else {
			$message = "$title successfully added";
			parent::showList($message);
		}
	}
} // End of Item{}

/**
* Type
*/
class Type extends PortfolioAdmin
{
	public $id, $title, $description, $order;
	
	function __construct($array) {
		parent::__construct();
		$this->id = $array['id_type'];
		$this->title = $array['type_title'];
		$this->description = $array['type_description'];
		$this->order = $array['type_order'];
	}
	
	public function process() {
		$id = intval($this->id);
		$title = wp_filter_nohtml_kses($this->title);
		$description = wp_filter_kses($this->description); // FIXME: This strips out <p>s, but shouldn't
		$order = intval($this->order);
		
		$query = "INSERT INTO $this->portfolio_types_table
		(id_type, type_title, type_description, type_order)
		VALUES ('$id', '$title', '$description', '$order')
		ON DUPLICATE KEY
		UPDATE type_title = '$title', type_description = '$description', type_order = '$order'";
		
		$updateType = mysql_query($query);
		$result_id = mysql_insert_id();
		
		$title = stripslashes($title);
		
		if ($result_id == $id) {
			$message = "$title successfully updated";
			parent::editIndividualType($id, "", $message);
		} else {
			$message = "$title successfully added";
			parent::showListTypes($message);
		}
	}
	
	public function validate() {
		if (empty($this->title)) {
			$errors[] = "Please type a type title.";
		}
		
		return $errors;
	}
}
