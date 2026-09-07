<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAudience extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_campaign_id',
        'company_id',
        'contact_id',
        'segment',
        'classification',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommercialCampaign::class, 'commercial_campaign_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
