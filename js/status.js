(function(root) {

	OCA.Registration = OCA.Registration || {};

	var setStatus = function(post, pre) {
		$("body")
			.removeClass(pre)
			.addClass(post);
	};
	var showDecorated = function(elem, msgs, decorator) {
		if (decorator) {
			msgs = msgs.map(function(msg) {
				return decorator(msg);
			});
		}
		$(elem).empty();
		msgs.forEach(function(msg) {
			$(elem).append(msg);
		});
	};
	OCA.Registration.Status = {
		success: function() {
			setStatus("success", "error");
		},
		showSuccessMessage: function(msg, decorator) {
			this.showSuccessMessages([msg], decorator);
		},
		showSuccessMessages: function(msgs, decorator) {
			showDecorated($(".msg.success"), msgs, decorator);
			this.success();
		},
		error: function() {
			setStatus("error", "success");
		},
		showErrorMessage: function(err, decorator) {
			this.showErrorMessages([err], decorator);
		},
		showErrorMessages: function(errs, decorator) {
			showDecorated($(".msg.error"), errs, decorator);
			this.error();
		},
	};

})(window);
