<div id="bu_post_selector" style="display:none;">
	<div class="inner wrap">
		<form action="" id="bu_post_search">
			<fieldset>
			    <?php wp_nonce_field('bu_ajax_post_search', 'nonce'); ?>
				<label for="bu_post_search_query">Search</label>
				<input type="text" tabindex="-1" name="s" id="bu_post_search_query" />
				<p class="subtext">Permalinks, Post IDs, or a keyword search</p>
			</fieldset>
		</form>
		<ul id="bu_post_search_results"></ul>
	</div>
</div>
