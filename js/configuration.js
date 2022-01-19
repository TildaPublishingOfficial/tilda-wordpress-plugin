;(function (w, d, $) {

    $(d).ready(function () {

        var $messagesTab = {
            success: $('#success_tab'),
            error: $('#error_tab')
        };

        var $commonSettingsForm = {
            enabledposttypes: $('#enabledposttypes').find('input'),
            storageforfiles: $('#storageforfiles')
        };

        var $addKeyButton = {self: $('#tilda_add_key')};

        var $addKeyForm = {
            self: $('#tilda_add_key_table'),
            public_key: $('#public_key'),
            secret_key: $('#secret_key'),
            store_html_only: $('#store_html_only'),
            apply_css_in_list: $('#apply_css_in_list'),
            waiting: $('#tilda_add_key_waiting')
        };

        var $keyTable = {
            self: $('#tilda_keys_table'),
            tbody: $('#tilda_keys_table_body')
        };

        var $projectTable = {
            self: $('#tilda-projects-table'),
            tbody: $('#tilda-projects-table-body')
        }

        var $webhookUrl = {
            self: $('#webhook_url'),
            container: $('#webhook_url_container'),
            icon: $('.dashicons-admin-page')
        }

        function tildaTranslate(text) {
            if (tilda_localize.hasOwnProperty(text)) {
                return tilda_localize[text];
            }
            return text;
        }

        var imgSwitcherOn = 'https://front.tildacdn.com/feeds/img/t-icon-switcher-on.png';
        var imgSwitcherOff = 'https://front.tildacdn.com/feeds/img/t-icon-switcher-off.png';

        function showErrorText(text) {
            hideSuccessText();
            $messagesTab.error.html('<p>' + text + '</p>');
            $messagesTab.error.show();
            w.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showSuccessText(text) {
            hideErrorText();
            $messagesTab.success.html('<p>' + text + '</p>');
            $messagesTab.success.show();
            w.scrollTo({top: 0, behavior: 'smooth'});
        }

        function hideErrorText() {
            $messagesTab.error.hide();
        }

        function hideSuccessText() {
            $messagesTab.success.hide();
        }

        function htmlKeyTable(data) {
            var html = '';
            if (data.length === 0) {
                return '<tr><td colspan="5" align="center">' + tildaTranslate('No added keys yet') + '</td></tr>';
            }
            $.each( data , function( key, value ) {
                var store_checked = (value.store_html_only) ? 'checked="checked"' : '';
                var apply_css_in_list = (value.apply_css_in_list) ? 'checked="checked"' : '';
                var store_switcher = ((value.store_html_only) ? imgSwitcherOn : imgSwitcherOff);
                var apply_css_switcher = ((value.apply_css_in_list) ? imgSwitcherOn : imgSwitcherOff);
                html += ''
                    + '<tr>'
                    +   '<td>' + value.public_key + '</td>'
                    +   '<td>' + value.secret_key + '</td>'
                    +   '<td><img src="' + store_switcher + '" width="40px" onclick="switchStore(this,\'' + value.id + '\')"><input id="store_checkbox_' + value.id + '" style="display: none" type="checkbox" '+ store_checked +'/></td>'
                    +   '<td><img src="' + apply_css_switcher + '" width="40px" onclick="switchApplyCss(this,\'' + value.id + '\')"><input id="apply_css_checkbox_' + value.id + '" type="checkbox" '+ apply_css_in_list +' style="display: none"/></td>'
                    +   '<td>'
                    +       '<span class="dashicons dashicons-image-rotate" onclick="onClickRefreshKey(\'' + value.id + '\')"> </span>'
                    +       '<span class="dashicons dashicons-trash tilda-delete-key" onclick="onClickDeleteKey(\'' + value.id + '\')"> </span>'
                    +   '</td>'
                    + '<tr>';
            });
            return html;
        }

        function htmlProjectTable(data) {
            var html = '';
            if (data.length === 0) {
                return '<tr><td colspan="2" align="center">' + tildaTranslate('No available projects') + '</td></tr>';
            }
            $.each(data, function (key, value) {
                var enable_switcher = ((value.enabled) ? imgSwitcherOn : imgSwitcherOff);
                var project_enabled = (value.enabled) ? 'checked="checked"' : '';
                html += ''
                    + '<tr>'
                    +   '<td>' + value.id + ' ' + value.title + '</td>'
                    +   '<td><img src="' + enable_switcher + '" width="40px" onclick="switchEnableProject(this,\'' + value.id + '\')"><input id="project_enable_checkbox_' + value.id + '" type="checkbox" ' + project_enabled + ' style="display: none"/></td>'
                    + '<tr>';
            });
            return html;
        }

        function htmlProjectTableWaiting() {
            var pluginUrl = (window.tilda_plugin_url !== undefined) ? window.tilda_plugin_url : '/wp-content/plugins/tilda-publishing/';
            return ''
                + '<tr>'
                +   '<td colspan="5" align="center">'
                +       '<img width="32" height="32" src="'+pluginUrl+'images/ajax-loader.gif" alt="Loading" />'
                +   '</td>'
                + '</tr>'
                + '';
        }

        w.onClickDeleteKey = function (id) {
            if (!confirm(tildaTranslate('Are you sure, you want to delete API key?'))) {
                return;
            }
            ajaxKeyDelete(id).then(function (data) {
                showSuccessText('Success, key was deleted');
                refreshKeyTable(data);
                refreshProjectTable();
            });
        }

        w.onClickRefreshKey = function (id) {
            ajaxKeyRefresh(id).then(function (data) {
                //showSuccessText('Success, key was refreshed');
                refreshProjectTable();
            });
        }

        w.switchStore = function (element, id) {
            var checkbox = $('#store_checkbox_' + id);
            var current = checkbox.prop('checked');
            $('#store_checkbox_' + id).prop('checked', !current);
            $(element).attr('src', (!current) ? imgSwitcherOn : imgSwitcherOff);
            ajaxChangeKey(id, 'store_html_only', !current);
        }

        w.switchApplyCss = function (element, id) {
            var checkbox = $('#apply_css_checkbox_' + id);
            var current = checkbox.prop('checked');
            $('#apply_css_checkbox_' + id).prop('checked', !current);
            $(element).attr('src', (!current) ? imgSwitcherOn : imgSwitcherOff);
            ajaxChangeKey(id, 'apply_css_in_list', !current);
        }

        w.switchEnableProject = function (element, id) {
            var checkbox = $('#project_enable_checkbox_' + id);
            var current = checkbox.prop('checked');
            $('#project_enable_checkbox_' + id).prop('checked', !current);
            $(element).attr('src', (!current) ? imgSwitcherOn : imgSwitcherOff);
            ajaxChangeProjectEnabled(id, !current);
        }

        function copyToClipboard() {
            var copyTextarea = d.querySelector('#webhook_url');
            copyTextarea.focus();
            copyTextarea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showSuccessText(tildaTranslate('Url copied to the clipboard'))
                }
            } catch (err) {
                console.warn(tildaTranslate("Can't copy url to the clipboard"));
            }
        }

        function refreshProjectTable(data) {
            if (typeof data === 'undefined') {
                $projectTable.tbody.html(htmlProjectTableWaiting());
                ajaxGetProjectList().then(function (data) {
                    $projectTable.tbody.html(htmlProjectTable(data));
                });
            } else {
                $projectTable.tbody.html(htmlProjectTable(data));
            }
        }

        function refreshKeyTable(data) {
            if (typeof data === 'undefined') {
                ajaxGetKeyList().then(function (data) {
                    $keyTable.tbody.html(htmlKeyTable(data));
                });
            } else {
                $keyTable.tbody.html(htmlKeyTable(data));
            }
        }

        function ajaxGetKeyList() {
            return new Promise(function (resolve) {
                $.post(w.ajaxurl, {action: 'get_keys'}, resolve);
            });
        }

        function ajaxGetProjectList() {
            return new Promise(function (resolve) {
                $.get(w.ajaxurl, {action: 'get_projects'}, resolve);
            });
        }

        function ajaxKeyRefresh(id) {
            return new Promise(function (resolve) {
                $.get(w.ajaxurl, {action: 'refresh_key', id: id}, resolve);
            });
        }

        function ajaxKeyDelete(id) {
            return new Promise(function (resolve) {
                $.get(w.ajaxurl, {action: 'delete_key', id: id}, resolve);
            });
        }

        function ajaxChangeKey(id, param_name, param_value) {
            var data = {action: 'update_key', id: id};
            data[param_name] = param_value;
            return new Promise(function (resolve) {
                $.get(w.ajaxurl, data, resolve);
            });
        }

        function ajaxChangeProjectEnabled(id, newvalue) {
            var data = {action: 'update_project', id: id, enabled: newvalue};
            return new Promise(function (resolve) {
                $.post(w.ajaxurl, data, resolve);
            });
        }

        function ajaxAddNewKey(public_key, secret_key, store_html_only, apply_css_in_list){
            var data = {
                action: 'add_new_key',
                public_key: public_key,
                secret_key: secret_key,
                store_html_only: store_html_only,
                apply_css_in_list: apply_css_in_list,
            }
            return new Promise(function(resolve, reject){
                $.post(w.ajaxurl, data)
                    .done(resolve)
                    .fail(reject);
            });

        }

        function ajaxSaveCommonSettings() {
            var enabledposttypes = [];
            $.each( $commonSettingsForm.enabledposttypes , function( key, value ) {
                if (value.checked){
                    enabledposttypes.push(value.value);
                }
            });

            var data = {
                action: 'tilda_admin_update_common_settings',
                enabledposttypes: enabledposttypes,
                storageforfiles: $commonSettingsForm.storageforfiles.val()
            };

            return new Promise(function (resolve) {
                $.post(w.ajaxurl, data, resolve);
            });
        }

        function onClickSwitcher(event){
            var self = $(event.target);
            var newValue = (self.attr('data-on') === '1') ? '0' : '1';
            var icon = (newValue === '1') ? 't-icon-switcher-on.png' : 't-icon-switcher-off.png';
            self.attr('src', 'https://front.tildacdn.com/feeds/img/' + icon);
            self.attr('data-on', newValue);
        }

        function onChangeCommonSettings() {
            ajaxSaveCommonSettings().then(function (data) {
                showSuccessText(tildaTranslate('Common settings successfully updated'))
            });
        }

        function onClickAddNewKey() {
            $addKeyForm.waiting.show();
            $addKeyForm.self.hide();
            ajaxAddNewKey(
                $addKeyForm.public_key.val(),
                $addKeyForm.secret_key.val(),
                $addKeyForm.store_html_only.attr('data-on'),
                $addKeyForm.apply_css_in_list.attr('data-on')
            ).then(
                function (data) {
                    $addKeyForm.waiting.hide();
                    $addKeyButton.self.show();
                    showSuccessText(tildaTranslate('Success, new key has been added!'))
                    refreshKeyTable(data);
                    refreshProjectTable();
                },
                function (error) {
                    $addKeyForm.waiting.hide();
                    $addKeyForm.self.show();
                    showErrorText(tildaTranslate("Can't add this key"));
                    console.error('error', error);
                });
        }

        /** Start */

        refreshKeyTable();
        refreshProjectTable();

        $addKeyButton.self.click(function (e) {
            $addKeyButton.self.hide();
            $addKeyForm.self.show();
        });

        $commonSettingsForm.storageforfiles.on('change' ,onChangeCommonSettings);
        $.each($commonSettingsForm.enabledposttypes, function(key, value){
            $(value).on('change',onChangeCommonSettings);
        });

        $('#save_new_key').click(onClickAddNewKey);

        $('#tilda-tooltip').click(function(e){
            copyToClipboard('#tilda-callback-url');
        });

        $webhookUrl.self.on('click', copyToClipboard);
        $webhookUrl.icon.on('click', copyToClipboard);

        $('#store_html_only').on('click', onClickSwitcher);
        $('#apply_css_in_list').on('click', onClickSwitcher);

    });
})(window, document, jQuery);


