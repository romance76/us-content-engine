<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('term')->unique();
            $table->string('source')->nullable(); // e.g. search_console, reddit, manual
            $table->unsignedInteger('search_volume')->nullable();
            $table->string('competition')->nullable();
            $table->string('status')->default('new'); // new, queued, used, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
