<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminController extends Controller
{
    public function changePassword(Request $request){
        if($request->isMethod('get')) return view('admin.change-password');

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        if(!Hash::check($request->old_password, Auth::guard('admin')->user()->password)){
            return back()->with(["success"=>false,"message"=>"Old Password Doesn't match!"]);
        }
        $User = User::find(Auth::guard('admin')->user()->id);
        $User->password = Hash::make($request->new_password);
        $User->save();
        return back()->with(["success"=>true,"message"=>"Password Updated successfully!"]);
    }
}
