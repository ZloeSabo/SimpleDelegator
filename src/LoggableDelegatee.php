<?php

namespace ZloeSabo\SimpleDelegator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Evgeny Soynov<saboteur@saboteur.me>
 */
class LoggableDelegatee implements DelegateeInterface
{
    /** @var object */
    private $originalDelegatee;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param $originalDelegatee
     * @param LoggerInterface|null $logger
     */
    public function __construct($originalDelegatee, LoggerInterface $logger = null)
    {
        $this->originalDelegatee = $originalDelegatee;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function call($method, $args)
    {
        $reflectedClass = new \ReflectionClass($this->originalDelegatee);
        if($reflectedClass->hasMethod($method)) {
            $method = $reflectedClass->getMethod($method);
            $closure = $method->getClosure($this->originalDelegatee);

            $this->logger->debug(sprintf(
                'Calling method %s in context of %s',
                $method,
                $reflectedClass->getName()
            ), ['args' => $args]);

            return call_user_func_array($closure, $args);
        }

        throw new NoMethodException(sprintf(
            'Method %s does not exist on class %s',
            $method,
            $reflectedClass->getName()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function get($property)
    {
        $reflectedClass = new \ReflectionClass($this->originalDelegatee);
        if($reflectedClass->hasProperty($property)) {
            $property = $reflectedClass->getProperty($property);
            $property->setAccessible(true);

            $this->logger->debug(sprintf(
                'Getting property %s from %s',
                $property,
                $reflectedClass->getName()
            ));

            return $property->getValue($this->originalDelegatee);
        }

        throw new NoPropertyException(sprintf(
            'Property %s does not exist on class %s',
            $property,
            $reflectedClass->getName()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function set($property, $value)
    {
        $reflectedClass = new \ReflectionClass($this->originalDelegatee);
        if($reflectedClass->hasProperty($property)) {
            $property = $reflectedClass->getProperty($property);
            $property->setAccessible(true);

            $this->logger->debug(sprintf(
                'Setting property %s of %s',
                $property,
                $reflectedClass->getName()
            ), ['value' => $value]);

            return $property->setValue($this->originalDelegatee, $value);
        }

        throw new NoPropertyException(sprintf(
            'Property %s does not exist on class %s',
            $property,
            $reflectedClass->getName()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function propertyIsSet($property)
    {
        $reflectedClass = new \ReflectionClass($this->originalDelegatee);

        $this->logger->debug(sprintf(
            'Checking if property property %s of %s',
            $property,
            $reflectedClass->getName()
        ));

        if($reflectedClass->hasProperty($property)) {
            $checker = function($args) {
                return isset($this->$args);
            };
            $executed = $checker->bindTo($this->originalDelegatee, $reflectedClass->getName());

            return $executed($property);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetProperty($property)
    {
        $reflectedClass = new \ReflectionClass($this->originalDelegatee);
        if($reflectedClass->hasProperty($property)) {
            $unsetFunction = function($args) {
                unset($this->$args);
            };
            $executed = $unsetFunction->bindTo($this->originalDelegatee, $reflectedClass->getName());

            $this->logger->debug(sprintf(
                'Unsetting property %s of %s',
                $property,
                $reflectedClass->getName()
            ));

            return $executed($property);
        }

        throw new NoPropertyException(sprintf(
            'Tried to unset property %s, which does not exist on class %s',
            $property,
            $reflectedClass->getName()
        ));
    }
}