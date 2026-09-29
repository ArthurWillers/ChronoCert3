<?php

namespace App\Actions\Users;

use App\Enums\AffiliationType;
use App\Models\Affiliation;
use App\Models\Course;
use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PreviewStudentAffiliationCsv
{
    /**
     * Parse and classify CSV rows for the coordinator's active course.
     *
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function execute(UploadedFile $file, Affiliation $activeAffiliation): array
    {
        return $this->classify($this->parse($file), $activeAffiliation);
    }

    /**
     * Recheck preview rows against the current database state.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function revalidate(array $rows, Affiliation $activeAffiliation): array
    {
        return $this->classify(array_map(static fn (array $row): array => [
            'number' => $row['number'],
            'cpf' => $row['cpf'],
            'name' => $row['name'],
            'email' => $row['email'],
            'registration_number' => $row['registration_number'],
            'structure_error' => $row['structure_error'] ?? null,
        ], $rows), $activeAffiliation);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parse(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $contents = $path === false ? false : file_get_contents($path);

        if (! is_string($contents) || $contents === '') {
            throw ValidationException::withMessages(['csv_file' => 'O arquivo CSV está vazio.']);
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages(['csv_file' => 'O arquivo deve usar codificação UTF-8.']);
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw ValidationException::withMessages(['csv_file' => 'Não foi possível ler o arquivo enviado.']);
        }

        fwrite($stream, $contents);
        rewind($stream);
        $firstLine = fgets($stream);
        $delimiter = $this->detectDelimiter($firstLine === false ? '' : $firstLine);
        rewind($stream);
        fgetcsv($stream, null, $delimiter, '"', '');

        $rows = [];
        $recordNumber = 1;

        while (($values = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $recordNumber++;

            if ($this->isBlankRecord($values)) {
                continue;
            }

            if (count($rows) >= 500) {
                fclose($stream);

                throw ValidationException::withMessages(['csv_file' => 'O arquivo pode conter no máximo 500 linhas de dados.']);
            }

            $isWellFormed = count($values) === 4;
            $values = array_pad(array_slice($values, 0, 4), 4, null);
            $rows[] = [
                'number' => $recordNumber,
                'cpf' => preg_replace('/\D/', '', trim((string) $values[0])) ?? '',
                'name' => trim((string) $values[1]),
                'email' => mb_strtolower(trim((string) $values[2])),
                'registration_number' => trim((string) $values[3]),
                'structure_error' => $isWellFormed ? null : 'A linha deve conter exatamente quatro colunas.',
            ];
        }

        fclose($stream);

        if ($rows === []) {
            throw ValidationException::withMessages(['csv_file' => 'O arquivo não contém linhas de dados.']);
        }

        return $rows;
    }

    private function detectDelimiter(string $header): string
    {
        foreach ([';', ','] as $delimiter) {
            $columns = str_getcsv(rtrim($header, "\r\n"), $delimiter, '"', '');
            $columns = array_map(static fn (?string $column): string => mb_strtolower(trim((string) $column)), $columns);

            if ($columns === ['cpf', 'nome', 'email', 'matricula']) {
                return $delimiter;
            }
        }

        throw ValidationException::withMessages([
            'csv_file' => 'O cabeçalho deve ser cpf, nome, email e matricula, separados por vírgula ou ponto e vírgula.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    private function classify(array $rows, Affiliation $activeAffiliation): array
    {
        $course = Course::query()->active()->whereKey($activeAffiliation->course_id)->first();

        if ($course === null) {
            throw ValidationException::withMessages(['csv_file' => 'O curso do vínculo ativo está inativo.']);
        }

        $cpfCounts = collect($rows)->pluck('cpf')->filter()->countBy();
        $registrationCounts = collect($rows)->pluck('registration_number')
            ->filter()
            ->map(static fn (string $number): string => mb_strtolower($number))
            ->countBy();
        $candidateCpfs = collect($rows)->pluck('cpf')->filter()->unique()->values();
        $candidateNumbers = collect($rows)->pluck('registration_number')->filter()->unique()->values();
        $usersByCpf = User::query()->whereIn('cpf', $candidateCpfs)->get()->keyBy('cpf');
        $affiliationsByUser = Affiliation::query()
            ->whereIn('user_id', $usersByCpf->modelKeys())
            ->where('type', AffiliationType::Student)
            ->where('course_id', $course->getKey())
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');
        $activeRegistrationNumbers = Affiliation::query()
            ->active()
            ->where('type', AffiliationType::Student)
            ->where('course_id', $course->getKey())
            ->whereIn('registration_number', $candidateNumbers)
            ->pluck('registration_number')
            ->flip();
        $candidateNewUserEmails = collect($rows)
            ->filter(fn (array $row): bool => ! $usersByCpf->has($row['cpf']))
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();
        $newUserEmailCounts = collect($rows)
            ->filter(fn (array $row): bool => ! $usersByCpf->has($row['cpf']))
            ->pluck('email')
            ->filter()
            ->countBy();
        $existingLoginEmails = User::query()
            ->whereIn('email', $candidateNewUserEmails)
            ->pluck('email')
            ->flip();

        $classifiedRows = [];

        foreach ($rows as $row) {
            $user = $usersByCpf->get($row['cpf']);
            $userAffiliations = $user === null ? collect() : $affiliationsByUser->get($user->getKey(), collect());
            $activeAffiliationForUser = $userAffiliations->first(fn (Affiliation $affiliation): bool => $affiliation->deactivated_at === null);
            $inactiveAffiliationForUser = $userAffiliations->first(fn (Affiliation $affiliation): bool => $affiliation->deactivated_at !== null);
            $validator = Validator::make([
                'cpf' => $row['cpf'],
                'name' => $row['name'],
                'email' => $row['email'],
                'registration_number' => $row['registration_number'],
            ], [
                'cpf' => ['required', 'string', new ValidCpf],
                'name' => [$user === null ? 'required' : 'nullable', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255'],
                'registration_number' => ['required', 'string', 'max:64'],
            ], [
                'cpf.required' => 'O CPF é obrigatório.',
                'name.required' => 'O nome é obrigatório para uma conta nova.',
                'name.max' => 'O nome não pode ultrapassar 255 caracteres.',
                'email.required' => 'O e-mail é obrigatório.',
                'email.email' => 'O e-mail informado é inválido.',
                'registration_number.required' => 'A matrícula é obrigatória.',
                'registration_number.max' => 'A matrícula não pode ultrapassar 64 caracteres.',
            ]);
            $errors = $validator->errors()->all();

            if ($row['structure_error'] !== null) {
                $errors[] = $row['structure_error'];
            }

            if (($cpfCounts[$row['cpf']] ?? 0) > 1) {
                $errors[] = 'Este CPF aparece mais de uma vez no arquivo.';
            }

            if (($registrationCounts[mb_strtolower($row['registration_number'])] ?? 0) > 1) {
                $errors[] = 'Esta matrícula aparece mais de uma vez no arquivo.';
            }

            $status = 'invalid';

            if ($errors === []) {
                if ($activeAffiliationForUser !== null) {
                    $status = 'ignored';
                } elseif ($activeRegistrationNumbers->has($row['registration_number'])) {
                    $errors[] = 'Esta matrícula já está ativa em outro vínculo deste curso.';
                } elseif ($user === null && ($newUserEmailCounts[$row['email']] ?? 0) > 1) {
                    $errors[] = 'Este e-mail aparece mais de uma vez para contas novas no arquivo.';
                } elseif ($user === null && $existingLoginEmails->has($row['email'])) {
                    $errors[] = 'Este e-mail já é usado como e-mail de login por outra conta.';
                } else {
                    $status = $user === null
                        ? 'new_user'
                        : ($inactiveAffiliationForUser === null ? 'new_affiliation' : 'reactivation');
                }
            }

            $classifiedRows[] = [
                ...$row,
                'status' => $errors === [] ? $status : 'invalid',
                'errors' => array_values(array_unique($errors)),
            ];
        }

        return [
            'rows' => $classifiedRows,
            'summary' => $this->summarize($classifiedRows),
        ];
    }

    /**
     * @param  array<int, string|null>  $record
     */
    private function isBlankRecord(array $record): bool
    {
        return count(array_filter($record, static fn (?string $value): bool => trim((string) $value) !== '')) === 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summarize(array $rows): array
    {
        $counts = collect($rows)->countBy('status');

        return [
            'new_users' => (int) $counts->get('new_user', 0),
            'new_affiliations' => (int) $counts->get('new_affiliation', 0),
            'reactivations' => (int) $counts->get('reactivation', 0),
            'ignored' => (int) $counts->get('ignored', 0),
            'invalid' => (int) $counts->get('invalid', 0),
        ];
    }
}
