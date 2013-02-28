/**
 * Ml SLider
 */
(function ($) {
	$(function () {

		/**
		 * Reindex the slides after they have been dragged/dropped
		 */
		var updateSlideOrder = function() {
			$('.metaslider table.sortable tr').each(function() {
				$('input.menu_order', $(this)).val($(this).index());
			});
		}

		/**
		 * Enable the correct options for this slider type
		 */
		var enableOptions = function(slider) {
			$('.metaslider .option:not(.' + slider + ')').attr('disabled', 'disabled').css('color','#ccc').parents('tr').hide();
			$('.metaslider .option.' + slider).removeAttr('disabled').css('color','').parents('tr').show();

			if ($('.effect option:selected').attr('disabled') == 'disabled') {
				$('.effect option:enabled:first').attr('selected', 'selected');
			}
		}		

		/**
		 * Enable the correct options on page load
		 */
		enableOptions($('.metaslider .select-slider:checked').attr('rel'));

		/**
		 * Handle slide libary switching
		 */
		$('.metaslider .select-slider').click(function() {
			enableOptions($(this).attr('rel'));
		});

		// Return a helper with preserved width of cells
		var helper = function(e, ui) {
		    ui.children().each(function() {
		        $(this).width($(this).width());
		    });
		    return ui;
		};

		$(".metaslider table.sortable tbody").sortable({
		    helper: helper,
			stop: function() {
				updateSlideOrder()
			}
		});


		$(".confirm").click(function() {
			return confirm(metaslider.confirm);
		});

		/**
		 * Helptext tooltips
		 */
		$(".metaslider .tooltip").tipsy({className: 'msTipsy', live: true, delayIn: 200, html: true, fade: true, gravity: 'e'});
		$(".metaslider .tooltiptop").tipsy({live: true, delayIn: 500, html: true, fade: true, gravity: 'se'});

		/**
		 * Image uploader
		 */
		var file_frame;

		jQuery('.upload_image_button').live('click', function( event ){
			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( file_frame ) {
			  file_frame.open();
			  return;
			}

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				title: jQuery( this ).data( 'uploader_title' ),
				button: {
					text: jQuery( this ).data( 'uploader_button_text' ),
				},
				multiple: 'add'
			});

			// When an image is selected, run a callback.
			file_frame.on( 'select', function() {
				var selection = file_frame.state().get('selection');

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					if (attachment.subtype == 'bmp') {
						alert('Warning: BML images not allowed');
						return;
					}

					var url = attachment.url;

					if (typeof(attachment.sizes.thumbnail) != 'undefined') {
						url = attachment.sizes.thumbnail.url;
					}

					var tableRow = "<tr class='slide'><td class='col-1'>" +
									"<div style='position: absolute'><a class='delete-slide remove-slide' href='#'>x</a></div>" +
									"<img src='" + url + "' width='150px'></td>" +
									"<td class='col-2'><textarea name='attachment[" + attachment.id + "][post_excerpt]' placeholder='" + metaslider.caption + "'>" + attachment.caption + "</textarea>" +
									"<input class='url' type='text' name='attachment[" + attachment.id + "][url]' placeholder='" + metaslider.url + "' value=''>" +
									"<div class='new_window'>" +
									"<label>" + metaslider.new_window + "<input type='checkbox' name='attachment[" + attachment.id + "][new_window]'></label>" +
									"</div>" +
									"<input type='hidden' class='menu_order' name='attachment[" + attachment.id + "][menu_order]'>" +
									"</td></tr>";

					// add slide to existing slides table
					jQuery(".metaslider .slides tbody").append(tableRow);

					// display the unsaved changes warning
					$('.metaslider .unsaved').show();
				});

				// the slides haven't been assigned to the slider yet, so just remove the row if the delete
				// button is clicked
				jQuery(".remove-slide").live('click', function(e){
					e.preventDefault();
					$(this).closest('tr').remove();
				});

				// reindex the slides
				updateSlideOrder();

			});

			file_frame.open();
		});

		// show the unsaved changes when the form is changed
		$('.metaslider form').live('change', function() { 
			$('.metaslider .unsaved').fadeIn();
		});

		$(".metaslider .shortcode input").click(function(){
		    // Select input field contents
		    this.select();
		});
	});
}(jQuery));