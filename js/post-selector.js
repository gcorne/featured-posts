jQuery(function($) {
	var $activeSelector = null;
	var nonce = $('#gc_post_search').find('[name="nonce"]').val();
	var fetchingPosts = false;
	var page = 1;

	var updatePostSelector = function(e) {
		var $selector = $(this);
		var baseID = $selector.attr('id');

		$selector.find('#' + baseID + '_post_id').val(e.post.ID);
		$selector.find('#' + baseID + '_original_title').val(e.post.title);
		$selector.find('#' + baseID + '_title').val(e.post.title);
		$selector.find('#' + baseID + '_custom_title').val(e.post.title)
			.removeAttr("disabled");
		if(e.post.image) {
			// need to pull in the intermedia sizes here
			var sizes = ['thumbnail', 'medium', 'large'];
			for(var i = 0; i < sizes.length; i++) {
				var src = e.post.image[sizes[i]]['src'];
				if(src) {
					var $img = $('<img/>').attr('src', e.post.image[sizes[i]]['src']);
					$selector.find('.image-' + sizes[i]).html($img);

				}
			}
		} else {
			$selector.find('.image')
				.addClass('hidden')
				.find('img').remove();
		}

		if($selector.find('.image img').length > 0) {
			$selector.find('.image').removeClass('hidden');
		}
	}

	var openSelectorDialog = function(e) {
		e.preventDefault();

		$activeSelector = $(this).closest('.post-selector');

		$('#gc_post_search_results').html('');
		$('#gc_post_search').find('[name="s"]').val('');
		$('#gc_post_selector').dialog({
			autoOpen: true,
			height: $(window).height() * 0.8,
			width: 640,
			modal: true,
			dialogClass: 'gc-ui',
			open: function() {
				$(this).scrollTop(0);
				var $widget = $(this).closest(".ui-dialog");
				$('body').addClass("gc-no-scroll");
				var $titlebar = $widget.find(".ui-dialog-titlebar");
				$titlebar.removeClass("ui-corner-all");
				$titlebar.addClass("ui-corner-top");
				$('#gc_post_search_query').focus();
				getPosts();
			},
			buttons: {
				Cancel: function() {
					$(this).dialog("close");
				}
			},
			close: function() {
				$activeSelector = null;
				$('body').removeClass("gc-no-scroll");
			}
		});
	}

	var showResults = function(results) {
		var $results = $('#gc_post_search_results');
		$results.html('');
		if(results == null) {
			$results.html('<li><em>No posts found.</em></li>');
		} else {
			addResults(results);
		}
	}

	var addResults = function(results) {
		if ( results == null ) {
			setTimeout( function() { fetchingPosts = false; }, 1000);
			return;
		}
		var $results = $('#gc_post_search_results');

		$.each(results, function(i, result) {
			var $snippet = $('<li><span class="item-title"></span><span class="item-info"></span></li>').data(result)
			$snippet.find('.item-title').text(result.title);
			if(result.status == 'publish') {
				$snippet.find('.item-info').text(result.date);
			} else {
				$snippet.find('.item-info').text(result.status);
			}

			if(result.image) {
				if(result.image.thumbnail.src) {
					$snippet.prepend($('<img/>').attr('src', result.image.thumbnail.src));
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
		$selector.find('.image').html('')
			.addClass('hidden');
	}


	var $postSelectors = $('.post-selector');
	$postSelectors.bind('updatePostSelector', updatePostSelector);
	$postSelectors.find('.replace').bind('click', openSelectorDialog);
	$postSelectors.find('.remove').bind('click', removeItem);

	$('#gc_post_search').submit(function(e){
		e.preventDefault();
		getPosts($(this).find('[name="s"]').val(), 1);
	});


	$('#gc_post_selector').scroll(function() {
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
		    action: 'gc_get_posts',
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

	$('#gc_post_search_results').on('click', 'li', function(e) {
		e.preventDefault();
		$activeSelector.trigger({type: 'updatePostSelector', post: $(this).data()});
		$('#gc_post_selector').dialog('close');
	});

});

