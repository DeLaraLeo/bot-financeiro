<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->integer('amount_cents');
            $table->char('currency', 3)->default('BRL');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->string('status', 16)->default('confirmed');
            $table->string('source_message_id')->unique();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'category_id', 'occurred_at']);
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
