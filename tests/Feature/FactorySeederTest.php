<?php

use App\Enums\Appointment\AppointmentType;
use App\Models\Appointment;
use App\Models\Email;
use App\Models\EmailPatient;
use App\Models\Entity;
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
        $table->softDeletesTz();
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
        $table->softDeletesTz();
    });

    Schema::create('appointments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id');
        $table->foreignId('user_id');
        $table->string('type');
        $table->smallInteger('duration');
        $table->dateTimeTz('scheduled_at');
        $table->dateTimeTz('confirmed_at')->nullable();
        $table->dateTimeTz('started_at')->nullable();
        $table->dateTimeTz('cancelled_at')->nullable();
        $table->timestampsTz();
        $table->softDeletesTz();
    });
});

afterEach(function () {
    Schema::drop('appointments');
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

it('creates appointment via factory', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->patient)->not->toBeNull();
    expect($appointment->user)->not->toBeNull();
    expect(AppointmentType::tryFrom($appointment->type))->not->toBeNull();
});

it('creates email patient pivot via factory', function () {
    EmailPatient::factory()->create();

    expect(EmailPatient::count())->toBe(1);
});

it('creates patient phone pivot via factory', function () {
    PatientPhone::factory()->create();

    expect(PatientPhone::count())->toBe(1);
});

it('seeds patient with contacts and appointment', function () {
    (new DatabaseSeeder())->run();

    expect(User::count())->toBe(1);
    expect(Entity::count())->toBe(1);
    $patient = Patient::first();
    expect($patient)->not->toBeNull();
    expect($patient->emails()->count())->toBe(1);
    expect($patient->phones()->count())->toBe(1);
    expect(Appointment::count())->toBe(1);
    expect(AppointmentType::tryFrom(Appointment::first()->type))->toBe(AppointmentType::General);
});

