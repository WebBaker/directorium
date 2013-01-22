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
		return in_array($listingID, self::getListingsForUser($userID));
	}


	/**
	 * Returns a (possibly empty) array of listing IDs that the user owns.
	 *
	 * @param $userID
	 * @return array
	 */
	public static function getListingsForUser($userID) {
		return get_user_meta($userID, self::USER_META, false);
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