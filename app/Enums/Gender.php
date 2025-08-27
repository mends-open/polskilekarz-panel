<?php

namespace App\Enums;

enum Gender
{
    // http://hl7.org/fhir/administrative-gender
    case Male;
    case Female;
    case Other;
    case Unknown;
}
