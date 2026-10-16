import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('accReviewDecision', (categoryImpact, categoryId, certificateHours) => ({
    categoryImpact,
    categoryId: String(categoryId ?? ''),
    certificateHours: String(certificateHours ?? ''),
    loading: false,
    get category() { return this.categoryImpact[this.categoryId] ?? null; },
    get hours() {
        const value = Number(this.certificateHours.replace(',', '.'));
        return Number.isFinite(value) && value > 0 ? value : 0;
    },
    get additionalHours() {
        if (!this.category) return 0;
        const remaining = Math.max(0, Math.round(this.category.limit * 100) - Math.round(this.category.recognized * 100));
        return Math.min(Math.round(this.hours * 100), remaining) / 100;
    },
    normalizeCertificateHours(event) {
        const input = event.target;
        const selectionStart = input.selectionStart;
        const previousValue = this.certificateHours;

        if (/[^0-9.,]/.test(input.value)) {
            input.value = previousValue;
            return;
        }

        const normalized = input.value
            .replace(/,/g, '.')
            .replace(/[^0-9.]/g, '');
        const [whole, ...fractionParts] = normalized.split('.');
        const value = whole + (fractionParts.length ? `.${fractionParts.join('').slice(0, 2)}` : '');

        input.value = value;
        this.certificateHours = value;

        if (selectionStart !== null) {
            input.setSelectionRange(Math.min(selectionStart, value.length), Math.min(selectionStart, value.length));
        }
    },
    formatHours(value) { return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value); },
}));

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
