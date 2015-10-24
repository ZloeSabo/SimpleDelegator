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

    public function __construct($originalDelegatee, LoggerInterface $logger = null)
    {
        $this->originalDelegatee = $originalDelegatee;
        $this->logger = $logger ?: new NullLogger();
    }

    public function call($name, $args) {}
    public function get($name) {}
    public function set($name, $args) {}
    public function propertyIsSet($name) {}
    public function unsetProperty($name) {}
}