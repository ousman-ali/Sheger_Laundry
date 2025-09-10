<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->unsignedBigInteger('parent_unit_id')->nullable();
            $table->decimal('conversion_factor', 10, 4)->nullable();
            $table->timestamps();

            $table->foreign('parent_unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
