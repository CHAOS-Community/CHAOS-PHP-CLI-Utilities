<?php
namespace CHAOS\Utilities\filters;
abstract class BaseFilter {
	/**
	 * The arguments for the filter.
	 * @param string[string] $arguments
	 */
	public abstract function __construct($arguments);
	
	/**
	 * Applies the filter to the CHAOS object.
	 * @param \CHAOS\Portal\Client\PortalClient $client The CHAOS client to use for communication.
	 * @param \std_class $object The CHAOS object to apply the action to.
	 * @return boolean True if the object passes the filter an actions can be applied to it, false otherwise.
	 */
	public abstract function apply($object);
}