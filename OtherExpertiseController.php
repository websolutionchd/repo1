<?php

namespace App\Http\Controllers;

use App\Models\OtherExperties;
use Illuminate\Http\Request;

class OtherExpertiseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $OtherExperties = OtherExperties::Paginate(10);
        return view('admin.other-expertise',['OtherExperties'=>$OtherExperties]);
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
        $OtherExperties = new OtherExperties();
        $OtherExperties->name = $request->name;
        $OtherExperties->status = $request->status;
        $OtherExperties->save();
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
    public function edit($id)
    {
        //
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
        $OtherExperties =  OtherExperties::find($id);
        if($request->has('name')){
            $OtherExperties->name = $request->name;
        }
        if($request->has('status')){
            $OtherExperties->status = $request->status;    
        }
        
        $OtherExperties->save();
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
