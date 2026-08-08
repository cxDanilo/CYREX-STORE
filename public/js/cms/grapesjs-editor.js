/**
 * Adaptador GrapesJS para el CMS de Cyrex Store.
 *
 * Este es el ÚNICO archivo del proyecto que conoce GrapesJS. Habla con el
 * backend exclusivamente a través del contrato JSON genérico expuesto por
 * Admin\PageBlockController (GET/PUT /admin/paginas/{page}/bloques,
 * POST .../bloques/preview) — ese contrato no sabe nada de GrapesJS.
 *
 * Si en el futuro se reemplaza GrapesJS por otro editor, se reemplaza este
 * archivo (y la vista que lo carga) — el backend no se toca.
 *
 * Regla de diseño no negociable: el HTML que arma GrapesJS en el canvas
 * NUNCA se guarda. Lo que se guarda es siempre {type, data, sort_order}
 * por bloque, extraído de las propiedades del modelo — el canvas solo
 * muestra un preview (renderizado por el servidor vía PageRenderer),
 * nunca es editable en línea.
 */
(function () {
  function debounce(fn, wait) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function csrfHeaders(csrfToken) {
    return {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    };
  }

  function buildTraits(fields) {
    return Object.keys(fields || {}).map((key) => {
      const f = fields[key];

      if (f.type === 'textarea') {
        return { type: 'textarea', name: key, label: f.label, changeProp: true };
      }

      if (f.type === 'select') {
        return { type: 'select', name: key, label: f.label, changeProp: true, options: f.options || [] };
      }

      if (f.type === 'number') {
        return { type: 'number', name: key, label: f.label, changeProp: true };
      }

      if (f.type === 'media') {
        return { type: 'media', name: key, label: f.label, changeProp: true };
      }

      if (f.type === 'repeater') {
        return {
          type: 'repeater',
          name: key,
          label: f.label,
          changeProp: true,
          subFields: f.fields || {},
        };
      }

      return { type: 'text', name: key, label: f.label, changeProp: true };
    });
  }

  /**
   * Campo compartido por el trait 'media' y por las filas de un
   * repeater con un subcampo 'media': un input de URL de toda la vida
   * (para pegar un link externo) + un botón que sube el archivo directo
   * a la Biblioteca de Medios y completa la URL solo. Una sola
   * implementación para no mantener la lógica de subida duplicada.
   */
  function buildMediaWidget(initialValue, onChange, mediaUploadUrl, csrfToken) {
    const wrap = document.createElement('div');
    wrap.className = 'cms-media-field';

    const thumb = document.createElement('img');
    thumb.className = 'cms-media-thumb';
    thumb.style.display = 'none';
    thumb.addEventListener('error', () => { thumb.style.display = 'none'; });

    function updateThumb(url) {
      if (url) {
        thumb.src = url;
        thumb.style.display = 'block';
      } else {
        thumb.removeAttribute('src');
        thumb.style.display = 'none';
      }
    }

    const controls = document.createElement('div');
    controls.className = 'cms-media-controls';

    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.className = 'gjs-field';
    textInput.placeholder = 'URL de la imagen';
    textInput.value = initialValue || '';
    textInput.addEventListener('input', () => {
      updateThumb(textInput.value);
      onChange(textInput.value);
    });

    const uploadLabel = document.createElement('label');
    uploadLabel.className = 'cms-media-upload-btn';
    uploadLabel.textContent = 'Subir';

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (!file) return;

      uploadLabel.textContent = 'Subiendo…';
      const formData = new FormData();
      formData.append('files[]', file);

      fetch(mediaUploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        body: formData,
      })
        .then((r) => r.json())
        .then((res) => {
          const uploaded = (res.items || [])[0];
          if (uploaded) {
            textInput.value = uploaded.url;
            updateThumb(uploaded.url);
            onChange(uploaded.url);
          }
          uploadLabel.textContent = 'Subir';
        })
        .catch(() => {
          uploadLabel.textContent = 'Error';
          setTimeout(() => { uploadLabel.textContent = 'Subir'; }, 1500);
        });

      fileInput.value = '';
    });

    uploadLabel.appendChild(fileInput);
    controls.appendChild(textInput);
    controls.appendChild(uploadLabel);

    updateThumb(initialValue);
    wrap.appendChild(thumb);
    wrap.appendChild(controls);

    return { wrap, textInput };
  }

  function registerCustomTraitTypes(editor, mediaUploadUrl, csrfToken) {
    editor.TraitManager.addType('media', {
      createInput({ trait }) {
        const { wrap, textInput } = buildMediaWidget(
          '',
          (val) => {
            // Evita que el "eco" del propio cambio (cada tecleo dispara
            // change:<campo>, que GrapesJS usa para volver a llamar a
            // onUpdate) reconstruya este mismo input y le haga perder el
            // foco a mitad de escritura.
            this.suppressNextUpdate = true;
            this.component.set(trait.get('name'), val);
          },
          mediaUploadUrl,
          csrfToken
        );
        this.input = textInput;
        return wrap;
      },
      onEvent() {
        // El cambio ya se aplica directo arriba (texto o subida) — igual
        // que el trait 'repeater', no hace falta nada acá.
      },
      onUpdate({ component, trait }) {
        this.component = component;
        if (this.suppressNextUpdate) {
          this.suppressNextUpdate = false;
          return;
        }
        if (this.input) this.input.value = component.get(trait.get('name')) || '';
      },
    });

    editor.TraitManager.addType('textarea', {
      createInput() {
        const el = document.createElement('textarea');
        el.rows = 4;
        el.className = 'gjs-field gjs-field-textarea';
        return el;
      },
      onEvent({ elInput, component, trait }) {
        component.set(trait.get('name'), elInput.value);
      },
      onUpdate({ elInput, component, trait }) {
        elInput.value = component.get(trait.get('name')) || '';
      },
    });

    editor.TraitManager.addType('repeater', {
      createInput({ trait }) {
        const wrap = document.createElement('div');
        wrap.className = 'cms-repeater-trait';
        this.subFields = trait.get('subFields') || {};
        this.rowsEl = document.createElement('div');
        wrap.appendChild(this.rowsEl);

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'cms-repeater-add';
        addBtn.textContent = '+ Agregar';
        addBtn.addEventListener('click', () => {
          const value = (this.component.get(trait.get('name')) || []).slice();
          const empty = {};
          Object.keys(this.subFields).forEach((k) => { empty[k] = ''; });
          value.push(empty);
          this.component.set(trait.get('name'), value);
          this.onUpdate({ component: this.component, trait });
        });
        wrap.appendChild(addBtn);

        return wrap;
      },
      onEvent() {
        // Los inputs individuales de cada fila disparan su propio listener
        // (agregado en onUpdate al construir las filas), no este evento genérico.
      },
      onUpdate({ elInput, component, trait }) {
        this.component = component;

        // changeProp:true hace que CADA tecleo (que ya actualizó el modelo
        // a mano, ver más abajo) dispare este mismo onUpdate como "eco".
        // Sin este corte, cada letra reconstruye TODAS las filas desde
        // cero — el input activo se destruye y el foco se pierde a mitad
        // de escritura. Reconstruir sí hace falta al agregar/quitar una
        // fila o al cargar por primera vez, así que ahí SÍ se deja pasar
        // (ver addBtn/removeBtn, que llaman a onUpdate directo).
        if (this.suppressNextUpdate) {
          this.suppressNextUpdate = false;
          return;
        }

        const rowsEl = elInput.querySelector('.cms-repeater-trait > div') || elInput.firstChild;
        rowsEl.innerHTML = '';
        const items = component.get(trait.get('name')) || [];
        const subFields = trait.get('subFields') || {};

        items.forEach((item, index) => {
          const row = document.createElement('div');
          row.className = 'cms-repeater-row';

          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = 'cms-repeater-remove';
          removeBtn.textContent = '×';
          removeBtn.setAttribute('aria-label', 'Eliminar');
          removeBtn.addEventListener('click', () => {
            const current = (component.get(trait.get('name')) || []).slice();
            current.splice(index, 1);
            component.set(trait.get('name'), current);
            this.onUpdate({ elInput, component, trait });
          });
          row.appendChild(removeBtn);

          // Cada subcampo va en su propia línea, con su etiqueta siempre
          // visible arriba — con 3-4 subcampos por ítem (como en
          // "Publicaciones de redes") meterlos todos lado a lado los
          // dejaba truncados a unas pocas letras.
          Object.keys(subFields).forEach((subKey) => {
            const subField = subFields[subKey];
            const field = document.createElement('div');
            field.className = 'cms-repeater-field';

            const label = document.createElement('label');
            label.className = 'cms-repeater-field-label';
            label.textContent = subField.label || subKey;
            field.appendChild(label);

            if (subField.type === 'media') {
              const commit = (val) => {
                const current = (component.get(trait.get('name')) || []).slice();
                current[index] = Object.assign({}, current[index], { [subKey]: val });
                this.suppressNextUpdate = true;
                component.set(trait.get('name'), current);
              };
              const { wrap: mediaWrap } = buildMediaWidget(item[subKey] || '', commit, mediaUploadUrl, csrfToken);
              field.appendChild(mediaWrap);
              row.appendChild(field);
              return;
            }

            let input;

            if (subField.type === 'select') {
              input = document.createElement('select');
              (subField.options || []).forEach((opt) => {
                const option = document.createElement('option');
                option.value = opt.id;
                option.textContent = opt.name;
                if (item[subKey] === opt.id) option.selected = true;
                input.appendChild(option);
              });
            } else {
              input = document.createElement('input');
              input.type = 'text';
              input.placeholder = subField.label || subKey;
              input.value = item[subKey] || '';
            }

            input.className = 'gjs-field';
            input.addEventListener(subField.type === 'select' ? 'change' : 'input', () => {
              const current = (component.get(trait.get('name')) || []).slice();
              current[index] = Object.assign({}, current[index], { [subKey]: input.value });
              this.suppressNextUpdate = true;
              component.set(trait.get('name'), current);
            });
            field.appendChild(input);
            row.appendChild(field);
          });

          rowsEl.appendChild(row);
        });
      },
    });
  }

  function registerBlockTypes(editor, types, previewUrl, csrfToken) {
    types.forEach((def) => {
      const fieldKeys = Object.keys(def.fields || {});

      editor.Components.addType(def.type, {
        model: {
          defaults: Object.assign(
            {
              tagName: 'div',
              name: def.label,
              draggable: true,
              droppable: false,
              editable: false,
              removable: true,
              copyable: false,
              highlightable: true,
              cmsType: def.type,
              cmsBlockId: null,
              cmsFieldKeys: fieldKeys,
              traits: buildTraits(def.fields),
            },
            def.defaults || {}
          ),
        },
        view: {
          init() {
            // Ojo: este listener dispara guardado en cada edición de campo,
            // pero NO durante la hidratación inicial — Backbone no emite
            // 'change:x' al setear atributos en la construcción del
            // componente (append), solo en sets posteriores a un modelo ya
            // existente. Por eso alcanza con no llamar triggerSave desde
            // onRender (que sí corre también durante la hidratación).
            const keys = this.model.get('cmsFieldKeys') || [];
            keys.forEach((k) => this.listenTo(this.model, 'change:' + k, () => {
              this.renderPreview();
              if (window.__cmsTriggerSave) window.__cmsTriggerSave();
            }));
          },
          onRender() {
            this.renderPreview();
          },
          renderPreview() {
            const model = this.model;
            const keys = model.get('cmsFieldKeys') || [];
            const data = {};
            keys.forEach((k) => { data[k] = model.get(k); });

            fetch(previewUrl, {
              method: 'POST',
              headers: csrfHeaders(csrfToken),
              body: JSON.stringify({ type: model.get('cmsType'), data }),
            })
              .then((r) => r.json())
              .then((res) => { this.el.innerHTML = res.html; })
              .catch(() => { /* preview best-effort; no rompe el editor si falla */ });
          },
        },
      });

      editor.Blocks.add(def.type, {
        label: def.label,
        category: def.category || 'Otros',
        media: def.icon || '',
        content: { type: def.type },
      });
    });
  }

  function extractBlocks(editor) {
    const wrapper = editor.getWrapper();
    const children = wrapper.components();
    const blocks = [];

    children.forEach((component, index) => {
      const type = component.get('cmsType');
      if (!type) return; // ignora cualquier componente que no sea un bloque nuestro

      const keys = component.get('cmsFieldKeys') || [];
      const data = {};
      keys.forEach((k) => { data[k] = component.get(k); });

      // El id viaja para que el backend pueda actualizar el bloque existente
      // en vez de borrar todo y recrearlo en cada guardado (ver
      // Admin\PageBlockController::store — sync por id, no delete+insert).
      blocks.push({ id: component.get('cmsBlockId') || null, type, data, sort_order: index });
    });

    return blocks;
  }

  function hydrate(editor, blocks) {
    const wrapper = editor.getWrapper();
    const sorted = blocks.slice().sort((a, b) => a.sort_order - b.sort_order);

    sorted.forEach((block) => {
      wrapper.append(Object.assign({ type: block.type, cmsBlockId: block.id }, block.data));
    });
  }

  window.CyrexCmsEditor = {
    init(config) {
      const { mountId, indexUrl, storeUrl, previewUrl, mediaUploadUrl, csrfToken, cssUrl, fontsUrl } = config;
      const statusEl = document.getElementById('cms-save-status');

      const editor = grapesjs.init({
        container: '#' + mountId,
        height: '100%',
        width: 'auto',
        fromElement: false,
        storageManager: false,
        blockManager: { appendTo: '#gjs-blocks-list' },
        layerManager: { appendTo: '#gjs-layers-list' },
        traitManager: { appendTo: '#gjs-settings-panel' },
        styleManager: { sectors: [] },
        selectorManager: { componentFirst: true },
        panels: { defaults: [] },
        deviceManager: {
          devices: [
            { name: 'Desktop', width: '' },
            { name: 'Tablet', width: '768px', widthMedia: '992px' },
            { name: 'Mobile', width: '375px', widthMedia: '480px' },
          ],
        },
        canvas: {
          styles: [cssUrl, fontsUrl].filter(Boolean),
        },
      });

      window.editor = editor; // expuesto para diagnóstico/verificación, no lo usa la app

      registerCustomTraitTypes(editor, mediaUploadUrl, csrfToken);

      const setStatus = (text) => { if (statusEl) statusEl.textContent = text; };

      // Se pone en true recién cuando termina la hidratación inicial (ver
      // más abajo). Antes de eso, agregar los bloques ya guardados dispara
      // los mismos eventos que agregarlos a mano — sin este flag, cargar el
      // editor con contenido existente disparaba un guardado innecesario
      // apenas terminaba de cargar, sin que el admin tocara nada.
      let ready = false;

      const doSave = () => {
        setStatus('Guardando…');
        const wrapper = editor.getWrapper();
        const components = wrapper.components();

        fetch(storeUrl, {
          method: 'PUT',
          headers: csrfHeaders(csrfToken),
          body: JSON.stringify({ blocks: extractBlocks(editor) }),
        })
          .then((r) => r.json())
          .then((res) => {
            // Los bloques nuevos no tienen id hasta el primer guardado
            // exitoso — se lo asignamos acá para que el PRÓXIMO guardado
            // los actualice en vez de crearlos de nuevo.
            (res.blocks || []).forEach((saved, i) => {
              const component = components.at(i);
              if (component) component.set('cmsBlockId', saved.id, { silent: true });
            });
            setStatus('Guardado');
          })
          .catch(() => setStatus('Error al guardar'));
      };

      const debouncedSave = debounce(doSave, 1200);
      window.__cmsTriggerSave = debouncedSave;

      editor.on('component:add component:remove component:move', () => {
        if (ready) debouncedSave();
      });

      // Botones de dispositivo (reemplazan el panel default de GrapesJS, que se
      // desactivó con panels:{defaults:[]} para no exponer botones fuera de
      // nuestro control, como el visor de código HTML/CSS crudo).
      const deviceBtns = document.querySelectorAll('[data-cms-device]');
      deviceBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
          editor.setDevice(btn.dataset.cmsDevice);
          deviceBtns.forEach((b) => b.classList.toggle('btn-primary', b === btn));
        });
      });

      const saveBtn = document.getElementById('cms-save-btn');
      if (saveBtn) saveBtn.addEventListener('click', doSave);

      const undoBtn = document.getElementById('cms-undo-btn');
      if (undoBtn) undoBtn.addEventListener('click', () => editor.UndoManager.undo());
      const redoBtn = document.getElementById('cms-redo-btn');
      if (redoBtn) redoBtn.addEventListener('click', () => editor.UndoManager.redo());

      // Carga automática: trae los tipos registrados (arma la paleta de
      // bloques) y los bloques ya guardados de esta página (hidrata el canvas).
      setStatus('Cargando…');
      fetch(indexUrl, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((res) => {
          registerBlockTypes(editor, res.types, previewUrl, csrfToken);
          hydrate(editor, res.blocks);
          setStatus('Listo');
          ready = true;
        });

      return editor;
    },
  };
})();
