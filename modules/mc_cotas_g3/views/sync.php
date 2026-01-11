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
                                    <i class="fa fa-cloud-download"></i> <?php echo _l('mc_cotas_g3_sync'); ?>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <?php if (has_permission('mc_cotas_g3', '', 'create')) { ?>
                                    <a href="<?php echo admin_url('mc_cotas_g3/sync_now'); ?>"
                                       class="btn btn-primary"
                                       onclick="return confirm('<?php echo _l('mc_cotas_g3_sync_confirm'); ?>');">
                                        <i class="fa fa-refresh"></i> <?php echo _l('mc_cotas_g3_sync_now'); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">

                        <!-- Estatísticas -->
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body text-center">
                                        <h3 class="text-info bold"><?php echo $stats['total_synced']; ?></h3>
                                        <p class="text-muted"><?php echo _l('mc_cotas_g3_total_synced'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body text-center">
                                        <h3 class="text-success bold"><?php echo $stats['total_titular']; ?></h3>
                                        <p class="text-muted"><?php echo _l('mc_cotas_g3_total_titular'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body text-center">
                                        <h3 class="text-warning bold"><?php echo $stats['total_dependente']; ?></h3>
                                        <p class="text-muted"><?php echo _l('mc_cotas_g3_total_dependente'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="panel_s">
                                    <div class="panel-body text-center">
                                        <h3 class="text-muted bold">
                                            <?php
                                            if ($stats['last_sync']) {
                                                echo time_ago($stats['last_sync']);
                                            } else {
                                                echo _l('mc_cotas_g3_never_synced');
                                            }
                                            ?>
                                        </h3>
                                        <p class="text-muted"><?php echo _l('mc_cotas_g3_last_sync'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Histórico de Sincronização -->
                        <div class="row mtop20">
                            <div class="col-md-12">
                                <h4><?php echo _l('mc_cotas_g3_sync_history'); ?></h4>
                                <hr>

                                <?php if (!empty($history)) { ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th><?php echo _l('mc_cotas_g3_sync_date'); ?></th>
                                                    <th class="text-center"><?php echo _l('mc_cotas_g3_total_members'); ?></th>
                                                    <th class="text-center"><?php echo _l('mc_cotas_g3_new_leads'); ?></th>
                                                    <th class="text-center"><?php echo _l('mc_cotas_g3_updated_leads'); ?></th>
                                                    <th class="text-center"><?php echo _l('mc_cotas_g3_errors'); ?></th>
                                                    <th class="text-center"><?php echo _l('mc_cotas_g3_execution_time'); ?></th>
                                                    <th><?php echo _l('mc_cotas_g3_sync_by'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($history as $log) { ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo _dt($log['sync_date']); ?>
                                                            <?php if ($log['is_cron'] == 1) { ?>
                                                                <span class="label label-default">CRON</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="text-center"><?php echo $log['total_members']; ?></td>
                                                        <td class="text-center">
                                                            <span class="label label-success"><?php echo $log['new_leads']; ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="label label-info"><?php echo $log['updated_leads']; ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if ($log['errors'] > 0) { ?>
                                                                <a href="<?php echo admin_url('mc_cotas_g3/view_sync_log/' . $log['id']); ?>"
                                                                   class="label label-danger">
                                                                    <?php echo $log['errors']; ?>
                                                                </a>
                                                            <?php } else { ?>
                                                                <span class="label label-default"><?php echo $log['errors']; ?></span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="text-center"><?php echo $log['execution_time']; ?>s</td>
                                                        <td>
                                                            <?php
                                                            if ($log['sync_by']) {
                                                                echo get_staff_full_name($log['sync_by']);
                                                            } else {
                                                                echo _l('system');
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } else { ?>
                                    <div class="alert alert-info">
                                        <?php echo _l('mc_cotas_g3_no_sync_history'); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
</body>
</html>
