/**
 * formOsImagens (página da OS): POST multipart via jQuery.ajax — Chrome não envia
 * ficheiros definidos com DataTransfer no submit HTML. Servidor responde JSON + redirect.
 *
 * formOS (criar/editar): ficheiros do diálogo ficam em input.files (submit nativo).
 * Arrastar e largar exige DataTransfer no input; depois dispara change para pré-visualizar.
 */
$(function () {
    var base = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : '';
    var RAW_MAX_BYTES = 20 * 1024 * 1024;
    var ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    var $fileInput = $('#fileInput');
    var $uploadArea = $('#uploadArea');
    if (!$fileInput.length || !$fileInput[0] || !$uploadArea.length) {
        return;
    }

    var $formImagens = $('#formOsImagens');
    var $formOS = $('#formOS');

    var maxAttr = parseInt($uploadArea.attr('data-max-files'), 10);
    var MAX = (!isNaN(maxAttr) && maxAttr > 0) ? maxAttr : 10;

    var $placeholder = $('#uploadPlaceholder');
    var $grid = $('#imagePreviewGrid');
    var $counterSpan = $('#imageCount');

    function revokeGridBlobs() {
        $grid.find('img').each(function () {
            var s = this.src || '';
            if (s.indexOf('blob:') === 0) {
                try {
                    URL.revokeObjectURL(s);
                } catch (ignore) {
                }
            }
        });
    }

    function updateCounter(n) {
        if ($counterSpan.length) {
            $counterSpan.text(n);
        }
    }

    function extOk(name) {
        var ext = (name.split('.').pop() || '').toLowerCase();
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1;
    }

    function isImageFile(file) {
        if (file.type && ALLOWED_TYPES.indexOf(file.type.toLowerCase()) !== -1) {
            return true;
        }
        return !file.type && extOk(file.name);
    }

    function loadImageFromFile(file) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () {
                var img = new Image();
                img.onload = function () { resolve(img); };
                img.onerror = function () { reject(new Error('Imagem inválida')); };
                img.src = reader.result;
            };
            reader.onerror = function () { reject(new Error('Falha ao ler imagem')); };
            reader.readAsDataURL(file);
        });
    }

    function canvasToBlob(canvas, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', quality);
        });
    }

    async function compressOneFile(file) {
        if (!isImageFile(file)) {
            throw new Error('Tipo de imagem não permitido: ' + file.name);
        }
        if ((file.size || 0) > RAW_MAX_BYTES) {
            throw new Error('Imagem maior que 20MB: ' + file.name);
        }
        var img = await loadImageFromFile(file);
        var maxDim = 1600;
        var w = img.naturalWidth || img.width;
        var h = img.naturalHeight || img.height;
        if (!w || !h) {
            throw new Error('Não foi possível ler dimensões da imagem: ' + file.name);
        }
        var ratio = Math.min(1, maxDim / Math.max(w, h));
        var nw = Math.max(1, Math.round(w * ratio));
        var nh = Math.max(1, Math.round(h * ratio));
        var canvas = document.createElement('canvas');
        canvas.width = nw;
        canvas.height = nh;
        var ctx = canvas.getContext('2d', { alpha: false });
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, nw, nh);
        ctx.drawImage(img, 0, 0, nw, nh);

        var qualities = [0.82, 0.75, 0.68, 0.62, 0.55];
        var targetBytes = 900 * 1024;
        var blob = null;
        for (var i = 0; i < qualities.length; i++) {
            blob = await canvasToBlob(canvas, qualities[i]);
            if (blob && blob.size <= targetBytes) {
                break;
            }
        }
        if (!blob) {
            throw new Error('Falha ao comprimir imagem: ' + file.name);
        }
        return new File([blob], (file.name || 'imagem').replace(/\.[^.]+$/, '') + '.jpg', {
            type: 'image/jpeg',
            lastModified: Date.now()
        });
    }

    async function compressFilesList(files) {
        var out = [];
        var list = Array.from(files || []).slice(0, MAX);
        for (var i = 0; i < list.length; i++) {
            out.push(await compressOneFile(list[i]));
        }
        return out;
    }

    /* ---------- Apenas formulário da OS na view (AJAX + JSON) ---------- */
    if ($formImagens.length) {
        var fileStore = [];

        function fileKey(f) {
            return f.name + '|' + f.size + '|' + (f.lastModified || 0);
        }

        function applyFilesToInput() {
            var el = $fileInput[0];
            var dt = new DataTransfer();
            fileStore.forEach(function (f) {
                try {
                    dt.items.add(f);
                } catch (ignore) {
                }
            });
            try {
                el.files = dt.files;
            } catch (ignore) {
            }
        }

        function mergeIncoming(fileList) {
            Array.from(fileList || []).forEach(function (f) {
                if (!isImageFile(f) || fileStore.length >= MAX) {
                    return;
                }
                var k = fileKey(f);
                if (fileStore.some(function (x) {
                    return fileKey(x) === k;
                })) {
                    return;
                }
                fileStore.push(f);
            });
        }

        function renderPreviews() {
            revokeGridBlobs();
            $grid.empty();
            if (fileStore.length === 0) {
                $placeholder.show();
                updateCounter(0);
                return;
            }
            $placeholder.hide();
            updateCounter(fileStore.length);
            fileStore.forEach(function (file, index) {
                var url = URL.createObjectURL(file);
                var $item = $('<div class="preview-item" data-index="' + index + '">' +
                    '<img src="' + url + '" alt="">' +
                    '<button type="button" class="preview-remove" data-index="' + index + '">' +
                    '<i class="fas fa-times"></i></button></div>');
                $grid.append($item);
            });
        }

        function afterSelection() {
            applyFilesToInput();
            renderPreviews();
        }

        function getFilesForUpload() {
            return fileStore.length ? fileStore.slice() : [];
        }

        $fileInput.on('change', function () {
            mergeIncoming(this.files);
            afterSelection();
        });

        $(document).on('click', '.preview-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var idx = parseInt($(this).attr('data-index'), 10);
            if (isNaN(idx) || idx < 0 || idx >= fileStore.length) {
                return;
            }
            fileStore.splice(idx, 1);
            afterSelection();
        });

        $formImagens.on('submit', function (e) {
            e.preventDefault();
            var files = getFilesForUpload();
            if (!files.length) {
                window.alert('Selecione pelo menos uma imagem (JPG, PNG, WebP ou GIF).');
                return;
            }

            var $btns = $formImagens.find('[type="submit"]');
            $btns.prop('disabled', true);
            var formEl = this;
            compressFilesList(files).then(function (compressed) {
                var fd = new FormData(formEl);
                try {
                    fd.delete('imagens[]');
                    fd.delete('imagens');
                } catch (ignore) {
                }
                compressed.forEach(function (f) {
                    fd.append('imagens[]', f, f.name);
                });
                return $.ajax({
                    url: formEl.action || (base + '/ordens'),
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    dataType: 'json',
                    cache: false
                });
            }).then(function (data) {
                if (!data || typeof data !== 'object') {
                    window.location.reload();
                    return;
                }
                if (data.ok === false && data.error) {
                    window.alert(data.error);
                    $btns.prop('disabled', false);
                    return;
                }
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                window.location.reload();
            }).catch(function (xhr) {
                $btns.prop('disabled', false);
                if (xhr && xhr.status === 0) {
                    window.alert('Erro de rede. Tente novamente.');
                    return;
                }
                if (xhr && xhr.status === 401 && xhr.responseJSON && xhr.responseJSON.redirect) {
                    window.location.href = xhr.responseJSON.redirect;
                    return;
                }
                var msg = 'Não foi possível enviar as imagens.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                } else if (xhr && xhr.responseText) {
                    try {
                        var j = JSON.parse(xhr.responseText);
                        if (j && j.error) msg = j.error;
                    } catch (ignore) {
                    }
                } else if (xhr && xhr.message) {
                    msg = xhr.message;
                }
                window.alert(msg);
            });
        });

        $uploadArea.on('click', function (e) {
            if ($(e.target).closest('.preview-remove').length) {
                return;
            }
            if (e.target === $fileInput[0]) {
                return;
            }
            try {
                $fileInput.trigger('click');
            } catch (ignore) {
            }
        });

        $uploadArea.on('dragover', function (e) {
            e.preventDefault();
            $uploadArea.addClass('drag-over');
        }).on('dragleave drop', function (e) {
            e.preventDefault();
            $uploadArea.removeClass('drag-over');
            if (e.type === 'drop') {
                mergeIncoming(e.originalEvent.dataTransfer.files);
                afterSelection();
            }
        });

        return;
    }

    /* ---------- Criar / Editar OS: preview nativo, submit nativo ---------- */
    if ($formOS.length) {
        function fileKeyOs(f) {
            return f.name + '|' + f.size + '|' + (f.lastModified || 0);
        }

        function mergeIntoFileInput(incomingList) {
            var el = $fileInput[0];
            var dt = new DataTransfer();
            var seen = {};
            var ordered = [];

            function pushUnique(f) {
                if (!isImageFile(f)) {
                    return;
                }
                var k = fileKeyOs(f);
                if (seen[k]) {
                    return;
                }
                seen[k] = true;
                ordered.push(f);
            }

            Array.from(el.files || []).forEach(pushUnique);
            Array.from(incomingList || []).forEach(pushUnique);

            ordered = ordered.slice(0, MAX);
            ordered.forEach(function (f) {
                try {
                    dt.items.add(f);
                } catch (ignore) {
                }
            });
            try {
                el.files = dt.files;
            } catch (ignore) {
            }
        }

        $fileInput.on('change', function () {
            revokeGridBlobs();
            $grid.empty();
            var files = this.files;
            if (!files || !files.length) {
                $placeholder.show();
                updateCounter(0);
                return;
            }
            $placeholder.hide();
            updateCounter(files.length);
            Array.from(files).forEach(function (file, index) {
                var url = URL.createObjectURL(file);
                var $item = $('<div class="preview-item preview-item-readonly" data-index="' + index + '">' +
                    '<img src="' + url + '" alt=""></div>');
                $grid.append($item);
            });
        });

        $uploadArea.on('click', function (e) {
            if (e.target === $fileInput[0]) {
                return;
            }
            try {
                $fileInput.trigger('click');
            } catch (ignore) {
            }
        });

        $uploadArea.on('dragover', function (e) {
            e.preventDefault();
            $uploadArea.addClass('drag-over');
        }).on('dragleave drop', function (e) {
            e.preventDefault();
            $uploadArea.removeClass('drag-over');
            if (e.type === 'drop') {
                mergeIntoFileInput(e.originalEvent.dataTransfer.files);
                $fileInput.trigger('change');
            }
        });

        $formOS.on('submit', function (e) {
            if ($(this).data('compressed-submit') === '1') {
                return;
            }
            var files = Array.from($fileInput[0].files || []);
            if (!files.length) {
                return;
            }
            e.preventDefault();
            var formEl = this;
            var $submitBtns = $(formEl).find('[type="submit"]');
            $submitBtns.prop('disabled', true);
            compressFilesList(files).then(function (compressed) {
                var dt = new DataTransfer();
                compressed.forEach(function (f) {
                    try {
                        dt.items.add(f);
                    } catch (ignore) {
                    }
                });
                $fileInput[0].files = dt.files;
                $(formEl).data('compressed-submit', '1');
                formEl.submit();
            }).catch(function (err) {
                $submitBtns.prop('disabled', false);
                window.alert(err && err.message ? err.message : 'Falha ao preparar imagens para envio.');
            });
        });
    }
});
