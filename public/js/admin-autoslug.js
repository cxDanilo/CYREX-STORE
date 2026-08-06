/**
 * Regla única de generación de slugs para todos los formularios del admin
 * (productos, categorías, plantillas, páginas...). Antes esta misma lógica
 * estaba copiada dentro de cada formulario — si había que ajustarla, había
 * que acordarse de tocar los 4 archivos. Ahora es una sola función global.
 */
window.autoSlugify = function (value) {
  return (value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{Mark}/gu, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
};
