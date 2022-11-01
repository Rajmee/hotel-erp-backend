<?php

namespace  App\Controllers\HRM;

use App\Auth\Auth;
use App\Models\HRM\Departments;

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Rules\Number;
use Respect\Validation\Validator as v;

class DepartmentController
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
        $this->departments = new Departments();
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
                $this->createDepartment($request, $response);
                break;
            case 'getDepartmentInfo':
                $this->getDepartmentInfo();
                break;
            case 'getAllDepartments':
                $this->getAllDepartments();
                break;
            case 'editDepartments':
                $this->editDepartment($request, $response);
                break;
            case 'deleteDepartment':
                $this->deleteDepartment();
                break;
            default:
                $this->responseMessage = "Invalid request!";
                return $this->customResponse->is400Response($response, $this->responseMessage);
        }

        if (!$this->success) {
            return $this->customResponse->is400Response($response, $this->responseMessage, $this->outputData);
        }

        return $this->customResponse->is200Response($response, $this->responseMessage, $this->outputData);
    }


    public function createDepartment(Request $request, Response $response)
    {

        $this->validator->validate($request, [
           "name"=>v::notEmpty(),
           "description"=>v::notEmpty(),
           "status"=>v::notEmpty()->intVal()
        ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        //check duplicate department
        $department = $this->departments->where(["name"=>$this->params->name])->first();
        if ($department) {
            $this->success = false;
            $this->responseMessage = "Department with the same name already exists!";
            return;
        }

        $department = $this->departments->create([
           "name" => $this->params->name,
           "description" => $this->params->description,
           "company" => $this->user->company,
           "clientID" => $this->user->clientID,
           "created_by" => $this->user->id,
           "status" => 1,
        ]);

        $this->responseMessage = "New department created successfully";
        $this->outputData = $department;
        $this->success = true;
    }

    public function deleteDepartment(){
        if(!isset($this->params->department)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $department = $this->departments->where('id', $this->params->department)->delete();
        // $department = $this->departments->where(["id"=>$this->params->department_id])->delete();

        if(!$department){
            $this->success = false;
            $this->responseMessage = "Couldn't remove successfully, Please contact with Admin.";
            return;
        }

        $this->responseMessage = "Department removed successfully!!";
        //$this->outputData = $this->params->department;
        //$this->outputData['creator'] = $department->creator;
        $this->success = true;
    }

    public function editDepartment(Request $request){
        if(!isset($this->params->data)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        //Check validation ######

        v::notEmpty()->validate($request, $this->params->department_id);
        v::notEmpty()->validate($request, $this->params->data['department_name']);
        v::notEmpty()->validate($request, $this->params->data['department_description']);
        v::notEmpty()->validate($request, $this->params->data['department_created_by']);
        v::notEmpty()->validate($request, $this->params->data['department_created_at']);
        v::notEmpty()->validate($request, $this->params->data['department_status']);

        if($this->validator->failed()){
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }
        
         //Update part ######
         $department = $this->departments->where(["id"=>$this->params->department_id])
         ->update([
            "name" => $this->params->data['department_name'],
            "description" => $this->params->data['department_description'],
            "created_by" => $this->params->data['department_created_by'],
            "created_at" => $this->params->data['department_created_at'],
            "status" => $this->params->data['department_status'],
         ]);

         //->where()->equals('id',$this->params->department_id);
        //Update part ######

        $this->responseMessage = "Hey, Update Department Success!";
        //$this->outputData = $this->$department;
        $this->success = true;
    }

    public function getAllDepartments(){
        $department = $this->departments->all();
        if(!$department){
            $this->success = false;
            $this->responseMessage = "Department not found!";
            return;
        }
        $this->responseMessage = "All Department fetched successfully";
        $this->outputData = $department;
        $this->success = true;
    }

    public function getDepartmentInfo()
    {
        if(!isset($this->params->department)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $department = $this->departments->find($this->params->department);

        if(!$department){
            $this->success = false;
            $this->responseMessage = "Department not found!";
            return;
        }

        $this->responseMessage = "Department info fetched successfully";
        $this->outputData = $department;
        $this->outputData['creator'] = $department->creator;
        $this->success = true;
    }

}

