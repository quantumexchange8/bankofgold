<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();

            // Meta information
            $table->string('table_name')->nullable();       // e.g., 'core_leads'
            $table->string('file_name')->nullable();        // Original uploaded filename

            // Processing status and statistics
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->string('status')->default('processing'); // processing / completed / failed

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('table_name');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
