/**
 * Ml SLider
 */
(function ($) {
	$(function () {
		/**
		 * Reindex the slides after they have been dragged/dropped
		 */
		var updateSlideOrder = function() {
			$('.ml-slider table.sortable tr').each(function() {
				$('input.menu_order', $(this)).val($(this).index());
			});
		}

		/**
		 * Enable the correct options for this slider type
		 */
		var enableOptions = function(slider) {
			$('.ml-slider .option:not(.' + slider + ')').attr('disabled', 'disabled').css('color','#ccc').parents('tr').hide();
			$('.ml-slider .option.' + slider).removeAttr('disabled').css('color','').parents('tr').show();

			if ($('.effect option:selected').attr('disabled') == 'disabled') {
				$('.effect option:enabled:first').attr('selected', 'selected');
			}
		}		

		/**
		 * Enable the correct options on page load
		 */
		enableOptions($('.ml-slider .select-slider:checked').attr('rel'));

		/**
		 * Handle slide libary switching
		 */
		$('.ml-slider .select-slider').click(function() {
			enableOptions($(this).attr('rel'));
		});

		// Return a helper with preserved width of cells
		var helper = function(e, ui) {
		    ui.children().each(function() {
		        $(this).width($(this).width());
		    });
		    return ui;
		};

		$(".ml-slider table.sortable tbody").sortable({
		    helper: helper,
			stop: function() {
				updateSlideOrder()
			}
		});

		$(".confirm").click(function() {
			return confirm("Are you sure?");
		});

		/**
		 * Helptext tooltips
		 */
		$(".ml-slider .tooltip").tipsy({html: true, fade: true, gravity: 'e'});
		$(".ml-slider .tooltiptop").tipsy({html: true, fade: true, gravity: 'se'});

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

					var tableRow = "<tr class='slide'><td>" +
									"<div style='position: absolute'>" + 
									"<a class='delete-slide remove-slide' href='#'>x</a> " + 
									"</div>" +
									"<img src='" + url + "' width='150px'></td><td> " + 
									"<textarea name='attachment[" + attachment.id + "][post_excerpt]' placeholder='Caption'>" + attachment.caption + "</textarea>" +
									"<input type='text' name='attachment[" + attachment.id + "][url]' placeholder='URL'>" + 
									"<input type='hidden' class='menu_order' name='attachment[" + attachment.id + "][menu_order]' value='100'>" + 
									"</td></tr>";

					// add slide to existing slides table
					jQuery(".ml-slider .slides tbody").append(tableRow);

					// display the unsaved changes warning
					$('.ml-slider .unsaved').show();
				});

				// the slides haven't been assigned to the slider yet, so just remove the row if the delete
				// button is clicked
				jQuery(".remove-slide").live('click', function(e){
					e.preventDefault();
					$(this).closest('tr').remove();
				});

				// reindex the slides
				updateSlideOrder();

				// ensure the rows are sortable
				$(".ml-slider table.sortable").tableDnD({
					onDrop: function() {
						updateSlideOrder()
					}
				});
			});

			file_frame.open();
		});

		// show the unsaved changes when the form is changed
		$('.ml-slider form').live('change', function() { 
			$('.ml-slider .unsaved').fadeIn();
		});
	});
}(jQuery));