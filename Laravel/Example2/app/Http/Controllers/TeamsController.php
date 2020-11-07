<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamsListRequest;
use App\Http\Requests\TeamsStoreRequest;
use App\Http\Requests\TeamsUpdateRequest;
use App\Models\Permission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\Interfaces\TeamServiceInterface;
use App\Services\Interfaces\TeamMemberServiceInterface;
use App\Services\Interfaces\ProjectServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamsController extends Controller
{
    /**
     * @var UserServiceInterface
     */
    private $userService;

    /**
     * @var TeamServiceInterface
     */
    private $teamService;

    /**
     * @var TeamMemberServiceInterface
     */
    private $teamMemberService;

    /**
     * @var ProjectServiceInterface
     */
    private $projectService;

    /**
     * ApiAuthController constructor.
     * @param UserServiceInterface $userService
     * @param TeamServiceInterface $teamService
     * @param ProjectServiceInterface $projectService
     * @param TeamMemberServiceInterface $teamMemberService
     */
    public function __construct(
        UserServiceInterface $userService,
        TeamServiceInterface $teamService,
        ProjectServiceInterface $projectService,
        TeamMemberServiceInterface $teamMemberService
    ) {
        $this->userService = $userService;
        $this->teamService = $teamService;
        $this->projectService = $projectService;
        $this->projectService = $projectService;
        $this->teamMemberService = $teamMemberService;
    }

    /**
     * Display a listing of the resource.
     * @param TeamsListRequest $request
     * @return JsonResponse
     */
    public function list(TeamsListRequest $request)
    {
        $items = [];
        if ($request->user()->can('view teams')) {
            $items = $this->teamService->list($request);
        }
        return response()->json(['status' => true, 'message' => __('getting_teams'), 'teams' => $items]);
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request)
    {
        $items = [];
        if ($request->user()->can('view teams')) {
            $items = $this->userService->activeUsersWithTeams();
        }

        return response()->json(['status' => true, 'message' => __('getting_users_with_teams'), 'users' => $items]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function projects(Request $request)
    {
        $projects = [];
        if ($request->user()->can('view teams')) {
            $projects = $this->projectService->activeProjects();
        }
        return response()->json([
            'status' => true,
            'message' => __('getting_all_projects_for_teams'),
            'projects' => $projects
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TeamsStoreRequest $request
     * @return JsonResponse
     */
    public function store(TeamsStoreRequest $request)
    {
        $loggedUser = auth()->user();
        if ($loggedUser->can('create', Team::class)) // Here is working the TeamPolicy
        {
            if (!in_array($request['status'], [Team::INACTIVE, Team::ACTIVE, Team::ARCHIVED])) {
                return response()->json(['status' => false, 'message' => __('Invalid Team status'), 'team' => null]);
            }

            $item = new Team();
            $item->fill([
                "name" => $request['name'],
                "description" => isset($request['description']) ? $request['description'] : null,
                "status" => $request['status'],
                "user_id" => $loggedUser->id,
            ]);
            if ($item->save()) {
                $bulkInsertData = [];
                $modelPermissionInsertData = [];
                $nowTime = Carbon::now();
                $viewSelfTeamDetailId = Permission::where('name', 'view self team details')->first(['id']);
                $viewSpecificTeamDetailId = Permission::where('name', 'view specific team details')->first(['id']);
                $addNotes = Permission::where('name', 'add team note')->first(['id']);
                $viewNotes = Permission::where('name', 'view team note')->first(['id']);
                foreach ($request['members'] as $member) {
                    $member = (array)$member;
                    if (empty($member['user_id'])
                        || !isset($member['status'])
                        || !in_array($member['status'],
                            [TeamMember::DEVELOPER, TeamMember::PROJECT_MANAGER, TeamMember::TEAM_LEAD])) {
                        continue;
                    }
                    $bulkInsertData[] = [
                        "team_id" => $item->id,
                        "user_id" => $member['user_id'],
                        "status" => $member['status'],
                        "created_at" => $nowTime,
                        "updated_at" => $nowTime,
                    ];
                    $permissionId = null;
                    $permissions = [];
                    if ($member['status'] == TeamMember::DEVELOPER) {
                        $permissions = [
                            $viewSelfTeamDetailId->id,
                            $viewNotes->id,
                        ];
                    } elseif ($member['status'] == TeamMember::PROJECT_MANAGER || $member['status'] == TeamMember::TEAM_LEAD) {
                        $permissions = [
                            $viewSpecificTeamDetailId->id,
                            $addNotes->id,
                            $viewNotes->id,
                        ];
                    }
                    foreach ($permissions as $permission) {
                        $modelPermissionInsertData[] = [
                            "model_id" => $member['user_id'],
                            "permission_id" => $permission,
                            "model_type" => User::class
                        ];
                    }
                }
                $project_id = [];
                foreach ($request['project_id'] as $project) {
                    $project_id[] = [
                        "team_id" => $item->id,
                        "project_id" => $project,
                    ];
                }
                if (empty($project_id)) {
                    return response()->json(['status' => false, 'message' => __('invalid_user_roles'), 'user' => null]);
                } elseif (!empty($project_id)) { // TODO what is this?
                    \DB::table('team_has_projects')->insertOrIgnore($project_id);
                }
                if (!empty($bulkInsertData)) {
                    foreach (array_chunk($bulkInsertData, 1000) as $bulkInsert) { // for big data
                        TeamMember::insert($bulkInsertData);
                    }
                }

                if (!empty($modelPermissionInsertData)) {
                    \DB::table(config('permission.table_names.model_has_permissions'))->insertOrIgnore($modelPermissionInsertData);
                }
                $data = Team::where('id', $item->id)->with('members')->first();
                return response()->json([
                    'status' => true,
                    'message' => __('The Team added successfully'),
                    'data' => $data
                ]);
            }
            return response()->json(['status' => false, 'message' => __('Something went wrong'), 'data' => null]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $teamId
     * @return mixed
     */
    public function show(int $teamId)
    {
        // TODO should we add permission check?
        $team = $this->teamService->find($teamId);
        $loggedUser = auth()->user();
        $teamMembers = $this->teamService->teamByIdWithMembers($teamId);
        $userIds = $this->teamMemberService->userIdsByTeamId($teamId);
        //today
        $todayStartDateTime = Carbon::now()->startOfDay()->setTimezone($loggedUser->timezone)->toDateTimeString();
        $todayEndDateTime = Carbon::now()->startOfDay()->copy()->endOfDay()->setTimezone($loggedUser->timezone)->toDateTimeString();
        //month
        $monthStartDate = Carbon::now()->firstOfMonth()->copy()->endOfMonth()->setTimezone($loggedUser->timezone)->toDateTimeString();
        $monthEndDate = Carbon::now()->firstOfMonth()->setTimezone($loggedUser->timezone)->toDateTimeString();
        //week
        $weekStartDate = Carbon::now()->startOfWeek()->setTimezone($loggedUser->timezone)->toDateTimeString();
        $weekEndDate = Carbon::now()->startOfWeek()->copy()->endOfWeek()->setTimezone($loggedUser->timezone)->toDateTimeString();

        $durationForToday = $this->teamService->userWorkDurations($userIds, $todayStartDateTime, $todayEndDateTime);
        $durationForMonth = $this->teamService->userWorkDurations($userIds, $monthEndDate, $monthStartDate);
        $durationForWeek = $this->teamService->userWorkDurations($userIds, $weekStartDate, $weekEndDate);

        $todayDuration = [];
        foreach ($durationForToday as $item) {
            $todayDuration[$item->work_user_id] = $item->total_duration;
        }

        $weekDuration = [];
        foreach ($durationForWeek as $item) {
            $weekDuration[$item->work_user_id] = $item->total_duration;
        }

        $monthDuration = [];
        foreach ($durationForMonth as $item) {
            $monthDuration[$item->work_user_id] = $item->total_duration;
        }

        foreach ($teamMembers['members'] as $k => $member) {
            $teamMembers['members'][$k]['todayDuration'] = isset($todayDuration[$member->id]) ? (int)$todayDuration[$member->id] : 0;
            $teamMembers['members'][$k]['weekDuration'] = isset($weekDuration[$member->id]) ? (int)$weekDuration[$member->id] : 0;
            $teamMembers['members'][$k]['monthDuration'] = isset($monthDuration[$member->id]) ? (int)$monthDuration[$member->id] : 0;
        }
        if ($loggedUser->can('view self team details', $team) && !$loggedUser->can('view team details', $team)) {
            foreach ($teamMembers['members'] as $k => $member) {
                if ($loggedUser->id === $member->id) {
                    return $teamMembers;
                }
            }
        } elseif (!$loggedUser->can('view self team details', $team) && $loggedUser->can('view specific team details',
                $team)) {
            return $teamMembers;
        } elseif ($loggedUser->can('view team details', $team)) {
            return $teamMembers;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TeamsUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(TeamsUpdateRequest $request, $id)
    {
        $loggedUser = auth()->user();
        $item = Team::find($id);
        if ($loggedUser->can('update', $item)) { // TODO should we add permission check? Maybe should be update teams?
            if (empty($item)) {
                return response()->json(['status' => false, 'message' => __('Invalid Team'), 'data' => null]);
            }
            $fillData = [
                "name" => $request['name'],
                "description" => $request['description'],
                "status" => $request['status'],
            ];
            $item->fill($fillData);

            if ($item->save()) {

                TeamMember::where('team_id', $item->id)->delete(); // removing all team members

                $bulkInsertData = [];
                $modelPermissionInsertData = [];
                $nowTime = Carbon::now();
                $viewSelfTeamDetailId = Permission::where('name', 'view self team details')->first(['id']);
                $viewSpecificTeamDetailId = Permission::where('name', 'view specific team details')->first(['id']);
                foreach ($request['members'] as $member) {
                    $member = (array)$member;
                    if (empty($member['user_id'])
                        || !isset($member['status'])
                        || !in_array($member['status'],
                            [TeamMember::DEVELOPER, TeamMember::PROJECT_MANAGER, TeamMember::TEAM_LEAD])) {
                        continue;
                    }
                    $bulkInsertData[] = [
                        "team_id" => $item->id,
                        "user_id" => $member['user_id'],
                        "status" => $member['status'],
                        "created_at" => $nowTime,
                        "updated_at" => $nowTime,
                    ];
                    $permissionId = null;
                    if ($member['status'] == TeamMember::DEVELOPER) {
                        $permissionId = $viewSelfTeamDetailId->id;
                    } elseif ($member['status'] == TeamMember::PROJECT_MANAGER || $member['status'] == TeamMember::TEAM_LEAD) {
                        $permissionId = $viewSpecificTeamDetailId->id;
                    }
                    if (!empty($permissionId)) {
                        $modelPermissionInsertData[] = [
                            "model_id" => $member['user_id'],
                            "permission_id" => $permissionId,
                            "model_type" => User::class
                        ];
                    }
                }
                if (!empty($bulkInsertData)) {
//                    foreach (array_chunk($bulkInsertData, 1000) as $bulkInsert) { // todo for big data
                    TeamMember::insert($bulkInsertData);
//                    }
                }
                $project_id = [];
                foreach ($request['project_id'] as $project) {
                    $project_id[] = [
                        "team_id" => $item->id,
                        "project_id" => $project,
                    ];
                }
                if (empty($project_id)) {
                    return response()->json(['status' => false, 'message' => __('invalid_user_roles'), 'user' => null]);
                } elseif (!empty($project_id)) { // TODO what is this?
                    \DB::table('team_has_projects')->where('team_id', '=', $item->id)->delete();
                    \DB::table('team_has_projects')->insertOrIgnore($project_id);
                }
                if (!empty($modelPermissionInsertData)) {
                    \DB::table(config('permission.table_names.model_has_permissions'))->insertOrIgnore($modelPermissionInsertData);
                }
                $data = Team::where('id', $item->id)->with('members')->first();
                return response()->json([
                    'status' => true,
                    'message' => __('The Team edited successfully'),
                    'data' => $data
                ]);
            }

            return response()->json(['status' => false, 'message' => __('Something went wrong'), 'data' => null]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id)
    {
        $item = $this->teamService->find($id);
        // TODO should we add permission check? Maybe should be update teams?
        if (auth()->user()->can('delete', $item)) {
            return response()->json([
                'status' => true,
                'message' => __('The Team deleted'),
                'data' => $this->teamService->delete($id)
            ]);
        }
        return response()->json(['status' => false, 'message' => __('Invalid data'), 'data' => null]);
    }
}
