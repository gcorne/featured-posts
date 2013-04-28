<div id="gc_post_selector" title="<?php esc_attr_e( "Choose a Post" ); ?>" style="display:none;">
	<div class="inner">
		<div class="search-wrap">
			<form action="" id="gc_post_search">
				<fieldset>
					<?php wp_nonce_field('gc_ajax_post_search', 'nonce'); ?>
					<label for="gc_post_search_query" class="screen-reader-text">Search</label>
						<input type="text" tabindex="-1" name="s" placeholder="<?php esc_attr_e( "Enter keyword, a post permalink, or a post id to find a specific post" ); ?>" id="gc_post_search_query" />
				</fieldset>
			</form>
		</div>
		<div class="results-wrap">
			<ul id="gc_post_search_results"></ul>
		</div>
	</div>
</div>
