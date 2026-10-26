<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simule seu empréstimo consignado</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
@verbatim
    <style>
        :root {
            color-scheme: light dark;
            --bg: #eef2f6; --surface: #ffffff; --ink: #14212e; --muted: #52606d; --line: #c4cfda;
            --brand: #0b5c63; --on-brand: #ffffff; --danger: #b3261e; --ok: #1e7a4c; --focus: #1a73e8;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f1a24; --surface: #16242f; --ink: #e6edf3; --muted: #9fb0bf; --line: #2f4252;
                --brand: #4fb3bf; --on-brand: #08262a; --danger: #ff8a80; --ok: #6fd49b; --focus: #8ab4f8;
            }
        }
        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        body {
            margin: 0; background: var(--bg); color: var(--ink);
            font: 1rem/1.5 "Atkinson Hyperlegible", system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        main { max-width: 40rem; margin: 0 auto; padding: 2rem 1rem 4rem; }
        h1 { margin: 0 0 .5rem; font-size: clamp(1.75rem, 5vw, 2.5rem); line-height: 1.15; letter-spacing: -.01em; }
        .lead { margin: 0 0 2rem; max-width: 34rem; color: var(--muted); }

        form { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 0 1.25rem 1.5rem; }
        fieldset { display: grid; gap: 1rem; margin: 0; padding: 1.25rem 0 .25rem; border: 0; border-top: 1px solid var(--line); }
        fieldset:first-of-type { border-top: 0; }
        legend { margin-bottom: 1rem; padding: 0; font-size: 1.15rem; font-weight: 700; }
        .row { display: grid; gap: 1rem; }
        @media (min-width: 36rem) { .row { grid-template-columns: 1fr 1fr; } }

        label { display: block; margin-bottom: .3rem; font-weight: 700; }
        input, select {
            width: 100%; min-height: 2.75rem; padding: .55rem .75rem; font: inherit; color: inherit;
            background: var(--surface); border: 1px solid var(--line); border-radius: 6px;
        }
        input:focus-visible, select:focus-visible, button:focus-visible, summary:focus-visible {
            outline: 3px solid var(--focus); outline-offset: 2px;
        }
        [aria-invalid="true"] { border-color: var(--danger); }
        .hint { margin: .3rem 0 0; font-size: .9rem; color: var(--muted); }
        .error { margin: .3rem 0 0; font-size: .9rem; color: var(--danger); }
        .error:empty { display: none; }

        .actions { margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--line); }
        .form-error { margin: 0 0 1rem; padding: .75rem 1rem; border-left: 4px solid var(--danger); background: var(--bg); }
        button { min-height: 3rem; padding: .6rem 1.5rem; font: inherit; font-weight: 700; border-radius: 6px; cursor: pointer; }
        .primary { width: 100%; border: 0; background: var(--brand); color: var(--on-brand); }
        .primary:disabled { opacity: .65; cursor: progress; }
        .secondary { margin-top: 1rem; border: 1px solid var(--line); background: transparent; color: inherit; }

        #result {
            margin-top: 1.5rem; padding: 1.25rem; background: var(--surface);
            border: 1px solid var(--line); border-left: 4px solid var(--ok); border-radius: 10px;
        }
        #result.is-failed { border-left-color: var(--danger); }
        #result h2 { margin: 0 0 .5rem; font-size: 1.25rem; }
        #result p { margin: .25rem 0; }
        #result pre { max-height: 20rem; overflow: auto; padding: .75rem; font-size: .85rem; background: var(--bg); border-radius: 6px; }
    </style>
