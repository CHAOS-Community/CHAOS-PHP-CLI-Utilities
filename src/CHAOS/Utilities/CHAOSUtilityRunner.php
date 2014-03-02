<?php
namespace CHAOS\Utilities;

class CHAOSUtilityRunner {
	
	const CLIENT_GUID = "f666b4a2-3a15-4328-a7a8-831eac282e9f";
	const RELATIVE_LIB_PATH = "/../../../lib";
	const SELECT_ALL_QUERY = "*:*";
	const PAGE_SIZE = 500;
	const FILTER_CLASS = '\\CHAOS\\Utilities\\filters\\%sFilter';
	const ACTION_CLASS = '\\CHAOS\\Utilities\\actions\\%sAction';

	static function main($arguments = array()) {
		self::printLogo();
		self::loadLibraries();
		// Append this projects src folder
		set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../../');
		// Register the class loader.
		require_once('CaseSensitiveAutoload.php');
		spl_autoload_extensions(".php");
		spl_autoload_register("CaseSensitiveAutoload");
		// Start the runner.
		$h = new self($arguments);
		$h->run();
	}

	/** @var array[string]string */
	protected $_options;
	
	/** @var \CHAOS\Portal\Client\EnhancedPortalClient */
	protected $_client;

	/** @var string */
	protected $_query;

	/** @var \CHAOS\Utilities\filters\BaseFilter[] */
	protected $_filters;
	
	/** @var \CHAOS\Utilities\actions\BaseAction[] */
	protected $_actions;
	
	function __construct($arguments = array()) {
		$this->_options = self::extractOptionsFromArguments($arguments);

		// Check and parse the runtime options.
		if(array_key_exists('query', $this->_options) && $this->_options['query'] != "") {
			$this->_query = $this->_options['query'];
		} else {
			$this->_query = self::SELECT_ALL_QUERY;
		}
		
		$this->_filters = array();
		if(array_key_exists('filter', $this->_options)) {
			if(is_string($this->_options['filter'])) {
				$this->_options['filter'] = array($this->_options['filter']);
			}
			foreach($this->_options['filter'] as $filter) {
				$filter = $this->parseFunction($filter);
				$filter_class = sprintf(self::FILTER_CLASS, $filter['name']);
				$this->_filters[] = new $filter_class($filter['arguments']);
			}
		}
		
		if(array_key_exists('action', $this->_options)) {
			if(is_string($this->_options['action'])) {
				$this->_options['action'] = array($this->_options['action']);
			}
			foreach($this->_options['action'] as $action) {
				$action = $this->parseFunction($action);
				$action_class = sprintf(self::ACTION_CLASS, $action['name']);
				$this->_actions[] = new $action_class($action['arguments']);
			}
		}
		
		if(count($this->_actions) == 0) {
			throw new \RuntimeException('No action given.');
		}
		
		if(array_key_exists('CHAOS_URL', $_SERVER) === false) {
			throw new \RuntimeException('The environment variable "CHAOS_URL" needs to be set.');
		}
		if(array_key_exists('CHAOS_EMAIL', $_SERVER) === false) {
			throw new \RuntimeException('The environment variable "CHAOS_EMAIL" needs to be set.');
		}
		if(array_key_exists('CHAOS_PASSWORD', $_SERVER) === false) {
			throw new \RuntimeException('The environment variable "CHAOS_PASSWORD" needs to be set.');
		}
		
		// Load the CHAOS client.
		$this->_client = new \CHAOS\Portal\Client\EnhancedPortalClient($_SERVER['CHAOS_URL'], self::CLIENT_GUID);
		$this->_client->EmailPassword()->Login($_SERVER['CHAOS_EMAIL'], $_SERVER['CHAOS_PASSWORD']);
	}
	
