<?php
class Geocoder {
	public static function lookup($location_string) {
		$api = 'http://maps.googleapis.com/maps/api/geocode/json';
		$api_call = new CurlRequest($api);
		$api_call->setParams(array(
			'sensor' => 'false',
			'address' => $location_string
		));

		try {
			$result = $api_call->execute();
			$geocode = json_decode($result['body']);
			if($geocode->status != 'OK') return false;

			return $geocode->results[0]->geometry->location;
		} catch (Exception $e) {
			return false;
		}
	}
}
