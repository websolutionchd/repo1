<?php

namespace App\Http\Controllers;

use App\Models\OtherListing;
use App\Models\OtherListingDetail;
use Illuminate\Http\Request;
use Storage;

class OtherListingDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $details = OtherListingDetail::where('parent_id',$request->parent_id)->get();
        $parent = OtherListing::where('id',$request->parent_id)->first(['title as name','id']);
        return view('admin.other-listing-detail',['details'=>$details,'parent'=>$parent]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request,$id=false)
    {
        $parent = OtherListing::where('id',$request->parent_id)->first(['title as name','id']);
        //
        $compact = compact("parent");
        if($id){
            $Listing  = OtherListingDetail::find($id);
            $compact = compact("parent","Listing");
        }
        return view('admin.add-edit-other-listing-detail',$compact);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$id=false)
    {
        $request->validate([
            // "title" =>"required",
            "parent_id" =>"required",
            // "tagline" =>"required",
            // "description" =>"required",
        ]);
        $OtherListingDetail = new OtherListingDetail();
        if($id){
            $OtherListingDetail =  OtherListingDetail::find($id);
        }
        $OtherListingDetail->parent_id = @$request->parent_id;
        // $OtherListingDetail->founded_year = @$request->founded_year;
        $OtherListingDetail->revenue = @$request->revenue;
        // $OtherListingDetail->founder = @$request->founder;
        $OtherListingDetail->parent_company = @$request->parent_company;
        // $OtherListingDetail->parent_location = @$request->parent_location;
        // $OtherListingDetail->website_url = @$request->website_url;
        // $OtherListingDetail->play_store_url = @$request->play_store_url;
        // $OtherListingDetail->apple_store_url = @$request->apple_store_url;
        // $OtherListingDetail->title = @$request->title;
        // $OtherListingDetail->tagline = @$request->tagline;
        // $OtherListingDetail->description = @$request->description;
        $OtherListingDetail->twitter = @$request->twitter;
        $OtherListingDetail->facebook = @$request->facebook;
        $OtherListingDetail->instagram = @$request->instagram;
        $OtherListingDetail->linkedIn = @$request->linkedIn;
        $OtherListingDetail->wikipedia = @$request->wikipedia;
        // try{
        //     if($request->has('logo')){
        //         $old_val = $OtherListingDetail->logo;
    
        //         $request->validate([
        //             'logo' => 'required|mimes:jpg,jpeg,png,gif,svg,webp|max:2048'
        //         ]);
    
        //         $fileName = str_replace(" ","-",time().'_'.$request->logo->getClientOriginalName());
        //         $filePath = $request->file('logo')->storeAs('uploads', $fileName, 'public');
        //         $OtherListingDetail->logo = '/storage/' . $filePath;
        //         if($id){
        //             Storage::disk('public')->delete(str_replace("/storage/",'',$old_val));
        //         }
        //     }
        // }catch(\Exception $e){
        //     $OtherListingDetail->logo = null;
        // }
        // try{
        //     if($request->has('website_logo')){
        //         $old_val = $OtherListingDetail->website_logo;
    
        //         $request->validate([
        //             'website_logo' => 'required|mimes:jpg,jpeg,png,gif,svg,webp|max:2048'
        //         ]);
    
        //         $fileName = str_replace(" ","-",time().'_'.$request->website_logo->getClientOriginalName());
        //         $filePath = $request->file('website_logo')->storeAs('uploads', $fileName, 'public');
        //         $OtherListingDetail->website_logo = '/storage/' . $filePath;
        //         if($id){
        //             Storage::disk('public')->delete(str_replace("/storage/",'',$old_val));
        //         }
        //     }
        // }catch(\Exception $e){
        //     $OtherListingDetail->website_logo = null;
        // }

        $OtherListingDetail->subdiaries = $request->subdiaries;
        $OtherListingDetail->youtube = $request->youtube;
        

        $OtherListingDetail->save();
        return redirect()->route('other-listing-details.edit',['other_listing_detail'=>$OtherListingDetail->id,'parent_id'=>$OtherListingDetail->parent_id])->with(["success"=>true,"message"=>"Updated Successfully"]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,$id)
    {   
        return $this->create($request,$id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        return $this->store($request,$id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        OtherListingDetail::where('id',$id)->delete();
        return back()->with(["success"=>true,"message"=>"Deleted successfully"]);
    }
}
