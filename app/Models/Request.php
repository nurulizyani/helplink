<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * =========================
     * MASS ASSIGNMENT
     * =========================
     */
    protected $fillable = [
        'user_id',
        'item_name',
        'description',
        'category',
        'address',
        'latitude',
        'longitude',
        'image',            // primary supporting image
        'document',         // supporting document (bill, slip, etc)
        'status',           // pending | approved | rejected | fulfilled
        'admin_remark',

        // AI ANALYSIS FIELDS
        'ai_document_type',
        'ai_summary',
        'ai_extracted_data',
        'ai_confidence',
    ];

    /**
     * =========================
     * CASTING
     * =========================
     */
    protected $casts = [
        'ai_extracted_data' => 'array',
        'latitude'          => 'float',
        'longitude'         => 'float',
        'ai_confidence'     => 'integer',
    ];

    /**
     * =========================
     * APPENDED ATTRIBUTES
     * =========================
     * These fields will be automatically included
     * in API responses (JSON)
     */
    protected $appends = [
        'image_url',
        'document_url',
        'status_badge',
    ];

    /**
     * =========================
     * RELATIONSHIPS
     * =========================
     */

    // Request belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Request claims (helpers)
    public function claimRequests()
    {
        return $this->hasMany(ClaimRequest::class, 'request_id');
    }

    // Completed claim (fulfilled)
    public function completedClaim()
    {
        return $this->hasOne(ClaimRequest::class, 'request_id')
            ->where('status', 'fulfilled');
    }

    /**
     * =========================
     * ACCESSORS (URL GENERATION)
     * =========================
     */

    // Public URL for request image
    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }

    // Public URL for request document
    public function getDocumentUrlAttribute()
    {
        return $this->document
            ? asset('storage/' . $this->document)
            : null;
    }

    /**
     * =========================
     * UI HELPER ATTRIBUTES
     * =========================
     */
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            'fulfilled' => 'primary',
            default     => 'secondary',
        };
    }
}
