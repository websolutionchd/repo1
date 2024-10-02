<?php

namespace App\Http\Controllers;

use App\Models\OtherListing;
use App\Models\OtherListingCategory;
use App\Models\OtherListingDetail;
use Illuminate\Http\Request;
use Storage;
use Auth;
class OtherListingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


     public function ajaxOtherListing(Request $request){
       

            $columns = array(
                "title",
                "",
                "",
                "",
                "",
                "sub_heading1",
                "",
                "",
                "created_at",
                "updated_at",
            ); 

            $totalTitles = OtherListing::withTrashed()->orderBy('id','desc')->get();
            $totalTitles = $totalTitles->count();
            $totalFiltered = $totalTitles;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $page_dir = $request->input('order.0.dir');

            if (empty($request->input('search.value'))) {
                $titles = OtherListing::withTrashed()->orderBy('id','desc');
                $titles = $titles->offset($start)
                                ->limit($limit)
                                ->orderBy($order, $page_dir)
                                ->get();
            } else {
                $string_search = $request->input('search.value');
                $titles = OtherListing::withTrashed()->where('title', 'LIKE', "%{$string_search}%")
                                //->orWhere('core_experties', 'LIKE', "%{$string_search}%")
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
                    <a href="'.route("view-other-listing".$rec->category_id,["tag"=>strtolower($rec->place),"slug"=>$rec->slug]).'" class="btn btn-primary p-2 showAskQsnModal" target="_blank" > <i class="fas fa-eye"></i></a>
                    &nbsp;&nbsp;
                    <a href="'.route("other-listing.edit",["other_listing"=>$rec->id]).'" class="btn btn-info p-2 showAskQsnModal" target="_blank"> <i class="fas fa-pencil"></i></a>
                    &nbsp;&nbsp;
                                    <a href="javascript:void(0)" class="hover-blue">'.@$rec->title.'</a>
                                    </div>';
                    $nestedData["Category"]= '<div class="text-bold text-blue">
                    <a href="javascript:void(0)" class="hover-blue">'.@$rec->core_experties.'</a>
                </div>';
                    $nestedData["place"]= $rec->place;
                    $nestedData["Category"]= $rec->category->name;
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

    public function index()
    {
        return view("admin.other-listing");
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request,$id=false)
    {
        $OtherListingCategory = OtherListingCategory::where('status','1')->get();
        if($id){
            $Listing = OtherListing::withTrashed()->find($id);
            return view("admin.add-edit-other-listing",compact('OtherListingCategory','Listing'));
        }
        return view("admin.add-edit-other-listing",compact('OtherListingCategory'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$id = false)
    {
        // echo "<pre>";
        // print_r(@$request->listing_details);die;
        $request->validate([
            'place' => 'required',
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
            "category_id"=>'required',
            'sidebar_image' => 'nullable|mimes:jpg,jpeg,png,gif,webp,svg|max:2048',
        ]);

        if($id){
            $request->validate([
                'title' => 'required|unique:other_listings,title,'.$id.'|max:255'
            ]);
        }else{
            $request->validate([
                'title' => 'required|unique:other_listings|max:255'
            ]);
        }
    
        $Listing = new OtherListing();
        if($id){
            $Listing = OtherListing::withTrashed()->find($id);
        }
        $Listing->place = @$request->place; 
        $Listing->title = @$request->title; 
        $Listing->meta_title = @$request->meta_title;
        $Listing->meta_description = @$request->meta_description;
        $Listing->slug = @$request->slug;
        $Listing->category_id = @$request->category_id;
        $Listing->description = @$request->description; 
        $Listing->sub_heading1 = @$request->sub_heading1; 
        $Listing->sub_description1 = @$request->sub_description1;
        $Listing->show_verified = $request->show_verified;
        $Listing->sub_heading2 = @$request->sub_heading2;
        $Listing->sub_description2 = @$request->sub_description2;
        $sub = [];
        if($Listing->show_verified){
        for($i=0;$i<3;$i++){
            if($id){
                $sub_listing = (array) json_decode($Listing->sub_listing);
                if(empty($request->file('sub_listing_logo')[$i])){
                    $sub[] = [
                                'name'=>$request->sub_listing[$i],
                                'url'=>@$request->sub_listing_url[$i],
                                "logo" => $sub_listing[$i]->logo
                            ]; 
                }else{
                    if(@$sub_listing[$i] && isset($sub_listing[$i])){
                        Storage::disk('public')->delete(str_replace("/storage/",'',$sub_listing[$i]->url));    
                    }
                    
                
                    $fileName = str_replace(" ","-",time().'_'.$request->sub_listing_logo[$i]->getClientOriginalName());
                    $filePath = $request->file('sub_listing_logo')[$i]->storeAs('uploads', $fileName, 'public');
                    $sub[] = [
                                'name'=>$request->sub_listing[$i],
                                'url'=>@$request->sub_listing_url[$i],
                                "logo" =>'/storage/' . $filePath
                            ]; 
                }
            }else{
                $fileName = str_replace(" ","-",time().'_'.$request->sub_listing_logo[$i]->getClientOriginalName());
                $filePath = $request->file('sub_listing_logo')[$i]->storeAs('uploads', $fileName, 'public');
                $sub[] = [
                            'name'=>$request->sub_listing[$i],
                            'url'=>@$request->sub_listing_url[$i],
                            "logo" =>'/storage/' . $filePath
                        ];
            }  
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
        
        if($request->has('sidebar_image')){
            $old_val = $Listing->sidebar_image;
            $fileName = str_replace(" ","-",time().'_'.$request->sidebar_image->getClientOriginalName());
            $filePath = $request->file('sidebar_image')->storeAs('uploads', $fileName, 'public');
            $Listing->sidebar_image = '/storage/' . $filePath;
            Storage::disk('public')->delete(str_replace("/storage/",'',$old_val));
        }
        
        $Listing->save();


        for($i=0;$i < count($request->listing_details['title']);$i++){
            $old = null;
           
            if(@$request->listing_details['id'][$i]){
                $OtherListingDetail =  OtherListingDetail::find($request->listing_details['id'][$i]);
                $old = $OtherListingDetail->logo;
            }else{
                $OtherListingDetail = new OtherListingDetail();
            }
                
            $OtherListingDetail->title = @$request->listing_details['title'][$i];
            $OtherListingDetail->tagline = @$request->listing_details['tagline'][$i];
            $OtherListingDetail->description = @$request->listing_details['description'][$i];
            $OtherListingDetail->order = @$request->listing_details['order'][$i];
            $OtherListingDetail->parent_id = @$Listing->id;
            
            $OtherListingDetail->apple_store_url = @$request->listing_details['apple_store_url'][$i];
            $OtherListingDetail->play_store_url = @$request->listing_details['play_store_url'][$i];
            $OtherListingDetail->website_url =@$request->listing_details['website_url'][$i];
            $OtherListingDetail->founded_year =  @$request->listing_details['founded_year'][$i];
            $OtherListingDetail->founder =  @$request->listing_details['founder'][$i];
            $OtherListingDetail->parent_location =  @$request->listing_details['parent_location'][$i];
            $file = @$request->listing_details['logo'][$i];
            if(!empty($file)){
                $fileName = str_replace(" ","-",time().'_'.$file->getClientOriginalName());
                $filePath = $file->storeAs('uploads', $fileName, 'public');
                $OtherListingDetail->logo = '/storage/' . $filePath;
                if($old){
                    Storage::disk('public')->delete(str_replace("/storage/",'',$old)); 
                }
            }
            $OtherListingDetail->save();
        }
        
        return redirect()->route("other-listing.edit",["other_listing"=>$Listing->id])->with(["success"=>true,"message"=>"listed Successfully"]);
    }

    
    public function edit(Request $request,$id)
    {
        return $this->create($request,$id);
    }

    public function update(Request $request, $id)
    {
        return $this->store($request, $id);
    }

    public function destroy(Request $request)
    {
        $Listing = OtherListing::withTrashed()->find($request->id);
        if($Listing->deleted_at != null){
            $Listing->deleted_at = null;
            $Listing->save();
            return back()->with(["success"=>true,"message"=>"Published Successfully"]);
        }else{
            $Listing->delete();    
            return back()->with(["success"=>true,"message"=>"Moved to draft Successfully"]);
        }
    }

    public function viewOtherListing(Request $request,$tag,$slug=false){
        if($slug){
            $OtherListing = OtherListing::query();
            if(Auth::guard('admin')->check()) {
                $OtherListing = $OtherListing->withTrashed();
            }
            $OtherListing = $OtherListing->where('slug',$slug)->first();
            if(empty($OtherListing)){
                abort(501,"Sorry No Record found");
            }
            $title = $OtherListing->meta_title;
            $description = $OtherListing->meta_description;
            $more_list = OtherListing::where('place',$OtherListing->place)
                                    ->where('category_id',$OtherListing->category_id)
                                    ->where('id','!=',$OtherListing->id)
                                    ->get(["place","slug","title","category_id"]);
            return view('view-other-listing',compact('OtherListing','title','description','more_list'));
        }else{

        }
    }
}
