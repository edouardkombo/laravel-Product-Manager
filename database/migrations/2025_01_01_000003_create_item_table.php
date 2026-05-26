<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order')->constrained('order')->cascadeOnDelete();
            $table->foreignId('product')->constrained('product');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
        });
    }
    public function down(): void { Schema::dropIfExists('item'); }
};
