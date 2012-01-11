<?php wp_nonce_field('bu_feature', 'bu_feature_nonce', false); ?>
<div class="post-selector first" id="bu_feature">
	<input type="hidden" id="bu_feature_post_id" name="bu_feature[post_id]" value="<?php echo $post_id; ?>"/>
	<label for="bu_feature_custom_title">Post Title</label><br />
	<input id="bu_feature_custom_title" class="bu_title" name="bu_feature[title]" type="text" value="<?php esc_attr_e($title); ?>"/><button class="button replace">Replace</button> <button class="button remove">Remove</button>
</div>
