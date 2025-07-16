<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('duplicate_records', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('field_name');
            $table->string('duplicate_value');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->index('table_name');
            $table->index('field_name');
            $table->index('duplicate_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_records');
    }
};
