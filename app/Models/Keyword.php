<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keyword extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_USED = 'used';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'term',
        'source',
        'search_volume',
        'competition',
        'status',
        'notes',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
