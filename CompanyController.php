<?php

namespace App\Http\Controllers;

use App\Jobs\QueueUserNotificationsJob;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyPortfolio;
use App\Models\CoreExperties;
use App\Models\Country;
use App\Models\ListingDetail;
use App\Models\OtherExperties;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Session;
use Validator;
use Storage;

class CompanyController extends Controller
{
    private function getStatus($key,$value){
        if($key == $value){
            return  "selected";
        }
    }
    
    public function editSEO(Request $request){
        if(!isset($request->id)){
            return abort(501,"Invalid Request, No Company ID found");
        }
        
        $Company = Company::find($request->id);
        if(empty($Company)){
             return abort(501,"Invalid Request, Company Does not exist");
        }
        
        if($request->isMethod("post")){
            if(!$request->has('seoTitle') || !$request->has('seoDesc')){
                return abort(501,"Invalid Request, Please add SEO Tilte and SEO description");
            }
            $Company->seoTitle = $request->seoTitle;
            $Company->seoDesc = $request->seoDesc;
            $Company->save();
            return back()->with(["success"=>true,"message"=>"Titles updated successfully"]);    
        }else{
            return view('admin.add-edit-seo',['Company'=>$Company]);    
        }
    }
    private function getDataTable(Request $request){
        if(in_array($request->type,["published","pending"])){
            $columns = array(
                "name" ,
                "website_url" ,
                "",
                "core_experties" ,
                "email" ,
                "first_name",
                "founded_year",
                "user_email",
                "",
                "",
                "",
                "registered_at",
                "email_verified_at",
            ); 

            $totalTitles = Company::orderBy('id','desc')
                                    ->where('status',$request->type)
                                    ->whereNull('deleted_at');
            if($request->type == "pending"){
                $totalTitles = $totalTitles->whereNotNull('description')
                                           ->where('description','!=','');
            }
            $totalTitles = $totalTitles->count();
            $totalFiltered = $totalTitles;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $page_dir = $request->input('order.0.dir');
    
            if (empty($request->input('search.value'))) {
                $titles = Company::orderBy('id','desc')
                                ->where('status',$request->type)
                                ->whereNull('deleted_at');
                if($request->type == "pending"){
                    $titles = $titles->whereNotNull('description')
                                    ->where('description','!=','');
                }     
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
            } else {
                $string_search = $request->input('search.value');
                $titles = Company::where('name', 'LIKE', "%{$string_search}%")
                                ->where('status',$request->type)
                                ->whereNull('deleted_at');
                if($request->type == "pending"){
                    $titles = $titles->whereNotNull('description')
                                    ->where('description','!=','');
                }     
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
                $totalFiltered = $titles->count();
            }
            
            $all_products_data = array();
            if (!empty($titles)) {
                $count = 1;
                
                foreach ($titles as $rec) {
                    
                    $nestedData['Company_Name'] = '
                                    <div class="text-bold text-blue">
                                        <a href="'.route("companies.add",["email"=>base64_encode($rec->email),"id"=>base64_encode($rec->id)]).'" data-id="'.$rec->id.'" class="btn btn-info p-2 showAskQsnModal" target="_blank"> <i class="fas fa-pencil"></i></a>
                                        &nbsp;
                                        &nbsp;
                                        <a href="'.route('companies.view',['url'=>$rec->slug]).'" class="hover-blue">'.@$rec->name.'</a>
                                    </div>';
                    $nestedData['Company_URL'] = '
                    <div class="text-bold text-blue">
                        <a href="'.@$rec->website_url.'?utm_source=makeanapplike" target="_blank" class="hover-blue">'.@$rec->website_url.'</a>
                    </div>
                    ';
                
                    $address = $rec->addresses->where('is_main','1');
                
                    $nestedData['HQ'] = @$address[0]->address;
                    $nestedData['Core_Experties'] = $rec->core_experties;
                    $nestedData['Company_Email'] = @$rec->email;
                    $nestedData['Founder_Name']  = @$rec->first_name.' '.@$rec->last_name;
                    $nestedData['Founded_In'] = @$rec->founded_year;
                    $nestedData['User_Email'] = @$rec->user_email;
                    $nestedData['Registred_by'] = @$rec->user_name;
                    $nestedData['Role'] = @$rec->designation;
                    $nestedData['Added_By'] = @$rec->registered_by;
                    $nestedData['Registered_At'] = date('d-M-y',strtotime($rec->created_at));
                    $nestedData['Email_Verified_At'] = empty($rec->email_verified_at) ? 'NA' : date('d-M-y',strtotime($rec->email_verified_at));
                    $nestedData['Status'] = Ucfirst($rec->status);


                    $nestedData['Change_Status'] = "<select name=\"status\" form=\"updateForm{$rec->id}\" onchange=\"if(confirm('are you sure, you wants to change the status of this company')) {this.form.submit()}else{ this.value = '{$rec->status}'}\">
                    <option value=\"pending\"".$this->getStatus($rec->status,'pending').">Pending</option>        
                            <option value=\"published\" ".$this->getStatus($rec->status,'published').">Published</option>        
                            <option value=\"blocked\" ".$this->getStatus($rec->status,'blocked').">Blocked</option>                
                    </select>";

                    $nestedData['Action'] = '
                    <div class="text-bold text-center text-blue">
                        <a href="'.route('companies.view',['url'=>$rec->slug]).'" data-id="'.$rec->id.'" class="btn btn-primary p-2 showAskQsnModal" target="_blank" > <i class="fas fa-eye"></i></a>
                        <a href="javascript:void(0)" class="btn btn-info" onClick="if(confirm(\'Are you sure you want to delete this company ?\')) {
                            console.log(document.getElementById(\'destroyCompaniesID\'));
                                document.getElementById(\'destroyCompaniesID\').value = '.$rec->id.';
                                document.getElementById(\'destroyCompanies\').submit();
                            }"> <i class="fas fa-trash"></i>
                        </a>  
                        <a href="'.route('companies.edit.seo-titles',['id'=>$rec->id]).'" class="btn btn-primary"> Edit SEO
                        </a>
                        <a href="'.route('companies.edit.other-details',['id'=>$rec->id]).'" class="btn btn-info"> Edit Oth Dtls
                        </a>    
                        </div>
                    ';

                    $all_products_data[] = $nestedData;
                    $count++;
                }
            }    
        }elseif($request->type == "drafted"){

            $columns = array(
                "email" ,
                "website_url"
            ); 

            $totalTitles = Company::orderBy('id','desc')
                                    ->where('status',"pending")
                                    ->whereNull('deleted_at')
                                    ->whereRaw('(description is null or description = "")')
                                    ->get();
            
            
            $totalTitles = $totalTitles->count();
            $totalFiltered = $totalTitles;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $page_dir = $request->input('order.0.dir');
    
            if (empty($request->input('search.value'))) {
                $titles = Company::orderBy('id','desc')
                                ->where('status',"pending")
                                ->whereNull('deleted_at')
                                ->whereRaw('(description is null or description = "")');
                
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
            } else {
                $string_search = $request->input('search.value');
                $titles = Company::where('email', 'LIKE', "%{$string_search}%")
                                ->where('status',"pending")
                                ->whereNull('deleted_at')
                                ->whereRaw('(description is null or description = "")');
                
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
                
                $totalFiltered = $titles->count();
            }
            
            $all_products_data = array();
            if (!empty($titles)) {
                $count = 1;
                
                foreach ($titles as $rec) {
                    
                    $nestedData['Company_URL'] = '
                    <div class="text-bold text-blue">
                    <a href="'.route("companies.add",["email"=>base64_encode($rec->email),"id"=>base64_encode($rec->id)]).'" data-id="'.$rec->id.'" class="btn btn-info p-2 showAskQsnModal" target="_blank"> <i class="fas fa-pencil"></i></a>
                    &nbsp;
                    &nbsp;
                        <a href="'.@$rec->website_url.'?utm_source=makeanapplike" target="_blank" class="hover-blue">'.@$rec->website_url.'</a>
                    </div>
                    ';
                
                    
                    $nestedData['Company_Email'] = @$rec->email;
                    
                    $nestedData['Action'] = '
                        <div class="text-bold text-center text-blue">
                        <a href="javascript:void(0)" class="btn btn-info" onClick="if(confirm(\'Are you sure you want to delete this company ?\')) {
                            console.log(document.getElementById(\'destroyCompaniesID\'));
                                document.getElementById(\'destroyCompaniesID\').value = '.$rec->id.';
                                document.getElementById(\'destroyCompanies\').submit();
                            }"> <i class="fas fa-trash"></i>
                        </a>  
                    </div>';

                    $all_products_data[] = $nestedData;
                    $count++;
                }
            } 


            
           
        }elseif($request->type == "trashed"){

            $columns = array(
                "email" ,
                "website_url"
            ); 

            $totalTitles = Company::orderBy('id','desc')
                                    ->onlyTrashed()
                                    ->get();
            
            
            $totalTitles = $totalTitles->count();
            $totalFiltered = $totalTitles;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $page_dir = $request->input('order.0.dir');
    
            if (empty($request->input('search.value'))) {
                $titles = Company::orderBy('id','desc')
                                ->onlyTrashed();
                
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
            } else {
                $string_search = $request->input('search.value');
                $titles = Company::where('email', 'LIKE', "%{$string_search}%")
                                ->onlyTrashed();
                
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
                
                $totalFiltered = $titles->count();
            }
            
            $all_products_data = array();
            if (!empty($titles)) {
                $count = 1;
                
                foreach ($titles as $rec) {
                    
                    $nestedData['Company_URL'] = '
                    <div class="text-bold text-blue">
                        <a href="'.@$rec->website_url.'?utm_source=makeanapplike" target="_blank" class="hover-blue">'.@$rec->website_url.'</a>
                    </div>';
                
                    $nestedData['Company_Email'] = @$rec->email;
                    
                    $nestedData['Action'] = '
                        <div class="text-bold text-center text-blue">
                        <a href="javascript:void(0)" class="btn btn-info" onClick="if(confirm(\'Are you sure you want to restore this company ?\')) {
                            console.log(document.getElementById(\'destroyCompaniesID\'));
                                document.getElementById(\'destroyCompaniesID\').value = '.$rec->id.';
                                document.getElementById(\'destroyType\').value = \'soft\';
                                document.getElementById(\'destroyCompanies\').submit();
                            }" title="Restore"> <i class="fas fa-undo"></i>
                        </a> 
                        <a href="javascript:void(0)" class="btn btn-info" onClick="if(confirm(\'Are you sure you want to permanent delete this company ?\')) {
                            console.log(document.getElementById(\'destroyCompaniesID\'));
                                document.getElementById(\'destroyCompaniesID\').value = '.$rec->id.';
                                document.getElementById(\'destroyType\').value = \'permanent\';
                                document.getElementById(\'destroyCompanies\').submit();
                            }" title="Permanent Delete"> <i class="fas fa-trash"></i>
                        </a> 
                         
                    </div>';

                    $all_products_data[] = $nestedData;
                    $count++;
                }
            }  
        }

        $product_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalTitles),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $all_products_data,
        );
        return response()->json($product_data);

    }
    public function index(Request $request){

        // $Company = Company::all();
        // foreach($Company as $rec){
        //         $name = preg_replace('/[^ \w-]/', '', $rec->name);
        //         $slug = str_replace(" ", "-", $name); 
        //         $slug = str_replace("--", "-", $slug); 
        //         $rec->slug = strtolower($slug);
        //         $rec->save();
        // }
        //  echo "done";die;

        if ($request->ajax()) {
            return $this->getDataTable($request);
        }
        if($request->type == "published"){
            return view('admin.published-company');
        }elseif($request->type == "pending"){
            return view('admin.pending-company');
        }elseif($request->type == "drafted"){
            return view('admin.drafted-company');
        }elseif($request->type == "destroy"){
            return $this->destroy($request);
        }else{
            return $this->onlyTrashed($request);
        }   
    }

    public function addCompany(Request $request,$email=false,$id=false){
        if(!Auth::guard('admin')->check()){
            //if(!Session::has('isValid')) return abort(501,"Sorry Session has been Expired");
        }
        if($request->isMethod('get')){
            $Country = Country::all();
            $CoreExperties = CoreExperties::where('status','1')->get();
            $OtherExperties = OtherExperties::where('status','1')->get();
            
            if(!$email){
                return view('add-company',['Country'=>$Country,"CoreExperties"=>$CoreExperties,"OtherExperties"=>$OtherExperties]);    
            }
            $email = base64_decode($email);
            $id = base64_decode($id);
            $Company = Company::where('email',$email)->where('id',$id)
                            //->whereNull('email_verified_at')
                            ->first();
            if(empty($Company)){
                return abort(501,"Oops! No match record found");
            }

            return view('add-company',['Company'=>$Company,'Country'=>$Country,"CoreExperties"=>$CoreExperties,"OtherExperties"=>$OtherExperties]);
        }
        return $this->step1($request);
    }
    public function editCompany(Request $request,$id){
        $Company = Company::find($id);
        if(empty($Company)){
            return abort(501,"Invalid Request");
        }
        return view('add-company',['Company'=>$Company]);
    }
    private function step1($request,$id = false){
        try{
            if($id){
                $Company = Company::find($id);
                if(Auth::guard('admin')->check()){
                     $Company->email = $request->email;
                     $Company->website_url = $request->website_url;
                }
            }else{
                $Company = new Company();
                $Company->email = $request->email;
                $Company->website_url = $request->website_url;
                if(Auth::guard('admin')->check()){
                    $Company->registered_by = 'Admin';
                }else{
                    $Company->registered_by = 'User';
                }
            }

            if($request->has('logo')){
                $old_val = $Company->logo;
                $validator = Validator::make($request->all(),
                                        [
                                            'logo' => 'required|mimes:jpg,jpeg,png,gif,svg,webp|max:2048'
                                        ]
                                );
                if ($validator->fails())
                {
                    return response()->json(["status"=>421,"message"=>$validator->errors()->all()]);
                }
                $fileName = str_replace(" ","-",time().'_'.$request->logo->getClientOriginalName());
                $filePath = $request->file('logo')->storeAs('uploads', $fileName, 'public');
                $Company->logo = '/storage/' . $filePath;
                Storage::disk('public')->delete(str_replace("/storage/",'',$old_val));
            }
            $Company->email_verified_at = date("Y-m-d H:i:s");
            $Company->phone = $request->phone;

            

            if($id && $Company->status != "published"){
                $name = preg_replace('/[^ \w-]/', '', $request->name);
                $slug = str_replace(" ", "-", $name); 
                $slug = str_replace("--", "-", $slug); 
                $Company->slug = strtolower($slug);
            }

            $Company->name = $request->name;
            $Company->founded_year = @$request->founded_year;
            $Company->first_name = $request->first_name;
            $Company->last_name = $request->last_name;
            $Company->tag_line = $request->tag_line;
            $Company->save();
            
            if($id){
                return response()->json(["status"=>200,"message"=>"Details Updated successfully"]);
            }else{
                Session::put('isValid',true);
                $return_url =  route('companies.add',['email'=>base64_encode($Company->email),"id"=>base64_encode($Company->id)]);
                return response()->json(["status"=>200,"message"=>"Details Added successfully","return_url"=>$return_url]);
            }
        }catch(\Exception $e){
            return response()->json(["status"=>500,"message"=>"Oops! there is some error , please contact to admin","error"=>$e->getMessage()]);
        }
        
    }

    private function step2($request,$id = false){
        $Company = Company::find($id);
        $Company->core_experties = $request->core_experties;
        $Company->other_experties = json_encode($request->other_experties);
        $Company->employee = $request->employee;
        $Company->rate = $request->rate;
        $Company->branches = (int)$request->branches;
        $Company->save();

        CompanyAddress::where('parent_id',$id)->delete();
        for($i = 0;$i<$Company->branches ;$i++){
            $CompanyAddress = new CompanyAddress();
            if($i == 0){
                $CompanyAddress->is_main = '1';
            }
            $CompanyAddress->parent_id = $id;
            $CompanyAddress->address = @$request->address[$i];
            $CompanyAddress->country_id = @$request->country_id[$i] ;
            $CompanyAddress->state_id = @$request->state_id[$i];
            $CompanyAddress->city_id = @$request->city_id[$i];
            $CompanyAddress->pincode = @$request->pincode[$i];
            $CompanyAddress->save();
        }
        return response()->json(["status"=>200,"message"=>"Details Updated successfully"]);
    }

    private function step3($request,$id = false){
        $Company = Company::find($id);
        $Company->completed_project = $request->completed_project;
        $Company->ticket_size = $request->ticket_size;
        $Company->facebook = $request->facebook;
        $Company->linkdin = $request->linkdin;
        $Company->youtube = $request->youtube;
        $Company->twitter = $request->twitter;
        $Company->instagram = $request->instagram;
        $Company->prime_firms = $request->prime_firms;
        $Company->g2 = $request->g2;
        $Company->behance = $request->behance;
        $Company->whatsapp = $request->whatsapp;        
        $Company->save();
        return response()->json(["status"=>200,"message"=>"Details Updated successfully"]);
    }
    private function sendAccountCreatedEmail($to,$username,$password){
        $this->sendEmailNotification($to,[
            "subject"=>"Company Account Created ".env('APP_NAME'),
            "line1"=>"We have created Your Account, use below user name and email to login",
            "line2"=>"Username : ".$username." \n Password : $password ",
            "action"=>"Login Now",
            "action_url"=>route('login')
        ]);
    }
    private function step4($request,$id = false){
        $Company = Company::find($id);
        $old_registered_at = $Company->registered_at;
        $old_status = $Company->status;
        $Company->description = $request->description;
        
        if(is_null($old_registered_at)){
            $Company->registered_at = date('Y-m-d H:i:s');
            $password = str_replace(' ','_',$Company->name).rand(1111,9999).'$$';
            $Company->password = Hash::make($password);
        }

        $Company->save();
        if(Auth::guard('admin')->check()){
            $Company->status = $request->status;
            $Company->save();

            if(is_null($old_registered_at)){
                if($old_status != $request->status && $request->status == 'published'){
                    $this->sendEmailNotification($Company,[
                        'greeting'=>'Dear '.$Company->name.',',
                        "subject"=>"Cross check your company details & fix the issues or Claim it",
                        "line1"=>"We are pleased to inform you that your company has been successfully registered with us. Congratulations! We are excited to have you as a part of our community.",
                        "line2"=>"Company Name:  $Company->name <br/>
                                    Profile URL: ".route('companies.view',['url'=>$Company->slug]) ." <br/>
                                    Email: $Company->email <br/>
                                    Password: $password <br/>",
                        "line3"=>"If you find any missing information there, login and update details. You can login using the above credentials and update your company details.",
                        "line4"=>"Don't let your competitors get ahead of you. Maximize your business's exposure and reach a wider audience by listing your B2B company with us. We encourage you to continuously add to your portfolio and showcase your best work. Doing so will not only help increase your visibility on our platform, but it will also help you climb up the ranks of our top 10 listicles we publish regularly.",
                        "action"=>"View",
                        "action_url"=>route('companies.view',['url'=>$Company->slug])
                    ]);
                }else{
                    $this->sendEmailNotification($Company,[
                        'greeting'=>'Dear '.$Company->name.',',
                        "subject"=>"Great Job! You have successfully submitted your company",
                        "line1"=>"Thank you for submitting your company to our platform. We have received your information, and we are excited to have you on board.",
                        "line2"=>"Our team of market analysts will review your submission in detail to ensure that all information provided is accurate and legitimate. This review process is essential to maintain the quality and integrity of our platform.",
                        "line3"=>"Company Name:  $Company->name <br/>
                                Email: $Company->email <br/>
                                Password: $password <br/> 
                                We kindly ask that you have included all the necessary details and that they are up-to-date. This will help expedite the approval process and ensure that your company is listed on our platform as soon as possible.",
                        "line4"=>"Please note that our team will manually approve each submission, and this process may take up to 8 to 16 working hours. We appreciate your patience during this time, and we will notify you as soon as your company has been approved.",
                        "action_url"=>route('login'),
                        "action"=>"Login Now"
                    ]);
                }
            }else{
                $this->sendEmailNotification($Company,[
                    'greeting'=>'Dear '.$Company->name.',',
                    "subject"=>"Cross check your company details & fix the issues or Claim it",
                    "line1"=>"We are pleased to inform you that your company details has been updated by admin.",
                    "line2"=>"Company Name:  $Company->name <br/>  Email: $Company->email <br/>",
                    "line3"=>"If you find any missing information there, login and update details. You can login using the above credentials and update your company details.",
                    "line4"=>"Don't let your competitors get ahead of you. Maximize your business's exposure and reach a wider audience by listing your B2B company with us. We encourage you to continuously add to your portfolio and showcase your best work. Doing so will not only help increase your visibility on our platform, but it will also help you climb up the ranks of our top 10 listicles we publish regularly.",
                    "action_url"=>route('login'),
                    "action"=>"Login Now"
                ]);
            }
        }else{
            if (is_null($old_registered_at)) {
                $this->sendEmailNotification($Company,[
                    'greeting'=>'Dear '.$Company->name.',',
                    "subject"=>"Great Job! You have successfully submitted your company",
                    "line1"=>"Thank you for submitting your company to our platform. We have received your information, and we are excited to have you on board.",
                    "line2"=>"Our team of market analysts will review your submission in detail to ensure that all information provided is accurate and legitimate. This review process is essential to maintain the quality and integrity of our platform.",
                    "line3"=>"Company Name:  $Company->name <br/>
                            Email: $Company->email <br/>
                            Password: $password <br/> 
                            We kindly ask that you have included all the necessary details and that they are up-to-date. This will help expedite the approval process and ensure that your company is listed on our platform as soon as possible.",
                    "line4"=>"Please note that our team will manually approve each submission, and this process may take up to 8 to 16 working hours. We appreciate your patience during this time, and we will notify you as soon as your company has been approved.",
                    "action_url"=>route('login'),
                    "action"=>"Login Now"
                ]);
            }
        }
        
        if (!Auth::guard('admin')->check()) {

            $action = route("companies.add",["email"=>base64_encode($Company->email),"id"=>base64_encode($Company->id)]);


            if(is_null($old_registered_at)){
                $subject = "New Company Registration".env('APP_NAME');
                $line1 = "A new Company has been registered on your portal with reference ID ( ".$Company->name." )";
            }else{
                $subject = "Company Update ".env('APP_NAME');
                $line1 = "A Company has been updated on your portal with reference ID ( ".$Company->name." )";
            }

            $this->sendEmailNotification(User::find(1),[
                        "subject"=>$subject,
                        "line1"=>$line1,
                        "line2"=>null,
                        "action"=>'View',
                        "action_url"=>$action
                    ]);

            if(!Auth::check()){
                Auth::login($Company);    
            } 
            
        }


        if(Auth::guard('admin')->check()){
            $redirect_to =  route('companies.edit.other-details',['id'=>$Company->id]);
        }else{
            $redirect_to =  route('user.home.founder-detail');
        }
        
        return response()->json(["status"=>200,"message"=>"Details Updated successfully","return_url"=>$redirect_to]);    

    }

    private function sendEmailNotification($to,$arr){
        dispatch(new QueueUserNotificationsJob($to,$arr));
    }

    public function updateCompany(Request $request,$id,$step){

        try{
            switch($step){
                case 1 :
                    return $this->step1($request,$id);
                break;
                case 2 :
                    return $this->step2($request,$id);
                break;
                case 3 :
                    return $this->step3($request,$id);
                break;
                case 4 :
                    return $this->step4($request,$id);
                break;
                default :
                    return response()->json(["status"=>500,"message"=>"Invalid Step"]);
                break;
            }
        }catch(\Exception $e){
            return response()->json(["status"=>500,"message"=>$e->getMessage()]);
        }
        
    }

    public function updateStatus(Request $request,$id){
        $Company = Company::find($id);
        if(empty($Company)){
            return abort(501,"Invalid Request");
        }
        $Company->status = $request->status;
        $Company->save();
        
        if($request->status == 'published'){
            $this->sendEmailNotification($Company,[
                "subject"=>"Company Registered with ".env('APP_NAME'),
                "line1"=>"Your Company is successfully registered with us.",
                "line2"=>"Kindly check your company with below ",
                "action"=>"View",
                "action_url"=>route('companies.view',['url'=>$Company->slug])
            ]);
        }

        if($request->status == 'blocked'){
            $this->sendEmailNotification($Company,[
                "subject"=>"Company Registration Denied with ".env('APP_NAME'),
                "line1"=>"Your Company can not be registered with us.",
                "line2"=>"Kindly contact to admin for more details ",
                "action_url"=>NULL,
                "action"=>NULL
            ]);
        }

        return back()->with(["success"=>true,"message"=>"Company Status has been changed successfully"]);
    }

    

    public function viewCompany(Request $request,$url=false){
        //Need To update slug with ID
        $Company = Company::where('slug',$url)->first();
        if(empty($Company)){
            return abort(501,"Record do not match with our record");
        }
        if(!Auth::guard('admin')->check()){
            if($Company->status !='published') return back()->with(["success"=>false,"message"=>"Your Company is not Published Yet, Kindly wait or contact to admin"]);
        }
        $addresses = $Company->addresses->where('is_main',1);

        $country_id = @$addresses[0]->country_id;
        //if(empty($country_id) || is_null($country_id)) return abort(501,"Some of filed missing");
        $core_experties = $Company->core_experties;

        $others = Company::query();
        $others = $others->where('core_experties',$core_experties)
                         ->where('id','!=',$Company->id);
        if(!empty($country_id) || !is_null($country_id)){
           $others = $others->whereRaw("$country_id = (select country_id from company_addresses where parent_id = companies.id and is_main = '1')");
        }
        $others = $others->where('rate',$Company->rate)
        ->where('employee',$Company->employee)
        ->limit(12);

        $country_name = @$addresses[0]->country->name;
        
        if($Company->seoTitle != "" || !empty($Company->seoTitle)){
            $title = $Company->seoTitle;
            $description = $Company->seoDesc;
        }else{
            $title = $Company->name." Reviews, Running Projects, Competitors Details & Turn Over";
            $description = "{$Company->name} was founded by {$Company->first_name} {$Company->last_name} in {$country_name}. Company has total {$Company->employee} employees. Complaints & reviews of {$Company->website_url}.";    
        }

        $keywords = $Company->name;
        return view('view-company',['Company'=>$Company,'others'=>$others,"title"=>$title,"description"=>$description,'keywords'=>$keywords]);
    }    

    public function addPortfolioPage(Request $request){
        $Country = Country::all();
        return view('user.portfolio',['Country'=>$Country]);
    }  

    public function addPortfolio(Request $request,$parent_id){
        $CompanyPortfolio = new CompanyPortfolio;
        $CompanyPortfolio->parent_id = $parent_id;
        $CompanyPortfolio->client_name = @$request->client_name;
        $CompanyPortfolio->website_url = @$request->website_url;
        $CompanyPortfolio->country_id = @$request->country_id;
        $CompanyPortfolio->Industry = @$request->industry;
        $CompanyPortfolio->description = @$request->client_description;
        $CompanyPortfolio->save();
        return response()->json(["status"=>200,"message"=>"Portfolio Added successfully"]);
    }

    public function deletePortfolio(Request $request,$id){
        CompanyPortfolio::where('id',$id)->delete();
        return response()->json(["status"=>200,"message"=>"Portfolio Removed successfully"]);
    }


    public function editPortfolio(Request $request,$id){
        $CompanyPortfolio = CompanyPortfolio::find($id);
        $CompanyPortfolio->client_name = @$request->client_name;
        $CompanyPortfolio->website_url = @$request->website_url;
        $CompanyPortfolio->country_id = @$request->country_id;
        $CompanyPortfolio->Industry = @$request->industry;
        $CompanyPortfolio->description = @$request->client_description;
        $CompanyPortfolio->save();
        return response()->json(["status"=>200,"message"=>"Portfolio Updated successfully"]);
    }

    public function destroy(Request $request){
        if($request->has('id') && !is_null($request->id)){
            $Company = Company::withTrashed()->find($request->id);
            if(empty($Company)){
                return back()->with(["success"=>false,"message"=>"Oops! no record found"]);        
            }
            $Company->description = NULL;
            $Company->status = "pending";
            $Company->save();
            $Company->delete();
            return back()->with(["success"=>true,"message"=>"Removed Successfully"]);        
        }
        return back()->with(["success"=>false,"message"=>"Oops! there is some error kindly check with developer"]);
    }

    public function restore(Request $request){

        if($request->has('id') && !is_null($request->id)){
            $Company = Company::withTrashed()->find($request->id);
            
            if(empty($Company)){
                return back()->with(["success"=>false,"message"=>"Oops! no record found"]);        
            }
            if($request->type == "permanent"){
                CompanyAddress::where('parent_id',$request->id)->delete();
                CompanyPortfolio::where('parent_id',$request->id)->delete();
                ListingDetail::where('company_id',$request->id)->delete();
                $Company->forceDelete();
                return back()->with(["success"=>true,"message"=>"Permanently Deleted Successfully"]);        
            }else{
                $Company->restore();
            }
            return back()->with(["success"=>true,"message"=>"Restored Successfully"]);        
        }
        return back()->with(["success"=>false,"message"=>"Oops! there is some error kindly check with developer"]);
    }

    public function onlyTrashed(Request $request){
        $Company = Company::onlyTrashed()->paginate(20);
        return view('admin.trashedCompany',compact('Company'));
    } 
}
