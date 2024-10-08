<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/test';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showAdminLoginForm(){
        if(Auth::guard('admin')->check()){
            return redirect()->route('home');
        }
        return view('auth.login', ['url' => Hash::make('admin')]);
    }

    public function login(Request $request)
    {
        if(!$request->has('type')) abort(500,"Could not process your request");
        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:6'
        ]);
       
        switch(true){
            case Hash::check('admin',$request->type) : 
                if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password],NULL)) {
                    return redirect()->route('home');
                }
                return back()->with(["success"=>false,"message"=>"Either user name or email is wrong"]);
            break;
            case Hash::check('web',$request->type) :
                if (Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password],NULL)) {
                    return redirect()->route('user.home');
                } 
                return back()->with(["success"=>false,"message"=>"Either user name or email is wrong"]);
            break;
            default :
                 abort(501,"Could not process your request");
            break;
        }
    }

    public function logout( Request $request ){
        if(Auth::guard('admin')->check()) // this means that the admin was logged in.
        {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login');
        }
        $this->guard()->logout();
        $request->session()->invalidate();
        return $this->loggedOut($request) ?: redirect('/login');
    }

}
