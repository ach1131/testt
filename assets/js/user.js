function changeuser(type, input) {
	const id = document.getElementById('userID')
	const elm = document.getElementById(input)

	$.post('/api/user/changeuser.php', {
		'id': id.value,
		'value': elm.value,
		'type': type,
	}).done(function(data){
		if (data.error) {
			round_warning_noti(data.message);
		} else {
			round_success_noti(data.message);
			updatePage();
		}
	}).catch(function(data){
		round_error_noti(`Ошибка на стороне сервера. Попробуйте позже`);
		console.log(data)
	})
}

function kickAdmin(vkid) {
	const reason = document.getElementById('kickReason')
	const hide = document.getElementById('kickHide')

	$.post('/api/user/kickadm.php', {
		'vkid': vkid,
		'reason': reason.value,
		'hide': hide.value 
	}).done(function(data) {
		const error = document.getElementById('error')
		if (data.error) {
			round_warning_noti(data.message);
		} else {
			document.getElementById('firstKick').hidden = true
			document.getElementById('acceptKick').hidden = true
			document.getElementById('acceptSendArchive').hidden = false
			document.getElementById('admArchive').hidden = false
			document.getElementById('archiveText').textContent = data.archive
			round_success_noti(data.message);
		}
	}).catch(function(data){
		round_error_noti(`Ошибка на стороне сервера. Попробуйте позже`);
		console.log(data)
	})
}

function updateuser() {
	$.post('/api/user/update.php', {
		'id': document.getElementById('userID').value,
		'name': document.getElementById('inputNick').value.trim(),
		'server': document.getElementById('inputServer').value,
		'lvl': document.getElementById('inputLvl').value,
		'post': document.getElementById('inputPost').value.trim(),
		'tag': document.getElementById('inputPrefix').value.trim(),
		'forum': document.getElementById('inputForum').value.trim(),
		'discord': document.getElementById('inputDiscord').value.trim(),
		'plusrep': document.getElementById('inputPlusrep').value,
		'type': document.getElementById('inputType').value,
		'ndate': document.getElementById('inputAssign').value,
		'oldate': document.getElementById('inputUpdate').value,
		'access': document.getElementById('inputAccess').value,
		'reprimand': document.getElementById('inputReprimand').value,
		'reprimandreason': document.getElementById('inputReprimandReason').value.trim(),
		'warn': document.getElementById('inputWarn').value,
		'warnreason': document.getElementById('inputWarnReason').value.trim(),
		'country': document.getElementById('inputCountry').value.trim(),
		'realname': document.getElementById('inputRealname').value.trim(),
		'city': document.getElementById('inputCity').value.trim(),
		'birth': document.getElementById('inputBirth').value,
		'note': document.getElementById('inputNote').value.trim()
	}).done(function(data) {
		const error = document.getElementById('error')
		if (data.error) {
			round_warning_noti(data.message);
		} else {
			round_success_noti('Пользователь успешно обновлен!');
			updatePage();
		}
	}).catch(function(data){
		round_error_noti(`Ошибка на стороне сервера. Попробуйте позже`);
		console.log(data)
	})
}