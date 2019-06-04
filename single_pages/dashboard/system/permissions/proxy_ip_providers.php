<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Package\ProxyIpManager\Controller\SinglePage\Dashboard\System\Permissions\ProxyIpProviders $controller
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Html\Service\Html $html
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 * @var array $providers
 * @var bool $autoUpdatingEnabled
 * @var int $autoUpdatingInterval
 * @var string $cliUpdateCommandName
 * @var string $nextAutomaticRun
 */

?>
<div id="proxy_ip_manager-vue" style="display: none">

    <div class="ccm-dashboard-header-buttons">
        <button class="btn btn-primary" v-bind:disabled="busy" v-on:click.prevent="applyUpdates"><?= t('Apply updates') ?></button>
    </div>

    <div id="proxy_ip_manager-updateresults" style="display: none">
        <pre>{{ updatesResult }}</pre>
        <div class="dialog-buttons">
            <button class="btn btn-primary" onclick="$(this).closest('.ui-dialog').find('.ui-dialog-content').dialog('close')"><?= t('Close') ?></button>
        </div>
    </div>

    <fieldset>
        <legend><?= t('Providers') ?></legend>

        <div class="alert alert-warning" v-if="providers.length === 0">
            <?= t('No Proxy IP Provider is registered.') ?>
        </div>

        <template v-else>
            <table class="table">
                <thead>
                    <tr>
                        <th></th>
                        <th><?= t('Handle') ?></th>
                        <th><?= t('Name') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(provider, providerIndex) in providers" v-bind:class="provider.enabled ? 'success' : 'danger'">
                        <td class="proxy_ip_manager-enabling">
                            <button class="btn btn-xs btn-success" v-bind:disabled="busy" v-on:click.prevent="setProviderEnabled(providerIndex, false)" v-if="provider.enabled" title="<?= t('Disable') ?>"><i class="fa fa-toggle-on"></i></button>
                            <button class="btn btn-xs btn-danger" v-bind:disabled="busy" v-on:click.prevent="setProviderEnabled(providerIndex, true)" v-else title="<?= t('Enable') ?>"><i class="fa fa-toggle-off"></i></button>
                        </td>
                        <td class="proxy_ip_manager-handle"><code>{{ provider.handle }}</code></td>
                        <td>{{ provider.name }}</td>
                        <td class="proxy_ip_manager-operations">
                            <a class="btn btn-xs btn-primary" title="<?= t('Configure') ?>" v-bind:disabled="busy" v-bind:href="<?= h(json_encode(rtrim((string) $view->action('configure'), '/') . '/') . ' + provider.handle') ?>" v-if="provider.configurable"><i class="fa fa-cog"></i></a>
                            <button class="btn btn-xs btn-info" title="<?= t('Test') ?>" v-bind:disabled="busy" v-on:click.prevent="testProvider(providerIndex)"><i class="fa fa-check"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>


            <div id="proxy_ip_manager-testresults" style="display: none">
                <template v-if="test.ips.length !== 0">
                    <p><?= t('The provider returned the following addresses:') ?></p>
                    <ul>
                        <li v-for="ip in test.ips"><code>{{ ip }}</code></li>
                    </ul>
                </template>
                <div class="alert alert-danger" v-if="test.errors">
                    <p><?= t('The following errors occurred:') ?></p>
                    <div style="white-space: pre-wrap">{{ test.errors }}</div>
                </div>
                <div class="alert alert-success" v-else>
                    <p><?= t('No errors occurred.') ?></p>
                </div>
                <div class="dialog-buttons">
                    <button class="btn btn-primary" onclick="$(this).closest('.ui-dialog').find('.ui-dialog-content').dialog('close')"><?= t('Close') ?></button>
                </div>
            </div>

        </template>
    </fieldset>

    <fieldset>
        <legend><?= t('Automatic updates') ?></legend>
        <div class="form-group">
            <?= $form->label('', t('Auto-updating')) ?>
            <div class="checkbox">
                <label>
                    <?= $form->checkbox('', '', false, ['v-model' => 'autoUpdating.enabled', 'v-bind:disabled' => 'busy']) ?>
                    <?= t('Enable automatic refresh during web requests')?>
                    <div class="text-muted small">
                        <?= t('By enabling this feature, the IP addresses will be updated automatically during normal web requests.') ?><br />
                        <?= t('This may slow down a bit the execution, in particular for slow providers. So, you should use a high update interval, or evaluate scheduling the execution of the %s CLI command instead.', "<code>{$cliUpdateCommandName}</code>") ?>
                    </div>
                </label>
            </div>
        </div>
        <div class="form-group">
            <?= $form->label('', t('Auto-updating interval')) ?>
            <div class="input-group input-group-sm" v-if="autoUpdating.editingInterval === null">
                <span class="form-control form-control-sm">{{ autoUpdatingIntervalDisplayValue }}</span>
                <span class="input-group-btn">
                    <button class="btn btn-sm btn-primary" v-bind:disabled="busy" v-on:click.prevent="autoUpdating.editingInterval = autoUpdating.interval"><i class="fa fa-pencil"></i></button>
                </span>
            </div>
            <div class="input-group input-group-sm" v-else>
                <input type="number" class="form-control" v-bind:value="autoUpdatingEditingIntervalParts.days" v-bind:disabled="busy" min="0" ref="autoUpdatingEditD" />
                <span class="input-group-addon"><?= Punic\Unit::getName('duration/day', 'narrow') ?></span>
                <input type="number" class="form-control" v-bind:value="autoUpdatingEditingIntervalParts.hours" v-bind:disabled="busy" min="0" ref="autoUpdatingEditH" />
                <span class="input-group-addon"><?= Punic\Unit::getName('duration/hour', 'narrow') ?></span>
                <input type="number" class="form-control" v-bind:value="autoUpdatingEditingIntervalParts.minutes" v-bind:disabled="busy" min="0" ref="autoUpdatingEditM" />
                <span class="input-group-addon"><?= Punic\Unit::getName('duration/minute', 'narrow') ?></span>
                <input type="number" class="form-control" v-bind:value="autoUpdatingEditingIntervalParts.seconds" v-bind:disabled="busy" min="0" ref="autoUpdatingEditS" />
                <span class="input-group-addon"><?= Punic\Unit::getName('duration/second', 'narrow') ?></span>
                <span class="input-group-btn">
                    <button class="btn btn-danger" v-bind:disabled="busy" v-on:click.prevent="autoUpdating.editingInterval = null"><i class="fa fa-times"></i></button>
                    <button class="btn btn-success" v-bind:disabled="busy" v-on:click.prevent="saveAutoUpdatingInterval"><i class="fa fa-check"></i></button>
                </span>
            </div>
        </div>

        <div class="alert alert-info" v-if="nextAutomaticRun !== ''">
            {{ nextAutomaticRun }}
        </div>
    </fieldset>

