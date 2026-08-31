import './bootstrap';

import Alpine from 'alpinejs';

import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal;

/**
 * Muestra una alerta global (toast) basada en los mensajes flash de la sesión.
 */
function mostrarFlash() {
    const data = window.__flash;
    if (!data) return;

    if (data.success && !window.__swalMostrado) {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: data.success,
            toast: true,
            position: 'top-end',
            timer: 3500,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    } else if (data.error && !window.__swalMostrado) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.error,
            toast: true,
            position: 'top-end',
            timer: 5000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    } else if (data.info && !window.__swalMostrado) {
        Swal.fire({
            icon: 'info',
            title: 'Información',
            text: data.info,
            toast: true,
            position: 'top-end',
            timer: 3500,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    }
    window.__swalMostrado = true;
}

document.addEventListener('DOMContentLoaded', mostrarFlash);

/**
 * Confirmación de eliminación con SweetAlert.
 * Cualquier formulario con el atributo data-confirm mostrará un diálogo de confirmación.
 */
document.addEventListener('submit', function (e) {
    const form = e.target;
    const mensaje = form.getAttribute('data-confirm');
    if (!mensaje) return;

    e.preventDefault();
    Swal.fire({
        title: '¿Estás seguro?',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            form.removeAttribute('data-confirm');
            form.submit();
        }
    });
});

Alpine.start();
