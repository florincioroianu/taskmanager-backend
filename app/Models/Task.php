<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    

    protected $hidden = ["created_at", "updated_at"];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
