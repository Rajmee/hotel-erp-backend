<?php

namespace  App\Controllers\HRM;

use App\Auth\Auth;
use App\Models\HRM\LeaveCategory;
use App\Models\HRM\LeaveApplication;
use App\Models\HRM\Employee;

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class LeaveController
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
        $this->leaveCategories = new LeaveCategory();
        $this->leaveApplications = new LeaveApplication();
        $this->employees = new Employee();
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
            case 'createLeaveCategory':
                $this->createLeaveCategory($request, $response);
                break;
            case 'getAllLeaveCategories':
                $this->getAllLeaveCategories($request, $response);
                break;
            case 'getLeaveCategoryInfo':
                $this->getLeaveCategoryInfo($request, $response);
                break;
            case 'editLeaveCategory':
                $this->editLeaveCategory($request, $response);
                break;
            case 'deleteLeaveCategory':
                $this->deleteLeaveCategory($request, $response);
                break;
            case 'createLeaveApplication':
                $this->createLeaveApplication($request, $response);
                break;
            case 'myLeaveApplication':
                $this->myLeaveApplication($request, $response);
                break;
            case 'createEmployeeLeaves':
                $this->createEmployeeLeaves($request, $response);
                break;
            case 'allLeaveApplication':
                $this->allLeaveApplication($request, $response);
                break;
            case 'getLeaveApplicationInfo':
                $this->getLeaveApplicationInfo($request, $response);
                break;
            case 'leaveApplicationApproval':
                $this->leaveApplicationApproval($request, $response);
                break;
            case 'editEmployeeLeave':
                $this->editEmployeeLeave($request, $response);
                break;
            case 'deleteLeaveApplication':
                $this->deleteLeaveApplication($request, $response);
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


    public function createLeaveCategory(Request $request, Response $response)
    {
        $this->validator->validate($request, [
           "title"=>v::notEmpty(),
        ]);
        v::intVal()->notEmpty()->validate($this->params->status);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        //check duplicate leave
        $current_leave = $this->leaveCategories->where(["title"=>$this->params->title])->where('status',1)->first();
        if ($current_leave) {
            $this->success = false;
            $this->responseMessage = "Leave Category with the same title already exists!";
            return;
        }

        if($this->params->status == 'on'){
            $status = 1;
        }
        else{
            $status = 0;
        }

        $leave = $this->leaveCategories->create([
           "title" => $this->params->title,
           "description" => $this->params->description,
           "created_by" => $this->user->id,
           "status" => $status,
        ]);

        $this->responseMessage = "New Leave Category created successfully";
        $this->outputData = $leave;
        $this->success = true;
    }

    public function getAllLeaveCategories()
    {
        $leaves = $this->leaveCategories->with(['applications','creator','updator'])->where('status',1)->get();

        $this->responseMessage = "Leave Categories list fetched successfully";
        $this->outputData = $leaves;
        $this->success = true;
    }

    public function getLeaveCategoryInfo(Request $request, Response $response)
    {
        if(!isset($this->params->leave_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $leave = $this->leaveCategories->find($this->params->leave_id);

        if($leave->status == 0){
            $this->success = false;
            $this->responseMessage = "Leave Category missing!";
            return;
        }

        if(!$leave){
            $this->success = false;
            $this->responseMessage = "Leave Category not found!";
            return;
        }

        $this->responseMessage = "Leave Category info fetched successfully";
        $this->outputData = $leave;
        $this->success = true;
    }

    public function editLeaveCategory(Request $request, Response $response)
    {
        if(!isset($this->params->leave_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $leave = $this->leaveCategories->where('status',1)->find($this->params->leave_id);

        if(!$leave){
            $this->success = false;
            $this->responseMessage = "Leave Category not found!";
            return;
        }

        $this->validator->validate($request, [
           "title"=>v::notEmpty(),
         ]);
         v::intVal()->notEmpty()->validate($this->params->status);
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }

         //check duplicate Leave
         $current_leave = $this->leaveCategories->where(["title"=>$this->params->title])->where('status',1)->first();
         if ($current_leave && $current_leave->id != $this->params->leave_id) {
             $this->success = false;
             $this->responseMessage = "Leave Category with the same name has already exists!";
             return;
         }

         $editedLeave = $leave->update([
           "title" => $this->params->title,
           "description" => $this->params->description,
           "updated_by" => $this->user->id,
           "status" => $this->params->status,
         ]);
 
         $this->responseMessage = "Leave Updated successfully";
         $this->outputData = $editedLeave;
         $this->success = true;
    }

    public function deleteLeaveCategory(Request $request, Response $response)
    {
        if(!isset($this->params->leave_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $leave = $this->leaveCategories->find($this->params->leave_id);

        if(!$leave){
            $this->success = false;
            $this->responseMessage = "Leave Category not found!";
            return;
        }
        
        $deletedLeave = $leave->update([
            "status" => 0,
         ]);
 
         $this->responseMessage = "Leave Category Deleted successfully";
         $this->outputData = $deletedLeave;
         $this->success = true;
    }

    public function createLeaveApplication(Request $request, Response $response)
    {
        $this->validator->validate($request, [
           "subject"=>v::notEmpty(),
           "leave_category"=>v::notEmpty(),
        ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $employee = $this->employees->where('user_id', $this->user->id)->first();

        $startDate = $this->params->startDate;
        $endDate = $this->params->endDate;

        function getBetweenDates($startDate, $endDate)
        {
            $rangArray = [];
                
            $startDate = strtotime($startDate);
            $endDate = strtotime($endDate);
                
            for ($currentDate = $startDate; $currentDate <= $endDate; 
                                            $currentDate += (86400)) {
                                                    
                $date = date('Y-m-d', $currentDate);
                $rangArray[] = $date;
            }
    
            return $rangArray;
        }
  
        $dates = getBetweenDates($startDate, $endDate);

        if($this->params->duration == 'Single Day' || $this->params->duration == 'Half Day')
        {
            //check duplicate application
            $current_application = $this->leaveApplications->where([['employee_id', $employee->id],['date', $this->params->date]])->where('status',1)->first();
            if ($current_application) {
                $this->success = false;
                $this->responseMessage = "Leave Application with the same Date already exists for this employee!!";
                return;
            }

            if($this->params->duration == 'Half Day'){
                $isHalfday = 1;
            }
            else{
                $isHalfday = 0;
            }
            $application = $this->leaveApplications->create([
                "subject" => $this->params->subject,
                "employee_id" => $employee->id,
                "leave_category_id" => $this->params->leave_category,
                "description" => $this->params->description,
                "created_by" => $this->user->id,
                "date" => $this->params->date,
                "leave_status" => 'Pending',
                "isHalfday" => $isHalfday,
             ]);
        }
        elseif($this->params->duration == 'Multiple Day')
        {
            $date_size = sizeof($dates);

            //check duplicate application
            $current_application = $this->leaveApplications->where('employee_id', $employee->id)->whereIn('date', $dates)->where('status',1)->first();
            if ($current_application) {
                $this->success = false;
                $this->responseMessage = "Leave Application with the same Date Range already exists for this employee!";
                return;
            }

            for($i=0 ; $i < $date_size ; $i++)
            {
                $application = $this->leaveApplications->create([
                    "subject" => $this->params->subject,
                    "employee_id" => $employee->id,
                    "leave_category_id" => $this->params->leave_category,
                    "description" => $this->params->description,
                    "created_by" => $this->user->id,
                    "date" => $dates[$i],
                    "leave_status" => 'Pending',
                    "isHalfday" => 0,
                    ]);
            } 
        }

        $this->responseMessage = "New Leave Category created successfully";
        $this->outputData = $application;
        $this->success = true;
    }

    public function myLeaveApplication()
    {
        $employee = $this->employees->where('user_id', $this->user->id)->first();
        $applications = $this->leaveApplications->with(['leaveCategory','creator'])->where([['employee_id',$employee->id],['status',1]])->get();

        $this->responseMessage = "Leave Applications list fetched successfully";
        $this->outputData = $applications;
        $this->success = true;
    }

    public function createEmployeeLeaves(Request $request, Response $response)
    {
        $this->validator->validate($request, [
           "leave_category"=>v::notEmpty(),
           "employee_id"=>v::notEmpty(),
        ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $startDate = $this->params->startDate;
        $endDate = $this->params->endDate;

        function getBetweenDatesAdmin($startDate, $endDate)
        {
            $rangArray = [];
                
            $startDate = strtotime($startDate);
            $endDate = strtotime($endDate);
                
            for ($currentDate = $startDate; $currentDate <= $endDate; 
                                            $currentDate += (86400)) {
                                                    
                $date = date('Y-m-d', $currentDate);
                $rangArray[] = $date;
            }
    
            return $rangArray;
        }
  
        $dates = getBetweenDatesAdmin($startDate, $endDate);

        if($this->params->duration == 'Single Day' || $this->params->duration == 'Half Day')
        {
            //check duplicate application
            $current_application = $this->leaveApplications->where([['employee_id', $this->params->employee_id],['date', $this->params->date]])->where('status',1)->first();
            if ($current_application) {
                $this->success = false;
                $this->responseMessage = "Leave Application with the same Date already exists for this employee!!";
                return;
            }

            if($this->params->duration == 'Half Day'){
                $isHalfday = 1;
            }
            else{
                $isHalfday = 0;
            }
            $application = $this->leaveApplications->create([
                "subject" => "Employee's Leave",
                "employee_id" => $this->params->employee_id,
                "leave_category_id" => $this->params->leave_category,
                "description" => $this->params->description,
                "created_by" => $this->user->id,
                "date" => $this->params->date,
                "leave_status" => 'Approved',
                "isHalfday" => $isHalfday,
             ]);
        }
        elseif($this->params->duration == 'Multiple Day')
        {
            $date_size = sizeof($dates);

            //check duplicate application
            $current_application = $this->leaveApplications->where('employee_id', $this->params->employee_id)->whereIn('date', $dates)->where('status',1)->first();
            if ($current_application) {
                $this->success = false;
                $this->responseMessage = "Leave Application with the same Date Range already exists for this employee!";
                return;
            }

            for($i=0 ; $i < $date_size ; $i++)
            {
                $application = $this->leaveApplications->create([
                    "subject" => "Employee's Leave",
                    "employee_id" => $this->params->employee_id,
                    "leave_category_id" => $this->params->leave_category,
                    "description" => $this->params->description,
                    "created_by" => $this->user->id,
                    "date" => $dates[$i],
                    "leave_status" => 'Approved',
                    "isHalfday" => 0,
                    ]);
            } 
        }

        $this->responseMessage = "New Employee's Leave created successfully";
        $this->outputData = $application;
        $this->success = true;
    }

    public function allLeaveApplication()
    {
        $applications = $this->leaveApplications->with(['employee','leaveCategory','creator'])->where('status',1)->get();

        $this->responseMessage = "Leave Applications list fetched successfully";
        $this->outputData = $applications;
        $this->success = true;
    }

    public function getLeaveApplicationInfo(Request $request, Response $response)
    {
        if(!isset($this->params->application_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $application = $this->leaveApplications->with('employee','creator','leaveCategory')->find($this->params->application_id);

        if($application->status == 0){
            $this->success = false;
            $this->responseMessage = "Leave Application missing!";
            return;
        }

        if(!$application){
            $this->success = false;
            $this->responseMessage = "Holiday not found!";
            return;
        }

        $this->responseMessage = "Leave Application info fetched successfully";
        $this->outputData = $application;
        $this->success = true;
    }

    public function leaveApplicationApproval(Request $request, Response $response)
    {
        if(!isset($this->params->application_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $application = $this->leaveApplications->find($this->params->application_id);

        if(!$application){
            $this->success = false;
            $this->responseMessage = "Leave Application not found!";
            return;
        }
        
        if($application->leave_status == 'Pending'){
            $approvalApplication = $application->update([
                "leave_status" => $this->params->leave_status,
                "admin_note" => $this->params->admin_note,
             ]);
        }
        else{
            $this->success = false;
            $this->responseMessage = "Can not change leave status Approve or Reject!";
            return;
        }
        
        $this->responseMessage = "Leave Application Updated with approval successfully";
        $this->outputData = $approvalApplication;
        $this->success = true;
    }

    public function editEmployeeLeave(Request $request, Response $response)
    {
        if(!isset($this->params->application_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $application = $this->leaveApplications->where('status',1)->find($this->params->application_id);

        if(!$application){
            $this->success = false;
            $this->responseMessage = "Leave Category not found!";
            return;
        }

        $this->validator->validate($request, [
            "leave_category"=>v::notEmpty(),
            "employee_id"=>v::notEmpty(),
            "date"=>v::notEmpty(),
         ]);
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }

        $mul_date = explode (",", $this->params->date);

        if($this->params->duration == 'Single Day' || $this->params->duration == 'Half Day')
        {
            //check duplicate application
            $current_application = $this->leaveApplications->where([['employee_id', $this->params->employee_id],['date', $this->params->date]])->where('status',1)->first();
            if ($current_application && $current_application->id != $this->params->application_id) {
                $this->success = false;
                $this->responseMessage = "Leave Application with the same Date already exists for this employee!!";
                return;
            }

            if($this->params->duration == 'Half Day'){
                $isHalfday = 1;
            }
            else{
                $isHalfday = 0;
            }
            $editedApplication = $application->update([
                "subject" => "Employee's Leave",
                "employee_id" => $this->params->employee_id,
                "leave_category_id" => $this->params->leave_category,
                "description" => $this->params->description,
                "updated_by" => $this->user->id,
                "date" => $this->params->date,
                "leave_status" => 'Approved',
                "isHalfday" => $isHalfday,
             ]);
        }
        elseif($this->params->duration == 'Multiple Day')
        {
            $date_size = sizeof($mul_date);

            //check duplicate application
            $current_application = $this->leaveApplications->where('employee_id', $this->params->employee_id)->whereIn('date', $mul_date)->where('status',1)->first();
            if ($current_application && $current_application->id != $this->params->application_id) {
                $this->success = false;
                $this->responseMessage = "Leave Application with the same Date Range already exists for this employee!";
                return;
            }

            for($i=0 ; $i < $date_size ; $i++)
            {
                $editedApplication = $application->update([
                    "subject" => "Employee's Leave",
                    "employee_id" => $this->params->employee_id,
                    "leave_category_id" => $this->params->leave_category,
                    "description" => $this->params->description,
                    "updated_by" => $this->user->id,
                    "date" => $mul_date[$i],
                    "leave_status" => 'Approved',
                    "isHalfday" => 0,
                    ]);
            } 
        }
 
         $this->responseMessage = "Employee's Leave Updated successfully";
         $this->outputData = $editedApplication;
         $this->success = true;
    }

    public function deleteLeaveApplication(Request $request, Response $response)
    {
        if(!isset($this->params->application_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $application = $this->leaveApplications->find($this->params->application_id);

        if(!$application){
            $this->success = false;
            $this->responseMessage = "Leave Application not found!";
            return;
        }
        
        $deletedHoliday = $application->update([
            "status" => 0,
         ]);
 
         $this->responseMessage = "Leave Application Deleted successfully";
         $this->outputData = $deletedHoliday;
         $this->success = true;
    }
    
}
