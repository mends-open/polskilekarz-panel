<?php

namespace App\Enums\Patient;

use Filament\Support\Contracts\HasLabel;

enum Identifier: string implements HasLabel
{
    case Unspecified = 'unspecified';
    // Document Identifiers
    case IdentityDocument = 'identity_document'; // Country agnostic
    case Passport = 'passport'; // Self-explanatory
    case DriversLicense = 'drivers_license'; // Self-explanatory
    case EHIC = 'ehic'; // European Health Insurance Card

    // National Identifiers
    case PESEL = 'pesel'; // Powszechny Elektroniczny System Ewidencji Ludności, PL
    case BSN = 'bsn'; // Burgerservicenummer, NL
    case IdNr = 'id_nr'; // Die Identifikationsnummer, DE
    case BIS = 'bis'; // BIS-nummer, BE
    case NIR = 'nir'; // Numéro d'Inscription au Répertoire, FR
    case NUSS = 'nuss'; // Número de la Seguridad Social, ES
    case CodiceFiscale = 'codice_fiscale'; // Codice Fiscale, IT

    public function getLabel(): ?string
    {
        return __('patient.identifier.' . $this->value);
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
