<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Designation;

class DesignationController extends Controller
{
    //
    public static function index()
    {
        $users = Designation::all();
        return (
            $users
           
        );
    }
    
}
