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
			toggleAddNewButton();
		});

		setupLiveCounts(); // Refresh the live count behaviours at the same time
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

	// Provides a version of the editor content, stripped of any HTML tags
	function getStrippedEditorContent() {
		var content = $(".directorium.listing-editor #listingcontent").val();
		return content.replace(/<(?:.|\s)*?>/g, "")
	}

	// Updates the live count of words used in the listing content editor
	function doWordCountUpdate() {
		// We need a valid maximum value - and we won't handle updates if there is no limit (maxWords === -1)
		if (typeof directorium.listing.maxWords !== "number" || directorium.listing.maxWords < 1 ) return;

		// Get the editor content - tags and double whitespaces removed - and count the number of words
		var content = getStrippedEditorContent().replace(/\s{2,}/g, " ");
		var wordCount = content.split(" ").length;

		// Get the usage format and use it to update the actual count (we expect two placeholders %d)
		var countOutput = directorium.listing.usageFormat;
		countOutput = countOutput.replace("%d", wordCount).replace("%d", directorium.listing.maxWords)

		$("dd.wordcount").html(countOutput);
	}

	// Updates the live count of characters used in the listing content editor
	function doCharCountUpdate() {
		// We need a valid maximum value - and we won't handle updates if there is no limit (maxWords === -1)
		if (typeof directorium.listing.maxWords !== "number" || directorium.listing.maxWords < 1 ) return;

		// Get the editor content (tags removed only) and count the number of characters
		var charCount = getStrippedEditorContent().length;

		// Get the usage format and use it to update the actual count (we expect two placeholders %d)
		var countOutput = directorium.listing.usageFormat;
		countOutput = countOutput.replace("%d", charCount).replace("%d", directorium.listing.maxChars)

		$("dd.charcount").html(countOutput);
	}

	// Updates the live count of selected taxonomies
	function doTaxCountUpdate() {
		var count = 0;

		$(this).find("input[type='checkbox']").each(function() {
			if ($(this).attr("checked") === "checked") count++;
		});

		var counter = $(this).siblings("dl.editorialcontrol").find("dd");

		// Get the taxonomy type so we can update the correct counter text
		var taxonomy = $(counter).attr("class").toString().match(/\S{1,20}count/);
		if (taxonomy.length !== 1) return;
		taxonomy = taxonomy[0].replace("count", "");

		// Do we have a valid maximum value?
		taxonomyKey = "max" + taxonomy.charAt(0).toUpperCase() + taxonomy.substr(1);
		if (typeof directorium.listing[taxonomyKey] !== "number" || directorium.listing[taxonomyKey] < 1) return;

		// Get the usage format and use it to update the actual count (we expect two placeholders %d)
		var countOutput = directorium.listing.usageFormat;
		countOutput = countOutput.replace("%d", count).replace("%d", directorium.listing[taxonomyKey])

		$(counter).html(countOutput);
	}

	// Updates the live count of images
	function doImgCountUpdate() {
		// We need a valid maximum value - and we won't handle updates if there is no limit (maxWords === -1)
		if (typeof directorium.listing.maxImages !== "number" || directorium.listing.maxImages < 1 ) return;

		var existingImages = $(".media.images .attachment img.attachment-preview").length;
		var markedForDeletion = 0;
		var newUploads = 0;

		$(".media.images .attachment input[type='checkbox']").each(function() {
			if ($(this).attr("checked") === "checked") markedForDeletion++;
		})

		$(".media.images .uploadinput input[type='file']").each(function() {
			if ($(this).val().length > 0) newUploads++;
		})

		var count = existingImages - markedForDeletion + newUploads;

		// Get the usage format and use it to update the actual count (we expect two placeholders %d)
		var countOutput = directorium.listing.usageFormat;
		countOutput = countOutput.replace("%d", count).replace("%d", directorium.listing.maxImages)

		$("dd.imagecount").html(countOutput);
	}

	// Enable live updates for the word, character, image and taxonomy counts
	function setupLiveCounts() {
		// We need the usage format for updates
		if (typeof directorium.listing.usageFormat !== "string") return;

		if ($("dt.wordcount").length >= 1) $(".directorium.listing-editor #listingcontent").clearQueue().keyup(doWordCountUpdate);
		if ($("dt.charcount").length >= 1) $(".directorium.listing-editor #listingcontent").clearQueue().keyup(doCharCountUpdate);
		if ($("dt.geoscount").length >= 1) $(".directorium.listing-editor .tax-selector-box.geos").clearQueue().change(doTaxCountUpdate);
		if ($("dt.btypescount").length >= 1) $(".directorium.listing-editor .tax-selector-box.btypes").clearQueue().change(doTaxCountUpdate);
		// For images we need to catch file input changes and existing image deletions
		if ($("dt.imagecount").length >= 1) $(".directorium.listing-editor .media.images input").clearQueue().change(doImgCountUpdate);
		if ($("dt.imagecount").length >= 1) $(".directorium.listing-editor .media.images img.removeattachedimageicon").clearQueue().click(doImgCountUpdate);
	}


	// Initial setup
	maybeRemoveImageInput();
	toggleAddNewButton();
	refreshRemoveActions();
	setupLiveCounts();
});