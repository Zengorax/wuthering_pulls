<?php

namespace App\Enum;

enum Element: string
{
    case Glacio = 'glacio';
    case Fusion = 'fusion';
    case Electro = 'electro';
    case Aero = 'aero';
    case Spectro = 'spectro';
    case Havoc = 'havoc';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
