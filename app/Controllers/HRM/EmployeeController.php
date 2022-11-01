<?php

namespace  App\Controllers\HRM;

use App\Auth\Auth;
use App\Models\HRM\Employee;
use App\Models\Users\ClientUsers;
use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class EmployeeController
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
        $this->employee = new Employee();
        $this->newUser = new ClientUsers();
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
            case 'allEmployee':
                $this->allEmployee();
                break;  
            case 'addEmployee':
                $this->createEmployee($request, $response);
                break;  
            case 'getEmployeeInfo':
                $this->getEmployeeInfo();
                break;  
            case 'updateEmployeeInfo':
                $this->updateEmployeeInfo($request, $response);
                break;         
            case 'deleteEmployee':
                $this->deleteEmployee($request, $response);
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

    //All Employee
    public function allEmployee(){
        $employee = $this->employee->where('status',1)->get();
        if(!$employee){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }
        $this->responseMessage = "All Employee fetched successfully";
        $this->outputData = $employee;
        $this->success = true;
    }

    //Add employee
    public function createEmployee(Request $request, Response $response)
    {

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "designation_id"=>v::notEmpty(),
            "department_id"=>v::notEmpty(),
            "address"=>v::notEmpty(),
            "gender"=>v::notEmpty(),
            "salary_type"=>v::notEmpty(),
            "salary_amount"=>v::notEmpty(),
            "mobile"=>v::notEmpty(),
            "email"=>v::notEmpty(),
         ]);
         //status validation
         v::intVal()->notEmpty()->validate($this->params->status);

        //if is_user checked then validation check
        if($this->params->is_user == 1){
            $this->validator->validate($request, [
                "role_id"=>v::notEmpty(),
                "country_id"=>v::notEmpty(),
                "city_id"=>v::notEmpty(),
                "state_id"=>v::notEmpty(),
                ]);

            //status validation
            v::intVal()->notEmpty()->validate($this->params->user_status);


            //password checking
            if($this->params->user_defined_password != true){
                $this->validator->validate($request, [
                    "password"=>v::notEmpty()
                ]);

                if ($this->validator->failed()) {
                    $this->success = false;
                    $this->responseMessage = $this->validator->errors;
                    return;
                }
            }
        
        }
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }



        //check duplicate employee
        $employee = $this->employee->where(["mobile"=>$this->params->mobile])->orWhere(["email"=>$this->params->email])->first();
        if ($employee) {
            $this->success = false;
            $this->responseMessage = "Employee with the same email or mobile number already exists!";
            return; 
        }

        //Check is user credentials
        $employee = $this->employee;
        $employee->name = $this->params->name;
        $employee->designation_id = $this->params->designation_id;
        $employee->department_id = $this->params->department_id;
        $employee->company = $this->user->company;
        $employee->clientID = $this->user->clientID;
        $employee->address = $this->params->address;
        $employee->gender = $this->params->gender;
        $employee->salary_type = $this->params->salary_type;
        $employee->salary_amount = $this->params->salary_amount;
        $employee->description = $this->params->description;
        $employee->mobile = $this->params->mobile;
        $employee->email = $this->params->email;
        $employee->created_by = $this->user->id;
        $employee->status = $this->params->status;

        //if is_user checked then create an user

        if($this->params->is_user == true){
            $user = $this->newUser;
            $user->name = $this->params->name;
            $user->email = $this->params->email;
            if($this->params->user_defined_password != true){
                //$passwordHash = password_hash($this->params->password, PASSWORD_DEFAULT);
                $user->password = password_hash($this->params->password, PASSWORD_DEFAULT);
            }
            else{
                $user->password = "";
                //send mail to set user password Mail::send()
            }
            $user->phone = $this->params->mobile;
            $user->company = $this->user->company;
            $user->clientID = $this->user->clientID;
            $user->address = $this->params->address;
            $user->role_id = $this->params->role_id;
            $user->country_id = $this->params->country_id;
            $user->city_id = $this->params->city_id;
            $user->state_id = $this->params->state_id;
            $user->status = $this->params->user_status;
            $user->created_by = $this->user->id;
            $user->save();

            $employee->user_id = $user->id;
        }

        $employee->save();

        $this->responseMessage = "New employee has been created successfully";
        $this->outputData = $employee;
        $this->success = true;
    }
    //Get employee details
    public function getEmployeeInfo(){
        if(!isset($this->params->employee_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        $employee = $this->employee->findOrFail($this->params->employee_id);

        if(!$employee){
            $this->success = false;
            $this->responseMessage = "Employee not found!";
            return;
        }


        $this->success = true;
        $this->responseMessage = "Employee info fetched successfully!";
        $this->outputData = $employee;
        $this->outputData['creator'] = $employee->creator;
        $this->outputData['department'] = $employee->department;
        $this->outputData['designation'] = $employee->designation;
        if($employee->user_id != null){
            $this->outputData['user'] = $employee->user;
            $this->outputData['user_role'] = $employee->user->role;
            $this->outputData['user_country'] = $employee->user->country;
            $this->outputData['user_state'] = $employee->user->state;
            $this->outputData['user_city'] = $employee->user->city;
        }
    }

    //update employee
    public function updateEmployeeInfo(Request $request, Response $response)
    {
        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "designation_id"=>v::notEmpty(),
            "department_id"=>v::notEmpty(),
            "address"=>v::notEmpty(),
            "gender"=>v::notEmpty(),
            "salary_type"=>v::notEmpty(),
            "salary_amount"=>v::notEmpty(),
            "mobile"=>v::notEmpty(),
            "email"=>v::notEmpty(),
         ]);
         //status validation
         v::intVal()->notEmpty()->validate($this->params->status);

        //if is_user checked then validation check
        if($this->params->is_user == 1){
            $this->validator->validate($request, [
                "role_id"=>v::notEmpty(),
                "country_id"=>v::notEmpty(),
                "city_id"=>v::notEmpty(),
                "state_id"=>v::notEmpty(),
                // "user_status"=>v::notEmpty()
                ]);
            //status validation
            v::intVal()->notEmpty()->validate($this->params->user_status);


            //password checking
            if($this->params->user_defined_password != true){
                $this->validator->validate($request, [
                    "password"=>v::notEmpty()
                ]);

                if ($this->validator->failed()) {
                    $this->success = false;
                    $this->responseMessage = $this->validator->errors;
                    return;
                }
            }
            
        }
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }



        //check duplicate employee
        $employee = $this->employee->where(["email"=>$this->params->email])->first();
        if ($employee && $employee->id != $this->params->employee_id) {
            $this->success = false;
            $this->responseMessage = "Employee with the same email has already exists!";
            return;
        }

        if(!isset($this->params->employee_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        $employee = $this->employee->find($this->params->employee_id);

        if(!$employee){
            $this->success = false;
            $this->responseMessage = "Employee not found!";
            return;
        }

            $employee->name = $this->params->name;
            $employee->designation_id = $this->params->designation_id;
            $employee->department_id = $this->params->department_id;
            $employee->company = $this->user->company;
            $employee->clientID = $this->user->clientID;
            $employee->address = $this->params->address;
            $employee->gender = $this->params->gender;
            $employee->salary_type = $this->params->salary_type;
            $employee->salary_amount = $this->params->salary_amount;
            $employee->description = $this->params->description;
            $employee->mobile = $this->params->mobile;
            $employee->email = $this->params->email;
            $employee->created_by = $this->user->id;
            //if is_user checked then create an user

            $user = $this->newUser->find($employee->user->id);

            if($this->params->is_user === true){
                if(!$user){
                    $user = $this->newUser;
                }

                $user->name = $this->params->name;
                $user->email = $this->params->email;
                if(!$this->params->user_defined_password){
                    $user->password = password_hash($this->params->password, PASSWORD_DEFAULT); //Pass valid
                }
                else{
                    $user->password = null;
                    //send mail to set user password Mail::send()
                }

                $user->phone = $this->params->mobile;
                $user->company = $this->user->company;
                $user->clientID = $this->user->clientID;
                $user->address = $this->params->address;
                $user->role_id = $this->params->role_id;
                $user->country_id = $this->params->country_id;
                $user->city_id = $this->params->city_id;
                $user->state_id = $this->params->state_id;
                $user->status = 1;
                $user->created_by = $this->user->id;
                $user->save();

            }
            if($this->params->is_user === false){
                if($user){
                    $user->status = 0;
                    $user->save();
                }
            }

            $employee->user_id = $user->id;
            $employee->save();
            
 
        $this->responseMessage = "Employee has been updated successfully";
        $this->outputData = $employee;
        $this->success = true;
    }

    //Delete Employee
    public function deleteEmployee(Request $request, Response $response){
        $employee = $this->employee->find($this->params->employee_id);
        if(!$employee){
            $this->success = false;
            $this->responseMessage = "Employee not found !";
            return;
        }

        $employee->status = 0;
        $employee->save();

        $user = $this->newUser->find($employee->user->id);
        if($user){
            $user->status = 0;
            $user->save();
        }

        $this->responseMessage = "Employee has been deleted successfully";
        $this->success = true;
    }
}