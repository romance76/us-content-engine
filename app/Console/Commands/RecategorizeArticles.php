<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class RecategorizeArticles extends Command
{
    protected $signature = 'articles:recategorize {--dry-run : Preview the new category assignments without saving them}';

    protected $description = 'Re-assign every article to the current (expanded) category list based on keywords in its title. Unlike articles:backfill-categories, this overwrites existing categories too — used for one-off taxonomy migrations.';

    /**
     * Checked in order — first pattern that matches the title wins. Ordered
     * from most specific to most generic so e.g. a mortgage article hits
     * 부동산 before the broader 금융 bucket. Anything left unmatched falls
     * back to '생활정보'.
     */
    private const RULES = [
        '이민·비자' => '/비자|영주권|시민권\s*시험|시민권\s*신청|이민국|USCIS|그린카드|Green\s*Card|I-9|I-485|I-130|I-765|EAD|OPT\b|H-1B|취업\s*비자|유학생\s*비자|DACA|망명/u',
        '보험' => '/보험|Medicare|메디케어|Medicaid|메디케이드|오바마케어|\bACA\b/u',
        '교통' => '/MARTA|대중교통|버스|지하철|공항|운전면허|주차|교통\s*법규|자동차\s*등록/u',
        '부동산' => '/부동산|주택|홈스테드|Homestead|재산세|오퍼|클로징|모기지|렌트|아파트/u',
        '세금' => '/세금|택스|소득세|Tax|\bIRS\b|W-2|W-4|1099|Standard\s*Deduction|공제|환급/u',
        '금융' => '/은행|계좌|신용|크레딧|학자금|401\s*\(?k\)?|\bIRA\b|투자|저축|대출|\bLoan\b|환율|주식/u',
        '통신' => '/휴대폰|이통사|알뜰폰|통신사/u',
        '날씨·안전' => '/허리케인|폭염|폭풍|한파|재난|사기|스캠|범죄/u',
        '창업·비즈니스' => '/창업|\bLLC\b|사업자|비즈니스/u',
        '교육' => '/학교|캠프|방과후|유치원|대학\s*입시/u',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $articles = Article::all(['id', 'title', 'category']);

        $counts = [];
        $changed = 0;

        foreach ($articles as $article) {
            $newCategory = '생활정보';

            foreach (self::RULES as $name => $pattern) {
                if (preg_match($pattern, $article->title)) {
                    $newCategory = $name;
                    break;
                }
            }

            $counts[$newCategory] = ($counts[$newCategory] ?? 0) + 1;

            if ($newCategory !== $article->category) {
                $changed++;
                $this->line("{$article->title}  [{$article->category}] → [{$newCategory}]");

                if (! $dryRun) {
                    $article->update(['category' => $newCategory]);
                }
            }
        }

        $this->newLine();
        foreach ($counts as $name => $count) {
            $this->line("{$name}: {$count}");
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run — {$changed} of {$articles->count()} articles would change category. Re-run without --dry-run to apply.");
        } else {
            $this->info("Done — {$changed} of {$articles->count()} articles updated.");
        }

        return self::SUCCESS;
    }
}
