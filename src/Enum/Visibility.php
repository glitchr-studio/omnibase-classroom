<?php

namespace Base\Classroom\Enum;

/** Who may download a resource: anyone, the members, or whoever bought it. */
enum Visibility: string
{
    case FREE = 'free';
    case MEMBERS = 'members';
    case PAID = 'paid';
}
