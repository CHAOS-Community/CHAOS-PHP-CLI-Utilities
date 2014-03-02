<?php
namespace CHAOS\Utilities\filters;
class HasMetadataFilter extends BaseFilter {
	protected $_metadataSchemaGUID = null;
	
	public function __construct($arguments) {
		if(strlen($arguments[0]) != 36) {
			throw new \RuntimeException('The argument must be a 32 chars long UUID.');
		}
		$this->_metadataSchemaGUID = strtolower($arguments[0]);
	}
	
	public function apply($object) {
		foreach($object->Metadatas as $metadata) {
			if(strtolower($metadata->MetadataSchemaGUID) == $this->_metadataSchemaGUID) {
				return true;
			}
		}
		return false;
	}
}