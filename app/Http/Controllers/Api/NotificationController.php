<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Pusher\Pusher;




class NotificationController extends Controller
{

    public function getSAIds(){
        $data = User::join('roles','roles.id','users.role_id')->where('roles.name','SA')->select('users.id')->get();
        foreach ($data as $key => $value) {
            $ids[] = $value['id'];
        }
        return $ids;
    }


    public function clearNotification($d){
        if($d == 'sa'){
            $data = Notifications::where('n_for','SA')->delete();
        }
        return $d;
    }


    public static function sendNotification($heading,$type,$title,$notification,$nfor,$path){
        $options = array(
            'cluster' => 'ap2',
            'encrypted' => true
        );
        $pusher = new Pusher(
            '76b5d8513b2ab0b9930c',
            '5214ac21cb51712950ec',
            '1368733', $options
        );

        Notifications::create([
            'heading' => $heading,
            'type' => $type,
            'title' => $title,
            'notification' => $notification,
            'n_for' => $nfor,
            'path' => $path,
            // 'user_id' => Auth::user()->id,
        ]);
        $data = User::join('roles','roles.id','users.role_id')->where('roles.name','SA')->select('users.id')->get();
        foreach ($data as $key => $value) {
            $ids[] = $value['id'];
        }

        foreach ($ids as $key => $value) {
            $count = User::where('id', $value)->get();
            User::where('id', $value)->update([
                'n_count' => (int)$count[0]->n_count + 1
            ]);
        }

       
        $message= $title;
        $pusher->trigger('notification', 'notification-event', $message);
    }


    public function resetNotification(Request $request)
    {
        User::where('id', Auth::user()->id)->update([
            'n_count' => 0
        ]);
        return 0;
    }
}
