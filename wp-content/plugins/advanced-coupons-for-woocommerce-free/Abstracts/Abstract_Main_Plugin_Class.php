<?php
namespace ACFWF\Abstracts;

use ACFWF\Interfaces\Model_Interface;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Abstract class that the main plugin class needs to extend.
 *
 * @since 1.0.0
 */
abstract class Abstract_Main_Plugin_Class
{

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
     */

    /**
     * Property that houses an array of all the "regular models" of the plugin.
     * All runnable models "SHOULD" be added inside this array for them to be ran.
     * All models inside this array is not automatically accessible to the external world.
     *
     * @since 1.0.0
     * @access protected
     * @var array
     */
    protected $_all_models = array();

    /**
     * Property that houses an array of all "public regular models" of the plugin.
     * Public models can be accessed and utilized by external entities via the main plugin class.
     *
     * When adding a public model, add them to the "_all_models" array first via the "add_to_all_plugin_models" function
     * for them to be ran.
     *
     * Then add them to "_models" array via the "add_to_public_models" function for them to be
     * accessible to the outside world.
     *
     * Ex. ACFWP->Public_Model->some_function();
     *
     * @since 1.0.0
     * @access protected
     * @var array
     */
    protected $_models = array();

    /**
     * Property that houses an array of all "public helper classes" of the plugin.
     * Can be accessed and utilized by external entities via the main plugin class.
     *
     * @since 1.0.0
     * @access protected
     * @var array
     */
    protected $_helpers = array();

    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
     */

    /**
     * Add a "regular model" to the main plugin class "all models" array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Model_Interface $model      Regular model.
     * @param string          $class_name Class name for get prop.
     */
    public function add_to_all_plugin_models(Model_Interface $model, $class_name = '')
    {
        if (!$class_name) {
            $class_reflection = new \ReflectionClass($model);
            $class_name       = $class_reflection->getShortName();
        }

        if (!array_key_exists($class_name, $this->_all_models)) {
            $this->_all_models[$class_name] = $model;
        }

    }

    /**
     * Add a "regular model" to the main plugin class "public models" array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Model_Interface $model      Regular model.
     * @param string          $class_name Class name for get prop.
     */
    public function add_to_public_models(Model_Interface $model, $class_name = '')
    {
        if (!$class_name) {
            $class_reflection = new \ReflectionClass($model);
            $class_name       = $class_reflection->getShortName();
        }

        if (!array_key_exists($class_name, $this->_models)) {
            $this->_models[$class_name] = $model;
        }

    }

    /**
     * Add a "helper class instance" to the main plugin class "public helpers" array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param object $helper     Helper class instance.
     * @param string $class_name Class name for get prop.
     */
    public function add_to_public_helpers($helper, $class_name = '')
    {
        if (!$class_name) {
            $class_reflection = new \ReflectionClass($helper);
            $class_name       = $class_reflection->getShortName();
        }

        if (!array_key_exists($class_name, $this->_helpers)) {
            $this->_helpers[$class_name] = $helper;
        }

    }

    /**
     * Access public models and helper models.
     * We use this magic method to automatically access data from the _models and _helpers property so
     * we do not need to create individual methods to expose each of the properties.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $prop Model to access.
     */
    public function __get($prop)
    {
        if (array_key_exists($prop, $this->_models)) {
            return $this->_models[$prop];
        } elseif (array_key_exists($prop, $this->_helpers)) {
            return $this->_helpers[$prop];
        } else {
            throw new \Exception("Trying to access unknown property " . $prop . " on Abstract_Main_Plugin_Class instance.");
        }

    }

}
