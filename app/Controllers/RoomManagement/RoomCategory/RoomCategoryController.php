<?php

namespace  App\Controllers\RoomManagement\RoomCategory;

use App\Auth\Auth;
use App\Models\RBM\RoomCategory;
use App\Validation\Validator;
use App\Response\CustomResponse;
use App\Models\Users\ClientUsers;
use App\Requests\CustomRequestHandler;

use Respect\Validation\Validator as v;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class RoomCategoryController
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
        $this->roomCategory = new RoomCategory();
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
         
            case 'allRoomCategories':
                $this->allRoomCategories();
                break;          
            case 'createRoomCategory':
                $this->createRoomCategory($request);
                break;          
            case 'roomCategoryInfo':
                $this->roomCategoryInfo();
                break;          
            case 'updateRoomCategory':
                $this->updateRoomCategory($request);
                break;          
            case 'deleteRoomCategory':
                $this->deleteRoomCategory();
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

    public function createRoomCategory(Request $request){
        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
         ]);


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $roomCategory = $this->roomCategory->where(['name'=>$this->params->name])->where(['status'=>1])->first();

        if($roomCategory){
            $this->success = false;
            $this->responseMessage = "Room category has been already exists!";
            return;
        }

        $roomCategory = $this->roomCategory;
        $roomCategory->name = $this->params->name;
        $roomCategory->description = $this->params->description;
        $roomCategory->save();

        $this->responseMessage = "Room category has been created successfully";
        $this->outputData = $roomCategory;
        $this->success = true;
    }

    //fetch  Room Types info
    public function roomCategoryInfo(){

        $room_category = $this->roomCategory->find($this->params->room_category_id);

        if(!$room_category){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "Room facility info fetched successfully";
        $this->outputData = $room_category;
        $this->success = true;
    }

    public function updateRoomCategory(Request $request){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
         ]);


        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }



        $roomCategory = $this->roomCategory->where(['name'=>$this->params->name])->where(['status'=>1])->first();

        if($roomCategory &&  $roomCategory->id !== $this->params->room_category_id){
            $this->success = false;
            $this->responseMessage = "Room category has been already exists!";
            return;
        }

        $roomCategory = $this->roomCategory->find($this->params->room_category_id);

        if(!$roomCategory){
            $this->success = false;
            $this->responseMessage = "Room Category not found!";
            return; 
        }

        $roomCategory->name = $this->params->name;
        $roomCategory->description = $this->params->description;
        $roomCategory->save();


        $this->responseMessage = "Room category has been updated successfully";
        $this->outputData = $roomCategory;
        $this->success = true;
    }

    //fetch all Room Types
    public function allRoomCategories(){

        $room_categories = $this->roomCategory->where('status',1)->get();

        if(!$room_categories){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All room categories fetched successfully";
        $this->outputData = $room_categories;
        $this->success = true;
    }

    //delete  Room Types 
    public function deleteRoomCategory(){

        $roomCategory = $this->roomCategory->find($this->params->room_category_id);

        if(!$roomCategory){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        DB::table('tower_floor_rooms')
            ->where('tower_floor_rooms.room_category_id','=',$this->params->room_category_id)
            ->delete();

        // $roomCategory->rooms()->delete();

        $roomCategory->status = 0;
        $roomCategory->save();

        $this->responseMessage = "Room category has been successfully deleted !";
        $this->success = true;
    }
}