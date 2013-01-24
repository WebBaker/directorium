<?php $fieldname = esc_attr('__'.str_replace('.', '_', $key)); ?>

<div> <label for="<?php echo $fieldname ?>"> <?php esc_html_e($label) ?> </label> </div>
<div> <input type="text" name="<?php echo $fieldname ?>" value="<?php esc_attr_e($value) ?>" /> </div>
<div> <!-- messages placeholder --> </div>