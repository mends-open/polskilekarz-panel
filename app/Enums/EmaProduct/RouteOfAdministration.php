<?php

namespace App\Enums\EmaProduct;

use Illuminate\Support\Str;

enum RouteOfAdministration: int
{
    case Unspecified = 0;
    case Auricular = 1;
    case Buccal = 2;
    case Cutaneous = 3;
    case Dental = 4;
    case Endocervical = 5;
    case Endosinusial = 6;
    case Endotracheopulmonary = 7;
    case Epidural = 8;
    case Epilesional = 9;
    case EnteralFeedingTube = 10;
    case Enteral = 11;
    case Extracorporeal = 12;
    case Gastric = 13;
    case Gastroenteral = 14;
    case Gingival = 15;
    case Haemodialysis = 16;
    case Implantation = 17;
    case Infiltration = 18;
    case Infusion = 19;
    case Injection = 20;
    case InhalationGas = 21;
    case Inhalation = 22;
    case Intraamniotic = 23;
    case Intraarterial = 24;
    case Intrabursal = 25;
    case Intracameral = 26;
    case Intracardiac = 27;
    case Intracavernous = 28;
    case Intracerebral = 29;
    case Intracerebroventricular = 30;
    case Intracervical = 31;
    case Intracisternal = 32;
    case Intracoronary = 33;
    case Intradermal = 34;
    case Intradiscal = 35;
    case Intraepidermal = 36;
    case Intraglandular = 37;
    case Intralesional = 38;
    case Intralymphatic = 39;
    case Intramuscular = 40;
    case Intraocular = 41;
    case Intraosseous = 42;
    case Intrapericardial = 43;
    case Intraperitoneal = 44;
    case Intraprostatic = 45;
    case Intrapleural = 46;
    case Intraputaminal = 47;
    case Intraretinal = 48;
    case Intrathecal = 49;
    case Intratumoral = 50;
    case Intrauterine = 51;
    case Intravascular = 52;
    case IntravenousBolusInjection = 53;
    case IntravenousBolus = 54;
    case IntravenousDrip = 55;
    case IntravenousInfusion = 57;
    case IntravenousInjection = 58;
    case IntravenousPerfusion = 59;
    case Intravenous = 60;
    case Intraventricular = 61;
    case Intravitreal = 62;
    case Local = 63;
    case Nasal = 64;
    case Ocular = 65;
    case Ophthalmic = 67;
    case Oral = 68;
    case Oromucosal = 70;
    case Oropharyngeal = 72;
    case Parenteral = 73;
    case Percutaneous = 74;
    case Periarticular = 75;
    case Perineural = 76;
    case Periosseous = 77;
    case Peritumoral = 78;
    case Rectal = 79;
    case Retrobulbar = 80;
    case RouteOfAdministrationNotApplicable = 81;
    case SkinScarification = 82;
    case SolutionForInfusion = 83;
    case SolutionForInjection = 84;
    case Subconjunctival = 85;
    case Subcutaneous = 86;
    case Submucosal = 87;
    case Sublingual = 88;
    case Subretinal = 89;
    case Topical = 90;
    case TopicalApplication = 91;
    case TopicalApplicationOnWound = 92;
    case Transdermal = 93;
    case Urethral = 94;
    case Vaginal = 95;

    public static function tryFromName(string $name): ?self
    {
        $caseName = Str::of($name)
            ->replace(['(', ')', '-', ',', '/', '.'], ' ')
            ->replaceMatches('/\buse\b/i', '')
            ->squish()
            ->studly()
            ->toString();

        foreach (self::cases() as $case) {
            if ($case->name === $caseName) {
                return $case;
            }
        }

        return null;
    }
}
