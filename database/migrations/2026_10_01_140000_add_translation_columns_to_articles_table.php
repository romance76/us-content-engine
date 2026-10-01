<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('excerpt_en')->nullable()->after('excerpt');
            $table->longText('body_en')->nullable()->after('body');
            $table->string('meta_title_en')->nullable()->after('meta_title');
            $table->string('meta_description_en')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'excerpt_en', 'body_en', 'meta_title_en', 'meta_description_en']);
        });
    }
};
