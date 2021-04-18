<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Twilio\Rest\Client;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function updateMobileNo(Request $request)
    {
        $request->validate([
            "newmobile" => 'required|min:5|max:12',
        ]);
        $me = Auth::user();
        $me->mobile = $request->input('newmobile');
        $me->verification_code = rand(1000, 9999);
        $me->mobile_verified_at = null;
        $this->sendSms("New verification code: " .$me->verification_code, $me->mobile);
        $me->save();
        Session::flash('info', 'Mobile number saved');
        return Redirect::route('dashboard');
    }
    public static function sendSms($message, $recipient)
    {
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_number = getenv("TWILIO_NUMBER");
        $client = new Client($account_sid, $auth_token);
        $client->messages->create('+44' . $recipient,
            [
                'from' => $twilio_number,
                'body' => $message,
                "statusCallback" => 'https://te.aw3.pl/status',
//                "statusCallback" => 'http://te.aw3.pl/status' temp fix
            ] );
    }
    public function resendCode(){
        $me = Auth::user();
        $me->verification_code = rand(1000, 9999);
        $this->sendSms("New verification code: " .$me->verification_code, $me->mobile);
        $me->save();
        return Redirect::route('dashboard')->with('newcode', $me->verification_code);
    }
    public function verifycode(Request $request){
        $request->validate([
            'verification_code'=>'numeric|required',
        ]);
        $me = Auth::user();
        if($request->input('verification_code') == $me->verification_code){
        $me->mobile_verified_at = now();
        $me->save();
        Session::flash('info', 'Mobile number verified!');
        } else {
            Session::flash('info', 'Wrong code!');
        }

       return Redirect::route('dashboard');
    }
}
