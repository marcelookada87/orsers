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
    var scanBtnEl = document.getElementById('btnOsScan');
    var scanModalEl = document.getElementById('osScanModal');
    var scanInputEl = document.getElementById('osScanInput');
    var scanVideoEl = document.getElementById('osScanVideo');
    var scanHintEl = document.getElementById('osScanHint');
    var scanConfirmEl = document.getElementById('btnOsScanConfirm');
    var scanCamStartEl = document.getElementById('btnOsCamStart');
    var scanCamStopEl = document.getElementById('btnOsCamStop');
    var scanItemIdEl = document.getElementById('osItemIdModal');
    var scanQtdEl = document.getElementById('osItemQtdModal');
    var scanFound = null;
    var scanStream = null;
    var scanDetector = null;
    var scanLoopActive = false;
    var scanBufferTimer = null;

    function buildLookupUrl(code) {
        var relative = '/api/estoque/item-por-codigo?code=' + encodeURIComponent(code);
        if (base === '') {
            return relative;
        }
        return base + relative;
    }

    function setMsg(text, isErr) {
        if (!msgEl) {
            return;
        }
        msgEl.textContent = text || '';
        msgEl.classList.toggle('text-danger', !!isErr);
        msgEl.classList.toggle('text-muted', !isErr && !!text);
    }

    function setScanHint(text, isErr) {
        if (!scanHintEl) {
            return;
        }
        scanHintEl.textContent = text || '';
        scanHintEl.classList.toggle('text-danger', !!isErr);
        scanHintEl.classList.toggle('text-muted', !isErr);
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

    function postLancamentoScanner(itemId, qtdRaw, confirmarNegativo) {
        var fd = new FormData();
        fd.set('item_id', String(itemId));
        fd.set('quantidade', String(qtdRaw));
        fd.set('observacao', 'Lançamento via scanner');
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

    function openScanModal() {
        if (!scanModalEl) {
            return;
        }
        scanModalEl.classList.add('is-open');
        scanModalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        scanFound = null;
        if (scanItemIdEl) scanItemIdEl.value = '';
        if (scanQtdEl) scanQtdEl.value = '';
        setText('osItemCodigo', '—');
        setText('osItemNome', '—');
        setText('osItemUn', '—');
        setText('osItemSaldo', '—');
        setScanHint('Aponte a pistola no campo acima ou use a câmera.', false);
        if (scanInputEl) {
            scanInputEl.value = '';
            scanInputEl.focus();
        }
    }

    function closeScanModal() {
        if (!scanModalEl) {
            return;
        }
        stopCameraScan();
        scanModalEl.classList.remove('is-open');
        scanModalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function preencherModalItem(item) {
        scanFound = item || null;
        if (!item) {
            if (scanItemIdEl) scanItemIdEl.value = '';
            return;
        }
        if (scanItemIdEl) scanItemIdEl.value = String(item.id || '');
        setText('osItemCodigo', String(item.codigo || '—'));
        setText('osItemNome', String(item.nome || '—'));
        setText('osItemUn', String(item.unidade || '—'));
        setText('osItemSaldo', fmtNumPt(item.saldo_disponivel || 0) + ' ' + String(item.unidade || ''));
        if (scanQtdEl) {
            scanQtdEl.focus();
        }
    }

    function syncSelectWithItem(item) {
        if (!selectEl || !item || !item.id) {
            return;
        }
        var v = String(item.id);
        if (selectEl.querySelector('option[value="' + v + '"]')) {
            selectEl.value = v;
            syncDisponivelHint();
        }
    }

    function lookupScannedCode(rawCode) {
        var code = String(rawCode || '').trim();
        if (!code) {
            setScanHint('Leitura vazia.', true);
            return;
        }
        setScanHint('Buscando item pelo código...', false);
        fetch(buildLookupUrl(code), {
            headers: apiHeaders(),
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { okHttp: r.ok, j: j };
                });
            })
            .then(function (res) {
                var j = res.j || {};
                if (!j.ok || !j.item) {
                    setScanHint(j.message || j.error || 'Item não encontrado.', true);
                    preencherModalItem(null);
                    return;
                }
                preencherModalItem(j.item);
                syncSelectWithItem(j.item);
                setScanHint('Item localizado. Informe a quantidade e confirme.', false);
            })
            .catch(function () {
                setScanHint('Falha ao consultar item do código lido.', true);
            });
    }

    function onScanInputChanged() {
        if (!scanInputEl) {
            return;
        }
        var v = scanInputEl.value || '';
        if (scanBufferTimer) {
            window.clearTimeout(scanBufferTimer);
        }
        scanBufferTimer = window.setTimeout(function () {
            lookupScannedCode(v);
        }, 120);
    }

    function stopCameraScan() {
        scanLoopActive = false;
        if (scanStream) {
            scanStream.getTracks().forEach(function (t) {
                t.stop();
            });
            scanStream = null;
        }
        if (scanVideoEl) {
            scanVideoEl.pause();
            scanVideoEl.srcObject = null;
            scanVideoEl.style.display = 'none';
        }
        if (scanCamStartEl) scanCamStartEl.style.display = '';
        if (scanCamStopEl) scanCamStopEl.style.display = 'none';
    }

    function cameraLoop() {
        if (!scanLoopActive || !scanDetector || !scanVideoEl) {
            return;
        }
        scanDetector
            .detect(scanVideoEl)
            .then(function (codes) {
                if (codes && codes.length > 0) {
                    var val = String(codes[0].rawValue || '').trim();
                    if (val !== '') {
                        if (scanInputEl) {
                            scanInputEl.value = val;
                        }
                        lookupScannedCode(val);
                        stopCameraScan();
                        return;
                    }
                }
                if (scanLoopActive) {
                    window.requestAnimationFrame(cameraLoop);
                }
            })
            .catch(function () {
                if (scanLoopActive) {
                    window.requestAnimationFrame(cameraLoop);
                }
            });
    }

    function startCameraScan() {
        if (!scanVideoEl) {
            return;
        }
        if (!('BarcodeDetector' in window) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setScanHint('Seu navegador não suporta leitura por câmera. Use a pistola neste campo.', true);
            return;
        }
        if (!scanDetector) {
            try {
                scanDetector = new window.BarcodeDetector({
                    formats: ['qr_code', 'code_128', 'ean_13', 'ean_8', 'code_39', 'upc_a', 'upc_e']
                });
            } catch (e) {
                setScanHint('Falha ao iniciar leitor de câmera neste navegador.', true);
                return;
            }
        }
        navigator.mediaDevices
            .getUserMedia({ video: { facingMode: 'environment' }, audio: false })
            .then(function (stream) {
                scanStream = stream;
                scanVideoEl.srcObject = stream;
                scanVideoEl.style.display = 'block';
                scanVideoEl.play();
                scanLoopActive = true;
                if (scanCamStartEl) scanCamStartEl.style.display = 'none';
                if (scanCamStopEl) scanCamStopEl.style.display = '';
                setScanHint('Aponte a câmera para o código de barras/QR.', false);
                window.requestAnimationFrame(cameraLoop);
            })
            .catch(function () {
                setScanHint('Não foi possível acessar a câmera.', true);
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

    if (scanBtnEl) {
        scanBtnEl.addEventListener('click', function () {
            openScanModal();
        });
    }
    if (scanModalEl) {
        scanModalEl.querySelectorAll('[data-os-scan-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                closeScanModal();
            });
        });
        scanModalEl.addEventListener('click', function (ev) {
            if (ev.target === scanModalEl) {
                closeScanModal();
            }
        });
    }
    if (scanInputEl) {
        scanInputEl.addEventListener('input', onScanInputChanged);
        scanInputEl.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                lookupScannedCode(scanInputEl.value);
            }
        });
    }
    if (scanCamStartEl) {
        scanCamStartEl.addEventListener('click', function () {
            startCameraScan();
        });
    }
    if (scanCamStopEl) {
        scanCamStopEl.addEventListener('click', function () {
            stopCameraScan();
        });
    }
    if (scanConfirmEl) {
        scanConfirmEl.addEventListener('click', function () {
            if (!scanFound || !scanItemIdEl || !scanQtdEl) {
                setScanHint('Leia um item antes de confirmar.', true);
                return;
            }
            var itemId = parseInt(scanItemIdEl.value || '0', 10);
            var qtd = String(scanQtdEl.value || '').trim();
            if (!itemId) {
                setScanHint('Item inválido para lançamento.', true);
                return;
            }
            if (!qtd) {
                setScanHint('Informe a quantidade.', true);
                scanQtdEl.focus();
                return;
            }

            function tratarScannerRes(res, jaConfirmou) {
                var j = res.j || {};
                if (j.ok) {
                    closeScanModal();
                    if (formEl) {
                        formEl.reset();
                    }
                    carregar();
                    return;
                }
                if (j.code === 'saldo_insuficiente' && !jaConfirmou) {
                    var conf = window.confirm(
                        'Quantidade acima do saldo disponível. Deseja lançar mesmo assim e ficar com saldo negativo?'
                    );
                    if (conf) {
                        postLancamentoScanner(itemId, qtd, true).then(function (r2) {
                            tratarScannerRes(r2, true);
                        });
                        return;
                    }
                }
                setScanHint(j.error || j.message || 'Não foi possível lançar este item.', true);
            }

            postLancamentoScanner(itemId, qtd, false)
                .then(function (res) {
                    tratarScannerRes(res, false);
                })
                .catch(function () {
                    setScanHint('Falha ao lançar item via scanner.', true);
                });
        });
    }

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && scanModalEl && scanModalEl.classList.contains('is-open')) {
            closeScanModal();
        }
    });

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
