
<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Firmar documento
            </h2>

            <a href="{{ route('documentos.show', $documento) }}"
               class="text-sm text-brand-600 hover:underline">
                ← Volver al documento
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">


            <!-- =========================================================
                 RESUMEN DEL DOCUMENTO
            ========================================================== -->

            <div class="card overflow-hidden mb-6">

                <div class="px-6 py-4">

                    <h3 class="font-semibold text-gray-800">
                        {{ $documento->tipoDocumento->nombre }}
                    </h3>

                    <p class="text-sm text-gray-600 mt-1">

                        Alumno:

                        <span class="font-medium">
                            {{ $documento->alumno->nombre_completo }}
                        </span>

                        (<span class="font-mono text-brand-700">
                            {{ $documento->alumno->codigo }}
                        </span>)

                        · Folio:

                        <span class="font-mono">
                            {{ $documento->folio ?? '—' }}
                        </span>

                    </p>

                    <p class="text-xs text-gray-400 mt-1">

                        Fecha de documento:
                        {{ $documento->fecha?->format('d/m/Y H:i') }}

                    </p>

                </div>

            </div>


            <!-- =========================================================
                 FORMULARIO
            ========================================================== -->

            <form method="POST"
                  action="{{ route('documentos.storeFirma', $documento) }}"
                  id="formFirma">

                @csrf


                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


                    <!-- =================================================
                         CANVAS
                    ================================================== -->

                    <div class="card overflow-hidden lg:col-span-2">


                        <!-- CABECERA -->

                        <div class="px-6 py-4 border-b border-gray-100
                                    flex items-center justify-between">

                            <h3 class="font-semibold text-gray-800">

                                Dibuja tu firma

                            </h3>


                            <div class="flex gap-2 items-center">


                                <span id="stuStatus"
                                      class="text-xs text-gray-400 mr-2">

                                    Wacom: sin conectar

                                </span>


                                <button type="button"
                                        id="btnConectarStu"
                                        class="btn-secondary text-xs">

                                    Conectar Wacom

                                </button>


                                <button type="button"
                                        id="btnLimpiar"
                                        class="btn-secondary">

                                    Limpiar

                                </button>


                                <button type="button"
                                        id="btnDeshacer"
                                        class="btn-secondary">

                                    Deshacer

                                </button>


                            </div>

                        </div>


                        <!-- CANVAS -->

                        <div class="p-6">


                            <div id="contenedorCanvas"
                                 class="relative border-2 border-dashed
                                        border-gray-300 rounded-xl
                                        overflow-hidden bg-white">


                                <canvas id="lienzo"
                                        width="1280"
                                        height="480"
                                        class="w-full h-[24vh] sm:h-[30vh] touch-none"
                                        style="
                                            background-color:#ffffff;
                                            cursor:crosshair;
                                        ">
                                </canvas>


                            </div>


                            <p class="text-xs text-gray-400 mt-2">

                                Dibuja con el ratón, dedo, stylus
                                o Wacom STU-530.

                            </p>


                            <input type="hidden"
                                   name="firma_data"
                                   id="firma_data">


                            <x-input-error
                                :messages="$errors->get('firma_data')"
                                class="mt-2"
                            />


                        </div>

                    </div>


                    <!-- =================================================
                         PANEL GUARDAR
                    ================================================== -->

                    <div class="card overflow-hidden h-max
                                lg:sticky lg:top-24">


                        <div class="px-6 py-4 border-b border-gray-100">

                            <h3 class="font-semibold text-gray-800">

                                Guardar firma

                            </h3>

                        </div>


                        <div class="p-6 space-y-3">


                            <p class="text-sm text-gray-600">

                                Confirma que la firma dibujada es correcta
                                y corresponde al alumno

                                <span class="font-medium text-gray-800">

                                    {{ $documento->alumno->nombre_completo }}

                                </span>.

                            </p>


                            <x-primary-button
                                class="w-full justify-center">

                                Guardar firma

                            </x-primary-button>


                            <a href="{{ route('documentos.show', $documento) }}"
                               class="block text-center text-sm text-gray-600
                                      hover:text-brand-700 hover:underline">

                                Cancelar

                            </a>


                        </div>

                    </div>


                </div>

            </form>

        </div>

    </div>


    @push('scripts')

    <script>

        (() => {

            'use strict';


            /* =========================================================
             * ELEMENTOS
             * ========================================================= */

            const canvas =
                document.getElementById('lienzo');

            const ctx =
                canvas.getContext('2d', {
                    alpha: false
                });


            const form =
                document.getElementById('formFirma');


            const firmaData =
                document.getElementById('firma_data');


            const btnLimpiar =
                document.getElementById('btnLimpiar');


            const btnDeshacer =
                document.getElementById('btnDeshacer');


            const btnConectar =
                document.getElementById('btnConectarStu');


            const stuStatus =
                document.getElementById('stuStatus');


            /* =========================================================
             * CONFIGURACIÓN CANVAS
            ========================================================== */

            const CANVAS_WIDTH = 1280;

            const CANVAS_HEIGHT = 480;


            canvas.width =
                CANVAS_WIDTH;

            canvas.height =
                CANVAS_HEIGHT;


            /* =========================================================
             * ESTADO DEL CANVAS
            ========================================================== */

            let dibujando =
                false;


            let ultimoPunto =
                null;


            let historial =
                [];


            let haDibujado =
                false;


            /* =========================================================
             * ESTADO WACOM
            ========================================================== */

            let stuDevice =
                null;


            let stuConectada =
                false;


            let stuPenDown =
                false;


            let stuUltimoPunto =
                null;


            let stuTabletWidth =
                10800;


            let stuTabletHeight =
                6500;


            let stuPressureMax =
                1023;


            /*
             * STU-530:
             *
             * VID = 0x056A
             * PID = 0x00A5
             */

            const WACOM_VENDOR_ID =
                0x056A;


            const WACOM_PRODUCT_ID =
                0x00A5;


            /* =========================================================
             * REPORT IDS WACOM
            ========================================================== */

            const REPORT = {

                PEN_DATA:
                    0x01,

                INFORMATION:
                    0x08,

                CAPABILITY:
                    0x09,

                WRITING_MODE:
                    0x0E,

                ESERIAL:
                    0x0F,

                CLEAR_SCREEN:
                    0x20,

                INKING_MODE:
                    0x21,

                WRITING_AREA:
                    0x2A,

                PEN_COLOR_WIDTH:
                    0x2D,

                BACKGROUND_COLOR:
                    0x2E,

                PEN_DATA_TIME:
                    0x34

            };


            /* =========================================================
             * STATUS WACOM
            ========================================================== */

            function setStuStatus(
                texto,
                correcto = null
            ) {

                stuStatus.textContent =
                    'Wacom: ' + texto;


                if (correcto === true) {

                    stuStatus.className =
                        'text-xs mr-2 text-green-600';

                } else if (correcto === false) {

                    stuStatus.className =
                        'text-xs mr-2 text-red-500';

                } else {

                    stuStatus.className =
                        'text-xs mr-2 text-gray-400';

                }

            }


            /* =========================================================
             * FONDO DEL CANVAS
            ========================================================== */

            function dibujarFondo() {

                ctx.fillStyle =
                    '#ffffff';


                ctx.fillRect(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );


                ctx.fillStyle =
                    '#374151';


                ctx.font =
                    'bold 44px sans-serif';


                ctx.textAlign =
                    'left';


                ctx.textBaseline =
                    'alphabetic';


                ctx.fillText(
                    'Recibí:',
                    40,
                    150
                );


                const fecha =
                    new Date();


                const opciones = {

                    weekday:
                        'long',

                    year:
                        'numeric',

                    month:
                        'long',

                    day:
                        'numeric'

                };


                const textoFecha =
                    fecha.toLocaleDateString(
                        'es-MX',
                        opciones
                    );


                ctx.font =
                    '300 30px sans-serif';


                ctx.fillStyle =
                    '#6b7280';


                ctx.textAlign =
                    'right';


                ctx.fillText(
                    textoFecha,
                    canvas.width - 50,
                    canvas.height - 40
                );


                ctx.textAlign =
                    'left';

            }


            /* =========================================================
             * GUARDAR ESTADO
             *
             * IMPORTANTE:
             *
             * Esto solamente ocurre al comenzar un trazo.
             * Nunca por cada punto.
            ========================================================== */

            function guardarEstado() {

                if (
                    historial.length >= 20
                ) {

                    historial.shift();

                }


                historial.push(
                    canvas.toDataURL('image/png')
                );

            }


            /* =========================================================
             * POSICIÓN MOUSE
            ========================================================== */

            function obtenerPosicion(e) {

                const rect =
                    canvas.getBoundingClientRect();


                return {

                    x:
                        (e.clientX - rect.left)
                        *
                        (canvas.width / rect.width),

                    y:
                        (e.clientY - rect.top)
                        *
                        (canvas.height / rect.height)

                };

            }


            /* =========================================================
             * DIBUJAR LÍNEA
            ========================================================== */

            function dibujarLinea(
                desde,
                hasta,
                grosor = 4
            ) {

                if (
                    !desde ||
                    !hasta
                ) {

                    return;

                }


                ctx.beginPath();


                ctx.moveTo(
                    desde.x,
                    desde.y
                );


                ctx.lineTo(
                    hasta.x,
                    hasta.y
                );


                ctx.strokeStyle =
                    '#111827';


                ctx.lineWidth =
                    grosor;


                ctx.lineCap =
                    'round';


                ctx.lineJoin =
                    'round';


                ctx.stroke();

            }


            /* =========================================================
             * MOUSE / TOUCH
            ========================================================== */

            function pointerDown(e) {

                e.preventDefault();


                try {

                    canvas.setPointerCapture(
                        e.pointerId
                    );

                } catch (_) {}


                guardarEstado();


                dibujando =
                    true;


                ultimoPunto =
                    obtenerPosicion(e);


                haDibujado =
                    true;

            }


            function pointerMove(e) {

                e.preventDefault();


                if (
                    !dibujando
                ) {

                    return;

                }


                const punto =
                    obtenerPosicion(e);


                dibujarLinea(
                    ultimoPunto,
                    punto,
                    5
                );


                ultimoPunto =
                    punto;


                haDibujado =
                    true;

            }


            function pointerUp(e) {

                e.preventDefault();


                dibujando =
                    false;


                ultimoPunto =
                    null;


                try {

                    canvas.releasePointerCapture(
                        e.pointerId
                    );

                } catch (_) {}

            }


            canvas.addEventListener(
                'pointerdown',
                pointerDown
            );


            canvas.addEventListener(
                'pointermove',
                pointerMove
            );


            canvas.addEventListener(
                'pointerup',
                pointerUp
            );


            canvas.addEventListener(
                'pointercancel',
                pointerUp
            );


            /* =========================================================
             * LIMPIAR CANVAS
            ========================================================== */

            function limpiarCanvas() {

                ctx.clearRect(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );


                dibujarFondo();


                historial =
                    [];


                haDibujado =
                    false;


                stuPenDown =
                    false;


                stuUltimoPunto =
                    null;

            }


            /* =========================================================
             * LIMPIAR TODO
             *
             * CANVAS + WACOM
            ========================================================== */

            btnLimpiar.addEventListener(
                'click',
                async () => {

                    if (
                        !confirm(
                            '¿Seguro que quieres borrar la firma?'
                        )
                    ) {

                        return;

                    }


                    limpiarCanvas();


                    /*
                     * Limpiar físicamente la pantalla
                     * de la Wacom.
                     */

                    if (
                        stuConectada &&
                        stuDevice
                    ) {

                        try {

                            await stuClearScreen();


                            setStuStatus(
                                'pantalla limpia',
                                true
                            );


                            setTimeout(
                                () => {

                                    if (
                                        stuConectada
                                    ) {

                                        setStuStatus(
                                            'captura activa',
                                            true
                                        );

                                    }

                                },
                                1000
                            );


                        } catch (error) {

                            console.error(
                                'No se pudo limpiar Wacom:',
                                error
                            );


                            setStuStatus(
                                'error al limpiar',
                                false
                            );

                        }

                    }

                }
            );


            /* =========================================================
             * DESHACER
            ========================================================== */

            btnDeshacer.addEventListener(
                'click',
                () => {

                    const anterior =
                        historial.pop();


                    if (
                        !anterior
                    ) {

                        return;

                    }


                    const imagen =
                        new Image();


                    imagen.onload =
                        () => {

                            ctx.clearRect(
                                0,
                                0,
                                canvas.width,
                                canvas.height
                            );


                            ctx.drawImage(
                                imagen,
                                0,
                                0
                            );

                        };


                    imagen.src =
                        anterior;

                }
            );


            /* =========================================================
             * CONVERSIÓN WACOM -> CANVAS
            ========================================================== */

            function wacomToCanvas(
                x,
                y
            ) {

                const cx =
                    (
                        x /
                        stuTabletWidth
                    )
                    *
                    canvas.width;


                const cy =
                    (
                        y /
                        stuTabletHeight
                    )
                    *
                    canvas.height;


                return {

                    x:
                        Math.max(
                            0,
                            Math.min(
                                canvas.width,
                                cx
                            )
                        ),

                    y:
                        Math.max(
                            0,
                            Math.min(
                                canvas.height,
                                cy
                            )
                        )

                };

            }


            /* =========================================================
             * PRESIÓN
            ========================================================== */

            function calcularGrosor(
                pressure
            ) {

                /*
                 * Para una firma electrónica
                 * no necesitamos variar demasiado
                 * el grosor.
                 *
                 * Esto evita que la firma se vea
                 * pixelada o demasiado irregular.
                 */

                if (
                    !pressure ||
                    pressure <= 0
                ) {

                    return 4;

                }


                const porcentaje =
                    Math.min(
                        1,
                        pressure /
                        stuPressureMax
                    );


                return (
                    3.0 +
                    porcentaje * 2.5
                );

            }


            /* =========================================================
             * PROCESAR PUNTO WACOM
            ========================================================== */

            function procesarPuntoWacom(
                x,
                y,
                sw,
                pressure
            ) {

                /*
                 * El lápiz no está tocando.
                 */

                if (
                    !sw
                ) {

                    if (
                        stuPenDown
                    ) {

                        stuPenDown =
                            false;

                        stuUltimoPunto =
                            null;

                    }

                    return;

                }


                const punto =
                    wacomToCanvas(
                        x,
                        y
                    );


                /*
                 * Comenzar nuevo trazo.
                 */

                if (
                    !stuPenDown
                ) {

                    guardarEstado();


                    stuPenDown =
                        true;


                    stuUltimoPunto =
                        punto;


                    haDibujado =
                        true;


                    /*
                     * Dibujar un pequeño punto
                     * para que firmas lentas
                     * no pierdan el primer contacto.
                     */

                    ctx.beginPath();


                    ctx.arc(
                        punto.x,
                        punto.y,
                        2,
                        0,
                        Math.PI * 2
                    );


                    ctx.fillStyle =
                        '#111827';


                    ctx.fill();


                    return;

                }


                /*
                 * Continuar trazo.
                 */

                if (
                    stuUltimoPunto
                ) {

                    const dx =
                        punto.x -
                        stuUltimoPunto.x;


                    const dy =
                        punto.y -
                        stuUltimoPunto.y;


                    const distancia =
                        Math.sqrt(
                            dx * dx +
                            dy * dy
                        );


                    /*
                     * Evitar saltos enormes
                     * provocados por un reporte
                     * HID extraño.
                     */

                    if (
                        distancia >
                        250
                    ) {

                        stuUltimoPunto =
                            punto;

                        return;

                    }


                    /*
                     * Dibujar.
                     */

                    dibujarLinea(
                        stuUltimoPunto,
                        punto,
                        calcularGrosor(
                            pressure
                        )
                    );

                }


                stuUltimoPunto =
                    punto;


                haDibujado =
                    true;

            }


            /* =========================================================
             * LEER REPORTE HID
            ========================================================== */

            function onStuInputReport(
                event
            ) {

                if (
                    !stuDevice
                ) {

                    return;

                }


                const reportId =
                    event.reportId;


                /*
                 * Solo procesamos:
                 *
                 * 0x01 PenData
                 * 0x34 PenDataTimeCountSequence
                 */

                if (
                    reportId !==
                    REPORT.PEN_DATA
                    &&
                    reportId !==
                    REPORT.PEN_DATA_TIME
                ) {

                    return;

                }


                const data =
                    event.data;


                if (
                    data.byteLength < 7
                ) {

                    return;

                }


                /*
                 * STATUS
                 *
                 * bit 0 = rdy
                 * bit 1 = sw
                 */

                const status =
                    data.getUint8(0);


                const rdy =
                    (
                        status &
                        0x01
                    ) !== 0;


                const sw =
                    (
                        status &
                        0x02
                    ) !== 0;


                /*
                 * STU usa valores de 16 bits
                 * para X/Y.
                 *
                 * La implementación WebHID
                 * comprobada utiliza estos
                 * campos directamente.
                 */

                const x =
                    data.getUint16(
                        2
                    );


                const y =
                    data.getUint16(
                        4
                    );


                /*
                 * Presión.
                 *
                 * En PenData normal
                 * se obtiene desde los bytes
                 * iniciales después de limpiar
                 * los bits de estado.
                 */

                let pressure =
                    0;


                /*
                 * El primer word contiene
                 * presión + bits de estado.
                 */

                pressure =
                    data.getUint16(0)
                    &
                    0x0FFF;


                /*
                 * Si el dispositivo no entrega
                 * presión válida, usamos una
                 * presión media.
                 */

                if (
                    pressure <= 0
                ) {

                    pressure =
                        600;

                }


                /*
                 * No necesitamos procesar
                 * puntos cuando el lápiz está
                 * simplemente cerca de la pantalla
                 * pero no tocándola.
                 */

                if (
                    !rdy &&
                    !sw
                ) {

                    if (
                        stuPenDown
                    ) {

                        stuPenDown =
                            false;

                        stuUltimoPunto =
                            null;

                    }

                    return;

                }


                procesarPuntoWacom(
                    x,
                    y,
                    sw,
                    pressure
                );

            }


            /* =========================================================
             * ENVIAR FEATURE REPORT
            ========================================================== */

            async function stuSend(
                reportId,
                data
            ) {

                if (
                    !stuDevice ||
                    !stuDevice.opened
                ) {

                    throw new Error(
                        'Wacom no conectada'
                    );

                }


                const bytes =
                    data instanceof Uint8Array
                        ? data
                        : new Uint8Array(data);


                await stuDevice.sendFeatureReport(
                    reportId,
                    bytes
                );

            }


            /* =========================================================
             * LEER FEATURE REPORT
            ========================================================== */

            async function stuRead(
                reportId
            ) {

                if (
                    !stuDevice ||
                    !stuDevice.opened
                ) {

                    throw new Error(
                        'Wacom no conectada'
                    );

                }


                return await stuDevice.receiveFeatureReport(
                    reportId
                );

            }


            /* =========================================================
             * LIMPIAR PANTALLA WACOM
            ========================================================== */

            async function stuClearScreen() {

                /*
                 * Wacom:
                 *
                 * Report 0x20
                 * data [0]
                 */

                await stuSend(
                    REPORT.CLEAR_SCREEN,
                    new Uint8Array([0])
                );

            }


            /* =========================================================
             * INKING ON/OFF
            ========================================================== */

            async function stuSetInking(
                enabled
            ) {

                await stuSend(
                    REPORT.INKING_MODE,
                    new Uint8Array([
                        enabled ? 1 : 0
                    ])
                );

            }


            /* =========================================================
             * WRITING MODE
            ========================================================== */

            async function stuSetWritingMode(
                mode
            ) {

                await stuSend(
                    REPORT.WRITING_MODE,
                    new Uint8Array([
                        mode
                    ])
                );

            }


            /* =========================================================
             * OBTENER CAPACIDADES
            ========================================================== */

            async function stuGetCapabilities() {

                try {

                    const data =
                        await stuRead(
                            REPORT.CAPABILITY
                        );


                    /*
                     * Capability report:
                     *
                     * offset 1 = tablet width
                     * offset 3 = tablet height
                     * offset 5 = pressure max
                     * offset 7 = screen width
                     * offset 9 = screen height
                     * offset 11 = report rate
                     */

                    if (
                        data.byteLength >= 12
                    ) {

                        /*
                         * La implementación WebHID
                         * usa DataView directamente.
                         */

                        const width =
                            data.getUint16(
                                1
                            );


                        const height =
                            data.getUint16(
                                3
                            );


                        const pressure =
                            data.getUint16(
                                5
                            );


                        const screenWidth =
                            data.getUint16(
                                7
                            );


                        const screenHeight =
                            data.getUint16(
                                9
                            );


                        if (
                            width > 0
                        ) {

                            stuTabletWidth =
                                width;

                        }


                        if (
                            height > 0
                        ) {

                            stuTabletHeight =
                                height;

                        }


                        if (
                            pressure > 0
                        ) {

                            stuPressureMax =
                                pressure;

                        }


                        console.log(
                            'Wacom capabilities:',
                            {
                                tabletWidth:
                                    stuTabletWidth,

                                tabletHeight:
                                    stuTabletHeight,

                                pressureMax:
                                    stuPressureMax,

                                screenWidth,

                                screenHeight
                            }
                        );

                    }

                } catch (error) {

                    /*
                     * Si el firmware no permite
                     * leer capability por WebHID,
                     * mantenemos valores por defecto.
                     */

                    console.warn(
                        'No se pudieron obtener capacidades Wacom:',
                        error
                    );

                }

            }


            /* =========================================================
             * CONFIGURAR WACOM
            ========================================================== */

            async function configurarWacom() {

                /*
                 * Primero obtener dimensiones reales.
                 */

                await stuGetCapabilities();


                /*
                 * Modo 1:
                 *
                 * TimeCountSequence.
                 *
                 * Es el modo que Wacom recomienda
                 * para STU-530.
                 *
                 * El reporte resultante es 0x34.
                 */

                try {

                    /*
                     * PenDataOptionMode =
                     * TimeCountSequence
                     *
                     * Report ID 0x16 en algunos
                     * dispositivos/SDK.
                     *
                     * No lo usamos directamente aquí
                     * si el dispositivo ya está entregando
                     * 0x34.
                     */

                    await stuSetWritingMode(
                        1
                    );

                } catch (error) {

                    console.warn(
                        'No se pudo configurar WritingMode:',
                        error
                    );

                }


                /*
                 * Limpiar pantalla antes de comenzar.
                 */

                try {

                    await stuClearScreen();

                } catch (error) {

                    console.warn(
                        'No se pudo limpiar Wacom:',
                        error
                    );

                }


                /*
                 * Activar tinta de la propia Wacom.
                 */

                try {

                    await stuSetInking(
                        true
                    );

                } catch (error) {

                    console.warn(
                        'No se pudo activar inking:',
                        error
                    );

                }


                /*
                 * Estado inicial.
                 */

                stuPenDown =
                    false;


                stuUltimoPunto =
                    null;

            }


            /* =========================================================
             * CONECTAR WACOM
            ========================================================== */

            async function conectarStu() {

                if (
                    !('hid' in navigator)
                ) {

                    setStuStatus(
                        'WebHID no disponible. Usa Chrome o Edge.',
                        false
                    );

                    return;

                }


                try {

                    setStuStatus(
                        'buscando...'
                    );


                    /*
                     * Buscar dispositivos ya autorizados.
                     */

                    const autorizados =
                        await navigator.hid.getDevices();


                    let dispositivo =
                        autorizados.find(
                            device =>
                                device.vendorId ===
                                WACOM_VENDOR_ID
                                &&
                                device.productId ===
                                WACOM_PRODUCT_ID
                        );


                    /*
                     * Si no existe, pedir autorización.
                     */

                    if (
                        !dispositivo
                    ) {

                        const seleccion =
                            await navigator.hid.requestDevice({

                                filters: [

                                    {

                                        vendorId:
                                            WACOM_VENDOR_ID,

                                        productId:
                                            WACOM_PRODUCT_ID

                                    }

                                ]

                            });


                        if (
                            !seleccion.length
                        ) {

                            setStuStatus(
                                'sin dispositivo',
                                false
                            );

                            return;

                        }


                        dispositivo =
                            seleccion[0];

                    }


                    stuDevice =
                        dispositivo;


                    /*
                     * Abrir dispositivo.
                     */

                    if (
                        !stuDevice.opened
                    ) {

                        await stuDevice.open();

                    }


                    /*
                     * Listener.
                     */

                    stuDevice.addEventListener(
                        'inputreport',
                        onStuInputReport
                    );


                    stuConectada =
                        true;


                    btnConectar.textContent =
                        'Desconectar Wacom';


                    setStuStatus(
                        'conectada — configurando...',
                        true
                    );


                    /*
                     * Configurar.
                     */

                    await configurarWacom();


                    setStuStatus(
                        'lista — firma en la Wacom',
                        true
                    );


                } catch (error) {

                    console.error(
                        'Error conectando Wacom:',
                        error
                    );


                    setStuStatus(
                        'error: ' +
                        error.message,
                        false
                    );


                    if (
                        stuDevice
                    ) {

                        try {

                            stuDevice.removeEventListener(
                                'inputreport',
                                onStuInputReport
                            );

                            if (
                                stuDevice.opened
                            ) {

                                await stuDevice.close();

                            }

                        } catch (_) {}

                    }


                    stuDevice =
                        null;


                    stuConectada =
                        false;


                    btnConectar.textContent =
                        'Conectar Wacom';

                }

            }


            /* =========================================================
             * DESCONECTAR WACOM
            ========================================================== */

            async function desconectarStu() {

                if (
                    !stuDevice
                ) {

                    return;

                }


                try {

                    /*
                     * Primero dejar de dibujar
                     * en la pantalla de Wacom.
                     */

                    await stuSetInking(
                        false
                    );

                } catch (_) {}


                try {

                    /*
                     * Limpiar pantalla.
                     */

                    await stuClearScreen();

                } catch (_) {}


                try {

                    stuDevice.removeEventListener(
                        'inputreport',
                        onStuInputReport
                    );

                } catch (_) {}


                try {

                    if (
                        stuDevice.opened
                    ) {

                        await stuDevice.close();

                    }

                } catch (_) {}


                stuDevice =
                    null;


                stuConectada =
                    false;


                stuPenDown =
                    false;


                stuUltimoPunto =
                    null;


                btnConectar.textContent =
                    'Conectar Wacom';


                setStuStatus(
                    'desconectada',
                    false
                );

            }


            /* =========================================================
             * BOTÓN CONECTAR
            ========================================================== */

            btnConectar.addEventListener(
                'click',
                async () => {

                    if (
                        stuConectada
                    ) {

                        await desconectarStu();

                    } else {

                        await conectarStu();

                    }

                }
            );


            /* =========================================================
             * DESCONEXIÓN USB
            ========================================================== */

            if (
                'hid' in navigator
            ) {

                navigator.hid.addEventListener(
                    'disconnect',
                    event => {

                        if (
                            stuDevice &&
                            event.device ===
                            stuDevice
                        ) {

                            stuDevice =
                                null;


                            stuConectada =
                                false;


                            stuPenDown =
                                false;


                            stuUltimoPunto =
                                null;


                            btnConectar.textContent =
                                'Conectar Wacom';


                            setStuStatus(
                                'desconectada',
                                false
                            );

                        }

                    }
                );

            }


            /* =========================================================
             * FORMULARIO
            ========================================================== */

            form.addEventListener(
                'submit',
                e => {

                    if (
                        !haDibujado
                    ) {

                        e.preventDefault();


                        alert(
                            'Debes dibujar tu firma antes de guardar.'
                        );


                        return;

                    }


                    /*
                     * SOLO AQUÍ generamos PNG.
                     *
                     * Nunca durante el dibujo.
                     */

                    firmaData.value =
                        canvas.toDataURL(
                            'image/png'
                        );

                }
            );


            /* =========================================================
             * INICIALIZAR
            ========================================================== */

            dibujarFondo();


        })();

    </script>

    @endpush

</x-app-layout>

