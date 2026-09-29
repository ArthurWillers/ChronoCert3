<x-layouts.app>
    <x-page-header title="Importar usuários" description="Revise um arquivo CSV para cadastrar discentes e criar os vínculos do seu curso.">
        <x-back-button :fallback="route('users.index')" />
    </x-page-header>

    <div class="mt-6 max-w-3xl space-y-6">
        <x-callout color="neutral" title="Curso da importação">
            Os vínculos serão criados no curso <strong>{{ $activeAffiliation->course->name }}</strong>.
            Se o CPF já estiver cadastrado, o nome e o e-mail de login da conta serão preservados; o e-mail da planilha será usado no vínculo.
        </x-callout>

        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Prepare o arquivo</h2>
                    <p class="mt-1 text-sm text-neutral-500">CSV UTF-8 com até 500 linhas de dados e tamanho máximo de 2 MB.</p>
                </div>
                <x-button :href="route('users.import.template')" color="outline">
                    <x-heroicon-o-arrow-down-tray class="size-4" /> Baixar modelo CSV
                </x-button>
            </div>

            <div class="mt-5 space-y-4 text-sm text-neutral-600">
                <p>Use as colunas nesta ordem: <strong>cpf</strong>, <strong>nome</strong>, <strong>email</strong> e <strong>matricula</strong>. O cabeçalho pode usar vírgulas ou ponto e vírgula.</p>
                <p>CPF, e-mail e matrícula são obrigatórios. O nome só é necessário para criar uma conta nova. Contas existentes não terão seus dados de identidade alterados.</p>
            </div>

            <form method="POST" action="{{ route('users.import.preview') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                <x-dropzone
                    name="csv_file"
                    label="Selecione o arquivo CSV"
                    sublabel="ou arraste e solte o arquivo aqui"
                    accept=".csv,text/csv,text/plain"
                    required
                />
                <div class="flex justify-end">
                    <x-button type="submit" color="accent"><x-heroicon-o-eye class="size-4" /> Conferir importação</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
