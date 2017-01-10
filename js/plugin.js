(function ($) {
    $.fn.getHiddenDimensions = function (includeMargin) {
        var $item = this,
            props = { position: 'absolute', visibility: 'hidden', display: 'block' },
            dim = { width: 0, height: 0, innerWidth: 0, innerHeight: 0, outerWidth: 0, outerHeight: 0 },
            $hiddenParents = $item.parents().andSelf().not(':visible'),
            includeMargin = (includeMargin == null) ? false : includeMargin;

        var oldProps = [];
        $hiddenParents.each(function () {
            var old = {};

            for (var name in props) {
                old[ name ] = this.style[ name ];
                this.style[ name ] = props[ name ];
            }

            oldProps.push(old);
        });

        dim.width = $item.width();
        dim.outerWidth = $item.outerWidth(includeMargin);
        dim.innerWidth = $item.innerWidth();
        dim.height = $item.height();
        dim.innerHeight = $item.innerHeight();
        dim.outerHeight = $item.outerHeight(includeMargin);

        $hiddenParents.each(function (i) {
            var old = oldProps[i];
            for (var name in props) {
                this.style[ name ] = old[ name ];
            }
        });

        return dim;
    }

    $(document).ready(function () {
        var $tilda_projects = $('#tilda_pages_list');
        var $tilda_status = $('input[name="tilda[status]"]');
        var $tilda_project_id = $('input[name="tilda[project_id]"]');
        var $tilda_update_page = $('input[name="tilda[update_page]"]');
        var $tilda_pages_list = $('.tilda_pages_list');
        var $tilda_projects_tabs = $('.tilda_projects_tabs')

        $tilda_projects_tabs
            .tabs()
            .addClass('ui-tabs-vertical ui-helper-clearfix')
            .on('click', '[type="radio"]', function () {
                var $panel = $(this).parents('.ui-tabs-panel').eq(0);

                $tilda_project_id.val($panel.attr('data-project-id'));
            });

        $('.form',$tilda_projects_tabs).height($tilda_projects_tabs.getHiddenDimensions().outerHeight);


        $('#tilda_toggle').click(function (e) {
            e.preventDefault();
                
            var val = $tilda_status.val();
            if (val == 'on') {
                val = 'off';
            } else {
                val = 'on';
            }
            $tilda_status.val(val);

            var data = {
                'action': 'tilda_admin_switcher_status',
                'tilda_status': val,
                'post_id': $('#post_ID').val()
            };
            
            $.post('admin-ajax.php', data, function(json) {
                if(!json || json.error > '') {
                    $('#tilda_switcher').show().find('.errors').append('<li>' + json.error + '</li>');
                } else {
                    window.location.reload();
                }
            }, 'json');
            return false; //$('#publish').click();
        });

        $('.sync')
            .click(function(e){
                e.preventDefault();
                $tilda_update_page.val('update_page');
                savePost();
            });

        $('#tilda_refresh_list').click(function(e){
            e.preventDefault();
            
            if ($('#tilda_update_data').length == 0) {
                $(this).parent().append('<input type="hidden" name="tilda[update_data]" value="update_data" id="tilda_update_data">')
            } else {
                $('#tilda_update_data').val('update_data');
            }

            savePost();

            return false;
        });

        var savePost = function() {
            var $btn = $('#save-post');

            if (!$btn.length) {
                $btn = $('#publish');
            }

            $btn.click();
        };

        $('#tilda_save_page').click(function(e){
            e.preventDefault();
            
            /*
            if ($tilda_update_page.length == 0) {
                $(this).parent().append('<input type="hidden" name="tilda[update_page]" value="update_page">')
            }
            $tilda_update_page.val('update_page');
            
            $('#publish').click();
            */
            var post_id = $(this).data('postid');
            var $tilda_page = $tilda_projects_tabs.find('input[type=radio]:checked');
            if ($tilda_page.length > 0) {
                tilda_start_sync($tilda_project_id.val(), $tilda_page.val(), post_id);
            }
            return false;
        });
        
        function tilda_export_files() {
            var data = {
                action: 'tilda_admin_export_file',
            };
            
            $.post('admin-ajax.php', data, function(json) {
                if(!json || json.error > '') {
                    $('#tilda_block_sync_progress').find('.tilda_sync_label').html(json.error);
                } else if(json.total_download > 0 ){
                    var tilda_count_download = json.count_downloaded;
                    
                    $('#tilda_progress_bar').find('.tilda_block').each(function(){
                        if (tilda_count_download > 0) {
                            if ($(this).hasClass('tilda_file_notloaded')) {
                                $(this).removeClass('tilda_file_notloaded').addClass('tilda_file_loaded');
                            }
                        }
                        tilda_count_download--;
                    });

                    if (json.need_download > 0 ){
                        tilda_export_files();
                    } else {
                        $('#tilda_block_sync_progress').find('.tilda_sync_label').html('Synchronization success. <a href="javascript:window.location.reload()">Refresh page</a>');
                        $('#ajaxsync').removeAttr('disabled').removeClass('disabled');
                        window.location.reload();
                    }
                } else {
                    $('#tilda_block_sync_progress').find('.tilda_sync_label').html('Synchronization success. <a href="javascript:window.location.reload()">Refresh page</a>');
                    $('#ajaxsync').removeAttr('disabled').removeClass('disabled');
                    window.location.reload();
                }
            },'json');
            
        }
        
        function tilda_start_sync($project_id, $page_id, $post_id) {
            var data = {
                action: 'tilda_admin_sync',
                project_id: $project_id,
                page_id: $page_id,
                post_id: $post_id
            };
            
            $('#tilda_progress_bar').hide();
            $('#tilda_block_sync_progress').show();
            $('#tilda_block_sync_progress').find('.tilda_sync_label').html('Sync in progress with Tilda.cc');
            $('#tilda_progress_bar').html('');
            
            $.post('admin-ajax.php', data, function(json) {
                if(!json || json.error > '') {
                    $('#tilda_block_sync_progress').find('.tilda_sync_label').html(json.error);
                } else if(json.total_download > 0 ){
                    var html = '';
                    for(i=0;i<json.total_download;i++) {
                        html += '<div class="tilda_block tilda_file_notloaded"></div>';
                    }
                    $('#tilda_progress_bar').html(html).show();
                    
                    tilda_export_files();
                } else {
                    $('#tilda_block_sync_progress').find('.tilda_sync_label').html('Synchronization success. <a href="javascript:window.location.reload()">Refresh page</a>');
                }
            },'json');
        }
        
        $('#ajaxsync').click(function(){
            var $project_id = $(this).data('projectid');
            var $page_id = $(this).data('pageid');
            var $post_id = $(this).data('postid');
            $(this).attr('disabled', 'disabled').addClass('disabled');
            
            tilda_start_sync($project_id, $page_id, $post_id);
            return false;
        });
        
        $('.tilda_edit_page')
            .click(function (e) {
                e.preventDefault();
                $tilda_pages_list.removeClass('close');
            });
    })
})(jQuery);


