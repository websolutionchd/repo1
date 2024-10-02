<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Country;
use Validator;
use Storage;


class UserController extends Controller
{
    public function userHome(Request $request){
        return view('user.dashboard');
    }
    public function changePassword(Request $request){
        if($request->isMethod('get')) return view('user.change-password');

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        if(!Hash::check($request->old_password, auth()->user()->password)){
            return back()->with(["success"=>false,"message"=>"Old Password Doesn't match!"]);
        }
        $Company = Company::find(Auth::user()->id);
        $Company->password = Hash::make($request->new_password);
        $Company->save();
        return back()->with(["success"=>true,"message"=>"Password Updated successfully!"]);
    }

    public function founderDetail(Request $request,$id=false){
        if($request->isMethod(('get'))) return view('user.founder');
        $request->validate([
            'first_name' => 'required',
            'founder_linkdin'=>"required",
            'bio'=>"required"
        ]);

        if(count(explode(' ', $request->bio)) > 200){
            return back()->with(["success"=>false,"message"=>"Founder Bio can take max of 200 words"]);
        }

        if(count(explode(' ', $request->bio)) < 20){
            return back()->with(["success"=>false,"message"=>"Founder Bio must take atleast 20 words"]);
        }

        if($id){
            $Company = Company::find($id);
        }else{
            $Company = Company::find(Auth::user()->id);    
        }
        
        if($request->has('profile_picture') && !is_null($request->profile_picture)){
            $old_val = $Company->profile_picture;
            $validator = Validator::make($request->all(),
                                    [
                                        'profile_picture' => 'required|mimes:jpg,jpeg,png,gif,webf,svg|max:2048'
                                    ]
                            );
            if ($validator->fails())
            {
                return back()->with(["success"=>false,"message"=>$validator->errors()]);
            }
            $fileName = str_replace(" ","-",time().'_'.$request->profile_picture->getClientOriginalName());
            $filePath = $request->file('profile_picture')->storeAs('uploads', $fileName, 'public');
            $Company->profile_picture = '/storage/' . $filePath;
            Storage::disk('public')->delete(str_replace("/storage/",'',$old_val));
        }
        $Company->first_name  = $request->first_name;
        if($request->has('last_name')){
            $Company->last_name  = @$request->last_name;    
        }
        
        $Company->founder_linkdin  = $request->founder_linkdin;
        $Company->bio  = $request->bio;
        $Company->save();

        if(!$id){
            if(!isset($Company->user_name)) return redirect()->route('user.home.user-detail')->with('success', false)->with('message', 'Please add User details');
        }
        
        return back()->with(["success"=>true,"message"=>"Details Updated successfully","active_tab"=>1]);
    }

    public function userDetail(Request $request,$id=false){
        if($request->isMethod(('get'))) {
            $Designation = Designation::where('status','1')->get();
            return view('user.user-detail',["Designation"=>$Designation]);
        }
        $request->validate([
            'user_name' => 'required',
            'user_email'=>"required",
            'designation'=>"required"
        ]);

        if($id){
            $Company = Company::find($id);
        }else{
            $Company = Company::find(Auth::user()->id);    
        }
        
        
        if($Company->email == $request->user_email){
            return back()->with(["success"=>false,"message"=>"Company Email and user Email can not be same"]);
        }
        $Company->user_email  = $request->user_email;
        $Company->user_name  = $request->user_name;
        $Company->designation  = $request->designation;
        $Company->save();

        return back()->with(["success"=>true,"message"=>"Details Updated successfully","active_tab"=>2]);
    }

    public function editOtherDetails(Request $request,$id,$type=false){
        if($request->isMethod('get')){
            $Country = Country::all();
            $Company = Company::find($id);
            $Designation = Designation::where('status','1')->get();
            return view('admin.edit-other-details',["Company"=>$Company,"Designation"=>$Designation,"Country"=>$Country]);
        }
        switch ($type) {
            case 'founder-detail':
                   return $this->founderDetail($request,$id);
            break;
            
            case 'user-detail':
                   return $this->userDetail($request,$id);
            break;

            default:
                return abort(501,"Invalid Request");
            break;
        }
    }
    
}
