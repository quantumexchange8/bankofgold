<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('core_leads', function (Blueprint $table) {
            $table->id();

            // Meta and source info
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('lead_id')->nullable();
            $table->string('categories')->nullable();
            $table->date('date_added')->nullable();
            $table->string('referrer')->nullable();

            // Personal info
            $table->string('first_name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->json('duplicate_ids')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete(); // or ->nullOnDelete(), etc.
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_leads');
    }
};
