<?php

namespace App\Repositories;

use App\Models\Team;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamRepository extends BaseRepository implements TeamRepositoryInterface
{

    /**
     * TeamRepository constructor.
     *
     * @param Team $model
     */
    public function __construct(Team $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Builder
     */
    public function queryWithMembersProjects(): Builder
    {
        return $this->model->with('members')->with('projects');
    }

    /**
     * @param int $status
     * @return Builder
     */
    public function queryByStatusWithMembers(int $status): Builder
    {
        return $this->model->where('status', $status)->with('members');
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function teamByIdWithMembers(int $id): ?Model
    {
        return $this->model->where('id', $id)->with('members')->first();
    }

    /**
     * @param array $userIds
     * @param string $startDateTime
     * @param string $endDateTime
     * @return mixed
     */
    public function userWorkDurations(array $userIds, string $startDateTime, string $endDateTime)
    {
        return $this->model->getUserWorkDurations($userIds, $startDateTime, $endDateTime);
    }
}