@endverbatim
</head>
<body>
<main>
    <h1>Simule seu empréstimo consignado</h1>
    <p class="lead">Preencha os dados abaixo. Consultamos vários bancos ao mesmo tempo e reunimos as propostas para você.</p>

    <form id="simulation-form" data-margin="{{ config('loan.margin_percent') }}" novalidate>
        <fieldset>
            <legend>Seus dados</legend>
            <div class="field">
                <label for="full_name">Nome completo</label>
                <input id="full_name" name="full_name" type="text" autocomplete="name" aria-describedby="error-full_name">
                <p class="error" id="error-full_name" data-error="full_name"></p>
            </div>
            <div class="row">
                <div class="field">
                    <label for="cpf">CPF</label>
                    <input id="cpf" name="cpf" type="text" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" aria-describedby="error-cpf">
                    <p class="error" id="error-cpf" data-error="cpf"></p>
                </div>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email" aria-describedby="error-email">
                    <p class="error" id="error-email" data-error="email"></p>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Seu vínculo</legend>
            <div class="field">
                <label for="employment_type">Tipo de vínculo</label>
                <select id="employment_type" name="employment_type" aria-describedby="error-employment_type">
                    <option value="clt">CLT</option>
                    <option value="public_servant">Servidor público</option>
                    <option value="inss_pensioner">Aposentado ou pensionista do INSS</option>
                </select>
                <p class="error" id="error-employment_type" data-error="employment_type"></p>
            </div>
            <div class="row">
                <div class="field">
                    <label for="employer_cnpj">CNPJ do empregador</label>
                    <input id="employer_cnpj" name="employer_cnpj" type="text" inputmode="numeric" autocomplete="off" placeholder="00.000.000/0000-00" aria-describedby="hint-cnpj error-employer_cnpj">
                    <p class="hint" id="hint-cnpj">Aposentados e pensionistas informam o CNPJ do órgão pagador.</p>
                    <p class="error" id="error-employer_cnpj" data-error="employer_cnpj"></p>
                </div>
                <div class="field">
                    <label for="gross_salary">Salário bruto mensal (R$)</label>
                    <input id="gross_salary" name="gross_salary" type="number" inputmode="decimal" min="0" step="0.01" aria-describedby="margin-hint error-gross_salary">
                    <p class="hint" id="margin-hint" aria-live="polite"></p>
                    <p class="error" id="error-gross_salary" data-error="gross_salary"></p>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>O empréstimo</legend>
            <div class="row">
                <div class="field">
                    <label for="requested_amount">Valor desejado (R$)</label>
                    <input id="requested_amount" name="requested_amount" type="number" inputmode="decimal" min="0" step="0.01" aria-describedby="error-requested_amount">
                    <p class="error" id="error-requested_amount" data-error="requested_amount"></p>
                </div>
                <div class="field">
                    <label for="requested_term_months">Prazo</label>
                    <select id="requested_term_months" name="requested_term_months" aria-describedby="error-requested_term_months">
                        <option value="12">12 meses</option>
                        <option value="24" selected>24 meses</option>
                        <option value="36">36 meses</option>
                        <option value="48">48 meses</option>
                        <option value="60">60 meses</option>
                        <option value="72">72 meses</option>
                        <option value="84">84 meses</option>
                        <option value="96">96 meses</option>
                    </select>
                    <p class="error" id="error-requested_term_months" data-error="requested_term_months"></p>
                </div>
            </div>
        </fieldset>

        <div class="actions">
            <p class="form-error" id="form-error" role="alert" hidden></p>
            <button class="primary" id="submit" type="submit">Simular empréstimo</button>
        </div>
    </form>

    <section id="result" tabindex="-1" aria-live="polite" hidden>
        <h2 id="result-title"></h2>
        <p id="result-text"></p>
        <p class="hint">Código da simulação: <code id="result-id"></code></p>
        <p class="hint">A lista detalhada de propostas será exibida na próxima etapa.</p>
        <details>
            <summary>Ver resposta completa da API</summary>
            <pre id="result-json"></pre>
        </details>
        <button class="secondary" id="new-simulation" type="button">Fazer nova simulação</button>
    </section>
</main>

