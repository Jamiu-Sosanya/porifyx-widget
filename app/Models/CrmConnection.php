<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmConnection extends Model
{
    /** provider => [label, credential fields [name => label]] */
    public const PROVIDERS = [
        'hubspot'     => ['HubSpot', ['token' => 'Private app access token']],
        'pipedrive'   => ['Pipedrive', ['token' => 'API token', 'domain' => 'Company domain (yourcompany.pipedrive.com)']],
        'gohighlevel' => ['GoHighLevel', ['token' => 'Private integration token', 'location_id' => 'Location ID', 'calendar_id' => 'Calendar ID (optional)']],
        'zoho'        => ['Zoho CRM', ['token' => 'OAuth access token', 'refresh_token' => 'Refresh token (optional)', 'client_id' => 'Client ID (optional)', 'client_secret' => 'Client secret (optional)', 'dc' => 'Data centre (com, eu, in, com.au)']],
        'salesforce'  => ['Salesforce', ['token' => 'Access token', 'instance_url' => 'Instance URL (https://yourorg.my.salesforce.com)']],
        'webhook'     => ['Zapier / Make / custom', ['url' => 'Webhook URL', 'secret' => 'Signing secret (optional)']],
    ];

    protected $fillable = ['widget_id', 'provider', 'credentials', 'settings', 'is_active', 'last_synced_at', 'last_error'];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials'    => 'encrypted:array',
        'settings'       => 'array',
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }

    public function label(): string { return self::PROVIDERS[$this->provider][0] ?? $this->provider; }

    public function refKey(): string { return $this->provider . ':' . $this->id; }
}
