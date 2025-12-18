<?php


namespace App\Traits;

trait CommonModelFunctions
{
    /**
     * @param $date
     * @return false|string
     */
    public function getCreatedAtAttribute($date)
    {
        if ($date) {
            return date('Y-m-d H:i:s', strtotime($date));
        }
        return $date;
    }

    /**
     * @param $date
     * @return false|string
     */
    public function getUpdatedAtAttribute($date)
    {
        if ($date) {
            return date('Y-m-d H:i:s', strtotime($date));
        }
        return $date;
    }

    public function scopeDump($query)
    {
        dd(getSqlQuery($query));
    }

    public function scopeRawQuery($query)
    {
        getSqlQuery($query);
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }

    public function scopeClone($query)
    {
        return clone $query;
    }

}
