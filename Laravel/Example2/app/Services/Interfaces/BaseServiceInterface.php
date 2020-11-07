<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

interface BaseServiceInterface
{
    /**
     * @return Model
     * @throws \Exception
     */
    public function getModel(): ?Model;

    /**
     * @return Collection
     */
    public function all(): Collection;

    /**
     * @param Request $request
     * @return Model
     */
    public function create(Request $request): Model;

    /**
     * @param int $id
     * @return Model
     */
    public function find(int $id): Model;

    /**
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function update(Request $request, int $id): bool;

    /**
     * @param int $id
     * @return bool|null
     */
    public function delete(int $id): ?bool;
}
