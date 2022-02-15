<?php

namespace Vendidero\StoreaBill\Admin;

defined( 'ABSPATH' ) || exit;

class Notices {

	/**
	 * Adds an admin notice. By default the notice is user-specific but not bound to a certain screen id.
	 *
	 * @param $message
	 * @param string $type
	 * @param bool $screen_id
	 * @param bool $is_global
	 */
	public static function add( $message, $type = 'error', $screen_id = false, $is_global = false ) {
		if ( false === $screen_id ) {
			$screen_id = self::get_current_screen_id();
		}

		$screen_id = self::format_screen_id( $screen_id );
		$notices   = self::get( $screen_id, false, $is_global );
		$notices[] = array( 'type' => $type, 'message' => $message );

		self::update( $notices, $screen_id, $is_global );
	}

	/**
	 * Outputs admin notices.
	 *
	 * @param bool $screen_id
	 * @param bool $type
	 * @param bool $is_global
	 */
	public static function output( $screen_id = false, $type = false, $is_global = false ) {
		$notices = self::get( $screen_id, $type, $is_global );

		if ( ! empty( $notices ) ) {
			foreach( $notices as $notice ) {
				$notice = wp_parse_args( $notice, array(
					'type'    => 'error',
					'message' => ''
				) );

				if ( ! empty( $notice ) ) {
					$notice_type = ( 'success' === $notice['type'] ? 'updated' : $notice['type'] );
					echo '<div id="message" class="' . $notice_type . ' notice is-dismissible sab-notice">' . wpautop( $notice['message'] ) . ' <button type="button" class="notice-dismiss"></button></div>';
				}
			}

			self::remove( $screen_id, $type, $is_global );
		}
	}

	protected static function get_current_screen_id() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( $screen ) {
			$screen_id = $screen->id;
		}

		return self::format_screen_id( $screen_id );
	}

	protected static function format_screen_id( $screen_id = '' ) {
		$screen_id = str_replace( 'woocommerce_page_', '', $screen_id );

		return $screen_id;
	}

	protected static function get_meta_key( $screen_id = '' ) {
		$notice_key = '_sab_notices' . ( ! empty( $screen_id ) ? '_' . $screen_id : '' );

		return $notice_key;
	}

	/**
	 * Get admin notices.
	 *
	 * @param bool $screen_id
	 * @param bool $type
	 * @param bool $is_global
	 *
	 * @return array|mixed
	 */
	public static function get( $screen_id = false, $type = false, $is_global = false ) {
		if ( false === $screen_id ) {
			$screen_id = self::get_current_screen_id();
		}

		$screen_id = self::format_screen_id( $screen_id );
		$notices   = self::fetch( $screen_id, $is_global );

		if ( $type ) {
			foreach( $notices as $key => $notice ) {
				$notice = wp_parse_args( $notice, array( 'type' => 'error' ) );

				if ( $type !== $notice['type'] ) {
					unset( $notices[ $key ] );
				}
			}

			$notices = array_values( $notices );
		}

		return $notices;
	}

	/**
	 * Remove notices
	 *
	 * @param bool $screen_id
	 * @param bool $type
	 * @param bool $is_global
	 */
	public static function remove( $screen_id = false, $type = false, $is_global = false ) {
		if ( false === $screen_id ) {
			$screen_id = self::get_current_screen_id();
		}

		$screen_id = self::format_screen_id( $screen_id );

		if ( $type ) {
			$notices = self::get( $screen_id, false, $is_global );

			foreach( $notices as $key => $notice ) {
				$notice = wp_parse_args( $notice, array( 'type' => 'error' ) );

				if ( $type === $notice['type'] ) {
					unset( $notices[ $key ] );
				}
			}

			$notices = array_values( $notices );

			if ( ! empty( $notices ) ) {
				self::update( $notices, $screen_id, $is_global );
			} else {
				self::delete( $screen_id, $is_global );
			}
		} else {
			self::delete( $screen_id, $is_global );
		}
	}

	protected static function update( $notices, $screen_id, $is_global = false ) {
		if ( ! $is_global ) {
			update_user_meta( get_current_user_id(), self::get_meta_key( $screen_id ), $notices );
		} else {
			update_option( self::get_meta_key( $screen_id ), $notices );
		}
	}

	protected static function fetch( $screen_id, $is_global = false ) {
		if ( ! $is_global ) {
			$notices = get_user_meta( get_current_user_id(), self::get_meta_key( $screen_id ), true );
		} else {
			$notices = get_option( self::get_meta_key( $screen_id ) );
		}

		if ( ! is_array( $notices ) ) {
			$notices = array( $notices );
		}

		return array_filter( $notices );
	}

	protected static function delete( $screen_id, $is_global = false ) {
		if ( ! $is_global ) {
			delete_user_meta( get_current_user_id(), self::get_meta_key( $screen_id ) );
		} else {
			delete_option( self::get_meta_key( $screen_id ) );
		}
	}
}