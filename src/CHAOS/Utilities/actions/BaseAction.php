<?php
namespace CHAOS\Utilities\actions;
abstract class BaseAction {
	/**
	 * The arguments for the action.
	 * @param string[string] $arguments
	 */
	public abstract function __construct($arguments);
	
	/**
	 * Applies the action to the CHAOS object.
	 * @param \CHAOS\Portal\Client\PortalClient $client The CHAOS client to use for communication.
	 * @param \std_class $object The CHAOS object to apply the action to.
	 */
	public abstract function apply($client, $object);
}