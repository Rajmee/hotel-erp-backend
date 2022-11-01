<?php

namespace  App\Controllers\RoomManagement\Tower;

use App\Auth\Auth;
use Carbon\Carbon;
use App\Models\RBM\Tower;
use App\Validation\Validator;
use App\Models\RBM\TowerFloor;
use App\Response\CustomResponse;
use App\Models\RBM\TowerFloorRoom;

use App\Requests\CustomRequestHandler;
use Respect\Validation\Validator as v;
use App\Models\Permission\RolePermission;
use App\Models\Permission\AccessPermission;
use App\Models\RBM\RoomFacility;
use App\Models\RBM\RoomType;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class TowerController
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
        $this->tower = new Tower();
        $this->room = new TowerFloorRoom();
        $this->floor = new TowerFloor();
        $this->roomType = new RoomType();
        $this->roomFacility = new RoomFacility();
        $this->validator = new Validator();
        $this->permission = new AccessPermission();
        $this->rolepermission = new RolePermission();

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
            case 'createTower':
                $this->createTower($request, $response);
                break;
            case 'getAllTowers':
                $this->getAllTowers();
                break;
            case 'getTowers':
                $this->getTowers();
                break;
            case 'getTowerInfo':
                $this->getTowerInfo();
                break;
            case 'updateTower':
                $this->updateTower($request, $response);
                break;          
            case 'deleteTower':
                $this->deleteTower();
                break;          
            case 'createFloor':
                $this->createFloor($request, $response);
                break;      
            case 'updateFloor':
                $this->updateFloor($request, $response);
                break;          
            case 'getTowerFloors':
                $this->getTowerFloors();
                break;          
            case 'getTowerFloorInfo':
                $this->getTowerFloorInfo();
                break;          
            case 'getRoomInfo':
                $this->getRoomInfo();
                break;          
            case 'updateRoom':
                $this->updateRoom($request, $response);
                break;          
            case 'getAllRooms':
                $this->getAllRooms();
                break;          
            case 'getAllRoomTypes':
                $this->getAllRoomTypes();
                break;          
            case 'getAllTowersRooms':
                $this->getAllTowersRooms();
                break;          
            case 'updateRoomStatus':
                $this->updateRoomStatus();
                break;  
                //All rooms by room type or room category        
            case 'roomsByTypesOrCategory':
                $this->roomsByTypesOrCategory();
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

    public function getAllTowers(){
        //$towers = $this->tower->where('tower_status',1)->with('creator')->orderBy('id','desc')->get();
        $towers = DB::table('towers')
                    ->join('org_users','towers.created_by','=','org_users.id')
                    ->select('towers.*','org_users.name as creator')
                    ->where('towers.tower_status','=',1)
                    ->orderBy('towers.id','desc')
                    ->get();

        if(!$towers){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All tower fetched successfully";
        $this->outputData = $towers;
        $this->success = true;

    }
    public function getTowers(){
        //fetch tower id as key groupBy id if has rooms
        $towers_id = DB::table('tower_floor_rooms')
            ->get()
            ->groupBy('tower_id');

        //store TowerIds as an array
        $towerIds = [];
        foreach($towers_id as $key => $v){
            $towerIds[] = $key;
        }

        //select towers if has rooms
        $towers = DB::table('towers')
                    ->select('towers.id','towers.name')
                    ->whereIn('id',$towerIds)
                    ->get();

        if(!$towers){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All tower fetched successfully";
        $this->outputData= $towers;
        //$this->outputData['towerIds'] = $towerIds;
        $this->success = true;

    }

    public function createTower(Request $request, Response $response){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "description"=>v::notEmpty(),
         ]);

        //status validation
        v::intVal()->notEmpty()->validate($this->params->tower_status);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $tower = $this->tower->where(['name'=>$this->params->name])->where(['tower_status'=>1])->first();

        if($tower){
            $this->success = false;
            $this->responseMessage = "Tower has been already exists!";
            return;
        }


        $tower = $this->tower;
        $tower->name = $this->params->name;
        $tower->description = $this->params->description;
        $tower->tower_status = $this->params->tower_status;
        $tower->created_by = $this->user->id;
        $tower->save();



        $this->responseMessage = "Tower has been created successfully";
        $this->outputData = $tower;
        $this->success = true;
    }

    public function getTowerInfo(){

        if(!isset($this->params->tower_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        $tower = $this->tower->with('creator')->find($this->params->tower_id);
        //$tower = $towers->floors->where(['status'=>1])->get();

        if(!$tower){
            $this->success = false;
            $this->responseMessage = "Tower not found!";
            return;
        }

        $this->responseMessage = "Tower fetched successfully";
        $this->outputData = $tower;
        $this->success = true;

    }

    public function updateTower(Request $request, Response $response){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "description"=>v::notEmpty(),
         ]);

        //status validation
        v::intVal()->notEmpty()->validate($this->params->tower_status);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        $tower = $this->tower->where(['name'=>$this->params->name])->where(['tower_status'=>1])->first();

        if($tower && $tower->id != $this->params->tower_id){
            $this->success = false;
            $this->responseMessage = "Tower has been already exists!";
            return;
        }

        $tower = $this->tower->findOrFail($this->params->tower_id);

        if(!$tower){
            $this->success = false;
            $this->responseMessage = "Tower not found!";
            return;
        }

        $tower->name = $this->params->name;
        $tower->description = $this->params->description;
        $tower->tower_status = $this->params->tower_status;
        $tower->created_by = $this->user->id;
        $tower->save();



        $this->responseMessage = "Tower has been updated successfully";
        $this->outputData = $tower;
        $this->success = true;
    }

    public function deleteTower(){
        if(!isset($this->params->tower_id)){
            $this->success = false;
            $this->responseMessage = "Tower missing";
            return;
        }

        $tower = $this->tower->findOrFail($this->params->tower_id);

        if(!$tower){
            $this->success = false;
            $this->responseMessage = "Tower not found!";
            return; 
        }

        $tower->tower_status = 0;
        $tower->save();

        $this->success = true;
        $this->responseMessage = "Successfully deleted !";
        return;

    }

    //Create Floor
    public function createFloor(Request $request, Response $response){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "tower_id"=>v::notEmpty(),
            "total_rooms"=>v::notEmpty(),
            "room_length"=>v::notEmpty(),
            "room_prefix"=>v::notEmpty(),
         ]);

        //status validation
        v::intVal()->notEmpty()->validate($this->params->status);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        //Floor Name Validation

        if($this->floor->get()->count() > 0){
            $floor = $this->floor->where(['tower_id'=>$this->params->tower_id])->where(['name'=>$this->params->name])->where(['status'=>1])->first();

            if($floor){
                $this->success = false;
                $this->responseMessage = "floor name has been already exists!";
                return;
            }
        }

        //Room prefix validation

        $floor_rooms = DB::table('tower_floors')
                ->select('tower_floors.room_prefix')
                ->where('tower_floors.tower_id','=',$this->params->tower_id)
                ->where('tower_floors.room_prefix','=',$this->params->room_prefix)
                ->take(1)
                ->get();

        if($floor_rooms[0] !== null){
                $this->success = false;
                $this->outputData = $floor_rooms[0];
                $this->responseMessage = "Same room prefix has been already exists!";
                return;
        }


        $floor = $this->floor;
        $floor->tower_id = $this->params->tower_id;
        $floor->name = $this->params->name;
        $floor->total_rooms = $this->params->total_rooms;
        $floor->room_prefix = $this->params->room_prefix;
        $floor->status = $this->params->status;
        $floor->created_by = $this->user->id;
        if($floor->save()){
            if($floor->total_rooms > 0){
                $array = array();
                $now = Carbon::now();

                for ($i=1; $i<=$floor->total_rooms; $i++){
                    $array[] = array(
                        'room_no' => $this->params->room_prefix.str_pad($i, $this->params->room_length, '0', STR_PAD_LEFT),
                        'tower_id' => $this->params->tower_id,
                        'tower_floor_id' => $floor->id,
                        'room_status' => "available",
                        'created_at' => $now->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s')
                    );
                }

                DB::table('tower_floor_rooms')->insert($array);
            }

        }

        $this->responseMessage = "Floor has been created successfully";
        $this->outputData = $floor;
        $this->success = true;
    }

    //update Floor
    public function updateFloor(Request $request, Response $response){

        $this->validator->validate($request, [
            "name"=>v::notEmpty(),
            "total_rooms"=>v::notEmpty(),
            "room_length"=>v::notEmpty(),
            "room_prefix"=>v::notEmpty(),
         ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        if(!isset($this->params->floor_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        //check duplicate Floor Name
        $floor = $this->floor->where(['tower_id'=>$this->params->tower_id])->where(['name'=>$this->params->name])->where(['status'=>1])->first();
        if ($floor && $floor->id != $this->params->floor_id) {
            $this->success = false;
            $this->responseMessage = "Floor name has already exists!";
            return;
        }

        $floor = $this->floor->find($this->params->floor_id);

        if(!$floor){
            $this->success = false;
            $this->responseMessage = "Floor not found!";
            return;
        }

        //Room prefix validation

        $floor_rooms = DB::table('tower_floors')
                ->where('tower_floors.tower_id','=',$this->params->tower_id)
                ->where('tower_floors.id','!=',$this->params->floor_id)
                ->where('tower_floors.room_prefix','=',$this->params->room_prefix)
                ->take(1)
                ->get();

        if($floor_rooms[0] !== null){
                $this->success = false;
                $this->responseMessage = "Same room prefix has been already exists!";
                return; 
        }

        $floor->name = $this->params->name;
        $floor->total_rooms = $this->params->total_rooms;
        $floor->room_prefix = $this->params->room_prefix;
        $floor->updated_by = $this->user->id;
        if($floor->save()){
            if($floor->total_rooms > 0){

                DB::table('tower_floor_rooms')
                    ->where('tower_floor_rooms.tower_id','=',$this->params->tower_id)
                    ->where('tower_floor_rooms.tower_floor_id','=',$this->params->floor_id)
                    ->delete();

                $array = array();
                $now = Carbon::now();

                for ($i=1; $i<=$floor->total_rooms; $i++){
                    $array[] = array(
                        'room_no' => $this->params->room_prefix.str_pad($i, $this->params->room_length, '0', STR_PAD_LEFT),
                        'tower_id' => $this->params->tower_id,
                        'tower_floor_id' => $floor->id,
                        'room_status' => "available",
                        'created_at' => $now->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s')
                    );
                }

                DB::table('tower_floor_rooms')->insert($array);
            }

        }

        $this->responseMessage = "Floor has been updated successfully";
        $this->outputData = $floor;
        $this->success = true;
    }

    //get All Floors
    public function getTowerFloors(){

        //$tower_floors = $this->tower->find($this->params->tower_id)->floors()->with('rooms')->get();
        $tower_floors = $this->tower->find($this->params->tower_id)->floors()->get();

        if(!$tower_floors){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All floors fetched successfully";
        $this->outputData = $tower_floors;
        $this->success = true;

    }

    //Floor info fetching
    public function getTowerFloorInfo(){

        if(!isset($this->params->tower_id)){
            $this->success = false;
            $this->responseMessage = "Tower missing";
            return;
        }

        if(!isset($this->params->floor_id)){
            $this->success = false;
            $this->responseMessage = "Floor missing";
            return;
        }

        $floor = $this->floor->where(['tower_id'=>$this->params->tower_id])->where(['id'=>$this->params->floor_id])->first();

        if(!$floor){
            $this->success = false;
            $this->responseMessage = "Floor not found!";
            return;
        }

        $rooms = DB::table('tower_floor_rooms')
            ->select('tower_floor_rooms.*','room_types.name as room_type_name')
            ->join('room_types','tower_floor_rooms.room_type_id','=','room_types.id')
            ->where('tower_floor_rooms.tower_id','=',$this->params->tower_id)
            ->where('tower_floor_rooms.tower_floor_id','=',$this->params->floor_id)
            ->get();

        $this->responseMessage = "Floor info fetched successfully";
        $this->outputData = $floor;
        $this->outputData['rooms'] = $rooms;
        $this->success = true;

    }

    //Floor info fetching
    public function getRoomInfo(){

        if(!isset($this->params->room_id)){
            $this->success = false;
            $this->responseMessage = "Room missing";
            return;
        }


        $room = $this->room->where(['id'=>$this->params->room_id])->with(['roomType','roomCategory'])->first();

        if(!$room){
            $this->success = false;
            $this->responseMessage = "Room not found!";
            return;
        }

        $room_type = $this->roomType->where(['id'=>$room->room_type_id])->with('roomFacilities')->first();


        $this->responseMessage = "Room info fetched successfully";
        $this->outputData = $room;
        //$this->outputData['type_facility'] = $room_type;
        $this->outputData['type'] = $room_type;
        $this->success = true;

    }

    public function updateRoom(Request $request, Response $response){
        $this->validator->validate($request, [
            "room_type_id"=>v::notEmpty()
         ]);

         if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }

        if(!isset($this->params->room_id)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        $room = $this->room->find($this->params->room_id);

        if(!$room){
            $this->success = false;
            $this->responseMessage = "Room not found!";
            return;
        }

        $room->room_type_id = $this->params->room_type_id;
        $room->room_category_id = $this->params->room_category_id;
        $room->room_description = $this->params->room_description;
        $room->room_status = $this->params->room_status;
        $room->save();

        $this->responseMessage = "Room has been updated successfully";
        $this->outputData = $room;
        $this->success = true;

    }

    //fetch all rooms
    public function getAllRooms(){

        $rooms = DB::table('tower_floor_rooms')
                ->select(
                    'tower_floor_rooms.id',
                    'tower_floor_rooms.room_no',
                    'tower_floor_rooms.room_type_id',
                    'tower_floor_rooms.room_description',
                    'tower_floor_rooms.room_status',
                    )
                //->where('tower_floor_rooms.tower_id','=',1)
                ->get();


        //$rooms = $this->room->where('room_status',1)->get();

        if(!$rooms){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All rooms fetched successfully";
        $this->outputData = $rooms;
        $this->success = true;
    }

    //fetch all Room Types
    public function getAllRoomTypes(){

        $room_types = DB::table('room_types')
                ->orderBy('room_types.name','asc')
                ->get();


        //$rooms = $this->room->where('room_status',1)->get();

        if(!$room_types){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All rooms fetched successfully";
        $this->outputData = $room_types;
        $this->success = true;
    }

    //Get All Rooms Under Room Type Under Towers
    public function getAllTowersRooms(){

        //$room_types = $this->roomType->with('rooms')->get();

        if(count($this->params->towerIds) > 0 && count($this->params->room_status)>0){
            $room_types = DB::table('room_types')
                ->select('name','tower_floor_rooms.room_no','tower_floor_rooms.id','tower_floor_rooms.room_status','tower_floor_rooms.tower_id')
                ->join('tower_floor_rooms','tower_floor_rooms.room_type_id','=','room_types.id')
                ->whereIn('tower_floor_rooms.tower_id',$this->params->towerIds)
                ->whereIn('tower_floor_rooms.room_status',$this->params->room_status)
                ->get()
                ->groupBy('name');
        }
        elseif(count($this->params->towerIds) > 0){

            $room_types = DB::table('room_types')
                ->select('name','tower_floor_rooms.room_no','tower_floor_rooms.id','tower_floor_rooms.room_status','tower_floor_rooms.tower_id')
                ->join('tower_floor_rooms','tower_floor_rooms.room_type_id','=','room_types.id')
                ->whereIn('tower_floor_rooms.tower_id',$this->params->towerIds)
                ->get()
                ->groupBy('name');
        }
        elseif(count($this->params->room_status)>0){
            $room_types = DB::table('room_types')
                ->select('name','tower_floor_rooms.room_no','tower_floor_rooms.id','tower_floor_rooms.room_status','tower_floor_rooms.tower_id')
                ->join('tower_floor_rooms','tower_floor_rooms.room_type_id','=','room_types.id')
                ->whereIn('tower_floor_rooms.room_status',$this->params->room_status)
                ->get()
                ->groupBy('name');
        }
        else{
            $room_types = DB::table('room_types')
            ->select('name','tower_floor_rooms.room_no','tower_floor_rooms.id','tower_floor_rooms.room_status','tower_floor_rooms.tower_id')
            ->join('tower_floor_rooms','tower_floor_rooms.room_type_id','=','room_types.id')
            ->get()
            ->groupBy('name');

        }




        if(!$room_types){
            $this->success = false;
            $this->responseMessage = "No data found!";
            return;
        }

        $this->responseMessage = "All rooms by room type has been fetched successfully";
        $this->outputData = $room_types;
        $this->success = true;
    }

    public function updateRoomStatus(){
        if(count($this->params->roomIds) > 0){
            DB::table('tower_floor_rooms')->whereIn('id',$this->params->roomIds)->update(['room_status'=>$this->params->roomStatus]);
        }
        else{
            $this->success = false;
            $this->responseMessage = "Not selected rooms !";
            return;
        }

        $this->responseMessage = "Room status updated successfully";
        $this->success = true;
    }

    public function roomsByTypesOrCategory(){
        if($this->params->room_type_id && $this->params->room_category_id){
            $rooms = DB::table('tower_floor_rooms')
                ->select('tower_floor_rooms.id','tower_floor_rooms.room_no','tower_floor_rooms.room_status','tower_floor_rooms.room_type_id','tower_floor_rooms.room_category_id')
                ->where('tower_floor_rooms.room_type_id',$this->params->room_type_id)
                ->where('tower_floor_rooms.room_category_id',$this->params->room_category_id)
                ->where('tower_floor_rooms.room_status','=','available')
                ->get();
        }
        elseif($this->params->room_type_id){

            $rooms = DB::table('tower_floor_rooms')
                ->select('tower_floor_rooms.id','tower_floor_rooms.room_no','tower_floor_rooms.room_status','tower_floor_rooms.room_type_id','tower_floor_rooms.room_category_id')
                ->where('tower_floor_rooms.room_type_id',$this->params->room_type_id)
                ->where('tower_floor_rooms.room_status','=','available')
                ->get();
        }
        elseif($this->params->room_category_id){
            $rooms = DB::table('tower_floor_rooms')
                ->select('tower_floor_rooms.id','tower_floor_rooms.room_no','tower_floor_rooms.room_status','tower_floor_rooms.room_type_id','tower_floor_rooms.room_category_id')
                ->where('tower_floor_rooms.room_category_id',$this->params->room_category_id)
                ->where('tower_floor_rooms.room_status','=','available')
                ->get();
        }
        else{
            $rooms = DB::table('tower_floor_rooms')
                ->select('tower_floor_rooms.id','tower_floor_rooms.room_no','tower_floor_rooms.room_status','tower_floor_rooms.room_type_id','tower_floor_rooms.room_category_id')
                ->where('tower_floor_rooms.room_status','=','available')
                ->get();

        }

        $this->responseMessage = "All rooms has been fetched successfully";
        $this->outputData = $rooms;
        $this->success = true;
    }
}


