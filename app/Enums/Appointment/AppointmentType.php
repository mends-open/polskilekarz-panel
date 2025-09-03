<?php

namespace App\Enums\Appointment;

enum AppointmentType
{
    case General;
    case Psychiatric;
    case Psychological;
    case Prescription;
    case Documentation;

}
