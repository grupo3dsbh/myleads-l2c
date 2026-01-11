<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    <i class="fa fa-file-text-o"></i> <?php echo _l('mc_cotas_g3_sync_log_details'); ?>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('mc_cotas_g3/sync'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">

                        <!-- Informações Gerais -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4><?php echo _l('mc_cotas_g3_general_info'); ?></h4>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%"><?php echo _l('mc_cotas_g3_sync_date'); ?></th>
                                        <td><?php echo _dt($log->sync_date); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('mc_cotas_g3_sync_by'); ?></th>
                                        <td>
                                            <?php
                                            if ($log->sync_by) {
                                                echo get_staff_full_name($log->sync_by);
                                            } else {
                                                echo _l('system');
                                            }
                                            ?>
                                            <?php if ($log->is_cron == 1) { ?>
                                                <span class="label label-default">CRON</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('mc_cotas_g3_execution_time'); ?></th>
                                        <td><?php echo $log->execution_time; ?> segundos</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%"><?php echo _l('mc_cotas_g3_total_members'); ?></th>
                                        <td><span class="label label-info"><?php echo $log->total_members; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('mc_cotas_g3_new_leads'); ?></th>
                                        <td><span class="label label-success"><?php echo $log->new_leads; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('mc_cotas_g3_updated_leads'); ?></th>
                                        <td><span class="label label-info"><?php echo $log->updated_leads; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('mc_cotas_g3_errors'); ?></th>
                                        <td><span class="label label-danger"><?php echo $log->errors; ?></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($errors) && $log->errors > 0) { ?>
                            <hr class="mtop30">

                            <!-- Detalhes dos Erros -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h4><?php echo _l('mc_cotas_g3_error_details'); ?></h4>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th><?php echo _l('mc_cotas_g3_member_id'); ?></th>
                                                    <th><?php echo _l('mc_cotas_g3_member_name'); ?></th>
                                                    <th><?php echo _l('mc_cotas_g3_error_message'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 1; foreach ($errors as $error) { ?>
                                                    <tr>
                                                        <td><?php echo $i++; ?></td>
                                                        <td><?php echo $error['member_id'] ?? 'N/A'; ?></td>
                                                        <td><?php echo $error['member_name'] ?? 'N/A'; ?></td>
                                                        <td><code><?php echo $error['error'] ?? 'N/A'; ?></code></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
</body>
</html>
