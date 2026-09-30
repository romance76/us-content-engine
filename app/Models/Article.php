<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PUBLISHED = 'published';

    public const CATEGORIES = [
        '생활정보',
        '금융·세금',
        '부동산',
        '교통',
        '날씨·안전',
        '통신',
        '창업·비즈니스',
        '교육',
    ];

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'keyword_id',
        'category',
        'reviewed_by',
        'meta_title',
        'meta_description',
        'cover_image_url',
        'generated_by',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now());
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public static function makeUniqueSlug(string $title): string
    {
        // Str::slug() only transliterates Latin scripts: a Korean title either
        // collapses to an empty string (breaking route generation) or gets
        // reduced to a stray leftover ASCII fragment (e.g. just "dds" out of a
        // whole Korean sentence). Detect non-ASCII content and build the slug
        // straight from the sanitized original title instead.
        $base = preg_match('/[^\x00-\x7F]/', $title)
            ? trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $title), '-')
            : Str::slug($title);

        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
