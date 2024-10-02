<?php

namespace App\Http\Controllers;

use App\Jobs\QueueUserNotificationsJob;
use App\Models\City;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\SendCustomEmail;
use Notification;
use Hash;
use Session;
use Auth;
use DB;
use App\Models\Country;
use App\Models\Listing;
use App\Models\OtherListing;
use App\Models\State;
use App\Models\ContributorCategory;
use App\Models\ExternalLink;
use App\Models\MarketPlaceBanner;
use App\Models\MarketPlaceListing;
use App\Models\MarketPlaceSetting;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function index(Request $request){
    
    	$title = "List Your Business For Free | Business Listing Site in USA";
        $description = "Increase your B2B business visibility by listing your business for free. We are free business listing site of 2023. One of free places to list your business in UK, Dubai & USA";
    	$keywords[] = "free business listings online";
    	$keywords[] = "list your business for free";
    	$keywords[] = "free local business listings";
    	$keywords[] = "free places to list your business";
	       
        
        return view('home',compact('title','description','keywords'));
    }
    
    public function welcome(Request $request){
    	$title = "Hire An IT, Software, Marketing or Consultant Easily Using 10+ Filters";
    	$description = "All companies are verified by our experts. Finding the right agency, software, or application that aligns with your business requirements without investing much time.";
    	$keywords = "Hire An Agency, Hire An Software Development, Hire Marketing Consultant";
        $core_experties = DB::select("select DISTINCT(core_experties) from companies where core_experties is not null and status  = 'published'");
        
        return view('welcome',compact('title','description','keywords','core_experties'));
    }

    public function search(Request $request,$category = false){
        $company = null;
        if($category || $request->has('category')){
            $category = $category ?? $request->category;
            $company = Company::where('core_experties','like',"%".str_replace("-"," ", $category)."%");
        }elseif(!empty($request->search)){
            $request->search = rawurldecode($request->search);
            $company = Company::where('core_experties',$request->search);
                        //->orWhere('other_experties','like',"%".$request->s."%");
        }elseif(!empty(@$request->by_company) ){
            $company = Company::where('name','like',$request->by_company."%");
            
        }


        if($request->has('rate2')){
            $company = $company->where('rate',$request->rate2);    
        }
        if($request->has('employee2')){
            $company = $company->where('employee',$request->employee2);    
        }

        if($request->has('completed_project2')){
            $company = $company->where('completed_project',$request->completed_project2);    
        }
        if($request->has('location2') || $request->has('location')){
            $company = $company->join('company_addresses','company_addresses.parent_id','companies.id')
                                ->where('is_main','1');

            if ($request->has('location2')) {
                $location = explode('_',$request->location2);
            }else{
                $location = explode('_',$request->location);
            }


            if($location[1] == "country"){
                $country_id = Country::where('name',$location[0])->value('id');
                $company = $company->where("country_id",$country_id);
            }elseif($location[1] == "state"){
                $state_id = State::where('name',$location[0])->value('id');
                $company = $company->where("country_id",$state_id);
            }elseif($location[1] == "city"){
                $city_id = City::where('name',$location[0])->value('id');
                $company = $company->where("city_id",$city_id);
            }
        }

        $company = $company
                                ->where('status','published')
                                ->distinct()->paginate(20);

        $title = "Hire An IT, Software, Marketing or Consultant Easily Using 10+ Filters";
        $description = "All companies are verified by our experts. Finding the right agency, software, or application that aligns with your business requirements without investing much time.";
        $keywords = "Hire An Agency, Hire An Software Development, Hire Marketing Consultant";
        $core_experties = DB::select("select DISTINCT(core_experties) from companies where core_experties is not null and status  = 'published' ;");
        
        return view('search-result',compact('title','description','keywords','core_experties','company'));
    }



    
    
    

    public function verifyCompany(Request $request){

        // $request->validate([
        //     'name' => 'required',
        //     'email' => 'required|unique:companies'
        // ]);

        try{
            
            
            $Company = Company::where('website_url',$request->name)
                    ->orWhere('email',$request->email)->first();
            //print_r($Company);die;
            if(empty($Company)){
                $Company = new  Company();
                $Company->email = $request->email;
                $Company->website_url = $request->name;
                
                if(Auth::guard('admin')->check()){
                    $Company->registered_by = 'Admin';
                }else{
                    $Company->registered_by = 'User';
                }
            }
            
           // dd($Company->email);
            
            $Company->otp = rand(111111,999999);
            $Company->save();
            $action = route('companies.verify.now',['email'=>base64_encode($Company->email),"id"=>base64_encode($Company->id)]);
            
            dispatch(new QueueUserNotificationsJob($Company,[
                    'greeting'=>'Dear '.$Company->name.',',
                    "subject"=>"OTP to Register Your Company at ".env('APP_NAME'),
                    "line1"=>"Thank you for choosing MakeAnAppLike Services to register your company with us. Don't let your competitors get ahead of you. Maximize your business's exposure and reach a wider audience by listing your B2B company with us",
                    "line2"=>"As a part of our registration process, we have sent you an OTP to verify your account. Please use ".$Company->otp." OTP to complete the registration process.",
                    "line3"=>"In case you encounter any difficulties while clicking the \"Verify Now\" button, you can copy and paste the URL provided below into your web browser.",
                    "line4"=>"If you have any questions or concerns regarding our services, please do not hesitate to contact our customer support team by visiting the contact us page. We will be happy to assist you with any issues you may have.",
                    "action"=>"Verify Now",
                    "action_url"=>$action]
                ));
    
            return redirect()->route('companies.verify.now',['email'=>base64_encode($Company->email),"id"=>base64_encode($Company->id)]);
        }catch(\Exception $e){
            print_r($e->getMessage());die;
            return back()->with(["success"=>false,"message"=>"There is some error please try again or contact to admin"]);
        }
    }

    public function verifyCompanyNow(Request $request,$email,$id){
        $email = base64_decode($email);
        $id = base64_decode($id);
        $Company = Company::where('id',$id)
                    ->where('email',$email)
                    //->whereNull('email_verified_at')
                    ->first();
        if(empty($Company)){
            return abort(501,"Oops! No match record found");
        }
        if($request->isMethod('get')){
            return view('verify',["Company"=>$Company]);
        }else{
               if($Company->otp == $request->otp){
                    if(is_null($Company->email_verified_at)){
                        $Company->email_verified_at = date('Y-m-d h:i:s');
                        $Company->save();
                    }
                    Session::put('isValid',true);
                    return redirect()->route('companies.add',['email'=>base64_encode($Company->email),"id"=>base64_encode($Company->id)]);
               }else{
                    return back()->with(["success"=>false,"message"=>"Invalid OTP"]);
               }
        }
    }

    public function home(Request $request)
    {
        $blocked = Company::where('status','blocked')->count();
        $pending = Company::where('status','pending')->count();
        $published = Company::where('status','published')->count();
        $total = Company::count();
    
        return view('dashboard',compact('blocked','published','pending','total'));
    }


    public function siteMap(){
        $Company = Company::where('status','published')
                    ->orderBy('updated_at','desc')
                    ->select(['slug','name','updated_at'])
                    ->paginate(1000);

        return response()->view('sitemap', [
            'Company' => $Company
        ])->header('Content-Type', 'text/xml');
    }


    public function listingSitemap(){
        $listing = Listing::orderBy('updated_at','desc')->select(["core_experties","slug","updated_at"])->paginate(1000);
        return response()->view('sitemap', [
            'listing' => $listing
        ])->header('Content-Type', 'text/xml');
    }

    public function otherlistingSitemap(){
        $listing = OtherListing::orderBy('updated_at','desc')->select(["category_id","place","slug","updated_at"])->paginate(1000);
        return response()->view('sitemap', [
            'otherListing' => $listing
        ])->header('Content-Type', 'text/xml');
    }

    public function guestPostMarketplace(Request $request){
        $data['title'] = "Best Guest Post Marketplace - By MAAL";
        $data['description'] = "35000+ Guest Posting Websites with Seller WhatsApp Details. MakeAnAppLike created the Guest Post Marketplace App & Website for SEO & Bloggers. Marketplace for Guest Post";
        $data['MarketPlaceListing'] = MarketPlaceListing::where('status','1')->orderBy('id', 'desc')->paginate(25);
        $data['ContributorCategory'] = ContributorCategory::where("status",'1')->get();
        return view("guest-post-marketplace",$data);
    }
}
