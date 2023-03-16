<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

class TestController extends Controller
{
    public function test($request)
    {
        $rules = [
            'file' => 'clamav',
        ];
        $validator = $request->validate($rules);
        try {
            if ($validator->fails()) {
                return 'danger';
            }
        } catch (\Throwable $th) {
            return 'safe';
        }
    }

    public function scanFile(Request $request)
    {
        return $this->test($request);
    }
}
