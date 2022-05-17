<?php

namespace Yarm\Bookshelf\Models;

use Illuminate\Database\Eloquent\Model;

class Shelf_book extends Model
{
    public $timestamps = true;

    protected $fillable = ['file_id','user_id','identifier_id','ref_id','session_id', 'readable', 'checked', 'unzipped', 'downloaded','pathAndName','type'];

}
