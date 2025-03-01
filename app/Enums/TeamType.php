<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TeamType: string implements HasLabel
{
    case KANTOR_PUSAT = 'kantor_pusat';
    case KANTOR_REGIONAL = 'kantor_regional';
    case KANTOR_CABANG_UTAMA = 'kantor_cabang_utama';
    case KANTOR_CABANG = 'kantor_cabang';
    case KANTOR_CABANG_PEMBANTU = 'kantor_cabang_pembantu';

    /**
     * Get a human-readable label for the enum.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::KANTOR_PUSAT => 'Kantor Pusat',
            self::KANTOR_REGIONAL => 'Kantor Regional',
            self::KANTOR_CABANG_UTAMA => 'Kantor Cabang Utama',
            self::KANTOR_CABANG => 'Kantor Cabang',
            self::KANTOR_CABANG_PEMBANTU => 'Kantor Cabang Pembantu',
        };
    }

    /**
     * Get allowed child types for each team type.
     */
    public function allowedChildren(): array
    {
        return match ($this) {
            self::KANTOR_PUSAT =>
            [
                self::KANTOR_PUSAT->value,
                self::KANTOR_REGIONAL->value,
                self::KANTOR_CABANG_UTAMA->value,
                self::KANTOR_CABANG->value,
                self::KANTOR_CABANG_PEMBANTU->value
            ],
            self::KANTOR_REGIONAL =>
            [
                self::KANTOR_CABANG_UTAMA->value,
            ],
            self::KANTOR_CABANG_UTAMA =>
            [
                self::KANTOR_CABANG->value,
                self::KANTOR_CABANG_PEMBANTU->value
            ],
        };
    }
}
