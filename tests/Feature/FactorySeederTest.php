<?php

use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Entry;
use Database\Seeders\DatabaseSeeder;
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

    Schema::create('entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('user_id');
        $table->foreignId('entity_id');
        $table->string('type');
        $table->json('data')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('user_id');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('document_entry', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id');
        $table->foreignId('entry_id');
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () {
    Schema::drop('document_entry');
    Schema::drop('documents');
    Schema::drop('entries');
    Schema::drop('entity_user');
    Schema::drop('entities');
    Schema::drop('patients');
    Schema::drop('users');
});

it('creates document via factory', function () {
    $document = Document::factory()->create();

    expect($document->patient)->not->toBeNull();
    expect($document->user)->not->toBeNull();
});

it('creates entry via factory', function () {
    $entry = Entry::factory()->create();

    expect($entry->patient)->not->toBeNull();
    expect($entry->user)->not->toBeNull();
    expect($entry->entity)->not->toBeNull();
});

it('creates document entry pivot via factory', function () {
    DocumentEntry::factory()->create();

    expect(DocumentEntry::count())->toBe(1);
});

it('seeds document with entries', function () {
    (new DatabaseSeeder())->run();

    expect(Document::count())->toBe(1);
    expect(Document::first()->entries()->count())->toBe(3);
});
