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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('category')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable(); // e.g., 'pcs', 'kg', 'liters'
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'bank_transfer','e-wallet'])->default('cash');
            $table->decimal('discount', 10, 2)->default(0.00); // Optional discount field
            $table->decimal('tax', 10, 2)->default(0.00); // Optional tax field
            $table->decimal('total_amount', 10, 2)->virtualAs('amount * quantity  - discount + tax'); // Calculated field for total amount
            $table->string('receipt_image')->nullable();
            $table->string('status')->default('pending'); // e.g., pending, approved, rejected
            $table->foreignId(('created_by'))
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('updated_by')
                ->constrained('users')
                ->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
