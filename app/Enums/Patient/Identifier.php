<?php

namespace App\Enums\Patient;

use Filament\Support\Contracts\HasLabel;

enum Identifier: int implements HasLabel
{
    case Unspecified = 0;
    // Document Identifiers
    case IdentityDocument = 1; // Country agnostic
    case Passport = 2; // Self-explanatory
    case DriversLicense = 3; // Self-explanatory
    case EHIC = 4; // European Health Insurance Card

    // National Identifiers
    case PESEL = 5; // Powszechny Elektroniczny System Ewidencji Ludności, PL
    case BSN = 6; // Burgerservicenummer, NL
    case IdNr = 7; // Die Identifikationsnummer, DE
    case BIS = 8; // BIS-nummer, BE
    case NIR = 9; // Numéro d'Inscription au Répertoire, FR
    case NUSS = 10; // Número de la Seguridad Social, ES
    case CodiceFiscale = 11; // Codice Fiscale, IT

    public function getLabel(): ?string
    {
        return __('patient.identifier.'.$this->value);
    }

    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->getLabel();
        }

        return $labels;
    }
}
