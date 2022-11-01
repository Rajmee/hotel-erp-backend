<?php

namespace App\Models\Permission;

use App\Models\Users\ClientUsers;

use Illuminate\Database\Eloquent\Model;

class AccessPermission extends Model
{

    protected $table = 'permissions';

    protected $guarded = [
        'id'
    ];

    public function creator()
    {
        return $this->belongsTo(ClientUsers::class, 'created_by');
    }
}
