<?php

namespace App\Traits;

trait StatusQueries
{
    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        $status = isset(self::ACTIVE['value']) ? self::ACTIVE['value'] : self::ACTIVE;
        return $query->where('status', $status);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeInactive($query)
    {
        $status = isset(self::INACTIVE['value']) ? self::INACTIVE['value'] : self::INACTIVE;
        return $query->where('status', $status);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeArchived($query)
    {
        $status = isset(self::ARCHIVED['value']) ? self::ARCHIVED['value'] : self::ARCHIVED;
        return $query->where('status', $status);
    }
}
