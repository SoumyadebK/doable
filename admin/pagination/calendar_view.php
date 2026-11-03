<?php
require_once('../../global/config.php');
$DEFAULT_LOCATION_ID = 1;
?>
<script>
    let defaultResources = [
        <?php
        if ($DEFAULT_LOCATION_ID > 0){
            $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
        } else {
            $service_provider_data = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
        }
        $resourceIdArray = [];
        while (!$service_provider_data->EOF) { $resourceIdArray[] = $service_provider_data->fields['PK_USER'];?>
        {
            id: <?=$service_provider_data->fields['PK_USER']?>,
            title: '<?=$service_provider_data->fields['NAME'].' - 0'?>',
        },
        <?php $service_provider_data->MoveNext();
        } $resourceIdArray = json_encode($resourceIdArray)?>
    ];

    let appointmentArray = [
        <?php
        if ($DEFAULT_LOCATION_ID > 0){
            $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER INNER JOIN DOA_USER_LOCATION ON SERVICE_PROVIDER.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC LIMIT 500");
        } else {
            $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC LIMIT 500");
        }
        while (!$appointment_data->EOF) { ?>
        {
            id: <?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>,
            resourceId: <?=$appointment_data->fields['SERVICE_PROVIDER_ID']?>,
            title: '<?=$appointment_data->fields['CUSTOMER_NAME'].' ('.$appointment_data->fields['SERVICE_NAME'].'-'.$appointment_data->fields['SERVICE_CODE'].') '.'\n'.$appointment_data->fields['ENROLLMENT_ID'].' - '.$appointment_data->fields['SERIAL_NUMBER'].(($appointment_data->fields['IS_PAID'] == 0)?' (Unpaid)':' (Paid)')?>',
            start: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($appointment_data->fields['END_TIME']))?>,1,1),
            color: '<?=$appointment_data->fields['COLOR_CODE']?>',
            type: 'appointment',
        },
        <?php $appointment_data->MoveNext();
        } ?>
    ];

    let specialAppointmentArray = [
        <?php $special_appointment_data = $db->Execute("SELECT DOA_SPECIAL_APPOINTMENT.*, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM `DOA_SPECIAL_APPOINTMENT` LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS WHERE DOA_SPECIAL_APPOINTMENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        while (!$special_appointment_data->EOF) { ?>
        {
            id: <?=$special_appointment_data->fields['PK_SPECIAL_APPOINTMENT']?>,
            resourceId: 0,
            title: '<?=$special_appointment_data->fields['TITLE']?>',
            start: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['START_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($special_appointment_data->fields['DATE']))?>,<?=intval((date("m",strtotime($special_appointment_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($special_appointment_data->fields['DATE'])))?>,<?=date("H",strtotime($special_appointment_data->fields['END_TIME']))?>,<?=date("i",strtotime($special_appointment_data->fields['END_TIME']))?>,1,1),
            color: '<?=$special_appointment_data->fields['COLOR_CODE']?>',
            type: 'special_appointment',
        },
        <?php $special_appointment_data->MoveNext();
        } ?>
    ];

    let groupClassArray = [
        <?php
        if ($DEFAULT_LOCATION_ID > 0){
            $standing_data = $db->Execute("SELECT DOA_GROUP_CLASS.PK_GROUP_CLASS, DOA_GROUP_CLASS.SERVICE_PROVIDER_ID_1, DOA_GROUP_CLASS.SERVICE_PROVIDER_ID_2, DOA_GROUP_CLASS.DATE, DOA_GROUP_CLASS.START_TIME, DOA_GROUP_CLASS.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_GROUP_CLASS.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_GROUP_CLASS LEFT JOIN DOA_SERVICE_MASTER ON DOA_GROUP_CLASS.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_GROUP_CLASS.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_SERVICE_CODE ON DOA_GROUP_CLASS.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_USER_LOCATION.PK_LOCATION = '$DEFAULT_LOCATION_ID' AND DOA_GROUP_CLASS.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        } else {
            $standing_data = $db->Execute("SELECT DOA_GROUP_CLASS.PK_GROUP_CLASS, DOA_GROUP_CLASS.SERVICE_PROVIDER_ID_1, DOA_GROUP_CLASS.SERVICE_PROVIDER_ID_2, DOA_GROUP_CLASS.DATE, DOA_GROUP_CLASS.START_TIME, DOA_GROUP_CLASS.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_GROUP_CLASS.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_GROUP_CLASS LEFT JOIN DOA_SERVICE_MASTER ON DOA_GROUP_CLASS.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_GROUP_CLASS.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN DOA_SERVICE_CODE ON DOA_GROUP_CLASS.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_GROUP_CLASS.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        }
        while (!$standing_data->EOF) { ?>
        {
            id: <?=$standing_data->fields['PK_GROUP_CLASS']?>,
            resourceId: <?=$standing_data->fields['SERVICE_PROVIDER_ID_1']?>,
            title: '<?=$standing_data->fields['SERVICE_NAME'].' - '.$standing_data->fields['SERVICE_CODE']?>',
            start: new Date(<?=date("Y",strtotime($standing_data->fields['DATE']))?>,<?=intval((date("m",strtotime($standing_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($standing_data->fields['DATE'])))?>,<?=date("H",strtotime($standing_data->fields['START_TIME']))?>,<?=date("i",strtotime($standing_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($standing_data->fields['DATE']))?>,<?=intval((date("m",strtotime($standing_data->fields['DATE'])) - 1))?>,<?=intval(date("d",strtotime($standing_data->fields['DATE'])))?>,<?=date("H",strtotime($standing_data->fields['END_TIME']))?>,<?=date("i",strtotime($standing_data->fields['END_TIME']))?>,1,1),
            color: '<?=$standing_data->fields['COLOR_CODE']?>',
            type: 'group_class',
        },
        <?php $standing_data->MoveNext();
        } ?>
    ];

    let eventArray = [
        <?php $event_data = $db->Execute("SELECT DOA_EVENT.*, DOA_EVENT_TYPE.EVENT_TYPE, DOA_EVENT_TYPE.COLOR_CODE FROM DOA_EVENT LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE WHERE DOA_EVENT.ACTIVE = 1 AND DOA_EVENT.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' LIMIT 500");
        while (!$event_data->EOF) {
        $END_DATE = ($event_data->fields['END_DATE'] == '0000-00-00')?$event_data->fields['START_DATE']:$event_data->fields['END_DATE'];
        $END_TIME = ($event_data->fields['END_TIME'] == '00:00:00')?$event_data->fields['START_TIME']:$event_data->fields['END_TIME'];
        $open_close_time_diff = (strtotime($CLOSE_TIME) - strtotime($OPEN_TIME)) - 1800;
        $start_end_time_diff = strtotime($END_DATE.' '.$END_TIME) - strtotime($event_data->fields['START_DATE'].' '.$event_data->fields['START_TIME']);?>
        {
            id: <?=$event_data->fields['PK_EVENT']?>,
            resourceIds: <?=$resourceIdArray?>,
            title: '<?=$event_data->fields['HEADER']?>',
            start: new Date(<?=date("Y",strtotime($event_data->fields['START_DATE']))?>,<?=intval((date("m",strtotime($event_data->fields['START_DATE'])) - 1))?>,<?=intval(date("d",strtotime($event_data->fields['START_DATE'])))?>,<?=date("H",strtotime($event_data->fields['START_TIME']))?>,<?=date("i",strtotime($event_data->fields['START_TIME']))?>,1,1),
            end: new Date(<?=date("Y",strtotime($END_DATE))?>,<?=intval((date("m",strtotime($END_DATE)) - 1))?>,<?=intval(date("d",strtotime($END_DATE)))?>,<?=date("H",strtotime($END_TIME))?>,<?=date("i",strtotime($END_TIME))?>,1,1),
            color: '<?=$event_data->fields['COLOR_CODE']?>',
            type: 'event',
            allDay: '<?=($start_end_time_diff >= $open_close_time_diff)?>'
        },
        <?php $event_data->MoveNext();
        } ?>
    ];

    let finalArray = appointmentArray.concat(eventArray).concat(specialAppointmentArray).concat(groupClassArray);

    document.addEventListener('DOMContentLoaded', function() {
        let open_time = '<?=$OPEN_TIME?>';
        let close_time = '<?=$CLOSE_TIME?>';
        let clickCount = 0;
        $('#calendar').fullCalendar({
            schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
            defaultView: 'agendaDay',
            minTime: open_time,
            maxTime: close_time,
            slotDuration: '00:30:00',
            slotLabelInterval: 30,
            slotMinutes: 30,
            //defaultDate: '2016-01-07',
            editable: true,
            selectable: true,
            eventLimit: true, // allow "more" link when too many events
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaDay,agendaTwoDay,agendaWeek,month'
            },
            views: {
                agendaTwoDay: {
                    type: 'agenda',
                    duration: { days: 2 },

                    // views that are more than a day will NOT do this behavior by default
                    // so, we need to explicitly enable it
                    groupByResource: true

                    //// uncomment this line to group by day FIRST with resources underneath
                    //groupByDateAndResource: true
                },
                day: {
                    titleFormat: 'dddd, MMMM Do YYYY'
                }
            },
            /*viewRender: function(view) {
                if(view.type == 'agendaDay') {
                    $('#calendar').fullCalendar( 'removeEventSource', ev1 );
                    $('#calendar').fullCalendar( 'addEventSource', ev2 );
                    return;
                } else {
                    $('#calendar').fullCalendar( 'removeEventSource', ev2 );
                    $('#calendar').fullCalendar( 'addEventSource', ev1 );
                    return;
                }
            },*/

            //// uncomment this line to hide the all-day slot
            //allDaySlot: false,

            resources: defaultResources /*[
                { id: 'a', title: 'Room A' },
                { id: 'b', title: 'Room B', eventColor: 'green' },
                { id: 'c', title: 'Room C', eventColor: 'orange' },
                { id: 'd', title: 'Room D', eventColor: 'red' }
            ]*/,
            events: finalArray,

            eventClick: function(info) {
                showAppointmentEdit(info);
                // window.location.href = "add_schedule.php?id="+info.id;
                //viewAppointmentDetails(info);
            },

            select: function(start, end, jsEvent, view, resource) {
                console.log(
                    'select',
                    start.format(),
                    end.format(),
                    resource ? resource.id : '(no resource)'
                );
            },
            dayClick: function(date, jsEvent, view, resource) {
                clickCount++;
                let singleClickTimer;
                if (clickCount === 1) {
                    singleClickTimer = setTimeout(function () {
                        clickCount = 0;
                    }, 400);
                } else if (clickCount === 2) {
                    clearTimeout(singleClickTimer);
                    clickCount = 0;
                    //window.location.href = "add_schedule.php";
                    openModel();
                }
                console.log(
                    'dayClick',
                    date.format(),
                    resource ? resource.id : '(no resource)'
                );
            }
        });


        $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});

        $('.fc-agendaDay-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});
        });
        $('.fc-agendaTwoDay-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});
        });
        $('.fc-agendaWeek-button').click(function () {
            $('.fc-body').css({"overflow-y":"scroll", "height":"600px", "display":"block"});
        });
        $('.fc-month-button').click(function () {
            $('.fc-body').css({"overflow-y":"", "height":"", "display":""});
        });

        getServiceProviderAppointmentCount();
        $('.fc-prev-button').click(function () {
            getServiceProviderAppointmentCount();
        });
        $('.fc-next-button').click(function () {
            getServiceProviderAppointmentCount();
        });
        $('.fc-today-button').click(function () {
            getServiceProviderAppointmentCount();
        });
    });

    function getServiceProviderAppointmentCount() {
        let currentDate = new Date($('#calendar').fullCalendar('getDate'));
        let day = currentDate.getDate();
        let month = currentDate.getMonth() + 1;
        let year = currentDate.getFullYear();

        let all_service_provider = $('.fc-resource-cell').map(function(){
            return $(this).data('resource-id');
        }).get();

        console.log(currentDate, all_service_provider);

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'getServiceProviderAppointmentCount', currentDate:year+'-'+month+'-'+day, all_service_provider:all_service_provider},
            async: false,
            cache: false,
            success: function (result) {
                let appointment_data = JSON.parse(result);
                for(let i=0; i<appointment_data.length; i++) {
                    $('.fc-resource-cell[data-resource-id="'+appointment_data[i].SERVICE_PROVIDER_ID+'"]').text(appointment_data[i].SERVICE_PROVIDER_NAME+' - '+appointment_data[i].APPOINTMENT_COUNT);
                }
            }
        });
    }
</script>
