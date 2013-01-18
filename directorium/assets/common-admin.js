jQuery(document).ready(function($) {
	/**
	 * Hides the first element from noticeList using slideUp(), sets an
	 * interval and calls itself again until noticeList is empty.
	 */
	function shiftWaitRepeat(elements) {
		// Don't continue if the array is empty
		if (noticeList.length < 1) return;

		// Hide the last element then repeat
		$(noticeList.shift()).slideUp(400);
		setTimeout(shiftWaitRepeat, 650);
	}

	/**
	 * Hides notices from the admin page incrementally, using slideUp().
	 */
	function removeNotices() {
		$("div#notices .notice").each(function() { noticeList.push(this); });
		shiftWaitRepeat();
	}

	/*
	 * Build a list of notice divs then set about removing them one-by-one
	 */
	var notices = $("div#notices .notice");
	var noticeList = new Array();
	if (notices.length > 0) setTimeout(removeNotices, 6200);

});