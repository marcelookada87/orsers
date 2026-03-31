/**
 * Painel SLA — modais (Ver, Editar, Comentar, Reset SLA, Concluir).
 */
(function ($) {
    'use strict';

    /**
     * jQuery converte data-ordem-id em ordemId — usar sempre .attr('data-ordem-id').
     */
    function ordemIdFromBtn($el) {
        var v = $el.attr('data-ordem-id');
        if (v === undefined || v === null || v === '') {
            v = $el.closest('[data-ordem-id]').attr('data-ordem-id');
        }
        return v ? String(v) : '';
    }

    var base = (function () {
        var b = typeof window.BASE_URL === 'string' ? window.BASE_URL.replace(/\/$/, '') : '';
        if (b) {
            return b;
        }
        var p = window.location.pathname || '';
        var i = p.indexOf('/sla');
        if (i >= 0) {
            return window.location.origin + (i > 0 ? p.substring(0, i) : '');
        }
        return window.location.origin || '';
    })();

    function esc(s) {
        if (s === null || s === undefined) {
            return '';
        }
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function xhrHeaders() {
        return { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
    }

    function openHost() {
        var $h = $('#slaModalHost');
        $h.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('sla-modal-open');
    }

    function closeHost() {
        var $h = $('#slaModalHost');
        $h.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('sla-modal-open');
        $('#slaModalContent').empty().attr('hidden', true);
        $('#slaModalLoading').show();
    }

    function setTitle(text) {
        $('#slaModalTitleText').text(text);
    }

    function showLoading(show) {
        if (show) {
            $('#slaModalLoading').show();
            $('#slaModalContent').attr('hidden', true).empty();
        } else {
            $('#slaModalLoading').hide();
        }
    }

    function renderVer(data) {
        var o = data.ordem;
        var s = data.sla;
        var pct = Math.min(s.percent, 100);
        var $wrap = $('<div class="sla-modal-ver"></div>');
        $wrap.append(
            $('<div class="sla-modal-ver-num"></div>').text(o.numero)
        );
        $wrap.append(
            $('<span class="badge-prioridade sla-modal-ver-prio"></span>')
                .css({ background: (o.prioridade_cor || '#888') + '22', color: o.prioridade_cor || '#888' })
                .text(o.prioridade_nome || '')
        );
        $wrap.append($('<h3 class="sla-modal-ver-titulo"></h3>').text(o.titulo));
        $wrap.append(
            $('<div class="sla-modal-ver-desc"></div>').text(o.descricao)
        );
        if (o.observacoes) {
            var $obs = $('<div class="sla-modal-ver-obs"></div>');
            $obs.append($('<strong></strong>').text('Observações'));
            $obs.append($('<p></p>').text(o.observacoes));
            $wrap.append($obs);
        }
        var $meta = $('<dl class="sla-modal-ver-meta"></dl>');
        function addRow(label, val) {
            $meta.append($('<dt></dt>').text(label));
            $meta.append($('<dd></dd>').text(val));
        }
        addRow('Cliente', (o.cliente_nome || '—') + (o.cliente_fantasia ? ' · ' + o.cliente_fantasia : ''));
        addRow('Categoria', o.categoria_nome || '—');
        addRow('Status', o.status.replace(/_/g, ' '));
        addRow('Responsável', o.responsavel_nome || '—');
        addRow('Abertura', o.data_abertura_fmt);
        addRow('Prazo SLA', o.sla_prazo_fmt);
        $wrap.append($meta);

        var $slaBox = $('<div class="sla-modal-ver-sla"></div>');
        $slaBox.append(
            $('<div class="sla-modal-ver-sla-head"></div>')
                .append($('<span class="sla-modal-ver-sla-label"></span>').text(s.label))
                .append($('<span class="sla-modal-ver-sla-pct"></span>').addClass(s.class).text(s.percent + '%'))
        );
        var $track = $('<div class="sla-progress-track sla-modal-ver-track"></div>');
        $track.append(
            $('<div class="sla-progress-fill"></div>')
                .addClass(s.class)
                .css('width', pct + '%')
        );
        $slaBox.append($('<div class="sla-progress-wrapper"></div>').append($track));
        $slaBox.append(
            $('<div class="sla-modal-ver-sla-foot"></div>')
                .append($('<span></span>').text('Restante: ' + s.restante))
                .append($('<span></span>').text('Aberta há: ' + s.aberto_ha))
        );
        $wrap.append($slaBox);

        $wrap.append(
            $('<a class="btn btn-secondary btn-sm sla-modal-ver-link"></a>')
                .attr('href', base + '/ordens/' + o.id)
                .html('<i class="fas fa-external-link-alt"></i> Abrir página completa')
        );

        return $wrap;
    }

    function renderComentarios(data, ordemId) {
        var $wrap = $('<div class="sla-modal-com"></div>');
        $wrap.append($('<p class="sla-modal-com-lead"></p>').text('OS ' + data.numero));

        var $list = $('<div class="sla-modal-com-list"></div>');
        if (!data.comentarios || data.comentarios.length === 0) {
            $list.append($('<p class="text-muted"></p>').text('Nenhum comentário ainda.'));
        } else {
            data.comentarios.forEach(function (c) {
                var $item = $('<div class="sla-modal-com-item"></div>');
                $item.append(
                    $('<div class="sla-modal-com-item-head"></div>')
                        .append($('<strong></strong>').text(c.usuario_nome || ''))
                        .append($('<span></span>').text(c.created_fmt || ''))
                );
                $item.append($('<div class="sla-modal-com-text"></div>').text(c.comentario));
                $list.append($item);
            });
        }
        $wrap.append($list);

        var $form = $('<form class="sla-modal-com-form"></form>');
        var $ta = $('<textarea name="comentario" class="form-control" rows="3" required placeholder="Novo comentário…"></textarea>');
        $form.append($('<div class="sla-modal-form-group"></div>').append($ta));
        $form.append(
            $('<button type="submit" class="btn btn-primary"></button>').html('<i class="fas fa-paper-plane"></i> Enviar')
        );

        $form.on('submit', function (e) {
            e.preventDefault();
            var txt = $.trim($ta.val());
            if (!txt) {
                return;
            }
            var fd = new FormData();
            fd.append('comentario', txt);
            $.ajax({
                url: base + '/ordens/' + ordemId + '/comentar',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: xhrHeaders()
            })
                .done(function (res) {
                    if (res && res.ok) {
                        closeHost();
                        window.location.reload();
                    }
                })
                .fail(function (xhr) {
                    var msg = 'Não foi possível enviar.';
                    try {
                        var j = xhr.responseJSON;
                        if (j && j.error) {
                            msg = j.error;
                        }
                    } catch (e2) {}
                    window.alert(msg);
                });
        });

        $wrap.append($form);
        return $wrap;
    }

    function loadVer(id) {
        setTitle('Detalhes da OS');
        showLoading(true);
        openHost();
        $.getJSON(base + '/api/sla/ordem/' + id + '/ver')
            .done(function (data) {
                if (!data || !data.ok) {
                    showLoading(false);
                    $('#slaModalContent').removeAttr('hidden').html('<p class="sla-modal-err">Dados indisponíveis.</p>');
                    return;
                }
                showLoading(false);
                var $el = renderVer(data);
                $('#slaModalContent').removeAttr('hidden').empty().append($el);
            })
            .fail(function (xhr) {
                showLoading(false);
                var msg = 'Erro ao carregar.';
                if (xhr.status === 403) {
                    msg = 'Sem permissão para ver esta OS.';
                } else if (xhr.status === 404) {
                    msg = 'OS não encontrada.';
                }
                $('#slaModalContent').removeAttr('hidden').html('<p class="sla-modal-err">' + esc(msg) + '</p>');
            });
    }

    function loadComentarios(id) {
        setTitle('Comentários');
        showLoading(true);
        openHost();
        $.getJSON(base + '/api/sla/ordem/' + id + '/comentarios')
            .done(function (data) {
                if (!data || !data.ok) {
                    showLoading(false);
                    $('#slaModalContent').removeAttr('hidden').html('<p class="sla-modal-err">Não foi possível carregar.</p>');
                    return;
                }
                showLoading(false);
                var $el = renderComentarios(data, id);
                $('#slaModalContent').removeAttr('hidden').empty().append($el);
            })
            .fail(function (xhr) {
                showLoading(false);
                var msg = 'Erro ao carregar.';
                if (xhr.status === 403) {
                    msg = 'Sem permissão.';
                }
                $('#slaModalContent').removeAttr('hidden').html('<p class="sla-modal-err">' + esc(msg) + '</p>');
            });
    }

    function loadEditar(id) {
        setTitle('Editar OS');
        showLoading(true);
        openHost();
        $.ajax({
            url: base + '/sla/ordem/' + id + '/modal-editar',
            method: 'GET',
            dataType: 'html'
        })
            .done(function (html) {
                showLoading(false);
                $('#slaModalContent').removeAttr('hidden').html(html);
            })
            .fail(function () {
                showLoading(false);
                $('#slaModalContent').removeAttr('hidden').html('<p class="sla-modal-err">Erro ao carregar o formulário.</p>');
            });
    }

    function modalReset(id) {
        setTitle('Renovar SLA');
        showLoading(false);
        openHost();
        var $box = $('<div class="sla-modal-confirm"></div>');
        $box.append(
            $('<p class="sla-modal-confirm-lead"></p>').html(
                '<i class="fas fa-redo-alt sla-modal-confirm-icon"></i> Recalcular o prazo de SLA <strong>a partir de agora</strong>. O cronômetro passa a contar do momento atual, com base na categoria e prioridade.'
            )
        );
        $box.append(
            $('<div class="sla-modal-confirm-actions"></div>')
                .append(
                    $('<button type="button" class="btn btn-ghost"></button>').text('Cancelar').on('click', closeHost)
                )
                .append(
                    $('<button type="button" class="btn btn-primary"></button>')
                        .html('<i class="fas fa-check"></i> Confirmar reset')
                        .on('click', function () {
                            var fd = new FormData();
                            $.ajax({
                                url: base + '/sla/ordem/' + id + '/resetar-sla',
                                method: 'POST',
                                data: fd,
                                processData: false,
                                contentType: false,
                                dataType: 'json',
                                headers: xhrHeaders()
                            })
                                .done(function (res) {
                                    if (res && res.ok) {
                                        closeHost();
                                        window.location.reload();
                                    }
                                })
                                .fail(function (xhr) {
                                    var msg = 'Falha ao resetar.';
                                    try {
                                        if (xhr.responseJSON && xhr.responseJSON.error) {
                                            msg = xhr.responseJSON.error;
                                        }
                                    } catch (e2) {}
                                    window.alert(msg);
                                });
                        })
                )
        );
        $('#slaModalLoading').hide();
        $('#slaModalContent').removeAttr('hidden').empty().append($box);
    }

    function modalConcluir(id) {
        setTitle('Concluir ordem');
        showLoading(false);
        openHost();
        var $box = $('<div class="sla-modal-confirm"></div>');
        $box.append(
            $('<p class="sla-modal-confirm-lead"></p>').html(
                '<i class="fas fa-flag-checkered sla-modal-confirm-icon"></i> Deseja <strong>finalizar</strong> esta ordem de serviço? Ela sairá do painel SLA.'
            )
        );
        $box.append(
            $('<div class="sla-modal-confirm-actions"></div>')
                .append($('<button type="button" class="btn btn-ghost"></button>').text('Cancelar').on('click', closeHost))
                .append(
                    $('<button type="button" class="btn btn-primary"></button>')
                        .html('<i class="fas fa-check"></i> Concluir OS')
                        .on('click', function () {
                            var fd = new FormData();
                            $.ajax({
                                url: base + '/sla/ordem/' + id + '/concluir',
                                method: 'POST',
                                data: fd,
                                processData: false,
                                contentType: false,
                                dataType: 'json',
                                headers: xhrHeaders()
                            })
                                .done(function (res) {
                                    if (res && res.ok) {
                                        closeHost();
                                        window.location.reload();
                                    }
                                })
                                .fail(function (xhr) {
                                    var msg = 'Não foi possível concluir.';
                                    try {
                                        if (xhr.responseJSON && xhr.responseJSON.error) {
                                            msg = xhr.responseJSON.error;
                                        }
                                    } catch (e2) {}
                                    window.alert(msg);
                                });
                        })
                )
        );
        $('#slaModalLoading').hide();
        $('#slaModalContent').removeAttr('hidden').empty().append($box);
    }

    $(document).on('click', '.js-sla-modal-ver', function (e) {
        e.preventDefault();
        var id = ordemIdFromBtn($(e.currentTarget));
        if (id) {
            loadVer(id);
        }
    });
    $(document).on('click', '.js-sla-modal-editar', function (e) {
        e.preventDefault();
        var id = ordemIdFromBtn($(e.currentTarget));
        if (id) {
            loadEditar(id);
        }
    });
    $(document).on('click', '.js-sla-modal-comentar', function (e) {
        e.preventDefault();
        var id = ordemIdFromBtn($(e.currentTarget));
        if (id) {
            loadComentarios(id);
        }
    });
    $(document).on('click', '.js-sla-modal-reset', function (e) {
        e.preventDefault();
        var id = ordemIdFromBtn($(e.currentTarget));
        if (id) {
            modalReset(id);
        }
    });
    $(document).on('click', '.js-sla-modal-concluir', function (e) {
        e.preventDefault();
        var id = ordemIdFromBtn($(e.currentTarget));
        if (id) {
            modalConcluir(id);
        }
    });

    $(document).on('click', '.js-sla-modal-close', function () {
        closeHost();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#slaModalHost').hasClass('is-open')) {
            closeHost();
        }
    });

    $(function () {
        $('#btnRefreshSla').on('click', function () {
            $(this).find('i').addClass('fa-spin');
            window.location.reload();
        });

        window.setTimeout(function () {
            window.location.reload();
        }, 120000);
    });
})(jQuery);
