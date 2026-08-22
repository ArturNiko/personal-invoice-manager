<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentTask extends Model
{
    protected $fillable = [
        'status',
        'file_path',
        'details',
        'invoice_id',
    ];

    
}
