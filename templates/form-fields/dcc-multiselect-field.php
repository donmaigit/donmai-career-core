<?php
/**
 * Custom Multi Select Template
 */
$options = ! empty( $field['options'] ) ? $field['options'] : array();
$value   = ! empty( $field['value'] ) ? $field['value'] : array();

// Ensure value is array
if ( is_string( $value ) ) $value = explode( ',', $value );
if ( ! is_array( $value ) ) $value = array( $value );
?>
<select name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>[]" 
        id="<?php echo esc_attr( $key ); ?>" 
        class="input-text" 
        multiple="multiple" 
        size="5">
    <?php foreach ( $options as $opt_key => $opt_label ) : ?>
        <option value="<?php echo esc_attr( $opt_key ); ?>" <?php if ( in_array( $opt_key, $value ) ) echo 'selected="selected"'; ?>>
            <?php echo esc_html( $opt_label ); ?>
        </option>
    <?php endforeach; ?>
</select>
<small class="description"><?php _e('Hold Ctrl (Windows) or Command (Mac) to select multiple.', 'donmai-career-core'); ?></small>