<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\ListingDetail;
use App\Models\Country;
use App\Models\Company;
use App\Models\CoreExperties;
use Validator;
use Storage;
use Illuminate\Support\Facades\Route;
use Auth;
use DB;

class ListingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
     public function ajaxListing(Request $request){
       

            $columns = array(
                "title",
                "core_experties",
                "",
                "",
                "",
                "sub_heading1",
                "",
                "",
                "created_at",
                "updated_at",
            ); 

            $totalTitles = Listing::withTrashed()->orderBy('id','desc')->get();
            $totalTitles = $totalTitles->count();
            $totalFiltered = $totalTitles;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $page_dir = $request->input('order.0.dir');
    
            if (empty($request->input('search.value'))) {
                $titles = Listing::withTrashed()->orderBy('id','desc');
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
            } else {
                $string_search = $request->input('search.value');
                $titles = Listing::withTrashed()->where('title', 'LIKE', "%{$string_search}%")
                                ->orWhere('core_experties', 'LIKE', "%{$string_search}%")
                                ->orWhere('sub_heading1', 'LIKE', "%{$string_search}%");
                
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

                    $nestedData["Title"]= '<div class="text-bold text-blue">
                    <a href="'.route(strtolower(str_replace(' ','-' ,$rec->core_experties)) ,['slug'=>$rec->slug]).'" data-id="'.$rec->id.'" class="btn btn-primary p-2 showAskQsnModal" target="_blank" > <i class="fas fa-eye"></i></a>
                    &nbsp;&nbsp;
                    <a href="'.route("listing.edit",["listing"=>$rec->id]).'" class="btn btn-info p-2 showAskQsnModal" target="_blank"> <i class="fas fa-pencil"></i></a>
                    &nbsp;&nbsp;
                                    <a href="javascript:void(0)" class="hover-blue">'.@$rec->title.'</a>
                                    </div>';
                    $nestedData["Category"]= '<div class="text-bold text-blue">
                    <a href="javascript:void(0)" class="hover-blue">'.@$rec->core_experties.'</a>
                </div>';
                    $nestedData["Coutry"]= $rec->country->name;
                    $nestedData["State"]= @$rec->state->name;
                    $nestedData["City"]= @$rec->city->name;
                    $nestedData["Sub"]= @$rec->sub_heading1;
                    $nestedData["Show"]= ($rec->show_verified == 1) ? 'Yes' : 'No';
                    $nestedData["Status"]= is_null($rec->deleted_at) ? '<span class="text-green">Published</span>' : '<span class="text-danger">Drafted</span>';
                    $nestedData["Published"]= date('d-M-y',strtotime($rec->created_at));
                    $nestedData["Modified"]= date('d-M-y',strtotime($rec->updated_at));
                    $nestedData["Action"]= ' <div class="text-bold text-center text-blue">
                        <a href="javascript:void(0)" class="btn btn-danger p-2 showAskQsnModal" onClick="if(confirm(\'Are you sure you want to perform this task?\')) {
                            console.log(document.getElementById(\'destroyCompaniesID\'));
                                document.getElementById(\'destroyCompaniesID\').value = '.$rec->id.';
                                document.getElementById(\'destroyCompanies\').submit();
                            }">';
                    
                    if(is_null($rec->deleted_at)) $nestedData["Action"].='<i class="fas fa-trash"></i>';
                    else $nestedData["Action"].= '<i class="fas fa-undo"></i>';
                    
                    $nestedData["Action"].=  '</a> </div>';
                    $all_products_data[] = $nestedData;
                    $count++;
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

    public function index(Request $request)
    {
        return view('admin.listing');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($Listing=false)
    {
        $Country = Country::all();
        $CoreExperties = CoreExperties::where('status','1')->get();
        //$Company = Company::where('status','published')->get();
        $compact = compact('Country','CoreExperties');
        if($Listing){
            $compact = compact('Country','CoreExperties','Listing');
        }

        return view("admin.add-edit-listing",$compact);
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
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',
            'meta_title' => 'required',
            'meta_description' => 'required',
            'slug' => 'required',
            'description' => 'required',
            'sub_heading1' => 'required|max:255',
            'sub_description1' => 'required',
            'show_verified'=>"required",
            'sub_heading2'=>"required_if:show_verified,1",
            'sub_description2'=>"required_if:show_verified,1",
            'sub_listing'=>"required_if:show_verified,1",
            'sidebar_image' => 'nullable|mimes:jpg,jpeg,png,gif,webp,svg|max:2048',

        ]);

        if($id){
            $request->validate([
                'title' => 'required|unique:listings,title,'.$id.'|max:255'
            ]);
        }else{
            $request->validate([
                'title' => 'required|unique:listings|max:255'
            ]);
        }
        

        //return back()->with(["success"=>true,"message"=>"listed Successfully"]);
        
        $Listing = new Listing();
        if($id){
            $Listing = Listing::withTrashed()->find($id);
        }

        $Listing->country_id = @$request->country_id;;
        $Listing->state_id = @$request->state_id; 
        $Listing->city_id = @$request->city_id; 
        $Listing->title = @$request->title; 
        $Listing->meta_title = @$request->meta_title;
        $Listing->meta_description = @$request->meta_description;
        $Listing->slug = @$request->slug;
        $Listing->core_experties = @$request->core_experties;
        $Listing->description = @$request->description; 
        $Listing->sub_heading1 = @$request->sub_heading1; 
        $Listing->sub_description1 = @$request->sub_description1;
        $Listing->show_verified = $request->show_verified;
        $Listing->cto_heading = $request->cto_heading;
        
        
            $Listing->sub_heading2 = @$request->sub_heading2;
            $Listing->sub_description2 = @$request->sub_description2;
            $sub = [];
            if($request->show_verified == 1){
                for($i=0;$i<3;$i++){
                    $sub[] = [
                                'id'=>$request->sub_listing[$i],
                                'url'=>@$request->sub_listing_url[$i]
                            ];
                }
            }
            $Listing->sub_listing = json_encode(@$sub);

            $faq = [];
            for($i=0;$i<count($request->question);$i++){
                $faq[] = [
                            'question'=>$request->question[$i],
                            'answer'=>@$request->answer[$i]
                        ];
            }
            $Listing->faq = json_encode(@$faq);


        //} 


        if($request->has('sidebar_image')){
            $old_val = $Listing->sidebar_image;
            $fileName = str_replace(" ","-",time().'_'.$request->sidebar_image->getClientOriginalName());
            $filePath = $request->file('sidebar_image')->storeAs('uploads', $fileName, 'public');
            $Listing->sidebar_image = '/storage/' . $filePath;
            Storage::disk('public')->delete(str_replace("/storage/",'',$old_val));
        }
        $Listing->save();

        if($id){
            ListingDetail::where('parent_id',$id)->delete();
        }

        $count = count($request->listing_details['company']);
        for ($i=0;$i<$count;$i++) {
              $ListingDetail = new ListingDetail;
              $ListingDetail->company_id = $request->listing_details['company'][$i];
              $ListingDetail->parent_id = $Listing->id;
              $ListingDetail->url = $request->listing_details['url'][$i];
              $ListingDetail->order = $request->listing_details['order'][$i];
              $ListingDetail->description = $request->listing_details['description'][$i];
              $ListingDetail->save();
        }
        
        return back()->with(["success"=>true,"message"=>"listed Successfully"]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $Listing = Listing::withTrashed()->find($id);
        if(empty($Listing)) return abort(501,"Invalid Request");
        return $this->create($Listing);
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

    public function destroy(Request $request){
        $Listing = Listing::withTrashed()->find($request->id);
        if($Listing->deleted_at != null){
            $Listing->deleted_at = null;
            $Listing->save();
            return back()->with(["success"=>true,"message"=>"Published Successfully"]);
        }else{
            $Listing->delete();    
            return back()->with(["success"=>true,"message"=>"Moved to draft Successfully"]);
        }
        
        
    }


    public function viewListing(Request $request,$slug=false){
        $category = Route::currentRouteName();
        if($slug){
            $category = str_replace('-',' ',$category);
            if(Auth::guard('admin')->check()){
                $Listing = Listing::withTrashed()->where('core_experties',$category)->where('slug',$slug)->first(); 
            }else{
                $Listing = Listing::where('core_experties',$category)->where('slug',$slug)->first();     
            }
            
            if($Listing){
                $title = $Listing->meta_title;
                $description = $Listing->meta_description;
                return view('view-listing',["Listing"=>$Listing,"title"=>$title,"description"=>$description]);
            }
            return abort(501,"Oops! No record match");
            
        }else{
            $category = str_replace('-',' ',$category);
            $Listing = Listing::where('core_experties',$category)->paginate(20);     
            $page = CoreExperties::where('name',$category)->first();
            $title = $page->meta_title;
            $description = $page->meta_description;

            $core_experties = DB::select("select DISTINCT(core_experties) as core_experties from companies where core_experties is not null and status  = 'published'");


            return view('category',["Listing"=>$Listing,"title"=>$title,"description"=>$description,'page'=>$page,'core_experties'=>$core_experties]); 
        }
    }
}
