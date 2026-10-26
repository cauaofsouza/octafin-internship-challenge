<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ofertas de empréstimo | OctaFin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light dark;
            --bg: #eef2f6;
            --surface: #ffffff;
            --ink: #14212e;
            --muted: #52606d;
            --line: #c4cfda;
            --brand: #0b5c63;
            --on-brand: #ffffff;
            --danger: #b3261e;
            --ok: #1e7a4c;
            --focus: #1a73e8;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f1a24;
                --surface: #16242f;
                --ink: #e6edf3;
                --muted: #9fb0bf;
                --line: #2f4252;
                --brand: #4fb3bf;
                --on-brand: #08262a;
                --danger: #ff8a80;
                --ok: #6fd49b;
                --focus: #8ab4f8;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 1rem/1.5 "Atkinson Hyperlegible",
            system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        main {
            max-width: 46rem;
            margin: auto;
            padding: 1.5rem 1rem 4rem;
        }

        a, button { font: inherit; }

        a:focus-visible, button:focus-visible {
            outline: 3px solid var(--focus);
            outline-offset: 3px;
        }

        .back {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }

        h1 {
            margin: 0 0 .5rem;
            font-size: clamp(1.8rem, 5vw, 2.4rem);
            line-height: 1.15;
        }

        h2 { font-size: 1.25rem; }

        .muted { color: var(--muted); }

        .panel, .offer {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 1.25rem;
            margin-top: 1rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .summary-label {
            display: block;
            color: var(--muted);
            font-size: .9rem;
        }

        .summary-value {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .best {
            border: 2px solid var(--ok);
        }

        .best-label {
            display: inline-block;
            margin-bottom: .5rem;
            color: var(--ok);
            font-weight: 700;
        }

        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .offer-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .status {
            display: inline-block;
            padding: .25rem .6rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.approved { color: var(--ok); }
        .status.rejected, .status.error, .status.timeout {
            color: var(--danger);
        }

        .offer-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .offer-grid > div {
            padding-top: .65rem;
            border-top: 1px solid var(--line);
        }

        .offer-grid .summary-value { font-size: 1rem; }

        .reason {
            margin-top: 1rem;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .empty {
            padding: 1rem;
            border-left: 4px solid var(--danger);
            background: var(--surface);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.5rem;
        }

        button {
            min-height: 3rem;
            padding: .6rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
        }

        .primary {
            border: 0;
            background: var(--brand);
            color: var(--on-brand);
        }

        .secondary {
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
        }

        [hidden] { display: none !important; }

        @media (max-width: 25rem) {
            .summary-grid, .offer-grid { grid-template-columns: 1fr; }
            .offer-header { flex-direction: column; }
        }
    </style>
</head>
<body>
<main>
    <a class="back" href="/" id="back-link">← Voltar ao formulário</a>

    <header>
        <h1>Resultado da simulação</h1>
        <p class="muted">
            Confira as propostas recebidas e compare as condições de cada banco.
        </p>
    </header>

    <section class="panel" aria-labelledby="summary-title">
        <h2 id="summary-title">Resumo da solicitação</h2>
        <div class="summary-grid">
            <div>
                <span class="summary-label">Solicitante</span>
                <span class="summary-value" id="customer-name">—</span>
            </div>
            <div>
                <span class="summary-label">Valor solicitado</span>
                <span class="summary-value" id="requested-amount">—</span>
            </div>
            <div>
                <span class="summary-label">Prazo</span>
                <span class="summary-value" id="requested-term">—</span>
            </div>
            <div>
                <span class="summary-label">Salário bruto</span>
                <span class="summary-value" id="gross-salary">—</span>
            </div>
        </div>
        <p class="muted" id="simulation-id"></p>
        <p id="summary-counts"></p>
    </section>

    <section id="best-section" hidden aria-labelledby="best-title">
        <h2 id="best-title">Melhor oferta encontrada</h2>
        <div id="best-offer" class="offer best"></div>
    </section>

    <section aria-labelledby="offers-title">
        <h2 id="offers-title">Ofertas dos bancos</h2>
        <div id="offers-list" aria-live="polite"></div>
    </section>

    <section id="no-data" class="empty" hidden role="alert">
        <h2>Não foi possível carregar os resultados</h2>
        <p>
            Os dados desta simulação não estão disponíveis nesta aba.
            Volte ao formulário e realize uma nova consulta.
        </p>
    </section>

    <div class="actions">
        <button class="secondary" id="back-button" type="button">
            Voltar ao formulário
        </button>
        <button class="primary" id="new-simulation" type="button">
            Nova simulação
        </button>
    </div>
</main>

<script>
    (function () {
        'use strict';

        const brl = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        const number = new Intl.NumberFormat('pt-BR', {
            maximumFractionDigits: 2
        });

        const get = (object, ...keys) => {
            for (const key of keys) {
                if (object && object[key] !== undefined && object[key] !== null) {
                    return object[key];
                }
            }
            return null;
        };

        const text = (value, fallback = 'Não informado') =>
            value === undefined || value === null || value === ''
                ? fallback
                : String(value);

        const money = value => {
            if (value === null || value === undefined || value === '') {
                return 'Não informado';
            }

            const parsed = Number(value);
            return Number.isFinite(parsed) ? brl.format(parsed) : 'Não informado';
        };

        const percent = value => {
            if (value === null || value === undefined || value === '') {
                return 'Não informado';
            }

            const parsed = Number(value);
            return Number.isFinite(parsed)
                ? number.format(parsed) + '%'
                : 'Não informado';
        };

        const statusInfo = status => {
            const normalized = String(status || '').toLowerCase();

            const statuses = {
                approved: { label: 'Aprovada', className: 'approved' },
                rejected: { label: 'Recusada', className: 'rejected' },
                error: { label: 'Erro na consulta', className: 'error' },
                timeout: { label: 'Tempo esgotado', className: 'timeout' },
            };

            return statuses[normalized] || {
                label: text(status, 'Status desconhecido'),
                className: ''
            };
        };

        function addMetric(container, label, value) {
            const wrapper = document.createElement('div');

            const caption = document.createElement('span');
            caption.className = 'summary-label';
            caption.textContent = label;

            const content = document.createElement('span');
            content.className = 'summary-value';
            content.textContent = value;

            wrapper.append(caption, content);
            container.appendChild(wrapper);
        }


        function createOfferCard(offer) {
            const card = document.createElement('article');
            card.className = 'offer';

            const header = document.createElement('div');
            header.className = 'offer-header';

            const title = document.createElement('h3');
            title.textContent = offer.bank || 'Banco não identificado';

            const statusLabels = {
                approved: { label: 'Aprovada', className: 'approved' },
                rejected: { label: 'Recusada', className: 'rejected' },
                error: { label: 'Erro na consulta', className: 'error' },
                timeout: { label: 'Tempo esgotado', className: 'timeout' }
            };

            const statusInfo = statusLabels[offer.status] || {
                label: offer.status || 'Status desconhecido',
                className: ''
            };

            const badge = document.createElement('span');
            badge.className = 'status ' + statusInfo.className;
            badge.textContent = statusInfo.label;

            header.append(title, badge);
            card.appendChild(header);

            const metrics = document.createElement('div');
            metrics.className = 'offer-grid';

            const money = value => {
                if (value === null || value === undefined) {
                    return 'Não informado';
                }

                return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(Number(value));
            };

            const percent = value => {
                if (value === null || value === undefined) {
                    return 'Não informado';
                }

                return new Intl.NumberFormat('pt-BR', {
                    maximumFractionDigits: 4
                }).format(Number(value)) + '%';
            };

            function addMetric(label, value) {
                const wrapper = document.createElement('div');

                const caption = document.createElement('span');
                caption.className = 'summary-label';
                caption.textContent = label;

                const content = document.createElement('span');
                content.className = 'summary-value';
                content.textContent = value;

                wrapper.append(caption, content);
                metrics.appendChild(wrapper);
            }

            addMetric('Valor oferecido', money(offer.offered_amount));
            addMetric('Parcela mensal', money(offer.installment_value));
            addMetric('Juros mensais', percent(offer.interest_rate_monthly));
            addMetric('CET mensal', percent(offer.cet_monthly));
            addMetric('Total a pagar', money(offer.total_payable));

            if (offer.response_time_ms !== null &&
                offer.response_time_ms !== undefined) {
                addMetric(
                    'Tempo de resposta',
                    new Intl.NumberFormat('pt-BR').format(offer.response_time_ms) + ' ms'
                );
            }

            card.appendChild(metrics);

            if (offer.rejection_reason) {
                const reason = document.createElement('p');
                reason.className = 'reason';
                reason.textContent = 'Motivo: ' + offer.rejection_reason;
                card.appendChild(reason);
            }

            return card;
        }


        function renderSimulation(simulation) {
            const request = simulation.request || {};
            const customer = simulation.customer || {};
            const summary = simulation.summary || {};
            const offers = Array.isArray(simulation.offers)
                ? simulation.offers
                : [];

            document.getElementById('customer-name').textContent =
                text(customer.full_name);

            document.getElementById('requested-amount').textContent =
                money(request.requested_amount);

            document.getElementById('requested-term').textContent =
                request.requested_term_months
                    ? request.requested_term_months + ' meses'
                    : 'Não informado';

            document.getElementById('gross-salary').textContent =
                money(request.gross_salary);

            document.getElementById('simulation-id').textContent =
                'Código da simulação: ' + text(simulation.id);

            document.getElementById('summary-counts').textContent =
                'Aprovadas: ' + text(summary.approved, '0') +
                ' · Recusadas: ' + text(summary.rejected, '0') +
                ' · Sem resposta: ' + text(summary.unavailable, '0');

            const bestSection = document.getElementById('best-section');
            const bestContainer = document.getElementById('best-offer');
            const bestOffer = simulation.best_offer;

            if (bestOffer) {
                bestContainer.replaceChildren();

                const label = document.createElement('span');
                label.className = 'best-label';
                label.textContent = 'Melhor proposta aprovada';

                bestContainer.append(label, createOfferCard(bestOffer));
                bestSection.hidden = false;
            } else {
                bestSection.hidden = true;
            }

            const list = document.getElementById('offers-list');
            list.replaceChildren();

            if (offers.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'empty';
                empty.textContent = 'A API não retornou ofertas para esta simulação.';
                list.appendChild(empty);
            } else {
                offers.forEach(offer => list.appendChild(createOfferCard(offer)));
            }
        }

        let simulation = null;

        try {
            const stored = sessionStorage.getItem('octafin_simulation');
            simulation = stored ? JSON.parse(stored) : null;
        } catch (error) {
            simulation = null;
        }

        if (simulation && typeof simulation === 'object') {
            renderSimulation(simulation);
        } else {
            document.getElementById('no-data').hidden = false;
            document.getElementById('best-section').hidden = true;
            document.getElementById('offers-list').replaceChildren();
        }

        function goBack() {
            window.location.href = '/';
        }

        document.getElementById('back-button').addEventListener('click', goBack);
        document.getElementById('new-simulation').addEventListener('click', goBack);
    })();
</script>
</body>
</html>
