<?php

namespace App\Models\RBM;

use App\Models\RBM\RoomFacility;
use App\Models\RBM\TowerFloorRoom;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{

    protected $table = 'room_types';

    protected $guarded = [
        'id',
    ];

    public function rooms()
    {
        return $this->hasMany(TowerFloorRoom::class,'room_type_id');
    }

    public function roomFacilities()
    {
        return $this->belongsToMany(RoomFacility::class,'room_facility_type');
    }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class, 'parent_id');
    }

    public function childrenRoomTypes()
    {
        return $this->hasMany(RoomType::class, 'parent_id')->with('roomTypes');
    }


}
