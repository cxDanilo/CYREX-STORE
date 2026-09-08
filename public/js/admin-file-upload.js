// Uploader de archivo del admin — mejora progresiva sobre el
// input[type=file] real (que sigue ahí, oculto, mandando exactamente
// lo mismo de siempre al form). Ver partials/admin-file-upload.blade.php.
(function () {
  function humanSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function setup(box) {
    var input = box.querySelector('[data-file-upload-input]');
    var trigger = box.querySelector('[data-file-upload-trigger]');
    var removeBtn = box.querySelector('[data-file-upload-remove]');
    var removeInput = box.querySelector('[data-file-upload-remove-input]');
    var thumb = box.querySelector('[data-file-upload-thumb]');
    var nameEl = box.querySelector('[data-file-upload-name]');
    if (!input || !trigger) return;

    trigger.addEventListener('click', function () { input.click(); });

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;

      if (removeInput) removeInput.checked = false;
      nameEl.textContent = file.name + ' · ' + humanSize(file.size);
      trigger.textContent = 'Reemplazar';

      if (file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function (e) {
          thumb.innerHTML = '<img src="' + e.target.result + '" alt="">';
        };
        reader.readAsDataURL(file);
      }
    });

    if (removeBtn && removeInput) {
      removeBtn.addEventListener('click', function () {
        input.value = '';
        removeInput.checked = true;
        nameEl.textContent = 'Se va a quitar al guardar';
        thumb.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 16l4.5-6 3.5 4.5 2.5-3L20 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>';
        trigger.textContent = 'Subir imagen';
      });
    }

    ['dragover', 'dragenter'].forEach(function (evt) {
      box.addEventListener(evt, function (e) {
        e.preventDefault();
        box.classList.add('is-dragover');
      });
    });
    ['dragleave', 'dragend'].forEach(function (evt) {
      box.addEventListener(evt, function () { box.classList.remove('is-dragover'); });
    });
    box.addEventListener('drop', function (e) {
      e.preventDefault();
      box.classList.remove('is-dragover');
      var file = e.dataTransfer.files && e.dataTransfer.files[0];
      if (!file) return;
      input.files = e.dataTransfer.files;
      input.dispatchEvent(new Event('change'));
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-file-upload]').forEach(setup);
  });
})();
