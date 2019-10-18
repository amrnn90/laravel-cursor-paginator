<?php

namespace Tests\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reply extends Model {
    use SoftDeletes;
    
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}