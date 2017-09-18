$(document).ready(function() {

	var REDIRECT_URL = OC.generateUrl('');

	$("form.verify").submit(function(e) {
		e.preventDefault(); // Prevent form submit

		var token = $(this).parent().data("token");
		$.ajax({
			url: OC.generateUrl('/apps/registration/api/v1/register/' + token),
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json',
		}).done(function(json) {
			OCA.Registration.Status.showSuccessMessages([
				$('<p/>').html(json.message).text(), // Sanitize response message
				t('registration', 'Click <a href="{url}">here</a> to continue.', {'url': REDIRECT_URL}),
			], function(text) {
				return $('<li>').html(text); // Use .html to show link
			});
		}).fail(function(xhr) {
			OCA.Registration.Status.showErrorMessage(xhr.responseJSON.error, function(text) {
				return $('<li>').text(text);
			});
		});
	});

});
