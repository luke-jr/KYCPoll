function disable_form(form) {
	var to_disable = form.getElementsByTagName('input');
	for (var i = 0; i < to_disable.length; ++i) {
		to_disable[i].readonly = true;
	}
	form.style.opacity = 0.5;
}

function save_answers() {
	var accept_checkbox = document.getElementById('accept_terms');
	if (!accept_checkbox.checked) {
		alert('Please accept the terms first');
		return;
	}
	var button = document.getElementById('save_button');
	button.value = 'Saving...';
	var form = document.getElementById('pollform');
	disable_form(form);
	form.submit();
}

function accept_terms_clicked() {
	var button = document.getElementById('save_button');
	var placeholder = document.getElementById('save_button_placeholder');
	if (button) {
		placeholder.removeChild(button);
	}
	var checkbox = document.getElementById('accept_terms');
	if (!checkbox.checked) {
		return;
	}
	button = document.createElement('input');
	button.id = 'save_button';
	button.type = "button";
	button.value = "Save";
	button.onclick = save_answers;
	while (placeholder.hasChildNodes()) {
	    placeholder.removeChild(placeholder.lastChild);
	}
	placeholder.appendChild(button);
}

window.addEventListener('load', accept_terms_clicked, false);

function do_logout() {
	if (!window.confirm("Any unsaved changes will be lost! Are you sure you want to logout?")) {
		return;
	}
	
	var form = document.getElementById('pollform');
	disable_form(form);
	
	var logout_elem = document.createElement('input');
	logout_elem.type = 'hidden';
	logout_elem.name = 'logout';
	logout_elem.value = '1';
	form.appendChild(logout_elem);
	
	form.submit();
}
