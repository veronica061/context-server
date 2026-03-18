<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalPlot extends Model
{
    protected $table = 'global_plots';
    protected $primaryKey = 'plot_id';

    protected $fillable = ['title', 'status'];

    public function characters()
    {
        return $this->hasMany(Character::class, 'plot_id', 'plot_id');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class, 'plot_id', 'plot_id');
    }
}
