<?php

namespace App\Enums\Patient;

enum PatientGender
{
    // http://hl7.org/fhir/administrative-gender
    case Male;
    case Female;
    case Other;
    case Unknown;
}
