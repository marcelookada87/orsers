$(function () {
    // Sidebar toggle
    var $sidebar = $('#sidebar');
    var $overlay = $('#sidebarOverlay');

    $('#sidebarToggle').on('click', function () {
        $sidebar.toggleClass('open');
        $overlay.toggleClass('show');
    });
    $overlay.on('click', function () {
        $sidebar.removeClass('open');
        $overlay.removeClass('show');
    });

    // Topbar: dropdown alertas de estoque mínimo
    var $tbWrap = $('.topbar-estoque-dropdown-wrap');
    if ($tbWrap.length) {
        var $tbBtn = $tbWrap.find('.js-topbar-estoque-bell');
        var $tbPanel = $tbWrap.find('.topbar-estoque-dropdown');

        function tbClose() {
            $tbPanel.prop('hidden', true);
            $tbBtn.attr('aria-expanded', 'false');
            $tbWrap.removeClass('is-open');
        }

        function tbOpen() {
            $tbPanel.prop('hidden', false);
            $tbBtn.attr('aria-expanded', 'true');
            $tbWrap.addClass('is-open');
        }

        $tbBtn.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if ($tbPanel.prop('hidden')) {
                tbOpen();
            } else {
                tbClose();
            }
        });

        $tbWrap.on('click', function (e) {
            e.stopPropagation();
        });

        $(document).on('click.topbarEstoque', function () {
            tbClose();
        });

        $(document).on('keydown.topbarEstoque', function (e) {
            if (e.key === 'Escape') {
                tbClose();
            }
        });
    }

    // Auto-dismiss flash messages
    setTimeout(function () {
        $('#flashMessage').fadeOut(400, function () { $(this).remove(); });
    }, 4000);
});
