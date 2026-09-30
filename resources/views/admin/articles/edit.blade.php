<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $article->exists ? 'Edit article' : 'New article' }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        @if ($article->exists)
            <div class="flex items-center gap-3 bg-white p-4 rounded-lg shadow-sm">
                <span class="text-sm text-gray-500">
                    Status: <strong>{{ $article->status }}</strong>
                    @if ($article->keyword) · from keyword "{{ $article->keyword->term }}" @endif
                </span>
                <div class="ml-auto flex gap-2">
                    @if ($article->status !== 'published')
                        <form method="POST" action="{{ route('admin.articles.publish', $article) }}">
                            @csrf
                            <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700">Publish</button>
                        </form>
                    @else
                        <a href="{{ route('articles.show', $article) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200">View live</a>
                        <form method="POST" action="{{ route('admin.articles.unpublish', $article) }}">
                            @csrf
                            <button class="px-3 py-1.5 bg-yellow-100 text-yellow-800 text-sm rounded hover:bg-yellow-200">Unpublish</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Delete this article?')">
                        @csrf
                        @method('DELETE')
                        <button class="px-3 py-1.5 bg-red-50 text-red-700 text-sm rounded hover:bg-red-100">Delete</button>
                    </form>
                </div>
            </div>
        @endif

        <form method="POST"
              action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
              class="bg-white p-6 rounded-lg shadow-sm space-y-5">
            @csrf
            @if ($article->exists) @method('PUT') @endif

            <details class="border border-blue-200 bg-blue-50 rounded-md p-4">
                <summary class="text-sm font-medium text-blue-800 cursor-pointer select-none">
                    ChatGPT 초안 가져오기 (제목/요약/본문 자동 채우기)
                </summary>
                <p class="mt-2 text-xs text-blue-700">
                    ChatGPT 등에서 작성한 글(마크다운 형식 — # 제목, ## 소제목, [링크 텍스트](URL), - 목록 등)을 아래에
                    통째로 붙여넣고 "채워넣기"를 누르면 제목/요약/본문 칸이 자동으로 채워집니다. 채운 뒤에는 그대로
                    검토·수정해서 저장하면 됩니다.
                </p>
                <textarea id="ai_draft" rows="8" class="mt-3 block w-full border-blue-300 rounded-md text-sm"
                          placeholder="# 조지아 자동차 등록 갱신 방법&#10;&#10;조지아에서 자동차 등록을 갱신하는 방법을 정리합니다.&#10;&#10;## 준비물&#10;- 운전면허증&#10;- 차량 보험증서&#10;&#10;자세한 내용은 [Georgia DOR](https://dor.georgia.gov) 참고."></textarea>
                <button type="button" id="ai_draft_fill" class="mt-3 px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                    채워넣기 →
                </button>
            </details>

            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" value="{{ old('title', $article->title) }}" required />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="slug" value="Slug (leave blank to auto-generate)" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full" value="{{ old('slug', $article->slug) }}" />
                <x-input-error :messages="$errors->get('slug')" class="mt-1" />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md">
                        @foreach (['draft', 'in_review', 'published'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $article->status) === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="category" value="Category" />
                    <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">—</option>
                        @foreach (\App\Models\Article::CATEGORIES as $c)
                            <option value="{{ $c }}" @selected(old('category', $article->category) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="keyword_id" value="Source keyword" />
                    <select id="keyword_id" name="keyword_id" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">—</option>
                        @foreach ($keywords as $k)
                            <option value="{{ $k->id }}" @selected(old('keyword_id', $article->keyword_id) == $k->id)>{{ $k->term }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <x-input-label for="excerpt" value="Excerpt" />
                <textarea id="excerpt" name="excerpt" rows="2" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('excerpt', $article->excerpt) }}</textarea>
            </div>

            <div>
                <x-input-label for="body" value="Body (HTML)" />
                <textarea id="body" name="body" rows="20" class="mt-1 block w-full border-gray-300 rounded-md font-mono text-sm" required>{{ old('body', $article->body) }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-1" />
            </div>

            <fieldset class="border rounded-md p-4 space-y-4">
                <legend class="text-sm font-medium text-gray-600 px-1">SEO</legend>
                <div>
                    <x-input-label for="meta_title" value="Meta title" />
                    <x-text-input id="meta_title" name="meta_title" class="mt-1 block w-full" value="{{ old('meta_title', $article->meta_title) }}" />
                </div>
                <div>
                    <x-input-label for="meta_description" value="Meta description" />
                    <textarea id="meta_description" name="meta_description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('meta_description', $article->meta_description) }}</textarea>
                </div>
                <div>
                    <x-input-label for="cover_image_url" value="Cover image URL" />
                    <x-text-input id="cover_image_url" name="cover_image_url" class="mt-1 block w-full" value="{{ old('cover_image_url', $article->cover_image_url) }}" />
                </div>
            </fieldset>

            <div class="flex justify-end">
                <x-primary-button>{{ $article->exists ? 'Save' : 'Create' }}</x-primary-button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('ai_draft_fill').addEventListener('click', function () {
            const raw = document.getElementById('ai_draft').value.trim();
            if (! raw) return;

            const lines = raw.split(/\r?\n/);

            // First non-empty line is the title (strip a leading markdown # if present).
            let i = 0;
            while (i < lines.length && lines[i].trim() === '') i++;
            const title = (lines[i] || '').replace(/^#+\s*/, '').trim();
            const rest = lines.slice(i + 1).join('\n').trim();

            const blocks = toBlocks(rest);

            // First plain-text block (not a heading/list) becomes the excerpt.
            const excerptBlock = blocks.find(b => ! /^#{1,6}\s|^[-*]\s|^\d+\.\s/.test(b));
            const excerpt = excerptBlock ? inlineMarkdown(excerptBlock).replace(/<[^>]+>/g, '').slice(0, 480) : '';

            const bodyHtml = blocks.map(blockToHtml).join('\n');

            document.getElementById('title').value = title;
            document.getElementById('excerpt').value = excerpt;
            document.getElementById('body').value = bodyHtml;
        });

        function inlineMarkdown(text) {
            return text
                .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/\*([^*]+)\*/g, '<em>$1</em>');
        }

        // Groups lines into homogeneous blocks (a heading line, a run of list
        // items, or a paragraph) — unlike a blank-line split, this also
        // separates a heading from a list that follows it with no blank line
        // in between, which is how ChatGPT typically formats a draft.
        function toBlocks(text) {
            const lines = text.split('\n');
            const blocks = [];
            const isHeading = l => /^#{2,6}\s+/.test(l);
            const isBullet = l => /^[-*]\s+/.test(l);
            const isNumbered = l => /^\d+\.\s+/.test(l);

            let i = 0;
            while (i < lines.length) {
                const line = lines[i].trim();
                if (line === '') { i++; continue; }

                if (isHeading(line)) { blocks.push(line); i++; continue; }

                if (isBullet(line) || isNumbered(line)) {
                    const test = isBullet(line) ? isBullet : isNumbered;
                    const items = [];
                    while (i < lines.length && test(lines[i].trim())) { items.push(lines[i].trim()); i++; }
                    blocks.push(items.join('\n'));
                    continue;
                }

                const para = [];
                while (i < lines.length && lines[i].trim() !== '' && !isHeading(lines[i].trim()) && !isBullet(lines[i].trim()) && !isNumbered(lines[i].trim())) {
                    para.push(lines[i].trim());
                    i++;
                }
                blocks.push(para.join(' '));
            }

            return blocks;
        }

        function blockToHtml(block) {
            const headingMatch = block.match(/^(#{2,6})\s+(.*)$/);
            if (headingMatch) {
                const level = Math.min(headingMatch[1].length, 4); // h2–h4 inside the article body
                return `<h${level}>${inlineMarkdown(headingMatch[2].trim())}</h${level}>`;
            }

            const lines = block.split('\n').map(l => l.trim()).filter(Boolean);

            if (lines.every(l => /^[-*]\s+/.test(l))) {
                const items = lines.map(l => `<li>${inlineMarkdown(l.replace(/^[-*]\s+/, ''))}</li>`).join('');
                return `<ul>${items}</ul>`;
            }

            if (lines.every(l => /^\d+\.\s+/.test(l))) {
                const items = lines.map(l => `<li>${inlineMarkdown(l.replace(/^\d+\.\s+/, ''))}</li>`).join('');
                return `<ol>${items}</ol>`;
            }

            return `<p>${inlineMarkdown(block)}</p>`;
        }
    </script>
</x-app-layout>
