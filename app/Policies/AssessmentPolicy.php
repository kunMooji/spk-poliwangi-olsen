<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

/**
 * Calon mahasiswa hanya boleh menyentuh sesi tes miliknya sendiri.
 * Admin berhak melihat seluruh sesi tes untuk keperluan rekapitulasi.
 */
class AssessmentPolicy
{
    public function view(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin() || $assessment->user_id === $user->id;
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $assessment->user_id === $user->id;
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin() || $assessment->user_id === $user->id;
    }
}
