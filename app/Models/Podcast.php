<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Podcast extends Model
{
    protected $fillable = [
        'title',
        'product_url',
        'conversation_length',
        'voice_alex_id',
        'voice_alex_name',
        'voice_sarah_id',
        'voice_sarah_name',
        'dialogue',
        'audio_path',
        'audio_url',
    ];

    protected $casts = [
        'dialogue' => 'array',
    ];
}
