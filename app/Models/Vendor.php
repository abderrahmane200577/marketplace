<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'store_name',
        'store_slug',
        'description',
        'logo',
        'banner',
        'phone',
        'address',
        'city',
        'country',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'approved_at'   => 'datetime',
        'total_revenue' => 'decimal:2',
        'balance'       => 'decimal:2',
    ];

    // Auto-generate slug from store_name
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($vendor) {
            $vendor->store_slug = Str::slug($vendor->store_name);
        });
    }

    // ─── Status Helpers ────────────────────────────────────────────

    public function isPending(): bool   { return $this->status === 'pending';   }
    public function isApproved(): bool  { return $this->status === 'approved';  }
    public function isRejected(): bool  { return $this->status === 'rejected';  }
    public function isSuspended(): bool { return $this->status === 'suspended'; }

    // ─── Relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
