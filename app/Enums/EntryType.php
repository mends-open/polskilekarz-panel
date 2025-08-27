<?php

namespace App\Enums;

enum EntryType
{
    case Generic;

    // Operational (internal)
    case Task;
    case Note;
    case Warning;
    case Danger;

    // Clinical narratives
    case Interview;
    case Examination;

    // Clinical data
    case Condition;          // includes pregnancy, breastfeeding, addiction (coded)
    case Allergy;
    case Observation;        // vitals, scores, measurements
    case MedicationPlan;     // plan/history, not the Rx itself

    // Patient-facing outputs
    case Recommendation;
    case MedicalCertificate;
    case SickLeave;
    case CrossBorderPrescription;

    // External docs
    case Attachment;         // imported/uploaded document
    case Referral;
}
