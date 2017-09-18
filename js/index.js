$(document).ready(function() {

	$("form.register").submit(function(e) {
		e.preventDefault(); // Prevent form submit

		$.ajax({
			url: OC.generateUrl('/apps/registration/api/v1/register'),
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json',
		}).done(function(json) {
			OCA.Registration.Status.success();
		}).fail(function(xhr) {
			OCA.Registration.Status.showErrorMessage(xhr.responseJSON.error, function(text) {
				return $('<li>').text(text);
			});
		});
	});

});
