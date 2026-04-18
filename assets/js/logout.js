function logout() {
    $.get('/api/logout.php').done(function () {
        location.reload()
    })
}