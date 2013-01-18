<?php wp_nonce_field('importCSVFile', 'directoriumImport'); ?>

<?php
	if (isset($taxonomyCleanup) and is_array($taxonomyCleanup))
		Directorium\View::write('cleanup-taxonomies', array('taxonomyCleanup' => $taxonomyCleanup));
 ?>

<table>
	<tr>
		<td><?php _e('File to import', 'directorium') ?></td>
		<td><input type="file" name="csvfile" id="csvfile" value="" /></td>
		<td rowspan="4" class="final">
			<div id="advice-businesstypes">
				<h4><?php _e('CSV Format for Business Type Imports', 'directorium') ?></h4>

				<p><?php _e('The CSV file should contain 2 columns in every row. If only one '
					.'is populated then that category will be created as a "root" category. '
					.'if both columns are populated then the first column is treated as the '
					.'parent and the second as the child category.', 'directorium') ?></p>

				<p><?php _e('Example: "Professionals, Lawyers" would result in a business '
					.'type of Professionals being created and a business type of Lawyers '
					.'would be created underneath it.', 'directorium') ?></p>
			</div>
			<div id="advice-geographies">
				<h4><?php _e('CSV Format for Geography Imports', 'directorium') ?></h4>

				<p><?php _e('The CSV file should contain 2 columns in every row. If only one '
					.'is populated then that category will be created as a "root" category. '
					.'if both columns are populated then the first column is treated as the '
					.'parent and the second as the child category.', 'directorium') ?></p>

				<p><?php _e('Example: "United Kingdom, Scotland" would result in a geography '
					.'of United Kingdom being created and a geography of Scotland would be '
					.'created underneath it.', 'directorium') ?></p>
			</div>
			<div id="advice-listings">
				<h4><?php _e('CSV Format for Listing Imports', 'directorium') ?></h4>

				<p><?php _e('Listings CSV files must contain between 1-14 columns. In order '
					.'these are Title, Description, Address 1, Address 2, City, Region, '
					.'Postal Code, Country, URL, Email, Phone, Max Words, Max Characters, '
					.'Maximum Images.', 'directorium') ?></p>

				<p><?php _e('It would be completely valid to only have rows of 11 columns '
					.'if for instance you did not wish to specify editorial data such as '
					.'"Max Words".', 'directorium') ?></p>
			</div>
		</td>
	</tr>
	<tr>
		<td><?php _e('Type of import', 'directorium') ?></td>
		<td><select name="type" id="type">
			<option value="businesstypes"><?php _e('Business Types', 'directorium') ?></option>
			<option value="geographies"><?php _e('Geographies', 'directorium') ?></option>
			<option value="listings"><?php _e('Listings', 'directorium') ?></option>
		</select></td>
	</tr>
	<tr>
		<td><?php _e('Ignore the first line?', 'directorium') ?></td>
		<td><input type="checkbox" name="ignoreline1" id="ignoreline1" value="1" /></td>
	</tr>
	<tr>
		<td></td>
		<td>
			<input type="submit" name="doimport" id="doimport"
				value="<?php _e('Start Import', 'directorium') ?>"
				class="button-primary" />
		</td>
	</tr>
</table>