<?php wp_nonce_field('gc_feature', 'gc_feature_nonce', false); ?>
<div class="post-selector first" id="gc_feature">
	<input type="hidden" id="gc_feature_post_id" name="gc_feature[post_id]" value="<?php echo $post_id; ?>"/>
	<div class="image image-thumbnail<?php if ( ! $image ): ?> hidden<?php endif;?>"><?php echo $image ?></div>
	<label for="gc_feature_custom_title"><?php _e( "Post Title" ); ?></label><br />
	<input id="gc_feature_custom_title" class="gc_title" name="gc_feature[title]" type="text" value="<?php esc_attr_e( $title ); ?>" disabled="disabled"/>
	<div class="buttons">
		<button class="button replace"><?php _e( "Replace" ); ?></button> 
		<button class="button remove"><?php _e( "Remove" ); ?></button>
	</div>
</div>
