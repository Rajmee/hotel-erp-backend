<?php

namespace  App\Controllers\RoomManagement\RoomType;

use App\Auth\Auth;
use App\Models\RBM\RoomType;
use App\Validation\Validator;
use App\Response\CustomResponse;
use App\Models\Users\ClientUsers;
use Illuminate\Database\Capsule\Manager as DB;
use App\Requests\CustomRequestHandler;

use Respect\Validation\Validator as v;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class RoomTypeController
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
        $this->roomType = new RoomType();
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
            case 'createRoomType':
                $this->createRoomType($request);
                break;          
            case 'allRoomTypes':
                $this->allRoomTypes();
                break;          
            case 'roomTypeInfo':
                $this->roomTypeInfo();
                break;          
            case 'updateRoomType':
                $this->updateRoomType($request);
                break;          
            case 'deleteRoomType':
                $this->deleteRoomType();
                break;          
            case 'parentRoomTypes':
                $this->parentRoomTypes();
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

    public function createRoomType(Request $request){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
         ]);


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $roomType = $this->roomType;
        $roomType->name = $this->params->name;
        if(count($this->params->upload_ids) > 0){
            $roomType->photos = json_encode($this->params->upload_ids);
        }
        $roomType->adults = $this->params->adults;
        $roomType->childrens = $this->params->childrens;
        $roomType->beds = $this->params->beds;
        $roomType->smoking_status = $this->params->smoking_status;
        $roomType->save();

        $room_facilities = $this->params->room_facilityIds;

        $roomType->roomFacilities()->attach($room_facilities);

        $this->responseMessage = "Room Type has been created successfully";
        $this->outputData = $roomType;
        $this->success = true;
    }

    public function updateRoomType(Request $request){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
         ]);


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $roomType = $this->roomType->find($this->params->room_type_id);

        $roomType->name = $this->params->name;
        
        if(count($this->params->upload_ids) > 0){
            $roomType->photos = json_encode($this->params->upload_ids);
        }
        else{
            $roomType->photos = NULL;
        }
        $roomType->adults = $this->params->adults;
        $roomType->childrens = $this->params->childrens;
        $roomType->beds = $this->params->beds;
        $roomType->smoking_status = $this->params->smoking_status;
        $roomType->save();

        $facilities_ids = array();

        foreach($roomType->roomFacilities as $facility){
            $facilities_ids[] = $facility->id;
        }

        $roomType->roomFacilities()->detach($facilities_ids);

        $room_facilities = $this->params->room_facilityIds;

        $roomType->roomFacilities()->attach($room_facilities);

        $this->responseMessage = "Room Type has been updated successfully";
        $this->outputData = $roomType;
        $this->success = true;
    }

    //fetch all Room Types
    public function allRoomTypes(){

        $room_types = $this->roomType->with('roomFacilities')->orderBy('name','ASC')->where('status',1)->get();

        if(!$room_types){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All rooms fetched successfully";
        $this->outputData = $room_types;
        $this->success = true;
    }

    //fetch  Room Types info
    public function roomTypeInfo(){

        $room_types = $this->roomType->with('roomFacilities')->find($this->params->room_type_id);

        if(!$room_types){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $photos = json_decode($room_types->photos);

        if(count($photos)>0){

            $ids = $photos;
            $uploadsData = array();
    
            for($i=0; $i< count($ids); $i++){
                $uploadsData[]= DB::table('uploads')->where('uploads.user_id','=',$this->user->id)->where('uploads.id','=',$ids[$i])->first();
            }
        }

        $this->responseMessage = "All rooms fetched successfully";
        $this->outputData = $room_types;
        $this->outputData['photos'] = $photos;
        $this->outputData['uploadsData'] = $uploadsData;
        $this->success = true;
    }

    //delete  Room Types 
    public function deleteRoomType(){

        $room_type = $this->roomType->find($this->params->room_type_id);

        if(!$room_type){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $facilities_ids = array();

        foreach($room_type->roomFacilities as $facility){
            $facilities_ids[] = $facility->id;
        }

        $room_type->roomFacilities()->detach($facilities_ids);

        $room_type->status = 0;
        $room_type->save();

        $this->responseMessage = "Room Type has been successfully deleted !";
        $this->success = true;
    }

    //Parent room types
    public function parentRoomTypes(){
        $room_types = $this->roomType->where('parent_id',0)->with('childrenRoomTypes')->get();

        $this->responseMessage = "All parent room types fetched successfully";
        $this->outputData = $room_types;
        $this->success = true;
    }
}