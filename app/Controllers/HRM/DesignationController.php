<?php

namespace  App\Controllers\HRM;

use App\Auth\Auth;
use App\Models\HRM\Designation;

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class DesignationController
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
        $this->designations = new Designation();
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
            case 'create':
                $this->createDesignation($request, $response);
                break;
            case 'getDesignations':
                $this->getDesignations();
                break;
            case 'getDesignationInfo':
                $this->getDesignationInfo($request, $response);
                break; 
            case 'editDesignations':
                $this->editDesignations($request, $response);
                break;
            case 'deleteDesignation':
                $this->deleteDesignation($request, $response);
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


    public function createDesignation(Request $request, Response $response)
    {
        $this->validator->validate($request, [
           "name"=>v::notEmpty(),
           "description"=>v::notEmpty(),
        //    "status"=>v::notEmpty()->intVal()
        ]);
        v::intVal()->notEmpty()->validate($this->params->status);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        //check duplicate department
        $designation = $this->designations->where(["name"=>$this->params->name])->first();
        if ($designation) {
            $this->success = false;
            $this->responseMessage = "Designation with the same name already exists!";
            return;
        }

        if($this->params->status == 'on'){
            $status = 1;
        }
        else{
            $status = 0;
        }

        $designation = $this->designations->create([
           "name" => $this->params->name,
           "description" => $this->params->description,
           "created_by" => $this->user->id,
           "status" => $status,
        ]);

        $this->responseMessage = "New designation created successfully";
        $this->outputData = $designation;
        $this->success = true;
    }

    public function getDesignations()
    {
        $designations = $this->designations->where('status',1)->get();

        $this->responseMessage = "Designations list fetched successfully";
        $this->outputData = $designations;
        $this->success = true;
    }

    public function getDesignationInfo(Request $request, Response $response)
    {
        if(!isset($this->params->designation_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $designation = $this->designations->find($this->params->designation_id);

        if($designation->status == 0){
            $this->success = false;
            $this->responseMessage = "Designation missing!";
            return;
        }

        if(!$designation){
            $this->success = false;
            $this->responseMessage = "Designation not found!";
            return;
        }

        foreach($designation->employees as $employee){
            $user = $employee->creator;
        }

        $this->responseMessage = "Designation info fetched successfully";
        $this->outputData = $designation;
        $this->outputData['creator'] = $designation->creator;
        $this->outputData['employees'] = $designation->employees;
        $this->outputData['user'] = $user;
        $this->success = true;
    }

    public function editDesignations(Request $request, Response $response)
    {
        if(!isset($this->params->designation_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $designation = $this->designations->find($this->params->designation_id);

        if(!$designation){
            $this->success = false;
            $this->responseMessage = "Designation not found!";
            return;
        }

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "description"=>v::notEmpty(),
            // "status"=>v::notEmpty()->intVal()
         ]);
         v::intVal()->notEmpty()->validate($this->params->status);
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }

         //check duplicate employee
        $current_designation = $this->designations->where(["name"=>$this->params->name])->first();
        if ($current_designation && $current_designation->id != $this->params->designation_id) {
            $this->success = false;
            $this->responseMessage = "Designation with the same name has already exists!";
            return;
        }

         $editedDesignation = $designation->update([
            "name" => $this->params->name,
            "description" => $this->params->description,
            "updated_by" => $this->user->id,
            "status" => $this->params->status,
         ]);
 
         $this->responseMessage = "Designation Updated successfully";
         $this->outputData = $editedDesignation;
         $this->success = true;
    }

    public function deleteDesignation(Request $request, Response $response)
    {
        if(!isset($this->params->designation_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $designation = $this->designations->find($this->params->designation_id);

        if(!$designation){
            $this->success = false;
            $this->responseMessage = "Designation not found!";
            return;
        }
        
        $deletedDesignation = $designation->update([
            "status" => 0,
         ]);
 
         $this->responseMessage = "Designation Deleted successfully";
         $this->outputData = $deletedDesignation;
         $this->success = true;
    }


    

    


    

}
