/*
	Function: onReady

	Initializes event handlers for image and file manipulation.

	Returns:

		On success, click events are registered for the 'remove file'
		buttons and inapplicable elements are hidden.

*/
$(document).ready(function() {
	// Hide the upload file inputs if the user already has that file format uploaded.
	$("input.hide, label.hide, form div.hide").hide(0);

	// Add onclick to the cover image "remove cover" checkbox.
	$("button[name='remove_cover']").click(function(e) {
        // Confirm that the user would like to delete this file.
		if(!confirm("Remove the cover image for your book?"))
			return;

        // Append hidden element to form indicating cover was deleted.
        $('<input>').attr({type: 'hidden', name: 'remove_cover', value: 'true'}).appendTo('#form_edit_book');

        // Get DIV containing upload cover form elements and reveal it.
        $("#form_upload_text_cover").parent().fadeIn(200);

		// Hide the "delete cover" button.
		$(e.currentTarget).hide();

		// Remove image and replace with default "no cover" image:
		var cover_image = $("#book_cover_image");
		cover_image.fadeOut('fast', function () {
			cover_image.attr('src', base_url + "img/default_covers/default_cover.jpg");
			cover_image.fadeIn('fast');
		});
	});

    // Add onclick to each file "remove file" checkbox.
	$("button[name='remove_content_file[]']").click(function(e) {
		// Button IDs are in the form of 'remove_content_file_TYPE'
		var html_id_prefix = "remove_content_file_";
		var format = e.currentTarget.id.substring(html_id_prefix.length);

		// Confirm user would like to remove the file.
		if(!confirm("Remove the " + format.toUpperCase() + " file for your book?"))
			return;

        // Append hidden element to form indicating cover was deleted.
        $('<input>').attr({type: 'hidden', name: 'remove_' + format, value: 'true'}).appendTo('#form_edit_book');

        // Get DIV containing upload file form elements and reveal it.
        $('#form_upload_text_' + format).parent().fadeIn(200);

        // Hide the "delete cover" button.
        $(e.currentTarget).hide();
	})
});
