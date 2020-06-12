<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Konfbayar extends Model
{
    protected $table='transaksi';
    protected $primaryKey='id_transaksi';
    protected $guarded=[];
    public $timestamps=false;
}
