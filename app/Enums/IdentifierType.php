<?php

namespace App\Enums;


enum IdentifierType
{
    // Document Identifiers
    case IdentityDocument; // Country agnostic
    case Passport; // Self-explanatory
    case DriversLicense; // Self-explanatory
    case EHIC; // European Health Insurance Card

    // National Identifiers
    case PESEL; // Powszechny Elektroniczny System Ewidencji Ludności, PL
    case BSN; // Burgerservicenummer, NL
    case IdNr; // Die Identifikationsnummer, DE
    case BIS; // BIS-nummer, BE
    case NIR; // Numéro d'Inscription au Répertoire, FR
    case NUSS; // Número de la Seguridad Social, ES
    case CodiceFiscale; // Codice Fiscale, IT
}
