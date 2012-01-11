jQuery(function($) {
	var $activeSelector = null;
	var nonce = $('#bu_post_search').find('[name="nonce"]').val();
	var fetchingPosts = false;
	var page = 1;

	$('#bu_post_selector').dialog({
		autoOpen: false,
		height: 400,
		width: 640,
		modal: true,
		title: 'Select Post',
		open: function() {
			$(this).scrollTop(0);
			$('#bu_post_search_query').focus();
			getPosts();
		},
		buttons: {
			Cancel: function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$activeSelector = null;
		}
	});

	var updatePostSelector = function(e) {
		var $selector = $(this);
		var baseID = $selector.attr('id');

		$selector.find('#' + baseID + '_post_id').val(e.post.ID);
		$selector.find('#' + baseID + '_original_title').val(e.post.title);
		$selector.find('#' + baseID + '_title').val(e.post.title);
		$selector.find('#' + baseID + '_custom_title').val(e.post.title)
			.removeAttr("disabled");
		if(e.post.image) {
			var sizes = ['thumbnail', 'small', 'medium', 'large'];
			for(var i = 0; i < sizes.length; i++) {
				var url = e.post.image[sizes[i]]['url'];
				if(url) {
					var $img = $('<img/>').attr('src', e.post.image[sizes[i]]['url']);
					$selector.find('.image-' + sizes[i])
					.html($img);

				}
			}
		} else {
			$selector.find('.image')
				.hide()
				.find('img').remove();
		}

		if($selector.find('.image img').length > 0) {
			$selector.find('.image').show();
		}
	}

	var openSelectorDialog = function(e) {
		e.preventDefault();

		$activeSelector = $(this).closest('.post-selector');

		$('#bu_post_search_results').html('');
		$('#bu_post_search').find('[name="s"]').val('');
		$('#bu_post_selector').dialog('open');

	}

	var showResults = function(results) {
		var $results = $('#bu_post_search_results');
		$results.html('');
		if(results == null) {
			$results.html('<li><em>No posts found.</em></li>');
		} else {
			addResults(results);
		}
	}

	var addResults = function(results) {
		var $results = $('#bu_post_search_results');

		$.each(results, function(i, result) {
			var $snippet = $('<li><a><span class="item-title"></span><span class="item-info"></span></a></li>')
				.find('a').attr('href', '#')
					.data(result).end();
			$snippet.find('.item-title').text(result.title);
			if(result.status == 'publish') {
				$snippet.find('.item-info').text(result.date);
			} else {
				$snippet.find('.item-info').text(result.status);
			}

			if(result.image) {
				if(result.image.thumbnail.url) {
					$snippet.find('a').prepend($('<img/>').attr('src', result.image.thumbnail.url));
				}
			}
			$results.append($snippet);
		});
		$results.children('li').filter(':odd').addClass('odd');
		setTimeout( function() { fetchingPosts = false; }, 1000);
	}

	var removeItem = function(e) {
		e.preventDefault();
		var $selector = $(this).closest('.post-selector');
		var baseID = $selector.attr('id');
		$selector.find('#' + baseID + '_post_id').val('');
		$selector.find('#' + baseID + '_original_title').val('');
		$selector.find('#' + baseID + '_title').val('');
		$selector.find('#' + baseID + '_custom_title').val('')
			.attr("disabled", "disabled");
	}


	var $postSelectors = $('.post-selector');
	$postSelectors.bind('updatePostSelector', updatePostSelector);
	$postSelectors.find('.replace').bind('click', openSelectorDialog);
	$postSelectors.find('.remove').bind('click', removeItem);

	$('#bu_post_search').submit(function(e){
		e.preventDefault();
		getPosts($(this).find('[name="s"]').val(), 1);
	});


	$('#bu_post_selector').scroll(function() {
		var $box = $(this);
		if(!fetchingPosts &&
			($box.scrollTop() >= ($box.find('.inner').height() - $box.height()))) {
			page++;
			getPosts($box.find('[name="s"]').val(), page);
		}
	});

	var getPosts = function(searchQuery, page) {
		var post_types = $activeSelector.data('post_types');

		fetchingPosts = true;

		if(!post_types) {
			post_types = '';
		}
		if(!page) {
			page = 1;
		}

		var data = {
		    nonce: nonce,
		    action: 'bu_get_posts',
		    post_types: post_types,
		    page: page

		};

		if(searchQuery) {
			data.s = searchQuery;
		}
		$.post(ajaxurl, data, function(results) {
			if(page == 1) {
				showResults(results);
			} else {
				addResults(results);
			}
		}, 'json');

	}

	$('#bu_post_search_results a').live('click', function(e) {
		e.preventDefault();
		$activeSelector.trigger({type: 'updatePostSelector', post: $(this).data()});
		$('#bu_post_selector').dialog('close');
	});

});

