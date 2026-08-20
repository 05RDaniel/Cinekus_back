<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('department', 20);
            $table->unique(['name', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
