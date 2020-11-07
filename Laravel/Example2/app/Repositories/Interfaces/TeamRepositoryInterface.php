<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface TeamRepositoryInterface
{
    /**
     * @return Builder
     */
    public function queryWithMembersProjects(): Builder;

    /**
     * @param int $status
     * @return Builder
     */
    public function queryByStatusWithMembers(int $status): Builder;

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
