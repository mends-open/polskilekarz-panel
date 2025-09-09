<?php

use App\Models\DocumentEntry;
use App\Models\Email;
use App\Models\EmailPatient;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\Patient;
use App\Models\PatientPhone;
use App\Models\Phone;
use App\Models\User;
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

    Schema::create('entities', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->json('headers')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('entity_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('entity_id');
        $table->foreignId('user_id');
        $table->timestamps();
    });

    Schema::create('patients', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->date('birth_date');
        $table->smallInteger('gender');
        $table->json('addresses')->nullable();
        $table->json('identifiers')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('emails', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('email_patient', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('email_id');
        $table->dateTimeTz('primary_since')->nullable();
        $table->dateTimeTz('message_consent_since')->nullable();
        $table->timestampsTz();
    });

    Schema::create('phones', function (Blueprint $table) {
        $table->id();
        $table->string('phone');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('patient_phone', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('phone_id');
        $table->dateTimeTz('primary_since')->nullable();
        $table->dateTimeTz('call_consent_since')->nullable();
        $table->dateTimeTz('sms_consent_since')->nullable();
        $table->dateTimeTz('whatsapp_consent_since')->nullable();
        $table->timestampsTz();
    });

    Schema::create('entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('user_id');
        $table->foreignId('entity_id');
        $table->smallInteger('type');
        $table->json('data')->nullable();
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('user_id');
        $table->foreignId('entity_id');
        $table->timestampsTz();
        $table->softDeletesTz();
    });

    Schema::create('document_entry', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id');
        $table->foreignId('entry_id');
        $table->timestampsTz();
    });
});

afterEach(function () {
    Schema::drop('document_entry');
    Schema::drop('documents');
    Schema::drop('entries');
    Schema::drop('patient_phone');
    Schema::drop('phones');
    Schema::drop('email_patient');
    Schema::drop('emails');
    Schema::drop('patients');
    Schema::drop('entity_user');
    Schema::drop('entities');
    Schema::drop('users');
});

it('creates email via factory', function () {
    Email::factory()->create();

    expect(Email::count())->toBe(1);
});

it('creates phone via factory', function () {
    Phone::factory()->create();

    expect(Phone::count())->toBe(1);
});

it('creates email patient pivot via factory', function () {
    EmailPatient::factory()->create();

    expect(EmailPatient::count())->toBe(1);
});

it('creates patient phone pivot via factory', function () {
    PatientPhone::factory()->create();

    expect(PatientPhone::count())->toBe(1);
});

it('creates entity user pivot via factory', function () {
    EntityUser::factory()->create();

    expect(EntityUser::count())->toBe(1);
});

it('seeds patients with contacts', function () {
    (new DatabaseSeeder)->run();

    expect(User::count())->toBe(3);
    expect(Entity::count())->toBe(1);
    expect(Patient::count())->toBe(10);
    expect(Email::count())->toBe(10);
    expect(Phone::count())->toBe(10);
    $patient = Patient::first();
    expect($patient)->not->toBeNull();
    expect($patient->emails()->count())->toBe(1);
    expect($patient->phones()->count())->toBe(1);
});

it('deletes email patient pivot', function () {
    $pivot = EmailPatient::factory()->create();
    $patient = $pivot->patient;

    expect($patient->emails()->count())->toBe(1);

    $pivot->delete();

    expect(EmailPatient::count())->toBe(0);

    $patient->refresh();
    expect($patient->emails()->count())->toBe(0);
});

it('deletes patient phone pivot', function () {
    $pivot = PatientPhone::factory()->create();
    $patient = $pivot->patient;

    expect($patient->phones()->count())->toBe(1);

    $pivot->delete();

    expect(PatientPhone::count())->toBe(0);

    $patient->refresh();
    expect($patient->phones()->count())->toBe(0);
});

it('deletes document entry pivot', function () {
    $pivot = DocumentEntry::factory()->create();
    $document = $pivot->document;

    expect($document->entries()->count())->toBe(1);

    $pivot->delete();

    expect(DocumentEntry::count())->toBe(0);

    $document->refresh();
    expect($document->entries()->count())->toBe(0);
});

it('deletes entity user pivot', function () {
    $pivot = EntityUser::factory()->create();
    $entity = $pivot->entity;

    expect($entity->users()->count())->toBe(1);

    $pivot->delete();

    expect(EntityUser::count())->toBe(0);

    $entity->refresh();
    expect($entity->users()->count())->toBe(0);
});
