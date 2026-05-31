<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('users')
            ->join('agents', 'users.agent_id', '=', 'agents.id')
            ->select([
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
                'agents.prenom',
                'agents.nom',
                'agents.postnom',
                'agents.email_professionnel',
                'agents.email',
                'agents.email_prive',
            ])
            ->orderBy('users.id')
            ->get();

        foreach ($rows as $row) {
            $updates = [];
            $name = trim(collect([$row->prenom, $row->nom, $row->postnom])
                ->filter(fn ($part) => filled($part))
                ->implode(' '));

            if ($name !== '' && $name !== $row->user_name) {
                $updates['name'] = $name;
            }

            $email = $this->firstValidEmail([
                $row->email_professionnel,
                $row->email,
                $row->email_prive,
            ]);

            if (
                $email
                && strtolower((string) $row->user_email) !== $email
                && !DB::table('users')->where('email', $email)->where('id', '!=', $row->user_id)->exists()
            ) {
                $updates['email'] = $email;
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('users')->where('id', $row->user_id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        //
    }

    private function firstValidEmail(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $email = strtolower(trim((string) $candidate));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }
};
