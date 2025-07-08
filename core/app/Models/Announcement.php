<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
     protected $fillable = ['title', 'description', 'media_type', 'media_path', 'is_active'];
}
