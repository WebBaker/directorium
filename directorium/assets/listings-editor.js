
jQuery(document).ready(function($) {
	// Don't proceed if the directorium object is missing
	if (typeof directorium !== "object") return;

	// Extra prompt to prevent unproofed amendments from accidental submission
	if ($('input[name="directoriumAmendment"]').val() === '1')
		$("input#publish").click(function(event) {
	        var allow = window.confirm(directorium.strings.confirmAmendment);
			if (allow) return;
			// Otherwise, stop event propagation immediately!
			event.stopImmediatePropagation();
			return false;
		});
});