<?php

namespace App\Helpers;

if (!function_exists('enum_values')) {
  function enum_values($enumClass)
  {
    return array_map(fn($case) => $case->value, $enumClass::cases());
  }

}
