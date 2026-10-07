<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;

class CityController extends Controller
{
    public function index()
    {
        return City::orderBy('name')->get(['id', 'name', 'region', 'latitude', 'longitude']);
    }
}
