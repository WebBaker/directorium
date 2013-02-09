jQuery(document).ready(function($) {
	var imageInputTemplate = '';
	var imageNextIndex = 2;
	var imageMaxAllowed = -1;

	// Check if a value for maximum allowed images has been set
	if (typeof directorium.listing.maxImages === "number")
		imageMaxAllowed = parseInt(directorium.listing.maxImages);

	// Copy the first image upload input to use as a template for creating additional inputs
	imageInputTemplate = '<div class="uploadinput">'+$(".imageuploadinputs .uploadinput").html()+'</div>';

	function countAttachedImages() {
		var totalAttachments = $(".attachedimages .attachment img.attachment-preview").length;
		var markedForDeletion = $(".attachedimages .attachment input:checked").length;
		return totalAttachments - markedForDeletion;
	}

	function countImageInputs() {
		return $(".imageuploadinputs input[type='file']").length;
	}

	function allowNewImageInput() {
		if (imageMaxAllowed == -1) return true;
		return ((countImageInputs() + countAttachedImages()) < imageMaxAllowed);
	}

	// Add further image upload inputs on request
	$(".imageuploadinputs .actions span#addimageuploadinput").click(function() {
		// Assign a new index to the input name
		var inputElements = imageInputTemplate;
		var templateInputName = "newlistingimage-1";
		var newInputName = "newlistingimage-"+imageNextIndex++;
		inputElements = inputElements.replace(templateInputName, newInputName);

		if (allowNewImageInput()) $(".imageuploadinputs .inputfields").append(inputElements);

		refreshRemoveActions();
		toggleAddNewButton();
	});

	// Allow inputs to be cleared/removed
	function refreshRemoveActions() {
		$(".imageuploadinputs .removeimageinput").clearQueue().click(function() {
			if (countImageInputs() > 1)	$(this).parent("div").remove();
		});
	}

	// Intelligently shows or hides the add new image input button
	function toggleAddNewButton() {
		var visible = false;

		if (imageMaxAllowed == -1) visible = true;
		if ((countImageInputs() + countAttachedImages()) < imageMaxAllowed) visible = true;

		if (visible) $(".imageuploadinputs .actions span#addimageuploadinput").show();
		else $(".imageuploadinputs .actions span#addimageuploadinput").hide();
	}

	// Remove upload input if already at the maximum allowed number of images
	function maybeRemoveImageInput() {
		var visible = false;

		if (imageMaxAllowed == -1) visible = true;
		if ((countImageInputs() + countAttachedImages()) < imageMaxAllowed) visible = true;

		$(".imageuploadinputs .inputfields .uploadinput").remove();
	}

	// Allow clicks on the "remove attachment" icon to check/uncheck the remove item checkbox
	// and also hide the image so it looks like it has indeed been removed
	$(".attachedimages .attachment .removeattachedimageicon").click(function() {
		var checkbox = $(this).siblings("input");
		if ($(checkbox).attr("checked") === "checked") $(checkbox).removeAttr("checked");
		else {
			$(checkbox).attr("checked", "checked");
			$(this).parent("div.attachment").hide();
			toggleAddNewButton();
		}
	});

	// Hide the "remove attachment" checkbox and label for a simpler interface
	$(".attachedimages .attachment label").hide();
	$(".attachedimages .attachment input[type='checkbox']").hide();

	// Initial setup
	maybeRemoveImageInput();
	toggleAddNewButton();
	refreshRemoveActions();
});