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

    function normalizaCode(raw) {
        var s = (raw || '').toString().trim();
        if (s === '') return '';
        var m = s.match(/(?:^|[;,\s])(code|codigo)\s*[:=]\s*([A-Za-z0-9._-]{1,64})/i);
        if (m && m[2]) {
            s = m[2];
        }
        return s.toUpperCase().slice(0, 64);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('itemViewModal');
        var scanModal = document.getElementById('catalogoScanModal');
        if (!modal) {
            return;
        }

        var base = (window.BASE_URL || '').replace(/\/$/, '');
        var btnScan = document.getElementById('btnCatalogoScan');
        var scanInput = document.getElementById('catalogoScanInput');
        var scanVideo = document.getElementById('catalogoScanVideo');
        var scanHint = document.getElementById('catalogoScanHint');
        var btnCamStart = document.getElementById('btnCatalogoCamStart');
        var btnCamStop = document.getElementById('btnCatalogoCamStop');
        var btnNovo = document.getElementById('btnCatalogoScanNovo');
        var btnEntrada = document.getElementById('btnCatalogoEntradaRapida');
        var fieldCodigo = document.querySelector('#catalogoNovoForm [name="codigo"]');
        var itemIdEl = document.getElementById('csItemId');
        var qtdEl = document.getElementById('csQtd');
        var nfEl = document.getElementById('csNfNumeroEntrada');
        var dtEl = document.getElementById('csDataCompra');
        var foundItem = null;
        var stream = null;
        var detector = null;
        var loopActive = false;
        var bufferTimer = null;

        function buildLookupUrl(code) {
            var relative = '/api/estoque/item-por-codigo?code=' + encodeURIComponent(code);
            if (base === '') {
                return relative;
            }
            return base + relative;
        }

        function setScanHint(text, isErr) {
            if (!scanHint) return;
            scanHint.textContent = text || '';
            scanHint.classList.toggle('text-danger', !!isErr);
            scanHint.classList.toggle('text-muted', !isErr);
        }

        function fillFound(item) {
            foundItem = item || null;
            if (itemIdEl) itemIdEl.value = item ? String(item.id || '') : '';
            setText('csCodigo', safeText(item && item.codigo));
            setText('csNome', safeText(item && item.nome));
            setText('csCategoria', safeText(item && item.categoria_nome));
            setText('csUnidade', safeText(item && item.unidade));
            setText('csStatus', safeText(item ? ((item.ativo ? 'Ativo' : 'Inativo')) : ''));
            setText('csDescricao', safeText(item && item.descricao));
            setText('csNf', safeText(item && item.nf_numero));
            setText('csFornecedor', safeText(item && item.fornecedor));
        }

        function stopCamera() {
            loopActive = false;
            if (stream) {
                stream.getTracks().forEach(function (t) {
                    t.stop();
                });
                stream = null;
            }
            if (scanVideo) {
                scanVideo.pause();
                scanVideo.srcObject = null;
                scanVideo.style.display = 'none';
            }
            if (btnCamStart) btnCamStart.style.display = '';
            if (btnCamStop) btnCamStop.style.display = 'none';
        }

        function cameraLoop() {
            if (!loopActive || !detector || !scanVideo) return;
            detector.detect(scanVideo).then(function (codes) {
                if (codes && codes.length > 0) {
                    var value = String(codes[0].rawValue || '').trim();
                    if (value !== '') {
                        if (scanInput) scanInput.value = value;
                        lookupCode(value);
                        stopCamera();
                        return;
                    }
                }
                if (loopActive) window.requestAnimationFrame(cameraLoop);
            }).catch(function () {
                if (loopActive) window.requestAnimationFrame(cameraLoop);
            });
        }

        function startCamera() {
            if (!('BarcodeDetector' in window) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setScanHint('Câmera não suportada neste navegador. Use a pistola.', true);
                return;
            }
            if (!detector) {
                try {
                    detector = new window.BarcodeDetector({
                        formats: ['qr_code', 'code_128', 'ean_13', 'ean_8', 'code_39', 'upc_a', 'upc_e']
                    });
                } catch (e) {
                    setScanHint('Falha ao iniciar leitor da câmera.', true);
                    return;
                }
            }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                .then(function (s) {
                    stream = s;
                    scanVideo.srcObject = s;
                    scanVideo.style.display = 'block';
                    scanVideo.play();
                    loopActive = true;
                    if (btnCamStart) btnCamStart.style.display = 'none';
                    if (btnCamStop) btnCamStop.style.display = '';
                    setScanHint('Aponte a câmera para o código de barras/QR.', false);
                    window.requestAnimationFrame(cameraLoop);
                })
                .catch(function () {
                    setScanHint('Não foi possível acessar a câmera.', true);
                });
        }

        function lookupCode(raw) {
            var code = normalizaCode(raw);
            if (code === '') {
                setScanHint('Leitura vazia.', true);
                fillFound(null);
                return;
            }
            setScanHint('Buscando item no catálogo...', false);
            fetch(buildLookupUrl(code), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) {
                    return r.json().then(function (j) {
                        return { ok: r.ok, j: j };
                    });
                })
                .then(function (res) {
                    var j = res.j || {};
                    if (!j.ok || !j.item) {
                        fillFound(null);
                        setScanHint(j.message || j.error || 'Item não encontrado. Você pode cadastrar novo.', true);
                        if (fieldCodigo) fieldCodigo.value = code;
                        return;
                    }
                    fillFound(j.item);
                    setScanHint('Item encontrado. Informe quantidade para entrada rápida.', false);
                    if (qtdEl) qtdEl.focus();
                })
                .catch(function () {
                    setScanHint('Erro ao consultar item por código.', true);
                });
        }

        function openScan() {
            if (!scanModal) return;
            openModal(scanModal);
            fillFound(null);
            if (scanInput) {
                scanInput.value = '';
                scanInput.focus();
            }
            if (qtdEl) qtdEl.value = '';
            if (nfEl) nfEl.value = '';
            if (dtEl) dtEl.value = '';
            setScanHint('Aponte a pistola ou use a câmera para ler o código.', false);
        }

        function closeScan() {
            if (!scanModal) return;
            stopCamera();
            closeModal(scanModal);
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
            if (e.target === modal) closeModal(modal);
        });

        if (btnScan) {
            btnScan.addEventListener('click', openScan);
        }
        if (scanModal) {
            scanModal.querySelectorAll('[data-catalogo-scan-close]').forEach(function (el) {
                el.addEventListener('click', closeScan);
            });
            scanModal.addEventListener('click', function (e) {
                if (e.target === scanModal) closeScan();
            });
        }
        if (scanInput) {
            scanInput.addEventListener('input', function () {
                if (bufferTimer) clearTimeout(bufferTimer);
                bufferTimer = setTimeout(function () {
                    lookupCode(scanInput.value);
                }, 120);
            });
            scanInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    lookupCode(scanInput.value);
                }
            });
        }
        if (btnCamStart) btnCamStart.addEventListener('click', startCamera);
        if (btnCamStop) btnCamStop.addEventListener('click', stopCamera);

        if (btnNovo) {
            btnNovo.addEventListener('click', function () {
                var code = normalizaCode(scanInput ? scanInput.value : '');
                if (fieldCodigo && code !== '') {
                    fieldCodigo.value = code;
                    fieldCodigo.focus();
                }
                closeScan();
            });
        }

        if (btnEntrada) {
            btnEntrada.addEventListener('click', function () {
                var itemId = parseInt(itemIdEl ? itemIdEl.value : '0', 10);
                var qtd = (qtdEl ? qtdEl.value : '').trim();
                if (!itemId) {
                    setScanHint('Leia um código de item existente para entrada rápida.', true);
                    return;
                }
                if (qtd === '') {
                    setScanHint('Quantidade é obrigatória.', true);
                    if (qtdEl) qtdEl.focus();
                    return;
                }
                var fd = new FormData();
                fd.set('item_id', String(itemId));
                fd.set('quantidade', qtd);
                fd.set('nf_numero', nfEl ? nfEl.value : '');
                fd.set('data_compra', dtEl ? dtEl.value : '');
                fetch(base + '/api/estoque/entrada-rapida', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    body: fd,
                    credentials: 'same-origin'
                })
                    .then(function (r) {
                        return r.json().then(function (j) {
                            return { ok: r.ok, j: j };
                        });
                    })
                    .then(function (res) {
                        var j = res.j || {};
                        if (!j.ok) {
                            setScanHint(j.error || j.message || 'Falha ao lançar entrada.', true);
                            return;
                        }
                        window.location.reload();
                    })
                    .catch(function () {
                        setScanHint('Falha na requisição de entrada rápida.', true);
                    });
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (scanModal && scanModal.classList.contains('is-open')) closeScan();
                if (modal.classList.contains('is-open')) closeModal(modal);
            }
        });
    });
})();
