<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Services\Interfaces\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class BaseService implements BaseServiceInterface
{
    /**
     * @var $modelRepository
     */
    protected $modelRepository;

    /**
     * BaseService constructor.
     *
     * @param BaseRepository $modelRepository
     */
    public function __construct(BaseRepository $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    /**
     * @return Model
     */
    public function getModel(): ?Model
    {
        return $this->modelRepository->getModel();
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->modelRepository->all();
    }

    /**
     * @param Request $request
     * @return Model
     */
    public function create(Request $request): Model
    {
        $attributes = $request->all();

        return $this->modelRepository->create($attributes);
    }

    /**
     * @param int $id
     * @return Model
     */
    public function find(int $id): Model
    {
        return $this->modelRepository->find($id);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return bool
     */
    public function update(Request $request, int $id): bool
    {
        $attributes = $request->all();

        return $this->modelRepository->update($attributes, $id);
    }

    /**
     * @param int $id
     * @return bool|null
     * @throws \Exception
     */
    public function delete(int $id): ?bool
    {
        return $this->modelRepository->delete($id);
    }
}
