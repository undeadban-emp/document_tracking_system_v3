<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCode extends Model
{
    use HasFactory;
    public $connection = 'doc_tracking';
    public $table = 'trans_codes';
    protected $fillable = ['code', 'value'];
}