<?php

namespace CL\LunaJsonStore;

use CL\LunaCore\Model\AbstractModel;
use CL\LunaCore\Model\State;
use CL\LunaCore\Save\AbstractFind;

/*
 * @author     Ivan Kerin
 * @copyright  (c) 2014 Clippings Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Find extends AbstractFind
{
    /**
     * @param  mixed   $value
     * @param  mixed   $condition
     * @return boolean
     */
    public static function isConditionMatch($value, $condition)
    {
        if ($condition instanceof Not) {
            return $value !== $condition->getValue();
        } elseif (is_array($condition)) {
            return in_array($value, $condition);
        } else {
            return $value === $condition;
        }
    }

    /**
     * @var AbstractJsonRepo
     */
    private $repo;

    /**
     * @var array
     */
    private $conditions = array();

    /**
     * @var int
     */
    public $limit = null;

    /**
     * @var int
     */
    public $offset = 0;

    public function __construct(AbstractJsonRepo $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @return AbstractJsonRepo
     */
    public function getRepo()
    {
        return $this->repo;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param  string $property
     * @param  mixed $value
     * @return Find $this
     */
    public function where($property, $value)
    {
        $this->conditions[$property] = $value;

        return $this;
    }

    /**
     * @param  string $property
     * @param  mixed $value
     * @return Find $this
     */
    public function whereNot($property, $value)
    {
        $this->conditions[$property] = new Not($value);

        return $this;
    }

    /**
     * @param  string $property
     * @param  array $value
     * @return Find $this
     */
    public function whereIn($property, array $value)
    {
        $this->conditions[$property] = $value;

        return $this;
    }

    /**
     * @param  int $limit
     * @return Find $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param  int $limit
     * @return Find $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param  array   $properties
     * @return boolean
     */
    public function isMatch(array $properties)
    {
        foreach ($this->conditions as $propertyName => $condition) {

            if (! isset($properties[$propertyName])) {
                return false;
            }

            if (! self::isConditionMatch($properties[$propertyName], $condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array  $properties
     * @return AbstractModel
     */
    public function newModel(array $properties)
    {
        if ($this->getRepo()->getInherited()) {
            $class = $properties['class'];
            return new $class($properties, State::SAVED);
        } else {
            return $this->getRepo()->newInstance($properties, State::SAVED);
        }
    }

    /**
     * @return AbstractModel[]
     */
    public function execute()
    {
        $found = array();

        $contents = $this->repo->getContents();

        foreach ($contents as $properties) {
            if ($this->isMatch($properties)) {
                $found []= $this->newModel($properties);
            }
        }

        return $found;
    }
}