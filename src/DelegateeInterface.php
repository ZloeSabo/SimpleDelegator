<?php


namespace ZloeSabo\SimpleDelegator;


/**
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
interface DelegateeInterface
{
    public function call($name, $args);
    public function get($name);
    public function set($name, $args);
    public function propertyIsSet($name);
    public function unsetProperty($name);
}