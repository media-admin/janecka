<?php
/**
 * StoreaBill REST Functions
 *
 * REST related functions.
 *
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check permissions of objects on REST API.
 *
 * @since 1.0.0
 * @param string $object_type Object type.
 * @param string $context   Request context.
 * @param int    $object_id Object ID.
 *
 * @return bool
 */
function sab_rest_check_permissions( $object_type, $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'batch'  => 'edit_others_' . $object_type . 's',
		'create' => 'create_' . $object_type . 's',
		'delete' => 'delete_' . $object_type . 's',
	);

	$cap        = array_key_exists( $context, $contexts ) ? $contexts[ $context ] : $context . '_' . $object_type;
	$permission = current_user_can( $cap, $object_id );

	return apply_filters( 'storeabill_rest_check_permissions', $permission, $context, $object_id, $object_type );
}