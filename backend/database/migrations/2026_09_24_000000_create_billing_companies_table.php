<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_number', 50)->unique();
            $table->string('name');
            $table->string('cuit', 11)->unique();
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('billing_company_id')->nullable()->constrained('billing_companies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_company_id');
        });
        Schema::dropIfExists('billing_companies');
    }
};
