<?php
namespace CHAOS\Utilities\actions;
class UnpublishObjectAction extends BaseAction {
	
	public function __construct($arguments) {
		
	}
	
	public function apply($client, $object) {
		$accesspoints = $object->AccessPoints;
		foreach($accesspoints as $accesspoint) {
			$accesspointGUID = $accesspoint->AccessPointGUID;
			// Check if the object is published on the accesspoint.
			if(self::isPublished($object, $accesspointGUID)) {
				printf("\t* Unpublishing %s from accesspoint = %s\n", $object->GUID, $accesspointGUID);
				$response = $client->Object()->SetPublishSettings($object->GUID, $accesspointGUID);
				if(!$response->WasSuccess()) {
					throw new RuntimeException("Couldn't set publish settings: {$response->Error()->Message()}");
				}
				if(!$response->MCM()->WasSuccess()) {
					throw new RuntimeException("Couldn't set publish settings: (MCM) {$response->MCM()->Error()->Message()}");
				}
			} else {
				// sprintf("Skipping the unpublishing of %s from accesspoint = %s: It's not published there anyway.", $object->GUID, $accesspointGUID);
			}
		}
	}
	
	public static function isPublished($object, $accesspoint_guid) {
		$now = new \DateTime();
		foreach($object->AccessPoints as $accesspoint) {
			if($accesspoint_guid === null || strtolower($accesspoint_guid) === strtolower($accesspoint->AccessPointGUID)) {
				// Check the start and end dates.
				if($accesspoint->StartDate == null) {
					continue; // Skipping something which has no start date set.
				}
				$startDate = new \DateTime();
				$startDate->setTimestamp($accesspoint->StartDate);
				// Is now after the start date?
				if($startDate < $now) {
					// Is the end date not sat? I.e. is it at the end of our time?
					if($accesspoint->EndDate == null) {
						return true;
					} else {
						$endDate = new \DateTime();
						$endDate->setTimestamp($accesspoint->EndDate);
						// Are we still publishing this?
						if($now < $endDate) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}
	
}