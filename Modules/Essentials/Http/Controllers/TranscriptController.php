<?php

namespace Modules\Essentials\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Essentials\Entities\EssentialsTranscript;
use Modules\Essentials\Http\Requests\StoreTranscriptRequest;
use Modules\Essentials\Utils\TranscriptUtil;
use Yajra\DataTables\Facades\DataTables;

class TranscriptController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected TranscriptUtil $transcriptUtil;

    public function __construct(ModuleUtil $moduleUtil, TranscriptUtil $transcriptUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transcriptUtil = $transcriptUtil;
    }

    /**
     * Display the transcript listing page; return DataTables JSON on AJAX.
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $transcripts = EssentialsTranscript::where('essentials_transcripts.business_id', $business_id)
                ->join('users', 'users.id', '=', 'essentials_transcripts.user_id')
                ->select([
                    'essentials_transcripts.id',
                    'essentials_transcripts.title',
                    'essentials_transcripts.source',
                    'essentials_transcripts.transcript',
                    'essentials_transcripts.created_at',
                    DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user_name"),
                ]);

            return DataTables::of($transcripts)
                ->addColumn('action', function ($row) {
                    return '<button data-transcript="' . e($row->transcript) . '" data-title="' . e($row->title ?? __('essentials::lang.voice_transcripts')) . '" class="btn btn-xs btn-info btn-view-transcript"><i class="fa fa-eye"></i> ' . __('essentials::lang.view') . '</button>'
                        . ' <button data-href="' . action([\Modules\Essentials\Http\Controllers\TranscriptController::class, 'destroy'], [$row->id]) . '" class="btn btn-xs btn-danger btn-delete-transcript"><i class="fa fa-trash"></i> ' . __('essentials::lang.delete') . '</button>';
                })
                ->editColumn('source', function ($row) {
                    $badge = $row->source === 'live' ? 'badge-success' : 'badge-info';
                    $label = $row->source === 'live' ? __('essentials::lang.record_live') : __('essentials::lang.upload_audio');

                    return '<span class="badge ' . $badge . '">' . $label . '</span>';
                })
                ->editColumn('transcript', function ($row) {
                    return '<span class="text-muted">' . e(Str::limit($row->transcript, 80)) . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d M Y H:i') : '';
                })
                ->rawColumns(['action', 'source', 'transcript'])
                ->make(true);
        }

        $page_title = __('essentials::lang.voice_transcripts');

        return view('essentials::transcript.index', compact('page_title'));
    }

    /**
     * Store a new transcript: handle file upload or live recording blob,
     * send to Groq API, and persist the transcript text.
     */
    public function store(StoreTranscriptRequest $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id     = $request->session()->get('user.id');

        $apiKey = $this->transcriptUtil->getApiKey($business_id);

        if (empty($apiKey)) {
            return $this->respondWithError(__('essentials::lang.groq_api_key_missing'));
        }

        try {
            $source = $request->hasFile('recorded_audio') ? 'live' : 'upload';
            $audioFile = $source === 'live'
                ? $request->file('recorded_audio')
                : $request->file('audio');

            $storedPath = Storage::putFile('essentials_audio', $audioFile);
            $absolutePath = Storage::path($storedPath);

            $transcriptText = $this->transcriptUtil->transcribe($absolutePath, $apiKey);

            $transcript = EssentialsTranscript::create([
                'business_id'    => $business_id,
                'user_id'        => $user_id,
                'title'          => $request->input('title') ?: null,
                'transcript'     => $transcriptText,
                'audio_filename' => $storedPath,
                'source'         => $source,
            ]);

            return $this->respondSuccess(__('essentials::lang.transcript_saved'), [
                'transcript_id'   => $transcript->id,
                'transcript_text' => $transcriptText,
            ]);
        } catch (\Exception $e) {
            Log::emergency('TranscriptController@store File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    /**
     * Delete a transcript (scoped to business).
     */
    public function destroy(Request $request, int $id)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $transcript = EssentialsTranscript::where('business_id', $business_id)
                ->findOrFail($id);

            if (! empty($transcript->audio_filename) && Storage::exists($transcript->audio_filename)) {
                Storage::delete($transcript->audio_filename);
            }

            $transcript->delete();

            return $this->respondSuccess(__('lang_v1.success'));
        } catch (\Exception $e) {
            Log::emergency('TranscriptController@destroy File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }
    }
}
