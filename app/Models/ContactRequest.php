<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    public const TOPICS = [
        'sales'        => 'Talk to sales',
        'support'      => 'Product support',
        'partnerships' => 'Partnerships',
        'careers'      => 'Careers',
        'other'        => 'Something else',
    ];

    public const TEAM_SIZES = ['1-10', '11-50', '51-200', '201-1000', '1000+'];

    public const VOLUMES = ['Under 1k', '1k-10k', '10k-50k', '50k+', 'Not sure'];

    protected $fillable = ['topic', 'name', 'email', 'company', 'website', 'team_size', 'volume', 'message', 'ip', 'handled_at'];

    protected $casts = ['handled_at' => 'datetime'];
}