</div>

<script>
$(document).ready(function() {
'use strict';

$('#proxy_ip_manager-vue').show();

var vue = new Vue({
    el: '#proxy_ip_manager-vue',
    data: function() {
        return <?= json_encode([
            'busy' => false,
            'providers' => $providers,
            'test' => [
                'errors' => '',
                'ips' => [],
            ],
            'autoUpdating' => [
                'enabled' => $autoUpdatingEnabled,
                'enabledSaved' => $autoUpdatingEnabled,
                'interval' => $autoUpdatingInterval,
                'editingInterval' => null,
            ],
            'updatesResult' => '',
            'nextAutomaticRun' => $nextAutomaticRun,
        ]) ?>;
    },
    watch: {
        'autoUpdating.enabled': function(newValue, oldValue) {
            var my = this;
            if (newValue === my.autoUpdating.enabledSaved) {
                return;
            }
            my.busy = true;
            $.ajax({
                cache: false,
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('proxy_ip_manager-enable_auto_updating')) ?>,
                    enable: newValue ? 1 : 0
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('enable_auto_updating')) ?>
            })
            .done(function(data) {
                my.nextAutomaticRun = data.nextAutomaticRun;
                my.autoUpdating.enabledSaved = newValue;
            })
            .fail(function(xhr, status, error) {
                my.autoUpdating.enabled = my.autoUpdating.enabledSaved;
                window.alert(xhr.responseJSON && xhr.responseJSON.error ? (xhr.responseJSON.error.message || xhr.responseJSON.error) : error);
            })
            .always(function() {
                my.busy = false;
            });
        }
    },
    computed: {
        autoUpdatingIntervalDisplayValue: function() {
            var dhms = this.secondsToDHMS(this.autoUpdating.interval),
                names = <?= json_encode([
                    'days' => Punic\Unit::getName('duration/day', 'long'),
                    'hours' => Punic\Unit::getName('duration/hour', 'long'),
                    'minutes' => Punic\Unit::getName('duration/minute', 'long'),
                    'seconds' => Punic\Unit::getName('duration/second', 'long'),
                ]) ?>,
                chunks = [];
            $.each(['days', 'hours', 'minutes', 'seconds'], function(_, key) {
                if (dhms[key] !== 0) {
                    chunks.push(dhms[key] + ' ' + names[key]);
                }
            });
            return chunks.length === 0 ? <?= json_encode(t('For every request')) ?> : chunks.join(', ');
        },
        autoUpdatingEditingIntervalParts: function() {
            return this.secondsToDHMS(this.autoUpdating.editingInterval);
        }
    },
    methods: {
        applyUpdates: function() {
            var my = this;
            my.busy = true;
            $.ajax({
                cache: false,
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('proxy_ip_manager-apply_updates')) ?>
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('apply_updates')) ?>
            })
            .done(function(data) {
                my.nextAutomaticRun = data.nextAutomaticRun;
                my.updatesResult = data.updatesResult;
                my.$nextTick(function() {
                    var $dlg;
                    $(document.body).append($dlg = $('<div class="ccm-ui" />').html($('#proxy_ip_manager-updateresults').html()));
                    $dlg.dialog({
                        modal: true,
                        width: Math.min(Math.max($(window).width() - 50, 200), 1500),
                        close: function() {
                            $dlg.remove();
                        }
                    });
                });
            })
            .fail(function(xhr, status, error) {
                window.alert(xhr.responseJSON && xhr.responseJSON.error ? (xhr.responseJSON.error.message || xhr.responseJSON.error) : error);
            })
            .always(function() {
                my.busy = false;
            });
        },
        testProvider: function(index) {
            var my = this;
            my.busy = true;
            $.ajax({
                cache: false,
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('proxy_ip_manager-test_provider')) ?>,
                    handle: my.providers[index].handle
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('test_provider')) ?>
            })
            .done(function(data) {
                my.nextAutomaticRun = data.nextAutomaticRun;
                my.test.errors = data.errors;
                my.test.ips.splice(0, my.test.ips.length);
                my.test.ips.push.apply(my.test.ips, data.ips);
                my.$nextTick(function() {
                    var $dlg;
                    $(document.body).append($dlg = $('<div class="ccm-ui" />').html($('#proxy_ip_manager-testresults').html()));
                    $dlg.dialog({
                        modal: true,
                        close: function() {
                            $dlg.remove();
                        }
                    });
                });
            })
            .fail(function(xhr, status, error) {
                window.alert(xhr.responseJSON && xhr.responseJSON.error ? (xhr.responseJSON.error.message || xhr.responseJSON.error) : error);
            })
            .always(function() {
                my.busy = false;
            });
        },
        setProviderEnabled: function(index, enabled) {
            var my = this;
            my.busy = true;
            $.ajax({
                cache: false,
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('proxy_ip_manager-set_provider_enabled')) ?>,
                    handle: my.providers[index].handle,
                    enabled: enabled ? 1 : 0
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('set_provider_enabled')) ?>
            })
            .done(function(data) {
                my.nextAutomaticRun = data.nextAutomaticRun;
                my.providers[index].enabled = enabled;
            })
            .fail(function(xhr, status, error) {
                window.alert(xhr.responseJSON && xhr.responseJSON.error ? (xhr.responseJSON.error.message || xhr.responseJSON.error) : error);
            })
            .always(function() {
                my.busy = false;
            });
        },
        secondsToDHMS: function(seconds) {
            var result = {},
                factor;
            factor = 60 * 60 * 24;
            result.days = Math.floor(seconds / factor);
            seconds -= result.days * factor;
            factor = 60 * 60;
            result.hours = Math.floor(seconds / factor);
            seconds -= result.hours * factor;
            factor = 60;
            result.minutes = Math.floor(seconds / factor);
            result.seconds = seconds - result.minutes * factor;

            return result;
        },
        saveAutoUpdatingInterval: function() {
            var my = this,
                toInt = function(value) { return Math.max(0, parseInt($.trim(value), 10) || 0); },
                days = toInt(this.$refs.autoUpdatingEditD.value),
                hours = toInt(this.$refs.autoUpdatingEditH.value),
                minutes = toInt(this.$refs.autoUpdatingEditM.value),
                seconds = toInt(this.$refs.autoUpdatingEditS.value);
            my.autoUpdating.editingInterval = seconds + 60 * (minutes + 60 * (hours + 24 * days));
            if (my.autoUpdating.editingInterval === my.autoUpdating.interval) {
                my.autoUpdating.editingInterval = null;
                return;
            }
            my.busy = true;
            $.ajax({
                cache: false,
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate('proxy_ip_manager-set_auto_updating_interval')) ?>,
                    interval: my.autoUpdating.editingInterval
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('set_auto_updating_interval')) ?>
            })
            .done(function(data) {
                my.nextAutomaticRun = data.nextAutomaticRun;
                my.autoUpdating.interval = data.interval;
                my.autoUpdating.editingInterval = null;
            })
            .fail(function(xhr, status, error) {
                window.alert(xhr.responseJSON && xhr.responseJSON.error ? (xhr.responseJSON.error.message || xhr.responseJSON.error) : error);
            })
            .always(function() {
                my.busy = false;
            });
        }
    }
});

});
</script>
