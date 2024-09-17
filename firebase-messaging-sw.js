importScripts("https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js");

firebase.initializeApp({
    apiKey: "AIzaSyD_5IV9n49aq0nqj8k1mU2Bhus5YJn_Rbc",
    authDomain: "myride-taxi-prod.firebaseapp.com",
    databaseURL: "https://myride-taxi-prod-default-rtdb.firebaseio.com",
    projectId: "myride-taxi-prod",
    storageBucket: "myride-taxi-prod.appspot.com",
    messagingSenderId: "553189906417",
    appId: "1:553189906417:web:2998df0e9643e1fdf01650"
});

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function (payload) {
    const promiseChain = clients
        .matchAll({
            type: "window",
            includeUncontrolled: true
        })
        .then(windowClients => {
            for (let i = 0; i < windowClients.length; i++) {
                const windowClient = windowClients[i];
                windowClient.postMessage(payload);
            }
        })
        .then(() => {
            const title = payload.notification.title;
            const options = {
                body: payload.notification.score
              };
            return registration.showNotification(title, options);
        });
    return promiseChain;
});
self.addEventListener('notificationclick', function (event) {
    console.log('notification received: ', event)
});