<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Crypt;
use App\Models\User;
 


class EncController extends Controller
{
    public function index(){
        $key = 'I3hA2u4RlTm1m7KQLpBRuFXdsMiv9DenY'; // The API_KEY in my .env file.
        $string = 'This is example text';

        $data = User::get();

        $data = response()->json($data);

        $encrypted = Crypt::encrypt($data);

        $decrypted = Crypt::decrypt($encrypted);
        
        return $encrypted;

    }
}
