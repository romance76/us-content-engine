<?php

namespace App\Services;

use DOMDocument;
use DOMText;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side Korean -> English translation via the free, keyless MyMemory
 * API (https://mymemory.translated.net) — chosen over a client-side widget
 * because the widget's own script/API calls get silently blocked by ad
 * blockers for a meaningful share of visitors, with no way to recover on
 * our side. Translating here means the page itself is bilingual; nothing
 * has to load in the visitor's browser for it to work.
 */
class Translator
{
    private const MAX_CHUNK_BYTES = 450; // MyMemory's anonymous-tier limit is 500 bytes per query.

    public function translateText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        $chunks = $this->splitIntoChunks($text);
        $translated = [];

        foreach ($chunks as $chunk) {
            $translated[] = $this->translateChunk($chunk);
        }

        return implode(' ', $translated);
    }

    /**
     * Translates every text node inside an HTML fragment, leaving tags,
     * attributes (src/href/alt included), and structure untouched.
     */
    public function translateHtml(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $textNodes = $xpath->query('//text()');

        foreach ($textNodes as $node) {
            /** @var DOMText $node */
            if (trim($node->nodeValue) === '') {
                continue;
            }
            // Read the original text before clearing — nodeValue and textContent
            // are the same underlying value, so clearing first leaves nothing to translate.
            $translated = $this->translateText($node->nodeValue);
            $node->nodeValue = '';
            $node->appendData($translated);
        }

        $wrapper = $doc->getElementsByTagName('div')->item(0);
        $out = '';
        foreach ($wrapper->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private function translateChunk(string $chunk): string
    {
        try {
            $response = Http::timeout(10)->get('https://api.mymemory.translated.net/get', [
                'q' => $chunk,
                'langpair' => 'ko|en',
            ]);

            // A quota/rate-limit hit still comes back as HTTP 200 with the warning
            // text sitting in responseData.translatedText — treat that (and any
            // non-200 responseStatus) as a failure rather than caching it as real
            // translated content.
            if ($response->json('responseStatus') != 200) {
                throw new \RuntimeException('MyMemory responseStatus: '.$response->json('responseStatus'));
            }

            $result = $response->json('responseData.translatedText');
            if ($result && str_contains(strtoupper($result), 'MYMEMORY WARNING')) {
                throw new \RuntimeException('MyMemory quota warning returned as translation');
            }

            return $result ?: $chunk;
        } catch (\Throwable $e) {
            Log::warning('Translation request failed', ['error' => $e->getMessage()]);

            return $chunk;
        }
    }

    /**
     * @return string[]
     */
    private function splitIntoChunks(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?。]|[\x{AC00}-\x{D7A3}]\.)\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];

        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            $candidate = $current === '' ? $sentence : $current.' '.$sentence;
            if (strlen($candidate) > self::MAX_CHUNK_BYTES && $current !== '') {
                $chunks[] = $current;
                $current = $sentence;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        // A single sentence longer than the limit still needs a hard split.
        $final = [];
        foreach ($chunks as $chunk) {
            if (strlen($chunk) <= self::MAX_CHUNK_BYTES) {
                $final[] = $chunk;

                continue;
            }
            foreach (mb_str_split($chunk, 150) as $piece) {
                $final[] = $piece;
            }
        }

        return $final;
    }
}
