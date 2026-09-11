<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merge_group_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merge_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['merge_group_id', 'import_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merge_group_imports');
    }
};
