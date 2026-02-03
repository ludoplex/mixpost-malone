<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixpost_entities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('hex_color', 7)->default('#6366f1');
            $table->json('media')->nullable(); // logo
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('mixpost_accounts', function (Blueprint $table) {
            $table->foreignId('entity_id')
                ->nullable()
                ->after('id')
                ->constrained('mixpost_entities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mixpost_accounts', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });

        Schema::dropIfExists('mixpost_entities');
    }
};
