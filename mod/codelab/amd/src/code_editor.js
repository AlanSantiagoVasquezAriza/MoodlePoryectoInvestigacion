// This file is part of Moodle - http://moodle.org/

/**
 * Módulo AMD del editor de código Monaco para CodeLab.
 *
 * @module    mod_codelab/code_editor
 * @copyright 2026 ProyectoEditorMoodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/notification'], function(Notification) {

    /** @type {object|null} Instancia del editor Monaco */
    var editor = null;
    /** @type {object} Config del módulo */
    var config = {};

    /**
     * Punto de entrada: inicializa el editor y los listeners.
     * @param {object} cfg - Configuración pasada desde PHP
     */
    function init(cfg) {
        config = cfg;
        loadMonaco().then(function() {
            initEditor();
            renderTestcasesList();
            bindEvents();
        }).catch(function(err) {
            window.console.error('CodeLab: error cargando Monaco Editor', err);
        });
    }

    /**
     * Carga Monaco Editor dinámicamente desde CDN.
     * Guarda y restaura el require de Moodle/RequireJS para evitar conflictos.
     * @returns {Promise}
     */
    function loadMonaco() {
        return new Promise(function(resolve, reject) {
            if (window.monaco) {
                resolve();
                return;
            }

            var loaderScript = document.createElement('script');
            loaderScript.src = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs/loader.js';

            loaderScript.onload = function() {
                // Guardar el require de Moodle/RequireJS antes de que Monaco lo pise.
                var moodleRequire = window.require;

                window.require.config({
                    paths: {vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.44.0/min/vs'},
                });

                window.require(['vs/editor/editor.main'], function() {
                    // Restaurar el require de Moodle.
                    window.require = moodleRequire;
                    defineCustomThemes();
                    resolve();
                });
            };

            loaderScript.onerror = reject;
            document.head.appendChild(loaderScript);
        });
    }

    /**
     * Inicializa la instancia del editor Monaco.
     */
    function initEditor() {
        var container = document.getElementById(config.editorEl);
        if (!container) {
            return;
        }

        editor = window.monaco.editor.create(container, {
            value:               config.initialCode || getDefaultCode(config.language),
            language:            getMonacoLanguage(config.language),
            theme:               'codelab-dark',
            automaticLayout:     true,
            minimap:             {enabled: false},
            fontSize:            14,
            lineHeight:          22,
            fontFamily:          "'JetBrains Mono', 'Fira Code', Consolas, monospace",
            scrollBeyondLastLine: false,
            renderLineHighlight: 'all',
            cursorBlinking:      'smooth',
            smoothScrolling:     true,
            tabSize:             4,
            insertSpaces:        true,
            formatOnPaste:       true,
            padding:             {top: 12, bottom: 12},
        });

        var langSelect = document.getElementById('codelab-lang-select');
        if (langSelect) {
            langSelect.addEventListener('change', function() {
                var model = editor.getModel();
                window.monaco.editor.setModelLanguage(model, getMonacoLanguage(langSelect.value));
            });
        }
    }

    /**
     * Renderiza la lista de casos de prueba en el panel derecho.
     */
    function renderTestcasesList() {
        var container = document.getElementById('codelab-testcases-list');
        if (!container) {
            return;
        }

        if (!config.testcases || config.testcases.length === 0) {
            container.innerHTML =
                '<div class="codelab-tc-empty text-muted p-3">' +
                '<i class="fa fa-info-circle"></i> Sin casos de prueba visibles.</div>';
            return;
        }

        var html = '';
        config.testcases.forEach(function(tc, i) {
            html += renderTestcaseCard(tc, i, null);
        });

        if (config.hiddenCount > 0) {
            html += '<div class="codelab-tc-hidden-notice alert alert-secondary mt-2 py-2">' +
                    '<i class="fa fa-eye-slash"></i> <strong>' + config.hiddenCount +
                    '</strong> caso(s) de prueba adicionales están ocultos. También se evaluarán al ejecutar.</div>';
        }

        container.innerHTML = html;
        updateScoreBadge(0, config.testcases.length + config.hiddenCount);
    }

    /**
     * Genera el HTML de una tarjeta de caso de prueba.
     */
    function renderTestcaseCard(tc, index, result) {
        var statusClass = 'pending';
        var statusIcon  = 'fa-circle-o';
        var statusText  = 'Sin ejecutar';

        if (result) {
            if (result.passed) {
                statusClass = 'passed';
                statusIcon  = 'fa-check-circle';
                statusText  = 'Correcto';
            } else {
                statusClass = mapStatusToClass(result.status);
                statusIcon  = 'fa-times-circle';
                statusText  = mapStatusToText(result.status);
            }
        }

        var stdinHtml = tc.stdin
            ? '<div class="tc-detail"><span class="tc-label">Entrada:</span> <code class="tc-code">' +
              escHtml(tc.stdin) + '</code></div>'
            : '';

        var outputHtml = tc.expected_output
            ? '<div class="tc-detail"><span class="tc-label">Salida esperada:</span> <code class="tc-code">' +
              escHtml(tc.expected_output) + '</code></div>'
            : '';

        var actualHtml = (result && !result.passed)
            ? '<div class="tc-detail tc-actual"><span class="tc-label text-danger">Tu salida:</span> ' +
              '<code class="tc-code text-danger">' + escHtml(result.actual_output || '(vacío)') + '</code></div>'
            : '';

        var errorHtml = (result && result.stderr)
            ? '<div class="tc-detail"><span class="tc-label text-warning">Error:</span> ' +
              '<pre class="tc-code text-warning">' + escHtml(result.stderr) + '</pre></div>'
            : '';

        var compileHtml = (result && result.compile_output)
            ? '<div class="tc-detail"><span class="tc-label text-danger">Error de compilación:</span> ' +
              '<pre class="tc-code text-danger">' + escHtml(result.compile_output) + '</pre></div>'
            : '';

        var timeHtml = (result && result.execution_time)
            ? '<span class="tc-meta text-muted ml-2"><i class="fa fa-clock-o"></i> ' + result.execution_time + 's</span>'
            : '';

        var ptsLabel = result
            ? (result.points_earned + '/' + result.points_possible)
            : tc.points;
        var ptsBadge = result && result.passed ? 'badge-success' : 'badge-secondary';
        var pointsHtml = '<span class="tc-points badge ' + ptsBadge + '">' + ptsLabel + ' pts</span>';

        return '<div class="codelab-tc-card status-' + statusClass + '" id="tc-card-' + tc.id +
               '" data-tcid="' + tc.id + '">' +
               '<div class="tc-card-header d-flex justify-content-between align-items-center">' +
               '<div class="tc-title"><i class="fa ' + statusIcon + ' tc-status-icon"></i> ' +
               '<strong class="tc-name">' + escHtml(tc.name) + '</strong>' +
               '<span class="tc-status-text text-muted ml-1">' + statusText + '</span>' +
               timeHtml + '</div>' + pointsHtml + '</div>' +
               '<div class="tc-card-body" style="display:none;">' +
               stdinHtml + outputHtml + actualHtml + errorHtml + compileHtml +
               '</div></div>';
    }

    /**
     * Vincula los eventos de botones.
     */
    function bindEvents() {
        var runBtn = document.getElementById('codelab-run-btn');
        if (runBtn) {
            runBtn.addEventListener('click', function() { runCode('run'); });
        }

        var submitBtn = document.getElementById('codelab-submit-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                if (window.confirm('¿Estás seguro de que deseas entregar este código?\nEsta acción contará como un intento.')) {
                    runCode('submit');
                }
            });
        }

        // Expandir/colapsar casos de prueba al hacer clic en el header.
        document.addEventListener('click', function(e) {
            var header = e.target.closest('.tc-card-header');
            if (header) {
                var card = header.closest('.codelab-tc-card');
                if (card) {
                    var body = card.querySelector('.tc-card-body');
                    if (body) {
                        body.style.display = body.style.display === 'none' ? 'block' : 'none';
                    }
                }
            }
        });
    }

    /**
     * Ejecuta el código contra los casos de prueba.
     */
    function runCode(action) {
        if (!editor) {
            return;
        }

        var code     = editor.getValue();
        var langSel  = document.getElementById('codelab-lang-select');
        var language = langSel ? langSel.value : config.language;
        var outputEl = document.getElementById('codelab-output');

        if (!code.trim()) {
            showOutputMessage('El editor está vacío. Escribe tu código antes de ejecutar.', 'warning');
            return;
        }

        setLoadingState(true, action);

        if (outputEl) {
            outputEl.textContent = action === 'submit'
                ? 'Enviando y evaluando tu entrega...'
                : 'Ejecutando y probando tu código...';
        }

        var body = JSON.stringify({
            cmid:     config.cmid,
            code:     code,
            language: language,
            action:   action,
            sesskey:  config.sesskey,
        });

        fetch(config.wwwroot + 'mod/codelab/execute.php', {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    body,
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (!data.success) {
                showOutputMessage('Error: ' + (data.error || 'Error desconocido'), 'danger');
                return;
            }
            displayResults(data, action);
        })
        .catch(function(err) {
            window.console.error('CodeLab: error al ejecutar', err);
            showOutputMessage('Error de conexión. Verifica tu red e inténtalo de nuevo.', 'danger');
        })
        .finally(function() {
            setLoadingState(false, action);
        });
    }

    /**
     * Muestra los resultados de ejecución en la UI.
     */
    function displayResults(data, action) {
        var container = document.getElementById('codelab-testcases-list');
        var outputEl  = document.getElementById('codelab-output');

        if (!container) {
            return;
        }

        var resultMap = {};
        (data.results || []).forEach(function(r) {
            resultMap[r.testcase_id] = r;
        });

        var html          = '';
        var visiblePassed = 0;

        config.testcases.forEach(function(tc, i) {
            var result = resultMap[tc.id] || null;
            if (result && result.passed) {
                visiblePassed++;
            }
            html += renderTestcaseCard(tc, i, result);
        });

        var hiddenResults = (data.results || []).filter(function(r) { return r.hidden; });
        var hiddenPassed  = hiddenResults.filter(function(r) { return r.passed; }).length;

        if (config.hiddenCount > 0) {
            var hiddenTotal = config.hiddenCount;
            var hiddenCls   = hiddenPassed === hiddenTotal ? 'alert-success' : 'alert-secondary';
            var hiddenIco   = hiddenPassed === hiddenTotal ? 'fa-check' : 'fa-eye-slash';
            html += '<div class="codelab-tc-hidden-notice alert ' + hiddenCls + ' mt-2 py-2">' +
                    '<i class="fa ' + hiddenIco + '"></i> Casos ocultos: <strong>' +
                    hiddenPassed + '/' + hiddenTotal + '</strong> correctos.</div>';
        }

        container.innerHTML = html;

        var totalPassed = data.tests_passed;
        var totalTests  = data.tests_total;

        updateScoreBadge(totalPassed, totalTests);

        var gradeDisplay = data.grade + ' / ' + data.max_grade;

        if (outputEl) {
            var pct = totalTests > 0 ? Math.round((totalPassed / totalTests) * 100) : 0;
            var icon = pct === 100 ? '🎉' : pct >= 60 ? '📊' : '❌';
            outputEl.textContent = icon + ' ' + totalPassed + '/' + totalTests +
                                   ' casos correctos  |  Nota: ' + gradeDisplay;

            var firstError = (data.results || []).filter(function(r) {
                return r.compile_output || r.stderr;
            })[0];
            if (firstError) {
                var errorMsg = firstError.compile_output || firstError.stderr;
                outputEl.textContent += '\n⚠ ' + String(errorMsg).substring(0, 300);
            }
        }

        if (action === 'submit' && data.submitted) {
            showSubmissionSuccess(data);
        }

        setTimeout(function() {
            var failed = document.querySelectorAll('.codelab-tc-card.status-failed, .codelab-tc-card.status-error');
            failed.forEach(function(card) {
                var body = card.querySelector('.tc-card-body');
                if (body) {
                    body.style.display = 'block';
                }
            });
        }, 100);
    }

    /**
     * Muestra el aviso de entrega exitosa.
     */
    function showSubmissionSuccess(data) {
        var pct = data.tests_total > 0
            ? Math.round((data.tests_passed / data.tests_total) * 100)
            : 0;
        var msg = '<strong>Entrega registrada!</strong><br>' +
                  'Casos correctos: <strong>' + data.tests_passed + '/' + data.tests_total + '</strong><br>' +
                  'Nota obtenida: <strong>' + data.grade + ' / ' + data.max_grade + '</strong>';

        Notification.addNotification({
            message: msg,
            type:    pct >= 60 ? 'success' : 'info',
        });
    }

    /**
     * Actualiza el badge de puntuación.
     */
    function updateScoreBadge(passed, total) {
        var badge = document.getElementById('codelab-score-badge');
        if (!badge) {
            return;
        }
        badge.textContent = passed + ' / ' + total;
        badge.className = 'badge ml-2 ';
        if (total === 0 || passed === 0) {
            badge.className += 'badge-secondary';
        } else if (passed === total) {
            badge.className += 'badge-success';
        } else {
            badge.className += 'badge-warning';
        }
    }

    /**
     * Cambia el estado de carga de los botones.
     */
    function setLoadingState(loading, action) {
        var runBtn    = document.getElementById('codelab-run-btn');
        var submitBtn = document.getElementById('codelab-submit-btn');

        if (runBtn) {
            runBtn.disabled = loading;
            runBtn.innerHTML = (loading && action === 'run')
                ? '<i class="fa fa-spinner fa-spin"></i> Ejecutando...'
                : '<i class="fa fa-play"></i> Ejecutar y probar';
        }
        if (submitBtn) {
            submitBtn.disabled = loading;
            submitBtn.innerHTML = (loading && action === 'submit')
                ? '<i class="fa fa-spinner fa-spin"></i> Enviando...'
                : '<i class="fa fa-paper-plane"></i> Entregar';
        }
    }

    /**
     * Muestra un mensaje en el área de salida.
     */
    function showOutputMessage(msg, type) {
        var outputEl = document.getElementById('codelab-output');
        if (outputEl) {
            outputEl.className = 'codelab-output-pre text-' + (type || 'info');
            outputEl.textContent = msg;
        }
    }

    function escHtml(str) {
        if (!str) {
            return '';
        }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getMonacoLanguage(lang) {
        var map = {
            python: 'python', javascript: 'javascript', java: 'java',
            c: 'c', cpp: 'cpp', php: 'php', csharp: 'csharp',
            ruby: 'ruby', go: 'go', kotlin: 'kotlin',
        };
        return map[lang] || 'plaintext';
    }

    function getDefaultCode(lang) {
        var defaults = {
            python:     '# Escribe tu solución aquí\n\n',
            javascript: '// Escribe tu solución aquí\n\n',
            java:       'public class Main {\n    public static void main(String[] args) {\n        // Tu código aquí\n    }\n}\n',
            c:          '#include <stdio.h>\n\nint main() {\n    // Tu código aquí\n    return 0;\n}\n',
            cpp:        '#include <iostream>\nusing namespace std;\n\nint main() {\n    // Tu código aquí\n    return 0;\n}\n',
            php:        '<?php\n// Tu código aquí\n',
            csharp:     'using System;\n\nclass Program {\n    static void Main() {\n        // Tu código aquí\n    }\n}\n',
            ruby:       '# Escribe tu solución aquí\n\n',
            go:         'package main\n\nimport "fmt"\n\nfunc main() {\n    // Tu código aquí\n}\n',
            kotlin:     'fun main() {\n    // Tu código aquí\n}\n',
        };
        return defaults[lang] || '// Escribe tu solución aquí\n';
    }

    function mapStatusToClass(status) {
        var map = {
            passed: 'passed', failed: 'failed', timeout: 'timeout',
            compile_error: 'error', error: 'error',
        };
        return map[status] || 'error';
    }

    function mapStatusToText(status) {
        var map = {
            passed:         'Correcto',
            failed:         'Incorrecto',
            timeout:        'Tiempo excedido',
            compile_error:  'Error de compilación',
            error:          'Error de ejecución',
            internal_error: 'Error interno',
        };
        return map[status] || 'Error';
    }

    function defineCustomThemes() {
        window.monaco.editor.defineTheme('codelab-dark', {
            base:    'vs-dark',
            inherit: true,
            rules: [
                {token: 'comment', foreground: '6A9955', fontStyle: 'italic'},
                {token: 'keyword', foreground: '569CD6'},
                {token: 'string',  foreground: 'CE9178'},
                {token: 'number',  foreground: 'B5CEA8'},
            ],
            colors: {
                'editor.background':            '#1E1E2E',
                'editor.foreground':            '#CDD6F4',
                'editor.lineHighlightBackground': '#2A2A3E',
                'editorCursor.foreground':      '#F5C2E7',
                'editor.selectionBackground':   '#3D3D5C',
                'editorLineNumber.foreground':  '#45475A',
                'editorLineNumber.activeForeground': '#CDD6F4',
            },
        });
    }

    return {
        init: init
    };
});
