/**
 * Utilidades para manejar contenido HTML
 */

/**
 * Extrae texto plano del HTML
 * @param html - String HTML o undefined
 * @returns Texto plano sin etiquetas HTML
 */
export function stripHtml(html: string | undefined | null): string {
    if (!html) return '';
    
    // Crear un elemento temporal para parsear el HTML
    const tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    
    // Obtener el texto plano
    return (tmp.textContent || tmp.innerText || '').trim();
}

/**
 * Trunca HTML manteniendo el texto plano
 * @param html - String HTML o undefined
 * @param maxLength - Longitud máxima del texto (default: 100)
 * @returns Texto truncado sin HTML
 */
export function truncateHtml(html: string | undefined | null, maxLength: number = 100): string {
    const text = stripHtml(html);
    
    if (text.length <= maxLength) {
        return text;
    }
    
    return text.substring(0, maxLength) + '...';
}

/**
 * Sanitiza y prepara HTML para renderizado seguro
 * @param html - String HTML o undefined
 * @returns HTML sanitizado o string vacío
 */
export function sanitizeHtml(html: string | undefined | null): string {
    if (!html) return '';
    
    // Vue's v-html ya hace sanitización básica,
    // pero podemos agregar validación adicional si es necesario
    return html;
}