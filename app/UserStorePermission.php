<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserStorePermission extends Model
{
   
    protected $guarded = ['id'];
    
    protected $table = 'user_store_permissions';

   
}
