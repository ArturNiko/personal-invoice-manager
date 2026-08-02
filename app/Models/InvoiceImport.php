<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceImport extends Model
{
    protected $fillable = [
        'file_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    
}
