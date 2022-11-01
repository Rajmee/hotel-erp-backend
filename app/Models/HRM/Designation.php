<?php

namespace App\Models\HRM;

use App\Models\Users\ClientUsers;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{

    protected $table = 'designations';

    protected $guarded = [
        'id',
    ];

    public function creator()
    {
        return $this->belongsTo(ClientUsers::class, 'created_by');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
