<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');       // e.g., 'core_leads'
            $table->string('file_name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('table_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
