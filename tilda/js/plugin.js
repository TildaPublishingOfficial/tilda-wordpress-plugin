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


        $('#tilda_toggle')
            .click(function (e) {
                var val = $tilda_status.val();
                if (val == 'on') {
                    $tilda_status.val('off');
                } else {
                    $tilda_status.val('on');
                }
                $('#publish').click();
            });

        $('.sync')
            .click(function(e){
                e.preventDefault();
                $tilda_update_page.val('update_page');
                $('#publish').click();
            });

        $('.tilda_edit_page')
            .click(function (e) {
                e.preventDefault();
                $tilda_pages_list.removeClass('close');
            })
    })
})(jQuery);


