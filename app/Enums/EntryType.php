<?php

namespace App\Enums;

enum EntryType
{
    // Operational (internal)
    case Task;
    case Appointment;
    case Note;
    case Warning;
    case Danger;

    // Clinical narratives
    case Interview;
    case Observation;
    case Condition;
    case Allergy;
    case Pregnancy;
    case Lactation;
    case Recommendation;
    case MedicalCertificate;
    case SickLeave;
    case CrossBorderPrescription;
    case Referral;
    case Attachment;
}
