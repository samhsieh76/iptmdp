<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() {
        $main_page = "";
        if (Auth::user()->can('locations.index')) {
            $main_page = route('locations.index');
        } else if (Auth::user()->can('toilets.index')) {
            $fetchLocations = App::make('fetch_locations');
            $location = $fetchLocations->first();
            if ($location) {
                $main_page = route('toilets.index', [$location->id]);
            }
        }
        if (!empty($main_page)) {
            return view('home', compact('main_page'));
        }
        Auth::logout();
        return view('not_location');
    }
}
