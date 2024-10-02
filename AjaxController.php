<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CompanyPortfolio;
use App\Models\Company;
use App\Models\CoreExperties;
use App\Models\OtherExperties;
use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Response;

class AjaxController extends Controller
{
    public function index(Request $request){
        switch($request->type){
            case 'GET_STATE':
                  return $this->GET_STATE($request->postdata,@$request->PLATFORM);  
            break;
            case 'GET_CITY':
                return $this->GET_CITY($request->postdata,@$request->PLATFORM);  
            break;
            case 'GET_PORTFOLIO':
                return $this->GET_PORTFOLIO($request->postdata,@$request->PLATFORM);  
            break;

            case 'GET_ALL_PORTFOLIO':
                return $this->GET_ALL_PORTFOLIO($request->postdata,@$request->PLATFORM);  
            break;
            case 'GET_CORE_EXPERTISE':
                return $this->GET_CORE_EXPERTISE($request->postdata,@$request->PLATFORM);  
            break;
            case 'GET_OTHER_EXPERTISE':
                return $this->GET_OTHER_EXPERTISE($request->postdata,@$request->PLATFORM);  
            break;

            case 'GET_COMPANIES_FOR_SELECT2':
                return $this->GET_COMPANIES_FOR_SELECT2($request->postdata,@$request->PLATFORM);  
            break;

            case 'GET_COUNTRY_STATE':
                return $this->GET_COUNTRY_STATE($request->postdata,@$request->PLATFORM);  
            break;

            case 'GET_CORE_EXPERTIES':
                return $this->GET_CORE_EXPERTIES($request->postdata,@$request->PLATFORM);  
            break;        
            
            default:
            return Response::json(["status"=>500,"message"=>"Invalid Request"]);
            break;
        }
    }

    public function GET_STATE($postdata,$PLATFORM = false){
        $State = State::where('country_id',$postdata['id'])->get();
        if(empty($State)) return response()->json(["status"=>500,"message"=>"No State found"]);
        $options = "<option value=''>Please select</option>";
        foreach($State as $rec){
            $options.="<option value='".$rec->id."'>".$rec->name."</option>";
        }
        return response()->json(["status"=>200,"data"=>$options]);
    }

    public function GET_CITY($postdata,$PLATFORM = false){
        $City = City::where('state_id',$postdata['id'])->get();
        if(empty($City)) return response()->json(["status"=>500,"message"=>"No State found"]);
        $options = "<option value=''>Please select</option>";
        foreach($City as $rec){
            $options.="<option value='".$rec->id."'>".$rec->name."</option>";
        }
        return response()->json(["status"=>200,"data"=>$options]);
    }

    public function GET_PORTFOLIO($postdata,$PLATFORM = false){
        $CompanyPortfolio = CompanyPortfolio::find($postdata['id']);
        if(empty($CompanyPortfolio)) return response()->json(["status"=>500,"message"=>"No Record found"]);
        return response()->json(["status"=>200,"data"=>$CompanyPortfolio]);
    }


    public function GET_ALL_PORTFOLIO($postdata,$PLATFORM = false){
        $CompanyPortfolio = CompanyPortfolio::where('parent_id',$postdata['id'])->get();
        $data = "";
        if(empty($CompanyPortfolio->count() > 0)){
            $data.="<tr><td colspan='7'> No Record Found </td></tr>";
        }else{
            $i = 1;
            foreach($CompanyPortfolio  as $rec):
                $data.='<tr>
                    <td>'.$i++.'</td>
                    <td>'.$rec->client_name.'</td>
                    <td>'.$rec->website_url.'</td>
                    <td>'.$rec->Industry.'</td>
                    <td>'.@$rec->country->name.'</td>
                    <td style=" overflow: hidden;white-space: nowrap;text-overflow: ellipsis;max-width: 150px;">'. $rec->description.'</td>
                    <td>
                        <span class="btn btn-primary edit_portfoloio" data-parent_id="'.$rec->parent_id.'" data-id="'.$rec->id.'" data-url="'.route('portfolio.edit',['id'=>$rec->id]).'"><i class="fa fa-pencil"></i></span>
                        <span class="btn btn-danger delete_portfoloio" data-parent_id="'.$rec->parent_id.'" data-url="'.route('portfolio.delete',['id'=>$rec->id]).'"><i class="fa fa-trash-o fas fa-trash"></i></span>
                    </td>
                </tr>';
            endforeach;
        }
        return response()->json(["status"=>200,"data"=>$data]);
    }

    public function GET_CORE_EXPERTISE($postdata,$PLATFORM = false){
        $CoreExperties = CoreExperties::find($postdata['id']);
        if(empty($CoreExperties)){
            return response()->json(["status"=>500,"message"=>"No record found"]);
        }
        return response()->json(["status"=>200,"data"=>$CoreExperties]);
    }

    public function GET_OTHER_EXPERTISE($postdata,$PLATFORM = false){
        $CoreExperties = OtherExperties::find($postdata['id']);
        if(empty($CoreExperties)){
            return response()->json(["status"=>500,"message"=>"No record found"]);
        }
        return response()->json(["status"=>200,"data"=>$CoreExperties]);
    }
    
    public function GET_COMPANIES_FOR_SELECT2($postdata,$PLATFORM = false){
        $key = @$postdata['search'];
        if(empty($key)) return response()->json([
            "results"=>[]
        ]);
        $Company = Company::where('name','like','%'.$key.'%')
                            ->where('status','published')
                            ->get();

        $response= [];
        if(@$postdata['for'] && $postdata['for'] == 'homesearch'){
            foreach($Company as $rec){
                $response[] = [
                                "id"=>$rec->name,
                                "text"=>$rec->name
                            ];
            }
        }else{
            foreach($Company as $rec){
                $response[] = [
                                "id"=>$rec->id,
                                "text"=>$rec->name
                            ];
            }
        }
        return response()->json([
            "results"=>$response
        ]);
    }

        public function GET_COUNTRY_STATE($postdata,$PLATFORM = false){
        $key = @$postdata['search'];
        if(empty($key)) return response()->json([
            "results"=>[]
        ]);
        $Country = Country::where('name','like','%'.$key.'%')->get();
        $State = State::where('name','like','%'.$key.'%')->get();
        $City = City::where('name','like','%'.$key.'%')->get();

        $response= [];
        foreach($Country as $rec){
            $response[] = [
                            "id"=>$rec->id.'_country_'.$rec->name,
                            "text"=>$rec->name
                        ];
        }

        foreach($State as $rec){
            $response[] = [
                            "id"=>$rec->id.'_state_'.$rec->name,
                            "text"=>$rec->name
                        ];
        }

        foreach($City as $rec){
            $response[] = [
                            "id"=>$rec->id.'_city_'.$rec->name,
                            "text"=>$rec->name
                        ];
        }

        return response()->json([
            "results"=>$response
        ]);
    }


    public function GET_CORE_EXPERTIES($postdata,$PLATFORM = false){
        $key = @$postdata['search'];
        // if(empty($key)) return response()->json([
        //     "results"=>[]
        // ]);
        $Country = CoreExperties::where('name','like','%'.$key.'%')->get();
        
        $response= [];
        foreach($Country as $rec){
            $response[] = [
                            "id"=>rawurlencode($rec->name),
                            "text"=>$rec->name
                        ];
        }
        return response()->json([
            "results"=>$response
        ]);
    }
    
}