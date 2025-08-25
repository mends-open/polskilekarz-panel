<?php

namespace App\Enums;

enum EntryType: string
{
    case Generic = 'generic';

    // Operational (internal)
    case Task = 'task';
    case Note = 'note';
    case Warning = 'warning';
    case Danger = 'danger';

    // Clinical narratives
    case Interview = 'interview';
    case Examination = 'examination';

    // Clinical data
    case Condition = 'condition';          // includes pregnancy, breastfeeding, addiction (coded)
    case Allergy = 'allergy';
    case Observation = 'observation';        // vitals, scores, measurements
    case MedicationPlan = 'medication_plan';     // plan/history, not the Rx itself

    // Patient-facing outputs
    case Recommendation = 'recommendation';
    case MedicalCertificate = 'medical_certificate';
    case SickLeave = 'sick_leave';
    case CrossBorderPrescription = 'cross_border_prescription';

    // External docs
    case Attachment = 'attachment';         // imported/uploaded document
    case Referral = 'referral';

}
