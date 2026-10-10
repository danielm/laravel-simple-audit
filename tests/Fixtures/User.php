<?php

namespace Danielm\LaravelSimpleAudit\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    public $timestamps = true;
}
