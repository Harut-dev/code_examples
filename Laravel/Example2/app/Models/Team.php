<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\StatusQueries;

class Team extends BaseModel
{
    use StatusQueries;

    const INACTIVE = 0;
    const ACTIVE = 1;
    const ARCHIVED = 2;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'integer'
    ];


    /**
     * A role may be given various permissions.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot('team_id', 'user_id', 'status');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'team_has_projects', 'team_id', 'project_id')
            ->withPivot('project_id', 'team_id');
    }

    public static function getTeamMemberIds($teamId)
    {
        return TeamMember::where('team_id', $teamId)->pluck('user_id')->toArray();
    }

    public static function getFilteredQuery($startDateTime, $endDateTime, $userIds = [])
    {
        $startDateTime = Carbon::parse($startDateTime)->format('Y-m-d H:i:s');
        $endDateTime = Carbon::parse($endDateTime)->format('Y-m-d H:i:s');
        $query = \DB::table('reports_view')
            ->where('start_date_time', '>=', $startDateTime)
            ->where(function ($q) use ($endDateTime) {
                $q->where(function ($q1) use ($endDateTime) {
                    $q1->whereNotNull('end_date_time');
                    $q1->where('end_date_time', '<=', $endDateTime);
                });

                $q->orWhere(function ($q2) use ($endDateTime) {
                    $q2->whereNull('end_date_time');
                    $q2->where(\DB::raw('NOW()'), '<=', $endDateTime);
                });
            });
        $query = $query->whereIn('work_user_id', $userIds);
        return $query;
    }

    public static function getUserWorkDurations($userIds, $startDateTime, $endDateTime)
    {
        $query = self::getFilteredQuery($startDateTime, $endDateTime, $userIds);
        return $query
            ->select('work_user_id',
                \DB::raw('SUM((
               CASE
                   WHEN reports_view.end_date_time IS NULL THEN (SELECT TIME_TO_SEC(TIMEDIFF(NOW(), reports_view.start_date_time)) as duration)
                   ELSE (SELECT TIME_TO_SEC(TIMEDIFF(reports_view.end_date_time, reports_view.start_date_time)) as duration)
                   END
               )) as total_duration')
            )->groupBy('work_user_id')
            ->get();
    }
}
