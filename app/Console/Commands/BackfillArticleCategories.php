<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class BackfillArticleCategories extends Command
{
    protected $signature = 'articles:backfill-categories';

    protected $description = 'Assign a category to any article that does not have one yet, based on keywords in its title.';

    /**
     * Checked in order — first pattern that matches the title wins.
     * Anything left unmatched falls back to '생활정보'.
     */
    private const RULES = [
        '교통' => '/MARTA|대중교통|버스|지하철|공항|운전면허|자동차\s*보험|주차/u',
        '부동산' => '/부동산|주택|홈스테드|Homestead|재산세|오퍼|클로징|모기지|렌트|아파트/u',
        '금융·세금' => '/세금|택스|은행|계좌|학자금|401|IRA|신용|소득세|W-2|Standard Deduction|저축|투자/u',
        '통신' => '/휴대폰|이통사|알뜰폰|통신사/u',
        '날씨·안전' => '/허리케인|폭염|폭풍|한파|재난/u',
        '창업·비즈니스' => '/창업|LLC|사업자|비즈니스/u',
        '교육' => '/학교|캠프|방과후|유치원|대학\s*입시/u',
    ];

    public function handle(): int
    {
        $articles = Article::whereNull('category')->get();

        $counts = [];

        foreach ($articles as $article) {
            $category = '생활정보';

            foreach (self::RULES as $name => $pattern) {
                if (preg_match($pattern, $article->title)) {
                    $category = $name;
                    break;
                }
            }

            $article->update(['category' => $category]);
            $counts[$category] = ($counts[$category] ?? 0) + 1;
            $this->line("{$article->title} → {$category}");
        }

        $this->newLine();
        foreach ($counts as $name => $count) {
            $this->line("{$name}: {$count}");
        }

        $this->info("Done — {$articles->count()} articles categorized.");

        return self::SUCCESS;
    }
}
