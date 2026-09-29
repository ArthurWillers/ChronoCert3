<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class PreviewStudentAffiliationImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('importStudents', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'max:2048', 'extensions:csv'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $file = $this->file('csv_file');

            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                return;
            }

            if ((int) $file->getSize() === 0) {
                $validator->errors()->add('csv_file', 'O arquivo CSV está vazio.');

                return;
            }

            $path = $file->getRealPath();
            $mimeType = $path === false ? null : (new \finfo(FILEINFO_MIME_TYPE))->file($path);

            if (! in_array($mimeType, ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'], true)) {
                $validator->errors()->add('csv_file', 'Envie um arquivo CSV válido.');

                return;
            }

            $contents = file_get_contents($path);

            if (! is_string($contents) || ! mb_check_encoding($contents, 'UTF-8')) {
                $validator->errors()->add('csv_file', 'O arquivo deve usar codificação UTF-8.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['csv_file' => 'arquivo CSV'];
    }
}
