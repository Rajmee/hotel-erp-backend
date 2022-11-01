<?php
namespace App\Controllers\Auth;

use App\Interfaces\SecretKeyInterface;
use Firebase\JWT\JWT;
// use App\Controllers\Permissions\PermissionController;
class GenerateTokenController implements SecretKeyInterface
{
    public static function generateToken($data)
    {
        $now = time();
        $future = strtotime('+12 hour', $now);
        $secretKey = self::JWT_SECRET_KEY;
        // $permissionController = new PermissionController;
        $payload = [
         "id"=>$data->id,
         "email"=>$data->email,
         "role"=>$data->role,
        //  "permissions"=>$permissionController->getPermissionByRoleIdReturn($data->role_id),
         "company"=>$data->company,
         "clientID"=>$data->clientID,
         "iat"=>$now,
         "exp"=>$future
        ];

        return JWT::encode($payload, $secretKey, "HS256");
    }
}
