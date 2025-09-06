<?php

use App\Models\Document;
use Database\Seeders\DocumentSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('patients', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->date('birth_date');
        $table->string('gender');
        $table->json('addresses')->nullable();
        $table->json('identifiers')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('entities', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->json('headers')->nullable();
        $table->json('footers')->nullable();
        $table->json('stamps')->nullable();
        $table->json('logos')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('entity_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('entity_id');
        $table->foreignId('user_id');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('entity_id');
        $table->foreignId('user_id');
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () {
    Schema::drop('documents');
    Schema::drop('entity_user');
    Schema::drop('entities');
    Schema::drop('patients');
    Schema::drop('users');
});

it('seeds documents', function () {
    (new DocumentSeeder())->run();

    expect(Document::count())->toBe(5);
});
