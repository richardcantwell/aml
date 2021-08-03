export default {
    init() {
        // JavaScript to be fired on all pages
    },
    finalize() {
        // JavaScript to be fired on all pages, after page specific JS is fired
        $( 'a.idpal_btn_submit_user' ).on( 'click', function(e) {
            e.stopPropagation();
            var btn = $(this);
            alert(aml_vars.ajax_url);
            // console.log(aml_vars.ajax_url);
            // console.log($(this).data('id'));
            var user_id = btn.data('id'); console.log('user_id clicked .. ' + user_id); // alert(user_id);
            btn.text('One moment ...');
            /*$.ajax({
                url: 'https://aml.tynandillon.ie/wp-admin/admin-ajax.php', // aml_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'idpal_btn_submit_user',
                    user_id: user_id,
                },
                success: function( response ) {
                    console.log('response = ' + response);
                    // alert(response);
                    btn.text('Initiated');
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                },
            });*/
            return false;
        });
    },
};
