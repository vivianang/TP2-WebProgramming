<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function register()
    {
        $data['title'] = 'Register';
        return view('user/register', $data);
    }

    public function register_action(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:tb_user',
            'password' => 'required|min:10|regex:/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).*$/',
        ], [
            'username.required' => 'The username field is required.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 10 characters.',
            'password.regex' => 'The password must be contains uppercase, lowercase number & symbol.'
        ]);

        $user = new User([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'counter' => '0',
        ]);
        $user->save();

        return redirect()->route('login')->with('success', 'Registration success. Please login!');
    }


    public function login()
    {
        $data['title'] = 'Login';
        return view('user/login', $data);
    }

    public function login_action(Request $request)
    {
        
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'captcha' => 'required|captcha'
        ]);
        
        
        
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $request->session()->regenerate();
            DB::table('tb_user')
                  ->where('username',  $request->username)
                  ->update(['counter' => '0']);
            return redirect()->intended('/');
        }

        $user = DB::table('tb_user')->where('username', $request->username)->first();
        DB::table('tb_user')
              ->where('username',  $request->username)
              ->update(['counter' => $user->counter+1]);

        return back()->withErrors([
            'password' => 'Wrong username or password',
        ]);
    }

    public function reloadCaptcha()
    {
        return response()->json(['captcha'=> captcha_img()]);
    }

    public function password()
    {
        $data['title'] = 'Change Password';
        return view('user/password', $data);
    }

    public function password_action(Request $request)
    {
        $request->validate([
            'old_password' => 'required|current_password',
            'new_password' => 'required|min:10|regex:/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).*$/',
        ], [
            'new_password.regex' => 'The password must be contains uppercase, lowercase number & symbol.'
        ]);

        $user = User::find(Auth::id());
        error_log("abc ", 3, "D:/php.log");
        error_log($request, 3, "D:/php.log");
        $user->password = Hash::make($request->new_password);
        $user->save();
        $request->session()->regenerate();
        return back()->with('success', 'Password changed!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}