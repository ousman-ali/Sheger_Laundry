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
        Schema::create('test_cloth_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('item_code')->unique();
            $table->string('name')->unique();
            $table->unsignedBigInteger('unit_id');
            $table->decimal('price', 10, 2)->default(0); // ✅ new price column
            $table->text('description')->nullable();
            $table->unsignedBigInteger('clothing_group_id')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')
                  ->references('id')
                  ->on('units')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_cloth_items');
    }
};