	protected static function printLogo() {
		echo " ______________                      \n";
		echo " __  ____/__  /_______ ______________\n";
		echo " _  /    __  __ \  __ `/  __ \_  ___/\n";
		echo " / /___  _  / / / /_/ // /_/ /(__  ) \n";
		echo " \____/  /_/ /_/\__,_/ \____//____/  \n";
		echo " Utility v.0.1                     \n";
		echo "\n";
	}
	
	protected static function loadLibraries() {
		$libs_path = __DIR__ . self::RELATIVE_LIB_PATH;
		foreach(scandir($libs_path) as $folder) {
			if($folder != '.' && $folder != '..' && is_dir($libs_path . '/' . $folder)) {
				$source_path = $libs_path . '/' . $folder . '/src';
				if(is_dir($source_path)) {
					$source_path = realpath($source_path);
					set_include_path(get_include_path() . PATH_SEPARATOR . $source_path);
				}
			}
		}
	}
	
	protected static function extractOptionsFromArguments($arguments) {
		$result = array();
		for($i = 0; $i < count($arguments); $i++) {
			if(strpos($arguments[$i], '--') === 0) {
				$equalsIndex = strpos($arguments[$i], '=');
				if($equalsIndex === false) {
					$name = substr($arguments[$i], 2);
					$result[$name] = true;
				} else {
					$name = substr($arguments[$i], 2, $equalsIndex-2);
					$value = substr($arguments[$i], $equalsIndex+1);
					if($value == 'true') {
						$value = true;
					} elseif($value == 'false') {
						$value = false;
					}
					if(array_key_exists($name, $result) && is_array($result[$name])) {
						$result[$name][] = $value;
					} elseif (array_key_exists($name, $result)) {
						$result[$name] = array($result[$name], $value);
					} else {
						$result[$name] = $value;
					}
				}
			}
		}
		return $result;
	}

	public function run() {
		$response = $this->_client->Object()->Get($this->_query, null, null, 0, 0);
		printf("A total of %u objects selected.\n", $response->MCM()->TotalCount());
		$pageIndex = 0;
		$objectPosition = 1;
		do {
			$response = $this->_client->Object()->Get($this->_query, null, null, $pageIndex, self::PAGE_SIZE, true, true, true, true);
			foreach($response->MCM()->Results() as $object) {
				printf("Parsing object %u/%u: ", $objectPosition, $response->MCM()->TotalCount());
				$passes_filters = $this->passesFilters($object);
				if($passes_filters === true) {
					// Apply the action.
					echo "Filters passed - applying actions!\n";
					$this->applyActions($object);
				} else {
					echo "Object didn't pass the filters.\n";
				}
				//var_dump($object);
				$objectPosition++;
			}
			$pageIndex++;
			// Continue as long as results are returned from CHAOS.
		} while($pageIndex * self::PAGE_SIZE < $response->MCM()->TotalCount());
	}
	
	public function parseFunction($function_string) {
		$arguments_start_index = strpos($function_string, '(');
		$arguments_end_index = strpos($function_string, ')');
		if($arguments_start_index === false || $arguments_end_index === false || $arguments_start_index > $arguments_end_index) {
			throw new \InvalidArgumentException('The function string given, has to have both the ( and ) chars in it, in this order.');
		}
		$function_name = substr($function_string, 0, $arguments_start_index);
		$arguments = substr($function_string, $arguments_start_index + 1, $arguments_end_index - $arguments_start_index - 1);
		$arguments = explode(',', $arguments);
		return array(
			'name' => $function_name,
			'arguments' => $arguments
		);
	}
	
	public function passesFilters($object) {
		foreach($this->_filters as $filter) {
			if($filter->apply($object) === false) {
				return false;
			}
		}
		return true;
	}
	
	public function applyActions($object) {
		foreach($this->_actions as $action) {
			echo "\t* " . get_class($action) . "\n";
			$action->apply($this->_client, $object);
		}
	}
}

CHAOSUtilityRunner::main($_SERVER['argv']);