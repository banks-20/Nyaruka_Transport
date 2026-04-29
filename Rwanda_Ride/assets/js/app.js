(() => {
    const parseJsonAttr = (el, key, fallback) => {
        try {
            const value = el?.dataset?.[key];
            if (!value) return fallback;
            return JSON.parse(value);
        } catch (error) {
            return fallback;
        }
    };

    const themeButton = document.querySelector('[data-theme-toggle]');
    if (themeButton) {
        const setThemeIcon = () => {
            const dark = document.documentElement.classList.contains('dark');
            themeButton.innerHTML = `<i class="${dark ? 'ri-sun-line' : 'ri-moon-clear-line'}"></i>`;
        };
        setThemeIcon();
        themeButton.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const dark = document.documentElement.classList.contains('dark');
            localStorage.setItem('nyaruka-theme', dark ? 'dark' : 'light');
            setThemeIcon();
        });
    }

    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const labels = parseJsonAttr(revenueCtx, 'labels', ['May 1', 'May 5', 'May 10', 'May 15', 'May 20', 'May 24']);
        const series = parseJsonAttr(revenueCtx, 'series', [3200000, 3800000, 4100000, 3900000, 4600000, 4900000]);
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue',
                    data: series,
                    borderColor: '#1f6feb',
                    backgroundColor: 'rgba(31, 111, 235, 0.15)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        ticks: {
                            callback: (value) => `FRW ${(Number(value) / 1000).toFixed(0)}k`
                        }
                    }
                }
            }
        });
    }

    const bookingCtx = document.getElementById('bookingDonut');
    if (bookingCtx) {
        const completed = Number(bookingCtx.dataset.completed || 2130);
        const pending = Number(bookingCtx.dataset.pending || 456);
        const cancelled = Number(bookingCtx.dataset.cancelled || 239);
        new Chart(bookingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Cancelled'],
                datasets: [{ data: [completed, pending, cancelled], backgroundColor: ['#2db57f', '#f5a524', '#e5484d'] }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }

    const fleetCtx = document.getElementById('fleetPie');
    if (fleetCtx) {
        const active = Number(fleetCtx.dataset.active || 94);
        const maintenance = Number(fleetCtx.dataset.maintenance || 23);
        const inactive = Number(fleetCtx.dataset.inactive || 11);
        new Chart(fleetCtx, {
            type: 'pie',
            data: {
                labels: ['Operational', 'Maintenance', 'Inactive'],
                datasets: [{ data: [active, maintenance, inactive], backgroundColor: ['#3b82f6', '#f59e0b', '#94a3b8'] }]
            }
        });
    }

    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const labels = parseJsonAttr(salesCtx, 'labels', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']);
        const series = parseJsonAttr(salesCtx, 'series', [120000, 140000, 170000, 165000, 190000, 210000, 230000]);
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ data: series, backgroundColor: '#1f6feb', borderRadius: 8 }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        ticks: {
                            callback: (value) => `FRW ${(Number(value) / 1000).toFixed(0)}k`
                        }
                    }
                }
            }
        });
    }

    const routeDefinitions = {
        'kigali-huye': [
            [-1.9441, 30.0619],
            [-2.0892, 29.7563],
            [-2.5967, 29.7392]
        ],
        'kigali-rubavu': [
            [-1.9441, 30.0619],
            [-1.4996, 29.6347],
            [-1.6797, 29.2583]
        ]
    };

    document.querySelectorAll('.live-map[data-route]').forEach((mapEl) => {
        if (typeof L === 'undefined') return;
        const routeKey = mapEl.dataset.route;
        const route = routeDefinitions[routeKey] || routeDefinitions['kigali-huye'];
        const map = L.map(mapEl, {
            zoomControl: false,
            attributionControl: false
        });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
        map.fitBounds(route, { padding: [20, 20] });

        L.polyline(route, { color: '#1f6feb', weight: 4, opacity: 0.85 }).addTo(map);
        const marker = L.circleMarker(route[0], {
            radius: 8,
            color: '#0d4dc0',
            fillColor: '#1f6feb',
            fillOpacity: 1
        }).addTo(map);

        let idx = 0;
        setInterval(() => {
            idx = (idx + 1) % route.length;
            marker.setLatLng(route[idx]);
        }, 1600);
    });

    document.querySelectorAll('[data-animate-bus]').forEach((dot) => {
        let progress = 0;
        setInterval(() => {
            progress = (progress + 1) % 75;
            dot.style.left = `${15 + progress}%`;
        }, 180);
    });
})();

