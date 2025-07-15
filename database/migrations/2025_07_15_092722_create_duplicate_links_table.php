<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('duplicate_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duplicate_record_id')->constrained('duplicate_records')->cascadeOnDelete();
            $table->string('related_table');
            $table->unsignedBigInteger('related_record_id');

            $table->timestamps();

            $table->unique(['duplicate_record_id', 'related_table', 'related_record_id'], 'duplicate_link_unique');
            $table->index(['related_table', 'related_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_links');
    }
};
