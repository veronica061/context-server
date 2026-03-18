<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $table = 'chapters';
    protected $primaryKey = 'chapter_id';

    protected $fillable = ['plot_id', 'chapter_number', 'chapter_text', 'chapter_summary', 'status'];

    public function globalPlot()
    {
        return $this->belongsTo(GlobalPlot::class, 'plot_id', 'plot_id');
    }

    public function plotpoints()
    {
        return $this->hasMany(Plotpoint::class, 'chapter_id', 'chapter_id');
    }
}
