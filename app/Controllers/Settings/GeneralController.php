<?php

namespace  App\Controllers\Settings;


use App\Auth\Auth;
use App\Validation\Validator;
use App\Response\CustomResponse;
use App\Models\Users\ClientUsers;
use App\Models\Settings\ConfigData;
use Illuminate\Pagination\Paginator;
use App\Requests\CustomRequestHandler;
use Respect\Validation\Validator as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class GeneralController
{

    protected $customResponse;

    protected $validator;

    protected $params;
    protected $responseMessage;
    protected $outputData;
    protected $success;
    protected $user;

    public function __construct()
    {
        $this->customResponse = new CustomResponse();
        $this->configData = new ConfigData();
        $this->clientUsers = new ClientUsers();
        $this->validator = new Validator();

        $this->responseMessage = "";
        $this->outputData = [];
        $this->success = false;
    }

   
    public function go(Request $request, Response $response)
    {
        $this->params = CustomRequestHandler::getAllParams($request);
        $action = isset($this->params->action) ? $this->params->action : "";

        $this->user = Auth::user($request);

        switch ($action) { 

            case 'configDataInfo':
                $this->configDataInfo();
                break;          
            case 'allTimeZone':
                $this->allTimeZone();
                break;          
            case 'updateOrCreateConfigData':
                $this->updateOrCreateConfigData($request);
                break;          
            case 'userVerifiaction':
                $this->userVerifiaction($request, $response);
                break;          
            default:
                $this->responseMessage = "Invalid request!";
                return $this->customResponse->is400Response($response, $this->responseMessage);
                break;
        }

        if (!$this->success) {
            return $this->customResponse->is400Response($response, $this->responseMessage, $this->outputData);
        }

        return $this->customResponse->is200Response($response, $this->responseMessage, $this->outputData);
    }

    public function userVerifiaction(Request $request, Response $response)
    {
        
       $user =  DB::table('org_users')->where('id',$this->user->id)->first();
       $hashedPassword = $user->password;
       $verify = password_verify($this->params->password, $hashedPassword);
       if($verify == true){
        $this->responseMessage = "Password Matched";
        $this->outputData = ['user'=>'verified'];
        $this->success = true;
       }else{
        $responseMessage = "Password Not Matched";
        return $this->customResponse->is400Response($response, $responseMessage);
       }
     
    }   

    public function allTimeZone()
    {
        $data = DB::table('time_zones')->select('id','name')->where('status',1)->get();
        $this->responseMessage = "Time zone fetched successfully";
        $this->outputData = $data; 
        $this->success = true;
    }


     public function updateOrCreateConfigData(Request $request){

        $this->validator->validate($request, [
            "config_value"=>v::notEmpty(),
            "config_name"=>v::notEmpty(),
        ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }
        $matchThese = ['config_name'=>$this->params->config_name];
        $configData = $this->configData->updateOrCreate($matchThese,[
            'config_value' => $this->params->config_value,
            'updated_by' => $this->user->id
        ]);

        // Write File 
        if($this->params->config_name == "Time Zone"){
            $xml=simplexml_load_file("../config/xml/config.xml"); 
            $xml->timeZone = $this->params->config_value; 
            $xml->asXML("../config/xml/config.xml"); 
        } 
       
        $this->responseMessage = "Config data has been updated successfully";
        $this->outputData = $configData;
        $this->success = true;
    }
 
    public function configDataInfo(){

        $configData = $this->configData->where('config_name',$this->params->name)->first();
        if(!$configData){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }
        $this->responseMessage = "Config data fetched successfully";
        $this->outputData = $configData; 
        $this->success = true;
    }

} 

?>