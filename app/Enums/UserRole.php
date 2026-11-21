<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Registrar = 'registrar';
    case Accounting = 'accounting';
    case Faculty = 'faculty';
    case Student = 'student';
}