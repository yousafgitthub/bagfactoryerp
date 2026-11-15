<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\MasterDataRequest;
use App\Support\MasterDataRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function index(Request $request, string $resource): Response
    {
        $config = MasterDataRegistry::get($resource);
        $viewPermission = $config['permissions']['view'];
        abort_unless($request->user()?->can($viewPermission), 403);

        $modelClass = MasterDataRegistry::modelClass($resource);
        $query = $modelClass::query();

        if (! empty($config['with'])) {
            $query->with($config['with']);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($builder) use ($config, $search) {
                foreach ($config['search'] as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        $sort = $request->string('sort', 'code')->toString();
        $direction = $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $allowedSort = collect($config['columns'])->pluck('key')->all();

        if (in_array($sort, $allowedSort, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('code');
        }

        $paginator = $query->paginate(15)->withQueryString();

        $rows = $paginator->getCollection()
            ->map(fn (Model $record) => MasterDataRegistry::mapRowForTable($record, $resource))
            ->values();

        return Inertia::render('MasterData/Index', [
            'pageTitle' => $config['label'],
            'breadcrumbs' => $this->breadcrumbs($resource, $config['label']),
            'resource' => $resource,
            'resourceLabel' => $config['label'],
            'columns' => $config['columns'],
            'rows' => $rows,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'pagination' => [
                'links' => $paginator->linkCollection()->toArray(),
                'meta' => [
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
            ],
            'canManage' => $request->user()?->can($config['permissions']['manage']) === true,
        ]);
    }

    public function create(Request $request, string $resource): Response
    {
        return $this->formResponse($request, $resource, null);
    }

    public function store(MasterDataRequest $request, string $resource): RedirectResponse
    {
        $modelClass = MasterDataRegistry::modelClass($resource);
        $modelClass::query()->create($this->payload($request, $resource));

        return redirect()
            ->route('master-data.index', $resource)
            ->with('status', 'master-data-created');
    }

    public function edit(Request $request, string $resource, int $record): Response
    {
        return $this->formResponse($request, $resource, $record);
    }

    public function update(MasterDataRequest $request, string $resource, int $record): RedirectResponse
    {
        $model = $this->findRecord($resource, $record);
        $model->update($this->payload($request, $resource));

        return redirect()
            ->route('master-data.index', $resource)
            ->with('status', 'master-data-updated');
    }

    public function deactivate(Request $request, string $resource, int $record): RedirectResponse
    {
        $config = MasterDataRegistry::get($resource);
        abort_unless($request->user()?->can($config['permissions']['manage']), 403);

        $model = $this->findRecord($resource, $record);

        if (MasterDataRegistry::isReferenced($model, $resource)) {
            $model->update(['status' => 'inactive']);

            return redirect()
                ->back()
                ->with('status', 'master-data-deactivated-referenced');
        }

        $model->update(['status' => 'inactive']);

        return redirect()
            ->back()
            ->with('status', 'master-data-deactivated');
    }

    private function formResponse(Request $request, string $resource, ?int $recordId): Response
    {
        $config = MasterDataRegistry::get($resource);
        abort_unless($request->user()?->can($config['permissions']['manage']), 403);

        $record = $recordId ? $this->findRecord($resource, $recordId) : null;

        return Inertia::render('MasterData/Form', [
            'pageTitle' => $record ? 'Edit '.$config['label'] : 'New '.$config['label'],
            'breadcrumbs' => array_merge(
                $this->breadcrumbs($resource, $config['label']),
                [['label' => $record ? 'Edit' : 'New']],
            ),
            'resource' => $resource,
            'resourceLabel' => $config['label'],
            'fields' => $config['fields'],
            'record' => $record,
            'relationOptions' => $this->relationOptions($config['fields']),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, list<array{id: int, label: string}>>
     */
    private function relationOptions(array $fields): array
    {
        $options = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? null) !== 'relation' || ! isset($field['relation'])) {
                continue;
            }

            $relationSlug = $field['relation'];
            $modelClass = MasterDataRegistry::modelClass($relationSlug);
            $options[$field['name']] = $modelClass::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Model $row) => [
                    'id' => $row->id,
                    'label' => trim($row->code.' — '.$row->name),
                ])
                ->all();
        }

        return $options;
    }

  /**
     * @return array<string, mixed>
     */
    private function payload(MasterDataRequest $request, string $resource): array
    {
        $config = MasterDataRegistry::get($resource);
        $data = [];

        foreach ($config['fields'] as $field) {
            $name = $field['name'];
            $value = $request->input($name);

            if ($name === 'parent_id' && $value === '' || $value === null) {
                $data[$name] = null;

                continue;
            }

            if (($field['type'] ?? null) === 'relation' && ($value === '' || $value === null)) {
                $data[$name] = null;

                continue;
            }

            $data[$name] = $value;
        }

        return $data;
    }

    private function findRecord(string $resource, int $id): Model
    {
        $modelClass = MasterDataRegistry::modelClass($resource);

        return $modelClass::query()->findOrFail($id);
    }

    /**
     * @return list<array{label: string, href?: string}>
     */
    private function breadcrumbs(string $resource, string $label): array
    {
        $hub = MasterDataRegistry::get($resource)['hub'] ?? 'settings';
        $hubRoute = $hub === 'machines-halls' ? 'erp.machines-halls' : 'erp.settings';

        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => $hub === 'machines-halls' ? 'Machines & Halls' : 'Settings', 'href' => route($hubRoute)],
            ['label' => $label],
        ];
    }
}