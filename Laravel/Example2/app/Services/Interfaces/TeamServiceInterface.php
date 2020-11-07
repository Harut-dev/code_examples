<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface TeamServiceInterface
{
    /**
     * @param int $id
     * @return Model|null
     */
    public function teamByIdWithMembers(int $id): ?Model;

    /**
     * @param array $userIds
     * @param string $startDateTime
     * @param string $endDateTime
     * @return mixed
     */
    public function userWorkDurations(array $userIds, string $startDateTime, string $endDateTime);
}
