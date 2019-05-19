<?php

namespace Tests\Models;

class Reply extends Model {
    public function user() 
    {
        return $this->belongsTo(User::class);
    }
}