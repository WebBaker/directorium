<?php $fieldname = esc_attr('__'.str_replace('.', '_', $key)); ?>

<div> <label for="<?php echo $fieldname ?>"> <?php esc_html_e($label) ?> </label> </div>
<div> <input type="checkbox" name="<?php echo $fieldname ?>" value="1" <?php if ($value >= 1) echo 'checked="checked"' ?> /> </div>
<div> <!-- messages placeholder --> </div>