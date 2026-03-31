/**
 * DataTables — inicialização global (arquivos locais, sem CDN).
 */
(function ($) {
    'use strict';

    var LANG_PT_BR = {
        emptyTable: 'Nenhum registro encontrado',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros no total)',
        lengthMenu: 'Exibir _MENU_ registros',
        loadingRecords: 'Carregando...',
        processing: 'Processando...',
        search: 'Buscar:',
        zeroRecords: 'Nenhum registro encontrado',
        paginate: {
            first: 'Primeiro',
            last: 'Último',
            next: 'Próximo',
            previous: 'Anterior'
        },
        aria: {
            sortAscending: ': ativar para ordenar a coluna de forma ascendente',
            sortDescending: ': ativar para ordenar a coluna de forma descendente'
        }
    };

    function shouldSkipTable($table) {
        var $rows = $table.find('tbody tr');
        if ($rows.length === 0) {
            return true;
        }
        if ($rows.length === 1 && $rows.find('td[colspan]').length > 0) {
            return true;
        }
        return false;
    }

    function buildColumnDefs($table) {
        var defs = [];
        var noSort = $table.attr('data-dt-no-sort-last');
        if (noSort === '1' || noSort === 'true') {
            defs.push({ targets: -1, orderable: false, searchable: false });
        }
        return defs;
    }

    function initTable($table) {
        if ($table.data('dtInit')) {
            return;
        }
        if (shouldSkipTable($table)) {
            return;
        }

        var orderIdx = parseInt($table.attr('data-dt-order-col'), 10);
        if (isNaN(orderIdx)) {
            orderIdx = 0;
        }
        var orderDir = ($table.attr('data-dt-order-dir') || 'asc').toLowerCase() === 'desc' ? 'desc' : 'asc';

        var pageLenAttr = parseInt($table.attr('data-dt-page-length'), 10);
        var pageLength = !isNaN(pageLenAttr) && pageLenAttr > 0 ? pageLenAttr : 15;

        $table.DataTable({
            language: LANG_PT_BR,
            pageLength: pageLength,
            lengthMenu: [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, 'Todos']],
            order: [[orderIdx, orderDir]],
            stripeClasses: [],
            autoWidth: false,
            columnDefs: buildColumnDefs($table),
            dom: '<"dt-toolbar"lf>rt<"dt-foot"ip>'
        });

        $table.data('dtInit', true);
    }

    $(function () {
        $('table.table-datatable').each(function () {
            initTable($(this));
        });
    });
})(jQuery);
