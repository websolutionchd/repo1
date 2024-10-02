<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContributorCategory;
use App\Models\ExternalLink;
use App\Models\MarketPlaceBanner;
use App\Models\MarketPlaceListing;
use App\Models\MarketPlaceSetting;
use App\Models\MarketPlaceListingProvider;

use DB;

class AppController extends Controller
{
    public function index(Request $request){
        $MarketPlaceSetting = MarketPlaceSetting::find('1');
        return response()->json(["status"=>200,"data"=>[
                    "banners"=>MarketPlaceBanner::where('status','1')->get(['content','image','bg_color','link']),
                    "welcome_title"=>@$MarketPlaceSetting->welcome_title,
                    "welcome_desc"=>$MarketPlaceSetting->welcome_desc,
                    "main_link_text"=>$MarketPlaceSetting->main_link_text,
                    "link_exachange_link"=>$MarketPlaceSetting->link_exachange_link,
                    "orm_link"=>$MarketPlaceSetting->orm_link,
                    "add_website_link"=>url()->to('guest-post-marketplace#add-website'),
                    "whatsapp_link"=>$MarketPlaceSetting->whatsapp_link,
                    "telegram_link"=>$MarketPlaceSetting->telegram_link,
                    "about_us_link"=>$MarketPlaceSetting->about_us_link,
                    "note"=>$MarketPlaceSetting->note,
                    "contact_link"=>$MarketPlaceSetting->contact_link,
                    "write_for_link"=>$MarketPlaceSetting->write_for_link
                ]    
            ]);
    }

    public function listing(Request $req,$id=false){
        $MarketPlaceSetting = MarketPlaceSetting::find('1');
        if($id){
            $MarketPlaceListing = DB::select("SELECT *
FROM `market_place_listings`
WHERE `status` = 1
AND JSON_SEARCH(`category`, 'one', $id) IS NOT NULL;");
            // dd($MarketPlaceListing);
        }else{
            $MarketPlaceListing = MarketPlaceListing::where('status','1')->get();
        }
        
        $MarketPlaceListingAr = [];
        foreach($MarketPlaceListing as $rec){
            $ContributorCategory = ContributorCategory::whereIn('id',json_decode($rec->category))->get('name');
            $category_name = "";
            foreach($ContributorCategory as $r){
                $category_name .= $r->name.',';
            }
            $category_name = substr($category_name,0,-1);


            


            $MarketPlaceListingAr[]=[
                "webiste_name"=>$rec->webiste_name,
                "website_link"=>$rec->webiste_link,
                "category_name"=>$category_name,
                "da"=>$rec->da,
                "ss"=>$rec->ss,
                "dr"=>$rec->dr,
                "traffic"=>$rec->traffic,
                "domain_age"=>$rec->domain_age,
                "allowed"=>$rec->allowed,
                "link_type"=>$rec->link_type,
                "saas"=>$rec->saas,
                
                "provider_list"=>DB::select("select `email_id`, `rate`, `tat`,CONCAT('https://wa.me/',whatsapp) as `whatsapp_link`, CONCAT('https://t.me/',twitter) as `twitter_link` from `market_place_listing_providers` where `parent_id` = $rec->id")
            ];
        }
       

        
        return response()->json(["status"=>200,"data"=>[
                "list_categories"=>ContributorCategory::where('status','1')->get(['name as category_name','id as category_id']),
                "external_links"=>ExternalLink::where('status','1')->get(['category_name','link']),
                "logo"=>$MarketPlaceSetting->logo,
                "logo_subtitle"=>$MarketPlaceSetting->logo_subtitle,
                "lists"=>$MarketPlaceListingAr
            ]    
        ]);
    }

    public function checkDomain(Request $request){
        $res = MarketPlaceListing::where('webiste_link',$request->domain)->first();
        if($res){
            return response()->json(["success"=>200,"exist"=>"Y","data"=>$res]);
        }else{
            return response()->json(["success"=>200,"exist"=>"N"]);
        }
    }

    public function submitListing(Request $request){
        $exist = false;
        if($request->listing_id && $request->listing_id != ""){
            $exist = true;
        }

        $MarketPlaceListing2 = MarketPlaceListing::where('webiste_link',$request->website_link)->first();
        if(!empty($MarketPlaceListing2)){
            $exist = true;
            $request->listing_id = $MarketPlaceListing2->id;
        }

        if($exist){
            $MarketPlaceListingProvider = MarketPlaceListingProvider::where("email_id",$request->email_id)->first();
            if(empty($MarketPlaceListingProvider)){
                $MarketPlaceListingProvider = new MarketPlaceListingProvider();
            }
            $MarketPlaceListingProvider->parent_id = $request->listing_id;
            $MarketPlaceListingProvider->email_id = @$request->email_id;
            $MarketPlaceListingProvider->tat = @$request->tat;
            $MarketPlaceListingProvider->rate = @$request->rate;
            $MarketPlaceListingProvider->whatsapp = @$request->whatsapp;
            $MarketPlaceListingProvider->twitter = @$request->twitter;
            $MarketPlaceListingProvider->status = '0';

            $MarketPlaceListingProvider->save();
            // if($request->platform == "web"){
            //     return back()->with(["success"=>true,"message"=>"Provider Added Successfully"]);
            // }else{
                return response()->json(["success"=>200,"message"=>"Provider Added Successfully"]);
            // }
        }else{
            $MarketPlaceListing = new MarketPlaceListing();
            $MarketPlaceListing->webiste_link = @$request->website_link;
            $MarketPlaceListing->webiste_name = @$request->website_name;
            $MarketPlaceListing->category = json_encode($request->category);
            $MarketPlaceListing->dr = @$request->dr;
            $MarketPlaceListing->da = @$request->da;
            $MarketPlaceListing->ss = @$request->ss;
            $MarketPlaceListing->saas = @$request->saas;
            $MarketPlaceListing->domain_age = @$request->domain_age;
            $MarketPlaceListing->traffic = @$request->traffic;
            $MarketPlaceListing->link_type = implode(", ",@$request->link_type);
            $MarketPlaceListing->allowed = implode(", ",@$request->allowed);
            $MarketPlaceListing->type = @$request->type;
            $MarketPlaceListing->status = '0';
            $MarketPlaceListing->save();

            $MarketPlaceListingProvider = new MarketPlaceListingProvider();
            $MarketPlaceListingProvider->email_id = @$request->email_id;
            $MarketPlaceListingProvider->tat = @$request->tat;
            $MarketPlaceListingProvider->rate = @$request->rate;
            $MarketPlaceListingProvider->whatsapp = @$request->whatsapp;
            $MarketPlaceListingProvider->twitter = @$request->twitter;
            $MarketPlaceListingProvider->parent_id = $MarketPlaceListing->id;
            $MarketPlaceListingProvider->status = '0';
            $MarketPlaceListingProvider->save();

            // if($request->platform == "web"){
            //     return back()->with(["success"=>true,"message"=>"Listing Added Successfully"]);
            // }else{
                return response()->json(["success"=>200,"message"=>"Provider Added Successfully"]);
            // }
        }
    }
}
