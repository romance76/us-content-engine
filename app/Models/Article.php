<?php

namespace App\Models;

use App\Services\Translator;
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

    public const CATEGORY_LABELS_EN = [
        '생활정보' => 'Daily Life',
        '금융·세금' => 'Finance & Tax',
        '부동산' => 'Real Estate',
        '교통' => 'Transportation',
        '날씨·안전' => 'Weather & Safety',
        '통신' => 'Mobile & Internet',
        '창업·비즈니스' => 'Business',
        '교육' => 'Education',
    ];

    protected $fillable = [
        'title',
        'title_en',
        'slug',
        'excerpt',
        'excerpt_en',
        'body',
        'body_en',
        'status',
        'keyword_id',
        'category',
        'reviewed_by',
        'meta_title',
        'meta_title_en',
        'meta_description',
        'meta_description_en',
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

    public function translatedTitle(): string
    {
        if ($this->title_en) {
            return $this->title_en;
        }

        // Lightweight listings (recent posts, prev/next) select only title+slug —
        // translate on the fly without triggering the full ensureTranslated() flow,
        // which needs body/excerpt columns this partial model doesn't have loaded.
        if (! array_key_exists('body', $this->attributes)) {
            return app(Translator::class)->translateText($this->title);
        }

        return $this->ensureTranslated()->title_en ?: $this->title;
    }

    public function translatedExcerpt(): ?string
    {
        if (! $this->excerpt) {
            return null;
        }

        return $this->ensureTranslated()->excerpt_en ?: $this->excerpt;
    }

    public function translatedBody(): string
    {
        return $this->ensureTranslated()->body_en ?: $this->body;
    }

    public function translatedMetaTitle(): string
    {
        return $this->ensureTranslated()->meta_title_en ?: ($this->meta_title ?: $this->translatedTitle());
    }

    public function translatedMetaDescription(): ?string
    {
        if (! $this->meta_description) {
            return $this->translatedExcerpt();
        }

        return $this->ensureTranslated()->meta_description_en ?: $this->meta_description;
    }

    public function translatedCategory(): ?string
    {
        return $this->category ? (self::CATEGORY_LABELS_EN[$this->category] ?? $this->category) : null;
    }

    /**
     * Translates and caches the English fields on first access for this
     * article, so the (slow, external) translation call only ever happens
     * once per article rather than on every request.
     */
    private function ensureTranslated(): self
    {
        if ($this->title_en) {
            return $this;
        }

        $translator = app(Translator::class);

        $this->title_en = $translator->translateText($this->title);
        $this->excerpt_en = $this->excerpt ? $translator->translateText($this->excerpt) : null;
        $this->body_en = $translator->translateHtml($this->body);
        $this->meta_title_en = $this->meta_title ? $translator->translateText($this->meta_title) : $this->title_en;
        $this->meta_description_en = $this->meta_description ? $translator->translateText($this->meta_description) : $this->excerpt_en;
        $this->save();

        return $this;
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
