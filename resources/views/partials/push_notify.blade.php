<script src="{{asset('assets/global/js/firebase/firebase-8.3.2.js')}}"></script>

<script>
    "use strict";   
    
    var permission = null;
    var authenticated = "{{ @userGuard()['type'] }}";
    var pushNotify = @json($general->pn);
    var firebaseConfig = @json($general->firebase_config);
 
    function pushNotifyAction(){ 
        permission = Notification.permission;

        if(!('Notification' in window)){
            notify('info', 'Push notifications not available in your browser. Try Chromium.')
        } 
        else if(permission === 'denied' || permission == 'default'){ //Notice for users dashboard 
            $('.push_notice').append(` 
                <div class='card mb-4'>
                    <div class="d-user-notification d-flex flex-wrap align-items-center">
                        <div class="icon text--warning">
                            <i class="las la-bell-slash"></i>
                        </div>
                        <div class="content">
                            <p class="text-white fw--bold">@lang('Please Allow / Reset Browser Notification').</p>
                        </div>
                    </div>
                    <div class='card-body text-center'>
                        @lang('If you want to get push notification then you have to allow notification from your browser')
                    </div>
                </div>
            `);
        }
    }

    //If enable push notification from admin panel
    if(pushNotify == 1){
        pushNotifyAction();
    }

    //When users allow browser notification
    if(permission != 'denied' && firebaseConfig){ 
   
        //Firebase 
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        navigator.serviceWorker.register("{{ asset('assets/global/js/firebase/firebase-messaging-sw.js') }}")
        
        .then((registration) => {
            messaging.useServiceWorker(registration);
            
            function initFirebaseMessagingRegistration() {
                messaging
                .requestPermission()
                .then(function () {
                    return messaging.getToken()
                })
                .then(function (token){   
                    $.ajax({ 
                        url: '{{ route("push.device.token") }}',
                        type: 'POST',
                        data: {
                            token: token,
                            '_token': "{{ csrf_token() }}"
                        },
                        success: function(response){
                            // console.log(response);
                        },
                        error: function (err) {
                            // console.log('User Chat Token Error'+ err);
                        },
                    });
                }).catch(function (error){
                    // console.warn(error);
                });
            }

            messaging.onMessage(function (payload){ 
                const title = payload.notification.title;
                const options = {
                    body: payload.notification.body,
                    icon: payload.notification.icon, 
                    click_action:payload.notification.click_action,
                    vibrate: [200, 100, 200]
                };
                new Notification(title, options);
            });

            //For authenticated users
            if(authenticated){
                initFirebaseMessagingRegistration();
            }

        });

    }
</script>