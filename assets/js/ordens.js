$(function () {
    var base = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : '';

    $(document).on('click', '.js-titulo-sugestao', function () {
        var raw = $(this).attr('data-titulo');
        var t = (raw !== undefined && raw !== '') ? raw : $.trim($(this).text());
        var $inp = $('#inputTituloOs');
        if (!$inp.length || !t) return;
        $inp.val(t).trigger('input').trigger('focus');
    });

    // Cadastro rápido de cliente (modal)
    var $modal = $('#modalClienteRapido');
    if ($modal.length) {
        function abrirModalCliente() {
            $('#msgClienteRapidoErro').hide().text('');
            $('#formClienteRapido')[0].reset();
            $modal.addClass('is-open').attr('aria-hidden', 'false');
            $('#cr_nome').trigger('focus');
        }
        function fecharModalCliente() {
            $modal.removeClass('is-open').attr('aria-hidden', 'true');
        }
        $('#btnAbrirClienteRapido').on('click', abrirModalCliente);
        $('#btnFecharModalCliente, #btnCancelarModalCliente').on('click', fecharModalCliente);
        $modal.on('click', function (e) {
            if ($(e.target).is('#modalClienteRapido')) fecharModalCliente();
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $modal.hasClass('is-open')) fecharModalCliente();
        });

        $('#formClienteRapido').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#btnSalvarClienteRapido');
            var $err = $('#msgClienteRapidoErro');
            $err.hide().text('');
            $btn.prop('disabled', true);

            $.ajax({
                url: base + '/clientes/rapido',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json'
            }).done(function (res) {
                if (res.ok && res.cliente) {
                    var $sel = $('#selectClienteOs');
                    var exists = $sel.find('option[value="' + res.cliente.id + '"]').length;
                    if (!exists) {
                        $sel.append($('<option></option>').attr('value', res.cliente.id).text(res.cliente.label));
                    }
                    $sel.val(String(res.cliente.id));
                    $('#toastClienteOsOk').text('Cliente cadastrado e selecionado.').fadeIn(200);
                    setTimeout(function () {
                        $('#toastClienteOsOk').fadeOut(400);
                    }, 3500);
                    fecharModalCliente();
                } else {
                    $err.text(res.error || 'Não foi possível salvar.').show();
                }
            }).fail(function (xhr) {
                var msg = 'Erro ao salvar.';
                var j = xhr.responseJSON;
                if (!j && xhr.responseText) {
                    try { j = JSON.parse(xhr.responseText); } catch (ignore) {}
                }
                if (j && j.error) msg = j.error;
                $err.text(msg).show();
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    }

    // SLA preview on create/edit form
    function updateSlaPreview() {
        var $catOption = $('#categoriaSelect').find(':selected');
        var slaHoras   = parseFloat($catOption.data('sla')) || 0;
        var $priOption = $('input[name="prioridade_id"]:checked');
        var mult       = parseFloat($priOption.data('mult')) || 1.0;

        if (!slaHoras) {
            $('#slaPreview').hide();
            return;
        }

        var totalHoras = slaHoras * mult;
        var display;
        if (totalHoras >= 24) {
            var dias = Math.floor(totalHoras / 24);
            var h    = Math.round(totalHoras % 24);
            display  = h > 0 ? dias + 'd ' + h + 'h' : dias + ' dia(s)';
        } else {
            display = totalHoras + 'h';
        }
        $('#slaPreviewValue').text(display);
        $('#slaPreview').show();
    }

    $('#categoriaSelect').on('change', updateSlaPreview);
    $('input[name="prioridade_id"]').on('change', updateSlaPreview);
    updateSlaPreview();

    // Delete image via AJAX (data-ordem / data-img — usar .attr)
    $(document).on('click', '.gallery-delete', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!window.confirm('Remover esta imagem permanentemente?')) {
            return;
        }

        var $btn    = $(this);
        var ordemId = $btn.attr('data-ordem');
        var imgId   = $btn.attr('data-img');
        if (!ordemId || !imgId) {
            return;
        }

        $.ajax({
            url: base + '/ordens/' + ordemId + '/imagem/' + imgId + '/deletar',
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            if (res && res.success) {
                window.location.reload();
            }
        }).fail(function () {
            window.alert('Não foi possível remover a imagem.');
        });
    });

    // Lightbox da galeria (ver OS / editar OS)
    (function () {
        var $lb = $('#lightboxGaleriaOs');
        var $imgLb = $('#lightboxGaleriaImg');
        var $prevLb = $lb.find('.lightbox-galeria-prev');
        var $nextLb = $lb.find('.lightbox-galeria-next');
        var $cntLb = $('#lightboxGaleriaCounter');
        var urlsLb = [];
        var idxLb = 0;

        function allowBrowserDefaultClick(e) {
            return !!(e.ctrlKey || e.metaKey || e.shiftKey || e.altKey)
                || (typeof e.which === 'number' && e.which === 2);
        }

        function showLb(i) {
            if (!urlsLb.length || !$lb.length) {
                return;
            }
            idxLb = ((i % urlsLb.length) + urlsLb.length) % urlsLb.length;
            $imgLb.attr('src', urlsLb[idxLb]);
            $cntLb.text((idxLb + 1) + ' / ' + urlsLb.length);
            var single = urlsLb.length <= 1;
            $prevLb.prop('disabled', single);
            $nextLb.prop('disabled', single);
        }

        function closeLb() {
            if (!$lb.length) {
                return;
            }
            $lb.removeClass('is-open').attr('aria-hidden', 'true');
            $('body').css('overflow', '');
            $imgLb.attr('src', '');
            $(document).off('keydown.lightboxGaleriaOs');
        }

        function openLb($link) {
            if (!$lb.length || !$imgLb.length) {
                var u = $link.attr('href');
                if (u) {
                    window.location.href = u;
                }
                return;
            }
            var $grid = $link.closest('.image-gallery-grid');
            if (!$grid.length) {
                return;
            }
            urlsLb = $grid.find('a.gallery-lightbox').map(function () {
                return $(this).attr('href');
            }).get();
            var href = $link.attr('href');
            idxLb = urlsLb.indexOf(href);
            if (idxLb < 0) {
                idxLb = 0;
            }
            $lb.addClass('is-open').attr('aria-hidden', 'false');
            $('body').css('overflow', 'hidden');
            showLb(idxLb);
            $(document).on('keydown.lightboxGaleriaOs', function (ev) {
                if (!$lb.hasClass('is-open')) {
                    return;
                }
                if (ev.key === 'Escape') {
                    closeLb();
                } else if (ev.key === 'ArrowLeft') {
                    ev.preventDefault();
                    showLb(idxLb - 1);
                } else if (ev.key === 'ArrowRight') {
                    ev.preventDefault();
                    showLb(idxLb + 1);
                }
            });
        }

        $(document).on('click', '.image-gallery-grid .gallery-item', function (e) {
            if ($(e.target).closest('.gallery-delete').length) {
                return;
            }
            var $a = $(this).find('a.gallery-lightbox').first();
            if (!$a.length) {
                return;
            }
            if (allowBrowserDefaultClick(e)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openLb($a);
        });

        if ($lb.length) {
            $lb.find('[data-lightbox-close]').on('click', function (e) {
                e.preventDefault();
                closeLb();
            });
            $prevLb.on('click', function (e) {
                e.stopPropagation();
                if ($(this).prop('disabled')) {
                    return;
                }
                showLb(idxLb - 1);
            });
            $nextLb.on('click', function (e) {
                e.stopPropagation();
                if ($(this).prop('disabled')) {
                    return;
                }
                showLb(idxLb + 1);
            });
        }
    }());
});
