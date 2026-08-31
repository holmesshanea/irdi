<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillCharterMembersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            /*
             * member_since indicates that the account actually became
             * an IRDI member at some point.
             *
             * We include currently active, suspended, or banned members
             * because suspension/ban should not erase Charter Member
             * recognition that was earned earlier.
             */
            $charterMemberIds = User::query()
                ->whereNotNull('member_since')
                ->orderBy('member_since')
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit(1000)
                ->pluck('id');

            User::query()
                ->whereIn('id', $charterMemberIds)
                ->update([
                    'is_charter_member' => true,
                ]);
        });
    }
}
