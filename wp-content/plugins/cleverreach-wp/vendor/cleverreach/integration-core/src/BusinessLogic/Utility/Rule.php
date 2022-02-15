<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility;

/**
 * Class Rule
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility
 */
class Rule
{
    /**
     * Rule field code.
     *
     * @var string
     */
    private $field;
    /**
     * Rule logic (contains).
     *
     * @var string
     */
    private $logic;
    /**
     * Rule condition.
     *
     * @var string
     */
    private $condition;

    /**
     * Rule constructor.
     *
     * @param string $field Field code.
     * @param string $logic Logic operator.
     * @param string $condition Rule condition.
     */
    public function __construct($field, $logic, $condition)
    {
        $this->field = $field;
        $this->logic = $logic;
        $this->condition = $condition;
    }

    /**
     * Get field code.
     *
     * @return string
     *   Field code.
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set field code.
     *
     * @param string $field Field code.
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * Get logic.
     *
     * @return string
     *   Field logic.
     */
    public function getLogic()
    {
        return $this->logic;
    }

    /**
     * Set logic.
     *
     * @param string $logic Field logic.
     */
    public function setLogic($logic)
    {
        $this->logic = $logic;
    }

    /**
     * Get field condition.
     *
     * @return string
     *   Field condition.
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set field condition.
     *
     * @param string $condition Field condition.
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * Array representation of Rule object.
     *
     * @return array
     *   Associative array
     *   [
     *     'field' => 'some_field',
     *     'logic' => 'contains',
     *     'condition' => 'some'
     *   ]
     */
    public function toArray()
    {
        return array(
            'field' => $this->field,
            'logic' => $this->logic,
            'condition' => $this->condition
        );
    }
}
