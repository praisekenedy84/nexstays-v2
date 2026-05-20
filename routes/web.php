<?php

use App\Support\HbmsNavigation;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central app — landlord domain only (no tenant context)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect(HbmsNavigation::tenantHomeUrl());
});
