<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $table = 'characters';
    protected $primaryKey = 'character_id';

    protected $fillable = [
        'plot_id', 'name', 'attitude_to_player',
        'description', 'status', 'memory', 'role'
    ];

    public function globalPlot()
    {
        return $this->belongsTo(GlobalPlot::class, 'plot_id', 'plot_id');
    }
}
