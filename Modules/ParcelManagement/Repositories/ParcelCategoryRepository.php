<?php

namespace Modules\ParcelManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ParcelManagement\Entities\ParcelCategory;
use Modules\ParcelManagement\Interfaces\ParcelCategoryInterface;

class ParcelCategoryRepository implements ParcelCategoryInterface
{
    private $category;

    public function __construct(ParcelCategory $category)
    {
        $this->category = $category;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param bool $dynamic_page
     * @param array $except
     * @param array $attributes
     * @param array $relations
     * @return LengthAwarePaginator|array|Collection
     */
    public function get(int $limit, int $offset, bool $dynamic_page = false, array $except = [], array $attributes = [], array $relations = []): LengthAwarePaginator|array|Collection
    {

        $search = array_key_exists('search', $attributes) ? $attributes['search'] : '';
        $value = array_key_exists('value', $attributes) ? $attributes['value'] : 'all';
        $column = array_key_exists('query', $attributes) ? $attributes['query'] : 'is_active';

        $relationalColumn = array_key_exists('column_name', $attributes) ? $attributes['column_name'] : '';
        $relationalColumnValue = array_key_exists('column_value', $attributes) ? $attributes['column_value'] : '';
        $hasKey = array_key_exists('whereHas', $attributes) ? $attributes['whereHas'] : null;
        $queryParam = ['search' => $search, 'query' => $column, 'value' => $value];

        $query = $this->category
            ->query()
            ->when(!empty($relations[0]), function ($query) use ($relations) {
                $query->with($relations);
            })
            ->when($search, function ($query) use ($attributes) {
                $keys = explode(' ', $attributes['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($column && $value != 'all', function ($query) use ($column, $value) {
                return $query->where($column, $value === 'active' ? 1 : 0);
            })
            ->when($relationalColumn && $relationalColumnValue && $hasKey, function ($query) use ($relationalColumn, $relationalColumnValue, $hasKey) {

                $query->whereHas($hasKey, function ($query) use ($relationalColumn, $relationalColumnValue) {
                    $query->where($relationalColumn, $relationalColumnValue);
                });
            })
            ->when(!empty($except[0]), function ($query) use ($except) {
                $query->whereNotIn('id', $except);
            });

        if (!$dynamic_page) {
            return $query->latest()->paginate($limit)->appends($queryParam);
        }

        return $query->latest()->paginate($limit, ['*'], $offset);
    }

    /**
     * @param string $column
     * @param string|int $value
     * @param array $attributes
     * @return mixed|Model
     */
    public function getBy(string $column, int|string $value, array $attributes = []): mixed
    {
        return $this->category->where([$column => $value])->firstOrFail();

    }

    /**
     * @param array $attributes
     * @return Model
     */
    public function store(array $attributes): Model
    {
        $model = $this->category;
        $model->name = $attributes['category_name'];
        $model->description = $attributes['short_desc'];
        $model->image = fileUploader('parcel/category/', 'png', $attributes['category_icon']);
        $model->save();
        return $model;
    }

    /**
     * @param array $attributes
     * @param string $id
     * @return Model
     */
    public function update(array $attributes, string $id): Model
    {
        $model = $this->getBy(column: 'id', value: $id);

        if (!array_key_exists('status', $attributes)) {

            $model->name = $attributes['category_name'];
            $model->description = $attributes['short_desc'];
            if (array_key_exists('category_icon', $attributes)) {
                $model->image = fileUploader('parcel/category/', 'png', $attributes['category_icon'], $model->image);

            }
        } else {
            $model->is_active = $attributes['status'];
        }

        $model->save();

        return $model;
    }

    /**
     * @param string $id
     * @return Model
     */
    public function destroy(string $id): Model
    {
        $model = $this->getBy(column: 'id', value: $id);
        $model->delete();
        return $model;
    }

    /**
     * Category wise parcels and its trip status
     * @param int $limit
     * @param int $offset
     * @param bool $dynamic_page
     * @param array $attributes
     * @return mixed
     */
    public function getCategorizedParcels(int $limit, int $offset, string $status_column, bool $dynamic_page = false, array $attributes = []): mixed
    {
        $search = array_key_exists('search', $attributes) ? $attributes['search'] : '';
        $value = array_key_exists('value', $attributes) ? $attributes['value'] : 'all';
        $column = array_key_exists('query', $attributes) ? $attributes['query'] : '';
        $queryParam = ['search' => $search, 'query' => $column, 'value' => $value];

        $query = $this->category->with(['parcels' => function ($query) use ($status_column) {
            return $query->with(['tripStatus' => function ($query) use ($status_column) {
                $query->whereNotNull($status_column);
            }])->whereHas('tripStatus', function ($query) use ($status_column) {
                $query->whereNotNull($status_column);
            });
        }])
            ->when($search, function ($query) use ($attributes) {
                $keys = explode(' ', $attributes['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($column && $value != 'all', function ($query) use ($column, $value) {
                return $query->where($column, ($value == 'active' ? 1 : ($value == 'inactive' ? 0 : $value)));
            });

        if (!$dynamic_page) {
            return $query->latest()->paginate($limit)->appends($queryParam);
        }

        return $query->latest()->paginate($limit, ['*'], $offset);
    }


    /**
     * @param array $attributes
     * @return mixed
     */
    public function download(array $attributes = []): mixed
    {
        $search = array_key_exists('search', $attributes) ? $attributes['search'] : '';
        $value = array_key_exists('value', $attributes) ? $attributes['value'] : 'all';
        $column = array_key_exists('query', $attributes) ? $attributes['query'] : '';

        $model = $this->category->withCount(['parcels as total_delivered' => function ($query) {
            return $query->whereHas('tripStatus', function ($query) {
                $query->whereNotNull('completed');
            });
        }])
            ->when($search, function ($query) use ($attributes) {
                $keys = explode(' ', $attributes['search']);
                return $query->where(function ($query) use ($keys) {
                    foreach ($keys as $key) {
                        $query->where('name', 'LIKE', '%' . $key . '%');
                    }
                });
            })
            ->when($column && $value != 'all', function ($query) use ($column, $value) {
                return $query->where($column, ($value == 'active' ? 1 : ($value == 'inactive' ? 0 : $value)));
            })
            ->latest()->get();

        return $model;
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function trashed(array $attributes)
    {
        $search = $attributes['search'] ?? null;
        $relations = $attributes['relations'] ?? null;
        return $this->category->query()
            ->when($relations, function ($query) use ($relations) {
                $query->with($relations);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $keys = explode(' ', $search);
                    foreach ($keys as $key) {
                        $query->where('name', 'like', '%' . $key . '%');
                    }
                });
            })
            ->onlyTrashed()
            ->paginate(paginationLimit())
            ->appends(['search' => $search]);

    }

    /**
     * @param string $id
     * @return mixed
     */

    public function restore(string $id)
    {
        return $this->category->query()->onlyTrashed()->find($id)->restore();
    }

    public function permanentDelete(string $id): Model
    {
        $model = $this->category->query()->onlyTrashed()->find($id);
        $model->forceDelete();
        return $model;
    }
}
