<?php

namespace ZloeSabo\SimpleDelegator;

/**
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
interface DelegateeInterface
{
    /**
     * Call given method in context of original delegatee. Should ignore visibility
     * @param string $method name of method
     * @param array $args array of method arguments
     * @return mixed
     */
    public function call($method, $args);

    /**
     * Get given property value from original delegatee. Should ignore visibility
     * @param string $property name of required property
     * @return mixed
     */
    public function get($property);

    /**
     * Set property value on original delegatee. Should ignore visibility
     * @param string $property
     * @param mixed $value
     */
    public function set($property, $value);

    /**
     * Checks if property is set on original delegatee. Should ignore visibility
     * @param string $property
     * @return boolean
     */
    public function propertyIsSet($property);

    /**
     * Unsets property on original delegatee. Should ignore visibility
     * @param string $property
     */
    public function unsetProperty($property);
}
