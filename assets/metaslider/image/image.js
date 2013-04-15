/**
 * Meta Slider
 */
(function ($) {
	$(function () {
		jQuery('.metaslider .add-slide').live('click', function(event){
			event.preventDefault();

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				multiple: 'add',
				frame: 'post',
				library: {type: 'image'}
			});

			// When an image is selected, run a callback.
			file_frame.on('insert', function() {
				var selection = file_frame.state().get('selection');

				selection.map( function( attachment ) {

					attachment = attachment.toJSON();

					var data = {
						action: 'create_image_slide',
						slide_id: attachment.id,
						slider_id: window.parent.metaslider_slider_id
					};

					jQuery.post(ajaxurl, data, function(response) {
						jQuery(".metaslider .left table").append(response);
					});
				});
			});

			file_frame.open();
		});
	});

}(jQuery));