import './bootstrap';

/**
 * Descarga un archivo vía fetch + blob para el botón "Exportar Excel".
 * Se hace acá (y no inline en el HTML) para no lidiar con comillas y
 * con el carácter ">" que rompen los atributos HTML.
 */
window.descargarExcel = function (enlace) {
    return fetch(enlace.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((res) => {
            if (!res.ok) {
                throw new Error('el servidor respondió ' + res.status);
            }

            const cd = res.headers.get('Content-Disposition') || '';
            const match = cd.match(/filename="([^"]+)"/);

            return res.blob().then((blob) => ({
                blob,
                nombre: match ? match[1] : 'PRESUPUESTO.xlsx',
            }));
        })
        .then(({ blob, nombre }) => {
            const url = URL.createObjectURL(blob);
            const temp = document.createElement('a');

            temp.href = url;
            temp.download = nombre;
            document.body.appendChild(temp);
            temp.click();
            temp.remove();
            URL.revokeObjectURL(url);
        })
        .catch(function (error) {
            alert('No se pudo descargar el presupuesto: ' + error.message);
        });
};
