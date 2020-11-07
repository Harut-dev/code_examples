<?php

namespace App\Services;

use App\Http\Requests\TeamsListRequest;
use App\Models\Team;
use App\Repositories\TeamRepository;
use App\Services\Interfaces\TeamServiceInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class TeamService extends BaseService implements TeamServiceInterface
{
    /**
     * TeamService constructor.
     *
     * @param TeamRepository $teamRepository
     */
    public function __construct(TeamRepository $teamRepository)
    {
        parent::__construct($teamRepository);
    }

    /**
     * @param int $id
     * @return Model|null
     */
    public function teamByIdWithMembers(int $id): ?Model
    {
        return $this->modelRepository->teamByIdWithMembers($id);
    }

    /**
     * @param TeamsListRequest $request
     * @return array|LengthAwarePaginator
     */
    public function list(TeamsListRequest $request): LengthAwarePaginator
    {
        $pageSize = isset($request->pageSize) ? $request->pageSize : env('DEFAULT_PER_PAGE');
        $loggedUser = $request->user();
        $itemsQuery = null;
        $items = [];
        $status = $request->status !== null ? $request->status : null;
        if ($status === null) {
            $itemsQuery = $this->modelRepository->queryWithMembersProjects();
        } else {
            $itemsQuery = $this->modelRepository->queryByStatusWithMembers($status);
        }

        // filter by user name
        if (!empty($request->name)) {
            $itemsQuery->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // filter by statues
        if (isset($request->statuses)) {
            $statuses = !is_array($request->statuses) ? [$request->statuses] : $request->statuses;
            $itemsQuery->whereIn('status', $statuses);
        }

        // filter by created_at
        if (!empty($request->created_at)) {
            $createdAtArr = $request->created_at;
            $fromCreatedAtDate = null;
            $toCreatedAtDate = null;

            if (isset($createdAtArr[0])) {
                $fromCreatedAtDate = Carbon::parse($createdAtArr[0], $loggedUser->time_offset)
                    ->startOfDay()
                    ->timezone('UTC')
                    ->format('Y-m-d H:i:s');
            }

            if (isset($createdAtArr[1])) {
                $toCreatedAtDate = Carbon::parse($createdAtArr[1], $loggedUser->time_offset)
                    ->endOfDay()
                    ->timezone('UTC')
                    ->format('Y-m-d H:i:s');
            }

            if ($fromCreatedAtDate && $toCreatedAtDate) {
                $itemsQuery->whereBetween('created_at', [$fromCreatedAtDate, $toCreatedAtDate]);
            } else {
                if ($fromCreatedAtDate === null && $toCreatedAtDate) {
                    $itemsQuery->where('created_at', '<=', $toCreatedAtDate);
                } else {
                    if ($fromCreatedAtDate && $toCreatedAtDate === null) {
                        $itemsQuery->where('created_at', '>=', $fromCreatedAtDate);
                    }
                }
            }
        }

        if ($itemsQuery !== null) {
            $items = $itemsQuery->paginate($pageSize);
        }

        return $items;
    }

    /**
     * @param array $userIds
     * @param string $startDateTime
     * @param string $endDateTime
     * @return mixed
     */
    public function userWorkDurations(array $userIds, string $startDateTime, string $endDateTime)
    {
        return $this->modelRepository->userWorkDurations($userIds, $startDateTime, $endDateTime);
    }
}
