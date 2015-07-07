/*
	Function: checkLogin

	Sends an AJAX request to check if the user entered valid login information.

	Parameters:

		event - login form submit event.
		email - user's login email.
		password - user's account password.

	Returns:

		On success, reloads the page with the user now logged in.
		On failure, sets an error message that login failed.

	Notes:

		Uses lightbox_me.
		Shows loader image while waiting for AJAX request to complete.

*/
function checkLogin(event)
{
	event.preventDefault();

	$.ajax({
        url: base_url + 'login',
        type: "post",
		data: $('#login_box form').serialize(),
        success: function(return_message){
			// If login was a success, refresh the page.
			if(return_message == 'true')
			{
				location.reload();
			}
			// If login was a failure, append an error message to the login box.
			else
			{
				// Only want to add the <p> error tag once.
				if($("#login_box div p.form_p_error").length < 1)
				{
					// Create DOM element.
					var error_message 	= document.createElement("P");
					error_message.className = "form_p_error";
					var text_message 	= document.createTextNode("Incorrect email and/or password.");
					error_message.appendChild(text_message);

					// Animate Entrance.
					$(error_message).insertBefore("#login_box div div.separator").hide().show(250);
				}
			}
		},
		beforeSend:function()
		{
			// If a loader image has not already been displayed, append one while waiting for the request to cpm.
			if($("#login_form_load_image").length < 1)
			{
				// Create DOM element.
				var img = document.createElement("IMG");
				img.id 	= "login_form_load_image";
				$(img).width("1.5em").attr("src", base_url + "img/loading/ajax-loader_5.gif");

				// Animate Entrance.
				$(img).appendTo("#login_box form div:last-child").hide().fadeIn(500);
			}
		},
		complete: function()
		{
			// Remove the AJAX loader image once the request has completed.
			if($("#login_form_load_image").length == 1)
			{
				$("#login_form_load_image").remove();
			}
		}
	});
}
