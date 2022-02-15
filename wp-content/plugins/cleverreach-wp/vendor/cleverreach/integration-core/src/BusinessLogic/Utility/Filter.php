<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility;

/**
 * Class Filter
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility
 */
class Filter
{
    const CLASS_NAME = __CLASS__;

    /**
     * Filter unique identifier.
     *
     * @var int
     */
    private $id;
    /**
     * Filter name.
     *
     * @var string
     */
    private $name;
    /**
     * Filter operator.
     *
     * @var string
     */
    private $operator;
    /**
     * List of rules applied on filter instance.
     *
     * @var Rule[]
     */
    private $allRules = array();

    /**
     * Filter constructor.
     *
     * @param string $name Filter name.
     * @param Rule $rule Filter rule.
     * @param string $operator Filter operator.
     */
    public function __construct($name, Rule $rule, $operator = null)
    {
        $this->name = $name;
        $this->operator = $operator ?: 'AND';
        $this->allRules[] = $rule;
    }

    /**
     * Convert object to array.
     *
     * @return array
     *   Array representation of object.
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'operator' => $this->operator,
            'rules' => $this->rulesToArray()
        );
    }

    /**
     * Get all rules applied on filter instance.
     *
     * @return Rule[]
     *   List of rules.
     */
    public function getAllRules()
    {
        return $this->allRules;
    }

    /**
     * Set rules.
     *
     * @param Rule[] $allRules Rules to be set. It will overwrite all existing rules.
     */
    public function setAllRules(array $allRules)
    {
        $this->allRules = $allRules;
    }

    /**
     * Get filter ID.
     *
     * @return int
     *   Filter ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set filter ID.
     *
     * @param int $id Filter ID.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get filter name.
     *
     * @return string
     *   Filter name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set filter name.
     *
     * @param string $name Filter name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets first condition in list of rules.
     *
     * @return string
     *   Gets first condition.
     */
    public function getFirstCondition()
    {
        return $this->allRules[0]->getCondition();
    }

    /**
     * Add rule to the end of the list of applied rules.
     *
     * @param Rule $rule Instance of @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Rule
     */
    public function addRule(Rule $rule)
    {
        $this->allRules[] = $rule;
    }

    /**
     * Converts allRules[Rule] to allRules[array[]]
     *
     * @return array[array]
     *   Array representation of applied rules.
     */
    private function rulesToArray()
    {
        $ret = array();
        foreach ($this->allRules as $rule) {
            $ret[] = $rule->toArray();
        }

        return $ret;
    }
}
