<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Auth;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RestrictAPIController extends Controller
{
    public function checkAuth(){
        if(Auth::user()){
            return 0;
        }
        return 1;
    }


    public function getIp(){
       return request()->ip();
    }
}
