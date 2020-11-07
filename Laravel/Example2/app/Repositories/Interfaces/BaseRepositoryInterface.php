<?php

namespace App\Repositories\Interfaces;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface EloquentRepositoryInterface
 * @package App\Repositories
 */
interface BaseRepositoryInterface
{
    /**
     * @return Model
     */
    public function getModel(): Model;

    /**
     * @return Collection
     */
    public function all(): Collection;

    /**
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model;

    /**
     * @param int $id
     * @return Model
     */
    public function find(int $id): ?Model;

    /**
     * @param array $attributes
     * @param int $id
     * @return bool|null
     */
    public function update(array $attributes, int $id): ?bool;

    /**
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id): ?bool;
}
