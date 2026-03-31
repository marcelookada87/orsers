(function () {
    var card = document.getElementById('estoqueOsCard');
    if (!card || typeof window.BASE_URL === 'undefined') {
        return;
    }

    var ordemId = card.getAttribute('data-ordem');
    if (!ordemId && typeof window.ORDEM_ID !== 'undefined') {
        ordemId = String(window.ORDEM_ID);
    }
    if (!ordemId) {
        return;
    }
    ordemId = String(ordemId);
    var base = window.BASE_URL.replace(/\/$/, '');
    var listEl = card.querySelector('[data-estoque-list]');
    var formEl = card.querySelector('[data-estoque-form]');
    var msgEl = card.querySelector('[data-estoque-msg]');
    var hintDisponivelEl = card.querySelector('[data-estoque-disponivel]');
    var selectEl = formEl ? formEl.querySelector('[name="item_id"]') : null;
    var qtyInputEl = formEl ? formEl.querySelector('[name="quantidade"]') : null;

    function setMsg(text, isErr) {
        if (!msgEl) {
            return;
        }
        msgEl.textContent = text || '';
        msgEl.classList.toggle('text-danger', !!isErr);
        msgEl.classList.toggle('text-muted', !isErr && !!text);
    }

    function apiHeaders() {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json'
        };
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function fmtNumPt(n) {
        var x = parseFloat(String(n).replace(',', '.'));
        if (isNaN(x)) {
            return String(n);
        }
        var t = x.toFixed(3);
        t = t.replace(/0+$/, '');
        t = t.replace(/\.$/, '');
        if (t === '') {
            return '0';
        }
        return t.replace('.', ',');
    }

    function syncDisponivelHint() {
        if (!hintDisponivelEl || !selectEl) {
            return;
        }
        var opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) {
            hintDisponivelEl.textContent =
                'Escolha o material. O saldo aparece entre parênteses na lista e aqui quando selecionar.';
            if (qtyInputEl) {
                qtyInputEl.removeAttribute('max');
            }
            return;
        }
        var d = opt.getAttribute('data-disponivel') || '';
        var u = opt.getAttribute('data-unidade') || '';
        var cod = opt.getAttribute('data-codigo') || '';
        var nom = opt.getAttribute('data-nome') || '';
        var linhaItem =
            cod || nom
                ? '<strong>' + escapeHtml(cod) + '</strong> — ' + escapeHtml(nom) + '<br>'
                : '';
        hintDisponivelEl.innerHTML =
            linhaItem +
            'Saldo disponível: <strong>' +
            escapeHtml(fmtNumPt(d)) +
            '</strong> ' +
            escapeHtml(u) +
            '. Informe a quantidade usada (acima do saldo será pedida confirmação).';
        var n = parseFloat(String(d).replace(',', '.'));
        if (qtyInputEl && !isNaN(n) && n > 0) {
            qtyInputEl.setAttribute('max', String(n));
        } else if (qtyInputEl) {
            qtyInputEl.removeAttribute('max');
        }
    }

    function preencherSelect(itens) {
        if (!selectEl) {
            return;
        }
        var cur = selectEl.value;
        selectEl.innerHTML = '';
        var op0 = document.createElement('option');
        op0.value = '';
        op0.textContent = 'Material…';
        selectEl.appendChild(op0);
        (itens || []).forEach(function (it) {
            var o = document.createElement('option');
            var qFmt = fmtNumPt(it.quantidade);
            var un = String(it.unidade || '').trim();
            o.value = String(it.item_id);
            o.setAttribute('data-disponivel', String(it.quantidade));
            o.setAttribute('data-unidade', un);
            o.setAttribute('data-codigo', String(it.codigo || ''));
            o.setAttribute('data-nome', String(it.nome || ''));
            o.textContent = it.codigo + ' — ' + it.nome + ' (' + qFmt + (un ? ' ' + un : '') + ')';
            selectEl.appendChild(o);
        });
        if (cur && selectEl.querySelector('option[value="' + cur + '"]')) {
            selectEl.value = cur;
        }
        syncDisponivelHint();
    }

    function carregarSaldo() {
        if (!selectEl) {
            return;
        }
        fetch(base + '/api/estoque/saldo', { headers: apiHeaders(), credentials: 'same-origin' })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { okHttp: r.ok, j: j };
                });
            })
            .then(function (res) {
                var data = res.j;
                if (!data.ok) {
                    setMsg(data.error || 'Não foi possível carregar seu saldo.', true);
                    return;
                }
                var lista = data.itens || [];
                preencherSelect(lista);
                if (lista.length === 0) {
                    setMsg('Não há itens com saldo disponível. Cadastre no catálogo (perfil técnico), faça entrada em Meu estoque e volte aqui para lançar.', false);
                } else {
                    setMsg('');
                }
            })
            .catch(function () {
                setMsg('Erro ao carregar saldo para lançamento.', true);
            });
    }

    function renderRows(itens, podeEditar) {
        if (!listEl) {
            return;
        }
        if (!itens || !itens.length) {
            listEl.innerHTML = '<p class="text-muted compact">Nenhum material lançado nesta OS.</p>';
            return;
        }
        var html = '<table class="table table-sm"><thead><tr><th>Item</th><th>Qtd</th><th>Por</th>';
        if (podeEditar) {
            html += '<th></th>';
        }
        html += '</tr></thead><tbody>';
        itens.forEach(function (row) {
            html += '<tr data-os-item="' + row.id + '">';
            html += '<td><code>' + escapeHtml(row.item_codigo) + '</code> ' + escapeHtml(row.item_nome) + '</td>';
            html += '<td>' + escapeHtml(String(row.quantidade)) + ' ' + escapeHtml(row.item_unidade) + '</td>';
            html += '<td>' + escapeHtml(row.usuario_nome || '') + '</td>';
            if (podeEditar) {
                html += '<td><button type="button" class="btn btn-ghost btn-sm estoque-remove" data-id="' + row.id + '" title="Remover e devolver ao estoque"><i class="fas fa-times"></i></button></td>';
            }
            html += '</tr>';
        });
        html += '</tbody></table>';
        listEl.innerHTML = html;
        if (podeEditar) {
            listEl.querySelectorAll('.estoque-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    remover(btn.getAttribute('data-id'));
                });
            });
        }
    }

    function carregar() {
        setMsg('');
        fetch(base + '/api/estoque/os/' + encodeURIComponent(ordemId) + '/itens', {
            headers: apiHeaders(),
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.ok) {
                    setMsg(data.error || 'Não foi possível carregar materiais.', true);
                    return;
                }
                renderRows(data.itens, data.pode_editar);
                if (formEl) {
                    formEl.style.display = data.pode_editar ? '' : 'none';
                }
                if (data.motivo_bloqueio) {
                    setMsg(data.motivo_bloqueio, false);
                }
                if (data.pode_editar) {
                    carregarSaldo();
                }
            })
            .catch(function () {
                setMsg('Erro ao carregar materiais.', true);
            });
    }

    function postLancamento(confirmarNegativo) {
        var fd = new FormData(formEl);
        if (confirmarNegativo) {
            fd.set('confirmar_saldo_negativo', '1');
        }
        return fetch(base + '/estoque/os/' + encodeURIComponent(ordemId) + '/item', {
            method: 'POST',
            headers: apiHeaders(),
            body: fd,
            credentials: 'same-origin'
        }).then(function (r) {
            return r.json().then(function (j) {
                return { okHttp: r.ok, j: j };
            });
        });
    }

    if (selectEl) {
        selectEl.addEventListener('change', function () {
            syncDisponivelHint();
            if (qtyInputEl) {
                qtyInputEl.focus();
            }
        });
    }

    if (formEl) {
        formEl.addEventListener('submit', function (ev) {
            ev.preventDefault();
            setMsg('');
            var itemId = parseInt(String(formEl.querySelector('[name="item_id"]').value || ''), 10);
            if (!itemId) {
                setMsg('Selecione um material.', true);
                return;
            }

            function tratarResposta(res, jaConfirmou) {
                var j = res.j;
                if (j.ok) {
                    formEl.reset();
                    carregar();
                    return undefined;
                }
                if (j.code === 'saldo_insuficiente' && !jaConfirmou) {
                    var msgLinha = j.message || 'Saldo insuficiente.';
                    setMsg(msgLinha, true);
                    var conf = window.confirm(
                        'A quantidade informada é maior que o disponível no seu estoque. ' +
                            'Se continuar, o saldo ficará negativo. Deseja usar mesmo assim?'
                    );
                    if (conf) {
                        return postLancamento(true).then(function (r2) {
                            tratarResposta(r2, true);
                        });
                    }
                    return undefined;
                }
                setMsg(j.error || j.message || 'Não foi possível lançar.', true);
                return undefined;
            }

            postLancamento(false)
                .then(function (res) {
                    return tratarResposta(res, false);
                })
                .catch(function () {
                    setMsg('Falha na requisição.', true);
                });
        });
    }

    function remover(osItemId) {
        if (!window.confirm('Remover este material da OS e devolver ao estoque?')) {
            return;
        }
        setMsg('');
        var fd = new FormData();
        fetch(base + '/estoque/os/' + encodeURIComponent(ordemId) + '/item/' + encodeURIComponent(osItemId) + '/remover', {
            method: 'POST',
            headers: apiHeaders(),
            body: fd,
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { ok: r.ok, j: j };
                });
            })
            .then(function (res) {
                if (!res.j.ok) {
                    setMsg(res.j.error || 'Não foi possível remover.', true);
                    return;
                }
                carregar();
            })
            .catch(function () {
                setMsg('Falha ao remover.', true);
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        carregar();
    });
})();
