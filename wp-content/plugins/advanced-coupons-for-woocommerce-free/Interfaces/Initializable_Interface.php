<?php
namespace ACFWF\Interfaces;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstraction that provides contract relating to initialization.
 * Any model that needs some executed on init needs to implement this interface.
 *
 * @since 1.0
 */
interface Initializable_Interface {

    /**
     * Contract for initialization.
     *
     * @since 1.0
     * @access public
     */
    public function initialize();

}