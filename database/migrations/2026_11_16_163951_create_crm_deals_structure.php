<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->string('title');
            $table->decimal('value', 14, 2);
            $table->string('stage');
            $table->string('pipeline');
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('deal_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();
            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['deal_id', 'contact_id']);
        });

        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();
            $table->string('external_id');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->timestamps();

            $table->unique(['deal_id', 'external_id']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();
            $table->string('external_id');
            $table->string('type');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->unique(['deal_id', 'external_id']);
        });

        Schema::create('deal_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();
            $table->string('field_key');
            $table->json('field_value')->nullable();
            $table->timestamps();

            $table->unique(['deal_id', 'field_key']);
        });

        Schema::create('metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')
                ->constrained('deals')
                ->cascadeOnDelete();
            $table->string('meta_key');
            $table->json('meta_value')->nullable();
            $table->timestamps();

            $table->unique(['deal_id', 'meta_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata');
        Schema::dropIfExists('deal_custom_fields');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('line_items');
        Schema::dropIfExists('deal_contact');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('organizations');
    }
};
