<?php

namespace App\Enums\EmaProduct;

use Illuminate\Support\Str;

enum RouteOfAdministration: int
{
    case Unspecified = 0;
    case AuricularUse = 1;
    case BuccalUse = 2;
    case CutaneousUse = 3;
    case DentalUse = 4;
    case EndocervicalUse = 5;
    case EndosinusialUse = 6;
    case EndotracheopulmonaryUse = 7;
    case EpiduralUse = 8;
    case EpilesionalUse = 9;
    case EnteralFeedingTube = 10;
    case EnteralUse = 11;
    case ExtracorporealUse = 12;
    case GastricUse = 13;
    case GastroenteralUse = 14;
    case GingivalUse = 15;
    case Haemodialysis = 16;
    case Implantation = 17;
    case Infiltration = 18;
    case Infusion = 19;
    case Injection = 20;
    case InhalationGas = 21;
    case InhalationUse = 22;
    case IntraamnioticUse = 23;
    case IntraarterialUse = 24;
    case IntrabursalUse = 25;
    case IntracameralUse = 26;
    case IntracardiacUse = 27;
    case IntracavernousUse = 28;
    case IntracerebralUse = 29;
    case IntracerebroventricularUse = 30;
    case IntracervicalUse = 31;
    case IntracisternalUse = 32;
    case IntracoronaryUse = 33;
    case IntradermalUse = 34;
    case IntradiscalUse = 35;
    case IntraepidermalUse = 36;
    case IntraglandularUse = 37;
    case IntralesionalUse = 38;
    case IntralymphaticUse = 39;
    case IntramuscularUse = 40;
    case IntraocularUse = 41;
    case IntraosseousUse = 42;
    case IntrapericardialUse = 43;
    case IntraperitonealUse = 44;
    case IntraprostaticUse = 45;
    case IntrapleuralUse = 46;
    case IntraputaminalUse = 47;
    case IntraretinalUse = 48;
    case IntrathecalUse = 49;
    case IntratumoralUse = 50;
    case IntrauterineUse = 51;
    case IntravascularUse = 52;
    case IntravenousBolusInjection = 53;
    case IntravenousBolusUse = 54;
    case IntravenousDrip = 55;
    case IntravenousDripUse = 56;
    case IntravenousInfusion = 57;
    case IntravenousInjection = 58;
    case IntravenousPerfusionUse = 59;
    case IntravenousUse = 60;
    case IntraventricularUse = 61;
    case IntravitrealUse = 62;
    case LocalUse = 63;
    case NasalUse = 64;
    case OcularUse = 65;
    case OphthalmicUse = 66;
    case Ophthalmic = 67;
    case Oral = 68;
    case OralUse = 69;
    case Oromucosal = 70;
    case OromucosalUse = 71;
    case OropharyngealUse = 72;
    case ParenteralUse = 73;
    case PercutaneousUse = 74;
    case PeriarticularUse = 75;
    case PerineuralUse = 76;
    case PeriosseousUse = 77;
    case PeritumoralUse = 78;
    case RectalUse = 79;
    case RetrobulbarUse = 80;
    case RouteOfAdministrationNotApplicable = 81;
    case SkinScarification = 82;
    case SolutionForInfusion = 83;
    case SolutionForInjection = 84;
    case SubconjunctivalUse = 85;
    case SubcutaneousUse = 86;
    case SubmucosalUse = 87;
    case SublingualUse = 88;
    case SubretinalUse = 89;
    case Topical = 90;
    case TopicalApplication = 91;
    case TopicalApplicationOnWound = 92;
    case TransdermalUse = 93;
    case UrethralUse = 94;
    case VaginalUse = 95;

    public static function tryFromName(string $name): ?self
    {
        $caseName = Str::of($name)
            ->replace(['(', ')', '-', ',', '/', '.'], ' ')
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
