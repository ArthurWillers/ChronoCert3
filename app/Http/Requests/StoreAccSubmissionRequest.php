<?php

namespace App\Http\Requests;

use App\Models\AccSubmission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreAccSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AccSubmission::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                File::types(AccSubmission::acceptedMimeTypes())
                    ->extensions(AccSubmission::acceptedExtensions())
                    ->max((int) ceil((int) config('acc.documents.max_file_size_bytes') / 1024)),
            ],
            'student_affiliation_id' => ['prohibited'],
            'submitted_by_affiliation_id' => ['prohibited'],
            'origin' => ['prohibited'],
            'status' => ['prohibited'],
            'course_id' => ['prohibited'],
        ];
    }

    /**
     * Validate the detected content type and the client filename as a matching pair.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $document = $this->file('document');

            if (! $document instanceof UploadedFile || ! $document->isValid()) {
                return;
            }

            if ((int) $document->getSize() === 0) {
                $validator->errors()->add('document', 'O comprovante não pode estar vazio.');

                return;
            }

            $temporaryPath = $document->getRealPath();
            $mimeType = $temporaryPath === false
                ? null
                : (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
            $extension = $document->getClientOriginalExtension();

            if (! is_string($mimeType) || ! AccSubmission::acceptsFile($mimeType, $extension)) {
                $validator->errors()->add('document', 'O comprovante deve ter um formato e uma extensão aceitos.');
            }

            $extensions = array_slice(explode('.', Str::lower($document->getClientOriginalName())), 1);
            $disallowedExtensions = collect(config('media-library.disallowed_extensions', []))
                ->map(static fn (string $disallowedExtension): string => Str::lower($disallowedExtension))
                ->all();

            if (array_intersect($extensions, $disallowedExtensions) !== []) {
                $validator->errors()->add('document', 'O nome do comprovante contém uma extensão não permitida.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['document' => 'comprovante'];
    }
}
