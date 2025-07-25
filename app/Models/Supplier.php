<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'inscricao_estadual',
        'email',
        'cnpj',
        'phone_1',
        'phone_2',
        'status',
    ];
}
