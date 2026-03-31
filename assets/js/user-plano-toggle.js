$(function () {
    function syncPlanoDesc() {
        var $sel = $('#selectPlanoUsuario');
        if (!$sel.length) {
            return;
        }
        var $opt = $sel.find('option:selected');
        var t = $opt.attr('data-desc') || '';
        var $box = $('#planoDescricaoPreview');
        if (!$box.length) {
            return;
        }
        $box.text(t);
        $box.toggle(!!t);
    }

    function sync() {
        var adm = $('#perfilSelect').val() === 'admin';
        $('#grupoOverrides').toggle(!adm);
        $('#grupoPlanoOver').toggle(!adm);
        if (!adm) {
            syncPlanoDesc();
        }
    }

    $('#perfilSelect').on('change', sync);
    $('#selectPlanoUsuario').on('change', syncPlanoDesc);
    sync();
    syncPlanoDesc();
});
