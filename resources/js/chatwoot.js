window.addEventListener("message", function (event) {
    console.log(event.data);
});

window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*')
