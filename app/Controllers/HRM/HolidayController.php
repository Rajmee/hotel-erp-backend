<?php

namespace  App\Controllers\HRM;

use App\Auth\Auth;
use App\Models\HRM\Holiday;

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class HolidayController
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
        $this->holidays = new Holiday();
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
                $this->createHoliday($request, $response);
                break;
            case 'getHolidays':
                $this->getHolidays();
                break;
            case 'getHolidayInfo':
                $this->getHolidayInfo($request, $response);
                break;
            case 'editHoliday':
                $this->editHoliday($request, $response);
                break;
            case 'deleteHoliday':
                $this->deleteHoliday($request, $response);
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


    public function createHoliday(Request $request, Response $response)
    {
        $this->validator->validate($request, [
           "title"=>v::notEmpty(),
           "type"=>v::notEmpty(),
           "date"=>v::notEmpty(),
           "year"=>v::notEmpty(),
           "description"=>v::notEmpty(),
        //    "status"=>v::notEmpty()->intVal()
        ]);
        v::intVal()->notEmpty()->validate($this->params->status);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        //check duplicate holiday
        $current_holiday = $this->holidays->where(["year" => $this->params->year])->where(["date"=>$this->params->date])->first();
        if ($current_holiday) {
            $this->success = false;
            $this->responseMessage = "Holiday with the same Date & Year already exists!";
            return;
        }

        if($this->params->status == 'on'){
            $status = 1;
        }
        else{
            $status = 0;
        }

        $holiday = $this->holidays->create([
           "title" => $this->params->title,
           "type" => $this->params->type,
           "date" => $this->params->date,
           "year" => $this->params->year,
           "description" => $this->params->description,
           "created_by" => $this->user->id,
           "status" => $status,
        ]);

        $this->responseMessage = "New holiday created successfully";
        $this->outputData = $holiday;
        $this->success = true;
    }

    public function getHolidays()
    {
        if(!$this->params->year)
        {
            $today = date("Y");
        }
        else{
            $today = $this->params->year;
        }
        
        $holidays = $this->holidays->with(['creator','updator'])->where('status',1)->where('year', $today)->get();

        $this->responseMessage = "Holidays list fetched successfully";
        $this->outputData = $holidays;
        $this->success = true;
    }

    public function getHolidayInfo(Request $request, Response $response)
    {
        if(!isset($this->params->holiday_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $holiday = $this->holidays->find($this->params->holiday_id);

        if($holiday->status == 0){
            $this->success = false;
            $this->responseMessage = "Holiday missing!";
            return;
        }

        if(!$holiday){
            $this->success = false;
            $this->responseMessage = "Holiday not found!";
            return;
        }

        $this->responseMessage = "Holiday info fetched successfully";
        $this->outputData = $holiday;
        $this->success = true;
    }

    public function editHoliday(Request $request, Response $response)
    {
        if(!isset($this->params->holiday_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $holiday = $this->holidays->find($this->params->holiday_id);

        if(!$holiday){
            $this->success = false;
            $this->responseMessage = "Holiday not found!";
            return;
        }

        $this->validator->validate($request, [
           "title"=>v::notEmpty(),
           "type"=>v::notEmpty(),
           "date"=>v::notEmpty(),
           "year"=>v::notEmpty(),
           "description"=>v::notEmpty(),
         ]);
         v::intVal()->notEmpty()->validate($this->params->status);
 
         if ($this->validator->failed()) {
             $this->success = false;
             $this->responseMessage = $this->validator->errors;
             return;
         }

         //check duplicate holiday
        $current_holiday = $this->holidays->where(["year" => $this->params->year])->where(["date"=>$this->params->date])->first();
        if ($current_holiday && $current_holiday->id != $this->params->holiday_id) {
            $this->success = false;
            $this->responseMessage = "Holiday with the same Date & Year already exists!";
            return;
        }

         $editedHoliday = $holiday->update([
           "title" => $this->params->title,
           "type" => $this->params->type,
           "date" => $this->params->date,
           "year" => $this->params->year,
           "description" => $this->params->description,
           "updated_by" => $this->user->id,
           "status" => $this->params->status,
         ]);
 
         $this->responseMessage = "Holiday Updated successfully";
         $this->outputData = $editedHoliday;
         $this->success = true;
    }

    public function deleteHoliday(Request $request, Response $response)
    {
        if(!isset($this->params->holiday_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        $holiday = $this->holidays->find($this->params->holiday_id);

        if(!$holiday){
            $this->success = false;
            $this->responseMessage = "Holiday not found!";
            return;
        }
        
        $deletedHoliday = $holiday->update([
            "status" => 0,
         ]);
 
         $this->responseMessage = "Holiday Deleted successfully";
         $this->outputData = $deletedHoliday;
         $this->success = true;
    }
}
