<?php
namespace CHAOS\Utilities\actions;
class RemoveMetadataAction extends BaseAction {
	
	public function __construct($arguments) {
		if(strlen($arguments[0]) != 36) {
			throw new \RuntimeException('The argument must be a 32 chars long UUID.');
		}
		$this->_metadataSchemaGUID = strtolower($arguments[0]);
	}
	
	public function apply($client, $object) {
		$occurances = array();
		foreach($object->Metadatas as $metadata) {
			if(strtolower($metadata->MetadataSchemaGUID) == $this->_metadataSchemaGUID) {
				$revisionID = $metadata->RevisionID;
				$languageCode = $metadata->LanguageCode;
				$client->Metadata()->Set($object->GUID, $this->_metadataSchemaGUID, $languageCode, $revisionID, null);
			}
		}
	}
	
}