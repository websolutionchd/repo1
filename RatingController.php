<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\RatingReply;
use Auth;

class RatingController extends Controller
{
    public function store(Request $request){
        try{

            $Rating2 = Rating::where("client_email_id",@$request->client_email_id)
                            ->where("company_id",@$request->company_id)
                            ->first();
                   
            if($Rating2){
                $RatingReply = new RatingReply();
                $RatingReply->rating_id = $Rating2->id;
                $RatingReply->rating = @$request->rating;
                $RatingReply->company_id = @$request->company_id;
                $RatingReply->description = @$request->description;
                $save = $RatingReply->save();
                $RatingReply->review_id = 'REPLY'.$RatingReply->id;
                $RatingReply->save();
                if($save){
                    return back()->with(["success"=>true,"message"=>"Reviews Added Successfully"]);
                }
            }else{
                $Rating = new Rating();
                $Rating->rating = @$request->rating;
                $Rating->company_id = @$request->company_id;
                $Rating->subject = @$request->subject;
                $Rating->app_name = @$request->app_name;
                $Rating->website_name = @$request->website_name;
                $Rating->website_link = @$request->website_link;
                $Rating->client_name = @$request->client_name;
                $Rating->client_email_id = @$request->client_email_id;
                $Rating->client_phone_number = @$request->client_phone_number;
                $Rating->client_linkedIn = @$request->client_linkedIn;
                $Rating->engagement_date = @$request->engagement_date;
                $Rating->description = @$request->description;
                $save = $Rating->save();
                if($save){
                    $Rating->review_id = 'REVIEW'.$Rating->id;
                    $Rating->save();    
                }
                if($save){
                    return back()->with(["success"=>true,"message"=>"Reviews Added Successfully"]);
                } 
            }
            return back()->with(["success"=>false,"message"=>"There is some error while adding the review please contact to admin to report the issue"])->withInput();
        }catch(\Exception $e){
            return back()->with(["success"=>false,"message"=>$e->getMessage()])->withInput();
        }
    }

    public function reviewsEdit(Request $request,$id){
        try{
            $Rating = Rating::find($id);
            if(empty($Rating)){
                return abort("Invalid Request");
            }

            if($request->isMethod("get")){
                return view('admin.edit-review',["review"=>$Rating]);
            }
            
            $Rating->rating = @$request->rating;
            $Rating->subject = @$request->subject;
            $Rating->app_name = @$request->app_name;
            $Rating->website_name = @$request->website_name;
            $Rating->website_link = @$request->website_link;
            $Rating->client_name = @$request->client_name;
            $Rating->client_email_id = @$request->client_email_id;
            $Rating->client_phone_number = @$request->client_phone_number;
            $Rating->client_linkedIn = @$request->client_linkedIn;
            $Rating->engagement_date = @$request->engagement_date;
            $Rating->description = @$request->description;
            $save = $Rating->save();
            if($save){
                return back()->with(["success"=>true,"message"=>"Reviews Updated Successfully"]);
            }
          
            return back()->with(["success"=>false,"message"=>"There is some error while adding the review please contact to admin to report the issue"])->withInput();
        }catch(\Exception $e){
            return back()->with(["success"=>false,"message"=>$e->getMessage()])->withInput();
        }
    }




    public function index(Request $request,$type){
        $Rating = Rating::query();
        $Rating = $Rating->where("ratings.status",$type)
                    ->join("companies","companies.id","company_id")
                    ->orderBy("ratings.id","desc");
        if($request->has("search")){
            // if(strripos($request->search,"review") !== false){
            //     $id = str_replace("review","",$request->search);
            //     $id = str_replace("REVIEW","",$request->search);
            //     if($id !== ""){
            //         $Rating = $Rating->where("ratings.id",$id);
            //     }
            // }else{
                $Rating = $Rating->where("app_name",$request->search)
                                ->orWhere("subject",$request->search)
                                ->orWhere("subject",$request->search)
                                ->orWhere("review_id",$request->search);
            // }
        }
        
        $Rating = $Rating->paginate(25,["ratings.*","companies.name"]);
        
        return view("admin.manage-reviews",["Rating"=>$Rating,"type"=>$type,"search"=>$request->search]);
    }

