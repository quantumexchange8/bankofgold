<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('duplicate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('duplicate_record_id');
            $table->string('related_table');
            $table->unsignedBigInteger('related_record_id');
            $table->timestamps();

            // Unique to avoid linking same duplicate to same record more than once
            $table->unique(['duplicate_record_id', 'related_table', 'related_record_id'], 'duplicate_link_unique');
            
            // Foreign keys
            $table->foreign('duplicate_record_id')->references('id')->on('duplicate_records')->cascadeOnDelete();
            
            // Indexes
            $table->index(['related_table', 'related_record_id']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_links');
    }
};
