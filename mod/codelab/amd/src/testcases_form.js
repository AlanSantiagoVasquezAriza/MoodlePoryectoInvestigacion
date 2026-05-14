// This file is part of Moodle - http://moodle.org/

/**
 * Gestión dinámica de casos de prueba en el formulario de configuración.
 *
 * @module    mod_codelab/testcases_form
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    /**
     * Inicializa el gestor de casos de prueba.
     * @param {object} cfg
     */
    function init(cfg) {
        var container = document.getElementById(cfg.containerid || 'codelab-testcases-container');
        var addBtn    = document.getElementById('codelab-add-testcase');
        var jsonField = document.querySelector('[name="testcases_json"]');
        var form      = document.querySelector('form#mform1') || document.querySelector('form');

        if (!container) {
            return;
        }

        // Botón añadir caso de prueba.
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                addNewRow(container);
                serializeTestcases(container, jsonField);
            });
        }

        // Delegación de eventos para botones eliminar.
        container.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.codelab-remove-tc');
            if (removeBtn) {
                var row = removeBtn.closest('.codelab-tc-row');
                if (row) {
                    var rows = container.querySelectorAll('.codelab-tc-row');
                    if (rows.length <= 1) {
                        clearRow(row);
                    } else {
                        row.remove();
                    }
                    reindexRows(container);
                    serializeTestcases(container, jsonField);
                }
            }
        });

        // Serializar en tiempo real al cambiar cualquier campo.
        container.addEventListener('input', function() {
            serializeTestcases(container, jsonField);
        });
        container.addEventListener('change', function() {
            serializeTestcases(container, jsonField);
        });

        // Serializar antes de enviar el formulario.
        if (form) {
            form.addEventListener('submit', function() {
                serializeTestcases(container, jsonField);
            });
        }

        // Serializar estado inicial si hay filas cargadas.
        serializeTestcases(container, jsonField);
    }

    /**
     * Añade una nueva fila de caso de prueba vacía.
     */
    function addNewRow(container) {
        var rows  = container.querySelectorAll('.codelab-tc-row');
        var index = rows.length;

        var div = document.createElement('div');
        div.className = 'codelab-tc-row row mb-2 align-items-start';
        div.dataset.index = index;
        div.innerHTML = buildRowHtml(index, {name: '', stdin: '', expected_output: '', points: '1', is_hidden: false});

        var rowsContainer = container.querySelector('#codelab-tc-rows') || container;
        rowsContainer.appendChild(div);
    }

    /**
     * Construye el HTML de una fila de caso de prueba.
     */
    function buildRowHtml(index, data) {
        var hidden = data.is_hidden ? 'checked' : '';
        return '<div class="col-md-3 col-sm-12 mb-1">' +
               '<input type="text" class="form-control form-control-sm tc-name"' +
               ' placeholder="Ej: Prueba básica" value="' + escHtml(data.name || '') + '"/>' +
               '</div>' +
               '<div class="col-md-3 col-sm-12 mb-1">' +
               '<textarea class="form-control form-control-sm tc-stdin" rows="2"' +
               ' placeholder="Entrada estándar (stdin)">' + escHtml(data.stdin || '') + '</textarea>' +
               '</div>' +
               '<div class="col-md-3 col-sm-12 mb-1">' +
               '<textarea class="form-control form-control-sm tc-expected" rows="2"' +
               ' placeholder="Salida esperada">' + escHtml(data.expected_output || '') + '</textarea>' +
               '</div>' +
               '<div class="col-md-1 col-sm-4 mb-1">' +
               '<label class="d-block d-md-none small text-muted">Puntos</label>' +
               '<input type="number" class="form-control form-control-sm tc-points"' +
               ' min="0" step="0.5" value="' + escHtml(String(data.points || '1')) + '"' +
               ' style="min-width:60px;"/>' +
               '</div>' +
               '<div class="col-md-1 col-sm-4 mb-1 text-center pt-2">' +
               '<label class="d-block d-md-none small text-muted">Oculto</label>' +
               '<input type="checkbox" class="tc-hidden" ' + hidden +
               ' title="Ocultar este caso al estudiante" style="width:20px;height:20px;"/>' +
               '</div>' +
               '<div class="col-md-1 col-sm-4 mb-1 pt-1">' +
               '<button type="button" class="btn btn-sm btn-danger codelab-remove-tc" title="Eliminar">' +
               '<i class="fa fa-trash"></i></button>' +
               '</div>';
    }

    /**
     * Limpia los campos de una fila (en lugar de eliminarla si es la última).
     */
    function clearRow(row) {
        var inputs = row.querySelectorAll('input[type="text"], textarea');
        inputs.forEach(function(inp) { inp.value = ''; });
        var numInput = row.querySelector('input[type="number"]');
        if (numInput) {
            numInput.value = '1';
        }
        var checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = false;
        }
    }

    /**
     * Re-indexa las filas después de eliminar.
     */
    function reindexRows(container) {
        var rows = container.querySelectorAll('.codelab-tc-row');
        rows.forEach(function(row, i) {
            row.dataset.index = i;
        });
    }

    /**
     * Serializa todos los casos de prueba en el campo JSON oculto.
     */
    function serializeTestcases(container, jsonField) {
        if (!jsonField) {
            return;
        }

        var rows = container.querySelectorAll('.codelab-tc-row');
        var testcases = [];

        rows.forEach(function(row, index) {
            var nameEl     = row.querySelector('.tc-name');
            var stdinEl    = row.querySelector('.tc-stdin');
            var expectedEl = row.querySelector('.tc-expected');
            var pointsEl   = row.querySelector('.tc-points');
            var hiddenEl   = row.querySelector('.tc-hidden');

            var name     = nameEl     ? nameEl.value.trim()     : '';
            var stdin    = stdinEl    ? stdinEl.value            : '';
            var expected = expectedEl ? expectedEl.value         : '';
            var points   = pointsEl   ? parseFloat(pointsEl.value || '1') : 1;
            var hidden   = hiddenEl   ? (hiddenEl.checked ? 1 : 0) : 0;

            if (name || expected) {
                testcases.push({
                    name:            name || ('Caso ' + (index + 1)),
                    stdin:           stdin,
                    expected_output: expected,
                    points:          isNaN(points) ? 1 : points,
                    is_hidden:       hidden,
                    ordering:        index
                });
            }
        });

        jsonField.value = JSON.stringify(testcases);
    }

    /**
     * Escapa HTML.
     */
    function escHtml(str) {
        if (!str) {
            return '';
        }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return {
        init: init
    };
});
