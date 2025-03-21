<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExpenseCategoryCode extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];
    
    protected $table = 'expense_categories_codes';

    protected static $logFillable = true;

    
    protected static $logName = 'Expense Category'; 

    

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
}
