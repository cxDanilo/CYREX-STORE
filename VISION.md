# Cyrex Store — Visión y filosofía de diseño

Este documento explica el "por qué" detrás de las decisiones de diseño del proyecto. El `CLAUDE.md` tiene el estado técnico; esto es la intención de fondo — para que cualquier cambio nuevo (de Claude Code, de otro dev, o de vos mismo en el futuro) se sienta coherente con la visión original, no solo "funcione".

## El pedido original

Danilo quería recrear cyrexstore.com desde cero: más funcional, más eficiente, sin depender de WordPress. No era solo "hacer una tienda" — era resolver problemas reales del sitio actual:

- Precios mezclados sin criterio entre USD y BOB, sin un sistema claro
- Estética inconsistente (imágenes promocionales pegadas sobre fotos de producto, colores fuera de paleta como el verde de un botón "Únete a la comunidad" en medio de un sitio negro/dorado)
- Sin admin real para gestionar 82 categorías entre padres e hijas
- Nada de personalización visual fácil (algo "tipo Elementor" pero simple)
- Necesitaba ser responsive de verdad, no una idea de responsive

## La estética objetivo

Minimalista, tipo Apple / Google 2025 — vender una experiencia antes que productos. Esto es central: el primer vistazo de la home NO debe sentirse como un catálogo. Debe sentirse atmosférico, cinematográfico, como entrar a algo — no como aterrizar en una grilla de productos con precios.

Referencias visuales explícitas que se usaron para calibrar el tono:

- overcitybo.com (competidor local boliviano) — filtros de categoría con íconos, cards limpias, tipografía pesada en mayúsculas
- spacex.com — hero cinematográfico, oscuro, tipografía enorme, texto escaso, todo respira
- apple.com/la/airpods-pro — el producto se presenta como experiencia, secciones que revelan features en scroll, nunca satura de información

Marca: negro (`#0A0A0B`, no negro puro) + dorado (`#FFD900`) exacto. El dorado se usa con moderación — como Apple usa su azul, no como fondo, sino como acento en CTAs, precios, estados activos. Nada de otros colores "decorativos" sueltos (ej. el verde del sitio viejo se identificó explícitamente como un error a corregir, salvo el punto de estado "en línea" que es un uso semántico estándar, no decorativo).

## La firma tipográfica del proyecto

Tres fuentes con roles claros, no una sola fuente para todo:

- **Space Grotesk** — titulares, geométrica, con carácter técnico
- **Inter** — texto de UI, cuerpo, legibilidad
- **JetBrains Mono** — SIEMPRE para precios y especificaciones técnicas

Esto último es intencional y es LA firma visual distintiva del proyecto: tratar precios y specs como un "benchmark readout" — la estética de un overlay de FPS o una terminal, algo que cualquier gamer/builder reconoce al instante. No es un capricho, es lo que diferencia a Cyrex Store de un ecommerce genérico. Cualquier número de precio o dato técnico nuevo que se agregue al sitio debería usar `--font-mono`, no la fuente de cuerpo.

## Filosofía de interacción: "menos es más"

En cada pantalla que se diseñó, la instrucción recurrente de Danilo fue recortar, no agregar. Ejemplos concretos de decisiones tomadas:

- Se sacaron las estadísticas de negocio (3 sucursales, 1200+ productos) del primer vistazo del hero — eso es información de catálogo, no de experiencia, y competía con el impacto inicial
- Se descartó un selector de "comprar con un asesor" (12 personas del equipo, no tenía sentido mostrarlos como opciones individuales) a favor de simplemente los botones WhatsApp + Carrito
- El carrito NO maneja cantidades por producto — decisión explícita, simplicidad sobre completitud
- Se quitaron las cuotas de pago (Cyrex no las ofrece) apenas se detectó que estaban en el diseño por inercia, no por necesidad real
- La sección de "categorías" en el nav pasó por 3 iteraciones hasta llegar a algo que no se sintiera "tedioso" (versión inicial obligaba a pasar por una página intermedia de categorías antes de ver productos — se descartó por eso)

Regla general: si una feature no tiene una razón de negocio clara, se saca, aunque "se vea bien".

## Animación: presencia sutil, nunca ruido

El sitio debe sentirse vivo (fue un pedido explícito — "que sea algo más animado, como el que tenemos actualmente" en referencia al video de fondo del sitio WordPress actual), pero la animación siempre es sutil:

- Fades y slides cortos (0.3–0.6s), nunca rebotes exagerados ni animaciones largas que hagan esperar al usuario
- Hover states discretos: lift de 1-2px, cambio de borde, nunca escalados grandes ni rotaciones dramáticas
- El hero de home tiene glows que "respiran" lentamente de fondo (glow blobs con animación de 16-20s, muy lenta) y un trazo de circuito con pulsos de luz viajando — simula movimiento de video sin necesitar un archivo de video real
- Los marquees (tiras de marcas) se pausan al hacer hover, nunca se sienten descontrolados

## Sobre las categorías (82 en total)

El desafío de diseño más grande del proyecto: mostrar 82 categorías (padre + hijas) sin que se sienta abrumador. Se evaluaron 4 propuestas de arquitectura antes de elegir (documentadas en el historial de chat, resumen acá):

- Mega-menú por departamentos inventados → descartado, agregaba una capa de taxonomía que no existe en los datos reales
- Árbol lateral con buscador → válido pero se sentía largo igual
- Hub visual de categorías como paso obligatorio → descartado por "tedioso", agregaba un clic de más antes de ver productos
- **Híbrida (elegida):** nav con accesos directos a las categorías más usadas + mega-menú agrupado con íconos (inspirado en overcitybo.com) + sidebar con flyout al hover (como el sitio actual de Cyrex ya lo hace, solo que rediseñado) + drawer mobile con acordeón agrupado y buscador en vivo — MISMA agrupación de categorías en los 3 lugares (nav, sidebar, mobile), nunca taxonomías distintas por plataforma

Esto es lo que se perdió al portar a Blade (el flyout del sidebar, el mega-menú del nav) y lo que hay que recuperar — no es un detalle menor, es parte central de cómo se resolvió el problema más difícil del proyecto.

## Variantes de producto — por qué importa

Pedido explícito de Danilo: "en vez de crear 2 productos que solo varían en los colores, que se cree uno con una variante". Esto no es solo una decisión de base de datos — es una decisión de que el catálogo se sienta curado y profesional, no inflado artificialmente con SKUs duplicados. Cada vez que se agregue un producto nuevo con variaciones (color, tamaño, lo que sea), la solución correcta es SIEMPRE una fila en `product_variants`, nunca un producto nuevo.

## El flujo de conversión: WhatsApp, no checkout tradicional

Cyrex Store no tiene pasarela de pago online — el patrón de conversión real (y el que hay que preservar) es: el cliente arma su pedido, y "Finalizar por WhatsApp" arma un mensaje con el detalle y lo manda directo a Cyrex. Esto no es una limitación temporal a "arreglar después" — es el modelo de negocio real para el mercado boliviano, documentado como tal. Cualquier feature de e-commerce que asuma checkout con tarjeta está fuera de alcance a menos que Danilo lo pida explícitamente.

## En resumen, si tenés que tomar una decisión de diseño no especificada

Preguntate: ¿esto vende una experiencia o vende un catálogo? ¿Es la opción más simple que resuelve el problema real, o es una opción "completa" que nadie pidió? ¿Usa la paleta y tipografía ya definidas, o inventa algo nuevo sin necesidad? Ante la duda, el sesgo del proyecto es siempre hacia menos, más silencioso, más premium — nunca hacia más funciones o más ruido visual.
