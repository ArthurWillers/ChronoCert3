import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-existing-user-lookup]').forEach((input) => {
    let lastCheckedCpf = '';

    input.addEventListener('change', async () => {
        const cpf = input.value.replace(/\D/g, '');

        if (cpf.length !== 11 || cpf === lastCheckedCpf) {
            return;
        }

        lastCheckedCpf = cpf;

        const feedback = input.parentElement?.parentElement?.querySelector('[data-existing-user-feedback]');

        try {
            const response = await fetch(`${input.dataset.existingUserLookup}?cpf=${encodeURIComponent(cpf)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (! response.ok) {
                return;
            }

            const result = await response.json();

            if (! result.found || ! result.redirect_to) {
                return;
            }

            if (feedback) {
                feedback.textContent = 'Conta existente encontrada. Abrindo os dados do vínculo…';
                feedback.classList.remove('hidden');
            }

            window.location.assign(result.redirect_to);
        } catch {}
    });
});
