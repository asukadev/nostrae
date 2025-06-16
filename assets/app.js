import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.scss';
import 'aos/dist/aos.css';

import { Tooltip, Modal } from 'bootstrap';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-date-fns';
import AOS from 'aos';

// Bootstrap tooltip

document.addEventListener('DOMContentLoaded', () => {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach((tooltipTriggerEl) => {
    new Tooltip(tooltipTriggerEl);
  });
});

// Charts.js 

document.addEventListener('DOMContentLoaded', () => {

    const createChart = (ctx, config) => new Chart(ctx, config);

    // Événements par type
    const eventTypeChart = document.getElementById('eventTypeChart');
    console.log(eventTypeChart)
    if (eventTypeChart) {
        const labels = JSON.parse(eventTypeChart.dataset.labels);
        const data = JSON.parse(eventTypeChart.dataset.values);
        console.log(labels, data)
        createChart(eventTypeChart, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Répartition par type',
                    data: data,
                    backgroundColor: ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#9c755f']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: "Événements par type" }
                }
            }
        });
    }

    // Réservations par événement
    const bookingChart = document.getElementById('bookingCountChart');
    if (bookingChart) {
        const labels = JSON.parse(bookingChart.dataset.labels);
        const data = JSON.parse(bookingChart.dataset.values);
        createChart(bookingChart, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Réservations par événement',
                    data,
                    backgroundColor: '#59a14f'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    title: { display: true, text: "Événements par type" }
                }
            }
        });
    }

    // Top villes
    const topCitiesChart = document.getElementById('topCitiesChart');
    if (topCitiesChart) {
        const labels = JSON.parse(topCitiesChart.dataset.labels);
        const data = JSON.parse(topCitiesChart.dataset.values);
        createChart(topCitiesChart, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Top 3 villes',
                    data,
                    backgroundColor: '#59a14f'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    title: { display: true, text: 'Top 3 des villes accueillant des événements' }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }

    // Remplissage moyen
    const fillingRateChart = document.getElementById('fillingRateChart');
    if (fillingRateChart) {
        const fillingRate = parseFloat(fillingRateChart.dataset.value);
        createChart(fillingRateChart, {
            type: 'doughnut',
            data: {
                labels: ['Rempli', 'Vide'],
                datasets: [{
                    data: [fillingRate, 100 - fillingRate],
                    backgroundColor: ['#4caf50', '#e0e0e0'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.label}: ${ctx.parsed}%`
                        }
                    },
                    title: { display: true, text: 'Top 3 des villes accueillant des événements' }
                },
                cutout: '70%'
            }
        });
    }

    // Timeline
    const timelineChart = document.getElementById('eventTimelineChart');
    if (timelineChart) {
        const labels = JSON.parse(timelineChart.dataset.labels);
        const dates = JSON.parse(timelineChart.dataset.dates).map(d => new Date(d));
        createChart(timelineChart, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Date de début',
                    data: dates,
                    backgroundColor: '#42a5f5'
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'dd/MM/yyyy HH:mm',
                            displayFormats: { day: 'dd/MM' }
                        },
                        title: { display: true, text: 'Date' }
                    },
                    y: {
                        title: { display: true, text: 'Événement' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `Début : ${new Date(ctx.raw).toLocaleString()}`
                        }
                    }
                }
            }
        });
    }
});

// Aos animation

AOS.init({
    duration: 800,
    once: true,
    offset: 120,
    easing: 'ease-out-cubic',
    // Désactivation automatique du scroll global (hack manuel après)
    disableMutationObserver: true,
});

// Menu sidebar

document.getElementById('menu-toggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('collapsed');
});
document.getElementById('menu-toggle-2').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('collapsed');
});

const menuToggle = document.getElementById('menu-toggle');
const menuToggle2 = document.getElementById('menu-toggle-2');
const wrapper = document.getElementById('wrapper');
const sidebar = document.getElementById('sidebar');

menuToggle.addEventListener('click', function () {
    wrapper.classList.toggle('toggled');
});
menuToggle2.addEventListener('click', function () {
    wrapper.classList.toggle('toggled');
});

// Auto-collapse on small screens
function handleResize() {
    if (window.innerWidth < 768) {
        wrapper.classList.add('toggled');
        sidebar.classList.add('collapsed');
    } else {
        wrapper.classList.remove('toggled');
    }
}

window.addEventListener('resize', handleResize);
window.addEventListener('load', handleResize);

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('locationForm');
    const select = document.querySelector('#event_location'); // adapte au nom exact du champ
    const errorDiv = document.getElementById('locationErrors');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            errorDiv.innerHTML = '';
            const formData = new FormData(form);

            fetch('/admin/location/new', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw response;
                return response.json();
            })
            .then(data => {
                const option = new Option(data.label, data.id, true, true);
                select.append(option);

                const modalElement = document.getElementById('locationModal');
                const modalInstance = Modal.getInstance(modalElement);
                modalInstance.hide();

                form.reset();
            })
            .catch(async error => {
                let message = 'Une erreur est survenue.';

                if (error instanceof Response) {
                    try {
                        const res = await error.json();
                        if (res.errors) {
                            message = res.errors.join('<br>');
                        }
                    } catch (e) {
                        message = 'Réponse invalide du serveur.';
                    }
                } else {
                    console.error(error); // erreur JS ou réseau
                }

                errorDiv.innerHTML = message;
            });

        });
    }
});
