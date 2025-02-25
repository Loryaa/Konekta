<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthManager extends Controller
{
    function login() {
        if (Auth::check()) {
            return redirect(route('home'));
        }
        return view('merchant_login');
    }

    function registration() {
        if (Auth::check()) {
            return redirect(route('home'));
        }
        return view('merchant_registration');
    }


function loginPost(Request $request){
    $request->validate([
        'email' => 'required',
        'password' => 'required'
    ]);

    $credentials = $request->only('email', 'password');
    if (Auth::guard('web')->attempt($credentials)) {
        return redirect()->intended(route('home'));
    }
    return redirect(route('merchant_login'))->with("error", "Login details are not valid");
}

function registrationPost(Request $request){
    $request->validate([
        'name' => 'required',
        'business_name' => 'required',
        'phone' => 'required',
        'email' => 'required|email|unique:users',
        'password' => [
            'required',
            'string',
            'min:8', // must be at least 8 characters in length
            'regex:/[a-z]/', // must contain at least one lowercase letter
            'regex:/[A-Z]/', // must contain at least one uppercase letter
            'regex:/[0-9]/', // must contain at least one digit
            'regex:/[@$!%*#?&]/', // must contain a special character
            'regex:/^\S*$/u' // must not contain spaces
            ]
    ]);       

    $data['name'] = $request->name;
    $data['business_name'] = $request->business_name;
    $data['phone'] = $request->phone;
    $data['email'] = $request->email;
    $data['password'] = Hash::make($request->password);
    $merchant = Merchant::create($data);
    if(!$merchant){
        return redirect(route('merchant_registration'))->with("error", "Registration failed");
    }
    return redirect(route('merchant_login'))->with("success", "Registration successful, login to access the app");
}


    function logout(){
        Session::flush();
        Auth::logout();
        return redirect(route('login'));
    }
}