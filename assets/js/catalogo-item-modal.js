(function () {
    'use strict';

    function safeText(v) {
        var s = (v || '').toString().trim();
        return s === '' ? '—' : s;
    }

    function formatDateIso(v) {
        var s = (v || '').toString().trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) {
            return safeText(s);
        }
        return s.slice(8, 10) + '/' + s.slice(5, 7) + '/' + s.slice(0, 4);
    }

    function formatMoney(v) {
        var s = (v || '').toString().trim();
        if (s === '') {
            return '—';
        }
        var n = Number(s);
        if (!isFinite(n)) {
            return safeText(s);
        }
        return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setText(id, text) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = text;
        }
    }

    function openModal(modal) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('itemViewModal');
        if (!modal) {
            return;
        }

        document.querySelectorAll('.js-item-view').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = btn.dataset;
                setText('ivCodigo', safeText(d.codigo));
                setText('ivNome', safeText(d.nome));
                setText('ivCategoria', safeText(d.categoria));
                setText('ivUnidade', safeText(d.unidade));
                setText('ivStatus', safeText(d.status));
                setText('ivDescricao', safeText(d.descricao));
                setText('ivNfNumero', safeText(d.nfNumero));
                setText('ivNfEmissao', formatDateIso(d.nfEmissao));
                setText('ivNfValor', formatMoney(d.nfValorTotal));
                setText('ivFornecedor', safeText(d.fornecedor));
                setText('ivFornecedorCnpj', safeText(d.fornecedorCnpj));
                setText('ivCompraObs', safeText(d.compraObservacoes));
                openModal(modal);
            });
        });

        modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                closeModal(modal);
            });
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal(modal);
            }
        });
    });
})();
