<?php
namespace App\Services;

use App\Models\Toilet;
use App\Models\Location;
use Illuminate\Support\Facades\App;

class LocationService {

    public function getLocationByToilet(Toilet $toilet) {
        return $toilet->location;
    }

    public function validateUserLocationAccess($location_id) {
        $fetchLocations = App::make('fetch_locations');
        $fetchLocations->firstOrFail(function ($item) use ($location_id) {
            return $item->id == $location_id;
        });
    }
}