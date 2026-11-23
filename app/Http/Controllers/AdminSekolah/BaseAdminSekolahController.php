<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

abstract class BaseAdminSekolahController extends Controller
{
    protected function getSchoolId(): int
    {
        return Auth::user()->school_id;
    }
}
