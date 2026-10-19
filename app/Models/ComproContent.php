<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComproContent extends Model
{
    use HasFactory;

    protected $table = 'compro_contents';

    protected $fillable = [
        'page',
        'section',
        'key',
        'type',
        'value',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Helper accessor untuk parsing format nilai berdasarkan type
     */
    public function getParsedValueAttribute()
    {
        if ($this->type === 'repeater' || $this->type === 'json') {
            return json_decode($this->value, true) ?: [];
        }

        return $this->value;
    }
}
