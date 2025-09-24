<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clothing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // group name
            $table->text('description')->nullable(); // optional description
            $table->foreignId('user_id')
                ->unique() // enforce one-to-one
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clothing_groups');
    }
};
