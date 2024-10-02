<?php

namespace App\Http\Controllers;

use App\Models\CoreExperties;
use App\Models\OtherExperties;
use Illuminate\Http\Request;

class ExpertiseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$id=false)
    {
        $CoreExperties = CoreExperties::Paginate(10);
        if($id){
            $expertise = CoreExperties::find($id);
            return view('admin.core-expertise',['CoreExperties'=>$CoreExperties,"expertise"=>$expertise]);
        }
        return view('admin.core-expertise',['CoreExperties'=>$CoreExperties]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $CoreExperties = new CoreExperties();
        $CoreExperties->name = $request->name;
        $CoreExperties->status = $request->status;
        $CoreExperties->description = @$request->description;
        $CoreExperties->heading = @$request->heading;
        $CoreExperties->meta_description = @$request->meta_description;
        $CoreExperties->meta_title = @$request->meta_title;
        $CoreExperties->save();
        return back()->with(["success"=>true,"message"=>"Added Successfully"]);
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
        return $this->index($request,$id);
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
        $CoreExperties =  CoreExperties::find($id);
        if($request->has('name')){
            $CoreExperties->name = $request->name;
        }
        if($request->has('status')){
            $CoreExperties->status = $request->status;    
        }
        $CoreExperties->description = @$request->description;
        $CoreExperties->heading = @$request->heading;
        $CoreExperties->meta_description = @$request->meta_description;
        $CoreExperties->meta_title = @$request->meta_title;
        $CoreExperties->save();
        return back()->with(["success"=>true,"message"=>"updated Successfully"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
