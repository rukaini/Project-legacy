<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PublicShortLink
{
    public static function urlFor(string $type, string $target): string
    {
        if (!in_array($type, ['attendance', 'feedback_participant', 'feedback_pic'], true)) {
            throw new \InvalidArgumentException('Unsupported short link type');
        }
        if (!Schema::hasTable('trainhub_short_links')) {
            throw new \RuntimeException('Run database_patches/2026_10_19_trainhub_features.sql to enable short links.');
        }
        $row = DB::table('trainhub_short_links')->where('link_type', $type)
            ->where('target_id', $target)->first(['token']);
        if ($row) return url('/'.$row->token);
        for ($i = 0; $i < 3; $i++) {
            $token = match ($type) {
                'attendance' => 'a/',
                'feedback_participant' => 'f/',
                'feedback_pic' => 'p/',
            } . bin2hex(random_bytes(12));
            try {
                DB::table('trainhub_short_links')->insert([
                    'token' => $token, 'link_type' => $type, 'target_id' => $target,
                    'created_at' => now(),
                ]);
                return url('/'.$token);
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1062) throw $e;
                $other = DB::table('trainhub_short_links')->where('link_type', $type)
                    ->where('target_id', $target)->value('token');
                if ($other) return url('/'.$other);
            }
        }
        throw new \RuntimeException('Unable to allocate a short link');
    }
}
