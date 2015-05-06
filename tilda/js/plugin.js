(function ($) {
    $(document).ready(function () {
        var $tilda_projects = $('#tilda_pages_list');
        var $tilda_status = $('input[name="tilda[status]"]');
        var $tilda_project_id = $('input[name="tilda[project_id]"]');
        var $tilda_pages_list = $('.tilda_pages_list');

        $('.tilda_projects_tabs')
            .tabs()
            .addClass('ui-tabs-vertical ui-helper-clearfix')
            .on('click','[type="radio"]',function(){
                var $panel = $(this).parents('.ui-tabs-panel').eq(0);

                $tilda_project_id.val($panel.attr('data-project-id'));
            });

        $('#tilda_toggle')
            .click(function (e) {
                var val = $tilda_status.val();
                if (val == 'on'){
                    $tilda_status.val('off');
                    $('#submit').click();
                }else{
                    $tilda_status.val('on');
                }

            })

        $('.tilda_edit_page').click(function(e){
            e.preventDefault();
            $tilda_pages_list.removeClass('close');
        })
    })
})(jQuery);


