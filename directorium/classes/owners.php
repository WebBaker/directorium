<?php
namespace Directorium;
use WP_User_Query as WP_User_Query;


class Owners {
	const USER_META = 'directoriumListings';

	/**
	 * Links a user to a listing, making them the definitive "owner".
	 *
	 * @param $userID
	 * @param $listingID
	 * @return bool
	 */
	public static function addOwnership($userID, $listingID) {
		if (self::hasOwnership($userID, $listingID)) return true;
		return add_user_meta($userID, self::USER_META, (int) $listingID);
	}


	/**
	 * Checks to see if the specified user owns the specified listing.
	 *
	 * @param $userID
	 * @param $listingID
	 * @return bool
	 */
	public static function hasOwnership($userID, $listingID) {
		return in_array($listingID, self::getListingsForUser($userID, true));
	}


	/**
	 * Returns a (possibly empty) array of listing IDs that the user owns. Trashed listings
	 * will be filtered out.
	 *
	 * Only original/source listings (not amendments) will be returned,
	 * therefore comparisons should be against $listing->originalID if the Listing object is
	 * in amendment mode.
	 *
	 * @param int $userID
	 * @param bool $includeAmendments = false
	 * @return array
	 */
	public static function getListingsForUser($userID, $includeAmendments = false) {
		global $wpdb;

		// Get all listings, convert to ints
		$listingPosts = get_user_meta($userID, self::USER_META, false);
		foreach ($listingPosts as &$postID) $postID = absint($postID);

		// Form the where clause
		$where = "( `post_type` ='".Listing::POST_TYPE."' ";
		if ($includeAmendments) $where .= " OR `post_type` = '".Listing::AMENDMENT_TYPE."'";
		$where .= " ) AND (`ID` = '".join("' OR `ID` = '", $listingPosts)."')";

		// Filter out trashed posts
		$results = $wpdb->get_results("SELECT `ID` FROM {$wpdb->posts} WHERE `post_status` != 'trash' AND $where ;");

		$activeListings = array();
		foreach ($results as $row) $activeListings[] = absint($row->ID);

		return $activeListings;
	}


	/**
	 * Removes the link between user and listing. If the link is removed or
	 * does not already exist returns true; if the link *cannot* be removed
	 * returns false.
	 *
	 * @param $userID
	 * @param $listingID
	 * @return bool
	 */
	public static function removeOwnership($userID, $listingID) {
		if (self::hasOwnership($userID, $listingID))
			return delete_user_meta($userID, self::USER_META, $listingID);
		return true;
	}


	/**
	 * Finds the owner(s) if any of this listing. Returns an array of user IDs
	 * which may be empty if no one is the assigned owner.
	 *
	 * @param int $listingID
	 * @return array
	 */
	public static function whoOwnsThis($listingID) {
		$owners = new WP_User_Query(array(
			'meta_query' => array(array(
					'key' => self::USER_META,
					'value' => $listingID,
					'compare' => '='
				)),
			'fields' => 'ID'));

		return (array) $owners->results;
	}


	/**
	 * Determines if the listing is owned by a user.
	 *
	 * Convenience function: this simply wraps whoOwnsThis() and checks the size
	 * of the returned array.
	 *
	 * @param $listingID
	 * @return bool
	 */
	public static function isOwnedBySomeone($listingID) {
		$owners = (array) self::whoOwnsThis($listingID);
		return (count($owners) < 1) ? false : true;
	}
}