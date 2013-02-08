jQuery(document).ready(function($) {
	function rotateAdvice() {
		$("td.final div").hide(0, function() {
			var selected = $("select#type").val();
			switch (selected) {
				case "businesstypes":
				case "geographies":
				case "listings":
					var advicediv = "div#advice-"+selected;
					$(advicediv).show();
				break;
			}
		});
	}

	rotateAdvice();
	$("select#type").change(rotateAdvice);

	function removeCleanupMsg(div) {
		$("#cleanup-action").slideUp();
	}

	// Look for cleanup action div
	var cleanup = $("#cleanup-action");
	if (cleanup.length > 0) {
		// Get the cleanup link
		var cleanupLink = $(cleanup).find("a").attr("href");
		cleanupLink = cleanupLink + "&ajax=1";

		// Tell the system to cleanup without reloading the page
		$.get(cleanupLink, function(msg) {
			if (msg === "ok") {
				$(cleanup).find("p").html(directoriumText.success);
				setTimeout(removeCleanupMsg, 6200);
			}
		});
	}
});