<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarketPlaceListing;
use App\Models\MarketPlaceListingProvider;
use App\Models\ContributorCategory;

class MarketPlaceController extends Controller
{
    public function listing(Request $request,$type){
        if($type == "published"){
            $data["MarketPlaceListing"] = MarketPlaceListing::where("status",'1')->orderBy("id","desc")->get();
        }else{
            $data["MarketPlaceListing"] = MarketPlaceListing::where("status",'0')->orderBy("id","desc")->get();
        }
        $data["type"] = $type;
        
        return view('admin.marketplace-listing',$data);
    }

    public function StatusUpdate(Request $request,$id,$type){
        try{
            if(empty($id)){
                return response()->json(["success"=>false,"message"=>"No ID Found"]);
            }
            $MarketPlaceListing = MarketPlaceListing::find($id);
            if(empty($MarketPlaceListing)){
                return response()->json(["success"=>false,"message"=>"Oops! No record found"]);
            }
            if($type == "DELETE"){
                MarketPlaceListingProvider::where("parent_id",$id)->delete();
                $save = $MarketPlaceListing->delete();
                if($save){
                    return response()->json(["success"=>true,"message"=>"Listing Deleted Successfully"]);
                }else{
                    return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
                }
            }else{
                $MarketPlaceListing->status = @$type;
                $save = $MarketPlaceListing->save();
                if($save){
                    return response()->json(["success"=>true,"message"=>"Status updated Successfully"]);
                }else{
                    return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
                }
            }
        }catch(\Exception $e){
            return response()->json(["success"=>false,"message"=>$e->getMessage()]);
        }
    }

    public function editListing(Request $request,$id){
        if($request->isMethod("post")){
            $MarketPlaceListing = MarketPlaceListing::find($id);
            if(empty($MarketPlaceListing)){
                return back()->with(["success"=>false,"message"=>"Oops! No record found"]);
            }

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
            $MarketPlaceListing->save();

            return back()->with(["success"=>true,"message"=>"Updated Successfully"]);
        }else{
            $data['MarketPlaceListing'] = MarketPlaceListing::find($id);
            $data['ContributorCategory'] = ContributorCategory::where("status",'1')->get();
            return view('admin.marketplace-listing-edit',$data);
        }
        
    }


    public function ProviderListing(Request $request,$type){
        if($type == "published"){
            $data["MarketPlaceListing"] = MarketPlaceListingProvider::where("market_place_listing_providers.status",1)->orderBy("market_place_listing_providers.id","desc")->get();
        }else{
            $data["MarketPlaceListing"] = MarketPlaceListingProvider::where("market_place_listing_providers.status",'0')->orderBy("market_place_listing_providers.id","desc")->get();
        }
        $data["type"] = $type;
        return view('admin.marketplace-listing-provider',$data);
    }

    public function ProviderStatusUpdate(Request $request,$id,$type){
        try{
            if(empty($id)){
                return response()->json(["success"=>false,"message"=>"No ID Found"]);
            }
            $MarketPlaceListing = MarketPlaceListingProvider::find($id);
            if(empty($MarketPlaceListing)){
                return response()->json(["success"=>false,"message"=>"Oops! No record found"]);
            }
            if($type == "DELETE"){
                $save = $MarketPlaceListing->delete();
                if($save){
                    return response()->json(["success"=>true,"message"=>"Listing Deleted Successfully"]);
                }else{
                    return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
                }
            }else{
                $MarketPlaceListing->status = @$type;
                $save = $MarketPlaceListing->save();
                if($save){
                    return response()->json(["success"=>true,"message"=>"Status updated Successfully"]);
                }else{
                    return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
                }
            }
        }catch(\Exception $e){
            return response()->json(["success"=>false,"message"=>$e->getMessage()]);
        }
    }

    public function EditProviderListing(Request $request,$id){
        if($request->isMethod("post")){
            $MarketPlaceListing = MarketPlaceListingProvider::find($id);
            if(empty($MarketPlaceListing)){
                return back()->with(["success"=>false,"message"=>"Oops! No record found"]);
            }

            $MarketPlaceListing->email_id = @$request->email_id;
            $MarketPlaceListing->rate = @$request->rate;
            $MarketPlaceListing->tat = @$request->tat;
            $MarketPlaceListing->whatsapp = @$request->whatsapp;
            $MarketPlaceListing->twitter = @$request->twitter;
            $MarketPlaceListing->save();

            return back()->with(["success"=>true,"message"=>"Updated Successfully"]);
        }else{
            $data['MarketPlaceListing'] = MarketPlaceListingProvider::where('market_place_listing_providers.id',$id)->orderBy("market_place_listing_providers.id","desc")->first();
            return view('admin.marketplace-listing-provider-edit',$data);
        }
        
    }
}
