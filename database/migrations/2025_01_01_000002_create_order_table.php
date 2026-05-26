<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->string('channel')->default('unknown');
            $table->timestamp('date')->useCurrent();
            $table->enum('status', ['pending', 'paid', 'shipped']);
        });
    }
    public function down(): void { Schema::dropIfExists('order'); }
};
