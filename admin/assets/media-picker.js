(function () {
    var style = document.createElement('style');
    style.textContent = `
    .spm-media-picker-actions{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:8px 0}.spm-media-picker-btn,.spm-media-picker-clear{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#111827;font-weight:700;font-size:12px;cursor:pointer}
    .spm-media-picker-btn:hover{border-color:#dc2626;color:#b91c1c}.spm-media-picker-clear{color:#6b7280}.spm-media-picker-clear:hover{border-color:#b91c1c;color:#b91c1c}.spm-media-picker-status{width:100%;color:#4b5563;font-size:12px}
    .spm-media-picker-modal{position:fixed;z-index:1000;inset:0;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.62)}
    .spm-media-picker-modal.open{display:flex}.spm-media-picker-panel{width:min(980px,100%);max-height:min(820px,92vh);overflow:hidden;border-radius:14px;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.35)}
    .spm-media-picker-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #e5e7eb}.spm-media-picker-head strong{font-size:16px}.spm-media-picker-head input{width:min(280px,45%);padding:9px 11px;border:1px solid #d1d5db;border-radius:8px}
    .spm-media-picker-close{border:0;background:transparent;font-size:22px;cursor:pointer}.spm-media-picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;padding:16px;overflow:auto;max-height:calc(92vh - 76px)}
    .spm-media-picker-card{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fff;text-align:left;cursor:pointer;padding:0}.spm-media-picker-card:hover{border-color:#dc2626}.spm-media-picker-thumb{height:125px;background:#f3f4f6;display:grid;place-items:center}.spm-media-picker-thumb img{width:100%;height:100%;object-fit:cover}.spm-media-picker-thumb video{width:100%;height:100%;object-fit:cover}
    .spm-media-picker-name{display:block;padding:9px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px}.spm-media-picker-empty{grid-column:1/-1;padding:40px;text-align:center;color:#6b7280}`;
    document.head.appendChild(style);

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function mediaType(input) {
        var accept = (input.getAttribute('accept') || '').toLowerCase();
        return accept.indexOf('video') !== -1 ? 'video' : 'image';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inputs = Array.prototype.slice.call(document.querySelectorAll('input[type="file"]'));
        if (!inputs.length) return;

        var modal = document.createElement('div');
        modal.className = 'spm-media-picker-modal';
        modal.innerHTML = '<div class="spm-media-picker-panel"><div class="spm-media-picker-head"><strong>Pilih dari Media</strong><input type="search" placeholder="Cari media..." aria-label="Cari media"><button type="button" class="spm-media-picker-close" aria-label="Tutup">&times;</button></div><div class="spm-media-picker-grid"></div></div>';
        document.body.appendChild(modal);

        var search = modal.querySelector('input');
        var grid = modal.querySelector('.spm-media-picker-grid');
        var activeInput = null;
        var activeType = 'image';
        var timer = null;

        function close() {
            modal.classList.remove('open');
            activeInput = null;
        }

        function load() {
            grid.innerHTML = '<div class="spm-media-picker-empty">Memuat media...</div>';
            var params = new URLSearchParams({ action: 'picker', type: activeType, search: search.value });
            var scriptElement = document.querySelector('script[src*="media-picker.js"]');
            var scriptUrl = scriptElement ? scriptElement.src : '';
            var rootUrl = scriptUrl ? scriptUrl.replace(/admin\/assets\/media-picker\.js(?:\?.*)?$/i, '') : (window.location.origin + '/');
            fetch(rootUrl + 'admin/media.php?' + params.toString(), { credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || 'Daftar media gagal dimuat.');
                    if (!data.items || !data.items.length) {
                        grid.innerHTML = '<div class="spm-media-picker-empty">Belum ada media yang sesuai.</div>';
                        return;
                    }
                    grid.innerHTML = data.items.map(function (item) {
                        var thumb = item.file_type === 'video'
                            ? '<video muted preload="metadata" src="' + escapeHtml(item.url) + '"></video>'
                            : '<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.alt_text || item.title || '') + '">';
                        return '<button type="button" class="spm-media-picker-card" data-path="' + escapeHtml(item.file_path) + '">' +
                            '<span class="spm-media-picker-thumb">' + thumb + '</span>' +
                            '<span class="spm-media-picker-name">' + escapeHtml(item.title || item.file_name) + '</span></button>';
                    }).join('');
                })
                .catch(function (error) {
                    grid.innerHTML = '<div class="spm-media-picker-empty">' + escapeHtml(error.message) + '</div>';
                });
        }

        modal.querySelector('.spm-media-picker-close').addEventListener('click', close);
        modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('open')) close();
        });
        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(load, 250);
        });

        grid.addEventListener('click', function (event) {
            var card = event.target.closest('.spm-media-picker-card');
            if (!card || !activeInput) return;
            var path = card.getAttribute('data-path') || '';
            var name = activeInput.name || 'image';
            var baseName = name.replace(/\[\]$/, '');
            var hidden = activeInput.parentNode.querySelector('input[type="hidden"][data-media-for="' + baseName + '"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = /\[\]$/.test(name) ? baseName + '_media[]' : baseName + '_media';
                hidden.setAttribute('data-media-for', baseName);
                activeInput.parentNode.insertBefore(hidden, activeInput);
            }
            hidden.value = path;
            activeInput.value = '';
            var status = activeInput.parentNode.querySelector('[data-media-status="' + baseName + '"]');
            if (status) {
                var nameLabel = card.querySelector('.spm-media-picker-name');
                status.textContent = 'Dipilih dari Media: ' + (nameLabel ? nameLabel.textContent : 'file media');
            }
            close();
        });

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var baseName = (input.name || 'image').replace(/\[\]$/, '');
                var hidden = input.parentNode.querySelector('input[type="hidden"][data-media-for="' + baseName + '"]');
                if (hidden) hidden.value = '';
                if (baseName === 'logo' && input.form) {
                    var actionField = input.form.querySelector('input[name="action"][type="hidden"]');
                    if (actionField) actionField.value = 'upload_logo';
                }
                var removeName = baseName === 'featured_image' ? 'remove_featured_image' : 'remove_' + baseName;
                var removeField = input.form ? input.form.querySelector('[name="' + removeName + '"]') : null;
                if (removeField) {
                    if (removeField.type === 'checkbox') removeField.checked = false;
                    else if (removeField.type === 'hidden') removeField.remove();
                }
                var status = input.parentNode.querySelector('[data-media-status="' + baseName + '"]');
                if (status) status.textContent = input.files && input.files.length
                    ? 'Upload baru dipilih: ' + input.files[0].name
                    : '';
                var container = input.closest('.current-media, .upload-preview, .image-preview, .upload-box, .media-box, .image-box, .form-group');
                if (container) {
                    container.querySelectorAll('img, video').forEach(function (preview) {
                        preview.style.display = '';
                    });
                }
            });

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'spm-media-picker-btn';
            button.innerHTML = '<i class="fa-solid fa-photo-film"></i> Pilih dari Media';
            button.addEventListener('click', function () {
                activeInput = input;
                activeType = mediaType(input);
                search.value = '';
                modal.classList.add('open');
                load();
            });

            var clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.className = 'spm-media-picker-clear';
            clearButton.innerHTML = '<i class="fa-solid fa-xmark"></i> Batal';
            clearButton.title = 'Kosongkan pilihan upload atau media';

            var baseName = (input.name || 'image').replace(/\[\]$/, '');
            var status = document.createElement('span');
            status.className = 'spm-media-picker-status';
            status.setAttribute('data-media-status', baseName);

            clearButton.addEventListener('click', function () {
                input.value = '';
                var hidden = input.parentNode.querySelector('input[type="hidden"][data-media-for="' + baseName + '"]');
                if (hidden) hidden.value = '';
                status.textContent = 'Pilihan upload dibatalkan.';
                var removeName = baseName === 'featured_image' ? 'remove_featured_image' : 'remove_' + baseName;
                var removeField = input.form && input.form.querySelector('[name="' + removeName + '"], [data-remove-media="' + baseName + '"]');
                if (baseName === 'logo') {
                    var actionField = input.form ? input.form.querySelector('input[name="action"][type="hidden"]') : null;
                    if (actionField) actionField.value = 'remove_logo';
                    status.textContent = 'Logo lama akan dihapus saat disimpan.';
                } else if (removeField) {
                    if (removeField.type === 'checkbox') removeField.checked = true;
                    else removeField.value = '1';
                    status.textContent = 'Media lama akan dihapus saat disimpan.';
                } else {
                    removeField = document.createElement('input');
                    removeField.type = 'hidden';
                    removeField.name = removeName;
                    removeField.value = '1';
                    removeField.setAttribute('data-remove-media', baseName);
                    input.form ? input.form.appendChild(removeField) : input.parentNode.appendChild(removeField);
                    status.textContent = 'Media lama akan dihapus saat disimpan.';
                }

                var container = input.closest('.current-media, .upload-preview, .image-preview, .upload-box, .media-box, .image-box, .form-group');
                if (container) {
                    container.querySelectorAll('img, video').forEach(function (preview) {
                        preview.style.display = 'none';
                    });
                }
            });

            var actions = document.createElement('div');
            actions.className = 'spm-media-picker-actions';
            actions.appendChild(button);
            actions.appendChild(clearButton);
            actions.appendChild(status);
            input.insertAdjacentElement('afterend', actions);
        });
    });
})();
