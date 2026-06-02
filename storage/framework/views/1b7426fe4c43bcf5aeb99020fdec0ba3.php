<?php $__env->startSection('title', 'Geo Analytics — Request Log Analyzer'); ?>
<?php $__env->startSection('page-title', 'Geo Analytics'); ?>

<?php $__env->startPush('head'); ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"/>
<style>
/* ══════════════════════════════════════════════════════════════
   Geo Analytics — scoped styles
══════════════════════════════════════════════════════════════ */

/* ── Summary stat cards ───────────────────────────────────────────────── */
.geo-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.4rem;
}
@media (max-width: 960px) { .geo-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 540px) { .geo-stats { grid-template-columns: 1fr; } }

.gs-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .875rem;
    padding: 1.2rem 1.4rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.gs-label { font-size: .68rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .07em; margin-bottom: .4rem; }
.gs-value { font-size: 1.75rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; color: #0f172a; margin-bottom: .2rem; }
.gs-sub   { font-size: .72rem; color: #9ca3af; }

/* ── Filter bar ───────────────────────────────────────────────────────── */
.geo-filter {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.2rem;
    flex-wrap: wrap;
}
.geo-filter label { font-size: .76rem; font-weight: 600; color: #374151; }
.geo-filter input[type=date] {
    border: 1px solid #d1d5db;
    border-radius: .5rem;
    padding: .38rem .7rem;
    font-size: .78rem;
    color: #374151;
    background: #fff;
    outline: none;
    transition: border-color .15s;
}
.geo-filter input[type=date]:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.geo-filter .btn-apply {
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: .5rem;
    padding: .4rem 1rem;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.geo-filter .btn-apply:hover { background: #1d4ed8; }
.geo-filter .btn-clear {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    padding: .38rem .85rem;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
}
.geo-filter .btn-clear:hover { background: #e2e8f0; }

/* ── Map card ─────────────────────────────────────────────────────────── */
.map-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    margin-bottom: 1.4rem;
}
.map-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .9rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: .5rem;
}
.map-card-head h2 { font-size: .9rem; font-weight: 700; color: #111827; }
.map-card-head .meta { font-size: .72rem; color: #9ca3af; }

#geo-map {
    height: 520px;
    width: 100%;
    background: #f0f4f8;
}
@media (max-width: 768px) { #geo-map { height: 340px; } }

/* Leaflet popup customisation */
.leaflet-popup-content-wrapper {
    border-radius: .6rem !important;
    padding: 0 !important;
    box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;
    font-family: system-ui,-apple-system,'Segoe UI',sans-serif !important;
}
.leaflet-popup-content { margin: 0 !important; }
.geo-popup {
    padding: .75rem 1rem;
    min-width: 140px;
}
.geo-popup-country { font-size: .82rem; font-weight: 700; color: #0f172a; margin-bottom: .35rem; }
.geo-popup-count   { font-size: 1.25rem; font-weight: 800; color: #2563eb; line-height: 1; }
.geo-popup-share   { font-size: .7rem; color: #6b7280; margin-top: .15rem; }

/* ── Legend ───────────────────────────────────────────────────────────── */
.map-legend {
    padding: .85rem 1.4rem;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.legend-label { font-size: .72rem; color: #6b7280; font-weight: 600; }
.legend-scale {
    display: flex;
    align-items: center;
    gap: .25rem;
    flex: 1;
}
.legend-swatch {
    height: 10px;
    flex: 1;
    border-radius: 3px;
}
.legend-tick {
    font-size: .65rem;
    color: #9ca3af;
    white-space: nowrap;
}

/* ── Country table ────────────────────────────────────────────────────── */
.geo-table-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.geo-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .9rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: .5rem;
}
.geo-table-head h2 { font-size: .9rem; font-weight: 700; color: #111827; }
#tbl-search {
    border: 1px solid #d1d5db;
    border-radius: .5rem;
    padding: .35rem .7rem;
    font-size: .78rem;
    color: #374151;
    outline: none;
    width: 180px;
    transition: border-color .15s;
}
#tbl-search:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }

.geo-table { width: 100%; border-collapse: collapse; }
.geo-table thead th {
    background: #f8fafc;
    padding: .55rem 1.1rem;
    text-align: left;
    font-size: .62rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}
.geo-table tbody td {
    padding: .65rem 1.1rem;
    font-size: .8rem;
    border-bottom: 1px solid #f8fafc;
    color: #374151;
    vertical-align: middle;
}
.geo-table tbody tr:last-child td { border-bottom: none; }
.geo-table tbody tr { cursor: pointer; transition: background .1s; }
.geo-table tbody tr:hover td { background: #f0f4ff; }
.geo-table tbody tr.highlighted td { background: #eff6ff; }
.geo-table td.rank { font-size: .7rem; color: #94a3b8; font-variant-numeric: tabular-nums; width: 36px; }
.geo-table td.country-name { font-weight: 600; color: #1e293b; }
.geo-table td.count { font-variant-numeric: tabular-nums; font-weight: 600; }
.geo-table td.bar-cell { width: 200px; }

.tbl-bar-wrap { display: flex; align-items: center; gap: .5rem; }
.tbl-bar { flex: 1; height: 6px; background: #f1f5f9; border-radius: 9999px; overflow: hidden; }
.tbl-bar-fill { height: 100%; border-radius: 9999px; background: linear-gradient(90deg, #3b82f6, #6366f1); transition: width .3s; }
.tbl-pct { font-size: .67rem; color: #9ca3af; white-space: nowrap; min-width: 35px; text-align: right; }

.no-rows { padding: 2rem; text-align: center; color: #94a3b8; font-size: .82rem; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>


<div class="geo-stats">
    <div class="gs-card">
        <div class="gs-label">Countries Tracked</div>
        <div class="gs-value" style="color:#2563eb;"><?php echo e(number_format($totalCountries)); ?></div>
        <div class="gs-sub">unique geo locations</div>
    </div>
    <div class="gs-card">
        <div class="gs-label">Total Requests</div>
        <div class="gs-value"><?php echo e(number_format($totalRequests)); ?></div>
        <div class="gs-sub">with location data</div>
    </div>
    <div class="gs-card">
        <div class="gs-label">Top Country</div>
        <div class="gs-value" style="font-size:1.1rem;line-height:1.4;"><?php echo e($topCountry?->country ?? '—'); ?></div>
        <div class="gs-sub"><?php echo e(number_format($topCountry?->count ?? 0)); ?> requests</div>
    </div>
    <div class="gs-card">
        <div class="gs-label">Top Country Share</div>
        <?php $topShare = $totalRequests > 0 ? round(($topCountry?->count ?? 0) / $totalRequests * 100, 1) : 0; ?>
        <div class="gs-value" style="color:#10b981;"><?php echo e($topShare); ?><span style="font-size:1rem;font-weight:600;">%</span></div>
        <div class="gs-sub">of all geo requests</div>
    </div>
</div>


<form method="GET" action="<?php echo e(route('request-log-analyzer.geo')); ?>" class="geo-filter">
    <label for="from">From</label>
    <input type="date" id="from" name="from" value="<?php echo e($from ?? ''); ?>">
    <label for="to">To</label>
    <input type="date" id="to" name="to" value="<?php echo e($to ?? ''); ?>">
    <button type="submit" class="btn-apply">Apply</button>
    <?php if($from || $to): ?>
        <a href="<?php echo e(route('request-log-analyzer.geo')); ?>" class="btn-clear">Clear</a>
    <?php endif; ?>
    <?php if($from || $to): ?>
        <span style="font-size:.75rem;color:#94a3b8;">
            Showing <?php echo e($from ?? '…'); ?> → <?php echo e($to ?? 'now'); ?>

        </span>
    <?php endif; ?>
</form>


<div class="map-card">
    <div class="map-card-head">
        <h2>
            <svg style="width:15px;height:15px;vertical-align:-.2em;margin-right:.35rem;color:#6366f1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            Requests per Country — World Heatmap
        </h2>
        <span class="meta" id="map-status">Loading map…</span>
    </div>

    <div id="geo-map"></div>

    <div class="map-legend">
        <span class="legend-label">Requests</span>
        <div class="legend-scale">
            <span class="legend-tick">0</span>
            <div class="legend-swatch" style="background:linear-gradient(90deg,#dbeafe,#93c5fd,#3b82f6,#1d4ed8,#1e3a8a);"></div>
            <span class="legend-tick" id="legend-max">—</span>
        </div>
        <span class="legend-tick" style="font-size:.7rem;color:#94a3b8;">Hover a country for details · Click to highlight in table</span>
    </div>
</div>


<div class="geo-table-card">
    <div class="geo-table-head">
        <h2>Country Breakdown</h2>
        <input type="text" id="tbl-search" placeholder="Search country…" autocomplete="off">
    </div>

    <?php if($countryStats->isEmpty()): ?>
        <div class="no-rows">No geo data available. Enable location tracking in your configuration.</div>
    <?php else: ?>
        <?php $totalGeo = $countryStats->sum('count'); ?>
        <div class="tbl-wrap" style="overflow-x:auto;">
            <table class="geo-table" id="geoTable">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th>Country</th>
                        <th>Requests</th>
                        <th style="width:220px;">Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $countryStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $cs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $pct = $totalGeo > 0 ? round($cs->count / $totalGeo * 100, 2) : 0; ?>
                    <tr data-country="<?php echo e(e($cs->country)); ?>">
                        <td class="rank"><?php echo e($i + 1); ?></td>
                        <td class="country-name"><?php echo e($cs->country); ?></td>
                        <td class="count"><?php echo e(number_format($cs->count)); ?></td>
                        <td class="bar-cell">
                            <div class="tbl-bar-wrap">
                                <div class="tbl-bar"><div class="tbl-bar-fill" style="width:<?php echo e($pct); ?>%;"></div></div>
                                <span class="tbl-pct"><?php echo e($pct); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    // ── PHP → JS data ────────────────────────────────────────────────────
    const countryData = <?php echo json_encode(
        $countryStats->mapWithKeys(fn($c) => [strtolower($c->country) => (int)$c->count])
    , 15, 512) ?>;
    const totalRequests = <?php echo e((int)$totalRequests); ?>;

    // ── ISO 3166-1 numeric → common name (world-atlas feature ids) ────────
    // Maps the numeric ID stored in world-atlas TopoJSON to canonical names
    // that we normalise via countryAliases below.
    const idToName = {
        4:'Afghanistan',8:'Albania',12:'Algeria',24:'Angola',32:'Argentina',
        36:'Australia',40:'Austria',31:'Azerbaijan',50:'Bangladesh',56:'Belgium',
        64:'Bhutan',68:'Bolivia',70:'Bosnia and Herzegovina',76:'Brazil',
        100:'Bulgaria',104:'Myanmar',116:'Cambodia',120:'Cameroon',124:'Canada',
        152:'Chile',156:'China',170:'Colombia',188:'Costa Rica',191:'Croatia',
        192:'Cuba',196:'Cyprus',203:'Czechia',208:'Denmark',214:'Dominican Republic',
        218:'Ecuador',818:'Egypt',222:'El Salvador',231:'Ethiopia',246:'Finland',
        250:'France',268:'Georgia',276:'Germany',288:'Ghana',300:'Greece',
        320:'Guatemala',332:'Haiti',340:'Honduras',348:'Hungary',356:'India',
        360:'Indonesia',364:'Iran',368:'Iraq',372:'Ireland',376:'Israel',
        380:'Italy',388:'Jamaica',392:'Japan',400:'Jordan',398:'Kazakhstan',
        404:'Kenya',408:'North Korea',410:'South Korea',414:'Kuwait',418:'Laos',
        422:'Lebanon',430:'Liberia',434:'Libya',440:'Lithuania',442:'Luxembourg',
        450:'Madagascar',458:'Malaysia',484:'Mexico',504:'Morocco',508:'Mozambique',
        516:'Namibia',524:'Nepal',528:'Netherlands',540:'New Caledonia',
        554:'New Zealand',558:'Nicaragua',566:'Nigeria',578:'Norway',512:'Oman',
        586:'Pakistan',275:'Palestine',591:'Panama',598:'Papua New Guinea',
        604:'Peru',608:'Philippines',616:'Poland',620:'Portugal',630:'Puerto Rico',
        634:'Qatar',642:'Romania',643:'Russia',682:'Saudi Arabia',686:'Senegal',
        694:'Sierra Leone',703:'Slovakia',705:'Slovenia',706:'Somalia',
        710:'South Africa',724:'Spain',144:'Sri Lanka',736:'Sudan',729:'South Sudan',
        752:'Sweden',756:'Switzerland',760:'Syria',158:'Taiwan',834:'Tanzania',
        764:'Thailand',788:'Tunisia',792:'Turkey',800:'Uganda',804:'Ukraine',
        784:'United Arab Emirates',826:'United Kingdom',840:'United States',
        858:'Uruguay',860:'Uzbekistan',862:'Venezuela',704:'Vietnam',
        887:'Yemen',716:'Zimbabwe',288:'Ghana',180:'Democratic Republic of the Congo',
        178:'Republic of the Congo',12:'Algeria',51:'Armenia',496:'Mongolia',
        498:'Moldova',428:'Latvia',426:'Lesotho',454:'Malawi',466:'Mali',
        222:'El Salvador',384:'Ivory Coast',480:'Mauritius',540:'New Caledonia',
        620:'Portugal',308:'Grenada',332:'Haiti',388:'Jamaica',242:'Fiji',
        132:'Cape Verde',170:'Colombia',
    };

    // Name aliases — maps alternate names → canonical (lowercase) for matching
    const countryAliases = {
        'russian federation':'russia',
        'united states of america':'united states',
        'republic of korea':'south korea',
        "democratic people's republic of korea":'north korea',
        "lao people's democratic republic":'laos',
        'viet nam':'vietnam',
        'syrian arab republic':'syria',
        'islamic republic of iran':'iran',
        'czechia':'czechia', 'czech republic':'czechia',
        'türkiye':'turkey','turkiye':'turkey',
        'great britain':'united kingdom',
        'republic of the congo':'republic of the congo',
        'dr congo':'democratic republic of the congo',
        "côte d'ivoire":'ivory coast',
        'cote d\'ivoire':'ivory coast',
    };

    function resolveCount(canonicalName) {
        const key = canonicalName.toLowerCase();
        if (countryData[key] !== undefined) return countryData[key];
        const alias = countryAliases[key];
        if (alias && countryData[alias] !== undefined) return countryData[alias];
        return 0;
    }

    // ── Colour scale (5 thresholds, blue palette) ─────────────────────────
    const maxCount = Math.max(...Object.values(countryData), 1);
    document.getElementById('legend-max').textContent = maxCount.toLocaleString();

    function getColor(count) {
        if (!count) return '#f0f4f8';
        const t = Math.log1p(count) / Math.log1p(maxCount);
        if (t < 0.15) return '#dbeafe';
        if (t < 0.35) return '#93c5fd';
        if (t < 0.55) return '#3b82f6';
        if (t < 0.75) return '#1d4ed8';
        return '#1e3a8a';
    }

    // ── Leaflet map ────────────────────────────────────────────────────────
    const map = L.map('geo-map', {
        center: [20, 10],
        zoom: 2,
        minZoom: 1,
        maxZoom: 6,
        zoomControl: true,
        attributionControl: true,
    });

    // Subtle tile layer (CartoDB light, no labels)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://carto.com/">CARTO</a> &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map);

    // ── Load world GeoJSON and render choropleth ───────────────────────────
    let geojsonLayer = null;
    let highlightedRow = null;
    const popup = L.popup({ closeButton: false, autoPan: false });

    function featureStyle(feature) {
        const name = idToName[parseInt(feature.id)];
        const count = name ? resolveCount(name) : 0;
        return {
            fillColor: getColor(count),
            weight: 0.8,
            color: '#ffffff',
            fillOpacity: 0.85,
        };
    }

    function highlightFeature(e) {
        const layer = e.target;
        layer.setStyle({ weight: 2, color: '#334155', fillOpacity: 0.95 });
        layer.bringToFront();

        const name = idToName[parseInt(layer.feature.id)] ?? 'Unknown';
        const count = resolveCount(name);
        const pct = totalRequests > 0 ? ((count / totalRequests) * 100).toFixed(2) : '0.00';

        popup
            .setLatLng(e.latlng)
            .setContent(`
                <div class="geo-popup">
                    <div class="geo-popup-country">${name}</div>
                    <div class="geo-popup-count">${count.toLocaleString()}</div>
                    <div class="geo-popup-share">${pct}% of requests</div>
                </div>
            `)
            .openOn(map);
    }

    function resetHighlight(e) {
        geojsonLayer.resetStyle(e.target);
        map.closePopup();
    }

    function onCountryClick(e) {
        const name = idToName[parseInt(e.target.feature.id)];
        if (!name) return;
        scrollToCountry(name);
    }

    function onEachFeature(feature, layer) {
        layer.on({
            mouseover: highlightFeature,
            mouseout:  resetHighlight,
            click:     onCountryClick,
        });
    }

    // Fetch world-atlas countries (TopoJSON) from jsDelivr
    fetch('https://cdn.jsdelivr.net/npm/world-atlas@2/countries-110m.json')
        .then(r => r.json())
        .then(world => {
            // Convert TopoJSON → GeoJSON using the bundled topojson in leaflet ecosystem
            // We use a minimal topojson decode included below
            const features = topoFeature(world, world.objects.countries);
            geojsonLayer = L.geoJSON(features, {
                style: featureStyle,
                onEachFeature: onEachFeature,
            }).addTo(map);

            document.getElementById('map-status').textContent =
                `${Object.keys(countryData).length} countr${Object.keys(countryData).length !== 1 ? 'ies' : 'y'} with data`;
        })
        .catch(() => {
            document.getElementById('map-status').textContent = 'Failed to load map data';
        });

    // ── Minimal TopoJSON → GeoJSON decoder (no external library needed) ───
    // Implements just enough of topojson-client to render countries-110m.json
    function topoFeature(topology, object) {
        return {
            type: 'FeatureCollection',
            features: object.geometries.map(geom => ({
                type: 'Feature',
                id: geom.id,
                properties: geom.properties || {},
                geometry: topoGeometry(topology, geom),
            })),
        };
    }

    function topoGeometry(topology, o) {
        const scale = topology.transform?.scale ?? [1, 1];
        const translate = topology.transform?.translate ?? [0, 0];

        function decodeArc(arc) {
            let x = 0, y = 0;
            return arc.map(([dx, dy]) => {
                x += dx; y += dy;
                return [x * scale[0] + translate[0], y * scale[1] + translate[1]];
            });
        }

        function resolveArcs(arcs) {
            return arcs.map(ring =>
                ring.map(i => {
                    const arc = topology.arcs[i < 0 ? ~i : i];
                    const pts = decodeArc(arc);
                    return i < 0 ? pts.reverse() : pts;
                }).flat()
            );
        }

        switch (o.type) {
            case 'Point':       return { type: 'Point', coordinates: o.coordinates };
            case 'MultiPoint':  return { type: 'MultiPoint', coordinates: o.coordinates };
            case 'LineString':  return { type: 'LineString', coordinates: resolveArcs(o.arcs)[0] };
            case 'Polygon':     return { type: 'Polygon', coordinates: resolveArcs(o.arcs) };
            case 'MultiPolygon':
                return { type: 'MultiPolygon', coordinates: o.arcs.map(a => resolveArcs(a)) };
            case 'GeometryCollection':
                return { type: 'GeometryCollection', geometries: o.geometries.map(g => topoGeometry(topology, g)) };
            default: return null;
        }
    }

    // ── Table row highlight + scroll on map click ──────────────────────────
    function scrollToCountry(countryName) {
        const rows = document.querySelectorAll('#geoTable tbody tr');
        rows.forEach(r => r.classList.remove('highlighted'));
        const key = countryName.toLowerCase();
        for (const row of rows) {
            if (row.dataset.country.toLowerCase() === key) {
                row.classList.add('highlighted');
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                highlightedRow = row;
                break;
            }
        }
    }

    // ── Table search filter ────────────────────────────────────────────────
    document.getElementById('tbl-search')?.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('#geoTable tbody tr').forEach(row => {
            const name = row.querySelector('.country-name')?.textContent.toLowerCase() ?? '';
            row.style.display = term === '' || name.includes(term) ? '' : 'none';
        });
    });

})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('request-log-analyzer::_layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\nintis\Package-Provider\packages\NIN\RequestLogAnalyzer\src/../resources/views/geo.blade.php ENDPATH**/ ?>