<?php

return [
    'label' => 'Appointment',
    'plural' => 'Appointments',
    'type' => [
        'general' => 'General',
        'psychiatric' => 'Psychiatric',
        'psychological' => 'Psychological',
        'prescription' => 'Prescription',
        'documentation' => 'Documentation',
    ],
    'fields' => [
        'patient_id' => 'Patient',
        'user_id' => 'User',
        'type' => 'Type',
        'duration' => 'Duration',
        'scheduled_at' => 'Scheduled at',
        'confirmed_at' => 'Confirmed at',
        'started_at' => 'Started at',
        'cancelled_at' => 'Cancelled at',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'deleted_at' => 'Deleted at',
    ],
];
