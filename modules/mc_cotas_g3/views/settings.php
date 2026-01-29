<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php echo form_open(admin_url('mc_cotas_g3/settings')); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-cog"></i> <?php echo _l('mc_cotas_g3_settings'); ?>
                        </h4>
                        <hr class="hr-panel-heading">

                        <!-- Configurações de Conexão SQL Server -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('mc_cotas_g3_connection_settings'); ?></h4>
                                <p class="text-muted"><?php echo _l('mc_cotas_g3_connection_settings_desc'); ?></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sqlserver_host', 'mc_cotas_g3_sqlserver_host', get_option('mc_cotas_g3_sqlserver_host'), 'text', ['required' => true]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sqlserver_port', 'mc_cotas_g3_sqlserver_port', get_option('mc_cotas_g3_sqlserver_port'), 'text', ['required' => true]); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sqlserver_database', 'mc_cotas_g3_sqlserver_database', get_option('mc_cotas_g3_sqlserver_database'), 'text', ['required' => true]); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sqlserver_user', 'mc_cotas_g3_sqlserver_user', get_option('mc_cotas_g3_sqlserver_user'), 'text', ['required' => true]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sqlserver_password', 'mc_cotas_g3_sqlserver_password', get_option('mc_cotas_g3_sqlserver_password'), 'password', ['required' => true, 'autocomplete' => 'new-password']); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-info" id="test-connection">
                                    <i class="fa fa-plug"></i> <?php echo _l('mc_cotas_g3_test_connection'); ?>
                                </button>
                                <div id="connection-result" class="mtop10"></div>
                            </div>
                        </div>

                        <hr class="mtop30">

                        <!-- Configurações de Sincronização -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('mc_cotas_g3_sync_settings'); ?></h4>
                                <p class="text-muted"><?php echo _l('mc_cotas_g3_sync_settings_desc'); ?></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" name="mc_cotas_g3_auto_sync" id="mc_cotas_g3_auto_sync"
                                               <?php echo get_option('mc_cotas_g3_auto_sync') == '1' ? 'checked' : ''; ?>>
                                        <label for="mc_cotas_g3_auto_sync">
                                            <?php echo _l('mc_cotas_g3_auto_sync'); ?>
                                        </label>
                                    </div>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_auto_sync_help'); ?></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sync_interval', 'mc_cotas_g3_sync_interval', get_option('mc_cotas_g3_sync_interval'), 'number', ['min' => 1]); ?>
                                <small class="text-muted"><?php echo _l('mc_cotas_g3_sync_interval_help'); ?></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" name="mc_cotas_g3_sync_only_titular" id="mc_cotas_g3_sync_only_titular"
                                               <?php echo get_option('mc_cotas_g3_sync_only_titular') == '1' ? 'checked' : ''; ?>>
                                        <label for="mc_cotas_g3_sync_only_titular">
                                            <?php echo _l('mc_cotas_g3_sync_only_titular'); ?>
                                        </label>
                                    </div>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_sync_only_titular_help'); ?></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" name="mc_cotas_g3_sync_only_active" id="mc_cotas_g3_sync_only_active"
                                               <?php echo get_option('mc_cotas_g3_sync_only_active') == '1' ? 'checked' : ''; ?>>
                                        <label for="mc_cotas_g3_sync_only_active">
                                            <?php echo _l('mc_cotas_g3_sync_only_active'); ?>
                                        </label>
                                    </div>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_sync_only_active_help'); ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('mc_cotas_g3_sync_batch_size', 'mc_cotas_g3_sync_batch_size', get_option('mc_cotas_g3_sync_batch_size'), 'number', ['min' => 10, 'max' => 1000, 'step' => 10]); ?>
                                <small class="text-muted"><?php echo _l('mc_cotas_g3_sync_batch_size_help'); ?></small>
                            </div>
                        </div>

                        <hr class="mtop30">

                        <!-- Configurações de Mapeamento -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('mc_cotas_g3_mapping_settings'); ?></h4>
                                <p class="text-muted"><?php echo _l('mc_cotas_g3_mapping_settings_desc'); ?></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mc_cotas_g3_default_status"><?php echo _l('mc_cotas_g3_default_status'); ?></label>
                                    <select name="mc_cotas_g3_default_status" id="mc_cotas_g3_default_status" class="selectpicker" data-width="100%">
                                        <?php foreach ($lead_statuses as $status) { ?>
                                            <option value="<?php echo $status['id']; ?>"
                                                    <?php echo get_option('mc_cotas_g3_default_status') == $status['id'] ? 'selected' : ''; ?>>
                                                <?php echo $status['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_default_status_help'); ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mc_cotas_g3_default_source"><?php echo _l('mc_cotas_g3_default_source'); ?></label>
                                    <select name="mc_cotas_g3_default_source" id="mc_cotas_g3_default_source" class="selectpicker" data-width="100%">
                                        <?php foreach ($lead_sources as $source) { ?>
                                            <option value="<?php echo $source['id']; ?>"
                                                    <?php echo get_option('mc_cotas_g3_default_source') == $source['id'] ? 'selected' : ''; ?>>
                                                <?php echo $source['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_default_source_help'); ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mc_cotas_g3_default_assigned"><?php echo _l('mc_cotas_g3_default_assigned'); ?></label>
                                    <select name="mc_cotas_g3_default_assigned" id="mc_cotas_g3_default_assigned" class="selectpicker" data-width="100%">
                                        <option value="0"><?php echo _l('mc_cotas_g3_not_assigned'); ?></option>
                                        <?php foreach ($staff as $member) { ?>
                                            <option value="<?php echo $member['staffid']; ?>"
                                                    <?php echo get_option('mc_cotas_g3_default_assigned') == $member['staffid'] ? 'selected' : ''; ?>>
                                                <?php echo $member['firstname'] . ' ' . $member['lastname']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_default_assigned_help'); ?></small>
                                </div>
                            </div>
                        </div>

                        <hr class="mtop30">

                        <!-- Configurações de Log -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('mc_cotas_g3_log_settings'); ?></h4>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" name="mc_cotas_g3_enable_detailed_log" id="mc_cotas_g3_enable_detailed_log"
                                               <?php echo get_option('mc_cotas_g3_enable_detailed_log') == '1' ? 'checked' : ''; ?>>
                                        <label for="mc_cotas_g3_enable_detailed_log">
                                            <?php echo _l('mc_cotas_g3_enable_detailed_log'); ?>
                                        </label>
                                    </div>
                                    <small class="text-muted"><?php echo _l('mc_cotas_g3_enable_detailed_log_help'); ?></small>
                                </div>
                            </div>
                        </div>

                        <hr class="mtop30">

                        <!-- Botões de Ação -->
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary pull-right">
                                    <i class="fa fa-save"></i> <?php echo _l('settings_save'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(document).ready(function() {
    $('#test-connection').on('click', function() {
        var btn = $(this);
        var resultDiv = $('#connection-result');

        btn.prop('disabled', true);
        btn.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('mc_cotas_g3_testing_connection'); ?>');
        resultDiv.html('');

        $.ajax({
            url: admin_url + 'mc_cotas_g3/test_connection',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="alert alert-success"><i class="fa fa-check"></i> ' + response.message + '</div>');
                } else {
                    resultDiv.html('<div class="alert alert-danger"><i class="fa fa-times"></i> ' + response.message + '</div>');
                }
            },
            error: function() {
                resultDiv.html('<div class="alert alert-danger"><i class="fa fa-times"></i> <?php echo _l('mc_cotas_g3_connection_error'); ?></div>');
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.html('<i class="fa fa-plug"></i> <?php echo _l('mc_cotas_g3_test_connection'); ?>');
            }
        });
    });
});
</script>

</body>
</html>
