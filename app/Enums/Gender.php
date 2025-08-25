<?php

namespace App\Enums;

enum Gender: string
{
    // http://hl7.org/fhir/administrative-gender
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case Unknown = 'unknown';
}
