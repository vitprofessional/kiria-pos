<?php

namespace Modules\Essentials\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EssentialsEmployeePaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [];
    
	public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
	
    protected static function newFactory()
    {
        return \Modules\Essentials\Database\factories\EssentialsEmployeePaymentSettingFactory::new();
    }
}
