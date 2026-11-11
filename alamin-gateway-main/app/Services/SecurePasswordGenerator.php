<?php

namespace App\Services;

class SecurePasswordGenerator
{
    private const LETTERS = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    private const NUMBERS = '23456789';

    public function generate(int $letters = 12, int $numbers = 4): string
    {
        $characters = [];

        for ($index = 0; $index < $letters; $index++) {
            $characters[] = $this->randomCharacter(self::LETTERS);
        }

        for ($index = 0; $index < $numbers; $index++) {
            $characters[] = $this->randomCharacter(self::NUMBERS);
        }

        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swapIndex = random_int(0, $index);
            [$characters[$index], $characters[$swapIndex]] = [$characters[$swapIndex], $characters[$index]];
        }

        return implode('', $characters);
    }

    private function randomCharacter(string $alphabet): string
    {
        return $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
}
