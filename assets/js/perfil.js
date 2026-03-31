$(function () {
    var base = typeof window.BASE_URL === 'string' ? window.BASE_URL : '';

    var $btn = $('#btnGerarToken');
    if ($btn.length) {
        $btn.on('click', function () {
            $.getJSON(base + '/perfil/telegram-token', function (data) {
                $('#telegramCmd').text(data.comando);
                $('#telegramTokenBox').slideDown();
            });
        });
    }

    var $planoSel = $('#selectPlanoPerfil');
    var $planoBox = $('#planoEscolhaInfo');
    var $planoTitulo = $('#planoEscolhaTitulo');
    var $planoTexto = $('#planoEscolhaTexto');
    if ($planoSel.length && $planoBox.length && $planoTitulo.length && $planoTexto.length) {
        function syncPlanoEscolhaInfo() {
            var $opt = $planoSel.find('option:selected');
            var nome = $opt.attr('data-nome') || $opt.text().replace(/\s*—\s*indisponível\s*$/, '').trim();
            var desc = ($opt.attr('data-desc') || '').trim();
            $planoTitulo.text(nome ? 'Sobre: ' + nome : 'Sobre o plano selecionado');
            if (desc) {
                $planoTexto.text(desc);
            } else {
                $planoTexto.text('Não há descrição cadastrada para este plano. Fale com o administrador se precisar de mais detalhes.');
            }
            $planoBox.addClass('is-visible');
        }
        $planoSel.on('change', syncPlanoEscolhaInfo);
        syncPlanoEscolhaInfo();
    }

});
