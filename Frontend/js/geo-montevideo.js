const GeoMontevideo = (() => {
    const claveCache = 'sigeru_geocoding_montevideo';
    const limites = { sur: -34.95, oeste: -56.43, norte: -34.70, este: -56.01 };
    const cache = JSON.parse(localStorage.getItem(claveCache) || '{}');
    const esperar = ms => new Promise(resolver => setTimeout(resolver, ms));

    function guardar(clave, valor) {
        cache[clave] = valor;
        localStorage.setItem(claveCache, JSON.stringify(cache));
        return valor;
    }

    function dentroDeMontevideo([lat, lon]) {
        return lat >= limites.sur && lat <= limites.norte && lon >= limites.oeste && lon <= limites.este;
    }

    function escaparRegex(texto) {
        return texto.replace(/[.*+?^${}()|[\]\\"]/g, '\\$&');
    }

    async function buscarEsquina(calle1, calle2) {
        const clave = `esquina|${calle1}|${calle2}`.toLowerCase();
        if (clave in cache) return cache[clave];
        await esperar(1100);
        const caja = `${limites.sur},${limites.oeste},${limites.norte},${limites.este}`;
        const consulta = `[out:json][timeout:25];
            way(${caja})["highway"]["name"~"^${escaparRegex(calle1)}$",i]->.w1;
            way(${caja})["highway"]["name"~"^${escaparRegex(calle2)}$",i]->.w2;
            node(w.w1)(w.w2);
            out;`;
        try {
            const respuesta = await fetch('https://overpass-api.de/api/interpreter', {
                method: 'POST',
                body: 'data=' + encodeURIComponent(consulta),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });
            const nodos = (await respuesta.json()).elements || [];
            return guardar(clave, nodos.length ? [nodos[0].lat, nodos[0].lon] : null);
        } catch (error) {
            return null;
        }
    }

    async function geocodificar(consulta) {
        if (consulta in cache) return cache[consulta];
        await esperar(1100);
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=uy' +
            `&viewbox=${limites.oeste},${limites.norte},${limites.este},${limites.sur}&bounded=1&q=` + encodeURIComponent(consulta);
        try {
            const resultado = await (await fetch(url)).json();
            const punto = resultado.length ? [parseFloat(resultado[0].lat), parseFloat(resultado[0].lon)] : null;
            return guardar(consulta, punto && dentroDeMontevideo(punto) ? punto : null);
        } catch (error) {
            return null;
        }
    }

    async function ubicarCalles(calle1, calle2) {
        if (calle1 && calle2) {
            const esquina = await buscarEsquina(calle1, calle2);
            if (esquina) return esquina;
        }
        return calle1 ? geocodificar(`${calle1}, Montevideo, Uruguay`) : null;
    }

    function puntoEnAnillo(lon, lat, anillo) {
        let dentro = false;
        for (let i = 0, j = anillo.length - 1; i < anillo.length; j = i++) {
            const [xi, yi] = anillo[i];
            const [xj, yj] = anillo[j];
            if ((yi > lat) !== (yj > lat) && lon < (xj - xi) * (lat - yi) / (yj - yi) + xi) dentro = !dentro;
        }
        return dentro;
    }

    function municipioDePunto([lat, lon]) {
        for (const [letra, anillos] of Object.entries(MUNICIPIOS_MVD)) {
            if (anillos.some(anillo => puntoEnAnillo(lon, lat, anillo))) return letra;
        }
        return null;
    }

    return { limites, ubicarCalles, municipioDePunto };
})();