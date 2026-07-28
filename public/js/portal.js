document.addEventListener('DOMContentLoaded', () => {
    /* ── Sidebar toggle ────────────────────────────────────── */
    const shell = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    toggle?.addEventListener('click', () => shell?.classList.toggle('sidebar-open'));

    /* ── Sidebar group collapse ────────────────────────────── */
    document.querySelectorAll('[data-collapse-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!expanded));
        });
    });

    /* ── Billing vs Collections chart ──────────────────────── */
    const chartEl = document.getElementById('billingChart');
    if (chartEl) {
        try {
            const data = JSON.parse(chartEl.dataset.chart || '[]');
            renderBarChart(chartEl, data);
        } catch (e) {
            chartEl.textContent = 'Chart unavailable';
        }
    }
});

function renderBarChart(container, data) {
    if (!data.length) return;

    const maxVal = Math.max(...data.map(d => Math.max(d.billed, d.collected)), 1);
    container.innerHTML = '';

    const barsWrap = document.createElement('div');
    barsWrap.style.cssText = 'display:flex;align-items:flex-end;gap:4px;height:100%;width:100%';

    const labelsWrap = document.createElement('div');
    labelsWrap.className = 'chart-labels';

    data.forEach(d => {
        const group = document.createElement('div');
        group.className = 'chart-bar-group';

        const billed = document.createElement('div');
        billed.className = 'chart-bar chart-bar--billed';
        billed.style.height = ((d.billed / maxVal) * 100) + '%';
        billed.style.flex = '1';
        billed.setAttribute('data-tooltip', 'Billed: TZS ' + formatNum(d.billed));
        billed.setAttribute('role', 'img');
        billed.setAttribute('aria-label', d.label + ' billed TZS ' + formatNum(d.billed));

        const collected = document.createElement('div');
        collected.className = 'chart-bar chart-bar--collected';
        collected.style.height = ((d.collected / maxVal) * 100) + '%';
        collected.style.flex = '1';
        collected.setAttribute('data-tooltip', 'Collected: TZS ' + formatNum(d.collected));
        collected.setAttribute('role', 'img');
        collected.setAttribute('aria-label', d.label + ' collected TZS ' + formatNum(d.collected));

        group.appendChild(billed);
        group.appendChild(collected);
        barsWrap.appendChild(group);

        const label = document.createElement('div');
        label.className = 'chart-label';
        label.textContent = d.label;
        labelsWrap.appendChild(label);
    });

    container.appendChild(barsWrap);
    container.appendChild(labelsWrap);
}

function formatNum(n) {
    return new Intl.NumberFormat('en').format(Math.round(n));
}
