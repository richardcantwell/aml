export default {
    init() {
        // JavaScript to be fired on all pages
    },
    finalize() {
        // JavaScript to be fired on all pages, after page specific JS is fired
        $( 'a.idpal_btn_submit_user' ).on( 'click', function(e) {
            e.stopPropagation();
            var btn = $(this);
            // console.log(aml_vars.ajax_url);
            // console.log($(this).data('id'));
            var user_id = btn.data('id');
            btn.text('..');
            $.ajax({
                url: 'https://aml.loc/wp/wp-admin/admin-ajax.php', // aml_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'idpal_btn_submit_user',
                    user_id: user_id,
                },
                success: function( response ) {
                    console.log('response = ' + response);
                    btn.text('Go');
                },
            });
            return false;
        });
    },
};