@verbatim
<script>
(function () {
    'use strict';

    const form = document.getElementById('simulation-form');
    const submitButton = document.getElementById('submit');
    const formError = document.getElementById('form-error');
    const marginHint = document.getElementById('margin-hint');
    const resultBox = document.getElementById('result');
    const marginPercent = Number(form.dataset.margin) || 35;
    const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    // Chave de idempotência: o mesmo envio repetido (duplo clique) devolve a mesma simulação.
    let idempotencyKey = newKey();

    /** Gera uma chave aleatória aceita pela API (letras, números e hífen). */
    function newKey() {
        return window.crypto && crypto.randomUUID
            ? crypto.randomUUID()
            : Date.now().toString(36) + Math.random().toString(36).slice(2);
    }

    /** Aplica a máscara 000.000.000-00. */
    function maskCpf(value) {
        return value.replace(/\D/g, '').slice(0, 11)
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    /** Aplica a máscara 00.000.000/0000-00. */
    function maskCnpj(value) {
        return value.replace(/\D/g, '').slice(0, 14)
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    form.elements.cpf.addEventListener('input', (e) => { e.target.value = maskCpf(e.target.value); });
    form.elements.employer_cnpj.addEventListener('input', (e) => { e.target.value = maskCnpj(e.target.value); });

    // Qualquer alteração nos dados é uma nova simulação: gera nova chave.
    form.addEventListener('input', () => { idempotencyKey = newKey(); });

    // Estimativa da parcela máxima (percentual vem da configuração do servidor).
    form.elements.gross_salary.addEventListener('input', (e) => {
        const salary = Number(e.target.value);
        marginHint.textContent = salary > 0
            ? `Parcela máxima estimada: ${brl.format(Math.floor(salary * marginPercent) / 100)} (${marginPercent}% do salário bruto).`
            : '';
    });

    /** Limpa mensagens de erro e marcações de campo inválido. */
    function clearErrors() {
        formError.hidden = true;
        formError.textContent = '';
        form.querySelectorAll('[data-error]').forEach((el) => { el.textContent = ''; });
        form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    }

    /** Exibe um erro geral acima do botão. */
    function showFormError(message) {
        formError.textContent = message;
        formError.hidden = false;
    }

    /** Exibe os erros de validação (422) abaixo de cada campo e foca o primeiro. */
    function showFieldErrors(errors) {
        let firstInvalid = null;
        const unmapped = [];

        Object.entries(errors).forEach(([name, messages]) => {
            const input = form.elements[name];
            const slot = form.querySelector(`[data-error="${name}"]`);
            if (!input || !slot) { unmapped.push(messages[0]); return; }
            slot.textContent = messages[0];
            input.setAttribute('aria-invalid', 'true');
            firstInvalid = firstInvalid || input;
        });

        if (unmapped.length) { showFormError(unmapped.join(' ')); }
        if (firstInvalid) { firstInvalid.focus(); }
    }

    /** Bloqueia o botão durante a consulta aos bancos (pode levar alguns segundos). */
    function setLoading(isLoading) {
        submitButton.disabled = isLoading;
        submitButton.textContent = isLoading ? 'Consultando os bancos…' : 'Simular empréstimo';
    }

    /** Monta o corpo da requisição, convertendo os campos numéricos. */
    function readPayload() {
        const data = Object.fromEntries(new FormData(form));
        ['gross_salary', 'requested_amount', 'requested_term_months'].forEach((key) => {
            data[key] = data[key] === '' ? null : Number(data[key]);
        });
        return data;
    }

    /** Mostra o resumo da simulação criada. */
    function showResult(simulation) {
        const failed = simulation.status === 'failed';
        const { approved, rejected, unavailable } = simulation.summary;

        resultBox.classList.toggle('is-failed', failed);
        document.getElementById('result-title').textContent = failed ? 'Nenhum banco respondeu' : 'Simulação concluída';
        document.getElementById('result-text').textContent = failed
            ? 'Não conseguimos consultar os bancos agora. Tente novamente em instantes.'
            : `Bancos consultados: ${simulation.offers.length}. Aprovadas: ${approved}. Recusadas: ${rejected}. Sem resposta: ${unavailable}.`;
        document.getElementById('result-id').textContent = simulation.id;
        document.getElementById('result-json').textContent = JSON.stringify(simulation, null, 2);
        resultBox.hidden = false;
        resultBox.focus();

        // Uma simulação que falhou por completo deve poder ser repetida com nova chave.
        if (failed) { idempotencyKey = newKey(); }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        resultBox.hidden = true;
        setLoading(true);

        try {
            const response = await fetch('/api/simulations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Idempotency-Key': idempotencyKey,
                },
                body: JSON.stringify(readPayload()),
            });
            const body = await response.json().catch(() => null);

            if (response.status === 201 && body && body.data) {
                showResult(body.data);
            } else if (response.status === 422 && body && body.errors) {
                showFieldErrors(body.errors);
            } else if (response.status === 429) {
                const wait = response.headers.get('Retry-After');
                showFormError(`Muitas tentativas seguidas. Aguarde ${wait ? wait + ' segundos' : 'um instante'} e tente de novo.`);
            } else {
                showFormError('Não foi possível concluir a simulação agora. Tente novamente em instantes.');
            }
        } catch (error) {
            showFormError('Sem conexão com o servidor. Verifique se a aplicação está em execução.');
        } finally {
            setLoading(false);
        }
    });

    document.getElementById('new-simulation').addEventListener('click', () => {
        form.reset();
        marginHint.textContent = '';
        clearErrors();
        idempotencyKey = newKey();
        resultBox.hidden = true;
        form.elements.full_name.focus();
    });
})();
</script>
@endverbatim
</body>
</html>
