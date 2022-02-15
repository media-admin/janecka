<?php
namespace ACFWF\Interfaces;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstraction that provides contract relating to deactivation.
 * Any model that needs some code executed on plugin deactivation must implement this interface.
 *
 * @since 1.0
 */
interface Deactivatable_Interface {

    /**
     * Contract for deactivate.
     *
     * @since 1.0.1
     * @access public
     */
    public function deactivate();

}