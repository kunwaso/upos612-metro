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
use Modules\Essentials\Http\Requests\PreviewTranscriptRequest;
use Modules\Essentials\Http\Requests\StoreTranscriptRequest;
use Modules\Essentials\Http\Requests\TranslateTranscriptRequest;
use Modules\Essentials\Utils\TranscriptTranslationUtil;
use Modules\Essentials\Utils\TranscriptUtil;
use Yajra\DataTables\Facades\DataTables;

class TranscriptController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected TranscriptUtil $transcriptUtil;

    protected TranscriptTranslationUtil $transcriptTranslationUtil;

    public function __construct(
        ModuleUtil $moduleUtil,
        TranscriptUtil $transcriptUtil,
        TranscriptTranslationUtil $transcriptTranslationUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->transcriptUtil = $transcriptUtil;
        $this->transcriptTranslationUtil = $transcriptTranslationUtil;
    }

    /**
     * Display transcript listing page; return DataTables JSON on AJAX.
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $languageOptions = $this->transcriptUtil->getLanguageOptions();

        if ($request->ajax()) {
            $transcripts = EssentialsTranscript::where('essentials_transcripts.business_id', $business_id)
                ->join('users', 'users.id', '=', 'essentials_transcripts.user_id')
                ->select([
                    'essentials_transcripts.id',
                    'essentials_transcripts.title',
                    'essentials_transcripts.source',
                    'essentials_transcripts.source_language',
                    'essentials_transcripts.target_language',
                    'essentials_transcripts.transcript',
                    'essentials_transcripts.translated_text',
                    'essentials_transcripts.created_at',
                    DB::raw("CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user_name"),
                ]);

            return DataTables::of($transcripts)
                ->addColumn('action', function ($row) {
                    return '<button data-transcript="' . e($row->transcript) . '" data-translated="' . e($row->translated_text ?? '') . '" data-language-pair="' . e($this->formatLanguagePair($row->source_language, $row->target_language)) . '" data-title="' . e($row->title ?? __('essentials::lang.voice_transcripts')) . '" class="btn btn-sm btn-light-primary btn-view-transcript"><i class="fa fa-eye"></i> ' . __('essentials::lang.view') . '</button>'
                        . ' <button data-href="' . action([\Modules\Essentials\Http\Controllers\TranscriptController::class, 'destroy'], [$row->id]) . '" class="btn btn-sm btn-light-danger btn-delete-transcript"><i class="fa fa-trash"></i> ' . __('essentials::lang.delete') . '</button>';
                })
                ->editColumn('source', function ($row) {
                    $badge = $row->source === 'live' ? 'badge-light-success' : 'badge-light-info';
                    $label = $row->source === 'live' ? __('essentials::lang.record_live') : __('essentials::lang.upload_audio');

                    return '<span class="badge ' . $badge . '">' . e($label) . '</span>';
                })
                ->addColumn('language_pair', function ($row) use ($languageOptions) {
                    if (empty($row->source_language) || empty($row->target_language)) {
                        return '<span class="text-muted">-</span>';
                    }

                    $source = $languageOptions[$row->source_language] ?? strtoupper((string) $row->source_language);
                    $target = $languageOptions[$row->target_language] ?? strtoupper((string) $row->target_language);

                    return '<span class="badge badge-light-primary">' . e($source . ' -> ' . $target) . '</span>';
                })
                ->editColumn('transcript', function ($row) {
                    return '<span class="text-muted">' . e(Str::limit($row->transcript, 80)) . '</span>';
                })
                ->addColumn('translated_preview', function ($row) {
                    return '<span class="text-muted">' . e(Str::limit((string) ($row->translated_text ?? ''), 80)) . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d M Y H:i') : '';
                })
                ->rawColumns(['action', 'source', 'language_pair', 'transcript', 'translated_preview'])
                ->make(true);
        }

        $page_title = __('essentials::lang.voice_transcripts');
        $default_source_language = strtolower((string) $request->session()->get('user.language', config('app.locale', 'en')));
        if (! array_key_exists($default_source_language, $languageOptions)) {
            $default_source_language = 'en';
        }

        $default_target_language = array_key_exists('vi', $languageOptions) ? 'vi' : 'en';

        $speech_locales = $this->transcriptUtil->getSpeechRecognitionLocales();
        $transcription_languages = $this->transcriptUtil->getTranscriptionLanguageCodes();

        return view('essentials::transcript.index', compact(
            'page_title',
            'languageOptions',
            'default_source_language',
            'default_target_language',
            'speech_locales',
            'transcription_languages'
        ));
    }

    /**
     * Preview transcript + translation without persisting DB row.
     */
    public function preview(PreviewTranscriptRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) $request->session()->get('user.id');
        $sourceLanguage = (string) $request->input('source_language');
        $targetLanguage = (string) $request->input('target_language');

        $apiKey = $this->transcriptUtil->getApiKey($business_id);
        if (empty($apiKey)) {
            return $this->respondWithError(__('essentials::lang.groq_api_key_missing'));
        }

        $audioFile = $request->hasFile('recorded_audio')
            ? $request->file('recorded_audio')
            : $request->file('audio');

        $storedPath = null;
        try {
            $storedPath = Storage::putFile('essentials_audio/tmp', $audioFile);
            $absolutePath = Storage::path($storedPath);

            $sttLanguage = $this->transcriptUtil->resolveTranscriptionLanguage($sourceLanguage);
            $transcriptText = $this->transcriptUtil->transcribe($absolutePath, $apiKey, $sttLanguage);
            $translatedText = $this->transcriptTranslationUtil->translateText(
                $business_id,
                $user_id,
                $transcriptText,
                $sourceLanguage,
                $targetLanguage
            );

            return $this->respondSuccess(__('essentials::lang.transcript_preview_ready'), [
                'data' => [
                    'transcript_text' => $transcriptText,
                    'translated_text' => $translatedText,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                ],
            ]);
        } catch (\RuntimeException $exception) {
            return $this->respondWithError($exception->getMessage());
        } catch (\Exception $exception) {
            Log::emergency('TranscriptController@preview File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        } finally {
            if (! empty($storedPath) && Storage::exists($storedPath)) {
                Storage::delete($storedPath);
            }
        }
    }

    /**
     * Translate a live text segment.
     */
    public function translate(TranslateTranscriptRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) $request->session()->get('user.id');
        $text = (string) $request->input('text', '');
        $sourceLanguage = (string) $request->input('source_language');
        $targetLanguage = (string) $request->input('target_language');

        try {
            $translatedText = $this->transcriptTranslationUtil->translateText(
                $business_id,
                $user_id,
                $text,
                $sourceLanguage,
                $targetLanguage
            );

            return $this->respondSuccess(__('essentials::lang.translation_ready'), [
                'data' => [
                    'translated_text' => $translatedText,
                ],
            ]);
        } catch (\RuntimeException $exception) {
            return $this->respondWithError($exception->getMessage());
        } catch (\Exception $exception) {
            Log::emergency('TranscriptController@translate File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    /**
     * Persist transcript row with provided text and translation.
     */
    public function store(StoreTranscriptRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) $request->session()->get('user.id');

        try {
            $source = (string) $request->input('source', $request->hasFile('recorded_audio') ? 'live' : 'upload');
            $audioFile = $request->hasFile('recorded_audio')
                ? $request->file('recorded_audio')
                : $request->file('audio');

            $storedPath = Storage::putFile('essentials_audio', $audioFile);

            $transcript = EssentialsTranscript::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'title' => $request->input('title') ?: null,
                'source' => $source,
                'source_language' => $request->input('source_language'),
                'target_language' => $request->input('target_language'),
                'transcript' => trim((string) $request->input('transcript_text')),
                'translated_text' => trim((string) $request->input('translated_text')),
                'audio_filename' => $storedPath,
            ]);

            return $this->respondSuccess(__('essentials::lang.transcript_saved'), [
                'data' => [
                    'transcript_id' => $transcript->id,
                    'transcript_text' => $transcript->transcript,
                    'translated_text' => $transcript->translated_text,
                    'source_language' => $transcript->source_language,
                    'target_language' => $transcript->target_language,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::emergency('TranscriptController@store File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    /**
     * Delete a transcript (scoped to business).
     */
    public function destroy(Request $request, int $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $transcript = EssentialsTranscript::where('business_id', $business_id)->findOrFail($id);

            if (! empty($transcript->audio_filename) && Storage::exists($transcript->audio_filename)) {
                Storage::delete($transcript->audio_filename);
            }

            $transcript->delete();

            return $this->respondSuccess(__('lang_v1.success'));
        } catch (\Exception $exception) {
            Log::emergency('TranscriptController@destroy File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    protected function formatLanguagePair(?string $sourceLanguage, ?string $targetLanguage): string
    {
        if (empty($sourceLanguage) || empty($targetLanguage)) {
            return '-';
        }

        $source = $this->transcriptUtil->getLanguageLabel($sourceLanguage);
        $target = $this->transcriptUtil->getLanguageLabel($targetLanguage);

        return $source . ' -> ' . $target;
    }
}
