/**
 * MC Cotas G3 - JavaScript
 */

(function($) {
    'use strict';

    // Executar quando o documento estiver pronto
    $(document).ready(function() {

        // Inicializar componentes
        initMcCotasG3();

    });

    /**
     * Inicializar MC Cotas G3
     */
    function initMcCotasG3() {

        // Auto-refresh da página de sincronização
        if (window.location.href.indexOf('mc_cotas_g3/sync') > -1) {
            // Adicionar tooltips
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Confirmação de sincronização
        $('.mc-cotas-sync-btn').on('click', function(e) {
            var confirmMsg = $(this).data('confirm-message');
            if (confirmMsg) {
                return confirm(confirmMsg);
            }
        });

        // Formatação de números nas estatísticas
        formatStatNumbers();

        // Highlight de erros na tabela
        highlightErrors();
    }

    /**
     * Formatar números nas estatísticas
     */
    function formatStatNumbers() {
        $('.mc-cotas-stat-box h3').each(function() {
            var $this = $(this);
            var number = parseInt($this.text());

            if (!isNaN(number)) {
                // Animar número se for novo
                if ($this.hasClass('animate-number')) {
                    animateNumber($this, 0, number, 1000);
                }
            }
        });
    }

    /**
     * Animar número
     */
    function animateNumber($element, start, end, duration) {
        var range = end - start;
        var current = start;
        var increment = end > start ? 1 : -1;
        var stepTime = Math.abs(Math.floor(duration / range));

        var timer = setInterval(function() {
            current += increment;
            $element.text(current);
            if (current == end) {
                clearInterval(timer);
            }
        }, stepTime);
    }

    /**
     * Highlight de erros
     */
    function highlightErrors() {
        $('.mc-cotas-history-table tbody tr').each(function() {
            var $row = $(this);
            var errors = parseInt($row.find('td:eq(4) .label').text());

            if (errors > 0) {
                $row.addClass('warning');
            }
        });
    }

    /**
     * Testar conexão SQL Server
     */
    window.testSqlServerConnection = function(button) {
        var $btn = $(button);
        var $result = $('#connection-result');

        // Desabilitar botão
        $btn.prop('disabled', true);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Testando conexão...');

        // Limpar resultado anterior
        $result.html('').hide();

        // Fazer requisição AJAX
        $.ajax({
            url: admin_url + 'mc_cotas_g3/test_connection',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $result.html(
                        '<div class="mc-cotas-message success">' +
                        '<i class="fa fa-check-circle"></i>' +
                        '<span>' + response.message + '</span>' +
                        '</div>'
                    ).fadeIn();
                } else {
                    $result.html(
                        '<div class="mc-cotas-message error">' +
                        '<i class="fa fa-times-circle"></i>' +
                        '<span>' + response.message + '</span>' +
                        '</div>'
                    ).fadeIn();
                }
            },
            error: function() {
                $result.html(
                    '<div class="mc-cotas-message error">' +
                    '<i class="fa fa-times-circle"></i>' +
                    '<span>Erro ao testar conexão. Tente novamente.</span>' +
                    '</div>'
                ).fadeIn();
            },
            complete: function() {
                // Reabilitar botão
                $btn.prop('disabled', false);
                $btn.html('<i class="fa fa-plug"></i> Testar Conexão');
            }
        });
    };

    /**
     * Atualizar histórico de sincronização
     */
    window.refreshSyncHistory = function() {
        var $historyTable = $('.mc-cotas-history-table tbody');

        if ($historyTable.length === 0) {
            return;
        }

        // Adicionar loading
        $historyTable.addClass('mc-cotas-loading');

        $.ajax({
            url: admin_url + 'mc_cotas_g3/get_sync_history',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.length > 0) {
                    // Atualizar tabela
                    renderSyncHistory(response, $historyTable);
                }
            },
            error: function() {
                console.error('Erro ao atualizar histórico de sincronização');
            },
            complete: function() {
                $historyTable.removeClass('mc-cotas-loading');
            }
        });
    };

    /**
     * Renderizar histórico de sincronização
     */
    function renderSyncHistory(data, $container) {
        $container.empty();

        $.each(data, function(index, log) {
            var row = '<tr>' +
                '<td>' + formatDateTime(log.sync_date) +
                (log.is_cron == 1 ? ' <span class="label label-default">CRON</span>' : '') +
                '</td>' +
                '<td class="text-center">' + log.total_members + '</td>' +
                '<td class="text-center"><span class="label label-success">' + log.new_leads + '</span></td>' +
                '<td class="text-center"><span class="label label-info">' + log.updated_leads + '</span></td>' +
                '<td class="text-center">' +
                (log.errors > 0 ?
                    '<a href="' + admin_url + 'mc_cotas_g3/view_sync_log/' + log.id + '" class="label label-danger">' + log.errors + '</a>' :
                    '<span class="label label-default">' + log.errors + '</span>'
                ) +
                '</td>' +
                '<td class="text-center">' + log.execution_time + 's</td>' +
                '<td>' + (log.sync_by ? getStaffName(log.sync_by) : 'Sistema') + '</td>' +
                '</tr>';

            $container.append(row);
        });

        highlightErrors();
    }

    /**
     * Formatar data/hora
     */
    function formatDateTime(datetime) {
        if (!datetime) return '-';

        var date = new Date(datetime);
        var day = ('0' + date.getDate()).slice(-2);
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var year = date.getFullYear();
        var hours = ('0' + date.getHours()).slice(-2);
        var minutes = ('0' + date.getMinutes()).slice(-2);

        return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
    }

    /**
     * Obter nome do staff (placeholder - implementar de acordo com o sistema)
     */
    function getStaffName(staffId) {
        // Esta função deve ser implementada para buscar o nome do staff
        return 'Staff #' + staffId;
    }

    /**
     * Confirmar ação
     */
    window.confirmAction = function(message, callback) {
        if (confirm(message)) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    };

    /**
     * Mostrar notificação
     */
    window.showNotification = function(type, message) {
        var iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';

        var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade in" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '<i class="fa ' + iconClass + '"></i> ' + message +
            '</div>');

        $('.content').prepend(notification);

        // Auto-remover após 5 segundos
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };

})(jQuery);
