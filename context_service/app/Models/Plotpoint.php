<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plotpoint extends Model
{
    protected $table = 'plotpoints';
    protected $primaryKey = 'plotpoint_id';

    protected $fillable = [
        'chapter_id', 'point_number', 'plot_text',
        'ai_post', 'player_post', 'result', 'status'
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class, 'chapter_id', 'chapter_id');
    }
}
