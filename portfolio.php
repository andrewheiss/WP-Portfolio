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
	
	function __construct()
	{
		# code...
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
			$this->editIndividual($_GET['entry']);
		}
	}

	function showList() {

		$query = "SELECT * FROM ah_portfolio";
		$fullList = mysql_query($query);
		?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2>Edit Portfolio Entries</h2>
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
			$page = $_GET['page'];
			while ($entry = mysql_fetch_array($fullList)) {
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

	function editIndividual($entryID) {
		$entryID = intval($entryID);
		$query = "SELECT * FROM ah_portfolio WHERE id_portfolio = $entryID";
		$result = mysql_query($query);

		if (mysql_num_rows($result)) {
			$date = trim(mysql_result($result, 0, "project_date"));
			$description = trim(mysql_result($result, 0, "project_description"));
			$image = trim(mysql_result($result, 0, "project_image"));
			$link = trim(mysql_result($result, 0, "project_link"));
			$title = trim(mysql_result($result, 0, "project_title"));
			$type = trim(mysql_result($result, 0, "project_type"));
			$id = trim(mysql_result($result, 0, "id_portfolio")); // TODO: Rename to id_project, use $project throughout
		} else {
			wp_die( __("You've tried to access a non-existent portfolio page.") );
		}

	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2>Edit <?php echo $title; ?></h2>
		<p>Here's where I can edit the portfolio entry.</p>
		<form action="" method="post" accept-charset="utf-8">
			<p><input class="button-primary" type="submit" value="Save Changes"></p>
		</form>
	</div>
	<?php
	}
	
}
