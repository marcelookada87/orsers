/**
 * Listagem de OS — modal de finalização rápida (técnicos).
 */
(function ($) {
    'use strict';

    var base = typeof window.BASE_URL === 'string' ? window.BASE_URL : '';

    function openModal($btn) {
        var id = $btn.attr('data-os-id');
        var num = $btn.attr('data-os-num') || '';
        var $modal = $('#modalFinalizarOs');
        if (!$modal.length || !id) {
            return;
        }
        $modal.find('input[name="data_finalizacao"]').val($btn.attr('data-default-dt') || '');
        $modal.find('textarea[name="motivo_finalizacao"]').val('');
        $modal.find('input[name="valor_servico"]').val('');
        $modal.find('input[name="valor_pago"]').val('');
        $modal.find('select[name="forma_pagamento"]').val('');
        $modal.find('textarea[name="detalhe_financeiro"]').val('');
        $modal.find('input[name="confirmar_encerramento"]').prop('checked', false);
        $modal.find('form').attr('action', base + '/ordens/' + id + '/finalizar-rapido');
        $modal.find('[data-modal-os-num]').text(num);
        $modal.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
    }

    function closeModal() {
        var $modal = $('#modalFinalizarOs');
        $modal.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
    }

    $(document).on('click', '[data-action="finalizar-os"]', function (e) {
        e.preventDefault();
        openModal($(this));
    });

    $(document).on('click', '[data-modal-close]', function () {
        closeModal();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    $(document).on('click', '#modalFinalizarOs', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
})(jQuery);
