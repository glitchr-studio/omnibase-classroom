<?php

namespace Base\Classroom\Enum;

/** The five periods of the French school year, between the holidays. */
enum Period: string
{
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';
    case P4 = 'p4';
    case P5 = 'p5';

    public function number(): int
    {
        return (int) substr($this->value, 1);
    }
}
