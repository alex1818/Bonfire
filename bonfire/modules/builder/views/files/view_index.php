<?php defined('BASEPATH') || exit('No direct script access allowed');

$pager = '';
if ($this->input->post('use_pagination') == 1) {
    $pager = "
    echo \$this->pagination->create_links();";
}

$headers = '';
for ($counter = 1; $field_total >= $counter; $counter++) {
	// Only build on fields that have data entered.
	if (set_value("view_field_label$counter") == null) {
		continue; 	// move onto next iteration of the loop
	}

	$label  = set_value("view_field_label$counter");
	$name   = set_value("view_field_name$counter");
	$headers .= "
					<th><?php echo lang('{$module_name_lower}_field_{$name}'); ?></th>";
}

$field_prefix = '';

// Only add maintenance columns to the view when module is creating a new db table
// (columns should already be present and handled below when existing table is used)
if ($db_required == 'new') {
	if ($useSoftDeletes) {
		$headers .= "
					<th><?php echo lang('{$module_name_lower}_column_deleted'); ?></th>";
	}
	if ($useCreated) {
		$headers .= "
					<th><?php echo lang('{$module_name_lower}_column_created'); ?></th>";
	}
	if ($useModified) {
		$headers .= "
					<th><?php echo lang('{$module_name_lower}_column_modified'); ?></th>";
	}
    if ($table_as_field_prefix === true) {
        $field_prefix = $module_name_lower . '_';
    }
}

$table_records = '';
$pencil_icon   = "'<span class=\"icon-pencil\"></span> ' . ";
for ($counter = 1; $field_total >= $counter; $counter++) {
	// Only build on fields that have data entered.
	if (set_value("view_field_name$counter") == null
        || set_value("view_field_name$counter") == $primary_key_field
       ) {
		continue; 	// move onto next iteration of the loop
	}

	$field_name = $field_prefix . set_value("view_field_name$counter");

	if ($counter == 1) {
		$table_records .= "
				<?php if (\$can_edit) : ?>
					<td><?php echo anchor(SITE_AREA . '/{$controller_name}/{$module_name_lower}/edit/' . \$record->{$primary_key_field}, {$pencil_icon} \$record->{$field_name}); ?></td>
				<?php else : ?>
					<td><?php e(\$record->{$field_name}); ?></td>
				<?php endif; ?>";
	} else {
		$field_name = set_value("view_field_name$counter");

		// When building from existing table, modify output of the 'deleted' maintenance column
		if  ($db_required == 'existing'
             && $field_name == $this->input->post("soft_delete_field")) {
			$table_records .= "
					<td><?php echo \$record->{$field_name} > 0 ? lang('{$module_name_lower}_true') : lang('{$module_name_lower}_false'); ?></td>";
		} else {
			$table_records .= "
					<td><?php e(\$record->{$field_name}); ?></td>";
		}
	}
}

// Only add maintenance columns to view when module is creating a new db table
// (columns should already be present and handled above when existing table is used)
if ($db_required == 'new') {
	if ($useSoftDeletes == 'true') {
		$table_records .= "
					<td><?php echo \$record->" . set_value('soft_delete_field') . " > 0 ? lang('{$module_name_lower}_true') : lang('{$module_name_lower}_false'); ?></td>";
		$field_total++;
	}

	if ($useCreated == 'true') {
		$table_records .= "
					<td><?php e(\$record->" . set_value('created_field') . "); ?></td>";
		$field_total++;
	}

	if ($useModified == 'true') {
		$table_records .= "
					<td><?php e(\$record->" . set_value('modified_field') . "); ?></td>";
		$field_total++;
	}
}

$permissionName = preg_replace("/[ -]/", "_", ucfirst($module_name)) . '.' . ucfirst($controller_name);

echo "<?php

\$num_columns	= {$field_total};
\$can_delete	= \$this->auth->has_permission('{$permissionName}.Delete');
\$can_edit		= \$this->auth->has_permission('{$permissionName}.Edit');
\$has_records	= isset(\$records) && is_array(\$records) && count(\$records);

if (\$can_delete) {
    \$num_columns++;
}
?>
<div class='admin-box'>
	<h3>
		<?php echo lang('{$module_name_lower}_area_title'); ?>
	</h3>
	<?php echo form_open(\$this->uri->uri_string()); ?>
		<table class='table table-striped'>
			<thead>
				<tr>
					<?php if (\$can_delete && \$has_records) : ?>
					<th class='column-check'><input class='check-all' type='checkbox' /></th>
					<?php endif;?>
					{$headers}
				</tr>
			</thead>
			<?php if (\$has_records) : ?>
			<tfoot>
				<?php if (\$can_delete) : ?>
				<tr>
					<td colspan='<?php echo \$num_columns; ?>'>
						<?php echo lang('bf_with_selected'); ?>
						<input type='submit' name='delete' id='delete-me' class='btn btn-danger' value=\"<?php echo lang('bf_action_delete'); ?>\" onclick=\"return confirm('<?php e(js_escape(lang('{$module_name_lower}_delete_confirm'))); ?>')\" />
					</td>
				</tr>
				<?php endif; ?>
			</tfoot>
			<?php endif; ?>
			<tbody>
				<?php
				if (\$has_records) :
					foreach (\$records as \$record) :
				?>
				<tr>
					<?php if (\$can_delete) : ?>
					<td class='column-check'><input type='checkbox' name='checked[]' value='<?php echo \$record->{$primary_key_field}; ?>' /></td>
					<?php endif;?>
					{$table_records}
				</tr>
				<?php
					endforeach;
				else:
				?>
				<tr>
					<td colspan='<?php echo \$num_columns; ?>'><?php echo lang('{$module_name_lower}_records_empty'); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	<?php
    echo form_close();
    {$pager}
    ?>
</div>";