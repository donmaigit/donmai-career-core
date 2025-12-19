<?php
$options = ! empty( $field['options'] ) ? $field['options'] : array();
$value   = ! empty( $field['value'] ) ? $field['value'] : '';
?>
<select name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>" 
        id="<?php echo esc_attr( $key ); ?>" 
        class="input-text">
    <?php foreach ( $options as $opt_key => $opt_label ) : ?>
        <option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $value, $opt_key ); ?>>
            <?php echo esc_html( $opt_label ); ?>
        </option>
    <?php endforeach; ?>
</select>
<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo $field['description']; ?></small><?php endif; ?>