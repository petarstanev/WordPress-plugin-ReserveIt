jQuery(document).ready(function($) {

    jQuery('#datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });

          
           jQuery('#reserveit_user_datepicker').datepicker({
        dateFormat: 'dd-mm-yy',
        minDate: new Date
    });

     jQuery('#reserveit_expand_datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });     
        
     jQuery('#reserveit_admin_add_datepicker').datepicker({
        dateFormat: 'dd-mm-yy',
        minDate: new Date
    });
    

   

    jQuery('#reserveit_all_reservations_datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });

    jQuery('#timepicker').timepicker({
        stepMinute: 30
    });

    jQuery('#reserveit_admin_add_timepicker').timepicker({
        stepMinute: 30
    });


});
