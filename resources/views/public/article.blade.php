@extends('public.layout')

@php
    $isEn = app()->getLocale() === 'en';
    $bodyHasImage = str_contains($article->body, '<img');
    $displayTitle = $isEn ? $article->translatedTitle() : $article->title;
    $displayExcerpt = $isEn ? $article->translatedExcerpt() : $article->excerpt;
    $displayBody = $isEn ? $article->translatedBody() : $article->body;
    $displayCategory = $isEn ? $article->translatedCategory() : $article->category;
    $displayMetaTitle = $isEn ? $article->translatedMetaTitle() : ($article->meta_title ?: $article->title);
    $displayMetaDescription = $isEn ? $article->translatedMetaDescription() : ($article->meta_description ?: $article->excerpt);
@endphp

@section('title', $displayMetaTitle)
@section('meta_description', $displayMetaDescription)
@section('og_type', 'article')
@if ($article->cover_image_url)
    @section('og_image', $article->cover_image_url)
@endif

@push('head')
    <meta property="article:published_time" content="{{ $article->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}">
@endpush

@section('content')
    <article>
        <a href="{{ $article->category ? route('category.show', $article->category) : route('home') }}"
           class="text-sm text-gray-400 hover:text-gray-600">{{ __('site.back_to_list') }}</a>

        @if ($article->status !== 'published')
            <div class="mt-4 mb-2 px-3 py-2 bg-yellow-50 text-yellow-800 text-sm rounded">
                {{ __('site.preview_only', ['status' => $article->status]) }}
            </div>
        @endif

        @if ($displayCategory)
            <a href="{{ route('category.show', $article->category) }}" class="mt-4 inline-block text-xs font-medium text-blue-700">{{ $displayCategory }}</a>
        @endif
        <h1 class="mt-1 text-3xl font-bold leading-tight">{{ $displayTitle }}</h1>
        <div class="mt-2 flex items-center gap-3 flex-wrap">
            <time class="text-sm text-gray-400" datetime="{{ $article->published_at?->toIso8601String() }}">
                {{ $article->published_at?->format('M j, Y') }}
            </time>
            <span class="text-gray-200">·</span>
            <button type="button" id="share-btn" class="text-sm text-blue-700 hover:underline">🔗 공유하기</button>
            <a id="share-fb" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="hidden text-sm text-blue-700 hover:underline">Facebook</a>
            <a id="share-x" href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($displayTitle) }}" target="_blank" rel="noopener" class="hidden text-sm text-blue-700 hover:underline">X</a>
            <button type="button" id="share-copy" class="hidden text-sm text-blue-700 hover:underline">링크 복사</button>
        </div>

        @if ($article->cover_image_url && ! $bodyHasImage)
            <img src="{{ $article->cover_image_url }}" alt="{{ $displayTitle }}" class="mt-6 rounded-lg w-full">
        @endif

        <div class="prose prose-neutral max-w-none mt-8">
            {!! $displayBody !!}
        </div>
    </article>

    @if ($older || $newer)
        <div class="mt-12 pt-6 border-t border-gray-100 grid grid-cols-2 gap-4">
            <div>
                @if ($older)
                    <a href="{{ route('articles.show', $older) }}" class="block">
                        <span class="text-xs text-gray-400">{{ __('site.previous_post') }}</span>
                        <span class="mt-1 block text-sm font-medium text-gray-700 hover:underline line-clamp-2">{{ $isEn ? $older->translatedTitle() : $older->title }}</span>
                    </a>
                @endif
            </div>
            <div class="text-right">
                @if ($newer)
                    <a href="{{ route('articles.show', $newer) }}" class="block">
                        <span class="text-xs text-gray-400">{{ __('site.next_post') }}</span>
                        <span class="mt-1 block text-sm font-medium text-gray-700 hover:underline line-clamp-2">{{ $isEn ? $newer->translatedTitle() : $newer->title }}</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-16 pt-8 border-t border-gray-100">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">{{ __('site.related') }}</h2>
            <ul class="mt-4 space-y-3">
                @foreach ($related as $item)
                    <li>
                        <a href="{{ route('articles.show', $item) }}" class="text-blue-700 hover:underline">{{ $isEn ? $item->translatedTitle() : $item->title }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $displayTitle,
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at->toIso8601String(),
        'description' => $displayMetaDescription,
    ]) !!}
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var shareBtn = document.getElementById('share-btn');
            var copyBtn = document.getElementById('share-copy');
            var url = {!! json_encode(url()->current()) !!};
            var title = {!! json_encode($displayTitle) !!};

            if (navigator.share) {
                shareBtn.addEventListener('click', function () {
                    navigator.share({ title: title, url: url }).catch(function () {});
                });
            } else {
                // No native share sheet (most desktop browsers) — show link
                // buttons instead of a button that would do nothing.
                shareBtn.classList.add('hidden');
                document.getElementById('share-fb').classList.remove('hidden');
                document.getElementById('share-x').classList.remove('hidden');
                copyBtn.classList.remove('hidden');
            }

            copyBtn.addEventListener('click', function () {
                navigator.clipboard.writeText(url).then(function () {
                    var original = copyBtn.textContent;
                    copyBtn.textContent = '복사됨!';
                    setTimeout(function () { copyBtn.textContent = original; }, 1500);
                }).catch(function () {});
            });
        });
    </script>
@endsection
