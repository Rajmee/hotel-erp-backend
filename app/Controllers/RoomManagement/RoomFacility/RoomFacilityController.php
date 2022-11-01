<?php

namespace  App\Controllers\RoomManagement\RoomFacility;

use App\Auth\Auth;
use App\Models\RBM\RoomFacility;
use App\Validation\Validator;
use App\Response\CustomResponse;
use App\Models\Users\ClientUsers;
use Illuminate\Database\Capsule\Manager as DB;
use App\Requests\CustomRequestHandler;

use Respect\Validation\Validator as v;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class RoomFacilityController
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
        $this->roomFacility = new RoomFacility();
        $this->user = new ClientUsers();
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
         
            case 'allRoomFacilities':
                $this->allRoomFacilities();
                break;          
            case 'createRoomFacility':
                $this->createRoomFacility($request);
                break;          
            case 'roomFacilityInfo':
                $this->roomFacilityInfo();
                break;          
            case 'updateRoomFacility':
                $this->updateRoomFacility($request);
                break;          
            case 'deleteRoomFacility':
                $this->deleteRoomFacility();
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

    public function createRoomFacility(Request $request){
        $this->validator->validate($request, [
            "facility"=>v::notEmpty(),
         ]);


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $roomFacility = $this->roomFacility;
        $roomFacility->facility = $this->params->facility;
        $roomFacility->save();

        $this->responseMessage = "Room Type has been created successfully";
        $this->outputData = $roomFacility;
        $this->success = true;
    }

    //fetch  Room Types info
    public function roomFacilityInfo(){

        $room_facility = $this->roomFacility->with('roomTypes')->find($this->params->room_facility_id);

        if(!$room_facility){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "Room facility info fetched successfully";
        $this->outputData = $room_facility;
        $this->success = true;
    }

    public function updateRoomFacility(Request $request){

        $this->validator->validate($request, [
            "facility"=>v::notEmpty(),
         ]);


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $roomFacility = $this->roomFacility->find($this->params->room_facility_id);

        $roomFacility->facility = $this->params->facility;
        $roomFacility->save();


        $this->responseMessage = "Room Facility has been updated successfully";
        $this->outputData = $roomFacility;
        $this->success = true;
    }

    //fetch all Room Types
    public function allRoomFacilities(){

        $room_facilities = $this->roomFacility->with('roomTypes')->where('status',1)->get();

        if(!$room_facilities){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All room facilities fetched successfully";
        $this->outputData = $room_facilities;
        $this->success = true;
    }

    //delete  Room Types 
    public function deleteRoomFacility(){

        $roomFacility = $this->roomFacility->find($this->params->room_facility_id);

        if(!$roomFacility){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $room_types_ids = array();

        foreach($roomFacility->roomTypes as $roomType){
            $room_types_ids[] = $roomType->id;
        }

        $roomFacility->roomTypes()->detach($room_types_ids);

        $roomFacility->status = 0;
        $roomFacility->save();

        $this->responseMessage = "Room facility has been successfully deleted !";
        $this->success = true;
    }
}