<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Media;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\EssentialsTodoComment;
use Modules\Essentials\Entities\ToDo;
use Modules\Essentials\Notifications\NewTaskCommentNotification;
use Modules\Essentials\Notifications\NewTaskDocumentNotification;
use Modules\Essentials\Notifications\NewTaskNotification;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsTodoCommentStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsTodoDocumentUploadRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsTodoStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsTodoUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class EssentialsTodoController extends Controller
{
    protected Util $commonUtil;

    protected ModuleUtil $moduleUtil;

    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    /**
     * @var array<string, string>
     */
    protected array $priorityColors;

    /**
     * @var array<string, string>
     */
    protected array $statusColors;

    public function __construct(
        Util $commonUtil,
        ModuleUtil $moduleUtil,
        ProjectXEssentialsUtil $projectXEssentialsUtil
    ) {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;

        $this->priorityColors = [
            'low' => 'badge-light-success',
            'medium' => 'badge-light-warning',
            'high' => 'badge-light-danger',
            'urgent' => 'badge-danger',
        ];

        $this->statusColors = [
            'new' => 'badge-light-warning',
            'in_progress' => 'badge-light-primary',
            'on_hold' => 'badge-light-danger',
            'completed' => 'badge-light-success',
        ];
    }

    public function index(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $auth_id = $this->projectXEssentialsUtil->authUserId();
        $task_statuses = ToDo::getTaskStatus();
        $priorities = ToDo::getTaskPriorities();

        if ($request->ajax()) {
            $todos = $this->projectXEssentialsUtil->getTodoQueryForUser($business_id, $auth_id)
                ->with(['users', 'assigned_by'])
                ->select('*');

            if ($request->filled('priority')) {
                $todos->where('priority', (string) $request->input('priority'));
            }

            if ($request->filled('status')) {
                $todos->where('status', (string) $request->input('status'));
            }

            if ($request->filled('user_id')) {
                $user_id = (int) $request->input('user_id');
                $todos->whereHas('users', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                });
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $todos->whereDate('date', '>=', (string) $request->input('start_date'))
                    ->whereDate('date', '<=', (string) $request->input('end_date'));
            }

            return DataTables::of($todos)
                ->addColumn('action', function (ToDo $row) {
                    $links = [];

                    if (auth()->user()->can('essentials.edit_todos')) {
                        $links[] = '<a href="' . route('projectx.essentials.todo.edit', ['todo' => $row->id]) . '" class="dropdown-item">' . e(__('messages.edit')) . '</a>';
                    }

                    if (auth()->user()->can('essentials.delete_todos')) {
                        $links[] = '<a href="#" data-id="' . e((string) $row->id) . '" class="dropdown-item projectx-delete-todo">' . e(__('messages.delete')) . '</a>';
                    }

                    $links[] = '<a href="' . route('projectx.essentials.todo.show', ['todo' => $row->id]) . '" class="dropdown-item">' . e(__('messages.view')) . '</a>';
                    $links[] = '<a href="#" data-id="' . e((string) $row->id) . '" data-status="' . e((string) $row->status) . '" class="dropdown-item projectx-change-status">' . e(__('essentials::lang.change_status')) . '</a>';

                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown" type="button">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $links) . '</div>'
                        . '</div>';
                })
                ->editColumn('task', function (ToDo $row) use ($priorities) {
                    $priority_badge = '';
                    if (! empty($row->priority) && ! empty($priorities[$row->priority])) {
                        $priority_class = $this->priorityColors[$row->priority] ?? 'badge-light';
                        $priority_badge = ' <span class="badge ' . e($priority_class) . '">' . e($priorities[$row->priority]) . '</span>';
                    }

                    $task_link = '<a href="' . route('projectx.essentials.todo.show', ['todo' => $row->id]) . '">' . e($row->task) . '</a>';
                    $docs_link = '<button type="button" class="btn btn-sm btn-light-primary ms-2 projectx-view-shared-docs" data-url="' . route('projectx.essentials.todo.shared-docs', ['todo' => $row->id]) . '">' . e(__('essentials::lang.docs')) . '</button>';

                    return $task_link . $priority_badge . '<div class="mt-1">' . $docs_link . '</div>';
                })
                ->addColumn('assigned_by', function (ToDo $row) {
                    return optional($row->assigned_by)->user_full_name;
                })
                ->editColumn('users', function (ToDo $row) {
                    return $row->users->pluck('user_full_name')->implode(', ');
                })
                ->editColumn('created_at', function (ToDo $row) {
                    return ! empty($row->created_at) ? $this->commonUtil->format_date($row->created_at, true) : '';
                })
                ->editColumn('date', function (ToDo $row) {
                    return ! empty($row->date) ? $this->commonUtil->format_date($row->date, true) : '';
                })
                ->editColumn('end_date', function (ToDo $row) {
                    return ! empty($row->end_date) ? $this->commonUtil->format_date($row->end_date, true) : '';
                })
                ->editColumn('status', function (ToDo $row) use ($task_statuses) {
                    $status_label = $task_statuses[$row->status] ?? $row->status;
                    $status_class = $this->statusColors[$row->status] ?? 'badge-light';

                    return '<button type="button" class="btn btn-sm projectx-change-status" data-id="' . e((string) $row->id) . '" data-status="' . e((string) $row->status) . '">'
                        . '<span class="badge ' . e($status_class) . '">' . e($status_label) . '</span>'
                        . '</button>';
                })
                ->rawColumns(['task', 'action', 'status'])
                ->make(true);
        }

        $users = auth()->user()->can('essentials.assign_todos')
            ? User::forDropdown($business_id, false)
            : [];

        return view('projectx::essentials.todo.index', compact('users', 'task_statuses', 'priorities'));
    }

    public function create(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensurePermission($business_id, ['essentials.add_todos']);

        $users = auth()->user()->can('essentials.assign_todos')
            ? User::forDropdown($business_id, false)
            : [];

        if ($request->filled('from_calendar')) {
            $users = [];
        }

        $task_statuses = ToDo::getTaskStatus();
        $priorities = ToDo::getTaskPriorities();
        $todo_form = $this->todoFormDefaults();

        return view('projectx::essentials.todo.create', compact('users', 'task_statuses', 'priorities', 'todo_form'));
    }

    public function store(EssentialsTodoStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $created_by = $this->projectXEssentialsUtil->authUserId();

        try {
            $input = $request->only([
                'task',
                'date',
                'description',
                'estimated_hours',
                'priority',
                'status',
                'end_date',
            ]);
            $input = $this->projectXEssentialsUtil->normalizeTodoInput($input);
            $input['business_id'] = $business_id;
            $input['created_by'] = $created_by;

            $settings = $this->projectXEssentialsUtil->essentialsSettings($business_id);
            $input['task_id'] = $this->projectXEssentialsUtil->generateTodoReference($settings);

            $users = $request->input('users', []);
            if (! auth()->user()->can('essentials.assign_todos') || empty($users)) {
                $users = [$created_by];
            }

            $to_do = ToDo::create($input);
            $to_do->users()->sync($users);

            $this->commonUtil->activityLog($to_do, 'added');

            $notified_users = $to_do->users->filter(function ($user) use ($created_by) {
                return (int) $user->id !== $created_by;
            });
            \Notification::send(
                $notified_users,
                new NewTaskNotification($to_do, route('projectx.essentials.todo.show', ['todo' => $to_do->id]))
            );

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.success'), ['todo_id' => $to_do->id]);
            }

            return redirect()
                ->route('projectx.essentials.todo.show', ['todo' => $to_do->id])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function show(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $auth_id = $this->projectXEssentialsUtil->authUserId();
        $todo = $this->projectXEssentialsUtil->getTodoQueryForUser($business_id, $auth_id)
            ->with([
                'assigned_by',
                'comments',
                'comments.added_by',
                'media',
                'media.uploaded_by_user',
                'users',
            ])
            ->findOrFail($id);

        $users = $todo->users->pluck('user_full_name')->values()->all();
        $task_statuses = ToDo::getTaskStatus();
        $priorities = ToDo::getTaskPriorities();

        $activities = Activity::forSubject($todo)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();

        $todo_view = [
            'date' => ! empty($todo->date) ? $this->commonUtil->format_date($todo->date, true) : '-',
            'end_date' => ! empty($todo->end_date) ? $this->commonUtil->format_date($todo->end_date, true) : '-',
        ];

        return view('projectx::essentials.todo.show', compact('todo', 'users', 'task_statuses', 'priorities', 'activities', 'todo_view'));
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensurePermission($business_id, ['essentials.edit_todos']);

        $auth_id = $this->projectXEssentialsUtil->authUserId();
        $todo = $this->projectXEssentialsUtil->getTodoQueryForUser($business_id, $auth_id)
            ->with(['users'])
            ->findOrFail($id);

        $users = auth()->user()->can('essentials.assign_todos')
            ? User::forDropdown($business_id, false)
            : [];

        $task_statuses = ToDo::getTaskStatus();
        $priorities = ToDo::getTaskPriorities();
        $todo_form = $this->todoFormDefaults($todo);

        return view('projectx::essentials.todo.edit', compact('users', 'todo', 'task_statuses', 'priorities', 'todo_form'));
    }

    public function update(EssentialsTodoUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $auth_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $todo = $this->projectXEssentialsUtil->getTodoQueryForUser($business_id, $auth_id)->findOrFail($id);
            $todo_before = $todo->replicate();

            $only_status = (bool) $request->input('only_status', false);
            if ($only_status) {
                $input = ['status' => (string) $request->input('status')];
            } else {
                $input = $request->only([
                    'task',
                    'date',
                    'description',
                    'estimated_hours',
                    'priority',
                    'status',
                    'end_date',
                ]);
                $input = $this->projectXEssentialsUtil->normalizeTodoInput($input);
            }

            $todo->update($input);

            if (! $only_status && auth()->user()->can('essentials.assign_todos')) {
                $todo->users()->sync((array) $request->input('users', []));
            }

            $this->commonUtil->activityLog($todo, 'edited', $todo_before);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.success'));
            }

            return redirect()
                ->route('projectx.essentials.todo.show', ['todo' => $todo->id])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensurePermission($business_id, ['essentials.delete_todos']);

        try {
            $query = ToDo::where('business_id', $business_id)->where('id', $id);
            if (! $this->projectXEssentialsUtil->isAdmin($business_id)) {
                $query->where('created_by', $this->projectXEssentialsUtil->authUserId());
            }
            $query->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.success'));
            }

            return redirect()
                ->route('projectx.essentials.todo.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function addComment(EssentialsTodoCommentStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $auth_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $todo = $this->projectXEssentialsUtil->getTodoQueryForUser($business_id, $auth_id)
                ->with('users')
                ->findOrFail((int) $request->input('task_id'));

            $comment = EssentialsTodoComment::create([
                'task_id' => $todo->id,
                'comment' => (string) $request->input('comment'),
                'comment_by' => $auth_id,
            ]);

            $comment->load('added_by');

            $comment_html = view('projectx::essentials.todo.partials._comment', compact('comment'))->render();

            $notified_users = $todo->users->filter(function ($user) use ($auth_id) {
                return (int) $user->id !== $auth_id;
            });

            \Notification::send(
                $notified_users,
                new NewTaskCommentNotification($comment, route('projectx.essentials.todo.show', ['todo' => $todo->id]))
            );

            return $this->respondSuccess(__('lang_v1.success'), ['comment_html' => $comment_html]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function deleteComment(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        try {
            EssentialsTodoComment::where('id', $id)
                ->where('comment_by', $this->projectXEssentialsUtil->authUserId())
                ->whereHas('task', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->delete();

            return $this->respondSuccess(__('lang_v1.success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function uploadDocument(EssentialsTodoDocumentUploadRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $auth_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $todo = $this->projectXEssentialsUtil->getTodoQueryForUser($business_id, $auth_id)
                ->with('users')
                ->findOrFail((int) $request->input('task_id'));

            Media::uploadMedia($business_id, $todo, $request, 'documents');

            $notified_users = $todo->users->filter(function ($user) use ($auth_id) {
                return (int) $user->id !== $auth_id;
            });

            $data = [
                'task_id' => $todo->task_id,
                'uploaded_by' => $auth_id,
                'id' => $todo->id,
                'uploaded_by_user_name' => auth()->user()->user_full_name,
            ];

            \Notification::send(
                $notified_users,
                new NewTaskDocumentNotification($data, route('projectx.essentials.todo.show', ['todo' => $todo->id]))
            );

            return redirect()
                ->route('projectx.essentials.todo.show', ['todo' => $todo->id])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function deleteDocument(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        try {
            $media = Media::where('business_id', $business_id)->findOrFail($id);
            if ($media->model_type !== ToDo::class) {
                return $this->respondWithError(__('messages.unauthorized_action'));
            }

            $todo = ToDo::where('business_id', $business_id)
                ->with('users')
                ->findOrFail((int) $media->model_id);

            if (! $this->projectXEssentialsUtil->canMutateTodo($todo, $this->projectXEssentialsUtil->authUserId(), $business_id)) {
                return $this->respondUnauthorized(__('messages.unauthorized_action'));
            }

            $this->projectXEssentialsUtil->deleteMediaDocument($media);

            return $this->respondSuccess(__('lang_v1.success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function viewSharedDocs(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! $request->ajax()) {
            abort(404);
        }

        $module_data = $this->moduleUtil->getModuleData('getSharedSpreadsheetForGivenData', [
            'business_id' => $business_id,
            'shared_with' => 'todo',
            'shared_id' => $id,
        ]);

        $sheets = [];
        if (! empty($module_data['Spreadsheet']) && is_array($module_data['Spreadsheet'])) {
            $sheets = $module_data['Spreadsheet'];
        }

        $todo = ToDo::where('business_id', $business_id)->findOrFail($id);

        return view('projectx::essentials.todo.partials._shared_docs_content', compact('sheets', 'todo'));
    }

    protected function todoFormDefaults(?ToDo $todo = null): array
    {
        $assigned_users = $todo?->relationLoaded('users') ? $todo->users->pluck('id')->map(fn ($id) => (int) $id)->all() : [];

        return [
            'task' => old('task', (string) ($todo->task ?? '')),
            'date' => old('date', ! empty($todo?->date) ? Carbon::parse((string) $todo->date)->format('Y-m-d H:i') : ''),
            'end_date' => old('end_date', ! empty($todo?->end_date) ? Carbon::parse((string) $todo->end_date)->format('Y-m-d H:i') : ''),
            'priority' => old('priority', (string) ($todo->priority ?? '')),
            'status' => old('status', (string) ($todo->status ?? 'new')),
            'estimated_hours' => old('estimated_hours', (string) ($todo->estimated_hours ?? '')),
            'description' => old('description', (string) ($todo->description ?? '')),
            'users' => array_map('intval', (array) old('users', $assigned_users)),
        ];
    }
}