    public function updateReview(Request $request,$id,$type){
        if(in_array($type,["CANCELLED","APPROVED","PENDING","PERMANENT"])){
            $Rating = Rating::find($id);
            if(empty($Rating)){
                return abort(500,"No Record found");    
            }
            if($type == "PERMANENT"){
                RatingReply::where('rating_id',$Rating->id)->delete();
                $Rating->delete();
                return back()->with(["success"=>true,"message"=>"Reviews deleted successfully"]);
            }else{
                $Rating->status = $type;
                $Rating->save();
                return back()->with(["success"=>true,"message"=>"Moved to {$type} list successfully"]);
            }
        }else{
            return abort(500,"Invalid Request");
        }
    }

    public function manageReviews(Request $request){
        return view("user.reviews");
    }

    public function ReviewsReply(Request $request,$id){
        try{
            if(empty($id)){
                return response()->json(["success"=>false,"message"=>"No ID Found"]);
            }elseif(empty($request->replyMessage)){
                return response()->json(["success"=>false,"message"=>"Reply Description can not be null, it should  containt atleast 40 words and minimum 90 words"]);
            }
            $Rating = Rating::find($id);
            if(empty($Rating)){
                return response()->json(["success"=>false,"message"=>"Oops! No record found"]);
            }
            $RatingReply = new RatingReply();
            $RatingReply->rating_id = $Rating->id;
            $RatingReply->company_id = @$Rating->company_id;
            $RatingReply->description = @$request->replyMessage;
            $RatingReply->created_by = Auth::user()->id;
            $RatingReply->status = 'APPROVED';
            $save = $RatingReply->save();
            
            
            if($save){
                $RatingReply->review_id = 'REPLY'.$RatingReply->id;
                $RatingReply->save();
                return response()->json(["success"=>true,"message"=>"Reply Send Successfully"]);
            }else{
                return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
            }
        }catch(\Exception $e){
            return response()->json(["success"=>false,"message"=>$e->getMessage()]);
        }
    }

    public function ReplyView(Request $request,$id){
        try{
            if(empty($id)){
                return response()->json(["success"=>false,"message"=>"No ID Found"]);
            }
            $Rating = RatingReply::where('rating_id',$id)->get();
            if(empty($Rating)){
                return response()->json(["success"=>false,"message"=>"Oops! No record found"]);
            }
            return response()->json(["success"=>true,"data"=>$Rating]);
        }catch(\Exception $e){
            return response()->json(["success"=>false,"message"=>$e->getMessage()]);
        }
    }

    public function ReplyEdit(Request $request,$id){
        try{
            if(empty($id)){
                return response()->json(["success"=>false,"message"=>"No ID Found"]);
            }elseif(empty($request->replyMessage)){
                return response()->json(["success"=>false,"message"=>"Reply Description can not be null, it should  containt atleast 40 words and minimum 90 words"]);
            }
            $RatingReply = RatingReply::find($id);
            if(empty($RatingReply)){
                return response()->json(["success"=>false,"message"=>"Oops! No record found"]);
            }
            $RatingReply->description = @$request->replyMessage;
            $RatingReply->actioned_by = Auth::user()->id;
            $RatingReply->actioned_at = date('Y-m-d h:i:s');

            $save = $RatingReply->save();
            if($save){
                return response()->json(["success"=>true,"message"=>"Reply updated Successfully"]);
            }else{
                return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
            }
        }catch(\Exception $e){
            return response()->json(["success"=>false,"message"=>$e->getMessage()]);
        }
    }

    public function ReplyStatusUpdate(Request $request,$id,$type){
        try{
            if(empty($id)){
                return response()->json(["success"=>false,"message"=>"No ID Found"]);
            }
            $RatingReply = RatingReply::find($id);
            if(empty($RatingReply)){
                return response()->json(["success"=>false,"message"=>"Oops! No record found"]);
            }
            if($type == "DELETE"){
                $save = $RatingReply->delete();
                if($save){
                    return response()->json(["success"=>true,"message"=>"Reply Deleted Successfully"]);
                }else{
                    return response()->json(["success"=>false,"message"=>"there is some issue with the replying, Please contact adminstratior"]);
                }
            }else{
                $RatingReply->status = @$type;
                $RatingReply->actioned_by = Auth::user()->id;
                $RatingReply->actioned_at = date('Y-m-d h:i:s');
                $save = $RatingReply->save();
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
}
