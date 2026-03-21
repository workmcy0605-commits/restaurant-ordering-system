<?php

namespace App\Traits;

trait GenerateCode
{
    public function generateCode($object, $prefix)
    {
        $attempts = 0;

        do {
            $time = now()->format('His');
            $rand = str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $code = $prefix.$time.$rand;

            $attempts++;
            if ($attempts > 5) {
                throw new \Exception('Failed to generate unique code after 5 attempts.');
            }
        } while ($object->newQuery()->where('code', $code)->exists());

        $object->code = $code;

        return $object->code;
    }
}
