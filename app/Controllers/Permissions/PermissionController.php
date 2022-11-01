<?php

namespace  App\Controllers\Permissions;

use App\Auth\Auth;
use App\Models\Permission\AccessPermission;
use App\Models\Permission\AccessRole;
use App\Models\Permission\RolePermission;

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;

use App\Validation\Validator;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class PermissionController
{

    protected $customResponse;

    protected $validator;

    protected $params;
    protected $responseMessage;
    protected $outputData;
    protected $success;
    protected $user;

    /** Permission ini */
    public $RolePermission;
    private $AccessPermissions;
    private $AccessRole;
    private $permissionList;


    public function __construct()
    {
        $this->customResponse = new CustomResponse();
        //Model Instance
        $this->AccessPermissions = new AccessPermission();
        $this->AccessRole = new AccessRole();
        $this->RolePermission = new RolePermission();
        /*Model Instance END */
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
            case 'getAllPermissions':
                $this->getAllPermissions();
                break;
            case 'getAllRoles':
                $this->getAllRoles();
                break;
            case 'createRolePermission':
                $this->createNewRolePermission($request);
                break;
            case 'createAccessRole':
                $this->createAccessRole($request);
                break;
            case 'createAccessPermission':
                $this->createAccessPermission($request);
                break;
            case 'getAllRolePermission':
                $this->getAllRolePermission();
                break;
            case 'getRoleById':
                $this->getRoleById($request);
                break;
            case 'getPermissionByRoleID':
                $this->getPermissionByRoleId();
                break;
            case 'updateRolePermission':
                $this->updatePermissionRole();
                break;
            case 'deletePermissionByRoleID':
                $this->deletePermissionByRoleID();
                break;
            case 'getPermissionSet':
                $this->getPermissionSet();
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
    /**Wiping data by Role ID */
    public function deletePermissionByRoleID(){

        $accessPermission = $this->RolePermission
        ->where(["role_id"=>$this->params->role_id])
        ->delete();

        $this->responseMessage = "Role-Permission Data Wiped successfully!!";
        $this->outputData = $accessPermission;
        $this->success = true;
    }

    /**Updating Role-Permission */
    public function updatePermissionRole(){
        /** Wiping out existing all params->role_id */
        $accessPermission = $this->RolePermission
        ->where(["role_id"=>$this->params->role_id])
        ->delete();
        /**Creating New Data For params->role_id */

        $this->permissionList = array($this->params->permissions_id);

        $array = array();
        foreach ($this->permissionList[0] as $key => $permission) {
            array_push($array, $permission);
            /**Database Insertion part */
            $this->RolePermission->insert([
            "role_id" => $this->params->role_id,
            // "access_role" => $this->params->data['access_role']['id'],
            "permission_id" => $permission,
            "status" => 1
         ]);

         /**Database Insertion part End*/
        }
        $this->responseMessage = "Role-Permission Data updated successfully";
        $this->outputData = $accessPermission;
        $this->success = true;

        /*
        $accessPermission = $this->RolePermission
        ->where(["role_id"=>$this->params->role_id])
        ->update([
            "permission_id" => $this->params->permission_id,
         ]);
        $this->responseMessage = "Role-Permission Data updated successfully";
        $this->outputData = $accessPermission;
        $this->success = true;
        */
    }
/**Updating Role-Permission END */


    /** Get Role By ID */
    public function getRoleById(){
        $accessRoles = $this->AccessRole->selectRaw('title as value')->where(["id"=>$this->params->role_id['id']])->first();
        if(!$accessRoles){
            $this->success = false;
            $this->responseMessage = "Role Data not found!";
            return;
        }

        // $this->validator->validate($request, [
        //     "role_id"=>v::notEmpty(),
        // ]);

        $this->responseMessage = "All Role-Permission Data fetched successfully";
        $this->outputData = $accessRoles;
        $this->success = true;
    }

    /**Get Permission By ID */
        /** Simple Return type */
    public function getPermissionSet(){
        $accessRolePermission = $this->RolePermission
        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
        ->where(["role_permission.role_id"=>$this->params->role_id])
        ->select('permissions.title', 'permissions.access_code', 'permissions.module')
        // ->toSql();
        ->get();
        if(!$accessRolePermission){
            $this->success = false;
            $this->responseMessage = "No Permission Set Data found!";
            return;
        }
        $this->responseMessage = "All Permission Set Data fetched successfully";
        $this->outputData = $accessRolePermission;
        $this->success = true;
    }
    public function getPermissionByRoleIdReturn($role_id) {
        $accessRolePermission = $this->RolePermission
        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
        ->where(["role_permission.role_id"=>$role_id])
        ->select('permissions.title', 'permissions.access_code', 'permissions.module')
        // ->toSql();
        ->get();

        
        // $accessRolePermission = $this->RolePermission
        // // ->join('permissions','permissions.id','=','role_permission.permission_id')
        // ->where(["role_id"=>$role_id])
        // ->get();
        // ->toSql();
        

        return $accessRolePermission;
    }
    public function getPermissionByRoleId() {
        $accessRolePermission = $this->RolePermission
        ->where(["role_id"=>$this->params->role_id])
        ->get();
        // $accessRolePermission = $this->RolePermission->all()->groupBy('role_id');
        // ->join('roles', 'role_permission.role_id', '=', 'roles.id');
        if(!$accessRolePermission){
            $this->success = false;
            $this->responseMessage = "No Role-Permission Data found!";
            return;
        }
        $this->responseMessage = "All Role-Permission Data fetched successfully";
        $this->outputData = $accessRolePermission;
        $this->success = true;
    }

    /**Get All Role-Permission data */
    public function getAllRolePermission(){
        /**test */
        // $accessRolePermission = RolePermission::select('role_id')
        // ->groupBy('role_id')
        // ->get();
        // $accessRolePermission = [];
        // foreach($this->RolePermission as $role){
        //     $accessRolePermission.push($role->permissions);
        // }

        // ->join('roles', 'role_permission.role_id', '=', 'roles.id')
        // ->select('role_permission.role_id', 'role_permission.permission_id', 'roles.title')
        // ->get()
        // ->groupBy('role_id');

        /**test */
        $accessRolePermission = $this->RolePermission
        ->join('roles', 'role_permission.role_id', '=', 'roles.id')
        ->select('role_permission.role_id', 'role_permission.permission_id', 'roles.title', 'roles.status')
        ->get()
        ->groupBy('role_id');



        // $accessRolePermission = $this->RolePermission->all()->groupBy('role_id');
        // ->join('roles', 'role_permission.role_id', '=', 'roles.id');
        if(!$accessRolePermission){
            $this->success = false;
            $this->responseMessage = "No Role-Permission Data found!";
            return;
        }
        $this->responseMessage = "All Role-Permission Data fetched successfully";
        $this->outputData = $accessRolePermission;
        $this->success = true;
    }

    //Getting All Roles
    public function getAllRoles(){
        $accessRoles = $this->AccessRole->all();
        if(!$accessRoles){
            $this->success = false;
            $this->responseMessage = "Role Data not found!";
            return;
        }
        $this->responseMessage = "All Roles Data fetched successfully";
        $this->outputData = $accessRoles;
        $this->success = true;
    }

    //Creating Access Permission
    public function createAccessPermission(Request $request){
        if(!isset($this->params)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        //############ Validation Start
        $this->validator->validate($request, [
            "title"=>v::notEmpty(),
            "access_code"=>v::notEmpty(),
            "module"=>v::notEmpty(),
            "status"=>v::notEmpty(),
        ]);
        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }
        //############ Validation End

        //################ Database insertion Start
        $Permission = $this->AccessPermissions->insert([
            "title" => $this->params->title,
            "access_code" => $this->params->access_code,
            "module" => $this->params->module,
            "parent_id" => $this->params->parent_id,
            "description" => $this->params->description,
            "status" => $this->params->status,
         ]);
        //################ Database insertion End


        $this->responseMessage = "New Access Permission Created Successfully";
        $this->outputData = $this->Permission;
        $this->success = true;
    }

    //Creating Access Role
    public function createAccessRole($request){
        if(!isset($this->params)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }
        //########### Validation Start
        $this->validator->validate($request, [
            "title"=>v::notEmpty(),
            "description"=>v::notEmpty(),
            "status"=>v::notEmpty()->intVal()
         ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }
        //########### Validation End


        //########### Duplicate Check Start
        $AccessRole = $this->AccessRole->where(["title"=>$this->params->title])->first();
        if ($AccessRole) {
            $this->success = false;
            $this->responseMessage = "Title with the same name already exists!";
            return;
        }
        //######### Duplicate Check End

          //########### Database Insertion Start

        $newRole = $this->AccessRole->create([
            "title" => $this->params->title,
            "description" => $this->params->description,
            "created_by" => $this->user->id,
            // "updated_by" => $this->params->updated_by,
            "status" => $this->params->status,
         ]);
        //########### Database Insertion End



        $this->responseMessage = "New Access Role Created Successfully";
        $this->outputData = $this->newRole;
        $this->success = true;
    }

    //Creating New Role
    public function createNewRolePermission($request){

        if(!isset($this->params)){
            $this->success = false;
            $this->responseMessage = "Parameter missing";
            return;
        }

        //################## Validation Start
        $this->validator->validate($request, [
            "role_id"=>v::notEmpty(),
            // "permission_id"=>v::notEmpty(),
            // "status"=>v::notEmpty()
         ]);

        if ($this->validator->failed()) {
            $this->success = false;
            $this->responseMessage = $this->validator->errors;
            return;
        }
        //################## Validation End

        //########### Checking duplicate entry start
        // $newRole = $this->RolePermission->where(["email"=>$this->params->email])->first();
        //########### Checking duplicate entry end

        //########### Database Insertion Start

        $this->permissionList = array($this->params->permissions_id);

          $array = array();
        //   $obj = json_decode($this->params->permissions_id, TRUE);

        foreach ($this->permissionList[0] as $key => $permission) {
            array_push($array, $permission);
            /**Database Insertion part */
            $this->RolePermission->insert([
            "role_id" => $this->params->role_id,
            // "access_role" => $this->params->data['access_role']['id'],
            "permission_id" => $permission,
            "status" => 1
         ]);

         /**Database Insertion part End*/
        }


        // $newRole = RolePermission::statement('select *');
        //########### Database Insertion End
         //gettype($obj)

        $this->responseMessage = "New Role Created Successfully";
        //$this->outputData = $this->permissionList;
        $this->outputData = $array;
        $this->success = true;
    }



    public function getAllPermissions(){
        $AccessPermissions = $this->AccessPermissions->all();
        if(!$AccessPermissions){
            $this->success = false;
            $this->responseMessage = "Permission Data not found!";
            return;
        }
        $this->responseMessage = "All Permissions fetched successfully";
        $this->outputData = $AccessPermissions;
        $this->success = true;
    }
}

