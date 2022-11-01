<?php

namespace  App\Controllers\Auth;

use App\Models\Users\ClientUsers;
use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use App\Validation\Validator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as v;
use App\Controllers\Permissions\PermissionController;
class AuthController
{

    protected $user;
    protected $customResponse;
    protected $validator;

    public function __construct()
    {
        $this->user = new ClientUsers();
        $this->customResponse = new CustomResponse();
        $this->validator = new Validator();
    }


    // public function register(Request $request, Response $response)
    // {
    //     $this->validator->validate($request, [
    //       "name"=>v::notEmpty(),
    //        "email"=>v::notEmpty()->email(),
    //        "password"=>v::notEmpty()
    //     ]);

    //     if ($this->validator->failed()) {
    //         $responseMessage = $this->validator->errors;
    //         return $this->customResponse->is400Response($response, $responseMessage);
    //     }

    //     if ($this->emailExist(CustomRequestHandler::getParam($request, "email"))) {
    //         $responseMessage = "Email already exist";
    //         return $this->customResponse->is400Response($response, $responseMessage);
    //     }

    //     $passwordHash = $this->hashPassword(CustomRequestHandler::getParam($request, 'password'));

    //     $this->user->create([
    //        "name"=>CustomRequestHandler::getParam($request, "name"),
    //        "email"=>CustomRequestHandler::getParam($request, "email"),
    //        "password"=>$passwordHash
    //     ]);

    //     $responseMessage = "new user created successfully";

    //     return $this->customResponse->is200Response($response, $responseMessage);
    // }



    public function login(Request $request, Response $response)
    {
        $this->validator->validate($request, [
           "email"=>v::notEmpty()->email(),
           "password"=>v::notEmpty()
        ]);

        if ($this->validator->failed()) {
            $responseMessage = $this->validator->errors;
            return $this->customResponse->is400Response($response, $responseMessage);
        }
        $verifyAccount = $this->verifyAccount(
            CustomRequestHandler::getParam($request, "password"),
            CustomRequestHandler::getParam($request, "email")
        );

        if ($verifyAccount==false) {
            $responseMessage = "invalid username or password";
            return $this->customResponse->is400Response($response, $responseMessage);
        }
        
        if ($verifyAccount->status!=1) {
            $responseMessage = "Inactive User";
            return $this->customResponse->is400Response($response, $responseMessage);
        }

        $permissionController = new PermissionController;
        
        $data = $verifyAccount;
        $data["permissions"] = $permissionController->getPermissionByRoleIdReturn($data->role_id);
        $responseMessage = GenerateTokenController::generateToken($data);
        // $responseMessage["permissions"] = PermissionController::getPermissionByRoleIdReturn($data->role_id);
        return $this->customResponse->is200Response($response, $responseMessage, $data);
    }

    public function verifyAccount($password, $email)
    {
        $count = $this->user->where(["email"=>$email])->count();
        if ($count==0) {
            return false;
        }
        $user = $this->user->where(["email"=>$email])->first();
        $hashedPassword = $user->password;
        $verify = password_verify($password, $hashedPassword);
        if ($verify==false) {
            return false;
        }

        return $user;
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function emailExist($email)
    {
        $count = $this->user->where(['email'=>$email])->count();
        if ($count==0) {
            return false;
        }
        return true;
    }
}
