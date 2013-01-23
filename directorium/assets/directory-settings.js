jQuery(document).ready(function($) {
	var saveActionHighlighted = false;

	$("input").add("select").add("textarea").change(function() {
		if (saveActionHighlighted) return;
		saveActionHighlighted = true;
		var saveButton = $("#savesettings");

		// Wiggle!
		$(saveButton).removeClass("button-secondary").addClass("button-primary");
		$(saveButton).animate({ "margin-left": "+=9" }, 200, function() {
			$(saveButton).animate({ "margin-left": "-=9" }, 210)
		});
	});
});